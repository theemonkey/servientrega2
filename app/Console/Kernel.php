<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ejecutar lmpieza automatica de archivos duplicados en temp_comprobantes
        /*$schedule->command('comprobantes:clean-duplicates')
            ->weekly()
            ->sundays() //Cada domingo
            ->at('02:00')
            ->withoutOverlapping()
            ->runInBackground();*/

        $schedule->command('comprobantes:clean-duplicates')->dailyAt('02:00'); // Diario
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
