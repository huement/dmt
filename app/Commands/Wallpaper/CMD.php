<?php

namespace App\Commands\Wallpaper;

use Imagine\Image;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Box;
use Imagine\Image\Point;

use claviska\SimpleImage;
use League\CLImate\CLImate;

use App\Controllers\PaletteController;
use App\Controllers\SixteenController;
use App\Controllers\WallpaperController;
use App\Controllers\ColorController;
use App\Controllers\FileController;

use App\Console\TUI;

/**
 * CMD
 *
 * Functions specific to generating CMD Wallpaper Graphics. Including:
 *
 *  + Generating Wallpaper Thumbnails
 *  + Applying texture overlay effects
 *  + Saving finished Wallpaper
 *  + Creating textures & overlays
 *
 */
class CMD
{

    const OUTDIR            = 'output/wallpapers/';
    const THUMBDIR          = 'output/wallpapers/_thumbs/';
    const OVERLAYDIR        = "resources/images/overlays/";
    const RAWOVERLAYS       = "resources/images/overlays_raw/";
    const PAINTEMPDIR       = "resources/images/paint_splatters/";
    const PALETTEFILEDIR    = 'resources/palettes/base16/';
    const WALLWIDTH         = 1920;
    const WALLHEIGHT        = 1080;
    const THUMBWIDTH        = 600;
    const THUMBHEIGHT       = 400;
    const GRUNGEOPAC        = 0.15;

    public static $DEBUG    = true;

    // ---------------------------------------------------------------------------
    // HELPER FUNCTIONS
    // ---------------------------------------------------------------------------

    public static function wallpaperFolderCheck()
    {
        $saveDirs = [self::OUTDIR, self::THUMBDIR];

        foreach ($saveDirs as $saveDir) {
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                die('Failed to create folder ' . $saveDir . '...');
            }
        }

