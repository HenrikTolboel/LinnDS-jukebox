#!/usr/bin/php
<?php
/*!
* LinnDS-jukebox-daemon
*
* Copyright (c) 2011-2015 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");

// Debug write out.... Higher number 1,2,3,.. means more output
$DEBUG = 2;

$Log_file = dirname($argv[0]) . "/logfile.txt";

// Create a socket to your linn LPEC interface, and connect...
$lpec_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($lpec_socket, $LINN_HOST, $LINN_PORT);

// Create a socket for clients to register on - listen on port
$port = 9050;

// create a streaming socket, of type TCP/IP
$new_client_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// set the option to reuse the port
socket_set_option($new_client_socket, SOL_SOCKET, SO_REUSEADDR, 1);

// "bind" the socket to the address to "localhost", on port $port
// so this means that all connections on this port are now our resposibility to send/recv data, disconnect, etc..
socket_bind($new_client_socket, 0, $port);

// start listen for connections
socket_listen($new_client_socket);

// Queue is a queue of outstanding commands to be sent to Linn.
// The currently executing command is still in the queue, removed when the
// response commes.
$Queue = array();
// AwaitResponse tells whether we miss a response before sending next
// command.
$State['AwaitResponse'] = 0;

// State contains the "accumulated" state of the linn device.
$State = array();
$State['MAX_VOLUME'] = 60;
$State['Volume'] = -1;
$State['IdArray'] = array('0');
$State['Id'] = 0;
$State['NewId'] = 0;
$State['RevNo'] = 0;
$State['PlaylistURLs'] = array();
$State['PlaylistXMLs'] = array();
$State['SourceName'] = array();

// SubscribeType tells the mapping between "EVENT <digits> XXX" subscribed
// to protokol (e.g. "Ds/Playlist")
// <digits> -> "Ds/Playlist"
// Used to make fewer regular expressions in the EVENT section
$SubscribeType = array();
$SubscribeType['Ds/Product'] = -1;
$SubscribeType['Ds/Playlist'] = -1;
$SubscribeType['Ds/Jukebox'] = -1;
$SubscribeType['Ds/Volume'] = -1;
$SubscribeType['Ds/Radio'] = -1;
$SubscribeType['Ds/Info'] = -1;
$SubscribeType['Ds/Time'] = -1;

$LogFile = fopen($Log_file, 'a');

LogWrite("############################## Restarted ######################################");

function DebugWrite($level, $Str)
{
    global $DEBUG;

    if ($DEBUG >= $level)
	LogWrite("DEBUG:$level: " . $Str);
}

function LogWrite($Str)
{
    global $LogFile;
    global $Queue;
    // Write to log
    //print date("D M j G:i:s T Y") . " : " . $Str . "\n";
    //print $Str . "\n";
    fwrite($LogFile, $Str . "\n");
    //print_r($Queue);
}

function CreateDatabase($DatabaseFileName)
{
    $DB = array();
    $DB[FILENAME] = $DatabaseFileName;
    $DB[DATABASE] = new SQLite3($DB[FILENAME], SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);

    $DB[DATABASE]->exec('CREATE TABLE IF NOT EXISTS Queue (LinnId INTEGER, Preset INTEGER, TrackSeq INTEGER, URL STRING, XML STRING)');
    $DB[DATABASE]->exec('CREATE TABLE IF NOT EXISTS State (Id STRING, Value STRING)');
    $DB[DATABASE]->exec('CREATE TABLE IF NOT EXISTS Sequence (Seq INTEGER, LinnId INTEGER)');


    $DB[INSERT_QUEUE_STMT] = $DB[DATABASE]->prepare('INSERT INTO Queue (LinnId, Preset, TrackSeq, URL, XML) VALUES (:LinnId, :Preset, :TrackSeq, :URL, :XML)');
    $DB[UPDATE_QUEUE_STMT] = $DB[DATABASE]->prepare('UPDATE Queue set LinnId = :LinnId where (LinnId == :LinnId) OR (LinnId == -1 and URL == :URL)');
    $DB[DELETE_QUEUE_STMT] = $DB[DATABASE]->prepare('DELETE FROM Queue');

    $DB[INSERT_STATE_STMT] = $DB[DATABASE]->prepare('INSERT INTO State (Id, Value) VALUES (:Id, :Value)');
    $DB[UPDATE_STATE_STMT] = $DB[DATABASE]->prepare('UPDATE State set Value = :Value WHERE Id = :Id');

    $DB[INSERT_SEQUENCE_STMT] = $DB[DATABASE]->prepare('INSERT INTO Sequence (Seq, LinnId) VALUES (:Seq, :LinnId)');
    $DB[DELETE_SEQUENCE_STMT] = $DB[DATABASE]->prepare('DELETE FROM Sequence');
    return $DB;
}

function InsertQueueDB($DB, $LinnId, $Preset, $TrackSeq, $URL, $XML)
{
    $DB[INSERT_QUEUE_STMT]->bindParam(':LinnId', $LinnId);
    $DB[INSERT_QUEUE_STMT]->bindParam(':Preset', $Preset);
    $DB[INSERT_QUEUE_STMT]->bindParam(':TrackSeq', $TrackSeq);
    $DB[INSERT_QUEUE_STMT]->bindParam(':URL', $URL);
    $DB[INSERT_QUEUE_STMT]->bindParam(':XML', $XML);

    $result = $DB[INSERT_QUEUE_STMT]->execute();

    $r = $DB[DATABASE]->changes();
    LogWrite("InsertQueueDB: $LinnId, $Preset, $TrackSeq, $URL -> $r");
    $DB[INSERT_QUEUE_STMT]->reset();
}

function UpdateQueueDB($DB, $LinnId, $Preset, $TrackSeq, $URL, $XML)
{
    $DB[UPDATE_QUEUE_STMT]->bindParam(':LinnId', $LinnId);
    $DB[UPDATE_QUEUE_STMT]->bindParam(':URL', $URL);

    $result = $DB[UPDATE_QUEUE_STMT]->execute();

    $r = $DB[DATABASE]->changes();
    LogWrite("UpdateQueueDB: $LinnId, $Preset, $TrackSeq, $URL -> $r");
    if ($DB[DATABASE]->changes() < 1)
    {
	InsertQueueDB($DB, $LinnId, $Preset, $TrackSeq, $URL, $XML);
    }

    $DB[UPDATE_QUEUE_STMT]->reset();
}

function DeleteQueueDB($DB)
{
    $result = $DB[DELETE_QUEUE_STMT]->execute();

    $r = $DB[DATABASE]->changes();
    LogWrite("DeleteQueueDB: -> $r");

    $DB[DELETE_QUEUE_STMT]->reset();
}

function SetStateDB($DB, $Id, $Value)
{
    $DB[UPDATE_STATE_STMT]->bindParam(':Id', $Id);
    $DB[UPDATE_STATE_STMT]->bindParam(':Value', $Value);

    $result = $DB[UPDATE_STATE_STMT]->execute();

    $r = $DB[DATABASE]->changes();
    LogWrite("SetStateDB: $Id, $Value -> $r");
    if ($DB[DATABASE]->changes() < 1)
    {
	$DB[INSERT_STATE_STMT]->bindParam(':Id', $Id);
	$DB[INSERT_STATE_STMT]->bindParam(':Value', $Value);

	$result = $DB[INSERT_STATE_STMT]->execute();

	$r = $DB[DATABASE]->changes();
	LogWrite("SetStateDB-Insert: $Id, $Value -> $r");
	$DB[INSERT_STATE_STMT]->reset();
    }

    $DB[UPDATE_STATE_STMT]->reset();
}

function InsertSequenceDB($DB, $Seq, $LinnId)
{
    $DB[INSERT_SEQUENCE_STMT]->bindParam(':Seq', $Seq);
    $DB[INSERT_SEQUENCE_STMT]->bindParam(':LinnId', $LinnId);

    $result = $DB[INSERT_SEQUENCE_STMT]->execute();

    $r = $DB[DATABASE]->changes();
    LogWrite("InsertSequenceDB: $Seq, $LinnId -> $r");
    $DB[INSERT_SEQUENCE_STMT]->reset();
}

function DeleteSequenceDB($DB)
{
    $result = $DB[DELETE_SEQUENCE_STMT]->execute();

    $r = $DB[DATABASE]->changes();
    LogWrite("DeleteSequenceDB: -> $r");

    $DB[DELETE_SEQUENCE_STMT]->reset();
}

function strposall($haystack,$needle)
{ 
    /** 
    * strposall 
    * 
    * Find all occurrences of a needle in a haystack 
    * 
    * @param string $haystack 
    * @param string $needle 
    * @return array or false 
    */ 

    $s=0; 
    $i=0; 

    while (is_integer($i)){ 
	
	$i = strpos($haystack,$needle,$s); 
	
	if (is_integer($i)) { 
	    $aStrPos[] = $i; 
	    $s = $i+strlen($needle); 
	} 
    } 
    if (isset($aStrPos)) { 
	return $aStrPos; 
    } else { 
	return false; 
    } 
} 


