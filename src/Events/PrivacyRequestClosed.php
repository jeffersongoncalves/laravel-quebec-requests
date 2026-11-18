<?php

namespace JeffersonGoncalves\QuebecRequests\Events;

use JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest;

/**
 * Completed or rejected: check $request->status. Notify the requester from a listener.
 */
class PrivacyRequestClosed
{
    public function __construct(public PrivacyRequest $request) {}
}
