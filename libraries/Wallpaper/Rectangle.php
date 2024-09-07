<?php

namespace App\Commands\Wallpaper;

use App\Commands\Wallpaper\Raster;

use App\Commands\Phreaks\TUI;
use App\Commands\Phreaks\SixteenPhreaks;
use App\Commands\Phreaks\WallpaperPhreaks;

/**
 *
 * ░█▀▄░█▀▀░█▀▀░▀█▀░█▀█░█▀█░█▀▀░█░░░█▀▀
 * ░█▀▄░█▀▀░█░░░░█░░█▀█░█░█░█░█░█░░░█▀▀
 * ░▀░▀░▀▀▀░▀▀▀░░▀░░▀░▀░▀░▀░▀▀▀░▀▀▀░▀▀▀
 *
 * Square or Rounded Rectangle wallpapers.
 *
 */

class Rectangle
{

    const OUTDIR        = 'output/wallpapers/';
    const THUMBDIR      = 'output/wallpapers/_thumbs/';
    const BASE16DIR     = 'resources/palettes/base16/';
    const WALLWIDTH     = 1920;
    const WALLHEIGHT    = 1080;
    const THUMBWIDTH    = 800;
    const THUMBHEIGHT   = 600;
    const SPECT_WIDTH   = 80;
    const SPECT_HEIGHT  = 240;
    const DEBUG_MODE    = false;

    public $usedNumbers = array();


    //
    // ROUNDED RECTANGLES WALLPAPER
    //

    /**
     * drawRoundedRectangle : Helper function that draws a single rounded rectangle
     *
     * @param [type] $image
     * @param [type] $rd
     * @return void
     */
    public function drawRoundedRectangle($image, $rd)
    {
        $image->roundedRectangle(
            $rd["start_x"],
            $rd["start_y"],
            $rd["stop_x"],
            $rd["stop_y"],
            $rd["roundness"],
            $rd["rgb_color"],
            $rd["style"]
        );
    }

    /*========================================================================================
    ##                                                                                      ##
    ## RECTANGLE COORDINATE BUILDERS                                                        ##
    ##                                                                                      ##
    ##======================================================================================*/

    /**
     * computeSpecCoords : Spectrum Rectangle Coordinates
     *
     * @param integer $tclrs
     * @param integer $item_width
     * @param integer $item_height
     * @param array $colors
     * @param boolean $singleMode
     * @param boolean $rowTwo
     * @return void
     */
    public function computeSpecCoords($tclrs = 8, $item_width = 100, $item_height = 100, $colors = array(), $singleMode = true, $rowTwo = false)
    {
        $total_wide    = intval(round($tclrs * $item_width));
        $wall_size     = intval(SELF::WALLWIDTH - $total_wide);
        $wall_height   = intval(SELF::WALLHEIGHT - $item_height);
        $blank_start_x = intval(round($wall_size * 0.5));

        if ($singleMode !== true) {
            $half_height = intval(round($item_height * 0.5));
            if ($rowTwo === false)
                $blank_start_y = intval(round(($wall_height * 0.5) - ($half_height + 5)));
            else
                $blank_start_y = intval(round(($wall_height * 0.5) + ($half_height + 5)));
        } else {
            $blank_start_y = intval(round(($wall_height * 0.5)));
        }


        $rData = array(
            "width"    => $item_width,
            "height"   => $item_height,
            "total"    => $tclrs,
            "colors"   => $colors,
            "padding"  => 10,
            "start_x"  => $blank_start_x,
            "start_y"  => $blank_start_y,
            "stop_y"   => intval($blank_start_y + $item_height)
        );

        return $rData;
    }

    /**
     * computeRectSidePad : Compute Padding on either side
     *
     * @param [type] $total_r
     * @return void
     */
    public function computeRectSidePad($total_r)
    {
        if ($total_r > 8)
            $side_pads = 2;
        else if ($total_r >= 6)
            $side_pads = 4;
        else if ($total_r >= 4)
            $side_pads = 6;
        else
            $side_pads = 8;

        return $side_pads;
    }

