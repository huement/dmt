<?php

namespace App\Commands\Wallpaper;

use App\Commands\Phreaks\TUI;
use App\Commands\Phreaks\ColorPhreaks;
use App\Commands\Phreaks\FilePhreaks;

use Imagine\Image;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Box;
use Imagine\Image\Point;

/**
 * Circle
 * @abstract This class builds out Circle Themed wallpapers. There are a number of different templates some with a few circles
 * some with quite a few circles. Some classes can be thrown an optional parameter to randomize the color &/or location of the circles.
 *
 * @todo The function "uniqueX" needs to do a better job of checking if a RANGE of values is present not a specific point.
 * @todo The function "circleLocation" checks if the newly generated value is between two values from an array of previously used positions. It should do a better job and loop through the array instead of taking the min and max values from the array.
 *
 */
class Circle
{
    public $DEBUG      = false;

    const OUTDIR       = 'output/wallpapers/';
    const THUMBDIR     = 'output/wallpapers/_thumbs/';
    const WALLWIDTH    = 1920;
    const WALLHEIGHT   = 1080;
    const THUMBWIDTH   = 800;
    const THUMBHEIGHT  = 600;
    const BORDERSIZE   = 40;
    const BORDERMID    = 150;
    const BORDERIN     = 50;
    const BBMOVE       = 250;
    const STACKMIN     = 600;
    const STACKMAX     = 750;
    const SMALLGAP     = 10;
    const INLINESPACE  = 200;
    const GRIDSQUARE   = 50;
    const SHADOW       = "000000";
    const BLURAMT      = 5;

    protected $mainSize;
    protected $circSpot_x;
    protected $circSpot_y;
    protected $dirMove;
    protected $allSizes;
    protected $xRangers;
    protected $yRangers;
    protected $xTotal;
    protected $yTotal;
    protected $xItems;
    protected $yItems;
    protected $GRID;

    public $imagine;
    public $themeName;
    public $themeType;
    public $shuffle;
    public $texture;

    public $palette;
    public $image;
    public $theme;
    public $params;


    public function __construct($imagine, $themeName, $themeType, $shuffle, $texture)
    {
        // Setup Globals for all Wallpapers
        $this->imagine    = $imagine;
        $this->themeName  = $themeName;
        $this->themeType  = $themeType;
        $this->shuffle    = $shuffle;
        $this->texture    = $texture;

        // Get the colors & theme data
        $this->theme = CMD::newWallpaper($this->themeType, $this->themeName, $this->shuffle);

        // Initialize Color Palette
        $this->palette = new RGB();

        // Create blank image to build upon
        $this->image = $this->imagine->create(
            new Box(self::WALLWIDTH, self::WALLHEIGHT),
            $this->palette->color($this->theme["bg"])
        );

        // Texture Overlays
        if ($this->texture !== false)
            $this->backgroundTexture();

        // SETUP GLOBAL "BATTLESHIP" GRID
        $this->GRID       = CMD::battleShipGrid(self::WALLWIDTH, self::WALLHEIGHT, self::GRIDSQUARE);
        $this->xRangers   = $this->GRID["x"];
        $this->yRangers   = $this->GRID["y"];
        $this->xTotal     = $this->GRID["xTotal"];
        $this->yTotal     = $this->GRID["yTotal"];
        $this->refillXYItems();

        if ($this->DEBUG !== false)
            TUI::Message("Constructor Finished Loading", " Circle ");
    }

    public function refillXYItems()
    {
        $this->refillXItems();
        $this->refillYItems();
    }

    public function refillXItems()
    {
        $this->xItems = range(0, $this->xTotal);
    }

    public function refillYItems()
    {
        $this->yItems = range(0, $this->yTotal);
    }

    public function randomCoordinate()
    {
        shuffle($this->xItems);
        shuffle($this->yItems);

        $xRand = array_pop($this->xItems);
        $yRand = array_pop($this->yItems);

        // $xMin = $xRand[0];
        $xArr = $this->xRangers[$xRand];
        $xMin = $xArr[0];
        $xMax = end($xArr);
        $xVal = intval(rand($xMin, $xMax));

        $yArr = $this->yRangers[$yRand];
        $yMin = $yArr[0];
        $yMay = end($yArr);
        $yVal = intval(rand($yMin, $yMay));

        // Check Levels

        if (count($this->xItems) < 1)
            $this->refillXItems();

        if (count($this->yItems) < 1)
            $this->refillYItems();

        return array("x" => $xVal, "y" => $yVal);
    }

