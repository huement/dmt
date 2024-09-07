<?php

namespace App\Console;

use ANSI;
use AnsiToRgb;
use App\Console\BoxerMini;
use Carbon\Carbon;
use Codedungeon\PHPMessenger\Facades\Messenger;
use Colors\Color;
use Laminas\Text\Figlet\Figlet;
use League\CLImate\CLImate;

require_once 'libraries/AnsiToRgb.php';
require_once 'libraries/ANSI.php';
use MikeAlmond\Color\Color as MAColor;
use Pixeler\Pixeler;
use Symfony\Component\Console\Cursor;

/**
 * TUI [ text user interface ]
 *
 * Handles all the Random basic functions needed to make Wallpapers, such as:
 *
 * + Gather up all the available palette files into an array + output any status
 * messages to the command line + Compute file size of wallpaper folder(s)
 */

class TUI
{
 // GLOBAL VARIABLES

 public static $climate; // Used via self::$climate->out('blah');
 public static $globalWidth = 60;
 public static $asciiArt    = "libraries/ascii/spaceman.txt";
 public static $ocean       = 42;
 public static $night       = 235;

 const PALETTEFILEDIR = "resouces/colors/";

 // ----------------------------------------------------------------
 // STARTUP ACTION(S)
 // ----------------------------------------------------------------

 public static function constructStatic()
 {
  self::$climate = new CLImate();
 }

 // ----------------------------------------------------------------
 // HELP & ERROR MESSAGES
 // ----------------------------------------------------------------

 public static function commandErrorMsg(
  $cmdName = "",
  $errorMsg = "",
  $showArt = true,
  $spaces = true
 ) {
  if ($spaces) {
   self::$climate->br();
  }

  if ($showArt) {
   self::printASCIIArt("spaceman", "white");
  }

  // $length = strlen($errorMsg);

  self::$climate
   ->backgroundRed()
   ->white()
   ->bold()
   ->out($cmdName);
  self::$climate->out($errorMsg);

  if ($spaces) {
   self::$climate->br(2);
  }
 }

 public static function echoHelpScreen($cat, $msg)
 {
  // TODO: This function is garbage and dog shit.

  // TODO: Instead, look if there is an ascii art for given category, if not, use generic help ascii file.

  // TODO: Then put the message in a nice padded box.
  // TODO: similar to how the color command etc do help.

  $cli = self::$climate;

  $cli->br();

  $t          = new BoxerMini($cat);
  $t->width   = 80;
  $t->align   = "center";
  $t->type    = "line_single";
  $t->padding = 2;

  $b          = new BoxerMini($msg);
  $b->width   = 80;
  $b->align   = "center";
  $b->type    = "line_thick";
  $b->padding = 4;

  self::Speaks($t->render());
  self::Speaks($b->render());

  $cli->br(2);
 }

 // ----------------------------------------------------------------
 // BUILD & RETURN FORMATTED TITLES & OUTLINE BOXES
 // ----------------------------------------------------------------

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
  $line = self::generateLine($lineLength);

  self::Break ();
  self::printASCIIArt("phreak", "white");
  self::moveCursor($output, "up", 2);
  self::moveCursor($output, "right", 19);
  self::Speaks($line);
  self::Break ();
  self::printASCIIArt($category, "white");

