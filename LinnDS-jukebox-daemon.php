#!/usr/bin/php
<?php
/*!
* LinnDS-jukebox-daemon
*
* Copyright (c) 2011-2013 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");

// Debug write out.... Higher number 1,2,3,.. means more output
$DEBUG = 2;

$URI_index_file = dirname($argv[0]) . "/URI_index";
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
$State['IdArray'] = array('0');
$State['Id'] = 0;
$State['NewId'] = 0;
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

// Load index over # -> DPL URI's
if (file_exists($URI_index_file))
{
    $State['URI_index_mtime'] = filemtime($URI_index_file);
    $URI_index = unserialize(file_get_contents($URI_index_file));
    $State['URI_index'] = "Loaded";
}
else
{
    $URI_index_mtime = 0;
}

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

/** 
 * strposall 
 * 
 * Find all occurrences of a needle in a haystack 
 * 
 * @param string $haystack 
 * @param string $needle 
 * @return array or false 
 */ 
function strposall($haystack,$needle){ 
    
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

    if ($State['AwaitResponse'] == 0 && count($Queue) > 0)
    {
	$S = array_shift($Queue);
	$S = str_replace("%NewId%", strval($State['NewId']), $S);
	LogWrite("Send: " . $S);
	socket_write($lpec_socket, $S . "\n");
	array_unshift($Queue, $S); // We leave the sent item in Queue - removed when we get the response
	$State['AwaitResponse'] = 1;
    }
    $State['CountQueue'] = count($Queue);
}

function PresetURL($num)
{
    global $State;
    global $LINN_JUKEBOX_URL;
    global $LINN_JUKEBOX_PATH;
    global $URI_index;
    global $URI_index_file;

    LogWrite("PresetURL: " . $URI_index[$num]['URI']);
    if (file_exists($URI_index_file))
    {
	clearstatcache(true,$URI_index_file);
    }
    $mt = filemtime($URI_index_file);
    if (file_exists($URI_index_file) && filemtime($URI_index_file) > $State['URI_index_mtime'])
    {
	$State['URI_index_mtime'] = filemtime($URI_index_file);
	$URI_index = unserialize(file_get_contents($URI_index_file));
	LogWrite("Load URI_index");
    }
    
    if ($State['URI_index_mtime'] > 0)
    {
	$dpl = $URI_index[$num]['URI'];
	$dpl = ProtectPath($dpl);
	$dpl = AbsolutePath($dpl);

	LogWrite("dpl: " . $dpl);

	return $dpl;
    }
    else
    {
	return $LINN_JUKEBOX_URL . "/_Presets/" . $num . ".dpl";
    }
}

function PrepareXML($xml)
{
    $xml = AbsoluteURL($xml); // late binding of http server

    $xml = htmlspecialchars(str_replace(array("\n", "\r"), '', $xml));
    $xml = str_replace("&amp;#", "&#", $xml); // e.g. danish "å" is transcoded from "&#E5;" to "&amp;#E5;" so we convert back
    return $xml;
}

function InsertDIDL_list($DIDL_URL, $OnlyTrackNo, $AfterId)
{
    global $State;
    global $NL;

    LogWrite("InsertDIDL_list: " . $DIDL_URL . ", " . $OnlyTrackNo . ", " . $AfterId);

    $xml = simplexml_load_file($DIDL_URL);

    $xml->registerXPathNamespace('didl', 'urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/');
    $URLs = $xml->xpath('//didl:res');

    $DIDLs = $xml->xpath('//didl:DIDL-Lite');

    if ($OnlyTrackNo == 0) {
	Send("ACTION Ds/Playlist 1 Insert \"" . $AfterId . "\" \"" . PrepareXML($URLs[0][0]) . "\" \"" . PrepareXML($DIDLs[0]->asXML()) . "\"");
	if ($State['TransportState'] == "Stopped")
	    Send("ACTION Ds/Playlist 1 Play");
	for ($i = 1; $i < sizeof($URLs); $i++)
	    Send("ACTION Ds/Playlist 1 Insert \"%NewId%\" \"" . PrepareXML($URLs[$i][0]) . "\" \"" . PrepareXML($DIDLs[$i]->asXML()) . "\"");
    }
    else
    {
	$No = $OnlyTrackNo -1;
	Send("ACTION Ds/Playlist 1 Insert \"" . $AfterId . "\" \"" . PrepareXML($URLs[$No][0]) . "\" \"" . PrepareXML($DIDLs[$No]->asXML()) . "\"");
	if ($State['TransportState'] == "Stopped")
	    Send("ACTION Ds/Playlist 1 Play");

    }
    Send("ACTION Ds/Playlist 1 IdArray");
}

