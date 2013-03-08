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

$LINN_JUKEBOX_URL = "http://192.168.0.105/musik";
$LINN_JUKEBOX_PATH = "/musik";
$NL = "\n";
$SQ = "'";

// Debug write out.... Higher number 1,2,3,.. means more output
$DEBUG = 2;

// This is where your linn is in the network.
$LINN_HOST = "192.168.0.108";
$LINN_PORT = 23;

$URI_index_file = dirname($argv[0]) . "/URI_index";

// Create a socket to your linn LPEC interface, and connect...
$lpec_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($lpec_socket, $LINN_HOST, $LINN_PORT);

// Create a socket for clients to register on - listen on port
$port = 9050;

// create a streaming socket, of type TCP/IP
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// set the option to reuse the port
socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

// "bind" the socket to the address to "localhost", on port $port
// so this means that all connections on this port are now our resposibility to send/recv data, disconnect, etc..
socket_bind($sock, 0, $port);

// start listen for connections
socket_listen($sock);

// Queue is a queue of outstanding commands to be sent to Linn.
// The currently executing command is still in the queue, removed when the
// response commes.
$Queue = array();
// AwaitResponse tells whether we miss a response before sending next
// command.
$AwaitResponse = 0;

// State contains the "accumulated" state of the linn device.
$State = array();
$State['MAX_VOLUME'] = 60;
$State['PlayNext'] = -1;
$State['PlayLater'] = array();
$State['SourceIndex_Playlist'] = -1;
$State['IdArray'] = array('0');
$State['Id'] = 0;
$State['NewId'] = 0;

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

function LogWrite($Str)
{
   global $Queue;
   // Write to log
   //print date("D M j G:i:s T Y") . " : " . $Str . "\n";
   print $Str . "\n";
   //print_r($Queue);
}

function Send($Str)
{
   global $Queue;
   global $lpec_socket;
   global $AwaitResponse;
   global $State;

   // Add to queue. if not awaiting responses, then send front
   if (strlen($Str) > 0)
   {
      array_push($Queue, $Str);
   }
   if ($AwaitResponse == 0 && count($Queue) > 0)
   {
      $S = array_shift($Queue);
      $S = str_replace("%NewId%", strval($State['NewId']), $S);
      LogWrite("Send: " . $S);
      socket_write($lpec_socket, $S . "\n");
      array_unshift($Queue, $S); // We leave the sent item in Queue - removed when we get the response
      $AwaitResponse = 1;
   }
}

function PresetURL($num)
{
    global $State;
    global $LINN_JUKEBOX_URL;
    global $LINN_JUKEBOX_PATH;
    global $URI_index;
    global $URI_index_file;

    LogWrite("PresetURL: " . $URI_index[$num]);
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
	$dpl = $URI_index[$num];
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
    global $NL;

    LogWrite("InsertDIDL_list: " . $DIDL_URL . ", " . $OnlyTrackNo . ", " . $AfterId);

    $xml = simplexml_load_file($DIDL_URL);

    $xml->registerXPathNamespace('didl', 'urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/');
    $URLs = $xml->xpath('//didl:res');

    $DIDLs = $xml->xpath('//didl:DIDL-Lite');

    if ($OnlyTrackNo == 0) {
	Send("ACTION Ds/Playlist 1 Insert \"" . $AfterId . "\" \"" . PrepareXML($URLs[0][0]) . "\" \"" . PrepareXML($DIDLs[0]->asXML()) . "\"");
	for ($i = 1; $i < sizeof($URLs); $i++)
	    Send("ACTION Ds/Playlist 1 Insert \"%NewId%\" \"" . PrepareXML($URLs[$i][0]) . "\" \"" . PrepareXML($DIDLs[$i]->asXML()) . "\"");
    }
    else
    {
	$No = $OnlyTrackNo -1;
	Send("ACTION Ds/Playlist 1 Insert \"" . $AfterId . "\" \"" . PrepareXML($URLs[$No][0]) . "\" \"" . PrepareXML($DIDLs[$No]->asXML()) . "\"");

    }
    Send("ACTION Ds/Playlist 1 IdArray");
}