    /**
     * compRect : Compute Rectangles Sizes
     *
     * @param array $allColors
     * @param boolean $rPos
     * @return void
     */
    public function compRect($allColors, $rPos = false)
    {
        $blank_sides         = 0;
        $totalColors         = count($allColors);
        $blank_sides         = $this->computeRectSidePad($totalColors);
        $rectangles          = intval($blank_sides + $totalColors);
        $indiv_rect_width    = intval(round(self::WALLWIDTH / $rectangles), 2);
        $blank_start_x       = intval(($blank_sides * 0.5) * $indiv_rect_width);
        $padding             = intval(round($indiv_rect_width * 0.45));

        // Vertical Positioning (optionally randomized)
        $blank_start_y       = intval(self::WALLHEIGHT * 0.20);
        $indiv_rect_height   = intval(round(self::WALLHEIGHT - ($blank_start_y * 2)));
        $blank_stop_y        = intval($blank_start_y + $indiv_rect_height);

        $cD = array(
            "width"    => $indiv_rect_width,
            "height"   => $indiv_rect_height,
            "total"    => $totalColors,
            "colors"   => $allColors,
            "padding"  => $padding,
            "start_x"  => $blank_start_x,
            "start_y"  => $blank_start_y,
            "stop_y"   => $blank_stop_y
        );

        if ($rPos !== false) {
            //TUI::Speaks("Random Mode Enabled!");

            $min_start_y = intval(self::WALLHEIGHT * 0.10);                      // 1000 * .1 = 100
            $max_start_y = intval(self::WALLHEIGHT * 0.20);                      // 1000 * .2 = 200
            $min_height  = intval(round(self::WALLHEIGHT - ($max_start_y * 2))); // 1000 - (200 * 2) = 400
            $max_height  = intval(round(self::WALLHEIGHT - ($min_start_y * 2))); // 1000 - (100 * 2) = 800
            $min_stop_y  = intval(self::WALLHEIGHT * 0.80);                      // 100 + 400 = 500
            $max_stop_y  = intval(self::WALLHEIGHT * 0.90);                      // 200 + 800 = 1000

            $cD["min_start_y"]  = $min_start_y;
            $cD["max_start_y"]  = $max_start_y;
            $cD["min_stop_y"]   = $min_stop_y;
            $cD["max_stop_y"]   = $max_stop_y;
            $cD["min_height"]   = $min_height;
            $cD["max_height"]   = $max_height;
        }

        return $cD;
    }


    /*========================================================================================
    ##                                                                                      ##
    ## RECTANGLE LOOPS                                                                      ##
    ##                                                                                      ##
    ##======================================================================================*/

    /**
     * roundedLoop : Loop through each of the colors and draw a colored rectangle
     *
     * @param [type] $image
     * @param [type] $rData
     * @param boolean $rPos
     * @return void
     */
    public function roundedLoop($image, $rData, $rPos = false)
    {
        $count  = 1;
        $next_x = 0;

        $totalRects = array();

        foreach ($rData["colors"] as $color) {
            $irData = array();

            if ($count === 1)
                $irData["start_x"] = $rData["start_x"];
            else
                $irData["start_x"] = $next_x;

            if ($rPos === false) {
                $irData["start_y"]   = $rData["start_y"];
                $irData["stop_x"]    = intval($irData["start_x"] + ($rData["width"] - 10));
                $irData["stop_y"]    = $rData["stop_y"];
            } else {
                //TUI::Speaks("min_max_y| ".$rData["min_start_y"]."__".$rData["max_start_y"]);
                $irData["start_y"]   = intval(rand($rData["min_start_y"], $rData["max_start_y"]));
                $irData["stop_x"]    = intval($irData["start_x"] + ($rData["width"] - 10));
                $irData["stop_y"]    = intval(rand($rData["min_stop_y"], $rData["max_stop_y"]));
            }

            $irData["roundness"] = $rData["padding"];
            $irData["rgb_color"] = $color;
            $irData["style"]     = "filled";

            //$this->drawRoundedRectangle($image, $irData);
            $totalRects[] = $irData;

            $next_x = intval($rData["start_x"] + intval($rData["width"] * $count));
            $count++;
        }

        // Draw dark shadow version
        foreach ($totalRects as $key => $irData) {

            // Reset rectangle data to be black and slightly off-center for shadows
            $irData["rgb_color"] = "000000";
            $irData["start_x"] = intval(round($irData["start_x"] + 4));
            $irData["stop_x"] = intval(round($irData["stop_x"] + 4));
            $irData["start_y"] = intval(round($irData["start_y"] + 3));
            $irData["stop_y"] = intval(round($irData["stop_y"] + 3));

            $this->drawRoundedRectangle($image, $irData);
        }

        // Blur the image
        $image->blur("gaussian", 10);

        // Draw colored version on top
        foreach ($totalRects as $rect) {
            $this->drawRoundedRectangle($image, $rect);
        }
    }

