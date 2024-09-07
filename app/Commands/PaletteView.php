<?php

namespace App\Commands;

use App\Controllers\ColorController;
use App\Controllers\DataPController;
use App\Controllers\FileController;
use App\Controllers\PaletteController;

use App\Console\TUI;
use App\Console\Boxer;
use App\Console\BoxerMini;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class PaletteView extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'palette:view
                            {data? : item to display. Phreak Palette ID, hexcode list, or file path}
                            {--A|all : show all saved palettes at once}
                            {--F|format= : change palette output from ASCII to JSON or SVG}
                            {--E|export : pass this flag to optionally export for Gimp, Photoshop etc. all at once}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Output a group of colors';

    protected $help = "TODO: Provide helpful information for this command";

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
        $this->setHelp($this->help);

        // Check for All Flag. If set, display all saved palettes and exit
        if (null !== $this->option("all") && $this->option("all") !== false) {
            $this->viewAll();
            return false;
        }

        // Confirm some kind of palette data is being sent, otherwise exit
        if (null === $this->argument("data") || $this->argument("data") === false) {
            $this->paletteParamError("empty_string");
            return false;
        }

        $cmds = $this->option();
        $data = $this->argument("data");

        if (\is_numeric($data) !== false) {
            $ID = intval($data);
            $this->printPhreakPalette($ID);
        } elseif (strlen($data) >= 7) {
            $this->printHexcodePalette($data);
        } elseif (FileController::localOrRemoteCheck($data) !== false) {
            $this->printFilePalette($data);
        } else {
            $this->paletteParamError();
        }
    }

    /**
     * paletteParamError
     *
     * This function only deals with Color Palettes. When a user is trying to display one,
     * but they enter a bad param, this function attempts to help them correct their mistake.
     *
     * @param string $code
     * @return void
     */
    public function paletteParamError($code = "")
    {
        TUI::phreakHeader($this->output, "palette", 24);

        switch ($code) {
            case "empty_file":
                $result = "TODO add empty_file msg";
                break;
            case "empty_db":
                $result = "TODO add empty_db msg";
                break;
            case "empty_string":
                $result = "TODO add empty_string msg";
                break;
            case "bad_file":
                $result = "TODO add bad_file msg";
                break;
            default:
                $result = "We could not find a palette using the data you provided.";
                break;
        }

        TUI::Message($result, "FAIL", true);
        TUI::Break(2);
    }

    public function printPhreakPalette($id)
    {
        $output = PaletteController::printPalette($id);

        $up = $output["data"]["columns"] * 2;
        $down = $output["data"]["columns"] * 4;

        // Setup the presentation Box
        $options = [
            "title" => $output["data"]["title"],
            "color" => $this->fgColor,
            "width" => $this->width,
            "margin" => $this->margin,
            "padding" => $up,
            "vis_content" => true,
            "height" => 1
        ];

        $Boxer = new Boxer();
        $Boxer->printBox($output["data"]["blank_rows"], $options);

        // Reset Cursor
        TUI::moveCursor($this->output, "up", (count($output["data"]["blank_rows"]) * 2));

        // Fill box with Data
        TUI::Break(2);
        TUI::outMove($output["text"], ["right" => 7], $this->output);
        TUI::outMove($output["inner_box"], ["right" => 7], $this->output);
        TUI::moveCursor($this->output, "up", $up - 1);
        TUI::outMove($output["colors"], ["right" => 9], $this->output);

        // Stick the landing
        TUI::moveCursor($this->output, "down", 5);
        TUI::Break(2);
    }

    public function printHexcodePalette($hexData)
    {
        $result = ColorController::findAllColorCodes($hexData);

        $total = count($result);

        $up = $total;
        if ($total <= 6) {
            $up = 6;
        }

        $options = ["up" => $up, "down" => $total * 4, "title" => "Hexcode List"];

        $this->paletteBox($result, $options);
    }

    public function printFilePalette($fileData)
    {
        $result = [];

        // Parse found file for color codes
        $rawData = FileController::localOrRemoteLoad($fileData);

        // Parse the file contents for color codes
        if (isset($rawData["json"]) && $rawData["json"] !== false) {
            $output = $rawData["json"];
        } elseif (isset($rawData["data"]) && $rawData["data"] !== false) {
            $output = $rawData["data"];
        }

        $colors = ColorController::findAllColorCodes($output);

        if (count($colors) > 0) {
            $result["colors"] = $colors;
            $result["title"] = basename($fileData);
        } else {
            $this->paletteParamError("empty_file");
            return false;
        }

        $options = ["up" => 0, "down" => 0];

        $this->paletteBox($result, $options);
    }

    public function paletteBox($boxData, $opts)
    {
        $data = ColorController::colorPalettePrint(2, $boxData, $opts["title"]);
        $ansi = ColorController::hex2ansi($boxData[1]);
        $options = [
            "title" => $opts["title"],
            "color" => $ansi,
            "width" => $this->width,
            "margin" => $this->margin,
            "padding" => 2
        ];

        // First we print the ASCII Art Box
        $Boxer = new Boxer;
        $Boxer->printBox($data, $options);

        // Then we fill it in with the color data!
        TUI::moveCursor($this->output, "up", $opts["up"]);
        TUI::echoMovingData($this->output, $data, ["right" => 7]);
        TUI::moveCursor($this->output, "down", $opts["down"]);
        TUI::Break(1);
    }

    public function viewAll()
    {
        $list = PaletteController::findSavedPalettes();

        $paletteBlock = PaletteController::displaySavedPalettes($list);
        $totalPalettes = count($paletteBlock);

        $columns = ($totalPalettes % 2 == 0) ? $totalPalettes * 0.5 : ($totalPalettes + 1) * 0.5;

        $upNum = ceil(($columns * 5));
        $up   = $upNum * 0.5;

        $down = ($columns * 5);

        $options = [
            "title" => "Palettes",
            "color" => $this->fgColor,
            "width" => $this->width,
            "margin" => $this->margin,
            "padding" => $upNum,
            "vis_content" => false
        ];

        $Boxer = new Boxer;
        $Boxer->printBox($paletteBlock, $options);

        TUI::moveCursor($this->output, "up", ($upNum + $up + 1));

        $counter = 0;
        $flip = 0;

        foreach ($paletteBlock as $pB) {
            $upBlock = round($flip * 7);
            $right = ($flip === 1 ? 40 : 10);
            $move = ["right" => $right];

            if ($upBlock > 0) {
                TUI::moveCursor($this->output, "up", $upBlock);
            }

            TUI::echoMovingData($this->output, $pB, $move);
            $counter++;
            $flip = (($flip === 0) ? 1 : 0);
        }

        TUI::moveCursor($this->output, "down", $down);
        TUI::Break(2);
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
