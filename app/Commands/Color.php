<?php

namespace App\Commands;

use Log;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use App\Controllers\FileController;
use App\Controllers\PaletteController;
use App\Controllers\ColorController;

use App\Console\TUI;
use App\Console\Boxer;
use App\Console\BoxerMini;

/**
 * Color Command Displays Color Info & allows for manipulation
 * @extends Command
 */
class Color extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'color
                            {hex? : Hexidecimal color code subject matter / starting point}
                            {--T|stack : Tailwind style stack for a given color}
                            {--S|shade= : Show a Hex code\'s number of Light & Dark shades}
                            {--L|lighten= : lighten color by given amount, ranging from 0 - 100}
                            {--D|darken= : darken color by given amount, ranging from 0 - 100}
                            {--F|fade= : fade color. TODO!}
                            {--R|rotate= : rotate color y given amount, ranging from 0-360}
                            {--A|saturate= : saturate color by given amount, ranging from 0 - 100}
                            {--E|desaturate= : desaturate color by given amount, ranging from 0 - 100}
                            {--N|inverse : get the inverse of a given color}
                            {--X|text : get good looking text color for given hex code}
                            {--C|comp : Include colors that complement the given hexcode}
                            {--W|web : returns the nearest web safe color code for given hexcode}
                            {--I|info= : When paired with a command keyword, provides detailed usage instructions}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Visualize and Manipulate Single Colors, and/or whole Palettes of Colors';


    /**
     * placeholder for global color code variable
     *
     * @var string
     */
    public $hexCode = "";

    /**
     * default width for data display
     *
     * @var int
     */
    public $width   = 70;

    /**
     * default space around data display
     *
     * @var int
     */
    public $margin  = 4;

    public function test_info(string $message)
    {
        return \fwrite(STDOUT, "\033[34m" . $message . "\033[0m\n");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cmds = $this->option();
        $hex  = $this->argument('hex');

        // if ((!isset($hex) || $hex !== true || $hex === "") && ($this->option("info") !== false || $this->option("info") !== null))
        print_r($hex);

        if (!isset($hex) && $this->option("info") !== false) {
            $this->colorHelp($this->option("info"));
        } elseif (isset($hex) && !ColorController::checkHexCode($hex)) {
            TUI::Message("Invalid hexcode detected! Cannot continue.", "FAIL");
        } elseif (isset($hex) && ColorController::checkHexCode($hex)) {

            // Hex is set and valid
            $this->setHexCode($hex);

            // Flag when command has ran.
            $finished = false;

            // Check if we are doing an advanced command
            foreach ($cmds as $cmd => $value) {
                if ((isset($value) && $value !== false) && $cmd !== "comp") {
                    $finished = true;
                    $this->runColorCMD($cmd, $value);
                }
            }

            // Running basic hexcode info command
            if (!$finished) {
                $this->runColorCMD("code");
            }
        } else {
            $this->colorHelp();
        }
    }

    public function runColorCMD($cmd = "", $param = false, $format = "ascii")
    {
        $HEX = $this->getHexCode();

        switch ($cmd) {
            case "code":

                $params = false;

                // Check for alter flags
                if ($this->option("comp") !== false) {
                    $params = ["comp" => true];
                }

                // Get color data
                $data      = ColorController::prettyColorReport($HEX, false, $params);
                $miniData  = ColorController::getMiniColorInfo($HEX);

                // Box Options
                $options = [
                    "title"   => $miniData["name"],
                    "color"   => $miniData["ansi"],
                    "width"   => $this->width,
                    "margin"  => $this->margin,
                ];

                // Fixed values. Customizing these would mess up the printout.
                $up   = 16;
                $down = 5;

                break;
            case "inverse":
            case "fade":
            case "saturate":
            case "desaturate":
            case "rotate":
            case "lighten":
            case "darken":
            case "text":

                if ($cmd === "lighten" || $cmd === "darken") {
                    $amount     = TUI::amountCheck($param, true);
                    $amount     = TUI::amountFormat($amount);
                    $newColor   = ($cmd === "lighten" ? ColorController::getLighter($HEX, $amount) : ColorController::getDarker($HEX, $amount));
                } elseif ($cmd === "saturate" || $cmd === "desaturate") {
                    $amount     = TUI::amountCheck($param, true);
                    $amount     = TUI::amountFormat($amount);
                    $newColor   = ($cmd === "saturate" ? ColorController::getSaturate($HEX, $amount) : ColorController::getDeSaturate($HEX, $amount));
                } elseif ($cmd === "rotate") {
                    $amount     = TUI::amountCheck($param, true);
                    $newColor   = ColorController::getRotate($HEX, $amount);
                } elseif ($cmd === "inverse") {
                    $amount = 180;
                    $newColor   = ColorController::getRotate($HEX, $amount);
                } elseif ($cmd === "fade") {
                    $amount     = TUI::amountCheck($param, true);
                    $newColor   = ColorController::getFade($HEX, $amount);
                } elseif ($cmd === "text") {
                    $amount     = false;
                    $textData   = ColorController::getTextColor($HEX);
                    $newColor   = ltrim($textData["hex"], "#");
                }

                $newDetails = ColorController::getMiniColorInfo($newColor);

                // Output Config
                $data = ColorController::prettyColorReport(
                    $newColor,
                    [
                        "color"  => $HEX,
                        "action" => $cmd,
                        "amount" => $amount
                    ]
                );

                // Box Options
                $options = [
                    "title" => $newDetails["name"],
                    "color" => $newDetails["ansi"],
                    "width" => $this->width,
                    "margin" => 4,
                    "padding" => 0
                ];

                $up   = 16;
                $down = 5;
                break;
            case "stack":
                $this->width = 38;

                //TUI::printDEBUG($HEX);
                //exit;

                $data = ColorController::colorStackPrint($HEX, $this->width);
                $miniData = ColorController::getMiniColorInfo($HEX);

                $options = [
                    "title" => false,
                    "color" => $miniData["ansi"],
                    "width" => $this->width,
                    "margin" => $this->margin
                ];

                $up = 16;
                $down = 5;
                break;
            case "shade":

                $this->width    = 78;
                $amount         = TUI::amountCheck($param);
                $data           = ColorController::colorShadesPrint($HEX, $amount, $this->width);
                $miniData       = ColorController::getMiniColorInfo($HEX);

                // Box Options
                $options = [
                    "title" => false,
                    "color" => $miniData["ansi"],
                    "width" => $this->width,
                    "margin" => $this->margin
                ];

                $up = $amount + 7;
                $down = $amount;
                break;
            case "web":

                $colors = ColorController::getWebSafe(true);

                //TUI::printDEBUG($colors[0]);
                //exit;

                $RGB = ColorController::hex2rgb($HEX);

                $inputColor = ["r" => $RGB["r"], "g" => $RGB["g"], "b" => $RGB["b"]];

                $wsColor = $colors[0];
                $deviation = PHP_INT_MAX;

                foreach ($colors as $color) {
                    $curDev = $this->compareColors($inputColor, $color);
                    if (
                        $curDev < $deviation
                    ) {
                        $deviation = $curDev;
                        $wsColor = $color;
                    }
                }

                $wsHex = ColorController::rgb2hex($wsColor["r"], $wsColor["g"], $wsColor["b"]);

                // Get color data
                $params    = ["websafe" => true];
                $data      = ColorController::prettyColorReport($wsHex, false, $params);
                $miniData  = ColorController::getMiniColorInfo($wsHex);

                // Box Options
                $options = [
                    "title"   => $miniData["name"],
                    "color"   => $miniData["ansi"],
                    "width"   => $this->width,
                    "margin"  => $this->margin,
                ];

                // Fixed values. Customizing these would mess up the printout.
                $up   = 16;
                $down = 5;

                //TUI::printDEBUG($ws);

                break;

            default:
                $this->colorHelp();
                break;
        }

        if ($format === "ascii" && isset($data)) {
            $this->presentResults($data, $options, $up, $down);
        }
    }

    /**
     * presentResults
     *
     * @param  mixed $colorData
     * @param  mixed $boxOptions
     * @param  mixed $up
     * @param  mixed $down
     * @param  mixed $mode
     * @return void
     */
    public function presentResults($colorData, $boxOptions, $up = 16, $down = 5)
    {
        $Boxer = new Boxer;
        $Boxer->printBox($colorData, $boxOptions);

        TUI::moveCursor($this->output, "up", $up);
        TUI::echoMovingData($this->output, $colorData, ["right" => 7]);
        TUI::moveCursor($this->output, "down", $down);
    }

    /**
     * colorHelp
     *
     * This is a helper function which allows for easily handeling error messages for given commands
     *
     * @param string $cmd
     * @return void
     */
    public function colorHelp($cmd = "", $flag = false)
    {
        TUI::phreakHeader($this->output, "color", 8);

        //TUI::printDEBUG($cmd);
        $results = PHP_EOL;

        switch ($cmd) {
            case "code":
            case "hexcode":
            case "color":
            case "color code":
                $results .= "Prints a detailed output of various formats, related colors, name etc." . PHP_EOL;
                $results .= "Optionally provide param(s) '--ver2' or '--ver3' to tweak output appearance." . PHP_EOL;
                break;
            case "palette":
                $results .= "In addition to normal color info, a pleasing color palette based around the color is also generated" . PHP_EOL;
                break;
            case "lighten":
                $results .= "LIGHTEN HELP TODO";
                break;
            case "darken":
                $results .= "DARKEN HELP TODO";
                break;
            case "stack":
                $results .= "STACK HELP TODO";
                break;
            case "shade":
                $results .= "SHADE HELP TODO";
                break;
            default:
                $results .= "For a list of all color commands" . PHP_EOL;
                $results .= "  $ php phreak color --help" . PHP_EOL;
                $results .= "For detailed instructions, use '--info={keyword}'" . PHP_EOL;
                $results .= "  $ php phreak color -I darken" . PHP_EOL;
                break;
        }

        $b = new BoxerMini($results);
        $b->width = 70;
        $b->align = "center";
        $b->type = "line_thick";
        $b->padding = 4;

        TUI::moveCursor($this->output, "up", 2);
        TUI::Speaks($b->render());

        TUI::Break(1);
    }



    public function arrayContainsWord($str, array $arr)
    {
        foreach ($arr as $word) {
            // Works in Hebrew and any other unicode characters
            // Thanks https://medium.com/@shiba1014/regex-word-boundaries-with-unicode-207794f6e7ed
            // Thanks https://www.phpliveregex.com/
            if (preg_match('/(?<=[\s,.:;"\']|^)' . $word . '(?=[\s,.:;"\']|$)/', $str)) {
                return true;
            }
        }

        return false;
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

    public function formatHexCode()
    {
        // Confirm Hexcode is valid.

        // Uppercase

        // Trim whitespace

        // Return result
    }

    public function compareColors($colorA, $colorB)
    {
        return abs($colorA["r"] - $colorB["r"]) + abs($colorA["g"] - $colorB["g"]) + abs($colorA["b"] - $colorB["b"]);
    }

    /**
     * Get placeholder for global color code variable
     *
     * @return  string
     */
    public function getHexCode()
    {
        return $this->hexCode;
    }

    /**
     * Set placeholder for global color code variable
     *
     * @param  string  $hexCode  placeholder for global color code variable
     *
     * @return  self
     */
    public function setHexCode(string $hexCode)
    {
        $this->hexCode = $hexCode;

        return $this;
    }
}
