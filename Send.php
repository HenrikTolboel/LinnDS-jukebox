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

if ($action == "State") {
    $Str = "State";
}
elseif ($action == "PlayNow") {
    $value = $_GET["preset"];
    $Str = "Jukebox PlayNow \"" . $value . "\"";
}
elseif ($action == "PlayNext") {
    $value = $_GET["preset"];
    $Str = "Jukebox PlayNext \"" . $value . "\"";
}
elseif ($action == "PlayLater") {
    $value = $_GET["preset"];
    $Str = "Jukebox PlayLater \"" . $value . "\"";
}
elseif ($action == "SetVolume") {
    $value = $_GET["volume"];
    $Str = "Volume Set \"" . $value . "\"";
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

if ($action == "State") {
    $string = socket_read($socket, 10000);
}

socket_close($socket);

if ($action == "State") {
    $State = unserialize($string);
    //echo print_r($State, TRUE);
    $a['MAX_VOLUME'] = $State['MAX_VOLUME'];
    $a['Volume'] = $State['Volume'];
    echo json_encode($a);
}

?>