    /*========================================================================================
    ##                                                                                      ##
    ## HELPER FUNCTIONS                                                                     ##
    ##                                                                                      ##
    ##======================================================================================*/

    public function drawColoredCircle($wallpaperImage, $wallpaperPalette, $cX, $cY, $cSize, $cColor, $fill = true, $thickness = 1)
    {
        // if ($this->DEBUG !== false)
        //     TUI::printDEBUG(array("size" => $cSize, "X" => $cX, "Y" => $cY, "color" => $cColor));

        $wallpaperImage->draw()
            ->ellipse(
                new Point($cX, $cY),
                new Box($cSize, $cSize),
                $wallpaperPalette->color($cColor),
                $fill,
                $thickness
            );
    }


    /**
     * drawCircle
     *
     * Quickly draw a colored circle w/ optional fill and thickness of the line
     *
     * @param  int $x X Coords
     * @param  int $y Y Coords
     * @param  int $s Size
     * @param  string $c color
     * @param  bool $f fill yes/no
     * @param  int $l line thickness
     * @return void
     */
    public function drawCircle($x, $y, $s, $c, $f = true, $l = 1)
    {
        $this->image->draw()
            ->ellipse(
                new Point($x, $y),
                new Box($s, $s),
                $this->palette->color($c),
                $f,
                $l
            );
    }

    public function drawTrueCircle($x, $y, $size, $color, $fill = true, $thickness = 1)
    {
        $this->image->draw()
            ->circle(
                new Point($x, $y),
                $size,
                $this->palette->color($color),
                $fill,
                $thickness
            );
    }

    public function drawLine($wallpaperImage, $wallpaperPalette, $startX, $startY, $stopX, $stopY, $color, $thickness = 1)
    {
        $wallpaperImage->draw()
            ->line(
                new Point($startX, $startY),
                new Point($stopX, $stopY),
                $wallpaperPalette->color($color),
                $thickness
            );
    }

    /**
     * drawCompoundCircle
     *
     * Four possible circles, two colored, two borders.
     *
     *  _________________
     * |  _____________  | outB
     * | |             | |
     * | |   _______   | | main
     * | |  |       |  | |
     * | |  |  ___  |  | | inB
     * | |  | |   | |  | |
     * | |  | |   | |  | | center
     * -------------------
     *
     * @param  mixed $cData
     * @param  mixed $rData
     * @return void
     */
    public function drawCompoundCircle($rData)
    {
        $X    = $rData["x"];
        $Y    = $rData["y"];
        $sz   = $rData["size"];

        // Ensure a minimum size for the center most circle
        if (isset($sz["center"]) && $sz["center"] !== false && $sz["center"] < 30)
            $sz["center"] = intval(rand(30, 100));

        // Check for special case where only outB has size, in which case, this is a "shadow" circle
        if ($sz["inB"] === false && $sz["main"] === false && $sz["center"] === false)
            $bgColor = $this->theme["shadow"];
        else
            $bgColor = $this->theme["bg"];


        // Display circle + borders for each size that isnt set to false
        if (isset($sz["outB"]) && $sz["outB"] !== false)
            $this->drawCircle($X, $Y, $sz["outB"], $bgColor);

        if (isset($sz["main"]) && $sz["main"] !== false)
            $this->drawCircle($X, $Y, $sz["main"], $rData["color"]);

        if (isset($sz["inB"]) && $sz["inB"] !== false)
            $this->drawCircle($X, $Y, $sz["inB"], $this->theme["bg"]);

        if (isset($sz["center"]) && $sz["center"] !== false)
            $this->drawCircle($X, $Y, $sz["center"], $rData["color"]);
    }

    /**
     * drawShadowedCircle
     *
     * draw a circle like normal, however a shadowed is drawn slightly offset down / right from the main
     *
     * @param  mixed $X
     * @param  mixed $Y
     * @param  mixed $size
     * @param  mixed $color
     * @param  mixed $fill
     * @param  mixed $thickness
     * @return void
     */
    public function drawShadowedCircle($X, $Y, $size, $color, $fill = false, $thickness = 1)
    {
        $shadow = (isset($this->theme["shadow"])) ? $this->theme["shadow"] : self::SHADOW;

        $this->drawColoredCircle($this->image, $this->palette, intval($X + 4), intval($Y + 4), intval($size * 0.99), $shadow, $fill, $thickness);
        $this->drawColoredCircle($this->image, $this->palette, $X, $Y, intval($size), $color, $fill, $thickness);
    }

