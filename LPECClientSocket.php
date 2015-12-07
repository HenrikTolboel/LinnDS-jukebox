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
require_once("MusicDB.php");
require_once("StringUtils.php");


class LPECClientSocket extends ClientSocket
{
    // Queue is a queue of outstanding commands to be sent to Linn.
    // The currently executing command is still in the queue, removed when the
    // response commes.
    protected $Queue = array();

    // AwaitResponse tells whether we miss a response before sending next
    // command.
    protected $AwaitResponse = 0;

    protected $lastCommandSent = "";
    
    // SubscribeType tells the mapping between "EVENT <digits> XXX" subscribed
    // to protokol (e.g. "Ds/Playlist")
    // <digits> -> "Ds/Playlist"
    // Used to make fewer regular expressions in the EVENT section
    protected $SubscribeType = array();

    // This is a singleton class
    public static function getInstance($Socket, ListeningSocketInterface $ListeningSocket, $BufferLength = 2048) 
    {
	static $inst = null;

	if ($inst === null) 
	{
	    $inst = new LPECClientSocket($Socket, $ListeningSocket, $BufferLength = 2048);
	}

	LogWrite("LPECClientSocket::getInstance");
	return $inst;
    }

    protected function InitClientSocket()
    {
	$this->SubscribeType['Ds/Product'] = -1;
	$this->SubscribeType['Ds/Playlist'] = -1;
	$this->SubscribeType['Ds/Jukebox'] = -1;
	$this->SubscribeType['Ds/Volume'] = -1;
	$this->SubscribeType['Ds/Radio'] = -1;
	$this->SubscribeType['Ds/Info'] = -1;
	$this->SubscribeType['Ds/Time'] = -1;

	$musicDB = new MusicDB();
	$this->IncrRevNo($musicDB);
	$musicDB->close();
    }

    private function getState()
    {
	return $this->listeningSocket->getState();
    }

    public function Send($Str) 
    {
	// Add to queue. if not awaiting responses, then send front
	if (strlen($Str) > 0)
	    array_push($this->Queue, $Str);
	else
	    $this->AwaitResponse = 0;

	$Res = true;
	if ($this->AwaitResponse == 0 && count($this->Queue) > 0)
	{
	    $S = array_shift($this->Queue);
	    $S = str_replace("%NewId%", strval($this->getState()->getState('NewId')), $S);
	    LogWrite("LPECClientSocket::Send: " . $S);
	    $sent = socket_write($this->socket, $S . "\n");
	    if ($sent === false)
	    {
		$Res = false;
		LogWrite("Send: socket_write failed with \"" . $S . "\"");
	    }
	    $this->lastCommandSent = $S;
	    array_unshift($this->Queue, $S); // We leave the sent item in Queue - removed when we get the response
	    $this->AwaitResponse = 1;
	}
	//$CountQueue = count($this->Queue);
	return $Res;
    }

    public function LastCommandSent() 
    {
	return $this->LastCommandSent;
    }

    function PrepareXML($xml)
    {
	$xml = AbsoluteURL($xml); // late binding of http server

	$xml = htmlspecialchars(str_replace(array("\n", "\r"), '', $xml));
	$xml = str_replace("&amp;#", "&#", $xml); // e.g. danish "å" is transcoded from "&#E5;" to "&amp;#E5;" so we convert back
	return $xml;
    }

    function InsertDIDL_list($musicDB, $Preset, $TrackSeq, $AfterId)
    {
	$DIDL_URL = $musicDB->PresetURL($Preset);
	$Res = true;
	LogWrite("InsertDIDL_list: " . $DIDL_URL . ", " . $TrackSeq . ", " . $AfterId);

	$xml = simplexml_load_file($DIDL_URL);

	$xml->registerXPathNamespace('didl', 'urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/');
	$URLs = $xml->xpath('//didl:res');

	$DIDLs = $xml->xpath('//didl:DIDL-Lite');

	if ($TrackSeq == 0) {
	    $musicDB->InsertQueue(-1, $Preset, 1, $this->PrepareXML($URLs[0][0]), $this->PrepareXML($DIDLs[0]->asXML()));
	    if ($this->Send("ACTION Ds/Playlist 1 Insert \"" . $AfterId . "\" \"" . $this->PrepareXML($URLs[0][0]) . "\" \"" . $this->PrepareXML($DIDLs[0]->asXML()) . "\"") === false)
		$Res = false;
	    if ($this->Play() === false)
		$Res = false;
	    for ($i = 1; $i < sizeof($URLs); $i++)
	    {
		$musicDB->InsertQueue(-1, $Preset, $i+1, $this->PrepareXML($URLs[$i][0]), $this->PrepareXML($DIDLs[$i]->asXML()));
		if ($this->Send("ACTION Ds/Playlist 1 Insert \"%NewId%\" \"" . $this->PrepareXML($URLs[$i][0]) . "\" \"" . $this->PrepareXML($DIDLs[$i]->asXML()) . "\"") === false)
		{
		    $Res = false;
		}
	    }
	}
	else
	{
	    $No = $TrackSeq -1;
	    $musicDB->InsertQueue(-1, $Preset, $TrackSeq, $this->PrepareXML($URLs[$No][0]), $this->PrepareXML($DIDLs[$No]->asXML()));
	    if ($this->Send("ACTION Ds/Playlist 1 Insert \"" . $AfterId . "\" \"" . $this->PrepareXML($URLs[$No][0]) . "\" \"" . $this->PrepareXML($DIDLs[$No]->asXML()) . "\"") === false)
		$Res = false;
	    if ($this->Play() === false)
		$Res = false;
	}
	$this->IncrRevNo($musicDB);
	return $Res;
    }

