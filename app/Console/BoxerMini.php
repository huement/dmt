<?php

namespace App\Console;

use Codedungeon\PHPMessenger\Facades\Messenger;

/**
 * WidgetBoxer
 *
 * Creates ASCII/Unicode boxes around text.
 * @forked from work by Martin Boudreau
 */


class BoxerMini
{
    const ALIGN_LEFT = 0;
    const ALIGN_RIGHT = 1;
    const ALIGN_CENTER = 2;
    const ALIGN_JUSTIFY = 3;
    const ALIGN_JUSTIFY_FORCED = 4;
    const EOL_WINDOWS = "\r\n";
    const EOL_MAC = "\r";
    const EOL_UNIX = PHP_EOL;
    const EOL_OTHER = "\n\r";

    /** @var array - The types of boxes available */
    static private $boxes = [
        'block_light' => [
            '░', '░', '░', '░',
            '░', ' ', '░', '░',
            '░', ' ', '░', '░',
            '░', '░', '░', '░',
        ],
        'block_medium' => [
            '▒', '▒', '▒', '▒',
            '▒', ' ', '▒', '▒',
            '▒', ' ', '▒', '▒',
            '▒', '▒', '▒', '▒',
        ],
        'block_dark' => [
            '▓', '▓', '▓', '▓',
            '▓', ' ', '▓', '▓',
            '▓', ' ', '▓', '▓',
            '▓', '▓', '▓', '▓',
        ],
        'block_black' => [
            '█', '█', '█', '█',
            '█', ' ', '█', '█',
            '█', ' ', '█', '█',
            '█', '█', '█', '█',
        ],
        'dash' => [
            '+', '-', '+', '+',
            '¦', ' ', '¦', '¦',
            '¦', ' ', '¦', '¦',
            '+', '-', '+', '+',
        ],
        'line_single' => [
            '┌', '─', '┬', '┐',
            '│', ' ', '│', '│',
            '│', ' ', '│', '│',
            '└', '─', '┴', '┘',
        ],
        'line_double' => [
            '╔', '═', '╦', '╗',
            '║', ' ', '║', '║',
            '║', ' ', '║', '║',
            '╚', '═', '╩', '╝',
        ],
        'line_single_double' => [
            '┌', '─', '╥', '┐',
            '│', ' ', '║', '│',
            '│', ' ', '║', '│',
            '└', '─', '╨', '┘',
        ],
        'line_double_single' => [
            '╔', '═', '╤', '╗',
            '║', ' ', '│', '║',
            '║', ' ', '│', '║',
            '╚', '═', '╧', '╝',
        ],
        'line_thick' => [
            '┏', '━', '┳', '┓',
            '┃', ' ', '┃', '┃',
            '┃', ' ', '┃', '┃',
            '┗', '━', '┻', '┛',
        ],
        'line_single_round' => [
            '╭', '─', '┬', '╮',
            '│', ' ', '│', '│',
            '│', ' ', '│', '│',
            '╰', '─', '┴', '╯',
        ],
    ];
    /** @var string - The default type of a box (serves as global preferences) */
    static public $defaultType = 'line_thick';
    /** @var string - The default width of a box (serves as global preferences) */
    static public $defaultWidth = 64;
    /** @var string - The default alignment of a box (serves as global preferences) */
    static public $defaultAlign = self::ALIGN_JUSTIFY;

    /** @var string - The type of box to use */
    private $_type = null;
    /** @var string - The text to put in the box */
    public $text = "";
    /** @var array - The 16 characters to use for the box. Is usually defined using $boxes and $type. */
    public $box = null;
    /** @var integer - The total width of the box */
    public $width = null;
    /** @var integer - The alignment of the content of the box */
    public $align = 2;
    /** @var integer - The left and right padding inside the box */
    public $padding = 1;
    /** @var string - String to use as end of line */
    public $eol = self::EOL_UNIX;

    /**
     * Constructor
     * @private
     * @param string $text - The text to display in the box
     * @param string $type - The type of box
     */
    function __construct($text, $type = null)
    {
        if (is_null($type)) {
            $this->type = self::$defaultType;
        } else {
            $this->type = $type;
        }

        //$bText = TUI::bufferWrite($text);
        $this->text = $text;

        $this->width = self::$defaultWidth;
        $this->align = self::$defaultAlign;
    }

    function __get($name)
    {
        $getName = "get" . ucfirst($name);
        if (method_exists($this, $getName)) {
            return $this->$getName();
        }
    }