    /**
     * drawBouncyCircle
     *
     * cool custom slightly random circle combo.
     * main filled circle with a outline similar sized circle "bounced" around the main
     *
     * @param  mixed $X
     * @param  mixed $Y
     * @param  mixed $size
     * @param  mixed $color
     * @param  mixed $fill
     * @param  mixed $thickness
     * @return void
     */
    public function drawBouncyCircle($X, $Y, $size, $color, $fill = false, $thickness = 1)
    {
        $bounceSize = intval(rand(intval(round($size * 0.5)), intval(round($size * 1.5))));
        $bounceX = intval(rand(5, 10));
        $bounceY = intval(rand(5, 10));

        $chanceX = mt_rand(1, 2);
        if ($chanceX === 2)
            $xPos = $X - $bounceX;
        else
            $xPos = $X + $bounceX;

        $chanceY = mt_rand(1, 2);
        if ($chanceY === 2)
            $yPos = $Y - $bounceY;
        else
            $yPos = $Y + $bounceY;

        $bounceThick = ($thickness <= 1) ? 5 : $thickness;

        $this->drawColoredCircle($this->image, $this->palette, $xPos, $yPos, $bounceSize, $color, false, $bounceThick);
        $this->drawColoredCircle($this->image, $this->palette, $X, $Y, $size, $color, $fill, $thickness);
    }

    /*========================================================================================
    ##                                                                                      ##
    ## Display Functions                                                                    ##
    ##                                                                                      ##
    ##======================================================================================*/

    public function backgroundTexture()
    {
        // Save Blank Background
        $tempString   = FilePhreaks::randomTxtString(5);
        $tempName     = $tempString . '.png';
        $tempFile     = base_path(self::OUTDIR . $tempName);

        $this->image->save($tempFile);

        // Apply Texture
        CMD::applyTexture($tempFile, true);

        // ReOpen Result as new Image
        $this->image = $this->imagine->open($tempFile);

        FilePhreaks::remove_file($tempFile);

        return true;
    }

    /**
     * Store compound circle's location to avoid overlapping
     *
     * @param int $size
     * @param int $xPoint
     * @return void
     */
    public function usedUpRange($size, $xPoint)
    {
        $halfSize = intval(round($size * 0.5));
        $minPoint = intval($xPoint - $halfSize);
        $maxPoint = intval($xPoint + $halfSize);

        if ($minPoint <= 0)
            $minPoint = intval($halfSize * 0.25);

        if ($maxPoint >= intval(self::WALLWIDTH))
            $maxPoint = intval(self::WALLWIDTH - ($halfSize * 0.25));

        $values = range($minPoint, $maxPoint);
        foreach ($values as $v) {
            $this->xRangers[] = $v;
        }

        //$this->xRangers[] = $minPoint;
        //$this->xRangers[] = $maxPoint;
    }

    /**
     * Row style circles loops
     *
     * @param [type] $image
     * @param [type] $palette
     * @param int $tfc
     * @param [type] $colorMode
     * @return void
     */
    public function rowLoop($image, $palette, $tfc, $colorMode)
    {
    }

    /**
     * Generate a spot on X coords that has never been covered
     *
     * @return int $X coordinate
     */
    public function uniqueX()
    {
        $X = mt_rand(0, intval(self::WALLWIDTH));

        if (in_array($X, $this->xRangers))
            return 0;
        else
            return $X;
    }

    public function edgeXWatcher($value, $size)
    {
        $width = self::WALLWIDTH;
        $minEdge = intval(round($size * 0.8));
        $maxEdge = intval(round($width - $minEdge));

        $rAmt = intval(round((rand(2, 5) * 0.01) * $size));

        $flip = mt_rand(1, 4);
        if ($flip <= 2)
            $mode = "plus";
        else
            $mode = "minus";

        if ($value < $minEdge)
            $value = ($mode === "plus") ? intval(round($minEdge + $rAmt)) : intval(round($minEdge - $rAmt));

        if ($value > $maxEdge)
            $value = ($mode === "plus") ? intval(round($maxEdge + $rAmt)) : intval(round($maxEdge - $rAmt));

        return $value;
    }