function CheckPlaylist()
{
    global $State;

    foreach ($State['IdArray'] as $value)
    {
	if (! isset($State['PlaylistURLs'][$value]))
	{
	    Send("ACTION Ds/Playlist 1 Read \"" . $value . "\"");
	}
    }
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

function DeleteAll()
{
    global $State;

    Send("ACTION Ds/Playlist 1 DeleteAll");
    $State['PlaylistURLs'] = array();
    $State['PlaylistXMLs'] = array();
    $State['IdArray'] = array('0');
    $State['Id'] = 0;
    $State['NewId'] = 0;
}

function SelectPlaylist()
{
    global $State;

    if ($State['Standby'] == 'true')
    {
	Send('ACTION Ds/Product 1 SetStandby "false"');
	Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['Playlist'] . '"');
    }
    elseif ($State['SourceIndex'] != $State['SourceName']['Playlist'])
	Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['Playlist'] . '"');
}


LogWrite("LinnDS-jukebox-daemon starts...");

// create a list of all the clients that will be connected to us..
// add the listening socket to this list
$clients = array($new_client_socket, $lpec_socket);

while (true) {
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
	    }
	    elseif (strpos($front, "ACTION Ds/Playlist 1 Insert ") !== false) {
		//ACTION Ds/Playlist 1 Insert \"(\d+)\" \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"
		//RESPONSE \"([[:ascii:]]+?)\"
		$F = getParameters($front);
		$D = getParameters($data);

		$State['NewId'] = $D[0];
		$State['PlaylistURLs'][$State['NewId']] = $F[1];
		$State['PlaylistXMLs'][$State['NewId']] = $F[2];
	    }
	    elseif (strpos($front, "ACTION Ds/Playlist 1 IdArray") !== false) {
		//ACTION Ds/Playlist 1 IdArray
		//RESPONSE \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"
		$F = getParameters($front);
		$D = getParameters($data);

		$State['IdArray_Token'] = $D[0];
		$State['IdArray_base64'] = $D[1];
		$State['IdArray'] = unpack("N*", base64_decode($D[1]));
		CheckPlaylist();
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
               }
	       if (strpos($data, "Id ") !== false)
               {
                  $State['Id'] = $E[Id];
		  //Send("ACTION Ds/Playlist 1 Read \"" . $matches[1] . "\"");
               }
	       if (strpos($data, "IdArray ") !== false)
               {
                  $State['IdArray_base64'] = $E[IdArray];
                  $State['IdArray'] = unpack("N*", base64_decode($State['IdArray_base64']));
		  CheckPlaylist();
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
               }
	       if (strpos($data, "Mute ") !== false)
               {
                  $State['Mute'] = $E[Mute];
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

		SelectPlaylist();

		if ($State['TransportState'] != "Stopped")
		    Send("ACTION Ds/Playlist 1 Stop");

		DeleteAll();
		InsertDIDL_list(PresetURL($JukeBoxPlay), $JukeBoxTrack, 0);

		//Send("ACTION Ds/Jukebox 3 SetCurrentPreset \"" . $JukeBoxPlay . "\"");

		if ($State['TransportState'] == "Stopped")
		    Send("ACTION Ds/Playlist 1 Play");
		$DataHandled = true;
            }
	    elseif (strpos($data, "Jukebox PlayNext ") !== false) {
		//Jukebox PlayNext \"(\d+)\" \"(\d+)\"
		$JukeBoxPlay = $D[0];
		$JukeBoxTrack = $D[1];
		LogWrite("JukeBoxPlayNext: " . $JukeBoxPlay . ", " . $JukeBoxTrack);

		InsertDIDL_list(PresetURL($JukeBoxPlay), $JukeBoxTrack, $State['Id']);

		SelectPlaylist();

		if ($State['TransportState'] == "Stopped")
		    Send("ACTION Ds/Playlist 1 Play");

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

		SelectPlaylist();

		InsertDIDL_list(PresetURL($JukeBoxPlay), $JukeBoxTrack, end($State['IdArray']));


		if ($State['TransportState'] == "Stopped")
		    Send("ACTION Ds/Playlist 1 Play");

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

		SelectPlaylist();

		if ($State['TransportState'] == "Stopped")
		    DeleteAll();

		for ($i = 0; $i < 50; $i++) {
		    $RandomPreset = rand($JukeBoxFirstAlbum, $JukeBoxLastAlbum);
		    $RandomTrack = rand(1, $URI_index[$RandomPreset]['NoTracks']);
		    if ($i == 0)
			InsertDIDL_list(PresetURL($RandomPreset), $RandomTrack, end($State['IdArray']));
		    else
			InsertDIDL_list(PresetURL($RandomPreset), $RandomTrack, "%NewId%");
		}

		if ($State['TransportState'] == "Stopped")
		    Send("ACTION Ds/Playlist 1 Play");

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
		if ($value != $State['Volume'])
		{
		    LogWrite("VolumeSet: " . $value);
		    Send("ACTION Ds/Volume 1 SetVolume \"" . $value . "\"");
		    $State['Volume'] = $value;
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Volume Incr") !== false) {
		//Volume Incr
		if ($State['Volume'] < $State['MAX_VOLUME'])
		{
		    LogWrite("VolumeIncr: ");
		    Send("ACTION Ds/Volume 1 VolumeInc");
		}
		else
		{
		    LogWrite("VolumeIncr: IGNORED MAX_VOLUME REACHED");
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Volume Incr") !== false) {
		//Volume Decr
		LogWrite("VolumeDecr: ");
		Send("ACTION Ds/Volume 1 VolumeDec");
		$DataHandled = true;
            }
	 }
         elseif (strpos($data, "Control") !== false) {
            // Here things happens - we execute the actions sent from the
            // application, by issuing a number of ACTIONs.

	    if (strpos($data, "Control Play") !== false) {
		//Control Play
		if ($State['TransportState'] != "Playing")
		{
		    LogWrite("ControlPlay: ");
		    Send("ACTION Ds/Playlist 1 Play");
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Control Pause") !== false) {
		//Control Pause
		if ($State['TransportState'] != "Paused")
		{
		    LogWrite("ControlPause: ");
		    Send("ACTION Ds/Playlist 1 Pause");
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Control Stop") !== false) {
		//Control Stop
		if ($State['TransportState'] != "Stopped")
		{
		    LogWrite("ControlStop: ");
		    Send("ACTION Ds/Playlist 1 Stop");
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Control Next") !== false) {
		//Control Next
		if ($State['TransportState'] != "Stopped")
		{
		    LogWrite("ControlNext: ");
		    Send("ACTION Ds/Playlist 1 Next");
		}
		$DataHandled = true;
            }
	    elseif (strpos($data, "Control Previous") !== false) {
		//Control Previous
		if ($State['TransportState'] != "Stopped")
		{
		    LogWrite("ControlPrevious: ");
		    Send("ACTION Ds/Playlist 1 Previous");
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
		    Send('ACTION Ds/Product 1 SetStandby "true"');
		}
		$DataHandled = true;
            }
	    else
	    {
	       if ($State['Standby'] == "true")
	       {
		   Send('ACTION Ds/Product 1 SetStandby "false"');
	       }

		if (strpos($data, "Source Playlist") !== false) {
		    //Source Playlist
		    if ($State['SourceIndex'] != $State['SourceName']['Playlist'])
		       Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['Playlist'] . '"');
		   if ($State['TransportState'] == "Stopped")
		       Send("ACTION Ds/Playlist 1 Play");
		    $DataHandled = true;
		}
		elseif (strpos($data, "Source TV") !== false) {
		    //Source TV
		    if ($State['SourceIndex'] != $State['SourceName']['TV'])
			Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['TV'] . '"');
		    $DataHandled = true;
		}
		elseif (strpos($data, "Source Radio") !== false) {
		    //Source Radio
		    if ($State['SourceIndex'] != $State['SourceName']['Radio'])
			Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['Radio'] . '"');
		    $DataHandled = true;
		}
		elseif (strpos($data, "Source NetAux") !== false) {
		    //Source NetAux
		    if ($State['SourceIndex'] != $State['SourceName']['Net Aux'])
			Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceName']['Net Aux'] . '"');
		    $DataHandled = true;
		}
	    }
	 }
         elseif (strpos($data, "State") !== false) {
	    LogWrite("State: " . print_r($State, true));
	    socket_write($read_sock, serialize($State) . "\n");
	    $DataHandled = true;
         }
         if (! $DataHandled)
         {
	    socket_write($read_sock, "unknown command, or invalid argument\n");
         }

      } // end of data not empty
     
   } // end of reading foreach

} // End of while(true)

// close listening socket
socket_close($new_client_socket);

?>
