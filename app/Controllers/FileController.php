<?php

namespace App\Controllers;

/**
 * FileController | file system helper class
 *
 * TODO: this file need a lot of work cleaning up function names to camelCase.
 *
 * Date: July 16 2021
 */

use League\CLImate\CLImate;

// use SVG\SVG;

// require_once "./libraries/svg/svglib/svglib.php";
// require_once './libraries/svg/svglib/inkscape.php';

class FileController
{
    // Random helpful info........................
    // $path_parts = pathinfo($file);
    // $dir_name = $path_parts['dirname'];
    // $file_name = $path_parts['basename'];
    // $parent_dir = strrchr(dirname($file), '/');

    // PUBLIC VARIABLES
    // -------------------------------
    public static $cacheDir = "output/_cache";
    public static $URLValidator = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|(([^\s()<>]+|(([^\s()<>]+)))*))+(?:(([^\s()<>]+|(([^\s()<>]+)))*)|[^\s`!()[]{};:'\".,<>?«»“”‘’]))/";

    /**
     * saveJSONFile
     *
     * @param [array] $data
     * @param [string] $filename
     * @param [string] $category
     * @return string
     */
    public static function saveJSONFile($data, $filename, $category)
    {
        $result = '';

        if(is_array($data)) {
            $json = json_encode(
                $data,
                JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_HEX_QUOT
            );

            $result = 'output/' . $category . '/'. $filename . ".json";

            file_put_contents($result, $json);
        }

        return $result;
    }

    /**
     * A Different Attempt to get Shell Username
     */
    public static function shellUserName()
    {
        exec("whoami", $hostname);
        $host = trim(implode(" ", $hostname));

        return $host;
    }

    /**
     * shellUserData
     *
     * Grabs relevant user data
     *
     * @param  string $result found data key. name, dir, shell, uid etc
     * @return string
     */
    public static function shellUserData($result = "dir")
    {
        $uid = posix_getuid();
        $shell_user = posix_getpwuid($uid);

        //print_r($shell_user);
        // Array
        // (
        //     [name] => lucky
        //     [passwd] => x
        //     [uid] => 1000
        //     [gid] => 1000
        //     [gecos] => Lucky Se7enteen,,,
        //     [dir] => /home/lucky
        //     [shell] => /bin/bash
        // )

        // not owner of running script process but script file owner
        //$home_dir = posix_getpwuid(getmyuid())['dir'];

        return $shell_user[$result];
    }

    /**
     * localOrRemoteCheck
     *
     * Performs a check as to whether or not the string in question is a local file or a remote URL.
     * both options return a valid response.
     *
     * @param  string $address
     * @return bool
     */
    public static function localOrRemoteCheck($address)
    {
        $result = false;

        // Check for valid url or if file exists on the local file system
        if (is_file($address) || preg_match(self::$URLValidator, $address)) {
            $result = true;
        }

        return $result;
    }

    public static function localOrRemoteLoad($address)
    {
        $result = ["data" => false, "json" => false];

        if (is_file($address)) {
            $result["data"] = file_get_contents($address);

            if (self::isJson($result["data"])) {
                $result["json"] = json_decode($result["data"], true);
            }
        } else {
            // If URL Download URL
            if (
                preg_match(
                    "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|(([^\s()<>]+|(([^\s()<>]+)))*))+(?:(([^\s()<>]+|(([^\s()<>]+)))*)|[^\s`!()[]{};:'\".,<>?«»“”‘’]))/",
                    $address
                )
            ) {
                // parsed path
                $path = parse_url($address, PHP_URL_PATH);
                // extracted basename
                $filename = basename($path["path"]);
                $destination = base_path(self::$cacheDir . "/" . $filename);

                self::remoteDownload($address, $destination);

                $result["data"] = file_get_contents($destination);

                if (self::isJson($result["data"])) {
                    $result["json"] = json_decode($result["data"], true);
                }
            }
        }

        return $result;
    }

    /**
     * remoteDownload
     *
     * @param  string $url example http://path/toserver/filename
     * @param  string $destination example uploads/filename
     * @return void
     */
    public static function remoteDownload($url, $destination)
    {
        // Old School way of downloading a file
        // ------------------------------------

        $fp = fopen($destination, "w+");
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);

        fclose($fp);
    }

    public static function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * randomTxtString
     * @abstract Helper function for random filenames
     * @param integer $limit
     * @return void
     */
    public static function randomTxtString($limit = 5)
    {
        $str = "aAcCeEfFgGhHkKpPqQrRtTwWxXyYzZ234678";
        $w_name = substr(str_shuffle($str), 0, $limit);

        return $w_name;
    }

    public static function randomNumberString($limit = 5)
    {
        $n1 = rand(100, 1000);
        $n2 = rand(10000, 99999);
        $n3 = rand(1, 10);

        $randomNumber = ceil($n1 / $n3) * $limit + floor($n2 * $n3);

        return $randomNumber;
    }

    public static function newMarkdown($filename, $content = "")
    {
        $file = fopen($filename, "w");
        fwrite($file, $content);
        fclose($file);

        if (is_file($filename)) {
            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * dir_if_none
     *
     * @param string $path
     * @return bool
     */
    public static function dir_if_none($path)
    {
        // If file, or folder doesnt exist, make it.
        if (!file_exists($path) || !is_dir($path)) {
            mkdir($path, 0777, true);
            return true;
        }

        return false;
    }

    /**
     * rem_tail
     *
     * @param string $string
     * @return string
     */
    public static function rem_tail($string)
    {
        $noSlash = rtrim($string, "/\\");
        return $noSlash;
    }

    public static function remove_file($file_pointer)
    {
        // Use unlink() function to delete a file
        if (!unlink($file_pointer)) {
            TUI::Message(
                $file_pointer . " cannot be deleted due to an error",
                "WARN"
            );
        } else {
            TUI::Message($file_pointer . " has been deleted", "INFO");
        }
    }

    /**
     * expand_tilde
     *
     * @param string $path
     * @return string
     */
    public static function expand_tilde($path)
    {
        if (function_exists("posix_getuid") && strpos($path, "~") !== false) {
            $info = posix_getpwuid(posix_getuid());
            $path = str_replace("~", $info["dir"], $path);
        }

        return $path;
    }

    /**
     * recursive_copy
     *
     * @param string $source
     * @param string $dest
     * @return bool
     */
    public static function recursive_copy($source, $dest)
    {
        self::dir_if_none($dest);

        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $source,
                    \RecursiveDirectoryIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::SELF_FIRST
            )
            as $item
        ) {
            if ($item->isDir()) {
                mkdir(
                    $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName()
                );
            } else {
                copy(
                    $item,
                    $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName()
                );
            }
        }

        return true;
    }

    /**
     * recursive_remove
     *
     * @param string $directory
     * @return bool
     */
    public static function recursive_remove($directory)
    {
        foreach (glob("{$directory}/*") as $file) {
            if (is_dir($file)) {
                self::recursive_remove($file);
            } elseif (!is_link($file)) {
                unlink($file);
            }
        }

        rmdir($directory);

        if (!file_exists($directory) && !is_dir($directory)) {
            return true;
        } else {
            return false;
        }
    }

    public static function new_dir_overcheckconfirm($newDir, $path)
    {
        $climate = new CLImate();

        // If New Dir is set, we first do a recursive copy, then change the new icons colors.

        $newDir = self::rem_tail($newDir);

        if (strpos($newDir, "~") !== false) {
            $newDir = self::expand_tilde($newDir);
        }

        if (!file_exists($newDir) && !is_dir($newDir)) {
            self::recursive_copy($path, $newDir);
        } else {
            $climate
                ->br()
                ->red()
                ->bold()
                ->out(" ERROR! ");
            $climate
                ->red()
                ->out(" " . $newDir . " already exists!")
                ->br();
            $input = $climate->confirm(
                " Replace NEW directory with fresh copy?"
            );
            // Continue? [y/n]
            if ($input->confirmed()) {
                // Do your thing here
                self::recursive_remove($newDir);
                sleep(3);
                self::recursive_copy($path, $newDir);
                sleep(1);
            } else {
                // Don't do your thing
                $climate
                    ->br()
                    ->br()
                    ->bold()
                    ->red()
                    ->out(
                        " Cannot continue, remove following directory and retry."
                    );
                $climate
                    ->red()
                    ->out($newDir)
                    ->br()
                    ->br();
                exit();
            }
        }

        return $newDir;
    }

    //  Helper for getting relative directory of files as array
    public static function directoryToArray(
        $dir,
        $typesString = "yaml,yml,YAML,YML"
    ) {
        $files = [];
        $total = 0;
        foreach (
            glob("./" . $dir . "/*.{" . $typesString . "}", GLOB_BRACE)
            as $filename
        ) {
            $files[] = $filename;
            $total++;
        }

        return $files;
    }

    // Print KB MB etc
    public static function humanFilesize($bytes, $dec = 0)
    {
        settype($bytes, "string");
        $size = ["B", "kB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) .
            @$size[$factor];
    }

    // Compute size of directory
    public static function folderSize($dir)
    {
        $size = 0;

        foreach (glob(rtrim($dir, "/") . "/*", GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : self::folderSize($each);
        }

        return $size;
    }

    public static function folderItemCount($dir)
    {
        $total = 0;

        foreach (glob(rtrim($dir, "/") . "/*", GLOB_NOSORT) as $each) {
            $total += is_file($each) ? 1 : 0;
        }

        return $total;
    }

    /**
     * cleanFilename
     * Cleans up a given filename string
     *
     * @param string $filename
     * @param string $beautify
     * @return void
     */
    public static function cleanFilename($filename, $beautify = true)
    {
        // sanitize filename
        $filename = preg_replace(
            '~
        [<>:"/\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x',
            "-",
            $filename
        );
        // avoids ".", ".." or ".hiddenFiles"
        $filename = ltrim($filename, ".-");
        // optional beautification
        if ($beautify) {
            $filename = self::beautify_filename($filename);
        }
        // maximize filename length to 255 bytes http://serverfault.com/a/9548/44086
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename =
            mb_strcut(
                pathinfo($filename, PATHINFO_FILENAME),
                0,
                255 - ($ext ? strlen($ext) + 1 : 0),
                mb_detect_encoding($filename)
            ) . ($ext ? "." . $ext : "");
        return $filename;
    }

    /**
     * beautify_filename
     * optional function called when cleanFilename is used
     *
     * @param string $filename
     * @return void
     */
    public static function beautify_filename($filename)
    {
        // reduce consecutive characters
        $filename = preg_replace(
            [
                // "file   name.zip" becomes "file-name.zip"
                "/ +/",
                // "file___name.zip" becomes "file-name.zip"
                "/_+/",
                // "file---name.zip" becomes "file-name.zip"
                "/-+/",
            ],
            "-",
            $filename
        );

        $filename = preg_replace(
            [
                // "file--.--.-.--name.zip" becomes "file.name.zip"
                "/-*\.-*/",
                // "file...name..zip" becomes "file.name.zip"
                "/\.{2,}/",
            ],
            ".",
            $filename
        );

        $unwanteds = [
            "iconfinder-" => "",
            "iconfinder_" => "",
            "si-glyph-" => "glyph-",
            "icon" => "",
            "/^[0-9]+\-/" => "",
        ];

        foreach ($unwanteds as $prefix => $replacement) {
            $filename = preg_replace(
                "/^" . preg_quote($prefix, "/") . "/",
                $replacement,
                $filename
            );
        }

        // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
        $filename = mb_strtolower($filename, mb_detect_encoding($filename));

        // ".file-name.-" becomes "file-name"
        $filename = trim($filename, ".-");

        return $filename;
    }

    public static function random_filename()
    {
        date_default_timezone_set("UTC");
        $date = date("H:i:s");
        $string = self::readable_random_string();
        $final = $string . "_" . $date;

        return $final;
    }

    private static function readable_random_string($length = 6)
    {
        $string = "";
        $vowels = ["a", "e", "i", "o", "u"];
        $consonants = [
            "b",
            "c",
            "d",
            "f",
            "g",
            "h",
            "k",
            "m",
            "n",
            "p",
            "r",
            "s",
            "t",
            "v",
            "w",
            "x",
            "y",
            "z",
        ];

        $max = round($length / 2);
        for ($i = 1; $i <= $max; $i++) {
            $string .= $consonants[rand(0, 17)];
            $string .= $vowels[rand(0, 4)];
        }

        return $string;
    }

    /**
     * batchRename
     * Uses filename helpers to rename all files in a given directory
     * End result is *hopefully* sanitized friendly filenames
     *
     * @param string $findString
     * @param string $newDir
     * @return void
     */
    public static function batchRename($filesArray, $iconCategory, $baseDir)
    {
        $total = count($filesArray);

        $climate = new CLImate();
        $climate->style->addCommand("holler", ["cyan", "bold"]);
        $climate->style->addCommand("pos", ["green", "bold"]);
        $climate->style->addCommand("neg", ["red", "bold"]);
        $climate->holler(" [RENAME]" . $total . " files.");

        foreach ($filesArray as $file) {
            // Sanitize the name
            $newName = self::cleanFilename($file["name"]);

            $newfile =
                $baseDir .
                DIRECTORY_SEPARATOR .
                $iconCategory .
                DIRECTORY_SEPARATOR .
                $newName;
            $origFile = $baseDir . DIRECTORY_SEPARATOR . $file["path"];

            if ($newName !== $file["name"]) {
                if (!rename($origFile, $newfile)) {
                    $climate->neg(" Failed to rename " . $file);
                } else {
                    $climate->pos(" Renamed to: " . $newfile);
                }
            } else {
                $climate->cyan()->out(" " . $file["name"] . " untouched");
            }
        }

        return true;
    }

    /**
     * camelCase
     *
     * @param  string $str string that will be transformed
     * @param  mixed $noStrip
     * @return string $str camelCase result
     */
    public static function camelCase($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace(
            "/[^a-z0-9" . implode("", $noStrip) . "]+/i",
            " ",
            $str
        );
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);
        $str = lcfirst($str);

        return $str;
    }
}