    public function edgeYWatcher($value, $size)
    {
        $width = self::WALLHEIGHT;
        $minEdge = intval(round($size * 0.6));
        $maxEdge = intval(round($width - $minEdge));

        $rAmt = intval(round((rand(1, 4) * 0.01) * $size));

        $flip = mt_rand(1, 4);
        if ($flip <= 2)
            $mode = "plus";
        else
            $mode = "minus";

        if ($value < $minEdge)
            $value = ($mode === "plus") ? intval(round($minEdge + $rAmt)) : intval(round($minEdge - $rAmt));

        if ($value > $maxEdge)
            $value = ($mode === "plus") ? intval(round($maxEdge + $rAmt)) : intval(round($maxEdge - $rAmt));

        return $value;
    }

    /**
     * Compute Circle Size
     *
     * @param int $count
     * @return void
     */
    public function compCircSizes($count)
    {
        $sizes = array();

        $multiplier = "0.2" . $count;
        settype($multiplier, "float");

        $mSize = round(floatval($multiplier * self::WALLWIDTH));
        if ($count === 1) $this->mainSize = $mSize;

        $borderSize = intval($mSize + self::BORDERSIZE);

        $sizeCheck = false;
        $sCC = 0;

        while ($sizeCheck === false) {
            $rOne = mt_rand(self::BORDERSIZE, intval(self::BORDERSIZE * 3));
            $rTwo = mt_rand(self::BORDERSIZE, intval(self::BORDERSIZE * 3));

            $bInSize    = intval($mSize - $rOne);
            $cInSize    = intval($bInSize - $rTwo);

            if (($bInSize > $cInSize) && $cInSize > 0) $sizeCheck = true;

            $sCC++;

            if ($sCC > 50) {
                $sizeCheck = true;

                if ($this->DEBUG !== false)
                    TUI::Message("Size Check Limit Reached.", " Circle ");

                $bInSize    = intval($this->mainSize * 1.6);
                $cInSize    = intval($this->mainSize * 1.4);
            }
        }

        $sizes["main"]   = $mSize;
        $sizes["outB"]   = $borderSize;
        $sizes["inB"]    = $bInSize;
        $sizes["center"] = $cInSize;

        return $sizes;
    }

    public function randomCompoundGenerator($baseSize)
    {
        $sizes = array();

        $bMult = rand(3, 23);
        $bPerc = (100 + $bMult) * 0.01;
        $borderSize = intval($baseSize * $bPerc);

        $mMult = rand(66, 100);
        $mPerc = ($mMult * 0.01);
        $mainSize = intval($borderSize * $mPerc);

        $rOne = rand(intval($baseSize * 0.5), intval($baseSize * 0.8));
        $rTwo = rand(intval($baseSize * 0.2), intval($baseSize * 0.48));

        $cInSize    = intval($baseSize - $rOne);
        $bInSize    = intval($baseSize - $rTwo);

        if ($mainSize <= ($bInSize * 0.95))
            $mainSize = intval($mainSize * (mt_rand(3, 10) * 0.01));

        // Possible Variations
        $variation = mt_rand(1, 4);
        if (($variation % 2) === 0) {
            $rand = mt_rand(1, 3);
            if (($rand % 2) === 0)
                $cInSize = false;
            else
                $bInSize = intval($cInSize + rand(20, 60));
        } else
            $rand = false;

        if (($borderSize * 0.80) > $mainSize) {
            if ($this->DEBUG !== false)
                TUI::Message("Random Compound Size Mismatch", "WARN");

            $borderSize = $mainSize * intval((rand(5, 13) + 100) * 0.01);
        }

        if (($borderSize - $mainSize) < 30)
            $borderSize = $mainSize + intval(rand(30, 60));

        if (intval(round($mainSize * 0.94)) < $bInSize) {
            if ($this->DEBUG !== false)
                TUI::Message("Small Ring Fix", "WARN");

            $bInSize  = intval(ceil($bInSize * 0.96));
            $mainSize = intval(ceil($mainSize * 1.04));
        }

        if ((intval(round($mainSize * 0.55)) > $bInSize) && $cInSize !== false) {
            if ($this->DEBUG !== false)
                TUI::Message("Fat Middle", "WARN");

            $bInSize = intval($bInSize * intval((rand(3, 13) + 100) * 0.01));
        }

        //if($cInSize !== false && ($bInSize ))


        $sizes["main"]   = $mainSize;
        $sizes["outB"]   = $borderSize;
        $sizes["inB"]    = $bInSize;
        $sizes["center"] = $cInSize;

        return $sizes;
        //TUI::printDEBUG($sizes);
    }

