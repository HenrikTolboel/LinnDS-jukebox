<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");
require_once("Manifest.php");

/*
$manifest = new Manifest($manifestfile);


$MaxPreset = $manifest->NumberOfPresets;

for ($i = 1; $i <= $MaxPreset; $i++)
{
    echo $i . ": " . $manifest->PresetImage80x80[$i] ."\n";
    if (file_exists("../" . $manifest->PresetImage80x80[$i]))
	$sourcefile = '../' . $manifest->PresetImage80x80[$i];
    else
	$sourcefile = "Transparent.gif";
    $newfile = sprintf("sprites/%04d.gif", $i);
    copy($sourcefile, $newfile);
}
*/

echo "Run montage -background transparent -tile 20x20 -geometry 80x80+1+1 sprites/* sprites/sprite.jpg";

$MaxPreset = 860;
$ImgSize = 80;
$TileW = 20;
$TileH = 20;

$css = "";
$cnt = 0;
for ($k = 0; $cnt < $MaxPreset; $k++)
{
   for ($i = 0; $i < $TileW && $cnt < $MaxPreset; $i++)
   {
      for ($j = 0; $j < $TileH && $cnt < $MaxPreset; $j++)
      {
         $cnt++;
         $posx = -1 * ($i * $ImgSize + $i*2 +1);
         $posy = -1 * ($j * $ImgSize + $j*2 +1);
         $css .= ".sprite_" . $cnt . "\n";
         $css .= "{\n";
         $css .= "   width: " . $ImgSize . "px;\n";
         $css .= "   height: " . $ImgSize . "px;\n";
         $css .= "   background: url(sprite-" . $k . ".jpg) no-repeat top left;\n";
         $css .= "   background-position: " . $posy . "px " . $posx . "px;\n";
         $css .= "}\n";
      }
   }
}

file_put_contents("sprites/sprites.css", $css);

    

?>
