<?php

namespace JeffersonGoncalves\QuebecRequests\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\QuebecRequests\Events\PrivacyRequestOverdue;
use JeffersonGoncalves\QuebecRequests\Models\PrivacyRequest;

class OverdueCommand extends Command
{
    protected $signature = 'quebec:requests-overdue';

    protected $description = 'Dispatch PrivacyRequestOverdue for every open request past its legal deadline';

    public function handle(): int
    {
        $count = 0;

        PrivacyRequest::query()->overdue()->lazyById()->each(function (PrivacyRequest $request) use (&$count): void {
            event(new PrivacyRequestOverdue($request));
            $count++;
        });

        $count === 0
            ? $this->info('No overdue requests.')
            : $this->warn("{$count} overdue request(s): deemed refused (s. 32).");

        return self::SUCCESS;
    }
}