    /*========================================================================================
    ##                                                                                      ##
    ## WALLPAPER GENERATION FUNCTIONS                                                       ##
    ##                                                                                      ##
    ##======================================================================================*/

    /**
     * Single Centered Row of themed colored circles
     *
     * @param [type] $imagine
     * @param [type] $theme
     * @param [type] $themeType
     * @return void
     */
    public function makeCircleInline()
    {

        // Generate Inline Circle Pattern
        $maxCs          = count($this->theme["colors"]);
        $WALLWIDTHMax   = intval(round(self::WALLWIDTH - (self::INLINESPACE * 2)));
        $Y              = intval(round(self::WALLHEIGHT * 0.5));
        $circleSize     = intval(round(($WALLWIDTHMax / $maxCs))); // Small Gap between each circle.
        $startX         = intval((self::INLINESPACE + ($circleSize * 0.5)) + 5);

        $circleResults  = array();

        // Run the Loop, Capture the results
        foreach ($this->theme["colors"] as $color) {

            // Compute the Size of each circle
            $sizes = $this->randomCompoundGenerator(intval($circleSize * 1.2));

            //$circleResults[] = array("x" => $startX, "y" => $Y, "size" => intval($circleSize - 10), "color" => $color);
            $circleResults[] = array("x" => $startX, "y" => $Y, "size" => $sizes, "color" => $color);
            $startX = intval($startX + $circleSize);
        }

        // Draw the black shadows
        foreach ($circleResults as $r) {
            $r["x"] = $r["x"] + 5;
            $r["y"] = $r["y"] + 5;

            $r["size"]["main"]    = false;
            $r["size"]["inB"]     = false;
            $r["size"]["center"]  = false;

            $this->drawCompoundCircle($r);
        }

        // Blur the image
        if ($this->DEBUG !== true)
            $this->image->effects()->blur(8);

        // Draw the colors
        // Randomize the Order they are drawn in. Otherwise the overlapping looks stupid.

        $tC = count($circleResults);
        $rg = range(0, ($tC - 1));
        shuffle($rg);
        shuffle($rg);

        if ($this->DEBUG === false) {
            foreach ($rg as $n) {
                $this->drawCompoundCircle($circleResults[$n]);
            }
        } else {
            foreach ($circleResults as $r) {
                $this->drawCompoundCircle($r);
                TUI::printDEBUG($r);
            }
        }
    }

    /**
     * Rows & Rows of theme colored circles. Depending on params size or color will be randomized.
     *
     * @param [type] $imagine
     * @param string $themeName
     * @param string $themeType
     * @param string $colorMode
     * @return void
     */
    public function makeCircleRows($colorMode = "random")
    {
        // Generate Rows and Rows of Circles
        $circleSize = intval(round(self::WALLWIDTH * 0.10));
        $tcs        = count($this->theme["colors"]);
        $tCRange    = $tcs - 1;
        $tenCount   = 0;
        $tenMax     = intval(round(self::WALLHEIGHT / 10));

        //$colorMode = "randomNot";

        while ($tenCount <= $tenMax) {
            $this->circSpot_x = intval(round($circleSize * 0.5));

            $rowCount = 0;
            $rowMax   = 9;
            $tCCount  = -1;
            $blackOut = false;

            while ($rowCount <= $rowMax) {

                if ($tCCount > $tCRange) $blackOut = true;

                if ($rowCount > 0) $this->circSpot_x = intval(round(($circleSize + $this->circSpot_x)));

                if ($tenCount > 0)
                    $this->circSpot_y = intval(round(($circleSize * $tenCount) + 60));
                else
                    $this->circSpot_y = 60;

                $mainColor  = $this->theme["bg"];
                $randColor  = mt_rand(0, $tCRange);
                $randChance = mt_rand(0, $tcs);

                if ($colorMode === "random") {
                    if ($randChance >= intval(round($tcs * 0.5))) $mainColor = $this->theme["colors"][$randColor];
                    $cSizish = intval(round(rand(5, intval(round($circleSize - 8)))));
                } else {
                    if ($blackOut !== true && $tCCount !== -1)
                        $mainColor = $this->theme["colors"][$tCCount];
                    else
                        $mainColor = $this->theme["bg"];

                    $cSizish = intval(round($circleSize - 8));
                }

                $this->drawColoredCircle($this->image, $this->palette, $this->circSpot_x, $this->circSpot_y, $cSizish, $mainColor);

                $rowCount++;
                $tCCount++;
            }

            $tenCount++;
        }
    }


