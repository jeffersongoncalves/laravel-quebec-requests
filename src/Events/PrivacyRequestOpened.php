<?php

namespace JeffersonGoncalves\QuebecRequests\Events;

use JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest;

class PrivacyRequestOpened
{
    public function __construct(public PrivacyRequest $request) {}
}
