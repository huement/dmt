<style>
    .smini {
        margin: 0;
        float: left;
        padding: 0;
        height: 12px;
        width: 12px;
    }
</style>
<?php
require_once('ColorChip.php');
$imgname = "ROOT_IMG_PATH";
$img_web = "WEB_IMG_PATH";

function colorPalette($img)
{

    $imgname = $img;
    $im = imagecreatefromjpeg($imgname);
    $x = imagesx($im);
    $y = imagesx($im);

    $start_x = 1;
    $start_y = 1;
    $palette = array();


    while ($start_x <= $x && $start_y <= $y) {
        $color = ImageColorAt($im, $start_x, $start_y);
        $r = ($color >> 16) & 0xFF;
        $g = ($color >> 8) & 0xFF;
        $b = $color & 0xFF;

        $rgbtohex = dechex($color);
        $userColor = strtoupper($_GET['userColor']);
        $color2 = new ColorChip($rgbtohex, null, null, CC_HEX);
        $webSafe = $color2->getNearestWebSafe();

        if (array_key_exists($webSafe->hex, $palette)) {
            $palette[$webSafe->hex]['count']++;
        } else {
            $palette[$webSafe->hex]['count'] = 1;
            $palette[$webSafe->hex]['r'] = $webSafe->r;
            $palette[$webSafe->hex]['g'] = $webSafe->g;
            $palette[$webSafe->hex]['b'] = $webSafe->b;
            $palette[$webSafe->hex]['h'] = $webSafe->h;
            $palette[$webSafe->hex]['s'] = $webSafe->s;
            $palette[$webSafe->hex]['v'] = $webSafe->v;
        }

        if ($start_x == $x) {
            $start_y++;
            $start_x = 1;
        }



        $start_x++;
    }

    return   $palette;
}
$colors = colorPalette($imgname);
echo  "<p> <img src = \" $img_web \" > </img> </p>";
foreach ($colors  as  $color => $infos) {
    $linkColor = ($infos['v'] > 60 ? "000000" : "FFFFFF");
    echo "<div class=\"smini $color\" style=\"padding: .5em\"> </div> ";
}

?>
