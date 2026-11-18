<?php

namespace JeffersonGoncalves\QuebecRequests\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\QuebecRequests\QuebecRequests
 */
class QuebecRequests extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-quebec-requests';
    }
}
