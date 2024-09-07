<?php

namespace App\Console;

use App\Console\TUI;
use App\Controllers\SixteenController;

use League\CLImate\CLImate;
use Colors\Color;

/**
 *
 *  Boxer
 *
 *  Wraps given input in a fancy ASCII Art box
 *
 *   + Wraps content to given width (otherwise default width)
 *   + Optionally colorize the output
 *   + Many "styles" of boxes available
 *
 */

class Boxer
{
    // GLOBAL VARIABLES
    public $climate;                     // Used via $this->climate->out('blah');
    public $bWIDTH   = 60;               // The standard width for the ASCII box
    public $bPAD     = 0;                // Padding space inside the box
    public $bMARGIN  = 0;                // Margin space outside the box
    public $bINDENT  = "";               // Content Indent Amount
    public $cWIDTH   = 0;
    public $bHEIGHT = 0;

    // Control Look & Feel of Box
    public $boxTYPE      = "reputation";
    public $boxTITLE     = "";
    public $boxCONTENT   = "";
    public $showCONTENT  = false;
    public $boxBtmTITLE  = false;
    public $themeName    = false;
    public $cON          = false;
    public $cC           = array();
    public $B16          = array();

    /**
     * constructStatic
     *
     * Cool Hack to get a constructor function in a Static Class.
     *
     * @return void
     */
    public function __construct()
    {
        setlocale(LC_ALL, 'en_US.UTF8');
        $this->climate = new CLImate;
    }

    // --------------------------------------------------------------
    //
    //  STYLISH THEMEABLE ASCII ART BOX STYLISH THEMEABLE ASCII ART BOX
    //
    // --------------------------------------------------------------

    /**
     * printBox
     *
     * Formerly $CMDPhreaks->outputBox($data ...)
     *
     * @param  mixed $data
     * @param  mixed $opts
     * @return void
     */
    public function printBox($data, $opts)
    {
        $cli = $this->climate;

        if (isset($opts["title"]))
            $this->setBoxTITLE($opts["title"]);

        if (isset($opts["btm_title"]))
            $this->setBoxBtmTITLE($opts["btm_title"]);

        if (isset($opts["height"]))
            $this->setBoxHEIGHT($opts["height"]);

        $width      = (isset($opts["width"]) ? $opts["width"] : 50);
        $type       = (isset($opts["type"]) ? $opts["type"] : "lover");
        $margin     = (isset($opts["margin"]) ? $opts["margin"] : 0);
        $padding    = (isset($opts["padding"]) ? $opts["padding"] : 0);
        $theme      = (isset($opts["theme"]) ? $opts["theme"] : false);
        $contentVis = ((isset($opts["vis_content"]) && $opts["vis_content"] === true) ? true : false);

        if (!isset($theme) || $theme !== true)
            $theme = (isset($opts["color"]) ? $opts["color"] : false);

        $this->setBoxCONTENT($data);
        $this->setBoxWIDTH($width);
        $this->setBoxTYPE($type);
        $this->setBoxMARGIN($margin);
        $this->setBoxPADDING($padding);
        $this->setBoxTHEME($theme);

        if ($contentVis !== false)
            $this->setShowCONTENT($contentVis);

        $string = $this->generateBox();

        $cli->out($string);
    }

