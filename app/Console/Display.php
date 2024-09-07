<?php

namespace App\Console;

use Colors\Color;
use League\CLImate\CLImate;
use Laminas\Text\Figlet\Figlet;
use Pixeler\Pixeler;
use Carbon\Carbon;
use Codedungeon\PHPMessenger\Facades\Messenger;
use Symfony\Component\Console\Cursor;

use App\Console\Boxer;
use App\Console\BoxerMini;

/**
 * Console > Display
 *
 * 'Static' Class that allows quick and easy way to display data in a terminal
 * shell. It does very little manipulation to the data. What you pass it will be
 * exactly what is displayed, with only maybe some ascii color markup added.
 */

class Display
{
    // GLOBAL VARIABLES
    public static $boxer;
    public static $climate; // Used via self::$climate->out('blah');
    public static $globalWidth = 60;
    public static $asciiArt = "libraries/ascii/spaceman.txt";
    public static $ocean = 42;
    public static $night = 235;

    const PALETTEFILEDIR = "resouces/colors/";
    const ASCIIDIR = "libraries/ascii";

    // ----------------------------------------------------------------
    // STARTUP STATIC HACK
    // ----------------------------------------------------------------
    public static function constructStatic()
    {
        // $climate = new \League\CLImate\CLImate;
        self::$climate = new \League\CLImate\CLImate;
        self::$boxer = new Boxer;
    }

    public static function debugVersion()
    {
        $cli = new \League\CLImate\CLImate;
        var_dump($cli);
        $cli->out('ver 0.0.1');
        exit(1);
    }

    /**
     * phreakHeader
     *
     * To Generate New Headers: figlet -f "ANSI Regular" -w 80 -Ssk Base16 |
     * lolcatjs -f >> Base16.txt
     *
     * @param mixed $output
     * @param mixed $category
     * @param mixed $lineLength
     *
     * @return void
     */
    public static function phreakHeader(
        $output,
        $category = "",
        $lineLength = 20
    ) {
        $line = self::printLine($lineLength);

        self::lineBreak();
        self::printASCIIArt("phreak", "white");
        self::moveCursor($output, "up", 2);
        self::moveCursor($output, "right", 19);
        self::Speaks($line);
        self::lineBreak();
        self::printASCIIArt($category, "white");

        return true;
    }

    /**
     * printLine
     *
     * Generates a randomly constructed line made up of dashes and dots, divided
     * into 3 segments. NOTE: The resulting line is actually 14 (FOURTEEN)
     * characters longer than whatever number is passed. This is do to the fact
     * that the 'dots' placed at the begining, end, and inbetween add 14
     * characters.
     *
     * @param  int $size
     *
     * @return string
     */
    public static function printLine($size = 60, $color = false)
    {
        $dots = " •• ";
        $line = "━";

        $rand1 = rand(7, 42) * 0.01;
        $rand2 = rand(13, 50) * 0.01;
        $seg1 = round($size * $rand1);
        $seg2 = round($size * $rand2);
        $seg3 = $size - ($seg1 + $seg2);

        $seg1L = str_repeat($line, $seg1);
        $seg2L = str_repeat($line, $seg2);
        $seg3L = str_repeat($line, $seg3);

        $fin = $dots . $seg1L . $dots . $seg2L . $dots . $seg3L . $dots;
        $final = trim($fin);

        if ($color !== false) {
            $final = self::colorizeString($final, self::$night);
        }

        return $final;
    }

    /**
     * lineBreak
     *
     * @param  mixed $amount
     *
     * @return void
     */
    public static function lineBreak($amount = 1)
    {
        $cli = self::$climate;

        $cli->br($amount);
    }

    public static function colorizeString($string, $background = false, $color = false)
    {
        $ANSIColor = new Color();

        $fgColor = self::$ocean;
        if (isset($color)) {
            $cI = intval($color);
            if ($cI > 0 && $cI < 257) {
                $fgColor = $cI;
            }
        }

        $result = $ANSIColor($string)->fg("color[" . $fgColor . "]");

        if ($background !== false) {
            $bI = intval($background);
            if ($bI > 0 && $bI < 257) {
                $result->bg("color[" . $bI . "]");
            }
        }

        return $result;
    }

    public static function printErrorExit(string $error)
    {
        self::printStatus($error, "ERROR");
        exit(1);
    }

    /**
     * Message
     *
     * output message w/ various status flags and matched color codings
     *
     * @param  string $msg
     * @param  string $status
     * @param  bool $tab
     *
     * @return void
     */
    public static function printStatus(
        $msg = "super phreak",
        $status = " !! ",
        $tab = false
    ) {
        // OLD TIMES
        //$cli = ($tab === true ? self::$climate->tab() : self::$climate);
        //$cli->lightBlue()->bold()->out(" <background_blue><white>[INFO]</white></background_blue>  " . $msg);

        switch ($status) {
            case "TODO":
            case "DEBUG":
                Messenger::debug($msg, $status);
                break;
            case "I":
            case "INFO":
            case "info":
                Messenger::info($msg, "INFO");
                break;
            case "G":
            case "GOOD":
            case "good":
                Messenger::success($msg, "SUCCESS");
                break;
            case "F":
            case "FAIL":
            case "fail":
            case "ERROR":
            case "error":
            case "BAD":
            case "bad":
            case "EXIT":
            case "exit":
            case "CRITICAL":
            case "critical":
                echo PHP_EOL;
                self::printASCIIArt("error");
                Messenger::critical($msg, "CRITICAL");
                echo PHP_EOL;
                exit(1);
                break;
            case "W":
            case "WARN":
            case "warn":
                Messenger::warning($msg, "WARNING");
                break;
            default:
                Messenger::status($msg, $status);
                break;
        }
    }

