<?php

//namespace Todstoychev\Wsc;

/**
 * Wsc
 *
 * @author Todor Todorov <todstoychev@gmail.com>
 * @package Todstoychev\Wsc;
 */
class Wsc
{

    /**
     * Returns color codes array. If $rgb is set to false returns codes in hex format:
     * @example
     * [
     *  000000, 003300, 000033, ...
     * ]
     *
     * If $rgb is set to true returns codes in rgb format:
     * @example
     * [
     *  ['r' => 0, 'g' => 0, 'b' => 0],
     *  ['r' => 0, 'g' => 51, 'b' => 0],
     *  ['r' => 0, 'g' => 0, 'b' => 51],
     *  ...
     * ]
     *
     * @param boolean $rgb
     *
     * @return array
     */
    public static function getColors($rgb = false)
    {
        $vals = [0, 51, 102, 153, 204, 255];

        if ($rgb) {
            return self::createRgbArray($vals);
        }

        return self::createHexArray($vals);
    }

    /**
     * Converts rgb codes to hex
     *
     * @param int $r Red value
     * @param int $g Green value
     * @param int $b Blue value
     *
     * @return string
     */
    private static function rgbToHex($r, $g, $b)
    {
        $hex = '';
        $hex .= str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

        return $hex;
    }

    /**
     * Creates rgb codes array
     *
     * @example
     * [
     *  ['r' => 0, 'g' => 0, 'b' => 0],
     *  ['r' => 0, 'g' => 51, 'b' => 0],
     *  ['r' => 0, 'g' => 0, 'b' => 51],
     *  ...
     * ]
     *
     * @param array $vals Initial values
     *
     * @return array
     */
    private static function createRgbArray(array $vals)
    {
        foreach ($vals as $r) {
            foreach ($vals as $g) {
                foreach ($vals as $b) {
                    $colors[] = ['r' => $r, 'g' => $g, 'b' => $b];
                }
            }
        }

        return $colors;
    }

    /**
     * Creates hex values array
     *
     * @example
     * [
     *  000000, 003300, 000033, ...
     * ]
     *
     * @param array $vals initial values
     *
     * @return array
     */
    private static function createHexArray(array $vals)
    {
        foreach ($vals as $r) {
            foreach ($vals as $g) {
                foreach ($vals as $b) {
                    $colors[] = self::rgbToHex($r, $g, $b);
                }
            }
        }

        return $colors;
    }
}
