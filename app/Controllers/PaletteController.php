<?php

namespace App\Controllers;

/**
 * PalettePhreaks | anything to do with color palettes
 * Date: July 25 2021
 */

use App\Console\TUI;
use App\Console\Boxer;
use App\Console\BoxerMini;

use App\Controllers\FileController;
use App\Controllers\ColorController;
use App\Controllers\DataController;

require_once "libraries/ANSI.php";

use League\CLImate\CLImate;

use ANSI;

class PaletteController
{
    // TODO This function is not being used but should be available somehow
    // TODO like via a seperate flow where user inputs only a single color and palette is built
    public static function createNewPaletteFromColor($color)
    {
        $climate = new CLImate();

        $climate->br();
        $climate
            ->lightMagenta()
            ->bold()
            ->out("   Palette Options");
        $climate->br();

        $options = ["triad", "complement", "monotone", "analogy", "all"];
        $input = $climate->radio("  Which Type?:", $options);
        $paletteType = $input->prompt();

        // @TODO GENERATE PALETTE TYPE FOR GIVEN COLOR
        $paletteColors = [];

        return $paletteColors;
    }

    // ----------------------------------------------------
    // CREATE NEW PALETTE
    // ----------------------------------------------------

    /**
     * createNewPalette
     *
     * Collect user inputted colors and save them to on disk database & JSON file
     *
     * This is the main function and is really the only one you directly call. It calls
     * all other functions to populate the new palette with appropriate data.
     *
     * @return void
     */
    public static function createNewPalette()
    {
        $check = DataController::checkTable("palettes");
        if (!$check) {
            // Table does not exist, template file not found to make it. major error!
            TUI::Message(
                "Serious Error. Palette table template file not found. Cannot continue!",
                "FAIL"
            );
        }

        $default = FileController::random_filename();

        // MAIN DATA GATHERING FUNCTIONS
        $names = self::createNewPalette_Name($default);
        $colors = self::createNewPalette_Colors($names);
        $stacks = self::createNewPalette_Stacks($colors, $names);
        $details = self::createNewPalette_Details($names);

        $finalPaletteObject = [
            "title" => $details["title"],
            "filename" => $details["name"],
            "colors" => $colors,
            "stacks" => $stacks,
        ];

        // @TO4DO GENERATE PREVIEW IMAGE BASED ON USER INPUTS
        // @TODO WILL USE WALLPAPERPHREAKS
        // @TODO DISPLAY THE PALETTE IN THE TERMINAL VIA COLORPHREAKS

        // SAVE DATA TO FILE
        $data = json_encode(
            $finalPaletteObject,
            JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_HEX_QUOT
        );
        file_put_contents("output/palettes/" . $names["name"] . ".json", $data);

        TUI::Break(2);
        TUI::Message(
            "FINISHED!! " . $names["title"] . " palette saved successfully!",
            "GOOD"
        );

        $endLine = TUI::generateLine(25);
        TUI::Speaks([" ", $endLine, " "]);
    }

    /**
     * createNewPalette_Name
     *
     * Determine the name for the new palette & make a slug for filename
     *
     * @param mixed $default
     * @return void
     */
    public static function createNewPalette_Name($default)
    {
        $climate = new CLImate();
        $nameCheck = 0;

        while ($nameCheck === 0) {
            $name = self::askQuestion("  Palette Title?", $default);

            $cleanName = FileController::beautify_filename($name);

            $paletteCheck = DataController::checkTableRow(
                "palettes",
                "name",
                $cleanName
            );

            if ($paletteCheck !== true) {
                $nameCheck = 1;
                DataController::addRowFV("palettes", "name", $cleanName);
            } else {
                $climate->br();
                TUI::Message("  That name has already been used", "WARN");
                $climate->br();

                // Continue? [y/n]
                $input = $climate->confirm(
                    "  [Y] Edit Existing [N] Try New Name"
                );
                $nameCheck = $input->confirmed() ? 1 : 0;
                $climate->br();
            }
        }

        return ["title" => $name, "name" => $cleanName];
    }