    public function generateHeader()
    {
        // Ensure a "clean" title
        $bT = $this->getBoxTITLE();
        if ($bT !== false)
            $boxTITLE = $this->clearUTF($this->getBoxTITLE());
        else
            $boxTITLE = false;

        // Load Box Characters
        $boxTYPE    = $this->getBoxTYPE();
        $boxCHARS   = $this->getBoxChars($boxTYPE);

        // Box Size Details
        $boxWIDTH   = $this->getBoxWIDTH();
        $boxPAD     = $this->getBoxPADDING();
        $boxMARGIN  = $this->getBoxMARGIN();

        // Optionally Set Color Scheme
        $boxTHEME   = $this->getBoxTHEME();

        // Check for min width
        if ($boxWIDTH < 20)
            TUI::Message("Minimum Box width is 20!. Cannot Continue.", "FAIL");

        // ---------------------------------------------------------------------------
        // BOX TITLE / HEADER
        // ---------------------------------------------------------------------------
        $headerLength   = mb_strlen($boxCHARS["header"]);
        $padTitle       = $boxWIDTH - $headerLength;
        $padBody        = $boxWIDTH - 2;

        if ($padTitle < 1) {
            // Box is to small for fancy Header, so simplify to mini version
            $boxCHARS["header"] = "◤◢◤◢◤◢███";
            $boxCHARS["headerRpt"] = "█";

            $headerLength   = mb_strlen($boxCHARS["header"]);
            $padTitle       = $boxWIDTH - $headerLength;
            $padBody        = $boxWIDTH - 2;
        }

        // Optionally insert blank 'Margin' lines & empty 'Margin' spaces
        $theTop       = "";
        $leftMargin   = "";
        $topTitle     = "";

        if ($boxMARGIN > 0) {
            $marginAmount = ceil($boxMARGIN * 0.5);
            for ($mc = 0; $mc < $marginAmount; $mc++) {
                $theTop .= PHP_EOL;
            }

            $leftMargin = str_repeat(
                " ",
                $boxMARGIN
            );
            $this->setBoxINDENT($leftMargin);
        }

        // Build the header line with computed data
        $hAlign = (isset($boxCHARS["hAlign"]) ? $boxCHARS["hAlign"] : "right");
        switch ($hAlign) {
            case 'center':
                $topBar = $this->phreakPadString($boxCHARS["header"], $padTitle, $boxCHARS["headerRpt"]);
                break;
            case 'left':
                $topBar = $boxCHARS["header"] . str_repeat($boxCHARS["headerRpt"], $padTitle);
                break;
            default:
            case 'right':
                $topBar = str_repeat($boxCHARS["headerRpt"], $padTitle) . $boxCHARS["header"];
                break;
        }

        $boxTop = $boxCHARS["leftTop"] . str_repeat($boxCHARS["topLine"], $padBody) . $boxCHARS["rightTop"];

        $boxINDENT  = $this->getBoxIndent();

        $theTop = $boxINDENT . $topBar . PHP_EOL;

        $theTop .= $boxINDENT . $boxTop . PHP_EOL;

        $font = (isset($boxCHARS["font"]) ? $boxCHARS["font"] : "pagga");
        $result = $this->figletTitle($boxTITLE, $boxCHARS, $font);

        $theTop .= $result;

        $topBtmBar = $boxCHARS["leftBtm"]  . str_repeat($boxCHARS["btmLine"], $padBody) . $boxCHARS["rightBtm"];
        if ($boxTHEME !== false) {
            $topBtmBar = $this->cC[2]($topBtmBar)->bold->fg('color[' . $this->B16["main"] . ']');
            if ($this->B16["bg"] !== false)
                $topBtmBar->bg('color[' . $this->B16["bg"] . ']');
        }

        $topTitle .= $boxINDENT . $topBtmBar . PHP_EOL;

        return $theTop . $topTitle;
    }

