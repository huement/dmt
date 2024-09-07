<?php

/**
 * ColorController | anything to do with colors
 * Date: August 16 2021
 */

namespace App\Controllers;

use ANSI;
use AnsiToRgb;
// use App\Controllers\HarmonyController;

use App\Console\Boxer;
use App\Console\TUI;
use App\Controllers\FileController;
// TODO use the new color name library JSON file
// TODO use this color name api: https://colornames.org/search/json/?hex=FF0000

use Colors\RandomColor;
use Illuminate\Support\Facades\Http;
use LukaPeharda\TailwindCssColorPaletteGenerator\Color as TailColor;
use LukaPeharda\TailwindCssColorPaletteGenerator\PaletteGenerator as TailPalGen;
use MikeAlmond\Color\Color as MAColor;
use MikeAlmond\Color\CssGenerator as MACSSGen;
use MikeAlmond\Color\PaletteGenerator as MAPalGen;
use ourcodeworld\NameThatColor\ColorInterpreter as NameThatColor;
use Phim\Color;

require_once 'libraries/Wsc.php';
require_once 'libraries/roundcolor/PKRoundColor.php';
require_once 'libraries/AnsiToRgb.php';
require_once 'libraries/ANSI.php';

use Wsc;

class ColorController
{

 public static $FoundColors  = [  ];
 public static $colorNames   = "libraries/colornames.json";
 public static $hexValidator = '/(?:[0-9a-f]{3}){1,2}/i';

 /**
  * TODO Implement this gradient command!!
  *  @URL https://github.com/mazznoer/gradient-rs
  */
 public static function cliGradient()
 {
  // gradient -p rainbow -H 5 -W 50
 }

 public static function rainbowFiglet()
 {
  // figlet -f rustofat Test | lolcatjs
 }

 public static function getBackgroundColor()
 {
  // Foreach hex code, get if light or dark.
  // Whichever is more common, light or dark,
  // get matching light or dark color.
 }

 // ---------------------------------------------------------------------------
 // COLOR DETAILS
 // ---------------------------------------------------------------------------

 /**
  * getColorInfo
  *
  * Creates an array with details for a given hex color. Only assembles data about that color,
  * does not get info about associated colors, nor does it create palettes and combos featuring given hex
  *
  * Very important function, should be used anytime a color is returned.
  *
  * @param  string $baseHex
  * @return array $colorInfo
  */
 public static function getColorInfo($baseHex = "")
 {

  $baseColor = MAColor::fromHex($baseHex);
  $baseHex   = trim($baseHex, "#");

  $name = self::getColorName($baseHex);

  $lightOrDark = ($baseColor->isDark() ? "dark" : "light");

  $rgb  = $baseColor->getRgb();
  $ansi = AnsiToRgb::toAnsi($rgb[ "r" ], $rgb[ "g" ], $rgb[ "b" ]);

  $hslDecimal = $baseColor->getHsl();
  $hslPercent = self::formatHSL($hslDecimal);

  $colorInfo = [
   "hex"         => "#" . $baseHex,
   "name"        => $name[ "name" ],
   "name_source" => $name[ "source" ],
   "hue"         => $name[ "hue" ],
   "side"        => $lightOrDark,
   "rgb"         => $rgb,
   "hsl_raw"     => $hslDecimal,
   "hsl"         => $hslPercent,
   "ansi"        => $ansi,
   ];

  return $colorInfo;
 }

 /**
  * lightOrDark
  *
  * returns if color is light or dark and also includes a useable text color.
  * great for figuring out background colors, or what kind of theme to use.
  *
  * @param  mixed $hex
  * @return void
  */
 public static function lightOrDark($hex)
 {
  if (\is_array($hex)) {
   $hex = $hex[ 0 ];
  }
  $baseColor   = MAColor::fromHex($hex);
  $lightOrDark = ($baseColor->isDark() ? "dark" : "light");
  $textColor   = $baseColor->getMatchingTextColor()->getHex();

  return [ "LightOrDark" => $lightOrDark, "text" => $textColor ];
 }

 /**
  * getColorName
  *
  * Quick helper function instead of calling full colorInfo to retreive just the name of a color
  *
  * @param  string $hex
  * @return array
  */
 public static function getColorName($hex = "")
 {
  $name   = false;
  $source = false;

  if (!self::checkHexCode($hex)) {
   TUI::Message('INVVALID HEX CODE: ' . $hex, "FAIL");
   exit;
  }

  $webName = json_decode(Http::get('https://colornames.org/search/json/?hex=' . $hex), true);
  if (isset($webName) && is_array($webName) && $webName[ "name" ]) {
   $name   = $webName[ "name" ];
   $source = 'colornames.org';
  }

  if ($name === false) {
   $file = base_path(self::$colorNames);

   if ($file !== false && is_file($file)) {
    $JSON = json_decode(file_get_contents($file), true);

    foreach ($JSON as $j) {
     if ($j[ "hex" ] === \strtolower("#" . $hex)) {
      $name   = $j[ "name" ];
      $source = 'local-json';
     }
    }
   }
  }

  if ($name === false) {
   $baseColor = MAColor::fromHex($hex);
   $instance  = new NameThatColor();
   $name      = $instance->name($baseColor);
   $source    = 'NameThatColor';
  }

  if (is_array($name)) {
   $name = $name[ "name" ];
  }

  $whatHue = Color::getColorHueRange(Color::get("#" . $hex));

  return [ "name" => $name, "hue" => $whatHue, "source" => $source ];
 }