    /**
     * createNewPalette_Details
     *
     * Setup how the new palette will look when the preview image is created
     *
     * @param mixed $nameData
     * @return void
     */
    public static function createNewPalette_Details($nameData)
    {
        $previewImg = self::configPreview();

        $previewImgFormats = json_encode([
            "svg" => $previewImg["svg"],
            "pdf" => $previewImg["pdf"],
            "png" => $previewImg["png"],
            "jpeg" => $previewImg["jpeg"],
        ]);

        $results = [
            "title" => $nameData["title"],
            "name" => $nameData["name"],
            "file" => "output/palettes/" . $nameData["name"] . ".json",
            "preview_style" => $previewImg["style"],
            "preview_equal-size" => $previewImg["equal"],
            "preview_details" => $previewImg["details"],
            "preview_formats" => $previewImgFormats,
        ];

        self::updateTableDB($nameData["name"], $nameData["title"], $results);

        return $results;
    }

    /**
     * createNewPalette_Colors
     *
     * Present options to the user for how they would like to add colors
     *
     * @param mixed $nameData
     * @return void
     */
    public static function createNewPalette_Colors(
        $nameData = ["name" => "", "title" => ""]
    ) {
        // Result Array we are going to populate
        $colors = [];

        $climate = new CLImate();

        // Build Section Title
        TUI::echoPhreakTitle("Add Colors");

        // Color Loop
        $finished = 0;
        while ($finished === 0) {
            $options = [
                "Color Picker",
                "Theme Builder",
                "Hexcode List",
                "Load File",
            ];
            $input = $climate->radio(
                "  How would you like to add colors? :",
                $options
            );
            $response = $input->prompt();
            $foundColors = self::addColor($response);

            $colors[] = $foundColors;

            TUI::Speaks([" ", count($foundColors) . " Colors Added", " "]);

            $input = $climate->confirm("  Finished adding colors?");
            if ($input->confirmed()) {
                $finished = 1;
                $climate->br(2);
            } else {
                $climate->br();
            }
        }

        // Cleanup the Colors array so its only 1 level deep
        $colors = ColorController::foundColorCleanup($colors);

        $results = ["colors" => json_encode($colors)];

        self::updateTableDB($nameData["name"], $nameData["title"], $results);

        return $colors;
    }

    public static function newPalette_Colors_Format(
        $colors = ["name" => "", "title" => ""]
    ) {
        // ...
    }

    /**
     * createNewPalette_Stacks
     *
     * If a user has chosen to add color stacks, this function generates them for each color
     *
     * @param mixed $colors
     * @param mixed $default
     * @return void
     */
    public static function createNewPalette_Stacks($colors, $nameData)
    {
        $stacks = self::configStacks();

        if ($stacks["generate"] === true) {
            $colorStacks = ColorController::getColorStack($colors);

            $results = [
                "stack_generate" => true,
                "stack_colors" => json_encode($colorStacks, JSON_FORCE_OBJECT),
            ];

            self::updateTableDB(
                $nameData["name"],
                $nameData["title"],
                $results
            );
        } else {
            $results = ["stack_generate" => false, "stack_colors" => false];
        }

        return $results;
    }

    // ----------------------------------------------------
    // ADVANCED USER PROMPT CONFIGS
    // ----------------------------------------------------

    /**
     * configPreview
     *
     * User Prompt Function
     * customize the preview image OPTIONS for the palette.
     * NOTE: this only sets the params, it doesn't generate the image itself.
     *
     * @return void
     */
    public static function configPreview()
    {
        // Defaults. Unless user chooses to change them.
        $svg = true;
        $pdf = false;
        $jpeg = true;
        $png = true;
        $details = false;
        $equal = true;
        $style = "wide";

        $climate = new CLImate();

        $input = $climate->confirm("  Customize Preview Image?");

        // Continue? [y/n]
        if ($input->confirmed()) {
            $climate->br();
            $climate
                ->lightMagenta()
                ->bold()
                ->out("   Preview Image Formats");
            $climate->br();
            $input = $climate->confirm("  SVG Image?");
            if ($input->confirmed()) {
                $svg = true;
            }
            $input = $climate->confirm("  PDF Image?");
            if ($input->confirmed()) {
                $pdf = true;
            }
            $input = $climate->confirm("  JPEG Image?");
            if ($input->confirmed()) {
                $jpeg = true;
            }
            $input = $climate->confirm("  PNG Image?");
            if ($input->confirmed()) {
                $png = true;
            }
            $input = $climate->confirm("  Include Details?");
            if ($input->confirmed()) {
                $details = true;
            }
            $input = $climate->confirm("  Colors are equal sized?");
            if ($input->confirmed()) {
                $equal = true;
            }
            $input = $climate->input("  Preview image style?");
            $input->accept(["tall", "wide"], true);
            $style = $input->prompt();
        }

        $result = [
            "svg" => $svg,
            "pdf" => $pdf,
            "jpeg" => $jpeg,
            "png" => $png,
            "details" => $details,
            "equal" => $equal,
            "style" => $style,
        ];

        return $result;
    }