    /**
     * spectrumLoopDeLoop
     *
     * actually the "loop" for half and full spectrum images. done this way because shadows were added in after the fact.
     *
     * @param  mixed $rData
     * @return array $results
     */
    public function spectrumLoopDeLoop($rData)
    {
        $count   = 1;
        $next_x  = 0;
        $results = array();

        foreach ($rData["colors"] as $color) {
            $irData = array();

            if ($count === 1)
                $irData["start_x"] = $rData["start_x"];
            else
                $irData["start_x"] = $next_x;

            $irData["start_y"]   = $rData["start_y"];
            $irData["stop_x"]    = intval($irData["start_x"] + ($rData["width"] - 10));
            $irData["stop_y"]    = $rData["stop_y"];

            $irData["roundness"] = $rData["padding"];
            $irData["rgb_color"] = $color;
            $irData["style"]     = "filled";

            // Save Results for further processing
            $results[] = $irData;

            $next_x = intval($rData["start_x"] + intval($rData["width"] * $count));
            $count++;
        }

        return $results;
    }

    /**
     * spectrumLoop
     *
     * builds out the half or full spectrum image. if a base16 theme is used, a full spectrum (meaning colors and grays)
     * is generated. as opposed to just the half spectrum (half the amount of rectangles).
     *
     * @param  mixed $image
     * @param  mixed $rData
     * @param  mixed $r2Data
     * @return void
     */
    public function spectrumLoop($image, $rData, $r2Data = false)
    {
        $results = $this->spectrumLoopDeLoop($rData);

        if ($r2Data !== false) {
            $results2 = $this->spectrumLoopDeLoop($r2Data);
            $results = array_merge($results, $results2);
        }

        // Draw the shadows
        foreach ($results as $irData) {
            // Reset rectangle data to be black and slightly off-center for shadows
            $irData["rgb_color"]    = "000000";
            $irData["start_x"]      = intval(round($irData["start_x"] + 4));
            $irData["stop_x"]       = intval(round($irData["stop_x"] + 4));
            $irData["start_y"]      = intval(round($irData["start_y"] + 3));
            $irData["stop_y"]       = intval(round($irData["stop_y"] + 3));
            $this->drawRoundedRectangle($image, $irData);
        }

        // Blur the image
        $image->blur("gaussian", 10);

        if ($r2Data !== false)
            $image->darken(10);

        // Draw the colors
        foreach ($results as $rect) {
            $this->drawRoundedRectangle($image, $rect);
        }
    }

    /**
     * caveHeightWatcher
     *
     * checks if the height param has been used before. if it hasn't been used, a range from 90% - 110% of the heights value is added
     * to a global array, which the next height will be compared against. ensuring no heights are the same, or even close to the same
     *
     * @param  mixed $height
     * @return int $height
     */
    public function caveHeightWatcher($height)
    {
        $height = round($height);

        if (in_array($height, $this->usedNumbers) !== true) {
            $min = round($height * 0.90);
            $max = round($height * 1.10);

            $number = range($min, $max);
            foreach ($number as $n) {
                $this->usedNumbers[] = $n;
            }

            return $height;
        } else {
            return false;
        }
    }

