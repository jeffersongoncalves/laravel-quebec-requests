<?php

namespace JeffersonGoncalves\QuebecRequests\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JeffersonGoncalves\PestPluginDatabaseMigrations\LoadsPackageMigrations;
use JeffersonGoncalves\Quebec\QuebecServiceProvider;
use JeffersonGoncalves\QuebecRequests\QuebecRequestsServiceProvider;
use JeffersonGoncalves\QuebecRequests\Tests\Fixtures\Order;
use JeffersonGoncalves\QuebecRequests\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use LoadsPackageMigrations;

    protected function getPackageProviders($app): array
    {
        return [
            QuebecServiceProvider::class,
            QuebecRequestsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('quebec.models', [User::class, Order::class]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations(QuebecRequestsServiceProvider::class);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_id')->nullable();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('shipping_address')->nullable();
            $table->unsignedInteger('total');
        });
    }
}