    /**
     * configStacks
     *
     * User Prompt Function
     * optionally generate a stack for each color in the palette
     * NOTE: this function only sets the generate value, it does not actually do the generation.
     *
     * @return void
     */
    public static function configStacks()
    {
        $climate = new CLImate();

        $input = $climate->confirm("  Generate color stacks for each color?");

        if ($input->confirmed()) {
            $generate = true;
        } else {
            $generate = false;
        }

        $result = ["generate" => $generate];
        return $result;
    }

    // ----------------------------------------------------
    // Various Shortcut Helpers
    // ----------------------------------------------------

    /**
     * updateTableDB
     *
     * Helper function for updating the palette database for given table name.
     *
     * @param mixed $tableName
     * @param mixed $displayName
     * @param mixed $data
     * @return void
     */
    public static function updateTableDB($tableName, $displayName, $data)
    {
        $save = DataController::updateTableData(
            $data,
            "palettes",
            "name",
            $tableName
        );

        if ($save !== true) {
            TUI::Message(
                "Serious error updating palette details. Cannot continue!",
                "FAIL"
            );
        } else {
            TUI::Message($displayName . " palette details updated!", "INFO");
            TUI::Break();
        }
    }

    /**
     * askQuestion
     *
     * shortcut helper function for using CLImate input prompt
     *
     * @param  mixed $string
     * @param  mixed $default
     * @return string
     */
    public static function askQuestion($string = "", $default = "")
    {
        $climate = new CLImate();

        $input = $climate->input($string);
        $input->defaultTo($default);
        $result = $input->prompt();
        $climate->br();

        return $result;
    }

    // ----------------------------------------------------
    // ADDING COLORS TO A PALETTE
    // ----------------------------------------------------

    /**
     * addColor
     *
     * main color for handling user input choice to add colors to a palette
     *
     * @param  string $mode user selected choice
     * @return array $results array of color codes
     */
    public static function addColor($mode = "Color Picker")
    {
        TUI::Break(2);

        // Handle the choice for color input(s)
        switch ($mode) {
            case "Theme Builder":
                exec("tcolors -o hex -output-on-exit", $output);
                break;
            case "Color Picker":
                exec("pastel pick | pastel format hex", $output);
                break;
            case "Hexcode List":
                $input = self::askQuestion(
                    "  Enter Hexcodes, separated by commas. EX: cc6666,ff9900"
                );
                $output = explode(",", $input);
                break;
            case "Load File":
                $jfile = self::askQuestion(
                    "  Enter File Path or Remote URL. EX: /my/dir/color.json OR http://example.com/file.css"
                );
                $rawData = FileController::localOrRemoteLoad($jfile);

                // Parse the file contents for color codes
                if (isset($rawData["json"]) && $rawData["json"] !== false) {
                    $output = $rawData["json"];
                } elseif (
                    isset($rawData["data"]) &&
                    $rawData["data"] !== false
                ) {
                    $output = $rawData["data"];
                }

                break;
            default:
                // TODO display error message that input choice not understood
                break;
        }

        // Now we parse the user input for all hex codes and save them to an array
        $results = ColorController::findAllColorCodes($output);

        return $results;
    }

    // ----------------------------------------------------
    // PALETTE CRUD FUNCTIONS (minus C)
    // ----------------------------------------------------

    public static function lookupPalette($id = 1)
    {
        $results = [];

        $paletteDBRow = DataController::getRowByID("palettes", $id);

        if (!isset($paletteDBRow->id)) {
            TUI::Message(
                "A palette matching that ID could not be found",
                "FAIL"
            );
        }

        $results[] = "";

        return $paletteDBRow;
    }

