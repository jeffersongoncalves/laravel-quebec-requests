<?php

namespace JeffersonGoncalves\QuebecRequests;

use JeffersonGoncalves\QuebecRequests\Commands\OverdueCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QuebecRequestsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('quebec-requests')
            ->hasConfigFile()
            ->hasMigrations(['create_quebec_requests_table'])
            ->hasCommand(OverdueCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RequestManager::class);
    }
}
