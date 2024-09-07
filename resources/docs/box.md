# Ways to make an ASCII box

## BoxPhreaks

Features

+ B16 Theme
+ Bottom Title
+ Padding & Margin
+ Figlet Title
+ Different Styles

Issues

+ Main content area. The wrap function seems to have some issues.
+ Optionally set the content width to control wrap issues.
+ Must declare setShowCONTENT(true) otherwise box is blank

```php
 $string = "Without development, you will lack affiliate-based compliance. We will regenerate our aptitude to empower without depreciating our capability to transform   vize proactively then you may also mesh iteravely. It sounds wonderful, but it's 100 percent accurate! The experiences factor is compelling. Quick: do you have a infinitely reconfigurable scheme for coping with emerging methodologies? Is it more important for something to be leading-edge or to be customer-directed? What does the industry jargon";

 $BoxPhreaks = new BoxPhreaks();

 //$BoxPhreaks->setContentWIDTH(51);
 $BoxPhreaks->setBoxTITLE("TESTING");
 $BoxPhreaks->setBoxCONTENT($string);
 $BoxPhreaks->setShowCONTENT(true);
 $BoxPhreaks->setBoxWIDTH(64);
 $BoxPhreaks->setBoxTYPE("punk_1");
 $BoxPhreaks->setBoxMARGIN(2);
 $BoxPhreaks->setBoxPADDING(2);
 $BoxPhreaks->setBoxBtmTITLE("PHREAK");
 $BoxPhreaks->setBoxTHEME(true);

 $string = $BoxPhreaks->generateBox();

 TUI::echoString($string);
```

## PHREAK BOX

This print the box empty and then manipulates the cursor position to "print overtop".
Major disadvantage is the box dimensions are not tied to the content.

```php

░█▀█░█░█░█▀▄░█▀▀░█▀█░█░█░░░█▀▄░█▀█░█░█
░█▀▀░█▀█░█▀▄░█▀▀░█▀█░█▀▄░░░█▀▄░█░█░▄▀▄
░▀░░░▀░▀░▀░▀░▀▀▀░▀░▀░▀░▀░░░▀▀░░▀▀▀░▀░▀

 $box = new CMDPhreaks();
 $box->setBoxWidth(25);
 $box->setMainRows(5);
 $box->setTopRows(1);
 $box->generatePhreakBox();

 $this->figletTitle("THIRT13N");
```

## WBPhreaks

This uses a 3rd party class that was been imported. Boxes are very basic.

@TODO: Test if things like color info can be put inside.

```php
 $string = PHP_EOL . "Quick: do you have a infinitely reconfigurable scheme for coping with emerging methodologies? Is it more important for something to be dynamic or to be best-of-breed? The portals factor can be summed up in one word: affiliate-based. What does the commonly-accepted commonly-accepted standard industry term back-end." . PHP_EOL;

 $b = new WBPhreaks($string);
 $b->width    = 40;
 $b->align    = "center";
 $b->type     = "block_black";
 $b->padding  = 10;

 echo $b->render();

 // OR ANOTHER WAY

 $start = WBPhreaks::create("This is the boxed string");
 TUI::echoString($start);

```