    function __set($name, $val)
    {
        $setName = "set" . ucfirst($name);
        if (method_exists($this, $setName)) {
            return $this->$setName($val);
        }
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setType($val)
    {
        $this->_type = $val;
        $this->box = self::$boxes[$this->type];
    }

    /**
     * Returns the box itself according to the object's properties
     * @return array - each element is a line that when printed consecutively creates an ascii art box.
     */
    public function render()
    {
        $result = [];

        $box = $this->box;
        $wText = $this->width - 2 - 2 * $this->padding;
        $paragraphs = preg_split('#[\v¶]+#', $this->text);
        $padding = str_repeat($box[5], $this->padding);
        $top = $box[0] . str_repeat($box[1], $this->width - 2) . $box[3];
        $middle = $box[8] . str_repeat($box[9], $this->width - 2) . $box[11];
        $bottom = $box[12] . str_repeat($box[13], $this->width - 2) . $box[15];
        $result[] = $top;

        $lines = self::alignTo(array_shift($paragraphs), $wText, $this->align);
        $lines = self::wrapAll($lines, $box[4] . $padding, $padding . $box[7]);
        $result = array_merge($result, $lines);

        foreach ($paragraphs as $paragraph) {
            $result[] = $middle;
            $lines = self::alignTo($paragraph, $wText, $this->align);
            $lines = self::wrapAll($lines, $box[4] . $padding, $padding . $box[7]);

            // Add in Each Line as its own string
            foreach ($lines as $line) {
                $result[] = $line;
            }
        }

        $result[] = $bottom;

        return $result;
    }

    /**
     * Sends the ext to proper alignment and returns the result
     * @param  string  $text  - The text to align
     * @param  integer $width - The width to respect
     * @param  integer $align - The alignment
     * @return array   - An array of aligned lines width fixed width
     */
    static private function alignTo($text, $width, $align)
    {
        switch ($align) {
            case self::ALIGN_LEFT:
                return self::left($text, $width);
            case self::ALIGN_RIGHT:
                return self::right($text, $width);
            case self::ALIGN_CENTER:
                return self::center($text, $width);
            case self::ALIGN_JUSTIFY:
                return self::justify($text, $width);
            case self::ALIGN_JUSTIFY_FORCED:
                return self::justify($text, $width, true);
            default:
                // Messenger::warning("Align Param mismatch", "WARNING");
                return self::center($text, $width);
        }
        //return $result;
    }

    /**
     * Returns an array of wrapped lines
     * @param  string  $text   - The text to wrap
     * @param  integer $length - The max width of a line
     * @return array   - The array of lines
     */
    static private function wordwrap($text, $length)
    {
        $result = preg_replace('#\s+#', " ", $text);
        $result = preg_split('#\v+#', wordwrap($result, $length));
        return $result;
    }

    /**
     * Returns an array of lines padded with pre- and post- fixes;
     * @param  array  $lines     - The lines to pad
     * @param  string [$pre='']  - What to put in front of each line
     * @param  string [$post=''] - What to put after each line
     * @return array  - Converted lines
     */
    static private function wrapAll($lines, $pre = '', $post = '')
    {
        $result = [];
        foreach ($lines as $line) {
            $result[] = $pre . $line . $post;
        }
        return $result;
    }

    /**
     * Wraps words of a string to given width and pads each line to one side
     * @param  string  $text  - The text to align
     * @param  integer $width - The max length of each line
     * @param  integer $side  - One of STR_PAD_RIGHT, STR_PAD_LEFT and STR_PAD_BOTH
     * @return array   - An array of padded lines
     */
    static private function pad($text, $width, $side)
    {
        $lines = self::wordwrap($text, $width);
        $result = [];
        foreach ($lines as $line) {
            $result[] = str_pad($line, $width, " ", $side);
        }
        return $result;
    }

    /**
     * Returns an array of left-aligned lines
     * @param  string  $text  - The text to align
     * @param  integer $width - The length of each line
     * @return array   - An array of padded lines
     */
    static public function left($text, $width)
    {
        return self::pad($text, $width, STR_PAD_RIGHT);
    }

    /**
     * Returns an array of right-aligned lines
     * @param  string  $text  - The text to align
     * @param  integer $width - The length of each line
     * @return array   - An array of padded lines
     */
    static public function right($text, $width)
    {
        return self::pad($text, $width, STR_PAD_LEFT);
    }

    /**
     * Returns an array of center-aligned lines
     * @param  string  $text  - The text to align
     * @param  integer $width - The length of each line
     * @return array   - An array of padded lines
     */
    static public function center($text, $width)
    {
        return self::pad($text, $width, STR_PAD_BOTH);
    }

    /**
     * Returns an array of justified lines
     * @param  string  $text  - The text to align
     * @param  integer $width - The length of each line
     * @param  boolean $force - Do we force last line to be justified?
     * @return array   - An array of padded lines
     */
    static public function justify($text, $width, $force = false)
    {
        $lines = self::wordwrap($text, $width);
        $last = array_pop($lines);
        $result = [];
        foreach ($lines as $line) {
            $result[] = self::justifyLine($line, $width);
        }
        if ($force) {
            $last = self::justifyLine($last, $width);
        } else {
            $last = str_pad($last, $width, " ", STR_PAD_RIGHT);
        }
        $result[] = $last;
        return $result;
    }

    /**
     * Justifies one line to a certain width
     * @param  string  $line  - The line to justify
     * @param  integer $width - The (max-)length of the resulting string
     * @return string  - A justified line
     */
    static private function justifyLine($line, $width)
    {
        $spaces = $width - strlen($line);
        if ($spaces <= 0) {
            return $line;
        }
        $words = preg_split('#\s+#', $line);
        $result = '';
        $result .= array_shift($words);
        $ratio = $spaces / count($words);
        foreach ($words as $idx => $word) {
            $nbSpaces = 1 + round(($idx + 1) * $ratio) - round($idx * $ratio);
            $result .= str_repeat(" ", $nbSpaces) . $word;
        }
        return $result;
    }

    static public function create($text, $properties = [])
    {
        $b = new self($text);
        foreach ($properties as $name => $property) {
            $b->$name = $property;
        }
        return $b->render();
    }
}
