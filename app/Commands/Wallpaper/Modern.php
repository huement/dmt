<?php

namespace App\Commands\Wallpaper;

use League\CLImate\CLImate;
use Pixeler\Pixeler;
use Dallgoot\Yaml;
use claviska\SimpleImage;

use App\Commands\Phreaks\TUI;

use App\Commands\Phreaks\FilePhreaks;
use App\Commands\Phreaks\WallpaperPhreaks;

use App\Commands\Wallpaper\Actions;
use App\Commands\Wallpaper\Raster;

class Modern
{

    const PAINTEMPDIR  = "resources/images/paint_splatters/";
    const WALLWIDTH    = 1920;
    const WALLHEIGHT   = 1080;
    const THUMBWIDTH   = 800;
    const THUMBHEIGHT  = 600;

    public function loadPaints()
    {
        $textures = array();

        foreach (glob(self::PAINTEMPDIR . "*.{png,PNG}", GLOB_BRACE) as $filename) {
            $textures[] = $filename;
        }

        shuffle($textures);

        return array("files" => $textures);
    }

    public function makePaintWallpaper()
    {
        $paints = $this->loadPaints();

        $allColors = WallpaperPhreaks::setupColors("base16", true);
        $bgColor = $allColors["background"];

        $paintImages = array();
        $sC = 0;

        $Raster = new Raster;

        foreach ($allColors["colors"] as $color) {
            $img = $paints["files"][$sC];
            $image = new \claviska\SimpleImage();
            $image
                ->fromFile($img)
                ->colorize($color);


            $results = CMD::saveDataURI($image);
            $paintImages[] = $results["image"];
            $sC++;
        }

        $pC = 0;
        $lastPaintImg = "";
        foreach ($paintImages as $pI) {
            $imageX = new \claviska\SimpleImage();
            if ($pC === 0) {
                $imageX
                    ->fromNew(self::WALLWIDTH, self::WALLHEIGHT);
            } else {
                $imageX->fromDataUri($lastPaintImg);
            }

            $imageX->overlay($pI, 'center center');
            $paintStep = CMD::saveDataURI($imageX);
            $lastPaintImg = $paintStep["image"];
            $pC++;
        }

        TUI::Speaks("Final Step. Loading Image...");
        $imageZ = new \claviska\SimpleImage();
        $imageZ->fromDataUri($lastPaintImg);

        $imageY = new \claviska\SimpleImage();
        $imageY->fromNew(self::WALLWIDTH, self::WALLHEIGHT, $bgColor);
        $imageY->overlay($imageZ, 'center center');

        $paintStep = CMD::saveRasterWallpaper($imageY, "paints_" . $allColors["theme"] . "_");
        return $paintStep;
    }
}