  return true;
 }

 public static function echoPhreakTitle(
  $title,
  $font = "pagga",
  $topLine = false
 ) {
  exec("figlet -s -f '" . $font . "' " . $title, $execOutput);

  $rlen       = mb_strlen($execOutput[ 0 ]) + 4;
  $lineString = self::generateLine($rlen);
  $colorLine  = self::colorIt($lineString);

  if ($topLine !== false) {
   self::$climate
    ->br(2)
    ->out($colorLine)
    ->br(1);
  }

  foreach ($execOutput as $row) {
   $colorRow = self::colorIt($row);
   self::$climate
    ->tab()
    ->bold()
    ->out($colorRow);
  }

  self::$climate
   ->br()
   ->out($colorLine)
   ->br(3);
 }

 public static function colorPreviewLine($color)
 {
  $baseColor    = MAColor::fromHex($color);
  $displayLabel = '▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒ ' . $color;
  $rgb          = $baseColor->getRgb();
  $ansiColor    = AnsiToRgb::toAnsi($rgb[ "r" ], $rgb[ "g" ], $rgb[ "b" ]);
  $ansiString   = ANSI::color256($ansiColor) . $displayLabel . ANSI::reset();

  return $ansiString;
 }

 /**
  * generateLine
  *
  * Generates a randomly constructed line made up of dashes and dots, divided
  * into 3 segments. NOTE: The resulting line is actually 14 (FOURTEEN)
  * characters longer than whatever number is passed. This is do to the fact
  * that the 'dots' placed at the begining, end, and inbetween add 14
  * characters.
  *
  * @param int $size
  *
  * @return string
  */
 public static function generateLine($size = 60, $color = false)
 {
  $dots = " •• ";
  $line = "━";

  $rand1 = rand(7, 42) * 0.01;
  $rand2 = rand(13, 50) * 0.01;
  $seg1  = round($size * $rand1);
  $seg2  = round($size * $rand2);
  $seg3  = $size - ($seg1 + $seg2);

  $seg1L = str_repeat($line, $seg1);
  $seg2L = str_repeat($line, $seg2);
  $seg3L = str_repeat($line, $seg3);

  $fin   = $dots . $seg1L . $dots . $seg2L . $dots . $seg3L . $dots;
  $final = trim($fin);

  if ($color !== false) {
   $final = self::colorIt($final, self::$night);
  }

  return $final;
 }

 /**
  * Break
  *
  * @param mixed $amount
  *
  * @return void
  */
 public static function break ($amount = 1)
 {
  $cli = self::$climate;

  $cli->br($amount);
 }

 public static function colorIt($string, $background = false, $color = false)
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

 // ----------------------------------------------------------------
 // STRING MESSAGE DISPLAY
 // ----------------------------------------------------------------

 /**
  * Message
  *
  * output message w/ various status flags and matched color codings
  *
  * @param string $msg
  * @param string $status
  * @param bool $tab
  *
  * @return void
  */
 public static function Message(
  $msg = "super phreak",
  $status = "GOOD",
  $tab = false
 ) {
  // OLD TIMES
  //$cli = ($tab === true ? self::$climate->tab() : self::$climate);
  //$cli->lightBlue()->bold()->out(" <background_blue><white>[INFO]</white></background_blue>  " . $msg);

  switch ($status) {
   case "TODO":
   case "DEBUG":
    $alert = $status === "TODO" ? "TODO" : "DEBUG";
    Messenger::debug($msg, $alert);
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
    echo PHP_EOL;
    self::printASCIIArt("error");
    Messenger::critical($msg, "CRITICAL");
    echo PHP_EOL;
    exit();
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
  * @param mixed $string
  * @param bool $break
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

 // ---------------------------------------------------------------------------
 // CURSOR CONTROL
 // ---------------------------------------------------------------------------

 public static function moveCursor($output, $direction, $amount)
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
 }

 /**
  * outMove
  *
  * handles outputting an ARRAY message.
  *
  * @param mixed $textArray
  * @param mixed $movements
  * @param mixed $output
  *
  * @return void
  */
 public static function outMove($textArray, $movements, $output)
 {
  if (!is_array($textArray)) {
   self::Message("Invalid output data sent. Not an array!", "FAIL");
  }

  // $cli = self::$climate;

  foreach ($textArray as $string) {
   // foreach ($movements as $key => $value) {
   //     self::moveCursor($output, $key, $value);
   // }

   // $cli->out($string);
   self::echoAndMove($output, $string, $movements);
  }

  return true;
 }

 /**
  * echoMovingData
  *
  * Used to output a STRING or ARRAY message. Really just facilitates passing
  * data to either output function, outMove or echoAndMove Formerly
  * $CMDPhreaks->outputData($ouput ...)
  *
  * @param mixed $output
  * @param mixed $strings
  * @param mixed $moveCursor
  *
  * @return void
  *
  * @SuppressWarnings(PHPMD.ElseExpression)
  */
 public static function echoMovingData(
  $output,
  $strings,
  $moveCursor = false
 ) {
  if (is_array($strings)) {
   self::outMove($strings, $moveCursor, $output);
  } else {
   self::echoAndMove($output, $strings, $moveCursor);
  }
 }

 /**
  * echoAndMove
  *
  * Used to output a STRING message.
  *
  * @param mixed $output
  * @param mixed $text
  * @param mixed $move
  *
  * @return void
  */
 public static function echoAndMove($output, $text, $move = false)
 {
  $cli = self::$climate;

  if (isset($move) && is_array($move)) {
   foreach ($move as $mvmnt => $value) {
    self::moveCursor($output, $mvmnt, $value);
   }
  }

  $cli->out($text);
 }

 // ----------------------------------------------------------------
 // TEXT MANIPULATIONS
 // ----------------------------------------------------------------

 /**
  * phreakWordwrap
  *
  * slightly smarter / more complex word wrapping function.
  *
  * @param string $string
  * @param int $width
  * @param string $break
  *
  * @return string
  *
  * @SuppressWarnings(PHPMD.ElseExpression)
  */
 public static function phreakWordwrap($string, $width = 75, $break = "\n")
 {
  // split on problem words over the line length
  $pattern = sprintf("/([^ ]{%d,})/", $width);
  $output  = "";
  $words   = preg_split(
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
    $count   = $width - (iconv_strlen(end($wrapped)) % $width);

    // fill the current line and add a break
    $output .= substr($word, 0, $count) . $break;

    // wrap any remaining characters from the problem word
    $output .= wordwrap(substr($word, $count), $width, $break);
   }
  }

  // wrap the final output w/ PHP's native wordwrap()
  return wordwrap($output, $width, $break);
 }

 // ----------------------------------------------------------------
 // DATE & TIME FUNCTIONS
 // ----------------------------------------------------------------

 public static function currentTime()
 {
  exec("timedatectl | grep 'Time zone' | cut -c 28-120", $timezone);
  $time = trim(implode(" ", $timezone));

  if (strlen($time) < 5) {
   self::Message(
    "Timezone could not be determined, using UTC",
    "WARN"
   );
   $time = "UTC";
  }

  $currently = Carbon::now($time);

  return [ "time" => $currently, "timezone" => $time ];
 }

 public static function UTCTime($mode)
 {
  // TODO: Im not sure why this has a switch element?
  switch ($mode) {
   case "now":
    $result = Carbon::now()->toDateTimeString();
    break;

   default:
    # code...
    break;
  }

  return $result;
 }

 public static function displayTime($date)
 {
  // TODO: Create this function to pretty print the time
 }

 // ----------------------------------------------------------------
 // MULTI COMMAND HELPERS + Param validate & format etc
 // ----------------------------------------------------------------

 /**
  * amountCheck
  *
  * Helper function that optionally lets user pass an amount. Used to set
  * light / dark amount, determine the number of columns to display etc.
  *
  * @param bool $isRequired
  *
  * @return void
  *
  * @SuppressWarnings(PHPMD.ElseExpression)
  */
 public static function amountCheck($amount = false, $isRequired = false)
 {
  //TUI::printDEBUG($amount);
  if (null !== $amount && $amount !== false) {
   if (!is_numeric($amount)) {
    TUI::Message("amount must be a numeric value!", "FAIL");
   } elseif ($amount <= 0) {
    TUI::Message(
     "amount must be greater than zero!",
     "FAIL"
    );
   } else {
    return $amount;
   }
  } else {
   if ($isRequired !== false) {
    TUI::Message(
     "Usage  --[KEYWORD]=[0-100] parameter when using this command!",
     "FAIL"
    );
   } else {
    return 0;
   }
  }
 }

 /**
  * amountFormat
  *
  * Only used when --data is being used to alter a color value. IE lighten a
  * color by 10%. Not used when referencing the column output etc.
  *
  * @param mixed $value
  *
  * @return void
  *
  * @SuppressWarnings(PHPMD.ElseExpression)
  */
 public static function amountFormat($value)
 {
  if ($value >= 1 && $value <= 100) {
   // Passing as a percentage
   $newValue = round($value * 0.01, 2);
  } elseif ($value > 100) {
   // This number is too high
   TUI::Message("--data amount cannot exceed 100", "FAIL");
  } elseif ($value < 1) {
   // Value already converted
   $value = round($value * 1, 2);
  } else {
   $newValue = $value;
  }

  return $newValue;
 }

 // ----------------------------------------------------------------
 // RANDOM FUNCTIONS
 // ----------------------------------------------------------------

 public static function getName($param = false)
 {
  if (!isset($param)) {
   exec("whoami", $hostname);
   $param = trim(implode(" ", $hostname));
  }

  return $param;
 }

 /**
  * echoColumns
  *
  * @param mixed $data
  * @param mixed $count
  *
  * @return void
  *
  * @SuppressWarnings(PHPMD.ElseExpression)
  */
 public static function echoColumns($data, $count = 3)
 {
  if (is_array($data)) {
   self::$climate->columns($data, $count);
  } else {
   self::Message("Data is not formatted as an array!", "FAIL");
  }

  return;
 }

 /**
  * phreakColorBar
  *
  * silly function that generates a string to use for color display
  *
  * @param string $pre
  * @param string $post
  *
  * @return string
  */
 public static function phreakColorBar($pre = "  ", $post = "")
 {
  $bars = [  ];
  // Generated via randomBarSmasher()
  // Trying to do this as unique each time results in
  // errors w/ printing certain chars. not worth the time sink.
  $bars[  ] = "████▓███▓███▓███";
  $bars[  ] = "▓███▓███▓▓█▓████";
  $bars[  ] = "▓███▓██████▓▓█▓█";
  $bars[  ] = "█████▓▓█▓███████";
  $bars[  ] = "██▓███▓███▓█████";
  $bars[  ] = "▓███▓███▓███████";
  $bars[  ] = "▓███▓███▓▓█▓████";
  $bars[  ] = "██████▓▓█▓██████";
  $bars[  ] = "▓███▓███████▓██▓";
  $bars[  ] = "▓███▓██████▓▓█▓█";
  $bars[  ] = "▓███▓█████▓▓█▓██";
  $bars[  ] = "██▓███▓███▓█████";

  shuffle($bars);
  shuffle($bars);

  $choice = rand(0, count($bars) - 1);
  $choice = $choice < 1 ? $choice++ : $choice--;

  return $pre . $bars[ $choice ] . $post;
 }

 // ----------------------------------------------------------------
 // DISPLAY IMAGES
 // ----------------------------------------------------------------

 // Output Result to Terminal
 public static function echoImg($img)
 {
  self::cliImgDisplay($img);
  $name = basename($img);
  self::Speaks("Wallpaper saved to: " . $name);
 }

 //  Displays an image in the terminal
 public static function cliImgDisplay($imagePath)
 {
  // Parse options from coFFmmand line
  $opts = array_merge(
   [
    "d" => 0, // Dithering mode : 0 = DITHER_NONE, 1 = DITHER_ERROR
    "f" => $imagePath,
    "i" => true,
    "r" => 0.15, // Resize factor 1.0 = 100%
    "w" => 50.95, // Dither treshold weight
    ],
   getopt("f:r:w:d:ib")
  );

  $pixeler = new Pixeler();
  $image   = $pixeler::image(
   $opts[ "f" ],
   $opts[ "r" ],
   isset($opts[ "i" ]),
   $opts[ "w" ],
   $opts[ "d" ]
  );

  echo "\r\n" . $image;
 }

 // ----------------------------------------------------------------
 // ASCII ART OUTPUT
 // ----------------------------------------------------------------

 // Output ascii image + Break
 /**
  * summary
  * @param  string $ascii_file description
  * @param  string $color description
  * @return void  description
  * @SuppressWarnings(PHPMD.ElseExpression)
  */
 public static function printASCIIArt(
  $ascii_file = "spaceman",
  $color = "white"
 ) {
  self::$climate->addArt("libraries/ascii");

  if ($color == "blue") {
   self::$climate->lightBlue()->boldDraw($ascii_file);
  } elseif ($color == "red") {
   self::$climate->red()->boldDraw($ascii_file);
  } elseif ($color == "green") {
   self::$climate->lightGreen()->boldDraw($ascii_file);
  } else {
   self::$climate->white()->boldDraw($ascii_file);
  }
 }

 // ----------------------------------------------------------------
 // TEST / DEBUG FUNCTIONS
 // ----------------------------------------------------------------

 // This shows how you can build a string with climate for use later!
 public static function bufferWrite($data)
 {
  foreach ($data as $row) {
   self::$climate->to("buffer")->write($row);
  }

  // Grab the formatted string
  $stringOut = self::$climate->output->get("buffer")->get();

  // Clear the string from the buffer
  self::$climate->output->get("buffer")->clean();

  return $stringOut;
 }

 /**
  * summary
  * @param  type $data description
  * @return void  description
  * @SuppressWarnings(PHPMD.ElseExpression)
  */
 public static function printDEBUG($data)
 {
  if (is_array($data)) {
   self::$climate
    ->br()
    ->bold()
    ->yellow()
    ->out(
     "    DEBUG ARRAY" .
     PHP_EOL .
     "┉┉━━━━┉┉┉┉━━━━┉┉┉┉━━━━┉┉┉┉┉┉━━━"
    )
    ->br();
   foreach ($data as $k => $d) {
    self::$climate
     ->bold()
     ->green()
     ->out("KEY: " . $k)
     ->br();
    var_dump($d);
    self::$climate->br();
   }
  } else {
   self::$climate
    ->br()
    ->bold()
    ->magenta()
    ->out(
     "   STRING DEBUG" .
     PHP_EOL .
     "▭ ▭▭▭▭ ▭▭▭ ▭▭ ▭ ▭▭▭ ▭▭▭▭ ▭▭▭ ▭"
    )
    ->br();
   var_dump($data);
   self::$climate->br();
  }

  self::$climate->br();
 }

 // ---------------------------------------------------------------------------
 // USER INPUT PROMPTS
 // ---------------------------------------------------------------------------

 /**
  * askQuestion
  *
  * shortcut helper function for using CLImate input prompt
  *
  * @param mixed $string
  * @param mixed $default
  *
  * @return string
  */
 public static function askQuestion($string = "", $default = "")
 {
  $climate = new CLImate();

  $climate->br();
  $input = $climate->input($string);
  $input->defaultTo($default);
  $result = $input->prompt();
  $climate->br();

  return $result;
 }

 public static function askYesNo($yes, $no)
 {
  $climate = new CLImate();

  $input    = $climate->confirm("  [Y] " . $yes . "  [N] " . $no);
  $response = $input->confirmed() ? true : false;
  $climate->br();

  return $response;
 }

 // ---------------------------------------------------------------------------
 // UNUSED / UNKNOWN / HERE BE DRAGONS
 // ---------------------------------------------------------------------------

 /**
  * @SuppressWarnings(PHPMD.ElseExpression)
  */
 public static function buildFiglet(
  $text,
  $font = "rebel",
  $width = 80,
  $smush = false
 ) {
  $figlet = new Figlet();

  //Check for Extension...
  $flfFont = resource_path("fonts/figlet/" . $font . ".flf");
  $tlfFont = resource_path("fonts/figlet/" . $font . ".tlf");

  if (file_exists($flfFont)) {
   $figFont = $flfFont;
   $figlet->setFont($figFont);
   $figlet->setOutputWidth($width);

   if ($smush !== false) {
    $figlet->setSmushMode("SM_SMUSH");
   }

   $figlet->setJustification("JUSTIFICATION_CENTER");

   self::Speaks([ " ", $figlet->render($text), " " ]);
  } elseif (file_exists($tlfFont)) {
   exec("toilet --gay -f '" . $font . "' " . $text, $execOutput);
   foreach ($execOutput as $line) {
    TUI::Speaks($line);
   }
  } else {
   self::Message("ERROR! Font File not found!", "FAIL", true);
   exit();
  }
 }
}

TUI::constructStatic();
