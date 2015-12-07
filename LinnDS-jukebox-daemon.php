#!/usr/bin/env php
<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2015-2016 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");
require_once("SocketServer.php");
require_once("LinnDSClientSocket.php");
require_once("LPECClientSocket.php");
require_once("ServerState.php");


class LinnDSListeningSocket extends ListeningSocket
{
    private $lPECListeningSocket;

    public function setLPECListeningSocket($LPEC)
    {
	$this->lPECListeningSocket = $LPEC;
    }

    public function getLPECListeningSocket()
    {
	return $this->lPECListeningSocket;
    }

}

class LPECListeningSocket extends ListeningSocket
{
    protected function InitSocket()
    {
	$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)  or die("Failed: socket_create()");
	socket_connect($this->socket, $this->addr, $this->port)       or die("Failed: socket_connect()");

	// Add it as the only client also
	$client = call_user_func(array($this->clientSocket, getInstance), $this->socket, $this, $this->maxBufferSize);
	array_push($this->clients, $client);
    }

    public function newClient() 
    {
	return reset($this->clients); // return first
    }

    function removeClient($client)
    {
    }

    public function getAllSockets()
    {
	$A = array();

	array_push($A, $this->getListeningSocket());

	return $A;
    }

    public function CallClients($func)
    {
	foreach ($this->clients as $client)
	{
	    call_user_func(array($client, $func));
	}
    }

    public function CallClients1($func, $v1)
    {
	foreach ($this->clients as $client)
	{
	    call_user_func(array($client, $func), $v1);
	}
    }

    public function CallClients4($func, $v1, $v2, $v3, $v4)
    {
	foreach ($this->clients as $client)
	{
	    call_user_func(array($client, $func), $v1, $v2, $v3, $v4);
	}
    }

}

SetLogFile(dirname($argv[0]) . "/logfile.txt");

LogWrite("############################## Restarted ######################################");

$serverState = ServerState::getInstance();

//LogWrite($serverState->dump());


$LS1 = new LPECListeningSocket($LINN_HOST, $LINN_PORT, 'LPECClientSocket', $serverState, 30000);

$LS2 = new LinnDSListeningSocket(0, 9050, 'LinnDSClientSocket', $serverState, 30000);
$LS2->setLPECListeningSocket($LS1);

//$LS1->dump();
//$LS2->dump();

$SS = new SocketServer();

$SS->addListeningSocket($LS1);
$SS->addListeningSocket($LS2);

LogWrite("LinnDS-jukebox-daemon starts...");
try {
    $SS->run();
}
catch (Exception $e) {
    LogWrite($e->getMessage());
}

?>

