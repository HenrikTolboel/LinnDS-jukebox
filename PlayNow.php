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

   $preset = $_GET["preset"];
   
   $HOST = "127.0.0.1";
   $PORT = 9050;

   $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
   socket_connect($socket, $HOST, $PORT);

   $Str = "Jukebox PlayNow \"" . $preset . "\"";
   socket_write($socket, $Str . "\n");

   socket_close($socket);

?>
