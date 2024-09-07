<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Codedungeon\PHPMessenger\Facades\Messenger;
use App\Console\TUI;
use App\Console\Boxer;
use App\Console\BoxerMini;
use App\Console\BoxerMax;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// use Colors\Color;

class BoxCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'box
                            {--data= : User input string}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = "Generate ASCII Box around some content";

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //TUI::testing();
        //exit;

        $this->setHelp(
            "This is a debugging command that doesnt really do anything, instead is a sandbox for testing sh*t out."
        );

        Messenger::status(
            "First you will select the type of resource you would like to craft",
            "STEP 1:"
        );

        Messenger::info(
            "At any time during process, you can exit process by pressing ctrl-c",
            "NOTE"
        );

        $result = $this->confirm("Would you like to continue?");

        if (!$result) {
            exit();
        }

        Messenger::success("Laravel Craftsman Wizard", "SUCCESS");

        // Phreak Box
        $box = new BoxerMax();
        $box->setBoxWidth(40);
        $box->setMainRows(5);
        $box->setTopRows(1);
        //        $box->generatePhreakBox();

        TUI::Break(2);
        TUI::outMove($box->generatePhreakBox(), ["right" => 5], $this->output);

        $this->figletTitle("THIRT13N TEST");

        TUI::Break(10);

        $start = BoxerMini::create("This is the boxed string");
        TUI::Speaks($start);

        echo PHP_EOL . PHP_EOL . PHP_EOL;

        $string =
            PHP_EOL .
            "Quick: do you have a infinitely reconfigurable scheme for coping with emerging methodologies? Is it more important for something to be dynamic or to be best-of-breed? The portals factor can be summed up in one word: affiliate-based. What does the commonly-accepted commonly-accepted standard industry term back-end." .
            PHP_EOL;

        $b = new BoxerMini($string);
        $b->width = 40;
        $b->align = "center";
        $b->type = "block_black";
        $b->padding = 4;

        TUI::outMove($b->render(), ["right" => 15], $this->output);

        echo PHP_EOL . PHP_EOL . PHP_EOL;

        // FIGLET TEXT
        $text = "Phreaks";
        TUI::buildFiglet($text, "rebel", 120, false);

        $string =
            "Without development, you will lack affiliate-based compliance. We will regenerate our aptitude to empower without depreciating our capability to transform   vize proactively then you may also mesh iteravely. It sounds wonderful, but it's 100 percent accurate! The experiences factor is compelling. Quick: do you have a infinitely reconfigurable scheme for coping with emerging methodologies? Is it more important for something to be leading-edge or to be customer-directed? What does the industry jargon";

        $Boxer = new Boxer();

        $Boxer->setBoxTITLE("Phreakerly 13");
        $Boxer->setBoxCONTENT($string);
        $Boxer->setShowCONTENT(true);
        $Boxer->setBoxWIDTH(44);
        $Boxer->setBoxTYPE("reputation");
        $Boxer->setBoxMARGIN(2);
        $Boxer->setBoxPADDING(2);
        $Boxer->setBoxBtmTITLE("HUEMENT");
        $Boxer->setBoxTHEME(true);

        $bstring = $Boxer->generateBox();

        TUI::Speaks($bstring);

        $Boxer->setBoxTITLE("OverRide");
        $Boxer->setColorOutput(false);
        $Boxer->setBoxCONTENT(false);
        $Boxer->setBoxWIDTH(44);
        $Boxer->setBoxHEIGHT(10);
        $Boxer->setBoxTYPE("folklore");
        $Boxer->setShowCONTENT(false);
        $Boxer->setBoxMARGIN(8);
        $Boxer->setBoxPADDING(0);
        $Boxer->setBoxBtmTITLE(false);
        $Boxer->setBoxTHEME(false);

        $forceTall = $Boxer->generateBox();

        TUI::Speaks($forceTall);
    }

    public function figletTitle($text, $mode = "rainbow")
    {
        $cursor = new Cursor($this->output);
        $cursor->moveUp(21);

        $cmdString = "figlet -s -w 65 -c -f 'pagga' " . $text;
        if ($mode === "rainbow") {
            $cmdString = "toilet --gay -f 'pagga' " . $text;
        }
        exec($cmdString, $execOutput);

        foreach ($execOutput as $titleROW) {
            if ($mode === "rainbow") {
                $cursor->moveRight(7);
            }

            $this->output->write($titleROW . PHP_EOL);
        }

        $cursor->moveDown(12);
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule)
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
