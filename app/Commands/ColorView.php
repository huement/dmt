<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use League\CLImate\CLImate;

use App\Controllers\ColorController;

use App\Console\Display;
use App\Console\TUI;
use App\Console\Boxer;

class ColorView extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'color:view
                            {hex? : Hexidecimal color code subject matter / starting point}
                            {--F|format= : ascii or json}
                            {--C|comp : complex info display}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'View available information for given color';

    /**
     * placeholder for global color code variable
     *
     * @var string
     */
    public $hexCode = "";

    /**
     * default width for data display
     *
     * @var int
     */
    public $width   = 70;

    /**
     * default space around data display
     *
     * @var int
     */
    public $margin  = 4;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cmds = $this->option();
        $hex  = (null !== $this->argument('hex') && strlen($this->argument('hex')) > 0) ? $this->argument('hex') : false;

        if(!$hex) Display::printErrorExit('[CNCV0] No HexCode Input! Ya Dingus!');
        if(!ColorController::checkHexCode($hex)) Display::printErrorExit('[CNCV1] Invalid HexCode!');

        $this->setHexCode($hex);

        // Optional Complement Colors
        $params =($this->option("comp") !== false) ? array("comp" => true) : false;

        // Optional Format Param
        $format = (null !== $this->option("format")) ? $this->option("format") : "ascii";

        // Get color data
        $data      = ColorController::prettyColorReport($hex, false, $params);
        $miniData  = ColorController::getMiniColorInfo($hex);

        // Box Options
        $options = array(
            "title"   => $miniData["name"],
            "color"   => $miniData["ansi"],
            "width"   => $this->width,
            "margin"  => $this->margin,
        );

        // Fixed values. Customizing these would mess up the printout.
        $up   = 16;
        $down = 5;

        if(!isset($data)) Display::printErrorExit('[CNCV2] No Returned Data?!');

        if ($format === "ascii" && isset($data))
            $this->presentResults($data, $options, $up, $down);
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
    public function presentResults($colorData, $boxOptions, $up = 16, $down = 5)
    {
        $Boxer = new Boxer;
        $Boxer->printBox($colorData, $boxOptions);

        Display::moveCursor("up", $up, $this->output);
        Display::moveThenPrintArray($colorData, array("right" => 7), $this->output);
        Display::moveCursor("down", $down, $this->output);
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    /**
     * Get placeholder for global color code variable
     *
     * @return  string
     */
    public function getHexCode()
    {
        return $this->hexCode;
    }

    /**
     * Set placeholder for global color code variable
     *
     * @param  string  $hexCode  placeholder for global color code variable
     *
     * @return  self
     */
    public function setHexCode(string $hexCode)
    {
        $this->hexCode = $hexCode;
        return $this;
    }
}