function ReadBlockFromSocket($read_sock)
{
     global $NL;

      // read until newline or 10240 bytes
      // socket_read while show errors when the client is disconnected, so silence the error messages
      //LogWrite("ReadBlockFromSocket: begin");
      $res = "";

      do {
	  $data = @socket_read($read_sock, 10240, PHP_NORMAL_READ);

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

print "LinnDS-jukebox-daemon starts...\n\n";

// create a list of all the clients that will be connected to us..
// add the listening socket to this list
$clients = array($sock, $lpec_socket);

while (true) {
   $read = $clients;

   if (socket_select($read, $write = NULL, $except = NULL, NULL) < 1)
      continue;

   // check if there is a client trying to connect
   if (in_array($sock, $read)) {
      // accept the client, and add him to the $clients array
      $clients[] = $newsock = socket_accept($sock);
     
      // send the client a welcome message
      socket_write($newsock, "Welcome to LinnDS-jukebox-daemon\n".
      "There are ".(count($clients) - 1)." client(s) connected\n");
     
      socket_getpeername($newsock, $ip);
      echo "New client connected: {$ip}\n";
     
      // remove the listening socket from the clients-with-data array
      $key = array_search($sock, $read);
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
         echo "client disconnected.\n";
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
            $AwaitResponse = 0;
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
            $AwaitResponse = 0;
            $front = array_shift($Queue);
            if ($DEBUG > 0)
               LogWrite("Command: " . $front . " -> " . $data);

            if (preg_match("/ACTION Ds\/Product 1 Source \"(\d+)\"/m", $front, $matches) > 0)
            {
               if (preg_match("/RESPONSE \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"/m", $data, $match) > 0)
               {
                  //$State['Source_SystemName'][$matches[1]] = $match[1];
                  //$State['Source_Type'][$matches[1]] = $match[2];
                  //$State['Source_Name'][$matches[1]] = $match[3];
                  //$State['Source_Visible'][$matches[1]] = $match[4];

		  if ($match[2] == "Playlist")
		  {
		      // We have the Playlist service. subscribe...
		      $State['SourceIndex_Playlist'] = $matches[1];
			Send("SUBSCRIBE Ds/Playlist");
			//Send("SUBSCRIBE Ds/Jukebox");
		  }
		  elseif ($match[2] == "Radio")
		  {
		      $State['SourceIndex_Radio'] = $matches[1];
		      // We have the Radio service. subscribe...
			//Send("SUBSCRIBE Ds/Radio");
		  }
               }
            }
	    elseif (preg_match("/ACTION Ds\/Playlist 1 Read \"(\d+)\"/m", $front, $matches) > 0)
            {
               if (preg_match("/RESPONSE \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"/m", $data, $match) > 0)
               {
		      $State['Id_URL'] = htmlspecialchars_decode($match[1]);
		      $State['Id_Metadata'] = htmlspecialchars_decode($match[2]);
	       }
               LogWrite("State:");
               print_r($State);
	    }
	    elseif (preg_match("/ACTION Ds\/Playlist 1 Insert \"(\d+)\"/m", $front, $matches) > 0)
            {
               if (preg_match("/RESPONSE \"([[:ascii:]]+?)\"/m", $data, $match) > 0)
               {
		      $State['NewId'] = $match[1];
	       }
               //LogWrite("State:");
               //print_r($State);
	    }
	    elseif (preg_match("/ACTION Ds\/Playlist 1 IdArray/m", $front, $matches) > 0)
            {
               if (preg_match("/RESPONSE \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"/m", $data, $match) > 0)
               {
		      $State['IdArray_base64'] = $match[2];
		      $State['IdArray'] = unpack("N*", base64_decode($match[2]));
	       }
               LogWrite("State:");
               print_r($State);
	    }

            Send("");
            $DataHandled = true;
         }
         elseif (strpos($data, "EVENT ") !== false)
         {
            // EVENTs are sent by Your linn - those that were subscribed
            // to. We think the below ones are interesting....

            if (strpos($data, "EVENT " . $SubscribeType['Ds/Product']) !== false)
            {
               if (preg_match("/SourceIndex \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State['SourceIndex'] = $matches[1];
               }
               if (preg_match("/ProductModel \"([[:ascii:]]+?)\"/m", $data, $matches) > 0)
               {
                  $State['ProductModel'] = $matches[1];
               }
               if (preg_match("/ProductName \"([[:ascii:]]+?)\"/m", $data, $matches) > 0)
               {
                  $State['ProductName'] = $matches[1];
               }
               if (preg_match("/ProductRoom \"([[:ascii:]]+?)\"/m", $data, $matches) > 0)
               {
                  $State['ProductRoom'] = $matches[1];
               }
               if (preg_match("/ProductType \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State['ProductType'] = $matches[1];
               }
               if (preg_match("/Standby \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State['Standby'] = $matches[1];
               }
               if (preg_match("/ProductUrl \"([[:ascii:]]+?)\"/m", $data, $matches) > 0)
               {
                  $State['ProductUrl'] = $matches[1];
               }
               if (preg_match("/Attributes \"([[:ascii:]]+?)\"/m", $data, $matches) > 0)
               {
                  $State['Attributes'] = $matches[1];
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
               if (preg_match("/SourceCount \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State['SourceCount'] = $matches[1];
                  for ($i = 0; $i < $State['SourceCount']; $i++) {
                     Send("ACTION Ds/Product 1 Source \"" . $i . "\"");
                  }
               }
               $DataHandled = true;
            }
	    elseif (strpos($data, "EVENT " . $SubscribeType['Ds/Playlist']) !== false)
            {
               if (preg_match("/TransportState \"(\w+)\"/m", $data, $matches) > 0)
               {
                  if (false && count($State['PlayLater']) >= 1 && $matches[1] == "Stopped" && $State['TransportState'] != $matches[1] && $State['SourceIndex'] == $State['SourceIndex_Playlist'])
                  {
                     $front = array_shift($State['PlayLater']);
                     Send("ACTION Ds/Jukebox 3 SetCurrentPreset \"" . $front . "\"");
                     Send("ACTION Ds/Playlist 1 Play");
                  }
                  $State['TransportState'] = $matches[1];
               }
               if (preg_match("/Id \"(\d+)\"/m", $data, $matches) > 0)
               {
                  if (false && $State['PlayNext'] != -1 && $State['Id'] != $matches[1])
                  {
                     Send("ACTION Ds/Playlist 1 Stop");
                     Send("ACTION Ds/Jukebox 3 SetCurrentPreset \"" . $State['PlayNext'] . "\"");
                     Send("ACTION Ds/Playlist 1 Play");
                     $State['PlayNext'] = -1;
                  }
                  $State['Id'] = $matches[1];
		  //Send("ACTION Ds/Playlist 1 Read \"" . $matches[1] . "\"");
               }
               if (preg_match("/IdArray \"([[:graph:]]+)\"/m", $data, $matches) > 0)
               {
                  $State['IdArray_base64'] = $matches[1];
                  $State['IdArray'] = unpack("N*", base64_decode($matches[1]));
               }
               if (preg_match("/Shuffle \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State['Shuffle'] = $matches[1];
               }
               if (preg_match("/Repeat \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State['Repeat'] = $matches[1];
               }
               if (preg_match("/TrackDuration \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State['TrackDuration'] = $matches[1];
               }
               if (preg_match("/TrackCodecName \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State['TrackCodecName'] = $matches[1];
               }
               if (preg_match("/TrackSampleRate \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State['TrackSampleRate'] = $matches[1];
               }
               if (preg_match("/TrackBitRate \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State['TrackBitRate'] = $matches[1];
               }
               if (preg_match("/TrackLossless \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State['TrackLossless'] = $matches[1];
               }
               $DataHandled = true;
            }
            elseif (strpos($data, "EVENT " . $SubscribeType['Ds/Volume']) !== false)
            {
               if (preg_match("/Volume \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State['Volume'] = $matches[1];
               }
               if (preg_match("/Mute \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State['Mute'] = $matches[1];
               }
               $DataHandled = true;
            }
            elseif (strpos($data, "EVENT " . $SubscribeType['Ds/Jukebox']) !== false)
            {
               if (preg_match("/CurrentPreset \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State['CurrentPreset'] = $matches[1];
               }
               if (preg_match("/CurrentBookmark \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State['CurrentBookmark'] = $matches[1];
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
            if (preg_match("/Jukebox PlayNow \"(\d+)\" \"(\d+)\"/m", $data, $matches) > 0)
            {
               $JukeBoxPlay = $matches[1];
               $JukeBoxTrack = $matches[2];
               $State['PlayNext'] = -1;
               $State['PlayLater'] = array();
               LogWrite("JukeBoxPlayNow: " . $JukeBoxPlay . ", " . $JukeBoxTrack);

	       if ($State['Standby'] == 'true')
	       {
		   Send('ACTION Ds/Product 1 SetStandby "false"');
		   Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceIndex_Playlist'] . '"');
	       }
	       elseif ($State['SourceIndex'] != $State['SourceIndex_Playlist'])
		   Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceIndex_Playlist'] . '"');

               Send("ACTION Ds/Playlist 1 Stop");

               Send("ACTION Ds/Playlist 1 DeleteAll");
	       InsertDIDL_list(PresetURL($JukeBoxPlay), $JukeBoxTrack, 0);

               //Send("ACTION Ds/Jukebox 3 SetCurrentPreset \"" . $JukeBoxPlay . "\"");

               Send("ACTION Ds/Playlist 1 Play");
               $DataHandled = true;
            }
            elseif (preg_match("/Jukebox PlayNext \"(\d+)\" \"(\d+)\"/m", $data, $matches) > 0)
            {
               $JukeBoxPlay = $matches[1];
               $JukeBoxTrack = $matches[2];
               //$State['PlayNext'] = $JukeBoxPlay;
               //$State['PlayLater'] = array();
               LogWrite("JukeBoxPlayNext: " . $JukeBoxPlay . ", " . $JukeBoxTrack);

	       InsertDIDL_list(PresetURL($JukeBoxPlay), $JukeBoxTrack, $State['Id']);

	       if ($State['Standby'] == 'true')
	       {
		   Send('ACTION Ds/Product 1 SetStandby "false"');
		   Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceIndex_Playlist'] . '"');
	       }
	       elseif ($State['SourceIndex'] != $State['SourceIndex_Playlist'])
		   Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceIndex_Playlist'] . '"');
	       if ($State['TransportState'] == "Stopped")
		   Send("ACTION Ds/Playlist 1 Play");

               if ($DEBUG > 0)
               {
                  //LogWrite($data);
                  //print_r($State);
               }
               $DataHandled = true;
            }
            elseif (preg_match("/Jukebox PlayLater \"(\d+)\" \"(\d+)\"/m", $data, $matches) > 0)
            {
               $JukeBoxPlay = $matches[1];
               $JukeBoxTrack = $matches[2];
               //$State['PlayNext'] = -1;
               //array_push($State['PlayLater'], $JukeBoxPlay);
               LogWrite("JukeBoxPlayLater: " . $JukeBoxPlay . ", " . $JukeBoxTrack);

	       InsertDIDL_list(PresetURL($JukeBoxPlay), $JukeBoxTrack, end($State['IdArray']));

	       if ($State['Standby'] == 'true')
	       {
		   Send('ACTION Ds/Product 1 SetStandby "false"');
		   Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceIndex_Playlist'] . '"');
	       }
	       elseif ($State['SourceIndex'] != $State['SourceIndex_Playlist'])
		   Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceIndex_Playlist'] . '"');
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
         elseif (strpos($data, "Volume") !== false)
         {
            // Here things happens - we execute the actions sent from the
            // application, by issuing a number of ACTIONs.
            if (preg_match("/Volume Set \"(\d+)\"/m", $data, $matches) > 0)
            {
               $value = $matches[1];
	       if ($value > $State['MAX_VOLUME'])
		   $value = $State['MAX_VOLUME'];
	       if ($value != $State['Volume'])
	       {
		   LogWrite("VolumeSet: " . $value);
		   Send("ACTION Ds/Volume 1 SetVolume \"" . $value . "\"");
	       }
               $DataHandled = true;
            }
            if (preg_match("/Volume Incr/m", $data, $matches) > 0)
            {
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
            if (preg_match("/Volume Decr/m", $data, $matches) > 0)
            {
		LogWrite("VolumeDecr: ");
		Send("ACTION Ds/Volume 1 VolumeDec");
		$DataHandled = true;
            }
	 }
         elseif (strpos($data, "Control") !== false)
         {
            // Here things happens - we execute the actions sent from the
            // application, by issuing a number of ACTIONs.
            if (preg_match("/Control Play/m", $data, $matches) > 0)
            {
		if ($State['TransportState'] != "Playing")
		{
		    LogWrite("ControlPlay: ");
		    Send("ACTION Ds/Playlist 1 Play");
		}
		$DataHandled = true;
            }
            if (preg_match("/Control Pause/m", $data, $matches) > 0)
            {
		if ($State['TransportState'] != "Paused")
		{
		    LogWrite("ControlPause: ");
		    Send("ACTION Ds/Playlist 1 Pause");
		}
		$DataHandled = true;
            }
            if (preg_match("/Control Stop/m", $data, $matches) > 0)
            {
		if ($State['TransportState'] != "Stopped")
		{
		    LogWrite("ControlStop: ");
		    Send("ACTION Ds/Playlist 1 Stop");
		}
		$DataHandled = true;
            }
            if (preg_match("/Control Next/m", $data, $matches) > 0)
            {
		if ($State['TransportState'] != "Stopped")
		{
		    LogWrite("ControlNext: ");
		    Send("ACTION Ds/Playlist 1 Next");
		}
		$DataHandled = true;
            }
            if (preg_match("/Control Previous/m", $data, $matches) > 0)
            {
		if ($State['TransportState'] != "Stopped")
		{
		    LogWrite("ControlPrevious: ");
		    Send("ACTION Ds/Playlist 1 Previous");
		}
		$DataHandled = true;
            }
	 }
         elseif (strpos($data, "Source") !== false)
         {
            // Here things happens - we execute the actions sent from the
            // application, by issuing a number of ACTIONs.
            if (preg_match("/Source Off/m", $data, $matches) > 0)
            {
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

		if (preg_match("/Source Playlist/m", $data, $matches) > 0)
		{
		   if ($State['SourceIndex'] != $State['SourceIndex_Playlist'])
		       Send('ACTION Ds/Product 1 SetSourceIndex "' . $State['SourceIndex_Playlist'] . '"');
		   if ($State['TransportState'] == "Stopped")
		       Send("ACTION Ds/Playlist 1 Play");
		    $DataHandled = true;
		}
		if (preg_match("/Source TV/m", $data, $matches) > 0)
		{
		   if ($State['SourceIndex'] != 5)
		       Send('ACTION Ds/Product 1 SetSourceIndex "' . 5 . '"');
		    $DataHandled = true;
		}
		if (preg_match("/Source Radio/m", $data, $matches) > 0)
		{
		   if ($State['SourceIndex'] != $State['SourceIndex_Radio'])
		       Send('ACTION Ds/Product 1 SetSourceIndex "' . 5 . '"');
		    $DataHandled = true;
		}
		if (preg_match("/Source NetAux/m", $data, $matches) > 0)
		{
		   if ($State['SourceIndex'] != 4)
		       Send('ACTION Ds/Product 1 SetSourceIndex "' . 4 . '"');
		    $DataHandled = true;
		}
	    }
	 }
         elseif (strpos($data, "State") !== false)
         {
            LogWrite("State");
            print_r($State);
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

?>
