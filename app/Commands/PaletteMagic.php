<?php

namespace App\Commands;

use App\Controllers\ColorController;
use App\Controllers\DataController;
use App\Controllers\FileController;
use App\Controllers\PaletteController;

use App\Console\TUI;
use App\Console\Boxer;
use App\Console\BoxerMini;

use Phim\Color;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class PaletteMagic extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'palette:magic
                            {id? : palette identifier}
                            {--W|websafe : create a duplicate palette w/ nearest websafe colors}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Update, Enhance, Alter and/or improve saved palette';

    public $width    = 70;
    public $margin   = 4;
    public $fgColor  = 84;
    public $bgColor  = 235;
    public $format   = "ascii";

    public $palette  = 0;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cmds = $this->option();
        $data = $this->argument("id");

        $results = [];

        if(null === $this->argument("id")) {
            TUI::Message("  You must provide a palette ID!", "WARN");
            exit;
        } else {
            TUI::echoPhreakTitle("Palette Magic", "maxiwi", true);

            $this->palette = intval($this->argument("id"));
            $pd = PaletteController::lookupPalette($this->palette);
            $colors = json_decode($pd->getField("colors"), true);

            TUI::Message("Magicifying " . $this->argument("id") . " Colors", "INFO");
            TUI::Break();

            $adobeArray = [];

            foreach($colors as $color) {
                TUI::Speaks(TUI::colorPreviewLine($color));

                $colorMeta    = ColorController::getColorInfo($color);
                $colorLighter = ColorController::getMatchingLighter($color);
                $colorDarker  = ColorController::getMatchingDarker($color);
                $shades       = ColorController::getColorShades($color, 9);

                $results[$color] = [
                    'hex' => $color,
                    'light'=>$colorLighter,
                    'dark'=>$colorDarker,
                    'meta'=>$colorMeta,
                    'shades'=>$shades
                ];

                $colorArray = [
                    "hex" => "#".$color,
                    "light" => $colorLighter,
                    "dark" => $colorDarker
                ];

                // Now we need to set the Color Key. This will be used in json2scss
                // as the $variable in the scss so make it easy & simple.

                $hue = $colorMeta["hue"];

                $cleanName = preg_replace('/\s+/', '', $colorMeta["name"]);
                $cleanName = preg_replace("/[^A-Za-z0-9.!?]/", '', $cleanName);

                if(isset($adobeArray[$hue])){
                    $adobeArray[$cleanName] = $colorArray;
                } else {
                    $adobeArray[$hue] = $colorArray;
                }
            }

            TUI::Break();
            FileController::saveJSONFile($results, $this->palette.'_magic', 'palettes');
            FileController::saveJSONFile($adobeArray, $this->palette.'_adobe', 'palettes');

            TUI::Message("Palette saved to: output/palettes/" . $pd->getField("title"), "INFO");
            exit;
        }

        // print_r($results);
        // TUI::Message("Option for Websafe duplication...", "TODO");
        // TUI::Message("Option for Adding Hover / Active States...", "TODO");
        // TUI::Message("Option for Tailwind Stack addition...", "TODO");
        // TUI::Message("Option for Color Harmonization...", "TODO");
    }

    public function tokenize()
    {
        $id = $this->palette;

    }

    public function websafe()
    {
        $id = $this->palette;

        // Call Phreak w/ ID.
        $pd = PaletteController::lookupPalette($id);

        // Foreach color, find websafe version
        $colors = json_decode($pd->getField("colors"), true);

        if (count($colors) < 1) {
            TUI::Message("Palette Color Error", "FAIL");
        }

        $wsColors = [];
        foreach ($colors as $color) {
            $wsColors[] = ColorController::hex2websafe($color);
        }

        TUI::printDEBUG($wsColors);

        // TODO SAVE RESULTS TO NEW PALETTE
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
