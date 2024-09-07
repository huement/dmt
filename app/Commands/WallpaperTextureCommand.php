<?php

namespace App\Commands;

use Log;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Commands\Wallpaper\Actions;
use App\Commands\Phreaks\TUI;

class WallpaperTextureCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'wallpaper:texture
                            {image : URL or File path of the texture file}
                            {--L|list : list all saved textures}
                            {--F|filter= : what filter to apply to the image after its imported}
                            {--R|remove : delete selected texture}
                            {--info : display detailed helpful documentation on texture importing}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generate, Update, or Remove Wallpaper Filters & Other Effects';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //TUI::Speaks("testing");

        $cmds          = $this->option();
        $textureFile   = $this->argument('image');

        // Validate image

        // Import the file (download or copy depending)

        // Optionally apply any filters



        // $icon = resource_path('icons/phreak-bride.svg');
        // Log::info('Icon Here: ' . $icon);
        // $this->notify("Hello Web Artisan", "Love beautiful..", resource_path('icons/phreak-bride.svg'));
        // $this->info('Simplicity is the ultimate sophistication.');
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule)
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