 public static function getTextColor($hex = "")
 {
  $baseColor = MAColor::fromHex($hex);
  $textColor = $baseColor->getMatchingTextColor()->getHex();

  $results = self::getColorInfo($textColor);
  return $results;
 }

 /**
  * getColorShades
  *
  * @param [type] $baseHex
  * @param integer $steps
  * @return void
  */
 public static function getColorShades($baseHex, $steps = 5)
 {
  $results = [  ];

  $baseColor = MAColor::fromHex($baseHex);
  $generator = new MAPalGen($baseColor);
  $shades    = $generator->monochromatic($steps);

  foreach ($shades as $shade) {
   $results[  ] = self::getColorInfo($shade->getHex());
  }

  return $results;
 }

 /**
  * getColorPalettes
  *
  * Generates 3 different color palettes for a given hex code.
  * Also builds out information for all the colors that make up each palette.
  *
  * Used in the Color Details report when requested via --comp flag
  *
  * @param  string $baseHex
  * @return array $cData
  */
 public static function getColorPalettes($baseHex)
 {

  $cData     = self::getColorInfo($baseHex);
  $baseColor = MAColor::fromHex($baseHex);

  $palettes  = [  ];
  $generator = new MAPalGen($baseColor);

  $palettes[ "triad" ]    = $generator->triad(30);
  $palettes[ "tetrad" ]   = $generator->tetrad(40);
  $palettes[ "adjacent" ] = $generator->adjacent(20);

  $cData[ "palettes" ] = [  ];
  foreach ($palettes as $key => $colors) {
   // Loop through each color in given palette
   $cData[ "palettes" ][ $key ] = [  ];

   foreach ($colors as $color) {
    // Assemble color object for each color
    $cData[ "palettes" ][ $key ][  ] = self::getColorInfo($color->getHex());
   }
  }

  return $cData;
 }

 /**
  * getColorComplement
  *
  * Walk around the color wheel from a starting Hex code to desired degrees of rotation
  * @param  string $hex
  * @param  int $amount
  * @return array
  */
 public static function getColorComplement($targetHEX, $amount = 180)
 {
  $complement = Color::complement(Color::get("#" . $targetHEX), $amount)->toRgb();
  $hex        = Color::toHexString($complement);
  $ansi       = AnsiToRgb::toAnsi($complement->getRed(), $complement->getGreen(), $complement->getBlue());
  $name       = self::getColorName($hex);

  $results = [
   "name" => $name[ "name" ],
   "hex"  => $hex,
   "ansi" => $ansi,
   "rgb"  => [ "r" => $complement->getRed(), "g" => $complement->getGreen(), "b" => $complement->getBlue() ],
   ];

  return $results;
 }

 /**
  * getMiniColorInfo
  *
  * Used to quickly return just the basic info about a color. Mostly used to generate ANSI code on the fly
  *
  * @param  string $hex
  * @return array
  */
 public static function getMiniColorInfo($hexString)
 {
  if (is_array($hexString)) {
   if (env("DEBUG")) {
    TUI::Message("SENT ARRAY TO getMiniColorInfo!! THIS IS INCORRECT!", "WARN");
   }
   $hex = $hexString[ 0 ];
  } else {
   $hex = $hexString;
  }

  $base = Color::get("#" . $hex)->toRgb();
  $hex  = Color::toHexString($base);
  $ansi = AnsiToRgb::toAnsi($base->getRed(), $base->getGreen(), $base->getBlue());
  $name = self::getColorName($hex);

  $results = [
   "name" => $name[ "name" ],
   "hex"  => $hex,
   "ansi" => $ansi,
   "rgb"  => [ "r" => $base->getRed(), "g" => $base->getGreen(), "b" => $base->getBlue() ],
   ];

  return $results;
 }

 /**
  * getLighter
  *
  * @param  mixed $hex
  * @param  mixed $amount
  * @return array
  */
 public static function getLighter($hex, $amount = 0.10)
 {
  $color    = Color::get("#" . $hex);
  $newColor = Color::lighten($color, $amount);
  $hex      = Color::toHexString($newColor);

  return $hex;
 }

 public static function getMatchingLighter($hex)
 {
  $colorOG = Color::get("#" . $hex);
  $color   = Color::lighten($colorOG, 0.10);
  $color   = Color::desaturate($color, 0.05);

  // Confirm color is different enough
  if (Color::equals($color, $colorOG, 2)) {
   $color = Color::lighten($colorOG, 0.15);
   $color = Color::desaturate($color, 0.05);
  }

  $color = Color::toHexString($color);

  return $color;
 }

 /**
  * getDarker
  *
  * @param  mixed $hex
  * @param  mixed $amount
  * @return array
  */
 public static function getDarker($hex, $amount = 0.10)
 {
  $color    = Color::get("#" . $hex);
  $newColor = Color::darken($color, $amount);
  $hex      = Color::toHexString($newColor);

  return $hex;
 }

 public static function getMatchingDarker($hex)
 {
  $color = Color::get("#" . $hex);
  // $color->toHsl()->setLightness(.6);
  $color = Color::darken($color, .12);
  $color = Color::desaturate($color, 0.08);
  $color = Color::toHexString($color);
  return $color;
 }