    /**
     * generateBox formerly genAsciiBox
     *
     * Creates **MOST** of an ASCII Art box for a given width (otherwise global width)
     * The top and bottom are generated, and the content to use in the middle is given.
     *
     * @param  mixed $basicTitle
     * @param  mixed $width
     * @param  mixed $type
     * @return string the resulting text to output
     */
    public function generateBox()
    {

        // ---------------------------------------------------------------------------
        // SETUP PARAMS
        // ---------------------------------------------------------------------------

        // Ensure a "clean" title
        $bT = $this->getBoxTITLE();
        if ($bT !== false)
            $boxTITLE = $this->clearUTF($this->getBoxTITLE());
        else
            $boxTITLE = false;

        // Load Box Characters
        $boxTYPE    = $this->getBoxTYPE();
        $boxCHARS   = $this->getBoxChars($boxTYPE);

        // Box Size Details
        $boxWIDTH   = $this->getBoxWIDTH();
        $boxPAD     = $this->getBoxPADDING();
        $boxMARGIN  = $this->getBoxMARGIN();

        // Optionally Set Color Scheme
        $boxTHEME   = $this->getBoxTHEME();

        // Check for min width
        if ($boxWIDTH < 20)
            TUI::Message("Minimum Box width is 20!. Cannot Continue.", "FAIL");


        if ($boxTHEME !== false) {
            // Turn Global color flag ON
            $this->setColorOutput(true);

            // Load Base16 theme data (as ANSI codes)
            if (!is_int($boxTHEME))
                $this->B16 = SixteenController::loadB16Theme();
            else
                $this->B16 = array("main" => $boxTHEME, "second" => $boxTHEME, "bg" => false);

            // Instantiate Color objects for later usage
            for ($cC = 0; $cC < 6; $cC++) {
                $this->cC[] = new  Color();
            }
        }

        // ---------------------------------------------------------------------------
        // BOX TITLE / HEADER
        // ---------------------------------------------------------------------------
        $headerLength   = mb_strlen($boxCHARS["header"]);
        $padTitle       = $boxWIDTH - $headerLength;
        $padBody        = $boxWIDTH - 2;

        if ($padTitle < 1) {
            // Box is to small for fancy Header, so simplify to mini version
            $boxCHARS["header"] = "◤◢◤◢◤◢███";
            $boxCHARS["headerRpt"] = "█";

            $headerLength   = mb_strlen($boxCHARS["header"]);
            $padTitle       = $boxWIDTH - $headerLength;
            $padBody        = $boxWIDTH - 2;
        }

        // Optionally insert blank 'Margin' lines & empty 'Margin' spaces
        $theTop       = "";
        $leftMargin   = "";
        $topTitle     = "";

        if ($boxMARGIN > 0) {
            $marginAmount = ceil($boxMARGIN * 0.5);
            for ($mc = 0; $mc < $marginAmount; $mc++) {
                $theTop .= PHP_EOL;
            }

            $leftMargin = str_repeat(" ", $boxMARGIN);
            $this->setBoxINDENT($leftMargin);
        }

        $boxINDENT  = $this->getBoxIndent();

        // Build the header line with computed data
        $hAlign = (isset($boxCHARS["hAlign"]) ? $boxCHARS["hAlign"] : "right");
        switch ($hAlign) {
            case 'center':
                $topBar = $this->phreakPadString($boxCHARS["header"], $padTitle, $boxCHARS["headerRpt"]);
                break;
            case 'left':
                $topBar = $boxCHARS["header"] . str_repeat($boxCHARS["headerRpt"], $padTitle);
                break;
            default:
            case 'right':
                $topBar = str_repeat($boxCHARS["headerRpt"], $padTitle) . $boxCHARS["header"];
                break;
        }

        $boxTop = $boxCHARS["leftTop"] . str_repeat($boxCHARS["topLine"], $padBody) . $boxCHARS["rightTop"];

        if ($boxTHEME !== false) {
            $topBar = $this->cC[0]($topBar)->bold->fg('color[' . $this->B16["main"] . ']');
            $boxTop = $this->cC[1]($boxTop)->bold->fg('color[' . $this->B16["main"] . ']');

            if ($this->B16["bg"] !== false) {
                $topBar->bg('color[' . $this->B16["bg"] . ']');
                $boxTop->bg('color[' . $this->B16["bg"] . ']');
            }
        }

        $theTop .= $boxINDENT . $topBar . PHP_EOL;

        if ($boxTITLE !== false) {
            $theTop .= $boxINDENT . $boxTop . PHP_EOL;

            $font = (isset($boxCHARS["font"]) ? $boxCHARS["font"] : "pagga");
            $result = $this->figletTitle($boxTITLE, $boxCHARS, $font);

            $theTop .= $result;

            $topBtmBar = $boxCHARS["leftBtm"]  . str_repeat($boxCHARS["btmLine"], $padBody) . $boxCHARS["rightBtm"];
            if ($boxTHEME !== false) {
                $topBtmBar = $this->cC[2]($topBtmBar)->bold->fg('color[' . $this->B16["main"] . ']');
                if ($this->B16["bg"] !== false)
                    $topBtmBar->bg('color[' . $this->B16["bg"] . ']');
            }

            $topTitle .= $boxINDENT . $topBtmBar . PHP_EOL;
        }

        // Quick Helper for doing padding
        $EMPTY = $boxCHARS["sideLT"] . str_repeat($boxCHARS["noLine"], $padBody) . $boxCHARS["sideRT"];
        if ($boxTHEME !== false) {
            $EMPTY = $this->cC[3]($EMPTY)->bold->fg('color[' . $this->B16["main"] . ']');
            if ($this->B16["bg"] !== false)
                $EMPTY->bg('color[' . $this->B16["bg"] . ']');
        }
        $EMPTYLINE = $boxINDENT . $EMPTY . PHP_EOL;

        // BREAK BTWN HEADER AND BODY + OPTIONAL EMPTY LINE PADDING
        $theMiddle = $EMPTYLINE;
        if ($boxPAD > 0) {
            for ($pc = 0; $pc < ceil($boxPAD * 0.5); $pc++) {
                $theMiddle .= $EMPTYLINE;
            }
        }

        // ---------------------------------------------------------------------------
        // MAIN CONTENT AREA!!!
        // ---------------------------------------------------------------------------
        $boxContent  = $this->getBoxCONTENT();
        $showContent = $this->getShowCONTENT();
        $forceHeight = $this->getBoxHEIGHT();

        if (!is_array($boxContent) && $showContent !== false) {
            //echo "NO ARRAY" . PHP_EOL;
            $wrapWidth = ($boxPAD > 0 ? ($boxWIDTH - 4) - ceil($boxPAD * 0.5) : $boxWIDTH - 4);
            $wrappedContent = $this->phreakWordwrap($boxContent, $wrapWidth);
            $boxContent   = preg_split("/\r\n|\n|\r/", $wrappedContent);
        }

        if ($boxContent !== false && is_array($boxContent)) {
            foreach ($boxContent as $line) {
                $cleanLine = (($showContent !== false) ? $line : " ");
                $theMiddle .= $this->flankString($cleanLine, $boxWIDTH, $boxCHARS["sideLT"], $boxCHARS["sideRT"]) . PHP_EOL;
            }
        }

        if ($forceHeight > 0) {
            $hCount = 0;
            while ($hCount < $forceHeight) {
                $theMiddle .= $this->flankString(" ", $boxWIDTH, $boxCHARS["sideLT"], $boxCHARS["sideRT"]) . PHP_EOL;
                $hCount++;
            }
        }

        // START On the Bottom. Optionally add in PADDING
        $theBottom = "";
        if ($boxPAD > 0) {
            for ($pc = 0; $pc < ceil($boxPAD * 0.5); $pc++) {
                $theBottom .= $EMPTYLINE;
            }
        }

        // ---------------------------------------------------------------------------
        // BOTTOM LINE OR BOTTOM TITLE
        // ---------------------------------------------------------------------------

        $leftEnd = (isset($boxCHARS["leftEnd"]) ? $boxCHARS["leftEnd"] : $boxCHARS["leftBtm"]);
        $rightEnd = (isset($boxCHARS["rightEnd"]) ? $boxCHARS["rightEnd"] : $boxCHARS["rightBtm"]);

        $btmTitle = $this->getBoxBtmTITLE();

        if (!$btmTitle) {
            $rockBottom = $leftEnd  . str_repeat($boxCHARS["btmLine"], $padBody) . $rightEnd;
        } else {

            // build the bottom title line which is as follows:
            // 2/3 width btmLine char | title |  1/3 width btmLine char
            $btmTitleFormat   = "▖" . $btmTitle . "▗";
            $btmTitleLength   = mb_strlen($btmTitleFormat);
            $btmRemLength     = $padBody - $btmTitleLength;
            $btmStartSpace    = round(($btmRemLength * 0.66), 0);
            $btmEndSpace      = $btmRemLength - $btmStartSpace;
            $padBodyNice      = round($padBody, 0);

            // ensure we are not over or under the length
            if (round(($btmStartSpace + $btmEndSpace + $btmTitleLength), 0) !== $padBodyNice) {
                if (round(($btmStartSpace + $btmEndSpace + $btmTitleLength), 0) < $padBodyNice) {
                    while (($btmStartSpace + $btmEndSpace + $btmTitleLength) < $padBodyNice) {
                        $btmStartSpace++;
                    }
                } elseif (round(($btmStartSpace + $btmEndSpace + $btmTitleLength)) > $padBodyNice) {
                    while (round(($btmStartSpace + $btmEndSpace + $btmTitleLength)) > $padBodyNice) {
                        $btmStartSpace--;
                    }
                }
            }

            $rockBottom = $leftEnd . str_repeat($boxCHARS["btmLine"], $btmStartSpace) . $btmTitleFormat . str_repeat($boxCHARS["btmLine"], $btmEndSpace) . $rightEnd;
        }

        if ($boxTHEME !== false) {
            $rockBottom = $this->cC[4]($rockBottom)->bold->fg('color[' . $this->B16["main"] . ']');
            if ($this->B16["bg"] !== false)
                $rockBottom->bg('color[' . $this->B16["bg"] . ']');
        }
        $theBottom .= $boxINDENT . $rockBottom . PHP_EOL;

        // Optionally add in MARGIN
        if ($boxMARGIN >= 1) {
            $theBottom = rtrim($theBottom, PHP_EOL);

            $marginAmount = round(($boxMARGIN * 0.5), 0);
            for ($mc = 0; $mc < $marginAmount; $mc++) {
                $theBottom .= PHP_EOL;
            }
        }

        // Catch all that shows only a cool header if the box has
        // a height of 0 and content set to false!
        if ($this->bHEIGHT < 1 && $this->boxCONTENT === false) {
            $theBottom = "";
            $theMiddle = "";
        }

        return $theTop . $topTitle . $theMiddle . $theBottom;
    }


