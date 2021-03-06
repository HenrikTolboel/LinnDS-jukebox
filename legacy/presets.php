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
require_once("setup.php");
$FirstPreset = $_GET["firstpreset"];
$PresetCount = $_GET["count"];

$cachefilename = $CACHE_DIR . "/presets-" . $FirstPreset . "-" . $PresetCount;

if ($DO_CACHE && file_exists($cachefilename) && filemtime($cachefilename) > filemtime($manifestfile))
{
    echo file_get_contents($cachefilename);
}
else
{
    require_once("Manifest.php");
    require_once("Functions.php");

    $manifest = new Manifest($manifestfile);

    echo MakeOnePreset($manifest, $FirstPreset, $PresetCount);
}
require("footer.inc");

?>