function getParameters($str) {
    //format: [ACTION | RESPONSE] "Param1" "Param2" "Param3" ...
    //Result: array or false
    //        a[0] = Param1 ...

    $a = strposall($str, '"');

    if ($a === false)
	return false;

    //LogWrite("getParameters: " . print_r($a, true));

    $i = 0;
    $cnt = count($a);

    while ($i < $cnt) {
	$aStr[] = substr($str, $a[$i]+1, $a[$i+1] - $a[$i] -1);
	$i += 2;
    }

    if (isset($aStr)) { 
	//LogWrite("getParameters: " . print_r($aStr, true));
	return $aStr; 
    } 
    else { 
	return false; 
    } 
}

function getEvent($str) {
    //format: EVENT <subscribe-no> <seq-no> Key1 "Value1" Key2 "Value2" ...
    //Result: associative array
    //        b[Key1] = Value1 ...

    $a = strposall($str, " ");
    $start = $a[2]+1;

    $b = array();

    $end = strlen($str);

    $i = $start;

    while ($i < $end) {
	$e = strpos($str, " ", $i);
	$key = substr($str, $i, $e-$i);
	$i = $e+2;
	$e = strpos($str, "\"", $i);
	$value=substr($str, $i, $e-$i);
	$i = $e+2;

	$b[$key] = $value;
    }

    LogWrite("getEvent: " . print_r($b, true));
    return $b;
}