 /**
  * getSaturate
  *
  * @param  mixed $hex
  * @param  mixed $amount
  * @return array
  */
 public static function getSaturate($hex, $amount = 0.10)
 {
  $color    = Color::get("#" . $hex);
  $newColor = Color::saturate($color, $amount);

  $hex = Color::toHexString($newColor);

  return $hex;
 }

 /**
  * getDesaturate
  *
  * @param  mixed $hex
  * @param  mixed $amount
  * @return array
  */
 public static function getDesaturate($hex, $amount = 0.10)
 {
  $color    = Color::get("#" . $hex);
  $newColor = Color::desaturate($color, $amount);

  $hex = Color::toHexString($newColor);

  return $hex;
 }

 // ---------------------------------------------------------------------------
 // SPECIAL AMOUNT FUNCTIONS
 // ---------------------------------------------------------------------------

 /**
  * getFade
  *
  * AMOUNT CAN BE -1 to 1
  *
  * @param  mixed $hex
  * @param  mixed $amount
  * @return array
  */
 public static function getFade($hex, $amount = 0.10)
 {
  $color    = Color::get("#" . $hex);
  $newColor = Color::fade($color, $amount);

  $hex = Color::toHexString($newColor);

  return $hex;
 }

 /**
  * getRotate
  *
  * AMOUNT CAN BE -360 to 360
  *
  * @param  mixed $hex
  * @param  mixed $amount
  * @return array
  */
 public static function getRotate($hex, $amount = 120)
 {
  $color    = Color::get("#" . $hex);
  $newColor = Color::complement($color, $amount);

  $hex = Color::toHexString($newColor);

  return $hex;
 }

 /**
  * getRando
  *
  * AMOUNT IS EITHER [hue] OR [hue],[luminosity]
  *
  * @param  mixed $hue
  * @param  mixed $luminosity
  * @return array
  */
 public static function getRando($hue = false, $luminosity = false)
 {
  $hues = [ "red", "orange", "yellow", "green", "blue", "purple", "pink", "monochrome" ];
  shuffle($hues);
  $number  = rand(0, (count($hues) - 1));
  $huement = (isset($hue) ? $hue : $hues[ $number ]);

  $lumins = [ "random", "bright", "light", "dark" ];
  shuffle($lumins);
  $numberTwo = rand(0, (count($lumins) - 1));
  $lumin     = (isset($luminosity) ? $luminosity : $lumins[ $numberTwo ]);

  $color = RandomColor::one([
   'hue'        => $huement,
   'luminosity' => $lumin,
   ]);

  return self::getMiniColorInfo($color);
 }

 /**
  * getWebSafe
  *
  * Returns either Hexcode arrays or RGB arrays of all websafe colors
  *
  * @param  bool $rgb set results as rgb if true. if false get hexcode results
  * @return array $webSafeColors array of results
  */
 public static function getWebSafe($rgb = false)
 {
  $WebSafeColors = Wsc::getColors($rgb);

  return $WebSafeColors;
 }

 // -------------------------------------------------------------------------------------
 // COLOR STACK FUNCTIONS
 // -------------------------------------------------------------------------------------

 /**
  * buildStack
  *
  * @param  string $baseHex color used to lighten & darken
  * @return array $final Color Stack as key=>value pairs
  */
 public static function buildStack($baseHex)
 {
  $baseColor = MAColor::fromHex($baseHex);

  $color50  = $baseColor->lighten(45)->getHex();
  $color100 = $baseColor->lighten(40)->getHex();
  $color200 = $baseColor->lighten(30)->getHex();
  $color300 = $baseColor->lighten(20)->getHex();
  $color400 = $baseColor->lighten(10)->getHex();
  $color600 = $baseColor->darken(10)->getHex();
  $color700 = $baseColor->darken(20)->getHex();
  $color800 = $baseColor->darken(30)->getHex();
  $color900 = $baseColor->darken(40)->getHex();

  $final = [
   "50"  => "#" . $color50,
   "100" => "#" . $color100,
   "200" => "#" . $color200,
   "300" => "#" . $color300,
   "400" => "#" . $color400,
   "500" => "#" . $baseColor->getHex(),
   "600" => "#" . $color600,
   "700" => "#" . $color700,
   "800" => "#" . $color800,
   "900" => "#" . $color900,
   ];

  return $final;
 }

 /**
  * colorStack
  * Helper function that actually does the generation when getColorStack is called
  *
  * @param mixed $Color
  * @return array
  */
 public static function colorStack($Color)
 {
  $instance = new NameThatColor();

  $baseColor = MAColor::fromHex($Color);
  $baseName  = $instance->name(MACSSGen::hex($baseColor));
  $cleanName = FileController::camelCase(str_replace(' ', '', $baseName[ "name" ]));

  $stack  = self::buildStack($baseColor);
  $result = [ "name" => $cleanName, "stack" => $stack ];

  return $result;
 }

 /**
  * getColorStack
  *
  * @param  array $ColorsToStack hex value(s) to generate stacks for
  * @return array $StackedColors contains stack of colors
  */
 public static function getColorStack($ColorsToStack)
 {
  $StackedColors = [  ];

  if (is_array($ColorsToStack)) {
   foreach ($ColorsToStack as $MainColor) {
    $result                             = self::colorStack($MainColor);
    $StackedColors[ $result[ "name" ] ] = $result[ "stack" ];
   }
  } else {
   $result                             = self::colorStack($ColorsToStack);
   $StackedColors[ $result[ "name" ] ] = $result[ "stack" ];
  }

  return $StackedColors;
 }