    // ---------------------------------------------------------------------------
    // CIRCLE BUILDING WALLPAPERS v2.00
    // ---------------------------------------------------------------------------

    public function coolCompounder($data)
    {
        $total = count($this->theme["colors"]);

        $borderSize = self::BBMOVE * 2;
        $circleArea = self::WALLWIDTH - $borderSize;

        // Each Circle can takeup roughly this much space
        $cSize = intval(round(($circleArea / $total)));

        $results = array();
        $mR = array();

        $cT = count($this->theme["colors"]);

        // Complex Loop that attempts to randomly place compounded circles
        foreach ($this->theme["colors"] as $color) {

            $loc = $this->randomCoordinate();

            if ($cT > 6)
                $base = intval(rand(intval($cSize * 1.42), intval($cSize * 1.88)));
            else
                $base = intval(rand(intval($cSize * 1.23), intval($cSize * 1.66)));

            $sizes  = $this->randomCompoundGenerator($base);

            $results[] = array("x" => $loc["x"], "y" => $loc["y"], "size" => $sizes, "color" => $color);
        }

        // Super Slick Random Size / Position Circle Generator
        // Peak Circle 2.0 Programming lol
        foreach ($this->theme["colors"] as $color) {
            $variation = mt_rand(1, 4);
            if (($variation % 2) === 0) {
                if ($cT > 6)
                    $size = intval(rand(intval($cSize * 0.66), intval($cSize * 0.88)));
                else
                    $size = intval(rand(intval($cSize * 0.42), intval($cSize * 0.66)));

                $sizes  = $this->randomCompoundGenerator($size);

                $fR   = mt_rand(0, 5);
                $fill = (($fR % 2) === 0) ? true : false;
                $line = (($fR % 2) === 0) ? 1 : intval(rand(2, 6));

                $loc  = $this->randomCoordinate();
                $mR[] = array("x" => $loc["x"], "y" => $loc["y"], "size" => $sizes, "color" => $color, "fill" => $fill, "line" => $line);
            }
        }


        // ---------------- [ DRAWING THE CIRCLES ] ----------------------------------------------

        // Draw the black shadows
        foreach ($results as $r) {
            $r["x"] = $r["x"] + 5;
            $r["y"] = $r["y"] + 5;

            $r["size"]["main"]    = false;
            $r["size"]["inB"]     = false;
            $r["size"]["center"]  = false;

            $this->drawCompoundCircle($r);
        }
        foreach ($mR as $r) {
            $r["x"] = $r["x"] + 5;
            $r["y"] = $r["y"] + 5;
            $r["color"] = $this->theme["shadow"];
            $r["size"]["main"]    = false;
            $r["size"]["inB"]     = false;
            $r["size"]["center"]  = false;
            //$this->drawCircle($r["x"], $r["y"], $r["size"], $r["color"], $r["fill"], $r["line"]);
            $this->drawCompoundCircle($r);
        }


        // Blur the image
        if ($this->DEBUG !== true)
            $this->image->effects()->blur(8);


        // Draw the colors
        foreach ($results as $r) {
            $this->drawCompoundCircle($r);
        }
        foreach ($mR as $r) {
            //$this->drawCircle($r["x"], $r["y"], $r["size"], $r["color"], $r["fill"], $r["line"]);
            $this->drawCompoundCircle($r);
        }
    }

