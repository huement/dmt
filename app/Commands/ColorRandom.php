<?php

namespace App\Commands;

use App\Controllers\ColorController;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ColorRandom extends Command
{
 /**
  * The signature of the command.
  *
  * @var string
  */
 protected $signature = 'color:random
                            {--D|data= : pass hue or luminance EX: --data=red or --data=bright}';

 /**
  * The description of the command.
  *
  * @var string
  */
 protected $description = 'Generate and display a random color.';

 protected $help = "Run the command without parameters for a random color,
                       or optionally include one of the flags to customize the
                       hue, brightness or other aspects of the generated color";

 /**
  * default width for data display
  *
  * @var int
  */
 public $width = 70;

 /**
  * default space around data display
  *
  * @var int
  */
 public $margin = 4;

 /**
  * Execute the console command.
  *
  * @return mixed
  */
 public function handle()
 {
  $cmds = $this->option();

  $this->setHelp($this->help);

  $hue = false;
  $lum = false;

  if (null !== $this->option("data") && $this->option("data") !== false) {

   $mod = $this->option("data");

   if (strpos($mod, ',') !== false) {
    $parts = explode(",", $mod);
    $hue   = $parts[ 0 ];
    $lum   = $parts[ 1 ];
   } else {
    $hue = $mod;
   }

  }

  $miniData = ColorController::getRando($hue, $lum);

  $length = strlen($miniData[ "name" ]);
  $fLen   = (10 * $length);

  if ($fLen < 60) {
   $fLen = 64;
  }

  if ($fLen > 100) {
   $fLen = 100;
  }

  $data = ColorController::prettyColorReport($miniData[ "hex" ]);

  $options = array(
   "title"   => $miniData[ "name" ],
   "color"   => $miniData[ "ansi" ],
   "width"   => $fLen,
   "margin"  => $this->margin,
   "padding" => 0,
  );

  $up   = 16;
  $down = 5;

  ColorController::presentResults($this->output, $data, $options, $up, $down);
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
}
