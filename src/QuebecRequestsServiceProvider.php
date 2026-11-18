<?php

namespace JeffersonGoncalves\QuebecRequests;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QuebecRequestsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-quebec-requests')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
