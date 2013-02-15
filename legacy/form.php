<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require("Manifest.php");
require("Functions.php");
$volume = $_POST["volume"];
$PresetCount = $_POST["count"];
$id = $_GET["id"];
$id = "presets";

$HOST = "127.0.0.1";
$PORT = 9050;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($socket, $HOST, $PORT);
$string = socket_read($socket, 10000); // Remove connection info sent from server...

socket_write($socket, "Volume Set \"" . $volume . "\"\n");
socket_close($socket);
file_put_contents("/tmp/log.txt", $volume);

return;
$cachefilename = "../tmp/presets-" . $FirstPreset . "-" . $PresetCount;

$manifestfile = "../_Presets/manifest.xml";

if (file_exists($cachefilename) && filemtime($cachefilename) > filemtime($manifestfile))
{
    echo file_get_contents($cachefilename);
}
else
{
    $manifest = new Manifest($manifestfile);
    $str = MakePresetList($manifest, $id, $FirstPreset, $PresetCount);
    file_put_contents($cachefilename, $str);
    echo $str;
}

?>
