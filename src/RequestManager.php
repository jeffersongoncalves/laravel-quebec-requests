<?php

namespace JeffersonGoncalves\QuebecRequests;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\Quebec\Contracts\HasPersonalData;
use JeffersonGoncalves\Quebec\Quebec;
use JeffersonGoncalves\QuebecRequests\Contracts\ResolvesPerson;
use JeffersonGoncalves\QuebecRequests\Enums\RequestStatus;
use JeffersonGoncalves\QuebecRequests\Enums\RequestType;
use JeffersonGoncalves\QuebecRequests\Events\PrivacyRequestClosed;
use JeffersonGoncalves\QuebecRequests\Events\PrivacyRequestOpened;
use JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest;
use LogicException;

class RequestManager
{
    public function __construct(protected Quebec $quebec) {}

    /**
     * @param  array{name?: string|null, email?: string|null, details?: string|null}  $requester
     */
    public function open(RequestType $type, ?Model $subject = null, array $requester = []): PrivacyRequest
    {
        $request = PrivacyRequest::create([
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
            'requester_name' => $requester['name'] ?? null,
            'requester_email' => $requester['email'] ?? null,
            'details' => $requester['details'] ?? null,
            'type' => $type,
            'status' => RequestStatus::Pending,
            'due_at' => now()->add(CarbonInterval::make((string) config('quebec-requests.deadline', '30 days'))),
        ]);

        event(new PrivacyRequestOpened($request));

        return $request;
    }

    /**
     * Mark the requester's identity as checked. Required before fulfil(): never export or erase on an unverified request.
     */
    public function verify(PrivacyRequest $request, ?Model $subject = null): PrivacyRequest
    {
        $request->forceFill(array_filter([
            'verified_at' => now(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
        ]))->save();

        return $request;
    }

    /**
     * Run the automatic types (access, portability, anonymization, deletion) and complete the request.
     */
    public function fulfil(PrivacyRequest $request, ?string $response = null): PrivacyRequest
    {
        if (! $request->isOpen()) {
            throw new LogicException("Request [{$request->getKey()}] is already closed.");
        }

        if (! $request->isVerified()) {
            throw new LogicException("Verify the requester's identity before fulfilling request [{$request->getKey()}].");
        }

        if (! $request->type->isAutomatic()) {
            throw new LogicException("Requests of type [{$request->type->value}] are answered by hand: call complete() when done.");
        }

        $subject = $request->subject;

        if (! $subject instanceof Model) {
            throw new LogicException("Request [{$request->getKey()}] has no person: pass one to verify().");
        }

        match ($request->type) {
            RequestType::Access, RequestType::Portability => $request->export_path = $this->exportToFile($subject, "request-{$request->getKey()}.json"),
            default => $this->erase($subject),
        };

        return $this->complete($request, $response);
    }

    /**
     * Extend the deadline once, only when quebec-requests.extension is set: the private sector act
     * has none (s. 32), so by default this refuses.
     */
    public function extend(PrivacyRequest $request, string $reason): PrivacyRequest
    {
        $extension = config('quebec-requests.extension');

        if (! is_string($extension) || $extension === '') {
            throw new LogicException('The private sector act sets no extension: answer within 30 days (s. 32).');
        }

        if (! $request->isOpen() || $request->extended_at !== null) {
            throw new LogicException("Request [{$request->getKey()}] is closed or was already extended.");
        }

        if ($request->due_at->isPast()) {
            throw new LogicException("Request [{$request->getKey()}] is past its deadline: extend it within the initial period.");
        }

        $request->forceFill([
            'due_at' => $request->due_at->copy()->add(CarbonInterval::make($extension)),
            'extended_at' => now(),
            'extension_reason' => $reason,
        ])->save();

        return $request;
    }

    public function complete(PrivacyRequest $request, ?string $response = null): PrivacyRequest
    {
        return $this->close($request, RequestStatus::Completed, $response);
    }

    /**
     * Refuse the request. The written answer must give the reasons, the provision the refusal rests on,
     * and tell the person they may apply to the Commission d'accès à l'information within 30 days (s. 34).
     */
    public function reject(PrivacyRequest $request, string $reason): PrivacyRequest
    {
        return $this->close($request, RequestStatus::Rejected, $reason);
    }

    /**
     * Every declared personal data of the subject, keyed by table (access and portability, s. 27).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function export(Model $subject): array
    {
        $data = [];

        foreach ($this->records($subject) as $record) {
            $data[$record->getTable()][] = $record->exportPersonalData();
        }

        return $data;
    }

    /**
     * @return string the path on the configured disk
     */
    public function exportToFile(Model $subject, string $filename): string
    {
        $path = trim((string) config('quebec-requests.directory', 'quebec/requests'), '/').'/'.$filename;

        Storage::disk(config('quebec-requests.disk'))->put(
            $path,
            (string) json_encode($this->export($subject), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );

        return $path;
    }

    /**
     * Anonymize every record of the subject with each column's strategy (Keep columns stay: information another law requires you to keep).
     *
     * @return int records anonymized
     */
    public function erase(Model $subject): int
    {
        return DB::transaction(function () use ($subject): int {
            $count = 0;

            foreach ($this->records($subject) as $record) {
                $record->anonymizePersonalData();
                $count++;
            }

            return $count;
        });
    }

    /**
     * Records of quebec.models that belong to the subject: the subject itself, plus ResolvesPerson models.
     *
     * @return iterable<int, Model&HasPersonalData>
     */
    public function records(Model $subject): iterable
    {
        foreach ($this->quebec->models() as $model) {
            if ($subject instanceof $model) {
                yield $subject;

                continue;
            }

            if (! is_subclass_of($model, ResolvesPerson::class)) {
                continue;
            }

            /** @var Builder<Model&HasPersonalData> $query */
            $query = $model::personQuery($subject);

            foreach ($query->lazyById() as $record) {
                yield $record;
            }
        }
    }

    protected function close(PrivacyRequest $request, RequestStatus $status, ?string $response): PrivacyRequest
    {
        $request->forceFill([
            'status' => $status,
            'response' => $response,
            'completed_at' => now(),
        ])->save();

        event(new PrivacyRequestClosed($request));

        return $request;
    }
}