 public static function getTailwindStack($baseHex, $cssString = false, $framework = "tailwind", $version = 2)
 {
  $hashless = ltrim($baseHex, "#");

  $baseColor        = TailColor::fromHex("#" . $hashless);
  $paletteGenerator = new TailPalGen;

  // Set Params : Default Tailwind 2.*
  switch ($framework) {
   case 'tailwind':
    if ($version === 2) {
     $paletteGenerator->setBaseValue(500);
     $paletteGenerator->setThresholdLightest(90);
     $paletteGenerator->setThresholdDarkest(10);
     $paletteGenerator->setColorSteps([ 50, 100, 200, 300, 400, 500, 600, 700, 800, 900 ]);
    }
    break;

   case 'mojo':
    if ($version === 1) {
     $paletteGenerator->setBaseValue(500);
     $paletteGenerator->setThresholdLightest(70);
     $paletteGenerator->setThresholdDarkest(30);
     $paletteGenerator->setColorSteps([ 300, 350, 400, 450, 500, 550, 600, 650, 700 ]);
    }
    break;
  }

  $paletteGenerator->setBaseColor($baseColor);
  $palette = $paletteGenerator->getPalette();

  $results = [  ];

  $css = ($cssString !== false) ? $cssString : "--color-primary-";

  foreach ($palette as $key => $color) {
   $hex = $color->getHex();

   $info = self::getColorInfo($hex);

   $results[  ] = [
    "css"  => $css . $key,
    "key"  => $key,
    "hex"  => $hex,
    "name" => $info[ "name" ],
    "rgb"  => $info[ "rgb" ],
    "hsl"  => $info[ "hsl_raw" ],
    "ansi" => $info[ "ansi" ],
    ];
  }

  return $results;
 }

 // -------------------------------------------------------------------------------------
 // HEX CODE FUNCTIONS | Finding / Validating, Formatting
 // -------------------------------------------------------------------------------------

 /**
  * findAllColorCodes
  *
  * Use this function to find any and all HEX color codes in a string
  * Also will remove the '#' symbol from the resulting matches if present.
  *
  * @param  mixed $inputData      array or string we are searching through
  * @param  bool  $unique         remove duplicate codes (default: false)
  * @return array $foundHexCodes  resulting array of hex codes
  */
 public static function findAllColorCodes($inputData, $unique = false, $limit = false)
 {
  // Search through inputData for hex codes
  // recursive search if inputData is array
  if (is_array($inputData)) {
   $foundHexCodes = [  ];

   self::queryRecursive($inputData);

   foreach (self::$FoundColors as $colorArr) {
    foreach ($colorArr as $key => $val) {
     $foundHexCodes[  ] = $val;
    }
   }
  } else {
   $foundHexCodes = self::allValidColorCodes($inputData);
  }

  if ($unique !== false) {
   $foundHexCodes = array_unique($foundHexCodes);
  }

  if ($limit !== false) {
   $foundHexCodes = array_slice($foundHexCodes, 0, $limit);
  }

  return $foundHexCodes;
 }

 public static function checkHexCode($color)
 {
  $hashless = ltrim($color, "#");

  //'/[a-f0-9]{6}$/i'

  if (preg_match(self::$hexValidator, $hashless)) {
   return true;
  } else {
   return false;
  }
 }

 /**
  * queryRecursive
  *
  * @param  mixed $arr
  * @return mixed
  */
 public static function queryRecursive($arr)
 {

  foreach ($arr as $key => $val) {
   if (is_array($val)) {
    self::queryRecursive($val);
   } else {
    self::$FoundColors[  ] = self::allValidColorCodes($val);
   }
  }

  return;
 }

 /**
  * foundColorCleanup
  *
  * Mashing the results of adding colors via hexcode user input, and finding colors in files
  * and however else can result in an array of arrays of colors. this function cleans up any
  * nested color arrays so the result is a single level array of hexcodes. nice and tidy.
  *
  * @param  array $messyArray
  * @return array
  */
 public static function foundColorCleanup($messyArray)
 {
  $cleanColors = [  ];

  foreach ($messyArray as $fHC) {
   if (is_array($fHC)) {
    foreach ($fHC as $cc) {
     if (is_array($cc)) {
      foreach ($cc as $c) {
       if (!is_array($c) && self::checkHexCode($c) !== false) {
        $cleanColors[  ] = $c;
       }
      }
     } else {
      if (self::checkHexCode($cc) !== false) {
       $cleanColors[  ] = $cc;
      }
     }
    }
   } else {
    if (self::checkHexCode($fHC) !== false) {
     $cleanColors[  ] = $fHC;
    }
   }
  }

  return $cleanColors;
 }

 /**
  * allValidColorCodes
  *
  * Searches through a string for AABBCC formatted hex codes and returns an array containing the results.
  *
  * @param  mixed $string
  * @return array
  */
 public static function allValidColorCodes($string)
 {
  $matches = [  ];
  $result  = [  ];
  $count   = 0;

  $found = preg_match_all(self::$hexValidator, $string, $matches, PREG_PATTERN_ORDER);

  if ($found) {
   foreach ($matches[ 0 ] as $m) {
    $clean = (strpos($m, "#") === 0 ? ltrim($m, "#") : $m);
    // Key $m for unique result:
    $count++;
    $result[ $count ] = $clean;
   }
  }

  return $result;
 }

