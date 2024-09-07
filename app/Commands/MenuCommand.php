<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use App\Console\TUI;

// MENU CLASSES
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\MenuItem\AsciiArtItem;
use PhpSchool\CliMenu\MenuStyle;
use PhpSchool\CliMenu\Input\Text;
use PhpSchool\CliMenu\Input\InputIO;

class MenuCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'menu';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'DMT MAIN MENU';

    protected $fgPop    = 47;
    protected $fgColor  = 84;
    protected $bgColor  = 235;
    protected $bgPop    = 232;
    protected $width    = 120;
    protected $asciiArt = 'libraries/ascii/spaceman.txt';

    public $menu;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //echo PHP_EOL . $this->dotBar(4) . PHP_EOL;

        $actions = $this->mainMenuActions();

        $menuBuilder = new CliMenuBuilder();

        $this->buildMainMenu(
            $menuBuilder,
            $actions
        );

        $this->buildColorMenu($menuBuilder, $actions);
        $this->buildPaletteMenu($menuBuilder, $actions);
        $this->buildBase16Menu($menuBuilder, $actions);
        $this->buildNotesMenu($menuBuilder, $actions);
        // $this->buildOptionsMenu($menuBuilder, $actions);

        $this->menu = $menuBuilder->build();
        $this->menu->open();
    }

    public function mainMenuLoad()
    {
        TUI::Speaks('mainMenuLoad');
    }

    public function mainMenuActions()
    {
        $actions = array();

        $popupStyle = (new MenuStyle)
            ->setBg($this->bgPop)
            ->setFg($this->fgPop);

        $actions["temp"] = function (\PhpSchool\CliMenu\CliMenu $mainMenu) {
            //$mainMenu::Speaks("Testing");
            echo "TESTING";
            $item = $mainMenu->getSelectedItem()->getText();
            //PalettePhreaks::testingAccess($item);
        };

        $actions["callNew"] = function (\PhpSchool\CliMenu\CliMenu $mainMenu) {
            $item = $mainMenu->getSelectedItem()->getText();
            TUI::Speaks($item);
            echo  PHP_EOL . "123 TESTING" . PHP_EOL;
        };

        $actions["new"] = function (\PhpSchool\CliMenu\CliMenu $menu) use ($popupStyle) {

            // Clear Menu
            foreach ($menu->getItems() as $item) {
                $menu->removeItem($item);
            }

            //$art = file_get_contents(base_path($this->asciiArt));
            //$menu->addAsciiArt($art, AsciiArtItem::POSITION_CENTER, "  --  BLASTED  --");
            $menu->redraw();

            // Pop up Dialog
            $result = $menu->askText($popupStyle)
                ->setPromptText('Name your new Palette')
                ->setPlaceholderText('.......?')
                ->setValidationFailedText('Invalid Name!')
                ->ask();

            //var_dump();
            $name = $result->fetch();

            $this->mainMenuLoad();
        };

        return $actions;
    }

    public function buildMainMenu(CliMenuBuilder $menuBuilder, $actions)
    {
        $art = file_get_contents(base_path($this->asciiArt));

        $menuBuilder
            ->addAsciiArt($art, AsciiArtItem::POSITION_CENTER, "  --  BLASTED  --")
            ->setTitleSeparator('◼')
            ->setTitle('Phreak ▶ Main Menu')
            ->setPadding(2, 4)
            ->setMarginAuto()
            ->setForegroundColour($this->fgColor)
            ->setBackgroundColour($this->bgColor)
            ->addLineBreak(' ')
            //->addStaticItem('COLORS')
            //->addItem('Hexapedia', $actions["new"])
            //->addItem('Palettes', $actions["new"], true)
            //->setItemExtra('[13]')
            //->addItem('Harmony', $actions["new"])
            // ->addItem('Base16 Themes', $actions["temp"])
            // ->addLineBreak(' ')
            // ->addStaticItem('KNOWLEDGE')
            // ->addItem('Notebooks', $actions["temp"], true)
            // ->setItemExtra('[23]')
            // ->addItem('Notes', $actions["temp"], true)
            // ->setItemExtra('[33]')
            // ->addLineBreak(' ')
            // ->addStaticItem('TOOLS')
            // ->addItem('Wallpapers', $actions["temp"])
            // ->addItem('Boxes', $actions["temp"])
            //->addCheckboxItem('Shuffle theme colors?', $importBase16)
            ->addLineBreak(' ')
            // ->addLineBreak('⎺')
            ->setWidth($this->width);

        return $menuBuilder;
    }

    public function buildColorMenu(CliMenuBuilder $menuBuilder, $actions)
    {
        $menuBuilder->addSubMenu('Hexapedia', function (CliMenuBuilder $h) use ($actions) {
            $h->setTitle('Color Info & Commands')
                ->setTitleSeparator($this->dotBar(1))
                ->setPadding(2, 4)
                ->setMarginAuto()
                ->setForegroundColour($this->fgColor)
                ->setBackgroundColour($this->bgColor)
                ->addLineBreak(' ')
                ->addItem('Details', $actions["temp"])
                ->addItem('Harmony', $actions["temp"])
                ->addLineBreak(' ')
                ->addStaticItem('TOOLS')
                ->addItem('Shades', $actions["temp"])
                ->addItem('Random', $actions["temp"])
                ->addItem('Stack', $actions["temp"])
                ->addLineBreak(' ')
                ->addStaticItem('COMMANDS')
                ->addItem('Lighten', $actions["temp"])
                ->addItem('Darken', $actions["temp"])
                ->addItem('Fade', $actions["temp"])
                ->addItem('Rotate', $actions["temp"])
                ->addItem('Saturate', $actions["temp"])
                ->addItem('Desaturate', $actions["temp"])
                ->addItem('Inverse', $actions["temp"])
                ->addItem('Text Color', $actions["temp"])
                ->addLineBreak(' ')
                ->addLineBreak('⎺')
                ->setWidth($this->width);
        })
            ->addLineBreak(' ');

        return $menuBuilder;
    }

    public function buildPaletteMenu(CliMenuBuilder $menuBuilder, $actions)
    {
        $menuBuilder->addSubMenu('Palette Phreaks', function (CliMenuBuilder $h) use ($actions) {
            $h->setTitle('Color Palettes ▶ Start')
                ->setTitleSeparator($this->dotBar(2))
                ->setPadding(2, 4)
                ->setMarginAuto()
                ->setForegroundColour($this->fgColor)
                ->setBackgroundColour($this->bgColor)
                ->addLineBreak(' ')
                ->addItem('Create New', $actions["temp"])
                ->addItem('List All', $actions["temp"], true)
                ->setItemExtra('[69]')
                ->addLineBreak(' ')
                ->addStaticItem('SAVED PALETTES')
                ->addItem('Display', $actions["temp"])
                ->addItem('Export', $actions["temp"])
                ->addItem('Update', $actions["temp"])
                ->addItem('Delete', $actions["temp"])
                ->addLineBreak(' ')
                ->addLineBreak('⎺')
                ->setWidth($this->width);
        })
            ->addLineBreak(' ');

        return $menuBuilder;
    }

    public function buildBase16Menu(CliMenuBuilder $menuBuilder, $actions)
    {
        $menuBuilder->addSubMenu('Base16 Themes', function (CliMenuBuilder $h) use ($actions) {
            $h->setTitle('Base16 Themes ▶ Start')
                ->setTitleSeparator($this->dotBar(3))
                ->setPadding(2, 4)
                ->setMarginAuto()
                ->setForegroundColour($this->fgColor)
                ->setBackgroundColour($this->bgColor)
                ->addLineBreak(' ')
                ->addStaticItem('Base16 Themes')
                ->addLineBreak(' ')
                ->addItem('Create New Palette', $actions["temp"])
                ->addLineBreak(' ')
                ->addItem('Preview Theme', $actions["temp"])
                ->addLineBreak(' ')
                ->addItem('Generate Wallpaper', $actions["temp"])
                ->addLineBreak(' ')
                ->addItem('Export Theme', $actions["temp"])
                ->addLineBreak(' ')
                ->addItem('Sync Themes', $actions["temp"])
                ->addItem('Sync Templates', $actions["temp"])
                ->addLineBreak(' ')
                ->addLineBreak('⎺')
                ->setWidth($this->width);
        })
            ->addLineBreak(' ');

        return $menuBuilder;
    }

    public function buildNotesMenu(CliMenuBuilder $menuBuilder, $actions)
    {
        $menuBuilder->addSubMenu('Knowledge Base', function (CliMenuBuilder $h) use ($actions) {
            $h->setTitle('Knowledge Base ▶ Main')
                ->setTitleSeparator($this->dotBar(4))
                ->setPadding(2, 4)
                ->setMarginAuto()
                ->setForegroundColour($this->fgColor)
                ->setBackgroundColour($this->bgColor)
                ->addLineBreak(' ')
                ->addStaticItem('Notebooks')
                ->addLineBreak(' ')
                ->addItem('Add New', $actions["temp"])
                ->addItem('Rename', $actions["temp"])
                ->addItem('List All', $actions["temp"])
                ->addLineBreak(' ')
                ->addStaticItem('Notes')
                ->addLineBreak(' ')
                ->addItem('Add New', $actions["temp"])
                ->addItem('Edit Note', $actions["temp"])
                ->addItem('List All', $actions["temp"])
                ->addLineBreak(' ')
                ->addLineBreak('⎺')
                ->setWidth($this->width);
        })
            ->addLineBreak(' ')
            ->addLineBreak(' ')
            ->addStaticItem('TOOLS')
            ->addLineBreak('⎺')
            ->addItem('Wallpapers', $actions["temp"])
            ->addItem('Boxes', $actions["temp"])
            ->addLineBreak(' ')
            ->addLineBreak(' ');

        return $menuBuilder;
    }

    public function buildWallpaperMenu(CliMenuBuilder $menuBuilder, $actions)
    {
        $menuBuilder->addSubMenu('Wallpapers', function (CliMenuBuilder $h) use ($actions) {
            $h->setTitle('Wallpapers ▶ Start')
                ->setTitleSeparator($this->dotBar(rand(1, 5)))
                ->setPadding(2, 4)
                ->setMarginAuto()
                ->setForegroundColour($this->fgColor)
                ->setBackgroundColour($this->bgColor)
                ->addLineBreak(' ')
                ->addStaticItem('Themed Wallpaper Generator')
                ->addLineBreak(' ')
                ->addItem('Create New Palette', $actions["temp"])
                ->addLineBreak(' ')
                ->addItem('Preview Theme', $actions["temp"])
                ->addLineBreak(' ')
                ->addItem('Generate Wallpaper', $actions["temp"])
                ->addLineBreak(' ')
                ->addItem('Export Theme', $actions["temp"])
                ->addLineBreak(' ')
                ->addItem('GENERATE ->', $actions["temp"])
                ->addLineBreak(' ')
                ->addLineBreak('⎺')
                ->setWidth($this->width);
        })
            ->addLineBreak(' ');

        return $menuBuilder;
    }

    public function buildOptionsMenu(CliMenuBuilder $menuBuilder, $actions)
    {
        $menuBuilder->addSubMenu('Phreak Options', function (CliMenuBuilder $h) use ($actions) {
            $h->setTitle('Phreak Options')
                ->setTitleSeparator('■■■■■■■■■■■■■■■■■■■■■■■▶ ● ● ● ◀■■■■■■■■■■■■■■■■■■■■■■■')
                ->setPadding(2, 4)
                ->setMarginAuto()
                ->setForegroundColour($this->fgColor)
                ->setBackgroundColour($this->bgColor)
                ->addLineBreak(' ')
                ->addStaticItem('Phreak Test')
                ->addItem('Toggle Global Option', $actions["temp"])
                ->addLineBreak(' ')
                ->addItem('Toggle Another Option', $actions["temp"])
                ->addLineBreak(' ')
                ->addLineBreak('⎺')
                ->setWidth($this->width);
        });

        return $menuBuilder;
    }

    public function dotBar($dotTotal)
    {
        $dot    = "●";
        $lS     = "▶";
        $rS     = "◀";
        $bar    = "■";

        $sRand = rand(32, 42);
        $eRand = rand(23, 36);
        if (($sRand + $eRand) < 58)
            $sRand = $sRand + 10;

        $start = str_repeat($bar, $sRand);
        $end   = str_repeat($bar, $eRand);

        $count = 0;
        $dString = "";
        while ($count < $dotTotal) {
            $dString .= $dot . " ";
            $count++;
        }

        $final = $start . $lS . " " . $dString . $rS . $end;

        return $final;
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
