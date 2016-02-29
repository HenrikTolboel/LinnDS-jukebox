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

interface ClientSocketInterface
{
    public static function getInstance($Socket, ListeningSocketInterface $ListeningSocket, $BufferLength = 2048);

    public function processMessage($message);

    public function process(); // Called immediately when the data is recieved. 

    public function connect();

    public function getSocket();

    public function Send($message); // Send message back to client

}

interface ListeningSocketInterface 
{
    public function getListeningSocket(); // returns the listening socket, that is ListeningSocketInterface listened on for new connections.

    public function getState(); // return the ServerState instance.

    public function newClient(); // returns a new client obeying ClientSocketInterface

    public function removeClient($client);

    public function findClient($socket);

    public function isClient($socket); // true if socket belongs to a client, false otherwise

    public function getAllSockets(); // returns array of listening socket + socket of all clients

    public function processClient($socket); // Read from socket and process received message via Client

    //public function SendAll($message); // Send message to all clients
    public function Send($message); // Send message to all clients
}


abstract class ClientSocket implements ClientSocketInterface 
{
    protected $socket;
    protected $listeningSocket;
    protected $maxBufferSize;

    protected function __construct($Socket, ListeningSocketInterface $ListeningSocket, $bufferLength = 2048) {

	$this->socket = $Socket;
	$this->listeningSocket = $ListeningSocket;
	$this->maxBufferSize = $bufferLength;

	$this->InitClientSocket();
    }

    protected function InitClientSocket()
    {
    }

    abstract public static function getInstance($Socket, ListeningSocketInterface $ListeningSocket, $BufferLength = 2048);

    abstract function processMessage($message);
    // true if handled OK, false otherwise

    private function ReadBlockFromSocket($socket)
    {
	// read until newline or 30000 bytes
	// socket_read will show errors when the client is disconnected, so silence the error messages
	//LogWrite("ReadBlockFromSocket: begin");
	$res = "";

	do {
	    $data = @socket_read($socket, 30000, PHP_NORMAL_READ);

	    if ($data === false) 
	    {
		if ($res != "")
		{
		    //LogWrite("ReadBlockFromSocket-false: end - res: $res");

		    return $res;
		}
		else
		{
		    //LogWrite("ReadBlockFromSocket-false: end - false");
		    return false;
		}
	    }

	    //LogWrite("ReadBlockFromSocket-addData: " . strlen($data));

	    $res .= $data;
	    $cnt = substr_count($res, '"');
	    //LogWrite("cnt: " . $cnt);
	} while ($cnt != 0 && $cnt % 2 != 0);

	// trim off the trailing/beginning white spaces
	$res = trim($res);

	//LogWrite("ReadBlockFromSocket: end - $res");
	return $res;
    }

    public function process()
    {
	//LogWrite("ClientSocket::process: $this->socket");
	//$this->dump();
	$data = $this->ReadBlockFromSocket($this->socket);

	if ($data === false) // disconnected
	    return false;

	if (empty($data))
	    return true;

	$DataHandled = $this->processMessage($data);

	if (! $DataHandled)
	{
	    socket_write($this->socket, "unknown command, or invalid argument\n");
	}

	return true;
    }

    public function connect()
    {
    }

    public function getSocket()
    {
	return $this->socket;
    }

    public function dump()
    {
	return print_r($this, true);
    }
}

class ListeningSocket implements ListeningSocketInterface 
{
    protected $clientSocket = 'ClientSocket'; // obeys ClientSocketInterface

    protected $maxBufferSize;

    protected $socket = null;

    protected $clients = array();

    private $serverState;

    protected $addr = -1;
    protected $port = -1;

    public function __construct($Addr, $Port, $ClientSocket, ServerState $ServerState, $bufferLength = 2048) 
    {

	$this->clientSocket = $ClientSocket;
	$this->serverState = $ServerState;
	$this->maxBufferSize = $bufferLength;

	$this->addr = $Addr;
	$this->port = $Port;

	$this->InitSocket();
    }

