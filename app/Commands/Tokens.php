<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use App\Console\TUI;

class Tokens extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'tokens';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Token Summary Command';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // COLORS
        // -----------------------------------------
        // Load a Palette.
        // Foreach color, build scale.
        // Save resulting output to JSON & Yaml file

        // TYPE
        // -----------------------------------------
        // Foreach Breakpoint...
        TUI::Speaks('Token Summary');
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
