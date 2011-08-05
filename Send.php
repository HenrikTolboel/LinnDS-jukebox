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

$value = $_GET["value"];
$action = $_GET["action"];

$HOST = "127.0.0.1";
$PORT = 9050;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($socket, $HOST, $PORT);
$string = socket_read($socket, 10000); // Remove connection info sent from server...

if ($action == "PlayNow") {
    $Str = "Jukebox PlayNow \"" . $value . "\"";
}
elseif ($action == "PlayNext") {
    $Str = "Jukebox PlayNext \"" . $value . "\"";
}
elseif ($action == "PlayLater") {
    $Str = "Jukebox PlayLater \"" . $value . "\"";
}
elseif ($action == "SetVolume") {
    $Str = "Volume Set \"" . $value . "\"";
}
socket_write($socket, $Str . "\n");

//socket_write($socket, "State\n");
//$string = socket_read($socket, 10000);

socket_close($socket);
//$State = unserialize($string);
//echo print_r($State, TRUE);

?>