    function CheckPlaylist($musicDB)
    {
	$Res = true;
	$musicDB->DeleteSequence();
	$seq = 0;
	foreach ($this->getState()->getState('IdArray') as $value)
	{
	    $musicDB->InsertSequence($seq, $value);
	    $seq++;
	    if ($this->getState()->getStateArray('PlaylistURLs', $value) === false)
	    {
		if ($this->Send("ACTION Ds/Playlist 1 Read \"" . $value . "\"") === false)
		    $Res = false;
	    }
	}
	$this->IncrRevNo($musicDB);
	return $Res;
    }

    function DeleteAll($musicDB)
    {
	$Res = true;
	if ($this->Send("ACTION Ds/Playlist 1 DeleteAll") === false)
	    $Res = false;
	$musicDB->DeleteQueue();
	$this->getState()->deleteAll();
	return $Res;
    }

    function IncrRevNo($musicDB)
    {
	$RevNo = $this->getState()->getState('RevNo');
	if ($RevNo === false) 
	    die("IncrRevNo: RevNo is false");
	$RevNo = $RevNo + 1;
	$this->getState()->setState('RevNo', $RevNo);
	$musicDB->SetState('RevNo', $RevNo);

    }

    public function SelectPlaylist()
    {
	$Res = true;
	if ($this->getState()->getState('Standby') == 'true')
	{
	    if ($this->Send('ACTION Ds/Product 1 SetStandby "false"') === false)
		$Res = false;
	    $this->getState()->setState('Standby', false);
	    if ($this->Send('ACTION Ds/Product 1 SetSourceIndex "' . $this->getState()->getStateArray('SourceName', 'Playlist') . '"') === false)
		$Res = false;
	}
	elseif ($this->getState()->getState('SourceIndex') != $this->getState()->getStateArray('SourceName', 'Playlist'))
	{
	    if ($this->Send('ACTION Ds/Product 1 SetSourceIndex "' . $this->getState()->getStateArray('SourceName', 'Playlist') . '"') === false)
		$Res = false;
	}
	return $Res;
    }

    function Stop()
    {
	$Res = true;
	if ($this->getState()->getState('TransportState') !== "Stopped")
	{
	    if ($this->Send("ACTION Ds/Playlist 1 Stop") === false)
		$Res = false;
	    $this->getState()->setState('TransportState', "Stopped");
	}
	return $Res;
    }

    function Play()
    {
	$Res = true;
	if ($this->getState()->getState('TransportState') === "Stopped" || $this->getState()->getState('TransportState') === "Paused")
	{
	    if ($this->Send("ACTION Ds/Playlist 1 Play") === false)
		$Res = false;
	    $this->getState()->setState('TransportState', "Starting");
	}
	return $Res;
    }