    /**
     * Identically sized rings w/ smaller diameter on each iteration
     *
     * @param [type] $imagine
     * @param string $themeName
     * @param string $themeType
     * @param boolean $fill
     * @param boolean $pos
     * @return void
     */
    public function makeRainbow($pos = true, $cloud = true)
    {
        $fill         = true;
        $thickness    = 1;
        $tCount = intval(count($this->theme["colors"]));

        // Set starting point for the rainbow
        if ($pos === true) {
            $X = intval(round(self::WALLWIDTH * 0.5));
            $Y = intval(round(self::WALLHEIGHT * 1));
        } else {
            $X = intval(rand(intval(round(self::WALLWIDTH * 0.33)), intval(round(self::WALLWIDTH * 0.76))));
            $Y = intval(rand(intval(round(self::WALLHEIGHT * 0.33)), intval(round(self::WALLHEIGHT * 0.76))));
        }

        $mCount = 1;
        foreach ($this->theme["colors"] as $color) {
            $rCount = intval($tCount - $mCount);

            if ($rCount < 0) {
                $S = intval(self::STACKMIN);
            } else {
                $multiplier   = "1." . $rCount;
                $multitwo     = "0." . $rCount;
                settype($multiplier, "float");
                settype($multitwo, "float");
                $S = intval((self::STACKMAX * $multiplier) + (self::STACKMAX * $multitwo));
            }

            if ($mCount === 1) {
                if ($this->DEBUG !== false)
                    TUI::Message("Shadow Rainbow", " Circle ");

                // shadow offset amount
                $soa = (count($this->theme["colors"]) > 5) ? 5 : 3;

                $this->drawColoredCircle($this->image, $this->palette, intval($X + $soa), intval($Y - $soa), $S, $this->theme["shadow"], $fill, $thickness);
                $this->drawColoredCircle($this->image, $this->palette, intval($X - floor($soa * 0.5)), intval($Y + (floor($soa * 0.5) - 1)), $S, "FFFFFF", $fill, $thickness);

                if ($this->DEBUG !== true)
                    $this->image->effects()->blur(6);
            }


            $this->drawColoredCircle($this->image, $this->palette, $X, $Y, $S, $color, true, 1);
            $mCount++;
        }

        // Make one last circle to finish off the rainbow
        $shadowS = intval(round($S * 0.78));
        $innerS  = intval(round($S * 0.77));
        $this->drawColoredCircle($this->image, $this->palette, intval($X + 2), intval($Y - 2), $shadowS, $this->theme["shadow"], true, 1);
        $this->drawColoredCircle($this->image, $this->palette, $X, $Y, $innerS, $this->theme["bg"], true, 1);


        // Cool Cloud Accents
        if ($this->theme["LoD"] !== "light" && $cloud === true) {
            $this->cloudMaker("A");
            $this->cloudMaker("B");
        } else if ($this->theme["LoD"] === "light" && $cloud === true) {
            $this->cloudMaker("A", true);
            $this->cloudMaker("B", true);
        }
    }

    public function cloudMaker($key, $inverse = false)
    {
        switch ($key) {
            case 'A':
                $mainS    = 280;
                $saS      = 210;
                $sbS      = 170;
                $sa2S     = false;
                $sb2S     = false;

                $mainX    = 360;
                $saX      = 230;
                $sbX      = 510;
                $sa2X     = false;
                $sb2X     = false;

                $mainY    = 310;
                $saY      = 340;
                $sbY      = 330;
                $sa2Y     = false;
                $sb2Y     = false;
                break;

            default:
                $mainS    = 270;
                $saS      = 160;
                $sbS      = 195;
                $sa2S     = 80;
                $sb2S     = false;

                $mainX    = 1560;
                $saX      = 1420;
                $sbX      = 1700;
                $sa2X     = 1460;
                $sb2X     = false;

                $mainY    = 233;
                $saY      = 250;
                $sbY      = 245;
                $sa2Y     = 150;
                $sb2Y     = false;
                break;
        }

        if ($inverse === false) {
            $cloudShadow = ColorPhreaks::getDarker("FFFFFF", 0.30);
            $cloudColor  = "FFFFFF";
        } else {
            $cloudShadow = ColorPhreaks::getDarker($this->theme["bg"], 0.25);
            $cloudColor  = ColorPhreaks::getDarker($this->theme["bg"], 0.20);
        }


        $this->drawColoredCircle($this->image, $this->palette, ($mainX - 15), ($mainY + 15), $mainS, $cloudShadow, true, 1);
        $this->drawColoredCircle($this->image, $this->palette, ($saX - 15), ($saY + 15), $saS, $cloudShadow, true, 1);
        $this->drawColoredCircle($this->image, $this->palette, ($sbX - 15), ($sbY + 15), $sbS, $cloudShadow, true, 1);

        if ($sa2S !== false)
            $this->drawColoredCircle($this->image, $this->palette, ($sa2X - 15), ($sa2Y + 15), $sa2S, $cloudShadow, true, 1);

        if ($sb2S !== false)
            $this->drawColoredCircle($this->image, $this->palette, ($sb2X - 15), ($sb2Y + 15), $sb2S, $cloudShadow, true, 1);


        if ($this->DEBUG !== false)
            TUI::Message("SIDE A2" . $sa2S, " Circle ");

        $this->drawColoredCircle($this->image, $this->palette, $mainX, $mainY, $mainS, $cloudColor, true, 1);
        $this->drawColoredCircle($this->image, $this->palette, $saX, $saY, $saS, $cloudColor, true, 1);
        $this->drawColoredCircle($this->image, $this->palette, $sbX, $sbY, $sbS, $cloudColor, true, 1);

        if ($sa2S !== false)
            $this->drawColoredCircle($this->image, $this->palette, $sa2X, $sa2Y, $sa2S, $cloudColor, true, 1);

        if ($sb2S !== false)
            $this->drawColoredCircle($this->image, $this->palette, $sb2X, $sb2Y, $sb2S, $cloudColor, true, 1);
    }

