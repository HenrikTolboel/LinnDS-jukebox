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
$startfrom = $_GET["startfrom"];
$href = "#";

$cont = "";
$cont .= '<div class="play">' . "\n";
$cont .= '<a href="' . $href . '" data-rel="back" class="dialogclick" data-role="button" data-theme="b" data-musik=' . "'" . '{"action": "PlayNow", "preset":' . $preset . "}'>" . 'Play Now</a>' . "\n";
$cont .= '<a href="' . $href . '" data-rel="back" class="dialogclick" data-role="button" data-theme="b" data-musik=' . "'" . '{"action": "PlayNext", "preset":' . $preset . "}'>" . 'Play Next</a>' . "\n";
$cont .= '<a href="' . $href . '" data-rel="back" class="dialogclick" data-role="button" data-theme="b" data-musik=' . "'" . '{"action": "PlayLater", "preset":' . $preset . "}'>" . 'Play Later</a>' . "\n";
$cont .= '<a href="' . $href . '" data-rel="back" data-icon="delete" data-role="button" data-theme="c">Cancel</a>' . "\n";
$cont .= '</div>' ."\n";

$str = Dialog("PlayDialog", "Dialog", $cont, "LinnDS-jukebox");
echo $str;

require("footer.inc");
?>