    /**
     * Speaks
     *
     * output a basic string or strings to the terminal. no formatting or color
     * codes
     *
     * @param  mixed $string
     * @param  bool $break
     *
     * @return bool
     */
    public static function Speaks($string, $break = false, $tab = false)
    {
        $cli = $tab === true ? self::$climate->tab() : self::$climate;

        if (is_array($string)) {
            foreach ($string as $s) {
                $cli->out($s);
                if ($break) {
                    $cli->br();
                }
            }

            return true;
        }

        $cli->out($string);
        if ($break) {
            $cli->br();
        }

        return true;
    }

    // ----------------------------------------------------------------
    // ASCII ART OUTPUT
    // ----------------------------------------------------------------

    /**
     * use climate to print a asciiart text file
     * @param                                  string $ascii_file name of the
     *                                                            file to
     *                                                            display
     * @param                                  string $color blue, red, green,
     *                                                       (default white)
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public static function printASCIIArt(
        $ascii_file = "spaceman",
        $color = "white"
    ) {
        $climate = new \League\CLImate\CLImate;

        $climate->addArt(self::ASCIIDIR);

        if ($color == "blue") {
            $climate->lightBlue()->boldDraw($ascii_file);
        } elseif ($color == "red") {
            $climate->red()->boldDraw($ascii_file);
        } elseif ($color == "green") {
            $climate->lightGreen()->boldDraw($ascii_file);
        } else {
            $climate->white()->boldDraw($ascii_file);
        }
    }

    // ---------------------------------------------------------------------------
    // CURSOR CONTROL
    // ---------------------------------------------------------------------------

    /**
     * moveCursor
     * @param  $direction
     * @param  $amount
     * @param  $output
     */
    public static function moveCursor($direction, $amount, $output)
    {
        $cursor = new Cursor($output);

        switch ($direction) {
            case "up":
                $cursor->moveUp($amount);
                break;
            case "left":
                $cursor->moveLeft($amount);
                break;
            case "right":
                $cursor->moveRight($amount);
                break;
            case "down":
                $cursor->moveDown($amount);
                break;
        }

        return true;
    }

    /**
     * moveThenPrintArray (outMove)
     *
     * move the cursor around before printing each line of an array (of strings)
     *
     * @param  mixed $textArray
     * @param  mixed $movements
     * @param  mixed $output
     *
     * @return void echoMovingData
     */
    public static function moveThenPrintArray($textArray, $movements, $output)
    {
        $cli = self::$climate;

        foreach ($textArray as $string) {
            if (isset($movements) && is_array($movements)) {
                foreach ($movements as $mvmnt => $value) {
                    self::moveCursor($mvmnt, $value, $output);
                }
            }
            //print_r($string);
            $cli->out($string);
        }

        return true;
    }

    /**
     * moveThenPrint (echoAndMove)
     *
     * move the cursor around and then print a string
     *
     * @param  object $output
     * @param  string $text
     * @param  array $move
     *
     * @return void
     */
    public static function moveThenPrint($text, $movements, $output)
    {
        if (isset($movements) && is_array($movements)) {
            foreach ($movements as $mvmnt => $value) {
                self::moveCursor($mvmnt, $value, $output);
            }
        }

        self::$climate->out($text);
    }

    // ----------------------------------------------------------------
    // TEXT MANIPULATIONS
    // ----------------------------------------------------------------

    /**
     * phreakWordwrap
     *
     * slightly smarter / more complex word wrapping function.
     *
     * @param                                  string $string
     * @param                                  int $width
     * @param                                  string $break
     *
     * @return                                 string
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public static function phreakWordwrap($string, $width = 75, $break = "\n")
    {
        // split on problem words over the line length
        $pattern = sprintf("/([^ ]{%d,})/", $width);
        $output = "";
        $words = preg_split(
            $pattern,
            $string,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );

        foreach ($words as $word) {
            if (false !== strpos($word, " ")) {
                // normal behavior, rebuild the string
                $output .= $word;
            } else {
                // work out how many characters would be on the current line
                $wrapped = explode($break, wordwrap($output, $width, $break));
                $count = $width - (iconv_strlen(end($wrapped)) % $width);

                // fill the current line and add a break
                $output .= substr($word, 0, $count) . $break;

                // wrap any remaining characters from the problem word
                $output .= wordwrap(substr($word, $count), $width, $break);
            }
        }

        // wrap the final output w/ PHP's native wordwrap()
        return wordwrap($output, $width, $break);
    }
}

Display::constructStatic();
