<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

   require("header.inc");
   require_once("Functions.php");

   $preset = $_GET["preset"];
   //$frompage = $_GET["frompage"];
   //$firstpreset = $_GET["firstpreset"];
   //$count = $_GET["count"];
   //$href= "#" . $frompage . "?firstpreset=" . $firstpreset . "&count=" . $count;
   $href = "#";

   $cont = "";
   $cont .= '<div class="play">' . "\n";
   $cont .= '<a href="' . $href . '" data-rel="back" class="onepreset" data-role="button" data-theme="b" data-musik=' . "'" . '{"playfunction": "PlayNow", "preset":' . $preset . "}'>" . 'Play Now</a>' . "\n";
   $cont .= '<a href="' . $href . '" data-rel="back" class="onepreset" data-role="button" data-theme="b" data-musik=' . "'" . '{"playfunction": "PlayNext", "preset":' . $preset . "}'>" . 'Play Next</a>' . "\n";
   $cont .= '<a href="' . $href . '" data-rel="back" class="onepreset" data-role="button" data-theme="b" data-musik=' . "'" . '{"playfunction": "PlayLater", "preset":' . $preset . "}'>" . 'Play Later</a>' . "\n";
   $cont .= '<a href="' . $href . '" data-rel="back" data-role="button" data-theme="c">Cancel</a>' . "\n";
   $cont .= '</div>' ."\n";

   //$str = Dialog("PlayDialog-" . $preset, "Dialog", $cont, "Page Footer");
   $str = Dialog("PlayDialog", "Dialog", $cont, "Page Footer");
   //$str .= "<script>\n<!--\n$('#PlayDialog-a').attr('href', 'abekat');\n//-->\n</script>\n";
   echo $str;
   
   require("footer.inc");
?>