    /**
     * caveHeightGenerator
     *
     * used to generate a random height value, which is then checked against a global array of previously used values.
     *
     * the generated value also attempts to take into account the previously generated value, ensuring there is a good
     * deal of difference between each section. So there shouldnt be two "tall" or two "short" sections next to each other
     *
     * TODO: there is a failsafe at the end where if the value generated is outside the range of min or max values, it simply
     * TODO: selects a random value from between the min and max. this should be better optimized.
     *
     * @param  mixed $min
     * @param  mixed $max
     * @param  mixed $current
     * @return void
     */
    public function caveHeightGenerator($min, $max, $current)
    {
        $random = intval(rand($min, $max));

        if ($current > round($max * 0.75))
            $current = (($current - $random) < $min) ? intval(round($random * 0.66)) : intval(round($current - $random));
        else
            $current = ($random < $current) ? intval(round($random * 2)) : intval(round($random * 1.13));

        // Check if we have failed....
        if (($current < $min) || ($current > $max))
            $current = intval(rand($min, $max));

        //TUI::Speaks("GENERATED: " . $current);

        return $current;
    }

    /**
     * caveHeightCheckLoop
     *
     * constantly loops through generating height values until it finds one that is not in the "used" range of values.
     * has a hard limit of 100 tries. if no available height has been generated in 100 times, it randomly selects one
     *
     * @param  mixed $min
     * @param  mixed $max
     * @param  mixed $current
     * @return int $validHeight
     */
    public function caveHeightCheckLoop($min, $max, $current)
    {
        // Check if height is unique

        $genLimit = 0;
        $tried = array();
        $valid = false;

        while ($valid === false) {
            $height = $this->caveHeightGenerator($min, $max, $current);

            $validHeight = $this->caveHeightWatcher($height);

            if ($validHeight !== false)
                $valid = true;
            else {
                $validHeight = $this->caveHeightWatcher($current);
                $tried[] = $height;
            }

            $genLimit++;
            if ($genLimit > 100) {
                shuffle($tried);
                $validHeight = $tried[13];
                $valid = true;
                TUI::Speaks("Generator Limit Reached! Random Choice: " . $validHeight);
            }
        }


        return $validHeight;
    }

    /**
     * caveLoop
     *
     * main loop function for the cave and cavern wallpapers.
     *
     * @param  mixed $image
     * @param  mixed $wData
     * @param  mixed $full
     * @return void
     */
    public function caveLoop($image, $wData, $full = false)
    {
        $results          = array();
        $total            = $wData["tc"];
        $oneThird         = ($full === false) ? intval(round(self::WALLWIDTH * 0.333)) : intval(round(self::WALLWIDTH * 1.0));
        $rWidth           = intval(round(($oneThird / $total)));        // 1/3 divided amongst total colors
        $maxHeight        = intval(round((self::WALLHEIGHT * 0.45)));   // tallest limit for rectangles
        $minHeight        = intval(round(($maxHeight * 0.40)));         // minimum limit for rectangles
        $currentHeight    = 0;                                          // random starting point for Y
        $startingX        = ($full === false) ? $oneThird : 0;          // fixed starting point for X
        $nextX            = 0;                                          // counter for X pos
        $count            = 0;                                          // counter for loop total
        $maxY             = intval(self::WALLHEIGHT);

        // Top & Bottom Array declarations
        $trData = array("roundness" => 0, "style" => "filled");
        $brData = array("roundness" => 0, "style" => "filled");

        foreach ($wData["colors"] as $color) {
            $minMaxRand = intval(rand($minHeight, $maxHeight));

            if ($nextX === 0) {
                $currentX = $startingX;
                $currentHeight = $minMaxRand;

                // Record initial height in used numbers list
                $this->caveHeightWatcher($currentHeight);
            } else {
                // Very complex loop, checks if next height isnt withing +-5% of all other numbers
                $currentHeight = $this->caveHeightCheckLoop($minHeight, $maxHeight, $currentHeight);
                $currentX = $nextX;
            }

            // Top Row Data
            $trData["start_x"]    = $currentX;
            $trData["stop_x"]     = intval($trData["start_x"] + $rWidth);
            $trData["start_y"]    = 0;
            $trData["stop_y"]     = $currentHeight;
            $trData["rgb_color"]  = $color;

            // Bottom Row Data
            $brData["start_y"]    = intval((self::WALLHEIGHT - ($maxHeight - $currentHeight)) - (self::WALLHEIGHT * 0.15));
            $brData["stop_y"]     = $maxY;
            $brData["start_x"]    = $currentX;
            $brData["stop_x"]     = intval($trData["start_x"] + $rWidth);
            $brData["rgb_color"]  = $color;

            // Collect Results for further processing
            $results[] = array("top" => $trData, "btm" => $brData);

            $nextX = intval($currentX + $rWidth);
            $count++;
        }

        // Draw the shadows
        foreach ($results as $key => $value) {
            $trData = $value["top"];
            $brData = $value["btm"];

            $trData["rgb_color"]  = "000000";
            $trData["start_x"]    = intval(round($trData["start_x"] + 4));
            $trData["stop_x"]     = intval(round($trData["stop_x"] + 4));
            $trData["start_y"]    = intval(round($trData["start_y"] + 4));
            $trData["stop_y"]     = intval(round($trData["stop_y"] + 4));

            $brData["rgb_color"]  = "000000";
            $brData["start_y"]    = intval(round($brData["start_y"] - 5));
            $brData["stop_y"]     = intval(round($brData["stop_y"] - 5));
            $brData["start_x"]    = intval(round($brData["start_x"] + 4));
            $brData["stop_x"]     = intval(round($brData["stop_x"] + 4));

            // Draw Each Row
            $this->drawRoundedRectangle($image, $trData);
            $this->drawRoundedRectangle($image, $brData);
        }

        // Blur the image
        $image->blur("gaussian", 10);

        // Draw the colored shapes
        foreach ($results as $key => $value) {
            $trData = $value["top"];
            $brData = $value["btm"];

            // Draw Each Row
            $this->drawRoundedRectangle($image, $trData);
            $this->drawRoundedRectangle($image, $brData);
        }
    }