    /**
     * throwCircleParty
     *
     * relative (to height) size of ring groupings of varying amounts,
     * there is a 33% chance said group will be drawn at various sizes / fills
     *
     * @return void
     */
    public function throwCircleParty()
    {
        $colors = $this->theme["colors"];
        $sizes = array("lg" => 1, "md" => 3, "sm" => 6, "xs" => 4);

        $min = 0;
        $max = 2;

        while ($min < $max) {
            foreach ($sizes as $k => $limit) {

                $now = 0;
                while ($now < $limit) {
                    $CD = $this->relativeRingSize($k);

                    $rCoord = $this->randomCoordinate();

                    //$this->drawColoredCircle($this->image, $this->palette, $rCoord["x"], $rCoord["y"], $CD["size"], $color, $CD["fill"], $CD["line"]);
                    shuffle($colors);
                    $chance = mt_rand(1, 3);

                    if ($chance !== 2) {
                        if ($k === "xs")
                            $this->drawBouncyCircle($rCoord["x"], $rCoord["y"], $CD["size"], $colors[0], $CD["fill"], $CD["line"]);
                        else
                            $this->drawColoredCircle($this->image, $this->palette, $rCoord["x"], $rCoord["y"], $CD["size"], $colors[0], $CD["fill"], $CD["line"]);
                    }

                    $now++;
                }
            }
            $min++;
        }
    }

    public function relativeRingSize($key)
    {
        switch ($key) {
            case 'lg':
                $size = intval(rand(intval(round(self::WALLHEIGHT * 1.5)), intval(round(self::WALLHEIGHT * 1.75))));
                $thick = 20;
                $fill = false;
                break;
            case 'md':
                $size = intval(rand(intval(round(self::WALLHEIGHT * 0.66)), intval(round(self::WALLHEIGHT * 0.95))));
                $thick = 8;
                $fill = false;
                break;
            case 'sm':
                $size = intval(rand(intval(round(self::WALLHEIGHT * 0.20)), intval(round(self::WALLHEIGHT * 0.33))));
                $thick = 4;
                $fill = false;
                break;
            default:
                $size = intval(rand(intval(round(self::WALLHEIGHT * 0.03)), intval(round(self::WALLHEIGHT * 0.08))));
                $thick = 1;
                $fill = true;
                break;
        }

        return array("size" => $size, "line" => $thick, "fill" => $fill);
    }

    /**
     * Random tiny little circles scattered all over the place
     *
     * @param [type] $imagine
     * @param string $themeName
     * @param string $themeType
     * @return void
     */

    public function makeDots()
    {

        $colors = $this->theme["colors"];
        $rowLimit = 33;

        foreach ($colors as $color) {

            if ($this->DEBUG !== false)
                TUI::Message("Dots Color: " . $color, " Circle ");

            $rowCount = 0;

            while ($rowCount < $rowLimit) {
                $rCoord = $this->randomCoordinate();
                $size = intval(rand(13, 45));
                $this->drawColoredCircle($this->image, $this->palette, $rCoord["x"], $rCoord["y"], $size, $color);

                $rowCount++;
            }
        }
    }
}
