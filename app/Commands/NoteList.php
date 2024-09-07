<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use App\Console\TUI;
use App\Console\Boxer;
use App\Controllers\DataController;
use App\Controllers\NoteController;

class NoteList extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'note:list';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List all your notes. Optionally search by tags or notebook.';

    /**
     * Notebooks
     *
     * @var array
     */
    public $Notebooks = array();

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $notes  = json_decode(DataController::getAllFromTable("notes"), true);

        $string = NoteController::noteLister($notes, "    ");
        $totalNotes = count($notes);
        $totalUp = $totalNotes * 4;

        TUI::Break(2);
        $this->noteBox($totalNotes);
        TUI::Break();
        TUI::Speaks($string);
        TUI::Break(2);
        exit;

        TUI::moveCursor($this->output, "up", $totalUp);

        TUI::outMove($string, array("right" => 5), $this->output);

        TUI::moveCursor($this->output, "down", 5);
        TUI::Break(3);
    }

    /**
     * noteBox
     *
     * generate an ascii box that is filled with a list of notes
     *
     * @param  mixed $total
     * @return void
     */
    public function noteBox($total = 1)
    {
        $height = $total * 4;

        $Boxer = new Boxer();

        $Boxer->setBoxTITLE("Notes");
        $Boxer->setColorOutput(false);
        $Boxer->setBoxCONTENT(false);
        $Boxer->setBoxWIDTH(80);
        $Boxer->setBoxHEIGHT(0);
        $Boxer->setBoxTYPE("folklore");
        $Boxer->setShowCONTENT(false);
        $Boxer->setBoxMARGIN(2);
        $Boxer->setBoxPADDING(0);
        $Boxer->setBoxBtmTITLE(false);
        $Boxer->setBoxTHEME(false);

        $forceTall = $Boxer->generateBox();

        TUI::Speaks($forceTall);
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
