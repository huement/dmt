<?php

/**
 *
 *  @abstract This file controls the generating of all the various wallpapers.
 *  @author Derek Scott <derek@huement.com>
 *
 */

declare(strict_types=1);

namespace App\Commands\Wallpaper;

use App\Commands\Phreaks\TUI;
use App\Commands\Phreaks\FilePhreaks;
use App\Commands\Phreaks\WallpaperPhreaks;

use Imagine\Imagick;
use Imagine\Imagick\Imagine;

class Generator
{

    public $imagine;
    public $simpleImage;

    public function __construct()
    {
        $this->imagine     = new Imagine();
        $this->simpleImage = new \claviska\SimpleImage();
    }

    public function paintGen($theme = false, $themeType = false, $shuffle = false, $texture = false)
    {
        $GenWall = new Modern();
        $result = $GenWall->makePaintWallpaper();
        return $result;
    }

    public function caveGen($theme = false, $themeType = false, $shuffle = false, $texture = false, $fillScreen = false)
    {
        $GenWall = new Rectangle();
        $result = $GenWall->makeCaveOfColors($this->simpleImage, $theme, $themeType, $fillScreen, $shuffle, $texture);
        return $result;
    }
    public function roundRectGen($theme = false, $themeType = false, $shuffle = false, $texture = false, $flow = false)
    {
        $GenWall = new Rectangle();
        $result = $GenWall->makeRectangle($this->simpleImage, $theme, $themeType, $flow, $shuffle, $texture);
        return $result;
    }

    public function specRectGen($theme = false, $themeType = false, $shuffle = false, $texture = false, $fullOrHalf = true)
    {
        $GenWall = new Rectangle();
        $result = $GenWall->makeSpecRectangle($this->simpleImage, $theme, $themeType, $fullOrHalf, $shuffle, $texture);
        return $result;
    }

    /**
     * Special Case Spectrum Image Generation
     *
     * @param string $theme the name of the Base16 theme
     * @return array $result the image & thumbnail path
     */
    public function spectrumGen($theme)
    {
        $GenWall = new Rectangle();
        $result = $GenWall->makeSpectrum($this->simpleImage, $theme);
        return $result;
    }

    public function coolCircles($themeName = false, $themeType = false, $shuffle = false, $texture = false)
    {
        $GenWall = new Circle($this->imagine, $themeName, $themeType, $shuffle, $texture);

        $GenWall->coolCompounder($GenWall->params);

        $cwt = $GenWall->theme["themeName"] . "_coolie-o-s_";
        $result = CMD::saveWallpaperImg($GenWall->image, $cwt, $GenWall->texture);

        return $result;
    }

    // TODO: ADJUST SIZE OF CIRCLES
    // TODO: ADD IN FILLED CENTER
    public function rainbowStack($themeName = false, $themeType = false, $shuffle = false, $texture = false, $params = false)
    {
        $position = $params[0];
        $clouds   = $params[1];

        // Rainbow is never shuffled.
        $GenWall = new Circle($this->imagine, $themeName, $themeType, false, $texture);
        $GenWall->makeRainbow($position, $clouds);

        $cwt = ($position !== false) ? "_rainbow_clouds_" : "_rainbow_rings_";
        $result = CMD::saveWallpaperImg($GenWall->image, $GenWall->theme["themeName"] . $cwt, $GenWall->texture);

        return $result;
    }

    public function circlePartyGen($themeName = false, $themeType = false, $shuffle = false, $texture = false, $params = false)
    {
        $GenWall = new Circle($this->imagine, $themeName, $themeType, $shuffle, $texture);
        $GenWall->throwCircleParty();

        $cwt = $GenWall->theme["themeName"] . "_circle-party_";
        $result = CMD::saveWallpaperImg($GenWall->image, $cwt, $GenWall->texture);

        return $result;
    }

    public function dotsGen($themeName = false, $themeType = false, $shuffle = false, $texture = false)
    {
        $GenWall = new Circle($this->imagine, $themeName, $themeType, $shuffle, $texture);
        $GenWall->makeDots();

        $cwt = $GenWall->theme["themeName"] . "_dots_";
        $result = CMD::saveWallpaperImg($GenWall->image, $cwt, $GenWall->texture);

        return $result;
    }

    public function rowsOfCircleGen($themeName = false, $themeType = false, $shuffle = false, $texture = false, $mode = "random")
    {
        $GenWall = new Circle($this->imagine, $themeName, $themeType, $shuffle, $texture);
        $GenWall->makeCircleRows($mode);

        $cwt =  ($mode === "random") ? "_rand-o_" : "_grid-o_";
        $result = CMD::saveWallpaperImg($GenWall->image, $GenWall->theme["themeName"] . $cwt, $GenWall->texture);

        return $result;
    }

    public function inlineCircleGen($themeName = false, $themeType = false, $shuffle = false, $texture = false)
    {
        $GenWall = new Circle($this->imagine, $themeName, $themeType, $shuffle, $texture);
        $GenWall->makeCircleInline();

        $cwt = $GenWall->theme["themeName"] . "_o-rows_";
        $result = CMD::saveWallpaperImg($GenWall->image, $cwt, $GenWall->texture);

        return $result;
    }
}