    protected function InitSocket()
    {
	$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)  or die("Failed: socket_create()");
	socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1) or die("Failed: socket_option()");
	socket_bind($this->socket, $this->addr, $this->port)          or die("Failed: socket_bind()");
	socket_listen($this->socket,20)                               or die("Failed: socket_listen()");
    }

    public function getState()
    {
	return $this->serverState;
    }

    public function getListeningSocket() 
    {
	if ($this->socket == null) die("Failed: You need to Listen() or Connect() on the ListeningSocket");
	return $this->socket;
    }

    public function newClient() 
    {
	$s = socket_accept($this->getListeningSocket());

	if ($s < 0) return false;

	$client = call_user_func(array($this->clientSocket, getInstance), $s, $this, $this->maxBufferSize);
	array_push($this->clients, $client);

	return $client;
    }

    function removeClient($client)
    {
	// remove client from $clients array
	$key = array_search($client, $this->clients);
	unset($this->clients[$key]);
    }

    function findClient($socket)
    {
	foreach ($this->clients as $client)
	{
	    if ($client->getSocket() === $socket)
		return $client;
	}
	return false;
    }

    public function isClient($socket)
    {
	$client = $this->findClient($socket);

	if ($client === false)
	{
	    //LogWrite("ListeningSocket::isClient - false");
	    return false;
	}
	//LogWrite("ListeningSocket::isClient - $socket");
	return true;
    }

    public function getAllSockets()
    {
	$A = array();

	array_push($A, $this->getListeningSocket());

	foreach ($this->clients as $client)
	{
	    array_push($A, $client->getSocket());
	}
	return $A;
    }

    public function processClient($socket)
    {
	$client = $this->findClient($socket);

	if ($client->process() === false)
	    $this->removeClient($client); // disconnected
    }

    //public function SendAll($message)
    public function Send($message)
    {
	foreach ($this->clients as $client)
	{
	    $client->Send($message);
	}
    }

    public function dump()
    {
	return print_r($this, true);
    }
}

class SocketServer 
{
    // The SocketServer handles a number of ListeningSockets from where new 
    // connections can be requested. Each ListeningSocket keeps its 
    // clients, and processes message on these.
    //

    private $listeningSockets = array(); // array of classes obeying ListeningSocketInterface

    private $select_timeout = null;

    function __construct()
    {
    }

    // Add a listening socket instance - must obey listeningSocketInterface
    public function addListeningSocket(ListeningSocketInterface $listeningSocket)
    {
	array_push($this->listeningSockets, $listeningSocket);
    }

    /** * Main processing loop */
    public function run() 
    {
	while(true) 
	{
	    $read = array();
	    foreach ($this->listeningSockets as $LS)
	    {
		$read = array_merge($read, $LS->getAllSockets());
	    }
	    $write = $except = null;

	    //LogWrite("SocketServer: socket_select: " . print_r($read, true));
	    
	    if (socket_select($read, $write, $except, $this->select_timeout) < 1)
		continue; // Nothing happened before timeout. If blocking (NULL) this should not happen.

	    foreach ($read as $socket) 
	    {
		//LogWrite("SocketServer: socket: $socket");
		foreach ($this->listeningSockets as $LS)
		{
		    //LogWrite("SocketServer: " . $LS->dump());
		    if ($LS->isClient($socket))
		    {
			$LS->processClient($socket);
			break;
		    }
		    else if ($socket === $LS->getListeningSocket()) // new connection
		    {
			$client = $LS->newClient();
			//LogWrite("SocketServer: newClient - " . $client->getSocket());

			if ($client === false)
			{
			    //LogWrite("SocketServer: newClient failed");
			}
			else
			    $client->connect();
			break;
		    }
		}
	    }
	    unset($read);
	}
    }
}

?>

