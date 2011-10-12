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
require_once("Functions.php");

$HOST = "127.0.0.1";
$PORT = 9050;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($socket, $HOST, $PORT);
$string = socket_read($socket, 10000); // Remove connection info sent from server...

socket_write($socket, "State\n");
$string = socket_read($socket, 10000);

$State = unserialize($string);

$cont = "";

$cont .= "<h2>Options</h2>"; 

//$cont .= '<p><form action="form.php" method="post"><div data-role="fieldcontain">';
$cont .= '<p><div data-role="fieldcontain">';
$cont .= '<label for="volume">Volume:</label>';
$cont .= '<input type="range" name="volume" id="volume" value="' . $State['Volume'] . '" min="0" max="' . $State['MAX_VOLUME'] . '" />';
//$cont .= '</form></div></p>';
$cont .= '</div></p>';

$cont .= '<p><a href="' . $State['ProductUrl'] . '">' . $State['ProductName'] .'-' . $State['ProductRoom'] . '</a></p>';

$str = Page("page_options", "Options", $cont, "Page Footer", "true");

file_put_contents($cachefilename, $str);
echo $str;

socket_close($socket);

require("footer.inc");

?>
