<?php

namespace JeffersonGoncalves\QuebecRequests\Events;

use JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest;

class PrivacyRequestOverdue
{
    public function __construct(public PrivacyRequest $request) {}
}
