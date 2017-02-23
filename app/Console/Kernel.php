<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\HarvestTeam::class,
        Commands\HarvestFixture::class,
        Commands\Monitor::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
       $schedule->command('harvest:team')->dailyAt('02:00');
       $schedule->command('harvest:fixture')->dailyAt('03:00');
       $schedule->command('harvest:monitor')->everyMinute();
    }
}
