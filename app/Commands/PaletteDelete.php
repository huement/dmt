<?php

namespace App\Commands;

use App\Controllers\ColorController;
use App\Controllers\DataController;
use App\Controllers\FileController;
use App\Controllers\PaletteController;

use App\Console\TUI;
use App\Console\Boxer;
use App\Console\BoxerMini;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class PaletteDelete extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'palette:delete
                            {id : palette identifier}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    public $width    = 70;
    public $margin   = 4;
    public $fgColor  = 84;
    public $bgColor  = 235;
    public $format   = "ascii";

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $param = $this->argument("id");

        $id = intval($param);
        $PaletteData = PaletteController::lookupPalette($id);
        $PaletteName = $PaletteData->getField("title");

        TUI::Break();

        TUI::phreakHeader($this->output, "sixteen", 10);

        TUI::Break();

        $confirmDelete = TUI::askYesNo("Yes! For sure delete palette: " . $PaletteName, "No I have changed my mind");

        if ($confirmDelete === false) {
            TUI::Break();
            TUI::Message("Okay. Cancelling that request! Have a nice day, playah", "INFO");
            TUI::Break();
            exit;
        }

        // Delete the Palette
        $remove = DataPhreaks::removeRow("palettes", $id);

        if ($remove !== true) {
            TUI::Message("There was an error deleting that palette. Confirm ID & try again?", "FAIL");
        }

        TUI::Break();
        TUI::Message($PaletteName . " was deleted successfully!", "GOOD");
        TUI::Break();
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
