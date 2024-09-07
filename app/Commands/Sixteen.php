<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use League\CLImate\CLImate;

use App\Console\TUI;
use App\Console\Boxer;
use App\Console\BoxerMini;
use App\Controllers\SixteenController;
// use App\Controllers\ColorController;

use App\Commands\Wallpaper\Generator;

class Sixteen extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'sixteen
                            {--N|name= : display specific palette}
                            {--L|list  : list all palettes}
                            {--I|image : display theme image}
                            {--S|spectrum : generate spectrum preview images for all saved themes}
                            {--C|create : create a new B16 Palette}';

    public $width = 64;

    public $B16_DIR = "palettes/base16/";

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Base16 color palette commands';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $gitHead = exec("git rev-parse --short HEAD");
        print_r($gitHead);
        exit;
        $cmds = $this->option();

        if (isset($cmds["image"]) && $cmds["image"] !== false) {
            $file = $this->selectList();

            TUI::Break(2);
            $themeImg = resource_path($this->B16_DIR) . $file . ".png";

            if (is_file($themeImg) !== true) {
                TUI::Message("  No theme image was found for: " . $file, "WARN");
                TUI::Message("  Attempting to generate...", "INFO");
                TUI::Break(2);
                $this->generateSpectrum();
                TUI::Break(2);
            }

            SixteenController::displayThemeImage($file);
            TUI::Break(2);
        } else if (isset($cmds["list"]) && $cmds["list"] !== false) {
            $file = $this->selectList();
            $this->loadTheme($file);
        } else if (isset($cmds["create"]) && $cmds["create"] !== false) {
            TUI::phreakHeader($this->output, "sixteen", 10);
            TUI::Message("Loading Website for Base16 theme generation....", "INFO");
            TUI::Message("This needs to post the result back to the CLI...", "TODO");
            $sixteenAppPath = resource_path("http/harmonic16/index.html");
            exec('php -S 0.0.0.0:8013 ' . $sixteenAppPath);
            exec('xdg-open 0.0.0.0:8013');
        } else if (isset($cmds["name"]) && $cmds["name"] !== false) {
            //echo "THIS" . PHP_EOL;;
            $name = trim($cmds["name"]);
            $this->loadTheme($name);
        } else if (isset($cmds["spectrum"]) && $cmds["spectrum"] !== false) {
            TUI::phreakHeader($this->output, "sixteen", 10);
            $this->generateSpectrum();
            TUI::Break(2);
        } else {
            TUI::phreakHeader($this->output, "sixteen", 10);

            $results = PHP_EOL . "You did not provide any commands. Try:" . PHP_EOL . " $ php phreak sixteen --help" . PHP_EOL . "for all available Base16 commands" . PHP_EOL;

            $b = new BoxerMini($results);
            $b->width = 70;
            $b->align = "center";
            $b->type = "line_thick";
            $b->padding = 4;

            TUI::moveCursor($this->output, "up", 2);
            TUI::Speaks($b->render());
        }

        //TUI::printDEBUG($cmds);
    }

    public function loadTheme($name = false)
    {
        $colors = SixteenController::loadB16Theme($name);

        $newData = $this->flipFlopper($colors);
        $data = ColorPhreaks::colorPalettePrint(2, $newData, $colors["name"]);

        // Turn Hex to Ansi
        $ansi = ColorPhreaks::hex2ansi($colors["hex"]["08"]);

        $options = array(
            "title" => $colors["name"],
            "color" => $ansi,
            "width" => $this->width,
            "margin" => 4,
            "padding" => 2
        );

        $this->presentResults($data, $options, 13);
    }

    /**
     * flipFlopper
     *
     * This function is used to create two columns, one with Grayscale colors, the other with the color colors,
     * in a side by side setup. Normally, due to how the print function displays the theme, they would be split horizontally
     * instead of two vertical columns.
     */
    public function flipFlopper($colors)
    {
        $newList = array();

        $newList[0]  = $colors["hex"]["00"];
        $newList[1]  = $colors["hex"]["08"];
        $newList[2]  = $colors["hex"]["01"];
        $newList[3]  = $colors["hex"]["09"];
        $newList[4]  = $colors["hex"]["02"];
        $newList[5]  = $colors["hex"]["0A"];
        $newList[6]  = $colors["hex"]["03"];
        $newList[7]  = $colors["hex"]["0B"];
        $newList[8]  = $colors["hex"]["04"];
        $newList[9]  = $colors["hex"]["0C"];
        $newList[10] = $colors["hex"]["05"];
        $newList[11] = $colors["hex"]["0D"];
        $newList[12] = $colors["hex"]["06"];
        $newList[13] = $colors["hex"]["0E"];
        $newList[14] = $colors["hex"]["07"];
        $newList[15] = $colors["hex"]["0F"];

        return $newList;
    }

    public function selectList()
    {
        $climate = new CLImate;

        $themes = SixteenController::loadAllBaseThemes();
        $output = array();

        foreach ($themes as $t) {
            $output[] = $t["name"];
        }

        TUI::phreakHeader($this->output, "sixteen", 10);

        $input    = $climate->radio('Select Base16 theme to see preview:', $output);
        $response = $input->prompt();

        TUI::Break(2);

        foreach ($themes as $t) {
            if ($t["name"] === $response) {
                $file = $t["id"];
            }
        }

        return $file;
    }

    public function presentResults($colorData, $boxOptions, $up = 16, $down = 5, $mode = "ascii")
    {
        switch ($mode) {
            case "ascii":

                // First we print the ASCII Art Box
                $Boxer = new Boxer;
                $Boxer->printBox($colorData, $boxOptions);

                // Then we fill it in with the color data!
                TUI::moveCursor($this->output, "up", $up);
                TUI::echoMovingData($this->output, $colorData, array("right" => 7));
                TUI::moveCursor($this->output, "down", $down);
                TUI::Break(1);

                break;
            case "json":
                echo "TODO" . PHP_EOL;
                break;
        }
    }

    public function generateSpectrum()
    {
        $themes = SixteenController::loadAllBaseThemes();

        foreach ($themes as $t) {
            $WGen = new Generator();
            $WGen->spectrumGen($t["id"]);
            TUI::Message("Created Base16 preview image for theme: " . $t["name"], "INFO");
        }
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