    /**
     * getBoxChars
     *
     * @param string $type ascii box version
     * @return array
     */
    public function getBoxChars($type = "lover")
    {
        $parts = array();

        switch ($type) {
            case '1989':
                $parts["header"]    = "▃▂▄▇▅▂█▃▆█▃▅▂▃█▂▆▅█▃▂▆▂";
                $parts["hAlign"]    = "center";
                $parts["headerRpt"] = "▃";
                $parts["leftTop"]   = "▛";
                $parts["leftMid"]   = "▏";
                $parts["leftBtm"]   = "▙";
                $parts["leftEnd"]   = "▜";
                $parts["rightTop"]  = "▜";
                $parts["rightMid"]  = "▕";
                $parts["rightBtm"]  = "▟";
                $parts["rightEnd"]  = "▛";
                $parts["topLine"]   = "▀";
                $parts["btmLine"]   = "▁";
                $parts["noLine"]    = " ";
                $parts["sideLT"]    = "▏";
                $parts["sideRT"]    = "▕";
                break;
            case 'red':
                $parts["header"]    = "◤◢◤◢◤◢◤◢◤◢◤◢◤◢◤◢◤◢◤◢◤◢◤◢███";
                $parts["headerRpt"] = "█";
                $parts["leftTop"]   = "▛";
                $parts["leftMid"]   = "▖";
                $parts["leftBtm"]   = "▙";
                $parts["rightTop"]  = "▜";
                $parts["rightMid"]  = "▗";
                $parts["rightBtm"]  = "▟";
                $parts["topLine"]   = "▔";
                $parts["btmLine"]   = "▁";
                $parts["noLine"]    = " ";
                $parts["sideLT"]    = "▘";
                $parts["sideRT"]    = "▝";
                break;
            case 'reputation':
                //  $parts["header"]    = " •• ━━ •• ━━━ • ━ •• ━━ •• ━━━ ••• ━";
                $parts["header"]    = "◣ ❶ ❸  ◥████◤  ❱ ❱ ❱  ◢██";
                $parts["headerRpt"] = "█";
                $parts["leftTop"]   = "┏";
                $parts["leftMid"]   = "┃";
                $parts["leftBtm"]   = "┣";
                $parts["leftEnd"]   = "┗";
                $parts["rightTop"]  = "┓";
                $parts["rightMid"]  = "┃";
                $parts["rightBtm"]  = "┫";
                $parts["rightEnd"]  = "┛";
                $parts["topLine"]   = "━";
                $parts["btmLine"]   = "━";
                $parts["noLine"]    = " ";
                $parts["sideLT"]    = "┃";
                $parts["sideRT"]    = "┃";
                break;
            case 'lover':
                //  $parts["header"]    = "◤◢◣◥◤◢◣◥◤◢◣◥◤◢◣◥◤◢◣◥█████";

                $parts["header"]    = "███◤◢◤◢◤◢◤ ◢◤◢██◤ ◢◤ ◢██◤◢";
                $parts["headerRpt"] = "█";
                $parts["hAlign"]    = "left";
                $parts["leftTop"]   = "▮";
                $parts["leftMid"]   = "⏽";
                $parts["leftBtm"]   = "▮";
                $parts["leftEnd"]   = "▮";
                $parts["rightTop"]  = "▮";
                $parts["rightMid"]  = "⏽";
                $parts["rightBtm"]  = "▮";
                $parts["rightEnd"]  = "▮";
                $parts["topLine"]   = "▔";
                $parts["btmLine"]   = "━";
                $parts["noLine"]    = " ";
                $parts["sideLT"]    = "⏽";
                $parts["sideRT"]    = "⏽";
                break;
            case 'folklore':
                $parts["header"]    = "██◤ ◢◤◢◤◢◤◢◤ ◢███◤ ◢██◣";
                $parts["headerRpt"] = "█";
                $parts["leftTop"]   = "█";
                $parts["leftMid"]   = "█";
                $parts["leftBtm"]   = "█";
                $parts["rightTop"]  = "█";
                $parts["rightMid"]  = "█";
                $parts["rightBtm"]  = "█";
                $parts["topLine"]   = "▔";
                $parts["btmLine"]   = "▃";
                $parts["noLine"]    = " ";
                $parts["sideLT"]    = "█";
                $parts["sideRT"]    = "█";
                break;
            default:
                $parts["header"]    = " ";
                $parts["headerRpt"] = " ";
                $parts["leftTop"]   = " ";
                $parts["leftMid"]   = " ";
                $parts["leftBtm"]   = " ";
                $parts["rightTop"]  = " ";
                $parts["rightMid"]  = " ";
                $parts["rightBtm"]  = " ";
                $parts["topLine"]   = " ";
                $parts["btmLine"]   = " ";
                $parts["noLine"]    = " ";
                $parts["sideLT"]    = " ";
                $parts["sideRT"]    = " ";
                break;
        }

        return $parts;
    }