function Send($Str)
{
    global $Queue;
    global $lpec_socket;
    global $State;

    // Add to queue. if not awaiting responses, then send front
    if (strlen($Str) > 0)
	array_push($Queue, $Str);
    else
	$State['AwaitResponse'] = 0;

    $Res = true;
    if ($State['AwaitResponse'] == 0 && count($Queue) > 0)
    {
	$S = array_shift($Queue);
	$S = str_replace("%NewId%", strval($State['NewId']), $S);
	LogWrite("Send: " . $S);
	$sent = socket_write($lpec_socket, $S . "\n");
	if ($sent === false)
	{
	    $Res = false;
	    LogWrite("Send: socket_write failed with \"" . $S . "\"");
	}
	array_unshift($Queue, $S); // We leave the sent item in Queue - removed when we get the response
	$State['AwaitResponse'] = 1;
    }
    $State['CountQueue'] = count($Queue);
    return $Res;
}

function PresetURL($preset)
{
    global $DATABASE_FILENAME;

    $db = new SQLite3($DATABASE_FILENAME);
    $stmt = $db->prepare("SELECT URI FROM Album WHERE Preset == :q1");
    $stmt->bindValue(":q1", $preset);

    $result = $stmt->execute();

    $R = array();
    $i = 0;

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$R[$i] = $row;
	$i++;
    }

    $stmt->close();
    $db->close();

    return AbsolutePath(ProtectPath($R[0][URI]));
}


function NumberOfTracks($Preset)
{
    global $DATABASE_FILENAME;

    $db = new SQLite3($DATABASE_FILENAME);
    $stmt = $db->prepare("SELECT NoTracks FROM Album WHERE Preset == :q1");
    $stmt->bindValue(":q1", $Preset);

    $result = $stmt->execute();

    $R = array();
    $i = 0;

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$R[$i] = $row;
	$i++;
    }

    $stmt->close();
    $db->close();

    return $R[0][NoTracks];
}

function PrepareXML($xml)
{
    $xml = AbsoluteURL($xml); // late binding of http server

    $xml = htmlspecialchars(str_replace(array("\n", "\r"), '', $xml));
    $xml = str_replace("&amp;#", "&#", $xml); // e.g. danish "å" is transcoded from "&#E5;" to "&amp;#E5;" so we convert back
    return $xml;
}

function InsertDIDL_list($DB, $Preset, $TrackSeq, $AfterId)
{
    global $State;
    global $NL;

    $DIDL_URL = PresetURL($Preset);
    $Res = true;
    LogWrite("InsertDIDL_list: " . $DIDL_URL . ", " . $TrackSeq . ", " . $AfterId);

    $xml = simplexml_load_file($DIDL_URL);

    $xml->registerXPathNamespace('didl', 'urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/');
    $URLs = $xml->xpath('//didl:res');

    $DIDLs = $xml->xpath('//didl:DIDL-Lite');

    if ($TrackSeq == 0) {
	InsertQueueDB($DB, -1, $Preset, 1, PrepareXML($URLs[0][0]), PrepareXML($DIDLs[0]->asXML()));
	if (Send("ACTION Ds/Playlist 1 Insert \"" . $AfterId . "\" \"" . PrepareXML($URLs[0][0]) . "\" \"" . PrepareXML($DIDLs[0]->asXML()) . "\"") == false)
	    $Res = false;
	if (Play() == false)
	    $Res = false;
	for ($i = 1; $i < sizeof($URLs); $i++)
	{
	    InsertQueueDB($DB, -1, $Preset, $i+1, PrepareXML($URLs[$i][0]), PrepareXML($DIDLs[$i]->asXML()));
	    if (Send("ACTION Ds/Playlist 1 Insert \"%NewId%\" \"" . PrepareXML($URLs[$i][0]) . "\" \"" . PrepareXML($DIDLs[$i]->asXML()) . "\"") == false)
	    {
		$Res = false;
	    }
	}
    }
    else
    {
	$No = $TrackSeq -1;
	InsertQueueDB($DB, -1, $Preset, $TrackSeq, PrepareXML($URLs[$No][0]), PrepareXML($DIDLs[$No]->asXML()));
	if (Send("ACTION Ds/Playlist 1 Insert \"" . $AfterId . "\" \"" . PrepareXML($URLs[$No][0]) . "\" \"" . PrepareXML($DIDLs[$No]->asXML()) . "\"") == false)
	    $Res = false;
	if (Play() == false)
	    $Res = false;
    }
    IncrRevNoDB($DB);
    return $Res;
}