        return true;
    }

    public static function generateGrid($width, $height, $squareSize)
    {
        $totalX = intval(round($width / $squareSize));
        $totalY = intval(round($height / $squareSize));
    }

    // ---------------------------------------------------------------------------
    // MOVEMENTS + X / Y GRIDS
    // ---------------------------------------------------------------------------

    public static function battleShipGrid($w, $h, $size)
    {
        $totX = intval(floor($w / $size));
        $totY = intval(floor($h / $size));

        $xRanges = [];
        $yRanges = [];

        $xLimit = 0;
        while ($xLimit <= $totX) {
            $start = intval($xLimit * $size);
            $end = intval(($start + $size) - 1);

            if ($end > $w) {
                $end = $w;
            }

            $xRanges[] = range($start, $end);

            $xLimit++;
        }

        $yLimit = 0;
        while ($yLimit <= $totY) {
            $start = intval($yLimit * $size);
            $end = intval(($start + $size) - 1);

            if ($end > $h) {
                $end = $h;
            }

            $yRanges[] = range($start, $end);

            $yLimit++;
        }

        return ["x" => $xRanges, "y" => $yRanges, "xTotal" => $totX, "yTotal" => $totY];
    }

    public static function searchPoint($xTest, $yTest, $xRanges, $yRanges)
    {
        $xResult = self::searchSquare($xTest, $xRanges);
        $yResult = self::searchSquare($yTest, $yRanges);

        return ["x" => $xResult, "y" => $yResult];
    }

    public static function searchSquare($needle, $haystack)
    {
        $found = false;
        $current = 0;
        while ($found === false) {
            $x = $haystack[$current];
            if (in_array($needle, $x)) {
                $found = $current;
            }

            $current++;
            if ($current > count($haystack)) {
                $found = "not_found";
            }
        }

        if ($found !== "not_found") {
            $result = $haystack[$found];
        } else {
            return false;
        }

        $min = $result[0];
        $max = end($result);

        return ["key" => $found, "min" => $min, "max" => $max, "items" => $result];
    }

    public static function wpGrid($width, $height)
    {
        $x_letters = ["A", "B", "C", "D"];
        $split = intval(round($width * 0.5));
        $secsp = intval(round($split * 0.5));

        $results = [];
        $results["A"] = range(0, round($secsp - 1));
        $results["B"] = range($secsp, round($split - 1));
        $results["C"] = range($split, round(($split + $secsp) - 1));
        $results["D"] = range(($split + $secsp), $width);

        $y_letters = ["A", "B", "C"];
        $third = intval(round($height * 0.333));

        $results_y = [];
        $results_y["A"] = range(0, round($third - 1));
        $results_y["B"] = range($third, round(($third * 2) - 1));
        $results_y["C"] = range(round($third * 2), $height);

        $ending = [];
        $ending["X"] = ["letters" => $x_letters, "values" => $results];
        $ending["Y"] = ["letters" => $y_letters, "values" => $results_y];

        return $ending;
    }


    // ---------------------------------------------------------------------------
    // SETUP + INITIALIZE ETC
    // ---------------------------------------------------------------------------

    public static function newWallpaper($themeType, $themeName, $shuffle)
    {
        $results = [];

        // Get the colors
        if ($themeType === "palette") {
            $allColors = WallpaperController::setupColors($themeType, $shuffle, $themeName);
            TUI::Speaks(count($allColors));
        } else {
            $allColors = SixteenController::spectrumSorter($themeName, $shuffle);
        }



        $LoDData = ColorController::lightOrDark($allColors["background"]);
        $LoD     = $LoDData["LightOrDark"];

        $results["colors"]    = $allColors["colors"];
        $results["bg"]        = $allColors["background"];
        $results["shadow"]    = ColorController::getDarker($allColors["background"], 0.25);
        $results["tc"]        = count($allColors["colors"]);
        $results["themeName"] = $allColors["theme"];
        $results["themeType"] = $themeType;
        $results["shuffle"]   = $shuffle;
        $results["LoD"]       = $LoD;

        if (isset($allColors["grays"])) {
            $results["grays"] = $allColors["grays"];
        }

        return $results;
    }

    public static function makeBlank($image)
    {
        $image->fromNew(self::WALLWIDTH, self::WALLHEIGHT);
        return $image;
    }


    // ---------------------------------------------------------------------------
    // SAVE RESULTING IMAGE
    // ---------------------------------------------------------------------------

    // General Save & Thumbnail Function
    public static function saveRasterWallpaper($wallpaperImage, $nameString, $saveDir = self::OUTDIR, $skip = false, $forceName = false)
    {
        if ($forceName === false) {
            $w_name = FileController::randomTxtString(3);
            $f_name = $nameString . $w_name . '.png';
        } else {
            $f_name = $nameString . ".png";
        }

        self::wallpaperFolderCheck();

        $wallpaperImage->toFile($saveDir . $f_name, 'image/png');

        $thumbFunc = self::wallThumbnail($saveDir . $f_name, self::THUMBWIDTH, self::THUMBHEIGHT, self::THUMBDIR . $f_name);

        if ($skip === false) {
            TUI::cliImgDisplay(self::THUMBDIR . $f_name);
        }

        return ["image" => $saveDir . $f_name, "thumbnail" => self::THUMBDIR . $f_name];
    }

    /**
     * General Save & Thumbnail Function
     *
     * Formerly saveCircleWallpaper
     *
     * @param [type] $wallpaperImage
     * @param [type] $nameString
     * @return void
     */
    public static function saveWallpaperImg($wallpaperImage, $nameString, $texture = false)
    {
        $w_name = FileController::randomTxtString(5);
        $f_name = $nameString . $w_name . '.png';

        self::wallpaperFolderCheck();

        $wallpaperImage->save(self::OUTDIR . $f_name);

        self::wallThumbnail(self::OUTDIR . $f_name, false, false, self::THUMBDIR . $f_name);

        TUI::cliImgDisplay(self::THUMBDIR . $f_name);

        return ["image" => self::OUTDIR . $f_name, "thumbnail" => self::THUMBDIR . $f_name];
    }

    public static function applyTexture($imgFile, $texture = true)
    {
        //$result =  base_path($imgFile);

        $quality = (self::$DEBUG !== true) ? 100 : 50;

        TUI::Message("Adding Texture Overlay", "Wallpaper");

        // Texture is given image or random file
        if ($texture === true) {
            $sendData = true;
        } else {
            $sendData = $texture;
        }

        // Load requested texture file (or random file)
        //$textureFile = new CMD();
        $overlay     = self::makeRandomTextureOverlay($sendData);

        $image = new \claviska\SimpleImage();
        $image
            ->fromFile($imgFile)                          // load image.jpg
            ->autoOrient()                                // adjust orientation based on exif data
            ->overlay($overlay, "center", 0.15)           // overlay texture file
            ->toFile($imgFile, 'image/png', $quality);    // save to file

        return $imgFile;
    }

    public static function saveDataURI($wallpaperImage)
    {
        $result = $wallpaperImage->toDataUri();
        return ["image" => $result];
    }

    public static function wallThumbnail($wall, $wallWidth = false, $wallHeight = false, $thumbName = "thumb.png")
    {
        $wallWidth  = ($wallWidth !== false ? $wallWidth : self::THUMBWIDTH);
        $wallHeight = ($wallHeight !== false ? $wallHeight : self::THUMBHEIGHT);

        $image = new \claviska\SimpleImage();

        // Magic! ✨
        $image
            ->fromFile($wall)                         // load image.jpg
            ->autoOrient()                            // adjust orientation based on exif data
            ->thumbnail($wallWidth, $wallHeight, 'center')   // resize to half size
            ->toFile($thumbName, 'image/png', 50);   // save to file

        if (file_exists($thumbName)) {
            return true;
        } else {
            return false;
        }
    }


    // ---------------------------------------------------------------------------
    // TEXTURE OVERLAY FUNCTIONS
    // ---------------------------------------------------------------------------

    /**
     * makeGrunge
     *
     * @param Imagine::object $image
     * @return array of arrays containing created wallpaper paths
     *
     * @todo figure out a method for what wallpapers will get the grunge overlay
     * @todo add an option for only applying certain grunge overlays
     *
     */
    public static function makeGrunge($image)
    {
        $finalArray = [];

        $walls = ["rr_F4RaW.png", "dots_YQ6fTz.png", "circleRowRandom_Wa3FQE.png", "circles_A8agWC.png", "circleBB_KXxt6y.png", "circleStacked_QHrwKR.png", "circleComp_aexfEz.png", "circleRow_xPTkYZ.png"];

        $textures = [];
        foreach (glob(self::OVERLAYDIR . "*.{png,PNG}", GLOB_BRACE) as $filename) {
            $textures[] = $filename;
        }

        foreach ($walls as $wall) {
            foreach ($textures as $texture) {
                $bname = basename($texture, "png");
                $wname = basename($wall, "png");
                $image
                    ->fromFile(self::OUTDIR . $wall)
                    ->autoOrient()
                    ->overlay($texture, "center", self::GRUNGEOPAC);

                $finalArray[] = self::saveRasterWallpaper($image, $wname . "_" . $bname);
            }
        }

        return $finalArray;
    }

    public static function rasterGrungeGen()
    {
        $image = new SimpleImage();
        $result = self::makeGrunge($image);
        return $result;
    }

    public static function loadTextures()
    {
        $textures = [];

        foreach (glob(self::OVERLAYDIR . "*.{png,PNG,jpg,JPG,jpeg,JPEG}", GLOB_BRACE) as $filename) {
            $textures[] = $filename;
            //Message("File: " . $filename);
        }

        shuffle($textures);
        $random_texture = $textures[0];

        return ["files" => $textures, "random" => $random_texture];
    }

    /**
     * makeReady
     * Apply any filter effects to textures to make them ready to use
     *
     * @param [type] $image
     * @return void
     */
    public static function makeOverlays($image)
    {
        if (!is_dir(self::OVERLAYDIR)) {
            if (!mkdir(self::OVERLAYDIR, 0777, true)) {
                die('Failed to create folders...');
            }
        }

        $dir = self::RAWOVERLAYS;
        $typeString = "jpg,jpeg";
        $textures = FileController::directoryToArray($dir, $typeString);

        // Run the loop
        foreach ($textures as $img) {

            $bname = basename($img);

            if (file_exists(self::OVERLAYDIR . $bname)) {
                TUI::Message("Already Processed: " . $bname, "WARN");
            } else {
                // Black and WHite Mode
                TUI::Message("Creating Overlay: " . $bname, "Wallpaper");
                $image
                    ->fromFile($img)
                    ->resize(1920, 1080)
                    ->desaturate()
                    ->sketch()
                    ->toFile(self::OVERLAYDIR . $bname, 'image/png');
            }
        }

        TUI::Message("Finished!", "Wallpaper");
    }

    public static function makeRandomTextureOverlay($filename)
    {
        if ($filename === true) {
            $textures = self::loadTextures();
            $file = $textures["random"];
            TUI::Message("Random Texture: " . $file, "Wallpaper");
        } else {
            $file = $filename;
            TUI::Message("Fixed Texture: " . $file, "Wallpaper");
        }

        $image = new \claviska\SimpleImage();
        $image->fromFile($file);

        $chance = mt_rand(0, 4);

        if ($chance === 1) {
            $image->flip("x");
        }
        if ($chance === 2) {
            $image->flip("y");
        }
        if ($chance === 0) {
            $image->flip("both");
        }

        return $image;
    }
}