    /* START FIGLET */

    /**
     * figletTitle
     *
     * @param  mixed $text
     * @param  mixed $chars
     * @param  mixed $font
     * @return string resulting figlet text
     */
    public function figletTitle($text, $chars, $font = "pagga")
    {
        $boxWIDTH  = $this->getBoxWIDTH();
        $boxCOLOR  = $this->getColorOutput();

        exec("figlet -w " . $boxWIDTH . " -s -f '" . $font . "' " . $text, $execOutput);

        $result = "";

        $boxINDENT = $this->getBoxINDENT();

        if ($boxCOLOR !== false)
            $CLI_Color = new Color();

        foreach ($execOutput as $titleROW) {

            $hROW = $this->figletHeaderAlign($titleROW);

            $headRIGHT    = $chars["rightMid"];
            $headLEFT     = $chars["leftMid"];

            $string = $headLEFT . $hROW . $headRIGHT;

            if ($boxCOLOR !== false) {
                $ColorString = $CLI_Color($string)->bold->fg('color[' . $this->B16["main"] . ']');
                if ($this->B16["bg"] !== false) {
                    $ColorString->bg('color[' . $this->B16["bg"] . ']');
                }

                $result .= $boxINDENT . $ColorString . PHP_EOL;
            } else {
                $result .= $boxINDENT . $string . PHP_EOL;
            }
        }

        return $result;
    }

