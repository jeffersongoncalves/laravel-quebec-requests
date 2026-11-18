<?php

namespace JeffersonGoncalves\QuebecRequests\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\QuebecRequests\RequestManager;

/**
 * @method static \JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest open(\JeffersonGoncalves\QuebecRequests\Enums\RequestType $type, ?\Illuminate\Database\Eloquent\Model $subject = null, array $requester = [])
 * @method static \JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest verify(\JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest $request, ?\Illuminate\Database\Eloquent\Model $subject = null)
 * @method static \JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest fulfil(\JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest $request, ?string $response = null)
 * @method static \JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest complete(\JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest $request, ?string $response = null)
 * @method static \JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest reject(\JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest $request, string $reason)
 * @method static array<string, list<array<string, mixed>>> export(\Illuminate\Database\Eloquent\Model $subject)
 * @method static string exportToFile(\Illuminate\Database\Eloquent\Model $subject, string $filename)
 * @method static int erase(\Illuminate\Database\Eloquent\Model $subject)
 *
 * @see RequestManager
 */
class QuebecRequests extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RequestManager::class;
    }
}
