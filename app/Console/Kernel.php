<?php

namespace App\Console;

use App\Jobs\UpdateDailyReportsJob;
use App\Jobs\UpdateHourlyReportsJob;
use App\Jobs\UpdateMonthlyReportsJob;
use App\Models\ScAccount;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        ScAccount::lazy()->each(function (ScAccount $account) use ($schedule) {
            $schedule->job(new UpdateHourlyReportsJob($account))->hourly();
            $schedule->job(new UpdateDailyReportsJob($account))->daily();
            $schedule->job(new UpdateMonthlyReportsJob($account))->daily();
        });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