    /**
     * makeRectangle
     *
     * Main logic function to generate a rounded rectangle wallpaper
     *
     * @param [type] $image
     * @param boolean $theme
     * @param boolean $theme_type
     * @param boolean $shuffle
     * @param boolean $texture
     * @param boolean $randomPositions
     * @return void
     */
    public function makeRectangle($image, $themeName = false, $themeType = false, $randomPositions = false, $shuffle = false, $texture = false)
    {
        $wData = CMD::newWallpaper($themeType, $themeName, $shuffle);
        $bgColor   = $wData["bg"];

        // Make a blank image to draw on
        $image
            ->fromNew(self::WALLWIDTH, self::WALLHEIGHT, $bgColor)
            ->autoOrient();

        // Get the Data setup based on amount of colors
        $r_data = $this->compRect($wData["colors"], $randomPositions);

        // Now we Draw on the blank image
        //TUI::Speaks("randomPositions".$randomPositions);
        $this->roundedLoop($image, $r_data, $randomPositions);

        // Save Final Wallpaper Image
        $string_prefix = "recty";
        if ($randomPositions === "random") $string_prefix = "randy";

        $string = $string_prefix . "_" . $wData["themeName"] . "_";

        $result = CMD::saveRasterWallpaper($image, $string);

        return $result;
    }

    /**
     * makeCaveOfColors
     *
     * cave and cavern wallpapers logic
     *
     * @param  mixed $image
     * @param  mixed $themeName
     * @param  mixed $themeType
     * @param  mixed $fillScreen
     * @param  mixed $shuffle
     * @param  mixed $texture
     * @return void
     */
    public function makeCaveOfColors($image, $themeName = false, $themeType = false, $fillScreen = false, $shuffle = false, $texture = false)
    {
        // DEBUG DATA
        if (self::DEBUG_MODE !== false) {
            $data = array("name" => $themeName, "type" => $themeType, "fillScreen" => $fillScreen, "shuffle" => $shuffle, "texture" => $texture);
            TUI::printDebug($data);
            //exit;
        }

        // Get the Theme, its colors and associated color data
        $wData = CMD::newWallpaper($themeType, $themeName, $shuffle);

        $image
            ->fromNew(self::WALLWIDTH, self::WALLHEIGHT, $wData["bg"])
            ->autoOrient();

        if ($fillScreen !== false)
            $this->caveLoop($image, $wData, true);
        else
            $this->caveLoop($image, $wData);


        // Save the result
        $string =  ($fillScreen !== false) ?  "_cavern-of-colors_" : "_cave-of-colors_";
        $result = CMD::saveRasterWallpaper($image, $wData["themeName"] . $string);

        return $result;
    }