    public function figletHeaderAlign($textRow)
    {
        $result = "";
        $boxWIDTH  = $this->getBoxWIDTH();
        //$boxINDENT = $this->getBoxINDENT();

        $headLEN   = mb_strlen($textRow);

        $splitWIDE = (round(($boxWIDTH - $headLEN), 0) * 0.5);
        $splitWIDE--;

        $headPAD = str_repeat(" ", $splitWIDE);
        $result = $headPAD . $textRow . $headPAD;

        return $result;
    }

    /* END FIGLET */


    /**
     * flankString
     *
     * fancy helper function for wrapping a string in some nice ASCII characters & empty spaces.
     *
     * @param string $rowString
     * @param string $sideL
     * @param string $sideR
     * @param mixed $width
     * @return string
     */
    public function flankString($rowString = "  ", $width = 0, $sideL = " ", $sideR = " ")
    {
        $boxINDENT = $this->getBoxINDENT();
        $boxCOLOR  = $this->getColorOutput();

        $padString = $this->phreakPadString($rowString, ($width - 2), " ", STR_PAD_BOTH);

        // Optionally Set Color Scheme
        if ($boxCOLOR !== false) {
            $CLIColor = new Color();
            $result = $CLIColor($sideL . $padString . $sideR)->bold->fg('color[' . $this->B16["main"] . ']');
            if ($this->B16["bg"] !== false)
                $result->bg('color[' . $this->B16["bg"] . ']');
        } else {
            $result = $sideL . $padString . $sideR;
        }

        return $boxINDENT . $result;
    }

