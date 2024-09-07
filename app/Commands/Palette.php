<?php

namespace App\Commands;

use Log;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use App\Controllers\ColorController;
use App\Controllers\DataController;
use App\Controllers\FileController;
use App\Controllers\PaletteController;

use App\Console\TUI;
use App\Console\Boxer;
use App\Console\BoxerMini;

use nunomaduro\dig;

class Palette extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'palette
                            {--N|new : start the create new palette wizard}
                            {--I|import= : new palette via import from file or URL}
                            {--info= : helpful details for command. eg --info=import}';

    // {--T|template= : template to use w/ new palette, if empty will list all palette templates}

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Perform all color palette commands';

    protected $fgPop    = 42;
    protected $fgColor  = 84;
    protected $bgColor  = 235;
    protected $bgPop    = 232;
    protected $width    = 120;
    protected $asciiArt = 'libraries/ascii/spaceman.txt';
    protected $bMargin  = 4;
    protected $bWidth   = 70;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ran  = false;
        $cmds = $this->option();

        // RUN PALETTE COMMDAND(S)
        foreach ($cmds as $cmd => $value) {
            if (isset($value) && $value !== false) {

                if ($this->option("info") !== false && $cmd === "info") {
                    // USER HAS REQUESTED MORE INFO ABOUT A COMMAND
                    $this->paletteHelp($value);
                } elseif ($cmd !== "info") {
                    // RUN COMMAND
                    $this->runPaletteCMD($cmd, $value);
                }

                $ran = true;
            }
        }

        // Final Check: Make sure something happened... otherwise error out
        if ($ran === false) {
            $this->paletteHelp();
        }
    }

    public function runPaletteCMD($cmd = "", $param = false)
    {
        switch ($cmd) {
            case "new":
                TUI::echoPhreakTitle("New Palette", "maxiwi", true);
                PaletteController::createNewPalette();
                break;
            case "import":

                if (FileController::localOrRemoteCheck($param) !== true) {
                    TUI::Message("Invalid import. File could not be found or is not valid.", "FAIL");
                }

                // Parse found file for color codes
                $rawData = FileController::localOrRemoteLoad($param);

                // Parse the file contents for color codes
                if (isset($rawData["json"]) && $rawData["json"] !== false) {
                    $output = $rawData["json"];
                } elseif (isset($rawData["data"]) && $rawData["data"] !== false) {
                    $output = $rawData["data"];
                } elseif ((isset($rawData["json"]) && $rawData["json"] === false) && (isset($rawData["data"]) && $rawData["data"] === false)) {
                    TUI::Message("File could not be parsed. Data not found!", "FAIL");
                }

                $colors = ColorController::findAllColorCodes($output, true, 20);

                if (count($colors) < 1) {
                    TUI::Message("No Hexidecimal colors were found in that file. Try something else!", "FAIL");
                }

                TUI::phreakHeader($this->output, "palette", 24);
                TUI::Break();
                TUI::Message("  File import successful! Total colors found: " . count($colors), "INFO");

                $results  =  ["colors" => json_encode($colors)];
                $default  =  FileController::random_filename();
                $nameData =  PaletteController::createNewPalette_Name($default);

                PaletteController::updateTableDB($nameData["name"], $nameData["title"], $results);

                $stacks  =  PaletteController::createNewPalette_Stacks($colors, $nameData);
                $details =  PaletteController::createNewPalette_Details($nameData);

                $finalPaletteObject = [
                    "title"    => $details["title"],
                    "filename" => $details["name"],
                    "colors"   => $colors,
                    "stacks"   => $stacks
                ];

                $data = json_encode($finalPaletteObject, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_HEX_QUOT);

                file_put_contents('output/palettes/' . $nameData["name"] . '.json', $data);

                TUI::Break(2);
                TUI::Message("  FINISHED!! " . $nameData["title"] . " palette saved successfully!", "GOOD");

                $endLine = TUI::generateLine(25);
                TUI::Speaks([" ", $endLine, " "]);
                break;
        }
    }

    public function captureInfo($question, $sanitize = false)
    {
        $valid = false;

        while ($valid !== true) {
            $result = TUI::askQuestion('  ' . $question, "");
            if (strlen($result) > 1) {
                $valid = true;
            } else {
                TUI::Speaks("Invalid Response! Please try again.");
                TUI::Break();
            }
        }

        if ($sanitize !== false) {
            $result = trim($result);
            $result = FileController::cleanFilename($result);
        }

        return $result;
    }

    public function confirmInfo($name)
    {
        $confirm = false;
        while ($confirm !== true) {
            TUI::Speaks("  You have entered: " . $name);
            TUI::Break();
            $confirmName = TUI::askYesNo("Yes thats good!", "No I want to rename it");

            if ($confirmName === false) {
                $name = $this->captureInfo("What should we name this palette?");
            } else {
                $confirm = true;
            }
        }
    }

    public function paletteError($cmd = "", $sentMsg = false, $status = false)
    {
        //TUI::printASCIIArt("spaceman", "white");
        TUI::phreakHeader($this->output, "palette", 24);

        switch ($cmd) {
            case 'sent':
                $result = $sentMsg;
                $status = $status;
                break;
            case 'error':
                $result = "No Command provided! Run 'php dmt palette --help' for a how-to guide. ";
                $status = "WARN";
                break;
            case 'no_info':
                $result = "This command allows you to build out color palettes.\n\tMake a new palette: -N. List all saved: -L. Display one: -D <ID>. OR --help for all options.";
                $status = "INFO";
                break;
            default:
                $result = "Internal Error. No help topic found...";
                $status = "FAIL";
                break;
        }

        TUI::Break();
        TUI::Message($result, $status);
        TUI::Break(2);
    }

    public function paletteHelp($cmd = "")
    {
        $result = "";

        TUI::phreakHeader($this->output, "palette", 24);

        switch ($cmd) {
            case 'new':
                $result = "Create a new palette using palette editor UI.  EX: php dmt palette -N ";
                break;
            case 'import':
                $result   = [];
                $result[] = "Import colors from a local file, or download a file from a URL and then import.";
                $result[] = "  $ php dmt palette --import=\"https://myfile.com/theme.json\"";
                $result[] = "  $ php dmt palette --import=\"/home/example/my_file.txt\"";
                $result[] = "";
                $result[] = "Files can either be YAML, TXT, JSON or CSS. Only the first 20 colors will be imported.";
                $result[] = "Currently, import will only import Hexidecimal color codes. No RGB or HSL etc.";
                break;
            case 'update':
                $result = "Requires palette ID to update saved colors using terminal color editor UI.  EX: php dmt palette -U 123456 ";
                break;
            case 'remove':
                $result = "Requires palette ID and will delete that saved palette.  EX: php dmt palette -R 123456 ";
                break;
            default:
                $result .= "For a list of all palette commands" . PHP_EOL;
                $result .= "  $ php dmt palette --help" . PHP_EOL;
                $result .= "For detailed instructions, use '--info={keyword}'" . PHP_EOL;
                $result .= "  $ php dmt palette -I import" . PHP_EOL;
                break;
        }

        if (is_array($result)) {
            $result = implode(PHP_EOL, $result);
        };

        $result     = PHP_EOL . $result . PHP_EOL;

        $b          = new BoxerMini($result);
        $b->width   = 70;
        $b->align   = "center";
        $b->type    = "line_thick";
        $b->padding = 4;

        TUI::moveCursor($this->output, "up", 2);
        TUI::Speaks($b->render());
        TUI::Break(2);
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