    /**
     * makeSimpleRectangle
     *
     * @param [type] $image
     * @param boolean $theme
     * @param boolean $theme_type
     * @param boolean $shuffle
     * @param boolean $texture
     * @param boolean $randomPositions
     * @return void
     */
    public function makeSpecRectangle($image, $themeName = false, $themeType = false, $singleDouble = true, $shuffle = false, $texture = false)
    {

        $wData       = CMD::newWallpaper($themeType, $themeName, $shuffle);
        $bgColor     = $wData["bg"];
        $tclrs       = count($wData["colors"]);

        // Make a blank image to draw on
        $image
            ->fromNew(self::WALLWIDTH, self::WALLHEIGHT, $bgColor)
            ->autoOrient();

        // Get the Data setup based on amount of colors
        $item_height    = 200;
        $item_width     = 100;
        $mode           = true;
        $rowTwo         = false;

        // Depending on 1 or 2 rows of rectangles.
        $colorList = $wData["colors"];

        if ($singleDouble !== true && isset($wData["grays"])) {
            $colorList = $wData["grays"];
            $mode = false;
            $rowTwo = false;
        }

        // Generate the start & stop coordinates for X & Y
        $rData = $this->computeSpecCoords($tclrs, $item_width, $item_height, $colorList, $mode, $rowTwo);

        // Now we plot coordinates on the blank image

        if ($singleDouble !== true && isset($wData["grays"])) {

            //$this->spectrumLoop($image, $rData);

            // And then we do it again. only slightly different
            $r2Data = $this->computeSpecCoords($tclrs, $item_width, $item_height, $wData["colors"], $mode, true);
            $this->spectrumLoop($image, $rData, $r2Data);
        } else {
            $this->spectrumLoop($image, $rData);
        }

        // @TODO add Texture Overlay Here.

        // Save Final Wallpaper Image
        $string = "halfSpec_" . $wData["themeName"] . "_";
        if ($singleDouble !== true && isset($wData["grays"])) $string = "fullSpec_" . $wData["themeName"] . "_";

        $result = CMD::saveRasterWallpaper($image, $string);

        return $result;
    }

    /**
     * makeSpectrum
     *
     * Generate a simple image with each Base16 color as a rectangle.
     *
     * NOTE: This is a special Wallpaper function, that is ONLY used for Base16 themes.
     *       It generates a preview image of each theme, or a theme "spectrum".
     *       makeSpectrum is called via php phreak sixteen --spectrum
     *       also it runs for every theme found in the B16 theme folder at once.
     *
     * @param [type] $image
     * @param [type] $theme
     * @return void
     */
    public function makeSpectrum($image, $theme)
    {
        $allColors   = SixteenPhreaks::spectrumSorter($theme, false, true);
        $bgColor     = $allColors["background"];

        // Add Colors & Grays into one array.
        // For some reason $array1 + $array2 wasnt working
        // and I dont want the color order messed up. so for now this stupid bit of shite.
        $spectrum = array();
        foreach ($allColors["grays"] as $gray) {
            $spectrum[] = $gray;
        }

        foreach ($allColors["colors"] as $bright) {
            $spectrum[] = $bright;
        }

        $indiv_rect_width  = self::SPECT_WIDTH;
        $indiv_rect_height = self::SPECT_HEIGHT;
        $total_width       = intval($indiv_rect_width * 16);

        // Make a blank image to draw on
        $image->fromNew($total_width, $indiv_rect_height, $bgColor)->autoOrient();

        $rd = array("start_x" => 0, "stop_x" => $indiv_rect_width);

        foreach ($spectrum as $color) {
            $image->roundedRectangle(
                $rd["start_x"],
                0,
                $rd["stop_x"],
                $indiv_rect_height,
                0,
                $color,
                "filled"
            );

            $last_stop  = $rd["stop_x"];
            $rd = array("start_x" => $last_stop, "stop_x" => intval(round($last_stop + $indiv_rect_width)));
        }

        // Save Final Wallpaper Image
        $result = self::BASE16DIR . $theme . ".png";
        $image->toFile($result, "image/png");

        return $result;
    }
}