 /**
  * stripHashHexColors
  *
  * cleanup hexcode(s) by removing leading '#' symbol, if present
  *
  * @param  mixed $colors string or array of hex codes
  * @return mixed $results will be string or array depending on what was passed as a param
  */
 public static function stripHashHexColors($colors)
 {
  if (is_array($colors)) {
   $results = [  ];
   foreach ($colors as $color) {
    $results[  ] = (strpos($color, "#") === 0 ? ltrim($color, "#") : $color);
   }
  } elseif (strlen($colors) > 2) {
   $results = (strpos($colors, "#") === 0 ? ltrim($colors, "#") : $colors);
  } else {
   $results = false;
  }

  return $results;
 }

 /**
  * nearestWebsafe
  *
  * find nearest websafe hexcode, one up one down from given hexcode
  *
  * @param  mixed $base
  * @return void
  */
 public static function nearestWebsafe($base)
 {
  $results = [  ];

  $allCodes = self::getWebSafe();

  $key = array_search(strtolower($base), $allCodes);

  if ($key > 1) {
   $next = $key + 1;
   $prev = $key - 1;

   $nextColor = $allCodes[ $next ];
   $prevColor = $allCodes[ $prev ];

   $nextInfo = self::getMiniColorInfo($nextColor);
   $prevInfo = self::getMiniColorInfo($prevColor);

   $results[  ] = $nextInfo;
   $results[  ] = $prevInfo;
  }

  return $results;
 }

 // ---------------------------------------------------------------------------
 // QUICK CONVERSIONS
 // ---------------------------------------------------------------------------

 public static function hex2rgb($hexVal)
 {
  $hexVal = str_replace("#", "", $hexVal);

  if (strlen($hexVal) == 3) {
   //Like #000
   $red   = hexdec(substr($hexVal, 0, 1) . substr($hexVal, 0, 1));
   $green = hexdec(substr($hexVal, 1, 1) . substr($hexVal, 1, 1));
   $blue  = hexdec(substr($hexVal, 2, 1) . substr($hexVal, 2, 1));
  } else {
   $red   = hexdec(substr($hexVal, 0, 2));
   $green = hexdec(substr($hexVal, 2, 2));
   $blue  = hexdec(substr($hexVal, 4, 2));
  }

  $rgb_min    = [ $red, $green, $blue ];
  $rgb_string = implode(",", $rgb_min);

  $rgb = [ "r" => $red, "g" => $green, "b" => $blue, "rgb" => $rgb_string ];

  return $rgb;
 }

 public static function hex2ansi($hexVal)
 {
  $base = Color::get("#" . $hexVal)->toRgb();
  // $hex    = Color::toHexString($base);
  $ansi = AnsiToRgb::toAnsi($base->getRed(), $base->getGreen(), $base->getBlue());

  //Get RGB
  // $RGB = self::hex2rgb($hexVal);
  // $ansi = AnsiToRgb::toAnsi($RGB["r"], $RGB["g"], $RGB["b"]);

  return $ansi;
 }

 public static function rgb2hex($r, $g, $b)
 {
  $baseColor = MAColor::fromRgb($r, $g, $b);
  $baseHex   = $baseColor->getHex();

  return $baseHex;
 }

 public static function rgb2hsl($r, $g, $b)
 {
  $baseColor = MAColor::fromRgb($r, $g, $b);
  $baseHSL   = $baseColor->getHsl();

  return $baseHSL;
 }

 public static function rgb2hsv($r, $g, $b)
 {
  $baseColor = MAColor::fromRgb($r, $g, $b);
  $baseHex   = $baseColor->getHex();

  $phimColor = Color::get("#" . $baseHex);
  $baseHSV   = $phimColor->toHsv();

  $H = $baseHSV->getHue();
  $S = $baseHSV->getSaturation();
  $V = $baseHSV->getValue();

  return [ "h" => $H, "s" => $S, "v" => $V ];
 }

 /**
  * hex2websafe
  *
  * for a given hexcode returns the nearest websafe equivalent color
  *
  * @param  string $hex color code
  * @return string $wshex websafe hexidecimal color code
  */
 public static function hex2websafe($hex)
 {
  $colors = self::getWebSafe(true);

  $RGB        = self::hex2rgb($hex);
  $inputColor = [ "r" => $RGB[ "r" ], "g" => $RGB[ "g" ], "b" => $RGB[ "b" ] ];
  $wsColor    = $colors[ 0 ];
  $deviation  = PHP_INT_MAX;

  foreach ($colors as $color) {

   $curDev = self::compareColors($inputColor, $color);

   if ($curDev < $deviation) {
    $deviation = $curDev;
    $wsColor   = $color;
   }
  }

  $wsHex = self::rgb2hex($wsColor[ "r" ], $wsColor[ "g" ], $wsColor[ "b" ]);

  return $wsHex;
 }

 /**
  * compareColors
  *
  * used to compare two RGB arrays when finding the nearest websafe color code
  *
  * @param  array $colorA RGB array
  * @param  array $colorB RGB array
  * @return int absolute value of a number
  */
 public static function compareColors($colorA, $colorB)
 {
  return abs($colorA[ "r" ] - $colorB[ "r" ]) + abs($colorA[ "g" ] - $colorB[ "g" ]) + abs($colorA[ "b" ] - $colorB[ "b" ]);
 }

 // -------------------------------------------------------------------------------------
 // COLOR TUI FUNCTIONS
 // -------------------------------------------------------------------------------------