    public static function findSavedPalettes()
    {
        $palettes = false;
        $total = 0;
        $error = false;

        $files = DataController::getAllFromTable("palettes");

        if (!$files) {
            $error = "No saved palettes were found!";
        } else {
            $palettes = json_decode($files, true);
            $total = count($palettes);
        }

        return ["palettes" => $palettes, "error" => $error, "total" => $total];
    }

    /**
     * displaySavedPalettes
     *
     * prints a brief summary of all saved palettes in a nice ascii art box
     *
     * @param  mixed $list
     * @return array
     */
    public static function displaySavedPalettes($list)
    {
        // Add each item
        $count = 0;

        $blocks = [];
        $all = [];

        foreach ($list["palettes"] as $item) {
            if ($count !== 0) {
                $id = $item["id"];
                $block = [];

                $colors = json_decode($item["colors"], true);

                $colorString = "";
                $box = "███";
                $cCount = 0;
                $cMax = 6;
                foreach ($colors as $c) {
                    if ($cCount < $cMax) {
                        $ansiColor = ColorController::hex2ansi($c);
                        $colorString .=
                            ANSI::color256($ansiColor) . $box . ANSI::reset();
                    }
                    $cCount++;
                }

                $block[] = "[" . $item["id"] . "] " . $item["title"];
                $block[] = str_repeat("=", \iconv_strlen($item["title"]));
                $block[] = "Total Colors: [ " . count($colors) . " ]";
                $block[] = $colorString;
                $block[] = "Created: " . $item["created"];
                $block[] = "";
                $block[] = "";
                $blocks[$id] = $block;
            }

            $count++;
        }

        return $blocks;
    }

    /**
     * printPalette
     *
     * Print a saved palette to the command line in a nice ASCII art box.
     * Contains all relevant information as well as ANSI color preview of all colors.
     *
     * @param  int $id Palette DB identifier
     * @return void
     */
    public static function printPalette($id = 1)
    {
        $PaletteData = self::lookupPalette($id);

        $title = $PaletteData->getField("title");
        $ID = $PaletteData->getField("id");
        $stack = $PaletteData->getField("stack_colors"); // TODO figure out what is up with the stack colors
        $colors = json_decode($PaletteData->getField("colors"), true);
        $dataText = [];
        $blankRows = [];

        $dataText[] = "DETAILS";
        $dataText[] = trim(TUI::generateLine(20));
        $dataText[] = "";
        $dataText[] = "CREATED      |  " . $PaletteData->getField("created");
        $dataText[] = "TOTAL COLORS |  " . count($colors);
        $dataText[] = "FILE         |  " . $PaletteData->getField("file");
        $dataText[] =
            "TEMPLATES    |  " . $PaletteData->getField("preview_formats");
        $dataText[] = "";
        $dataText[] = "PREVIEW";

        foreach ($dataText as $dt) {
            $blankRows[] = " ";
        }

        $colorCount = 1;
        $colorTotal = count($colors);
        $columnCount =
            $colorTotal % 2 == 0 ? $colorTotal * 0.5 : ($colorTotal + 1) * 0.5;

        if ($columnCount <= 1) {
            $columnCount = 2;
        }

        $string = " ";
        while ($colorCount < $columnCount) {
            $string .= " " . PHP_EOL;
            $blankRows[] = " ";
            $colorCount++;
        }

        $b = new BoxerMini($string);
        $b->width = 58;
        $b->align = "center";
        $b->type = "line_double";
        $b->padding = 10;
        $innerBox = $b->render();

        $dataColors = ColorController::colorPalettePrint(2, $colors, $title);

        $infoDump = [
            "columns" => $columnCount,
            "colors" => $colorTotal,
            "title" => $title,
            "id" => $ID,
            "blank_rows" => $blankRows,
        ];

        $result = [
            "text" => $dataText,
            "inner_box" => $innerBox,
            "colors" => $dataColors,
            "data" => $infoDump,
        ];

        return $result;
    }

    public static function editPalette($id = 1, $name = "", $colors = [])
    {
        TUI::Speaks(" --- ");
    }
}
