<?php

namespace JeffersonGoncalves\QuebecRequests\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Quebec\Attributes\PersonalData;
use JeffersonGoncalves\Quebec\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Quebec\Contracts\HasPersonalData;
use JeffersonGoncalves\Quebec\Enums\AnonymizeStrategy;
use JeffersonGoncalves\QuebecRequests\Enums\RequestStatus;
use JeffersonGoncalves\QuebecRequests\Enums\RequestType;

/**
 * @property RequestType $type
 * @property RequestStatus $status
 * @property string|null $export_path
 * @property Carbon $due_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $extended_at
 * @property string|null $extension_reason
 * @property Carbon|null $completed_at
 * @property Model|null $subject
 */
#[PersonalData('requester_name', purpose: 'Answer requests (ss. 27 to 34)', anonymize: AnonymizeStrategy::Mask)]
#[PersonalData('requester_email', purpose: 'Answer requests (ss. 27 to 34)', anonymize: AnonymizeStrategy::Hash)]
class PrivacyRequest extends Model implements HasPersonalData
{
    use InteractsWithPersonalData;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('quebec-requests.table', 'quebec_requests');
    }

    protected function casts(): array
    {
        return [
            'type' => RequestType::class,
            'status' => RequestStatus::class,
            'due_at' => 'datetime',
            'verified_at' => 'datetime',
            'extended_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', RequestStatus::open());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereIn('status', RequestStatus::open())->where('due_at', '<', now());
    }

    public function isOpen(): bool
    {
        return in_array($this->status, RequestStatus::open(), true);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