    /**
     * phreakWordwrap
     *
     * slightly smarter / more complex word wrapping function.
     *
     * @param string $string
     * @param int $width
     * @param string $break
     * @return string
     */
    public function phreakWordwrap($string, $width = 75, $break = "\n")
    {
        // split on problem words over the line length
        $pattern = sprintf('/([^ ]{%d,})/', $width);
        $output = '';
        $words = preg_split($pattern, $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        foreach ($words as $word) {
            if (false !== strpos($word, ' ')) {
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


    /**
     * mb_str_pad
     *
     * multibyte string padding
     *
     * @param  mixed $input
     * @param  mixed $pad_length
     * @param  mixed $pad_string
     * @param  mixed $pad_type
     * @param  mixed $encoding
     * @return string
     */
    public function phreakPadString($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_BOTH, $encoding = 'UTF-8')
    {
        $presetWIDTH = $this->getContentWIDTH();

        if (isset($presetWIDTH) && $presetWIDTH > 0) {
            // echo "OVERRIDE" . PHP_EOL;
            $input_length = $presetWIDTH;
        } else {
            $clean = iconv("UTF-8", "ISO-8859-1//IGNORE", $input);
            $cleanLen = mb_strlen($clean);
            $input_length = $cleanLen;
        }

        $pad_string_length = iconv_strlen($pad_string, $encoding);

        if (
            $pad_length <= 0 || ($pad_length - $input_length) <= 0
        ) {
            return $input;
        }

        $num_pad_chars = $pad_length - $input_length;

        switch ($pad_type) {
            case STR_PAD_RIGHT:
                $left_pad = 0;
                $right_pad = $num_pad_chars;
                break;

            case STR_PAD_LEFT:
                $left_pad = $num_pad_chars;
                $right_pad = 0;
                break;

            case STR_PAD_BOTH:
                $left_pad = floor($num_pad_chars / 2);
                $right_pad = $num_pad_chars - $left_pad;
                break;
        }

        $result = '';
        for ($i = 0; $i < $left_pad; ++$i) {
            $result .= mb_substr($pad_string, $i % $pad_string_length, 1, $encoding);
        }

        $result .= $input;
        for ($i = 0; $i < $right_pad; ++$i) {
            $result .= mb_substr($pad_string, $i % $pad_string_length, 1, $encoding);
        }

        return $result;
    }


    /**
     * clearUTF
     *
     * Remove any and all invalid chars (German Umlauts etc)
     *
     * @param  string $s
     * @return string
     */
    private function clearUTF($s)
    {
        $r = '';
        $s1 = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        for ($i = 0; $i < iconv_strlen($s1); $i++) {
            $ch1 = $s1[$i];
            $ch2 = mb_substr($s, $i, 1);
            $r .= $ch1 == '?' ? $ch2 : $ch1;
        }
        return $r;
    }


    /**
     * @todo Test this out! Currently Unused. Based on count_chars_unicode
     * @url  https://www.php.net/manual/en/function.count-chars.php
     *
     * @todo also check into iconv conversions
     * @url  http://phpcoderweb.com/manual/function-iconv_2803.html
     */
    public function unicodeCounter($str, $x = false)
    {
        $tmp = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tmp as $c) {
            $chr[$c] = isset($chr[$c]) ? $chr[$c] + 1 : 1;
        }
        return is_bool($x)
            ? ($x ? $chr : count($chr))
            : $chr[$x];
    }

    // ---------------------------------------------------------------------------
    // GETTERS AND SETTERS!!!!!!!
    // ---------------------------------------------------------------------------

    /**
     * Get the value of themeName
     */
    public function getBoxTHEME()
    {
        return $this->themeName;
    }

    /**
     * Set the value of themeName
     *
     * @return  self
     */
    public function setBoxTHEME($themeName)
    {
        $this->themeName = $themeName;

        return $this;
    }

    /**
     * Get the value of cON
     */
    public function getColorOutput()
    {
        return $this->cON;
    }

    /**
     * Set the value of cON
     *
     * @return  self
     */
    public function setColorOutput($cON)
    {
        $this->cON = $cON;

        return $this;
    }

    /**
     * Get the value of bWIDTH
     */
    public function getBoxWIDTH()
    {
        return $this->bWIDTH;
    }

    /**
     * Set the value of bWIDTH
     *
     * @return  self
     */
    public function setBoxWIDTH($bWIDTH)
    {
        $this->bWIDTH = $bWIDTH;

        return $this;
    }

    /**
     * Get the value of bMARGIN
     */
    public function getBoxMARGIN()
    {
        return $this->bMARGIN;
    }

    /**
     * Set the value of bMARGIN
     *
     * @return  self
     */
    public function setBoxMARGIN($bMARGIN)
    {
        $this->bMARGIN = $bMARGIN;

        return $this;
    }

    /**
     * Get the value of bPAD
     */
    public function getBoxPADDING()
    {
        return $this->bPAD;
    }

    /**
     * Set the value of bPAD
     *
     * @return  self
     */
    public function setBoxPADDING($bPAD)
    {
        $this->bPAD = $bPAD;

        return $this;
    }

    /**
     * Get the value of boxTYPE
     */
    public function getBoxTYPE()
    {
        return $this->boxTYPE;
    }

    /**
     * Set the value of boxTYPE
     *
     * Options are: reputation, folklore, lover or red
     *
     * @return  self
     */
    public function setBoxTYPE($boxTYPE)
    {
        $this->boxTYPE = $boxTYPE;

        return $this;
    }

    /**
     * Get the value of boxTITLE
     */
    public function getBoxTITLE()
    {
        return $this->boxTITLE;
    }

    /**
     * Set the value of boxTITLE
     *
     * @return  self
     */
    public function setBoxTITLE($boxTITLE)
    {
        $this->boxTITLE = $boxTITLE;

        return $this;
    }

    /**
     * Get the value of boxCONTENT
     */
    public function getBoxCONTENT()
    {
        return $this->boxCONTENT;
    }

    /**
     * Set the value of boxCONTENT
     *
     * @return  self
     */
    public function setBoxCONTENT($boxCONTENT)
    {
        $this->boxCONTENT = $boxCONTENT;

        return $this;
    }

    /**
     * Get the value of cWIDTH
     */
    public function getContentWIDTH()
    {
        return $this->cWIDTH;
    }

    /**
     * Set the value of cWIDTH
     *
     * @return  self
     */
    public function setContentWIDTH($cWIDTH)
    {
        $this->cWIDTH = $cWIDTH;

        return $this;
    }

    /**
     * Get the value of boxBtmTITLE
     */
    public function getBoxBtmTITLE()
    {
        return $this->boxBtmTITLE;
    }

    /**
     * Set the value of boxBtmTITLE
     *
     * @return  self
     */
    public function setBoxBtmTITLE($boxBtmTITLE)
    {
        $this->boxBtmTITLE = $boxBtmTITLE;

        return $this;
    }

    /**
     * Get the value of bINDENT
     */
    public function getBoxINDENT()
    {
        return $this->bINDENT;
    }

    /**
     * Set the value of bINDENT
     *
     * @return  self
     */
    public function setBoxINDENT($bINDENT)
    {
        $this->bINDENT = $bINDENT;

        return $this;
    }

    /**
     * Get the value of showCONTENT
     */
    public function getShowCONTENT()
    {
        return $this->showCONTENT;
    }

    /**
     * Set the value of showCONTENT
     *
     * @return  self
     */
    public function setShowCONTENT($showCONTENT)
    {
        $this->showCONTENT = $showCONTENT;

        return $this;
    }

    /**
     * Get the value of bHEIGHT
     */
    public function getBoxHEIGHT()
    {
        return $this->bHEIGHT;
    }

    /**
     * Set the value of bHEIGHT
     *
     * @return  self
     */
    public function setBoxHEIGHT($bHEIGHT)
    {
        $this->bHEIGHT = $bHEIGHT;

        return $this;
    }
}