function CheckPlaylist($DB)
{
    global $State;

    $Res = true;
    DeleteSequenceDB($DB);
    $seq = 0;
    foreach ($State['IdArray'] as $value)
    {
	InsertSequenceDB($DB, $seq, $value);
	$seq++;
	if (! isset($State['PlaylistURLs'][$value]))
	{
	    if (Send("ACTION Ds/Playlist 1 Read \"" . $value . "\"") == false)
		$Res = false;
	}
    }
    IncrRevNoDB($DB);
    return $Res;
}

function ReadBlockFromSocket($read_sock)
{
    global $NL;

    // read until newline or 30000 bytes
    // socket_read while show errors when the client is disconnected, so silence the error messages
    //LogWrite("ReadBlockFromSocket: begin");
    $res = "";

    do {
	$data = @socket_read($read_sock, 30000, PHP_NORMAL_READ);

	if ($data === false) {
	    if ($res != "")
		return $res;
	    else
		return $data;
	}

	//LogWrite("ReadBlockFromSocket: " . strlen($data));

	$res .= $data;
	$cnt = substr_count($res, '"');
	//LogWrite("cnt: " . $cnt);
    } while ($cnt != 0 && $cnt % 2 != 0);

    return $res;
}

function DeleteAll($DB)
{
    global $State;

    $Res = true;
    if (Send("ACTION Ds/Playlist 1 DeleteAll") == false)
	$Res = false;
    DeleteQueueDB($DB);
    $State['PlaylistURLs'] = array();
    $State['PlaylistXMLs'] = array();
    $State['IdArray'] = array('0');
    $State['Id'] = 0;
    $State['NewId'] = 0;
    return $Res;
}

function IncrRevNoDB($DB)
{
    global $State;

    $State['RevNo'] = $State['RevNo'] + 1;
    SetStateDB($DB, "RevNo", $State['RevNo']);

}


function SelectPlaylist()
{
    global $State;

    $Res = true;
    if ($State['Standby'] == 'true')
    {
	if (Send('ACTION Ds/Product 1 SetStandby "false"') == false)
	    $Res = false;
	$State['Standby'] = false;
	if (Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['Playlist'] . '"') == false)
	    $Res = false;
    }
    elseif ($State['SourceIndex'] != $State['SourceName']['Playlist'])
    {
	if (Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['Playlist'] . '"') == false)
	    $Res = false;
    }
    return $Res;
}

function Stop()
{
    global $State;

    $Res = true;
    if ($State['TransportState'] != "Stopped")
    {
	if (Send("ACTION Ds/Playlist 1 Stop") == false)
	    $Res = false;
	$State['TransportState'] = "Stopped";
    }
    return $Res;
}

function Play()
{
    global $State;

    $Res = true;
    if ($State['TransportState'] == "Stopped")
    {
	if (Send("ACTION Ds/Playlist 1 Play") == false)
	    $Res = false;
	$State['TransportState'] = "Starting";
    }
    return $Res;
}

LogWrite("LinnDS-jukebox-daemon starts...");

// create a list of all the clients that will be connected to us..
// add the listening socket to this list
$clients = array($new_client_socket, $lpec_socket);

$DB = CreateDatabase($QUEUEDB_FILENAME);