 public static function formatHSL($HSLR)
 {
  $H = round($HSLR[ "h" ] * 100) . "%";
  $S = round($HSLR[ "s" ] * 100) . "%";
  $L = round($HSLR[ "l" ] * 100) . "%";

  $hsl = [ "h" => $H, "s" => $S, "l" => $L ];

  return $hsl;
 }

 public static function propFmt($key, $value, $iden = "⦿")
 {
  if (strlen($key) > 1) {
   $string = "  " . $iden . " " . $key . " | " . $value;
  } else {
   $string = " | " . $value;
  }
  return $string;
 }

 public static function relColorFmt($key, $value, $iden = "●", $details = "")
 {
  $string = "▒█████";

  $colorBlock = ANSI::color256($value) . $string . ANSI::reset();
  //$colorBlock = $string;

  $string = "  " . $iden . " " . $key . "  " . $colorBlock;

  if (strlen($details) > 2) {
   $string = $string . "  " . $details;
  }

  return $string;
 }

 public static function padValues($RGB, $HSL)
 {
  $RGBPad = "";
  $RGBPad .= (strlen($RGB[ "r" ]) === 1 ? "  " : "");
  $RGBPad .= (strlen($RGB[ "r" ]) === 2 ? " " : "");
  $RGBPad .= (strlen($RGB[ "g" ]) === 1 ? "  " : "");
  $RGBPad .= (strlen($RGB[ "g" ]) === 2 ? " " : "");
  $RGBPad .= (strlen($RGB[ "b" ]) === 1 ? "  " : "");
  $RGBPad .= (strlen($RGB[ "b" ]) === 2 ? " " : "");

  $HSLPad = "";
  $HSLPad .= (strlen($HSL[ "h" ]) === 2 ? "  " : "");
  $HSLPad .= (strlen($HSL[ "h" ]) === 3 ? " " : "");
  $HSLPad .= (strlen($HSL[ "s" ]) === 2 ? "  " : "");
  $HSLPad .= (strlen($HSL[ "s" ]) === 3 ? " " : "");
  $HSLPad .= (strlen($HSL[ "l" ]) === 2 ? "  " : "");
  $HSLPad .= (strlen($HSL[ "l" ]) === 3 ? " " : "");

  return [ "rgb" => $RGBPad, "hsl" => $HSLPad ];
 }

 /**
  * colorInfoPrint
  *
  * Prints out a detailed bunch of info regarding a given color hexcode
  * Optionally set the 'mod' parameter to indicate the color printed has been
  * modified from what was originally inputed (ie result has been darked 13%)
  *
  * @param mixed $hex
  * @param bool $mod
  * @return array
  */
 public static function prettyColorReport($hex, $mod = false, $alt = false)
 {
  $colorObj = self::getColorInfo($hex);
  $endArray = [  ];

  $ansiColor = $colorObj[ "ansi" ];
  $colorName = $colorObj[ "name" ];
  $RGB       = $colorObj[ "rgb" ];
  $HSL       = $colorObj[ "hsl" ];

  $cBox     = [  ];
  $cBox[  ] = '░░░░░░░░░░░░░░░░';
  $cBox[  ] = '▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒';
  $cBox[  ] = '████████████████';
  $cBox[  ] = '████████████████';
  $cBox[  ] = '████████████████';
  $cBox[  ] = '████████████████';
  $cBox[  ] = '████████████████';
  $cBox[  ] = '████████████████';
  $cBox[  ] = '████████████████';
  $cBox[  ] = '▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒';
  $cBox[  ] = '░░░░░░░░░░░░░░░░';

  $nameString = (isset($alt[ "websafe" ])) ? "  WEBSAFE COLOR" : "   Name | " . $colorName;

  $cInfo = [  ];
  if (!$mod) {
   $cInfo[  ] = $nameString;
  } else {
   $orig = self::getMiniColorInfo($mod[ "color" ]);
   if ($mod[ "action" ] === "rotate" || $mod[ "action" ] === "inverse") {
    $modAmount = " ❪ " . $mod[ "amount" ] . " deg ❫ ";
   } elseif ($mod[ "action" ] === "text") {
    $modAmount       = "";
    $mod[ "action" ] = "Text Color";
   } else {
    $modAmount = " ❪ " . ($mod[ "amount" ] * 100) . "% ❫ ";
   }

   $cInfo[  ] = "  " . "❱ " . strtoupper($orig[ "hex" ]) . " • " . strtoupper($mod[ "action" ]) . $modAmount;
  }

  if (!$mod) {
   $cInfo[  ] = "";
  } else {
   $cInfo[  ] = "";
   $cInfo[  ] = "  RESULTS";
   $cInfo[  ] = "";
  }

  // THE HEXCODE SPECIFIC DETAILS
  $cInfo[  ] = self::propFmt("HEX", $colorObj[ "hex" ], "❶");
  $cInfo[  ] = self::propFmt("RGB", "rgb(" . $RGB[ "r" ] . ", " . $RGB[ "g" ] . ", " . $RGB[ "b" ] . ")", "❷");
  $cInfo[  ] = self::propFmt("HSL", "hsl(" . $HSL[ "h" ] . ", " . $HSL[ "s" ] . ", " . $HSL[ "l" ] . ")", "❸");
  $cInfo[  ] = self::propFmt("HUE", $colorObj[ "hue" ], "❹");

  if (!$mod) {

   $cInfo[  ] = "";

   // Check for Alternate Output flag
   if ($alt !== false && isset($alt[ "websafe" ])) {
    $addColors = self::threeColors("websafe", $hex);
   } elseif ($alt !== false && isset($alt[ "comp" ])) {
    $addColors = self::threeColors("comp", $hex);
   } else {
    $addColors = self::threeColors("basic", $hex);
   }

   $cInfo[  ] = $addColors[ "TITLE" ];
   $cInfo[  ] = $addColors[ "A" ];
   $cInfo[  ] = $addColors[ "B" ];
   $cInfo[  ] = $addColors[ "C" ];
  } else {
   $cInfo[  ] = "";
   $cInfo[  ] = self::relColorFmt("ORG", $orig[ "ansi" ], "◉", $orig[ "hex" ] . "  " . $orig[ "name" ]);
  }

  $cInfo[  ] = "";

  $i = 0;

  $endArray[  ] = "";

  // Merge all the $cInfo[]'s into $endArray[] in a rather hacky fashion...
  foreach ($cBox as $box) {
   $endArray[  ] = ANSI::color256($ansiColor) . $box . ANSI::reset() . $cInfo[ $i ];
   $i++;
  }

  $endArray[  ] = "";

  return $endArray;
 }

