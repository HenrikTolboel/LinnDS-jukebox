<?php

require_once("setup.php");
require_once("SocketServer.php");
require_once("StringUtils.php");

class LinnDSClientSocket extends ClientSocket
{
    public static function getInstance($Socket, ListeningSocketInterface $ListeningSocket, $BufferLength = 2048) 
    {
	$inst = new LinnDSClientSocket($Socket,  $ListeningSocket, $BufferLength = 2048);

	LogWrite("LinnDSClientSocket::getInstance");
	return $inst;
    }

    private function getState()
    {
	return $this->listeningSocket->getState();
    }

    public function Send($message)
    {
	socket_write($this->getSocket(), $message . "\n");
    }

    public function processMessage($message)
    {
	LogWrite("LinnDSClientSocket::processMessage - $message");

	$DataHandled = false;
	if ($DEBUG > 1)
	    LogWrite($message);

	if (strpos($message, "Jukebox") !== false)
	{
	    // Here things happens - we execute the actions sent from the
	    // application, by issuing a number of ACTIONs.
	    $D = getParameters($message);

	    if (strpos($message, "Jukebox PlayNow ") !== false) 
	    {
		//Jukebox PlayNow \"(\d+)\" \"(\d+)\"
		$JukeBoxPlay = $D[0];
		$JukeBoxTrack = $D[1];
		LogWrite("JukeBoxPlayNow: " . $JukeBoxPlay . ", " . $JukeBoxTrack);

		if ($this->listeningSocket->getLPECListeningSocket()->CallClients('SelectPlaylist') == false)
		    $Continue = false;

		if ($this->listeningSocket->getLPECListeningSocket()->CallClients('Stop') == false)
		    $Continue = false;

		$musicDB = new MusicDB();
		if ($this->listeningSocket->getLPECListeningSocket()->CallClients1('DeleteAll', $musicDB) == false)
		$Continue = false;
		if ($this->listeningSocket->getLPECListeningSocket()->CallClients4('InsertDIDL_list', $musicDB, $JukeBoxPlay, $JukeBoxTrack, 0) == false)
		    $Continue = false;
		$musicDB->close();

		//Send("ACTION Ds/Jukebox 3 SetCurrentPreset \"" . $JukeBoxPlay . "\"");

		if ($this->listeningSocket->getLPECListeningSocket()->CallClients('Play') == false)
		    $Continue = false;
		if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Playlist 1 IdArray") == false)
		    $Continue = false;
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Jukebox PlayNext ") !== false) 
	    {
		//Jukebox PlayNext \"(\d+)\" \"(\d+)\"
		$JukeBoxPlay = $D[0];
		$JukeBoxTrack = $D[1];
		LogWrite("JukeBoxPlayNext: " . $JukeBoxPlay . ", " . $JukeBoxTrack);

		if ($this->listeningSocket->getLPECListeningSocket()->CallClients('SelectPlaylist') == false)
		    $Continue = false;

		$musicDB = new MusicDB();
		if ($this->listeningSocket->getLPECListeningSocket()->CallClients4('InsertDIDL_list', $musicDB, $JukeBoxPlay, $JukeBoxTrack, $this->getState()->getState('Id')) == false)
		    $Continue = false;
		$musicDB->close();

		if ($this->listeningSocket->getLPECListeningSocket()->CallClients('Play') == false)
		    $Continue = false;
		if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Playlist 1 IdArray") == false)
		    $Continue = false;

		if ($DEBUG > 0)
		{
		    //LogWrite($message);
		    //print_r($State);
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Jukebox PlayLater ") !== false) 
	    {
		//Jukebox PlayLater \"(\d+)\" \"(\d+)\"
		$JukeBoxPlay = $D[0];
		$JukeBoxTrack = $D[1];
		LogWrite("JukeBoxPlayLater: " . $JukeBoxPlay . ", " . $JukeBoxTrack);

		if ($this->listeningSocket->getLPECListeningSocket()->CallClients('SelectPlaylist') == false)
		    $Continue = false;

		$musicDB = new MusicDB();
		if ($this->listeningSocket->getLPECListeningSocket()->CallClients4('InsertDIDL_list', $musicDB, $JukeBoxPlay, $JukeBoxTrack, end($this->getState()->getState('IdArray'))) == false)
		    $Continue = false;
		$musicDB->close();


		if ($this->listeningSocket->getLPECListeningSocket()->CallClients('Play') == false)
		    $Continue = false;
		$this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Playlist 1 IdArray");

		if ($DEBUG > 0)
		{
		    //LogWrite($message);
		    //print_r($State);
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Jukebox PlayRandomTracks ") !== false) 
	    {
		//Jukebox PlayRandomTracks \"(\d+)\" \"(\d+)\"
		$JukeBoxFirstAlbum = $D[0];
		$JukeBoxLastAlbum = $D[1];
		LogWrite("JukeBoxPlayRandomTracks: " . $JukeBoxFirstAlbum . ", " . $JukeBoxLastAlbum);

		if ($this->listeningSocket->getLPECListeningSocket()->CallClients('SelectPlaylist') == false)
		    $Continue = false;

		$musicDB = new MusicDB();
		if ($this->getState()->getState('TransportState') == "Stopped")
		{
		    if ($this->listeningSocket->getLPECListeningSocket()->CallClients1('DeleteAll', $musicDB) == false)
			$Continue = false;
		}

		for ($i = 0; $i < 50; $i++) 
		{
		    $RandomPreset = rand($JukeBoxFirstAlbum, $JukeBoxLastAlbum);
		    $RandomTrack = rand(1, $musicDB->NumberOfTracks($RandomPreset));
		    if ($i == 0)
		    {
			if ($this->listeningSocket->getLPECListeningSocket()->CallClients4('InsertDIDL_list', $musicDB, $RandomPreset, $RandomTrack, end($this->getState()->getState('IdArray'))) == false)
			    $Continue = false;
		    }
		    else
		    {
			if ($this->listeningSocket->getLPECListeningSocket()->CallClients4('InsertDIDL_list', $musicDB, $RandomPreset, $RandomTrack, "%NewId%") == false)
			    $Continue = false;
		    }
		}

		$musicDB->close();

		if ($this->listeningSocket->getLPECListeningSocket()->CallClients('Play') == false)
		    $Continue = false;
		$this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Playlist 1 IdArray");

		if ($DEBUG > 0)
		{
		    //LogWrite($message);
		    //print_r($State);
		}
		$DataHandled = true;
	    }
	}
	elseif (strpos($message, "Volume") !== false) 
	{
	    $D = getParameters($message);
	    // Here things happens - we execute the actions sent from the
	    // application, by issuing a number of ACTIONs.

	    if (strpos($message, "Volume Set ") !== false) 
	    {
		//Volume Set \"(\d+)\"
		$value = $D[0];
		if ($value > $this->getState()->getState('MAX_VOLUME'))
		{
		    $value = $this->getState()->getState('MAX_VOLUME');
		}
		if ($value != $this->getState()->getState('Volume') && $value != "")
		{
		    LogWrite("VolumeSet: " . $value);
		    if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 SetVolume \"" . $value . "\"") == false)
			$Continue = false;
		    $this->getState()->setState('Volume', $value);
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Volume Incr5") !== false) 
	    {
		//Volume Incr5
		if ($this->getState()->getState('Volume') < $this->getState()->getState('MAX_VOLUME') -5)
		{
		    LogWrite("VolumeIncr5: ");
		    $value = $this->getState()->getState('Volume');
		    $value = $value + 5;
		    if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		    if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		    if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		    if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		    if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeInc") == false)
			$Continue = false;
		    $this->getState()->setState('Volume', $value);
		}
		else
		{
		    LogWrite("VolumeIncr: IGNORED MAX_VOLUME REACHED");
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Volume Incr") !== false) 
	    {
		//Volume Incr
		if ($this->getState()->getState('Volume') < $this->getState()->getState('MAX_VOLUME'))
		{
		    LogWrite("VolumeIncr: ");
		    $value = $this->getState()->getState('Volume');
		    $value = $value + 1;
		    if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeInc") == false)
		        $Continue = false;
		    $this->getState()->setState('Volume', $value);
		}
		else
		{
		    LogWrite("VolumeIncr: IGNORED MAX_VOLUME REACHED");
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Volume Decr5") !== false) 
	    {
		//Volume Decr5
		LogWrite("VolumeDecr: ");
		$value = $this->getState()->getState('Volume');
		$value = $value - 5;
		if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		$this->getState()->setState('Volume', $value);
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Volume Decr") !== false) 
	    {
		//Volume Decr
		LogWrite("VolumeDecr: ");
		$value = $this->getState()->getState('Volume');
		$value = $value - 1;
		if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 VolumeDec") == false)
		    $Continue = false;
		$this->getState()->setState('Volume', $value);
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Volume Reset") !== false) 
	    {
		//Volume Reset
		LogWrite("VolumeReset: ");
		$value = 30;
		LogWrite("VolumeSet: " . $value);
		if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Volume 1 SetVolume \"" . $value . "\"") == false)
		    $Continue = false;
		$this->getState()->setState('Volume', $value);
		$DataHandled = true;
	    }
	}
	elseif (strpos($message, "Control") !== false) 
	{
	    // Here things happens - we execute the actions sent from the
	    // application, by issuing a number of ACTIONs.

	    if (strpos($message, "Control Play") !== false) 
	    {
		//Control Play
		if ($this->getState()->getState('TransportState') === "Stopped" || $this->getState()->getState('TransportState') === "Paused")
		{
		    LogWrite("ControlPlay: ");
		    if ($this->listeningSocket->getLPECListeningSocket()->CallClients('Play') == false)
			$Continue = false;
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Control Pause") !== false) 
	    {
		//Control Pause
		if ($this->getState()->getState('TransportState') !== "Paused")
		{
		    LogWrite("ControlPause: ");
		    if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Playlist 1 Pause") == false)
			$Continue = false;
		}
		else
		{
		    LogWrite("ControlPause - restart: ");
		    if ($this->listeningSocket->getLPECListeningSocket()->CallClients('Play') == false)
			$Continue = false;
		}

		$DataHandled = true;
	    }
	    elseif (strpos($message, "Control Stop") !== false) 
	    {
		//Control Stop
		if ($this->getState()->getState('TransportState') !== "Stopped")
		{
		    LogWrite("ControlStop: ");
		    if ($this->listeningSocket->getLPECListeningSocket()->CallClients('Stop') == false)
			$Continue = false;
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Control Next") !== false) 
	    {
		//Control Next
		if ($this->getState()->getState('TransportState') != "Stopped")
		{
		    LogWrite("ControlNext: ");
		    if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Playlist 1 Next") == false)
			$Continue = false;
		}
		$DataHandled = true;
	    }
	    elseif (strpos($message, "Control Previous") !== false) 
	    {
		//Control Previous
		if ($this->getState()->getState('TransportState') != "Stopped")
		{
		    LogWrite("ControlPrevious: ");
		    if ($this->listeningSocket->getLPECListeningSocket()->Send("ACTION Ds/Playlist 1 Previous") == false)
			$Continue = false;
		}
		$DataHandled = true;
	    }
	}
	elseif (strpos($message, "Source") !== false) 
	{
	    // Here things happens - we execute the actions sent from the
	    // application, by issuing a number of ACTIONs.

	    if (strpos($message, "Source Off") !== false) 
	    {
		//Source Off
		if ($this->getState()->getState('Standby') == "false")
		{
		    if ($this->listeningSocket->getLPECListeningSocket()->Send('ACTION Ds/Product 1 SetStandby "true"') == false)
			$Continue = false;
		    $this->getState()->setState('Standby', true);
		}
		$DataHandled = true;
	    }
	    else
	    {
		if ($this->getState()->getState('Standby') == "true")
		{
		    if ($this->listeningSocket->getLPECListeningSocket()->Send('ACTION Ds/Product 1 SetStandby "false"') == false)
			$Continue = false;
		    $this->getState()->setState('Standby', true);
		}

		if (strpos($message, "Source Playlist") !== false) 
		{
		    //Source Playlist
		    if ($this->getState()->getState('SourceIndex') != $this->getState()->getStateArray('SourceName', 'Playlist'))
		    {
			if ($this->listeningSocket->getLPECListeningSocket()->Send('ACTION Ds/Product 1 SetSourceIndex "' . $this->getState()->getStateArray('SourceName', 'Playlist') . '"') == false)
			    $Continue = false;
		    }
		    if ($this->listeningSocket->getLPECListeningSocket()->CallClients('Play') == false)
			$Continue = false;
		    $DataHandled = true;
		}
		elseif (strpos($message, "Source TV") !== false) 
		{
		    //Source TV
		    if ($this->getState()->getState('SourceIndex') != $this->getState()->getStateArray('SourceName', 'TV'))
		    {
			if ($this->listeningSocket->getLPECListeningSocket()->Send('ACTION Ds/Product 1 SetSourceIndex "' . $this->getState()->getStateArray('SourceName', 'TV') . '"') == false)
			    $Continue = false;
		    }
		    $DataHandled = true;
		}
		elseif (strpos($message, "Source Radio") !== false) 
		{
		    //Source Radio
		    if ($this->getState()->getState('SourceIndex') != $this->getState()->getStateArray('SourceName', 'Radio'))
		    {
			if ($this->listeningSocket->getLPECListeningSocket()->Send('ACTION Ds/Product 1 SetSourceIndex "' . $this-getState()->getStateArray('SourceName', 'Radio') . '"') == false)
			    $Continue = false;
		    }
		    $DataHandled = true;
		}
		elseif (strpos($message, "Source NetAux") !== false) 
		{
		    //Source NetAux
		    if ($this->getState()->getState('SourceIndex') != $this->getState()->getStateArray('SourceName', 'Net Aux'))
		    {
			if ($this->listeningSocket->getLPECListeningSocket()->Send('ACTION Ds/Product 1 SetSourceIndex "' . $this->getState()->getStateArray('SourceName', 'Net Aux') . '"') == false)
			    $Continue = false;
		    }
		    $DataHandled = true;
		}
	    }
	}
	elseif (strpos($message, "State") !== false) 
	{
	    LogWrite("HTState: " . $this->getState()->dump());
	    $seri = $this->getState()->Serialize();
	    LogWrite("Serialized: " . $seri);
	    $this->Send($seri);
	    $DataHandled = true;
	}

	return $DataHandled;
    }
}

?>

