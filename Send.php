<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2013 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");

$action = $_GET["action"];

$HOST = "127.0.0.1";
$PORT = 9050;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($socket, $HOST, $PORT);
$string = socket_read($socket, 10000); // Remove connection info sent from server...

$track = 0;
if (isset($_GET["track"]) && !empty($_GET["track"])) {
    $track = $_GET["track"];
}

if ($action == "State") {
    $Str = "State";
}
elseif ($action == "Playlist") {
    $Str = "State";
}
elseif ($action == "PlayNow") {
    $preset = $_GET["preset"];
    $Str = "Jukebox PlayNow \"" . $preset . "\" \"" . $track . "\"";
}
elseif ($action == "PlayNext") {
    $preset = $_GET["preset"];
    $Str = "Jukebox PlayNext \"" . $preset . "\" \"" . $track . "\"";
}
elseif ($action == "PlayLater") {
    $preset = $_GET["preset"];
    $Str = "Jukebox PlayLater \"" . $preset . "\" \"" . $track . "\"";
}
elseif ($action == "PlayRandomTracks") {
    $preset = $_GET["preset"];
    $Str = "Jukebox PlayRandomTracks \"" . $preset . "\" \"" . $track . "\"";
}
elseif ($action == "SetVolume") {
    $volume = $_GET["volume"];
    $Str = "Volume Set \"" . $volume . "\"";
}
elseif ($action == "IncrVolume") {
    $Str = "Volume Incr";
}
elseif ($action == "DecrVolume") {
    $Str = "Volume Decr";
}
elseif ($action == "Control-Play") {
    $Str = "Control Play";
}
elseif ($action == "Control-Pause") {
    $Str = "Control Pause";
}
elseif ($action == "Control-Stop") {
    $Str = "Control Stop";
}
elseif ($action == "Control-Next") {
    $Str = "Control Next";
}
elseif ($action == "Control-Previous") {
    $Str = "Control Previous";
}
elseif ($action == "Source-Playlist") {
    $Str = "Source Playlist";
}
elseif ($action == "Source-TV") {
    $Str = "Source TV";
}
elseif ($action == "Source-Radio") {
    $Str = "Source Radio";
}
elseif ($action == "Source-NetAux") {
    $Str = "Source NetAux";
}
elseif ($action == "Source-Off") {
    $Str = "Source Off";
}

socket_write($socket, $Str . $NL);

if ($Str == "State") {
    $string = socket_read($socket, 100000);
}

socket_close($socket);

if ($action == "State") {
    $State = unserialize($string);
    //echo print_r($State, TRUE);
    $a['MAX_VOLUME'] = $State['MAX_VOLUME'];
    $a['Volume'] = $State['Volume'];
    echo json_encode($a);
}
elseif ($action == "Playlist") {
    $State = unserialize($string);
    //echo print_r($State, TRUE);
    $a['PlaylistXMLs'] = $State['PlaylistXMLs'];
    $a['Id'] = $State['Id'];
    echo json_encode($a);
}

?>