    public function processMessage($message)
    {
	LogWrite("LPECClientSocket::processMessage - $message");
    
	$DataHandled = false;
	if ($DEBUG > 1)
	    LogWrite($message);
	if (strpos($message, "ALIVE Ds") !== false)
	{
	    $this->Send("SUBSCRIBE Ds/Product");
	    $DataHandled = true;
	}
	elseif (strpos($message, "ALIVE") !== false)
	{
	    LogWrite("ALIVE ignored : " . $message);
	    $DataHandled = true;
	}
	elseif (strpos($message, "ERROR") !== false)
	{
	    LogWrite("ERROR ignored : " . $message);
	    $DataHandled = true;
	}
	elseif (strpos($message, "SUBSCRIBE") !== false)
	{
	    // SUBSCRIBE are sent by Linn when a SUBSCRIBE finishes, thus
	    // we send the possible next command (Send) after removing
	    // previous command.
	    // We record the Number to Subscribe action in the array to
	    // help do less work with the events.
	    $front = array_shift($this->Queue);
	    if ($DEBUG > 1)
		LogWrite("Command: " . $front . " -> " . $message);
	    $S1 = substr($front, 10);
	    $S2 = substr($message, 10);
	    $this->SubscribeType[$S1] = $S2;
	    $this->Send("");
	    $DataHandled = true;
	}
	elseif (strpos($message, "RESPONSE") !== false)
	{
	    // RESPONSE are sent by Linn when an ACTION finishes, thus we
	    // send the possible next command (Send) after removing
	    // previous command.
	    $front = array_shift($this->Queue);
	    if ($DEBUG > 0)
		LogWrite("Command: " . $front . " -> " . $message);

	    if (strpos($front, "ACTION Ds/Product 1 Source ") !== false) 
	    {
		//ACTION Ds/Product 1 Source \"(\d+)\"
		//RESPONSE \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"
		$F = getParameters($front);
		$D = getParameters($message);

		//$State['Source_SystemName'][$F[0]] = $D[0];
		//$State['Source_Type'][$F[0]] = $D[1];
		//$State['Source_Name'][$F[0]] = $D[2];
		//$State['Source_Visible'][$F[0]] = $D[3];

		$this->getState()->setStateArray('SourceName', $D[2], $F[0]);

		if ($D[1] == "Playlist")
		{
		    // We have the Playlist service. subscribe...
		    $this->Send("SUBSCRIBE Ds/Playlist");
		    //$this->Send("SUBSCRIBE Ds/Jukebox");
		}
		elseif ($D[1] == "Radio")
		{
		    // We have the Radio service. subscribe...
		    //$this->Send("SUBSCRIBE Ds/Radio");
		}
	    }
	    elseif (strpos($front, "ACTION Ds/Playlist 1 Read ") !== false) 
	    {
		//ACTION Ds/Playlist 1 Read \"(\d+)\"
		//RESPONSE \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"
		$F = getParameters($front);
		$D = getParameters($message);

		$this->getState()->setStateArray('PlaylistURLs', $F[0], $D[0]);
		$this->getState()->setStateArray('PlaylistXMLs', $F[0], $D[1]);
		$musicDB = new MusicDB();
		$musicDB->UpdateQueue($F[0], -1, -1, $D[0], $D[1]);
		$musicDB->close();
	    }
	    elseif (strpos($front, "ACTION Ds/Playlist 1 Insert ") !== false) 
	    {
		//ACTION Ds/Playlist 1 Insert \"(\d+)\" \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"
		//RESPONSE \"([[:ascii:]]+?)\"
		$F = getParameters($front);
		$D = getParameters($message);

		$this->getState()->setState('NewId', $D[0]);
		$this->getState()->setStateArray('PlaylistURLs', $D[0], $F[1]);
		$this->getState()->setStateArray('PlaylistXMLs', $D[0], $F[2]);
		$musicDB = new MusicDB();
		$musicDB->UpdateQueue($D[0], -1, -1, $F[1], $F[2]);
		$musicDB->close();
	    }
	    elseif (strpos($front, "ACTION Ds/Playlist 1 IdArray") !== false) 
	    {
		//ACTION Ds/Playlist 1 IdArray
		//RESPONSE \"([[:ascii:]]+?)\" \"([[:ascii:]]+?)\"
		$F = getParameters($front);
		$D = getParameters($message);

		$this->getState()->setState('IdArray_Token', $D[0]);
		$this->getState()->setState('IdArray_base64', $D[1]);
		$this->getState()->setState('IdArray', unpack("N*", base64_decode($D[1])));
		$musicDB = new MusicDB();
		$this->CheckPlaylist($musicDB);
		$musicDB->close();
	    }

	    $this->Send("");
	    $DataHandled = true;
	}
	elseif (strpos($message, "EVENT ") !== false)
	{
	    // EVENTs are sent by Your linn - those that were subscribed
	    // to. We think the below ones are interesting....

	    $E = getEvent($message);
	    if (strpos($message, "EVENT " . $this->SubscribeType['Ds/Product']) !== false)
	    {
		if (strpos($message, "SourceIndex ") !== false)
		{
		    $this->getState()->setState('SourceIndex', $E[SourceIndex]);
		}
		if (strpos($message, "ProductModel ") !== false)
		{
		    $this->getState()->setState('ProductModel', $E[ProductModel]);
		}
		if (strpos($message, "ProductName ") !== false)
		{
		    $this->getState()->setState('ProductName', $E[ProductName]);
		}
		if (strpos($message, "ProductRoom ") !== false)
		{
		    $this->getState()->setState('ProductRoom', $E[ProductRoom]);
		}
		if (strpos($message, "ProductType ") !== false)
		{
		    $this->getState()->setState('ProductType', $E[ProductType]);
		}
		if (strpos($message, "Standby ") !== false)
		{
		    $this->getState()->setState('Standby', $E[Standby]);
		    $musicDB = new MusicDB();
		    $musicDB->SetState("Standby", $E[Standby]);
		    $musicDB->close();
		}
		if (strpos($message, "ProductUrl ") !== false)
		{
		    $this->getState()->setState('ProductUrl', $E[ProductUrl]);
		}
		if (strpos($message, "Attributes ") !== false)
		{
		    $this->getState()->setState('Attributes', $E[Attributes]);
		    if (strpos($E[Attributes], "Volume") !== false) // We have a Volume service
		    {
			$this->Send("SUBSCRIBE Ds/Volume");
		    }
		    if (strpos($E[Attributes], "Info") !== false) // We have a Info service
		    {
			//$this->Send("SUBSCRIBE Ds/Info");
		    }
		    if (strpos($E[Attributes], "Time") !== false) // We have a Time service
		    {
			//$this->Send("SUBSCRIBE Ds/Time");
		    }
		}
		if (strpos($message, "SourceCount ") !== false)
		{
		    for ($i = 0; $i < $E[SourceCount]; $i++) 
		    {
			$this->Send("ACTION Ds/Product 1 Source \"" . $i . "\"");
		    }
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "EVENT " . $this->SubscribeType['Ds/Playlist']) !== false)
	    {
		if (strpos($message, "TransportState ") !== false)
		{
		    $this->getState()->setState('TransportState', $E[TransportState]);
		    $musicDB = new MusicDB();
		    $musicDB->SetState("TransportState", $E[TransportState]);
		    $musicDB->close();
		}
		if (strpos($message, "Id ") !== false)
		{
		    $this->getState()->setState('Id', $E[Id]);
		    $musicDB = new MusicDB();
		    $musicDB->SetState("LinnId", $E[Id]);
		    $musicDB->close();
		}
		if (strpos($message, "IdArray ") !== false)
		{
		    $this->getState()->setState('IdArray_base64', $E[IdArray]);
		    $this->getState()->setState('IdArray', unpack("N*", base64_decode($E[IdArray])));
		    $musicDB = new MusicDB();
		    $this->CheckPlaylist($musicDB);
		    $musicDB->close();
		}
		if (strpos($message, "Shuffle ") !== false)
		{
		    $this->getState()->setState('Shuffle', $E[Shuffle]);
		}
		if (strpos($message, "Repeat ") !== false)
		{
		    $this->getState()->setState('Repeat', $E[Repeat]);
		}
		if (strpos($message, "TrackDuration ") !== false)
		{
		    $this->getState()->setState('TrackDuration', $E[TrackDuration]);
		}
		if (strpos($message, "TrackCodecName ") !== false)
		{
		    $this->getState()->setState('TrackCodecName', $E[TrackCodecName]);
		}
		if (strpos($message, "TrackSampleRate ") !== false)
		{
		    $this->getState()->setState('TrackSampleRate', $E[TrackSampleRate]);
		}
		if (strpos($message, "TrackBitRate ") !== false)
		{
		    $this->getState()->setState('TrackBitRate', $E[TrackBitRate]);
		}
		if (strpos($message, "TrackLossless ") !== false)
		{
		    $this->getState()->setState('TrackLossless', $E[TrackLossless]);
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "EVENT " . $this->SubscribeType['Ds/Volume']) !== false)
	    {
		if (strpos($message, "Volume ") !== false)
		{
		    LogWrite("Event Volume");
		    $this->getState()->setState('Volume', $E[Volume]);
		    $musicDB = new MusicDB();
		    $musicDB->SetState("Volume", $E[Volume]);
		    $musicDB->close();
		}
		if (strpos($message, "Mute ") !== false)
		{
		    $this->getState()->setState('Mute', $E[Mute]);
		    $musicDB = new MusicDB();
		    $musicDB->SetState("Mute", $E[Mute]);
		    $musicDB->close();
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "EVENT " . $this->SubscribeType['Ds/Jukebox']) !== false)
	    {
		if (strpos($message, "CurrentPreset ") !== false)
		{
		    $this->getState()->setState('CurrentPreset', $E[CurrentPreset]);
		}
		if (strpos($message, "CurrentBookmark ") !== false)
		{
		    $this->getState()->setState('CurrentBookmark', $E[CurrentBookmark]);
		}
		$DataHandled = true;
	    }
	    else
	    {
		LogWrite("UNKNOWN : " . $message);
		$DataHandled = true;
	    }
	}
	return $DataHandled;
    }
}


?>

