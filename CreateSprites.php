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

$manifest = new Manifest($manifestfile);


$MaxPreset = $manifest->NumberOfPresets;

for ($i = 1; $i <= $MaxPreset; $i++)
{
   echo $i .": " . $manifest->PresetImage[$i] . "\n";
   $img = rawurldecode($manifest->PresetImage[$i]);
    echo $i . ": " . $img ."\n";
    if (strlen($img) > 4 && file_exists("../" . $img))
    {
       $sourcefile = '../' . $img;
       $newfile = sprintf("folder/folder_%04d.jpg", $i);
    }
    else
    {
       $sourcefile = "grey.jpg";
       $newfile = sprintf("folder/folder_%04d.gif", $i);
    }
    copy($sourcefile, $newfile);
}

//$MaxPreset = 860;
$ImgSize = 80;
$TileW = 10;
$TileH = 10;

$SpriteW = $ImgSize * $TileW + 2 * $TileW;
$SpriteH = $ImgSize * $TileH + 2 * $TileH;

// On an ipad somehow the size of a sprite image should be < 1024 pixels 
// wide / high - otherwise the display of sprite elements are distorted.

echo "Run montage -background transparent -tile " . $TileW . "x" . $TileH . " -geometry 80x80+1+1 folder/folder_* sprites/sprite.jpg\n";
echo "Run montage -background transparent -tile " . $TileW . "x" . $TileH . " -geometry 160x160+1+1 folder/folder_* sprites/sprite@2x.jpg\n";

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
         $css .= "   background: url(sprite@2x-" . $k . ".jpg) no-repeat top left;\n";
         $css .= "   background-size: " . $SpriteW . "px " . $SpriteH . "px;\n";
         $css .= "   background-position: " . $posy . "px " . $posx . "px;\n";
         $css .= "}\n";
      }
  }
}

file_put_contents("sprites/sprites@2x.css", $css);

    

?>