 public static function threeColors($type = "basic", $hex)
 {
  $results = [  ];

  switch ($type) {
   case 'websafe':
    $wsc = self::nearestWebsafe($hex);

    $results[ "A" ] = self::relColorFmt(" UP ", $wsc[ 0 ][ "ansi" ], "∆", $wsc[ 0 ][ "hex" ] . "  " . $wsc[ 0 ][ "name" ]);
    $results[ "B" ] = self::relColorFmt("DOWN", $wsc[ 1 ][ "ansi" ], "∇", $wsc[ 1 ][ "hex" ] . "  " . $wsc[ 1 ][ "name" ]);
    $results[ "C" ] = "";

    $results[ "TITLE" ] = "  NEAREST WEBSAFE";

    break;
   case 'basic':
    $minThirty = self::getColorComplement($hex, "-30");
    $comp90    = self::getColorComplement($hex, 30);
    $darker    = self::getDarker($hex, 0.15);
    $darkData  = self::getMiniColorInfo($darker);

    $results[ "A" ] = self::relColorFmt("-30°", $minThirty[ "ansi" ], "◐", $minThirty[ "hex" ] . "  " . $minThirty[ "name" ]);
    $results[ "B" ] = self::relColorFmt("+30°", $comp90[ "ansi" ], "▲", $comp90[ "hex" ] . "  " . $comp90[ "name" ]);
    $results[ "C" ] = self::relColorFmt("DARK", $darkData[ "ansi" ], "✚", $darkData[ "hex" ] . "  " . $darkData[ "name" ]);

    $results[ "TITLE" ] = "  SIMILAR";
    break;
   case 'comp':
    $paletteData = self::getColorPalettes($hex);
    $compData    = $paletteData[ "palettes" ];

    $tri = $compData[ "triad" ][ 1 ];
    $tet = $compData[ "tetrad" ][ 3 ];
    $adj = $compData[ "adjacent" ][ 2 ];

    $results[ "A" ] = self::relColorFmt(" TRI", $tri[ "ansi" ], "♣", $tri[ "hex" ] . "  " . $tri[ "name" ]);
    $results[ "B" ] = self::relColorFmt(" TET", $tet[ "ansi" ], "♦", $tet[ "hex" ] . "  " . $tet[ "name" ]);
    $results[ "C" ] = self::relColorFmt(" ADJ", $adj[ "ansi" ], "⚉", $adj[ "hex" ] . "  " . $adj[ "name" ]);

    $results[ "TITLE" ] = "  COMPLEMENTS";
    break;
  }

  return $results;
 }

 public static function miniColorReport($hex, $padding = 4)
 {
  $colorObj = self::getColorInfo($hex);

  $endArray = [  ];
  $pad      = str_repeat(" ", $padding);

  $ansiColor = $colorObj[ "ansi" ];
  $colorName = $colorObj[ "name" ];
  $RGB       = $colorObj[ "rgb" ];
  $HSL       = $colorObj[ "hsl" ];

  $cBox     = [  ];
  $cBox[  ] = '████████████████';
  $cBox[  ] = '████████████████';
  $cBox[  ] = '████████████████';
  $cBox[  ] = '██▒████▒▒████▒██';
  $cBox[  ] = '░▒▒██░██▒▒▒▒██▒▒';
  $cBox[  ] = ' ░░▒▒░ ▒░░░ ▒▒▒░';

  $cInfo     = [  ];
  $cInfo[  ] = "  " . ANSI::bold() . $colorName . ANSI::reset();
  //$cInfo[] = "  " . $colorName;
  $cInfo[  ] = "";
  $cInfo[  ] = self::propFmt("HEX", $colorObj[ "hex" ], "❶");
  $cInfo[  ] = self::propFmt("RGB", "rgb(" . $RGB[ "r" ] . ", " . $RGB[ "g" ] . ", " . $RGB[ "b" ] . ")", "❷");
  $cInfo[  ] = self::propFmt("HSL", "hsl(" . $HSL[ "h" ] . ", " . $HSL[ "s" ] . ", " . $HSL[ "l" ] . ")", "❸");
  $cInfo[  ] = self::propFmt("HUE", $colorObj[ "hue" ], "❹");

  $i            = 0;
  $endArray[  ] = "";

  foreach ($cBox as $box) {
   $endArray[  ] = $pad . ANSI::color256($ansiColor) . $box . ANSI::reset() . $cInfo[ $i ];
   $i++;
  }

  $endArray[  ] = "";

  return $endArray;
 }