IncrRevNoDB($DB);
$Continue = true;
while ($Continue) {
$read = $clients;  // reset list to all sockets

if (socket_select($read, $write = NULL, $except = NULL, NULL) < 1)
    continue;

// check if there is a client trying to connect
if (in_array($new_client_socket, $read)) {
    // accept the client, and add him to the $clients array
    $clients[] = $newsock = socket_accept($new_client_socket);

    // send the client a welcome message
    socket_write($newsock, "Welcome to LinnDS-jukebox-daemon\n".
    "There are ".(count($clients) - 1)." client(s) connected\n");

    socket_getpeername($newsock, $ip);
    LogWrite("New client connected: {$ip}");

    // remove the listening socket from the clients-with-data array
    $key = array_search($new_client_socket, $read);
    unset($read[$key]);
}

// loop through all the clients that have data to read from
foreach ($read as $read_sock) {
    $data = ReadBlockFromSocket($read_sock);
 
    // check if the client is disconnected
    if ($data === false) {

	// remove client for $clients array
	$key = array_search($read_sock, $clients);
	unset($clients[$key]);
	LogWrite("client disconnected.");
	// continue to the next client to read from, if any
	continue;
    }
 
    // trim off the trailing/beginning white spaces
    $data = trim($data);
 
// check if there is any data after trimming off the spaces
if (!empty($data)) {
     $DataHandled = false;
     if ($DEBUG > 1)
	LogWrite($data);
     if (strpos($data, "ALIVE Ds") !== false)
     {
	Send("SUBSCRIBE Ds/Product");
	$DataHandled = true;
     }
     elseif (strpos($data, "ALIVE") !== false)
     {
	LogWrite("ALIVE ignored : " . $data);
	$DataHandled = true;
     }
     elseif (strpos($data, "ERROR") !== false)
     {
	LogWrite("ERROR ignored : " . $data);
	$DataHandled = true;
     }
     elseif (strpos($data, "SUBSCRIBE") !== false)
     {
	// SUBSCRIBE are sent by Linn when a SUBSCRIBE finishes, thus
	// we send the possible next command (Send) after removing
	// previous command.
	// We record the Number to Subscribe action in the array to
	// help do less work with the events.
	$front = array_shift($Queue);
	if ($DEBUG > 1)
	   LogWrite("Command: " . $front . " -> " . $data);
	$S1 = substr($front, 10);
	$S2 = substr($data, 10);
	$SubscribeType[$S1] = $S2;
	Send("");
	$DataHandled = true;
     }
     elseif (strpos($data, "RESPONSE") !== false)
     {
	// RESPONSE are sent by Linn when an ACTION finishes, thus we
	// send the possible next command (Send) after removing
	// previous command.
	$front = array_shift($Queue);
	if ($DEBUG > 0)
	   LogWrite("Command: " . $front . " -> " . $data);

	if (strpos($front, "ACTION Ds/Product 1 Source ") !== false) {
	    //ACTION Ds/Product 1 Source \"(\d+)\"
	    //RESPONSE \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"
	    $F = getParameters($front);
	    $D = getParameters($data);

	    //$State['Source_SystemName'][$F[0]] = $D[0];
	    //$State['Source_Type'][$F[0]] = $D[1];
	    //$State['Source_Name'][$F[0]] = $D[2];
	    //$State['Source_Visible'][$F[0]] = $D[3];

	    $State['SourceName'][$D[2]] = $F[0];

	    if ($D[1] == "Playlist")
	    {
		// We have the Playlist service. subscribe...
		Send("SUBSCRIBE Ds/Playlist");
		//Send("SUBSCRIBE Ds/Jukebox");
	    }
	    elseif ($D[1] == "Radio")
	    {
		// We have the Radio service. subscribe...
		//Send("SUBSCRIBE Ds/Radio");
	    }
	}
	elseif (strpos($front, "ACTION Ds/Playlist 1 Read ") !== false) {
	    //ACTION Ds/Playlist 1 Read \"(\d+)\"
	    //RESPONSE \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"
	    $F = getParameters($front);
	    $D = getParameters($data);

	    //$State['PlaylistURLs'][$F[0]] = htmlspecialchars_decode($D[0]);
	    //$State['PlaylistXMLs'][$F[0]] = htmlspecialchars_decode($D[1]);
	    $State['PlaylistURLs'][$F[0]] = $D[0];
	    $State['PlaylistXMLs'][$F[0]] = $D[1];
	    UpdateQueueDB($DB, $F[0], -1, -1, $D[0], $D[1]);
	}
	elseif (strpos($front, "ACTION Ds/Playlist 1 Insert ") !== false) {
	    //ACTION Ds/Playlist 1 Insert \"(\d+)\" \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"
	    //RESPONSE \"([[:ascii:]]+?)\"
	    $F = getParameters($front);
	    $D = getParameters($data);

	    $State['NewId'] = $D[0];
	    $State['PlaylistURLs'][$State['NewId']] = $F[1];
	    $State['PlaylistXMLs'][$State['NewId']] = $F[2];
	    UpdateQueueDB($DB, $D[0], -1, -1, $F[1], $F[2]);
	}
	elseif (strpos($front, "ACTION Ds/Playlist 1 IdArray") !== false) {
	    //ACTION Ds/Playlist 1 IdArray
	    //RESPONSE \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"
	    $F = getParameters($front);
	    $D = getParameters($data);

	    $State['IdArray_Token'] = $D[0];
	    $State['IdArray_base64'] = $D[1];
	    $State['IdArray'] = unpack("N*", base64_decode($D[1]));
	    CheckPlaylist($DB);
	}

	Send("");
	$DataHandled = true;
     }
     elseif (strpos($data, "EVENT ") !== false)
     {
	// EVENTs are sent by Your linn - those that were subscribed
	// to. We think the below ones are interesting....

	$E = getEvent($data);
	if (strpos($data, "EVENT " . $SubscribeType['Ds/Product']) !== false)
	{
	   if (strpos($data, "SourceIndex ") !== false)
	   {
	      $State['SourceIndex'] = $E[SourceIndex];
	   }
	   if (strpos($data, "ProductModel ") !== false)
	   {
	      $State['ProductModel'] = $E[ProductModel];
	   }
	   if (strpos($data, "ProductName ") !== false)
	   {
	      $State['ProductName'] = $E[ProductName];
	   }
	   if (strpos($data, "ProductRoom ") !== false)
	   {
	      $State['ProductRoom'] = $E[ProductRoom];
	   }
	   if (strpos($data, "ProductType ") !== false)
	   {
	      $State['ProductType'] = $E[ProductType];
	   }
	   if (strpos($data, "Standby ") !== false)
	   {
	      $State['Standby'] = $E[Standby];
	      SetStateDB($DB, "Standby", $E[Standby]);
	   }
	   if (strpos($data, "ProductUrl ") !== false)
	   {
	      $State['ProductUrl'] = $E[ProductUrl];
	   }
	   if (strpos($data, "Attributes ") !== false)
	   {
	      $State['Attributes'] = $E[Attributes];
	      if (strpos($State['Attributes'], "Volume") !== false) // We have a Volume service
	      {
		Send("SUBSCRIBE Ds/Volume");
	      }
	      if (strpos($State['Attributes'], "Info") !== false) // We have a Info service
	      {
		//Send("SUBSCRIBE Ds/Info");
	      }
	      if (strpos($State['Attributes'], "Time") !== false) // We have a Time service
	      {
		//Send("SUBSCRIBE Ds/Time");
	      }
	   }
	   if (strpos($data, "SourceCount ") !== false)
	   {
	      for ($i = 0; $i < $E[SourceCount]; $i++) {
		 Send("ACTION Ds/Product 1 Source \"" . $i . "\"");
	      }
	   }
	   $DataHandled = true;
	}
	elseif (strpos($data, "EVENT " . $SubscribeType['Ds/Playlist']) !== false)
	{
	   if (strpos($data, "TransportState ") !== false)
	   {
	      $State['TransportState'] = $E[TransportState];
	      SetStateDB($DB, "TransportState", $E[TransportState]);
	   }
	   if (strpos($data, "Id ") !== false)
	   {
	      $State['Id'] = $E[Id];
	      SetStateDB($DB, "LinnId", $E[Id]);
	   }
	   if (strpos($data, "IdArray ") !== false)
	   {
	      $State['IdArray_base64'] = $E[IdArray];
	      $State['IdArray'] = unpack("N*", base64_decode($State['IdArray_base64']));
	      CheckPlaylist($DB);
	   }
	   if (strpos($data, "Shuffle ") !== false)
	   {
	      $State['Shuffle'] = $E[Shuffle];
	   }
	   if (strpos($data, "Repeat ") !== false)
	   {
	      $State['Repeat'] = $E[Repeat];
	   }
	   if (strpos($data, "TrackDuration ") !== false)
	   {
	      $State['TrackDuration'] = $E[TrackDuration];
	   }
	   if (strpos($data, "TrackCodecName ") !== false)
	   {
	      $State['TrackCodecName'] = $E[TrackCodecName];
	   }
	   if (strpos($data, "TrackSampleRate ") !== false)
	   {
	      $State['TrackSampleRate'] = $E[TrackSampleRate];
	   }
	   if (strpos($data, "TrackBitRate ") !== false)
	   {
	      $State['TrackBitRate'] = $E[TrackBitRate];
	   }
	   if (strpos($data, "TrackLossless ") !== false)
	   {
	      $State['TrackLossless'] = $E[TrackLossless];
	   }
	   $DataHandled = true;
	}
	elseif (strpos($data, "EVENT " . $SubscribeType['Ds/Volume']) !== false)
	{
	   if (strpos($data, "Volume ") !== false)
	   {
	      $State['Volume'] = $E[Volume];
	      SetStateDB($DB, "Volume", $E[Volume]);
	   }
	   if (strpos($data, "Mute ") !== false)
	   {
	      $State['Mute'] = $E[Mute];
	      SetStateDB($DB, "Mute", $E[Mute]);
	   }
	   $DataHandled = true;
	}
	elseif (strpos($data, "EVENT " . $SubscribeType['Ds/Jukebox']) !== false)
	{
	   if (strpos($data, "CurrentPreset ") !== false)
	   {
	      $State['CurrentPreset'] = $E[CurrentPreset];
	   }
	   if (strpos($data, "CurrentBookmark ") !== false)
	   {
	      $State['CurrentBookmark'] = $E[CurrentBookmark];
	   }
	   $DataHandled = true;
	}
	else
	{
	    LogWrite("UNKNOWN : " . $data);
	    $DataHandled = true;
	}

	if ($DEBUG > 0)
	{
	   //LogWrite("State:");
	   //print_r($State);
	}
     }
     elseif (strpos($data, "Jukebox") !== false)
     {
	// Here things happens - we execute the actions sent from the
	// application, by issuing a number of ACTIONs.
	$D = getParameters($data);

	if (strpos($data, "Jukebox PlayNow ") !== false) {
	    //Jukebox PlayNow \"(\d+)\" \"(\d+)\"
	    $JukeBoxPlay = $D[0];
	    $JukeBoxTrack = $D[1];
	    LogWrite("JukeBoxPlayNow: " . $JukeBoxPlay . ", " . $JukeBoxTrack);

	    if (SelectPlaylist() == false)
		$Continue = false;

	    if (Stop() == false)
		$Continue = false;

	    if (DeleteAll($DB) == false)
		$Continue = false;
	    if (InsertDIDL_list($DB, $JukeBoxPlay, $JukeBoxTrack, 0) == false)
		$Continue = false;

	    //Send("ACTION Ds/Jukebox 3 SetCurrentPreset \"" . $JukeBoxPlay . "\"");

	    if (Play() == false)
		$Continue = false;
	    if (Send("ACTION Ds/Playlist 1 IdArray") == false)
		$Continue = false;
	    $DataHandled = true;
	}
	elseif (strpos($data, "Jukebox PlayNext ") !== false) {
	    //Jukebox PlayNext \"(\d+)\" \"(\d+)\"
	    $JukeBoxPlay = $D[0];
	    $JukeBoxTrack = $D[1];
	    LogWrite("JukeBoxPlayNext: " . $JukeBoxPlay . ", " . $JukeBoxTrack);

	    if (SelectPlaylist() == false)
		$Continue = false;

	    if (InsertDIDL_list($DB, $JukeBoxPlay, $JukeBoxTrack, $State['Id']) == false)
		$Continue = false;

	    if (Play() == false)
		$Continue = false;
	    if (Send("ACTION Ds/Playlist 1 IdArray") == false)
		$Continue = false;

	    if ($DEBUG > 0)
	    {
		//LogWrite($data);
		//print_r($State);
	    }
	    $DataHandled = true;
	}
	elseif (strpos($data, "Jukebox PlayLater ") !== false) {
	    //Jukebox PlayLater \"(\d+)\" \"(\d+)\"
	    $JukeBoxPlay = $D[0];
	    $JukeBoxTrack = $D[1];
	    LogWrite("JukeBoxPlayLater: " . $JukeBoxPlay . ", " . $JukeBoxTrack);

	    if (SelectPlaylist() == false)
		$Continue = false;

	    if (InsertDIDL_list($DB, $JukeBoxPlay, $JukeBoxTrack, end($State['IdArray'])) == false)
		$Continue = false;


	    if (Play() == false)
		$Continue = false;
	    Send("ACTION Ds/Playlist 1 IdArray");

	    if ($DEBUG > 0)
	    {
	    //LogWrite($data);
	    //print_r($State);
	    }
	    $DataHandled = true;
	}
	elseif (strpos($data, "Jukebox PlayRandomTracks ") !== false) {
		//Jukebox PlayRandomTracks \"(\d+)\" \"(\d+)\"
		$JukeBoxFirstAlbum = $D[0];
		$JukeBoxLastAlbum = $D[1];
		LogWrite("JukeBoxPlayRandomTracks: " . $JukeBoxFirstAlbum . ", " . $JukeBoxLastAlbum);

		if (SelectPlaylist() == false)
		    $Continue = false;

		if ($State['TransportState'] == "Stopped")
		{
		    if (DeleteAll($DB) == false)
			$Continue = false;
		}

		for ($i = 0; $i < 50; $i++) {
		    $RandomPreset = rand($JukeBoxFirstAlbum, $JukeBoxLastAlbum);
		    $RandomTrack = rand(1, NumberOfTracks($RandomPreset));
		    if ($i == 0)
		    {
			if (InsertDIDL_list($DB, $RandomPreset, $RandomTrack, end($State['IdArray'])) == false)
			    $Continue = false;
		    }
		    else
		    {
			if (InsertDIDL_list($DB, $RandomPreset, $RandomTrack, "%NewId%") == false)
			    $Continue = false;
		    }
		}

		if (Play() == false)
		    $Continue = false;
		Send("ACTION Ds/Playlist 1 IdArray");

		if ($DEBUG > 0)
		{
		    //LogWrite($data);
		    //print_r($State);
		}
		$DataHandled = true;
            }
         }
         elseif (strpos($data, "Volume") !== false) {

	    $D = getParameters($data);
            // Here things happens - we execute the actions sent from the
            // application, by issuing a number of ACTIONs.

	    if (strpos($data, "Volume Set ") !== false) {
		//Volume Set \"(\d+)\"
		$value = $D[0];
		if ($value > $State['MAX_VOLUME'])
		    $value = $State['MAX_VOLUME'];
		if ($value != $State['Volume'] && $value != "")
		{
		    LogWrite("VolumeSet: " . $value);
		    if (Send("ACTION Ds/Volume 1 SetVolume \"" . $value . "\"") == false)
			$Continue = false;
		    $State['Volume'] = $value;
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Volume Incr5") !== false) {
		//Volume Incr5
		if ($State['Volume'] < $State['MAX_VOLUME'] -5)
		{
		    LogWrite("VolumeIncr5: ");
		    if (Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		    if (Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		    if (Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		    if (Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		    if (Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		}
		else
		{
		    LogWrite("VolumeIncr: IGNORED MAX_VOLUME REACHED");
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Volume Incr") !== false) {
		//Volume Incr
		if ($State['Volume'] < $State['MAX_VOLUME'])
		{
		    LogWrite("VolumeIncr: ");
		    if (Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		}
		else
		{
		    LogWrite("VolumeIncr: IGNORED MAX_VOLUME REACHED");
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Volume Decr5") !== false) {
		//Volume Decr5
		LogWrite("VolumeDecr: ");
		if (Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		if (Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		if (Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		if (Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		if (Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		$DataHandled = true;
            }
	    elseif (strpos($data, "Volume Decr") !== false) {
		//Volume Decr
		LogWrite("VolumeDecr: ");
		if (Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		$DataHandled = true;
            }
	    elseif (strpos($data, "Volume Reset") !== false) {
		//Volume Reset
		LogWrite("VolumeReset: ");
		$value = 35;
		LogWrite("VolumeSet: " . $value);
		if (Send("ACTION Ds/Volume 1 SetVolume \"" . $value . "\"") == false)
		    $Continue = false;
		$State['Volume'] = $value;
		$DataHandled = true;
            }
	 }
         elseif (strpos($data, "Control") !== false) {
            // Here things happens - we execute the actions sent from the
            // application, by issuing a number of ACTIONs.

	    if (strpos($data, "Control Play") !== false) {
		//Control Play
		if ($State['TransportState'] == "Stopped")
		{
		    LogWrite("ControlPlay: ");
		    if (Play() == false)
			$Continue = false;
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Control Pause") !== false) {
		//Control Pause
		if ($State['TransportState'] != "Paused")
		{
		    LogWrite("ControlPause: ");
		    if (Send("ACTION Ds/Playlist 1 Pause") == false)
			$Continue = false;
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Control Stop") !== false) {
		//Control Stop
		if ($State['TransportState'] != "Stopped")
		{
		    LogWrite("ControlStop: ");
		    if (Stop() == false)
			$Continue = false;
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Control Next") !== false) {
		//Control Next
		if ($State['TransportState'] != "Stopped")
		{
		    LogWrite("ControlNext: ");
		    if (Send("ACTION Ds/Playlist 1 Next") == false)
			$Continue = false;
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Control Previous") !== false) {
		//Control Previous
		if ($State['TransportState'] != "Stopped")
		{
		    LogWrite("ControlPrevious: ");
		    if (Send("ACTION Ds/Playlist 1 Previous") == false)
			$Continue = false;
		}
		$DataHandled = true;
            }
	 }
         elseif (strpos($data, "Source") !== false) {
            // Here things happens - we execute the actions sent from the
            // application, by issuing a number of ACTIONs.

	    if (strpos($data, "Source Off") !== false) {
		//Source Off
		if ($State['Standby'] == "false")
		{
		    if (Send('ACTION Ds/Product 1 SetStandby "true"') == false)
			$Continue = false;
		    $State['Standby'] = true;
		}
		$DataHandled = true;
            }
	    else
	    {
	       if ($State['Standby'] == "true")
	       {
		   if (Send('ACTION Ds/Product 1 SetStandby "false"') == false)
		       $Continue = false;
		   $State['Standby'] = true;
	       }

		if (strpos($data, "Source Playlist") !== false) {
		    //Source Playlist
		    if ($State['SourceIndex'] != $State['SourceName']['Playlist'])
		    {
			if (Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['Playlist'] . '"') == false)
			    $Continue = false;
		    }
		    if (Play() == false)
			$Continue = false;
		    $DataHandled = true;
		}
		elseif (strpos($data, "Source TV") !== false) {
		    //Source TV
		    if ($State['SourceIndex'] != $State['SourceName']['TV'])
		    {
			if (Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['TV'] . '"') == false)
			    $Continue = false;
		    }
		    $DataHandled = true;
		}
		elseif (strpos($data, "Source Radio") !== false) {
		    //Source Radio
		    if ($State['SourceIndex'] != $State['SourceName']['Radio'])
		    {
			if (Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['Radio'] . '"') == false)
			    $Continue = false;
		    }
		    $DataHandled = true;
		}
		elseif (strpos($data, "Source NetAux") !== false) {
		    //Source NetAux
		    if ($State['SourceIndex'] != $State['SourceName']['Net Aux'])
		    {
			if (Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['Net Aux'] . '"') == false)
			    $Continue = false;
		    }
		    $DataHandled = true;
		}
	    }
	 }
         elseif (strpos($data, "State") !== false) {
	    LogWrite("HTState: " . print_r($State, true));
	    socket_write($read_sock, serialize($State) . "\n");
	    $DataHandled = true;
         }
         if (! $DataHandled)
         {
	    socket_write($read_sock, "unknown command, or invalid argument\n");
         }

      } // end of data not empty
     
   } // end of reading foreach

} // End of while($Continue)

// close listening socket and Linn LPEC socket
socket_close($new_client_socket);
socket_close($lpec_socket);

?>
