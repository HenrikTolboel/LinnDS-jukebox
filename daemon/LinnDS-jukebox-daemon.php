#!/usr/bin/php
<?php
/*!
* LinnDS-jukebox-daemon
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/


// Debug write out.... Higher number 1,2,3,.. means more output
$DEBUG = 1;

// This is where your linn is in the network.
$LINN_HOST = "192.168.0.108";
$LINN_PORT = 23;

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
$State[MAX_VOLUME] = 60;
$State[PlayNext] = -1;
$State[PlayLater] = array();

// SubscribeType tells the mapping between "EVENT <digits> XXX" subscribed
// to protokol (e.g. "Ds/Ds")
// <digits> -> "Ds/Ds"
// Used to make fewer regular expressions in the EVENT section
$SubscribeType = array();

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

   // Add to queue. if not awaiting responses, then send front
   if (strlen($Str) > 0)
   {
      array_push($Queue, $Str);
   }
   if ($AwaitResponse == 0 && count($Queue) > 0)
   {
      $S = array_shift($Queue);
      socket_write($lpec_socket, $S . "\n");
      array_unshift($Queue, $S); // We leave the sent item in Queue - removed when we get the response
      $AwaitResponse = 1;
   }
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
      // read until newline or 1024 bytes
      // socket_read while show errors when the client is disconnected, so silence the error messages
      $data = @socket_read($read_sock, 10240, PHP_NORMAL_READ);
     
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
            Send("SUBSCRIBE Ds/Ds");
            Send("SUBSCRIBE Ds/Preamp");
            Send("SUBSCRIBE Ds/Jukebox");
            Send("SUBSCRIBE Ds/Playlist");
            Send("SUBSCRIBE Ds/Product");
            $DataHandled = true;
         }
         //elseif (strpos($data, "ALIVE Preamp") !== false)
         //{
            //Send("SUBSCRIBE Preamp");
            //$DataHandled = true;
         //}
         elseif (strpos($data, "ALIVE") !== false)
         {
            LogWrite("ALIVE ignored : " . $data);
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

            if (preg_match("/ACTION Ds\/Product 3 SourceType \"(\d+)\"/m", $front, $matches) > 0)
            {
               if (preg_match("/RESPONSE \"(\w+)\"/m", $data, $match) > 0)
               {
                  $State[SourceType][$matches[1]] = $match[1];
               }
            }

            if (preg_match("/ACTION Ds\/Product 3 SourceName \"(\d+)\"/m", $front, $matches) > 0)
            {
               if (preg_match("/RESPONSE \"(\w+)\"/m", $data, $match) > 0)
               {
                  $State[SourceName][$matches[1]] = $match[1];
               }
            }

            Send("");
            $DataHandled = true;
         }
         elseif (strpos($data, "EVENT ") !== false)
         {
            // EVENTs are sent by Your linn - those that were subscribed
            // to. We think the below ones are interesting....

            if (strpos($data, "EVENT " . $SubscribeType["Ds/Ds"]) !== false)
            {
               if (preg_match("/TransportState \"(\w+)\"/m", $data, $matches) > 0)
               {
                  if (count($State[PlayLater]) >= 1 && $matches[1] == "Stopped" && $State[TransportState] != $matches[1])
                  {
                     $front = array_shift($State[PlayLater]);
                     Send("ACTION Ds/Jukebox 2 SetCurrentPreset \"" . $front . "\"");
                     Send("ACTION Ds/Ds 1 Play");
                  }
                  $State[TransportState] = $matches[1];
               }
               if (preg_match("/TrackId \"(\d+)\"/m", $data, $matches) > 0)
               {
                  if ($State[PlayNext] != -1 && $State[TrackId] != $matches[1])
                  {
                     Send("ACTION Ds/Ds 1 Stop");
                     Send("ACTION Ds/Jukebox 2 SetCurrentPreset \"" . $State[PlayNext] . "\"");
                     Send("ACTION Ds/Ds 1 Play");
                     $State[PlayNext] = -1;
                  }
                  $State[TrackId] = $matches[1];
               }
               if (preg_match("/TrackDuration \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State[TrackDuration] = $matches[1];
               }
               if (preg_match("/TrackCodecName \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State[TrackCodecName] = $matches[1];
               }
               if (preg_match("/TrackSampleRate \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State[TrackSampleRate] = $matches[1];
               }
               if (preg_match("/TrackBitRate \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State[TrackBitRate] = $matches[1];
               }
               if (preg_match("/TrackLossless \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State[TrackLossless] = $matches[1];
               }
               $DataHandled = true;
            }
            elseif (strpos($data, "EVENT " . $SubscribeType["Ds/Preamp"]) !== false)
            {
               if (preg_match("/Volume \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State[Volume] = $matches[1];
               }
               if (preg_match("/Mute \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State[Mute] = $matches[1];
               }
               $DataHandled = true;
            }
            elseif (strpos($data, "EVENT " . $SubscribeType["Ds/Jukebox"]) !== false)
            {
               if (preg_match("/CurrentPreset \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State[CurrentPreset] = $matches[1];
               }
               if (preg_match("/CurrentBookmark \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State[CurrentBookmark] = $matches[1];
               }
               $DataHandled = true;
            }
            elseif (strpos($data, "EVENT " . $SubscribeType["Ds/Playlist"]) !== false)
            {
               if (preg_match("/IdArray \"([[:graph:]]+)\"/m", $data, $matches) > 0)
               {
                  $State[IdArray] = $matches[1];
               }
               if (preg_match("/Shuffle \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State[Shuffle] = $matches[1];
               }
               if (preg_match("/Repeat \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State[Repeat] = $matches[1];
               }
               $DataHandled = true;
            }
            elseif (strpos($data, "EVENT " . $SubscribeType["Ds/Product"]) !== false)
            {
               if (preg_match("/ProductSourceIndex \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State[ProductSourceIndex] = $matches[1];
               }
               if (preg_match("/ProductModel \"([[:ascii:]]+?)\"/m", $data, $matches) > 0)
               {
                  $State[ProductModel] = $matches[1];
               }
               if (preg_match("/ProductName \"([[:ascii:]]+?)\"/m", $data, $matches) > 0)
               {
                  $State[ProductName] = $matches[1];
               }
               if (preg_match("/ProductRoom \"([[:ascii:]]+?)\"/m", $data, $matches) > 0)
               {
                  $State[ProductRoom] = $matches[1];
               }
               if (preg_match("/ProductType \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State[ProductType] = $matches[1];
               }
               if (preg_match("/ProductStandby \"(\w+)\"/m", $data, $matches) > 0)
               {
                  $State[ProductStandby] = $matches[1];
               }
               //if (preg_match("/ProductSourceXml \"([[:graph:]]+)\"/m", $data, $matches) > 0)
               //{
                  //$State[ProductSourceXml] = $matches[1];
               //}
               //if (preg_match("/ProductAnySourceType \"(\w+)\"/m", $data, $matches) > 0)
               //{
                  //$State[ProductAnySourceType] = $matches[1];
               //}
               if (preg_match("/ProductSourceCount \"(\d+)\"/m", $data, $matches) > 0)
               {
                  $State[ProductSourceCount] = $matches[1];
                  for ($i = 0; $i < $State[ProductSourceCount]; $i++) {
                     Send("ACTION Ds/Product 3 SourceType \"" . $i . "\"");
                     Send("ACTION Ds/Product 3 SourceName \"" . $i . "\"");
                  }
               }
               $DataHandled = true;
            }

            if ($DEBUG > 0)
            {
               LogWrite($data);
               print_r($State);
            }
         }
         elseif (strpos($data, "Jukebox") !== false)
         {
            // Here things happens - we execute the actions sent from the
            // application, by issuing a number of ACTIONs.
            if (preg_match("/Jukebox PlayNow \"(\d+)\"/m", $data, $matches) > 0)
            {
               $JukeBoxPlay = $matches[1];
               $State[PlayNext] = -1;
               $State[PlayLater] = array();
               LogWrite("JukeBoxPlayNow: " . $JukeBoxPlay);
               Send("ACTION Ds/Ds 1 Stop");
               Send("ACTION Ds/Jukebox 2 SetCurrentPreset \"" . $JukeBoxPlay . "\"");
               Send("ACTION Ds/Ds 1 Play");
               $DataHandled = true;
            }
            elseif (preg_match("/Jukebox PlayNext \"(\d+)\"/m", $data, $matches) > 0)
            {
               $JukeBoxPlay = $matches[1];
               LogWrite("JukeBoxPlayNext: " . $JukeBoxPlay);
               $State[PlayNext] = $JukeBoxPlay;
               $State[PlayLater] = array();
               if ($DEBUG > 0)
               {
                  //LogWrite($data);
                  print_r($State);
               }
               $DataHandled = true;
            }
            elseif (preg_match("/Jukebox PlayLater \"(\d+)\"/m", $data, $matches) > 0)
            {
               $JukeBoxPlay = $matches[1];
               LogWrite("JukeBoxPlayLater: " . $JukeBoxPlay);
               $State[PlayNext] = -1;
               array_push($State[PlayLater], $JukeBoxPlay);
               if ($DEBUG > 0)
               {
                  //LogWrite($data);
                  print_r($State);
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
	       if ($value > $State[MAX_VOLUME])
		   $value = $State[MAX_VOLUME];
	       if ($value != $State[Volume])
	       {
		   LogWrite("VolumeSet: " . $value);
		   Send("ACTION Ds/Preamp 4 SetVolume \"" . $value . "\"");
	       }
               $DataHandled = true;
            }
            if (preg_match("/Volume Incr/m", $data, $matches) > 0)
            {
		if ($State[Volume] < $State[MAX_VOLUME])
		{
		    LogWrite("VolumeIncr: ");
		    Send("ACTION Ds/Preamp 4 VolumeInc");
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
		Send("ACTION Ds/Preamp 4 VolumeDec");
		$DataHandled = true;
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