 /**
  * colorShadesPrint
  *
  * @param  mixed $hex
  * @param  mixed $amount
  * @param  mixed $width
  * @return array
  */
 public static function colorShadesPrint($hex, $amount = false, $width = 64)
 {
  $endArray = [  ];

  $total = (isset($amount) && is_numeric($amount) && $amount > 0 ? $amount : 5);

  $shades = self::getColorShades($hex, $total);

  $colorNames = self::getColorName($hex);

  $shadeBox    = "░▒▓█████████████";
  $shadeString = "";

  $baseTitle = $total . " shades of " . $colorNames[ "name" ];

  $endArray[  ] = "";
  $padTitle     = str_pad($baseTitle, ($width - 4), " ", STR_PAD_BOTH);
  $endArray[  ] = $padTitle;

  //$boxTitle = TUI::genBoxTitle($baseTitle, 71, 1);
  //$shadeString .= $boxTitle["title"] . $boxTitle["top"];

  $count = 0;
  foreach ($shades as $shade) {
   $RGB = $shade[ "rgb" ];
   $HSL = $shade[ "hsl" ];
   $PAD = self::padValues($RGB, $HSL);
   $count++;

   $shadeString = " " . ANSI::color256($shade[ "ansi" ]) . $shadeBox . ANSI::reset();
   $shadeString .= self::propFmt("", $shade[ "hex" ], "");
   $shadeString .= self::propFmt("", "rgb(" . $RGB[ "r" ] . ", " . $RGB[ "g" ] . ", " . $RGB[ "b" ] . ")" . $PAD[ "rgb" ], "");
   $shadeString .= self::propFmt("", "hsl(" . $HSL[ "h" ] . ", " . $HSL[ "s" ] . ", " . $HSL[ "l" ] . ")", "");
   //$shadeString .=  PHP_EOL;

   $endArray[  ] = $shadeString;
  }

  $endArray[  ] = "";

  return $endArray;
  //$shadeString .= $boxTitle["btm"] . PHP_EOL;
  //fwrite(STDOUT, $shadeString);
 }

 /**
  * colorPalettePrint
  *
  * @param  mixed $limit
  * @param  mixed $colors
  * @param  mixed $title
  * @return array
  */
 public static function colorPalettePrint($limit = 2, $colors = [  ], $title = "")
 {
  $string     = " ▒██████████████▒";
  $testBlocks = [  ];
  $testBlock  = "";

  $count  = 0;
  $total  = 0;
  $clrtot = count($colors);

  foreach ($colors as $key => $color) {

   $cData = self::getMiniColorInfo($color);

   $paddedHex = (strlen($cData[ "hex" ]) < 7 ? $cData[ "hex" ] . "   " : $cData[ "hex" ]);
   $stringC   = ANSI::color256($cData[ "ansi" ]) . $string . ANSI::reset();

   $testBlock .= $stringC . " " . $paddedHex . "  ";
   $count++;
   $total++;

   if ($count >= $limit || $total >= $clrtot) {
    $testBlocks[  ] = $testBlock;
    $count          = 0;
    $testBlock      = "";
   }
  }

  return $testBlocks;
 }

 /**
  * colorStackPrint
  *
  * Display a Tailwind Style CSS stack theme
  *
  * @param  mixed $hex
  * @param  int $limit
  * @return array
  */
 public static function colorStackPrint($hex, $width = 64)
 {

  $endArray = [  ];

  $colorNames = self::getColorName($hex);
  $title      = ucfirst($colorNames[ "name" ]) . " Color Stack";

  $padTitle = str_pad($title, ($width - 4), " ", STR_PAD_BOTH);

  // First generate a stack for given color
  //$stacks = self::buildStack($hex);
  $stackColors = self::getTailwindStack($hex);

  // Then display the stack
  $string = " █▓▓█████████████";

  //$TUI = TUI::genBoxTitle($title, 32, 1);

  //$endArray[] = "";
  $endArray[  ] = $padTitle;
  $endArray[  ] = "";

  $count = 0;

  foreach ($stackColors as $key => $color) {

   // $string = TUI::phreakColorBar(" ", "");

   //$cData = self::getMiniColorInfo($color["hex"]);

   $key = ($color[ "key" ] === 50 || $color[ "key" ] === "50" ? $color[ "key" ] . " " : $color[ "key" ]);

   $colorBlock = ANSI::color256($color[ "ansi" ]) . $string . ANSI::reset();
   $colorBlock .= "  " . ANSI::bold() . $key . ANSI::reset() . "  " . $color[ "hex" ];

   $count++;

   $endArray[  ] = $colorBlock;
  }

  $endArray[  ] = "";

  return $endArray;
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
 public static function presentResults($output, $colorData, $boxOptions, $up = 16, $down = 5)
 {
  $Boxer = new Boxer;
  $Boxer->printBox($colorData, $boxOptions);

  TUI::moveCursor($output, "up", $up);
  TUI::echoMovingData($output, $colorData, [ "right" => 7 ]);
  TUI::moveCursor($output, "down", $down);
 }
}
