<?php

namespace JeffersonGoncalves\QuebecRequests\Tests;

use JeffersonGoncalves\QuebecRequests\QuebecRequestsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            QuebecRequestsServiceProvider::class,
        ];
    }
}
