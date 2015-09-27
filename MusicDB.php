#!/usr/bin/php
<?php
/*!
* MusicDB
*
* Copyright (c) 2015-2015 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");

class MusicDB extends SQLite3
{
    private $insertQueueStmt = 0;
    private $updateQueueStmt = 0;
    private $deleteQueueStmt = 0;

    private $insertStateStmt = 0;
    private $updateStateStmt = 0;

    private $insertSequenceStmt = 0;
    private $deleteSequenceStmt = 0;

    private $numberOfTracksStmt = 0;
    private $presetURLStmt = 0;

    function __construct()
    {
	global $DATABASE_FILENAME;

	$this->open($DATABASE_FILENAME, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
	$this->CreateTables();
    }

    private function CreateTables()
    {
	$this->exec('CREATE TABLE IF NOT EXISTS Tracks (Preset INTEGER, TrackSeq INTEGER, URL STRING, Duration STRING, Title STRING, Year STRING, AlbumArt STRING, ArtWork STRING, Genre STRING, ArtistPerformer STRING, ArtistComposer STRING, ArtistAlbumArtist STRING, ArtistConductor STRING, Album STRING, TrackNumber STRING, DiscNumber STRING, DiscCount STRING)');

	$this->exec('CREATE TABLE IF NOT EXISTS Album (Preset INTEGER, NoTracks INTEGER, URI STRING, ArtistFirst STRING, SortArtist STRING, Artist STRING, Album STRING, Date STRING, Genre STRING, MusicTime INTEGER, ImageURI STRING, TopDirectory STRING, RootMenuNo INTEGER)');

	// Tables used in LinnDS-jukebox-daemon.php
	$this->exec('CREATE TABLE IF NOT EXISTS Queue (LinnId INTEGER, Preset INTEGER, TrackSeq INTEGER, URL STRING, XML STRING)');
	$this->exec('CREATE TABLE IF NOT EXISTS State (Id STRING, Value STRING)');
	$this->exec('CREATE TABLE IF NOT EXISTS Sequence (Seq INTEGER, LinnId INTEGER)');
    }

    function InsertQueueStmt()
    {
	if ($this->insertQueueStmt == 0)
	    $this->insertQueueStmt = $this->prepare('INSERT INTO Queue (LinnId, Preset, TrackSeq, URL, XML) VALUES (:LinnId, :Preset, :TrackSeq, :URL, :XML)');

	return $this->insertQueueStmt;
    }

    function UpdateQueueStmt()
    {
	if ($this->updateQueueStmt == 0)
	    $this->updateQueueStmt = $this->prepare('UPDATE Queue set LinnId = :LinnId where (LinnId == :LinnId) OR (LinnId == -1 and URL == :URL)');

	return $this->updateQueueStmt;
    }

    function DeleteQueueStmt()
    {
	if ($this->deleteQueueStmt == 0)
	    $this->deleteQueueStmt = $this->prepare('DELETE FROM Queue');

	return $this->deleteQueueStmt;
    }


    function InsertStateStmt()
    {
	if ($this->insertStateStmt == 0)
	    $this->insertStateStmt = $this->prepare('INSERT INTO State (Id, Value) VALUES (:Id, :Value)');
	return $this->insertStateStmt;
    }

    function UpdateStateStmt()
    {
	if ($this->updateStateStmt == 0)
	    $this->updateStateStmt = $this->prepare('UPDATE State set Value = :Value WHERE Id = :Id');

	return $this->updateStateStmt;
    }

    function InsertSequenceStmt()
    {
	if ($this->insertSequenceStmt == 0)
	    $this->insertSequenceStmt = $this->prepare('INSERT INTO Sequence (Seq, LinnId) VALUES (:Seq, :LinnId)');

	return $this->insertSequenceStmt;
    }

    function DeleteSequenceStmt()
    {
	if ($this->deleteSequenceStmt == 0)
	    $this->deleteSequenceStmt = $this->prepare('DELETE FROM Sequence');

	return $this->deleteSequenceStmt;
    }

    function NumberOfTracksStmt()
    {
	if ($this->numberOfTracksStmt == 0)
	    $this->numberOfTracksStmt = $this->prepare('SELECT NoTracks FROM Album WHERE Preset == :q1');

	return $this->numberOfTracksStmt;
    }

    function PresetURLStmt()
    {
	if ($this->presetURLStmt == 0)
	    $this->presetURLStmt = $this->prepare('SELECT URI FROM Album WHERE Preset == :q1');

	return $this->presetURLStmt;
    }

    public function InsertQueue($LinnId, $Preset, $TrackSeq, $URL, $XML)
    {
	$this->InsertQueueStmt()->bindParam(':LinnId', $LinnId);
	$this->InsertQueueStmt()->bindParam(':Preset', $Preset);
	$this->InsertQueueStmt()->bindParam(':TrackSeq', $TrackSeq);
	$this->InsertQueueStmt()->bindParam(':URL', $URL);
	$this->InsertQueueStmt()->bindParam(':XML', $XML);

	$result = $this->InsertQueueStmt()->execute();

	$r = $this->changes();
	LogWrite("InsertQueue: $LinnId, $Preset, $TrackSeq, $URL -> $r");
	$this->InsertQueueStmt()->reset();
    }

    public function UpdateQueue($LinnId, $Preset, $TrackSeq, $URL, $XML)
    {
	$this->UpdateQueueStmt()->bindParam(':LinnId', $LinnId);
	$this->UpdateQueueStmt()->bindParam(':URL', $URL);

	$result = $this->UpdateQueueStmt()->execute();

	$r = $this->changes();
	LogWrite("UpdateQueue: $LinnId, $Preset, $TrackSeq, $URL -> $r");
	if ($this->changes() < 1)
	{
	    $this->InsertQueue($LinnId, $Preset, $TrackSeq, $URL, $XML);
	}

	$this->UpdateQueueStmt()->reset();
    }

    public function DeleteQueue()
    {
	$result = $this->DeleteQueueStmt()->execute();

	$r = $this->changes();
	LogWrite("DeleteQueue: -> $r");

	$this->DeleteQueueStmt()->reset();
    }

    public function SetState($Id, $Value)
    {
	$this->UpdateStateStmt()->bindParam(':Id', $Id);
	$this->UpdateStateStmt()->bindParam(':Value', $Value);

	$result = $this->UpdateStateStmt()->execute();

	$r = $this->changes();
	LogWrite("SetState: $Id, $Value -> $r");
	if ($this->changes() < 1)
	{
	    $this->InsertStateStmt()->bindParam(':Id', $Id);
	    $this->InsertStateStmt()->bindParam(':Value', $Value);

	    $result = $this->InsertStateStmt()->execute();

	    $r = $this->changes();
	    LogWrite("SetState-Insert: $Id, $Value -> $r");
	    $this->InsertStateStmt()->reset();
	}

	$this->UpdateStateStmt()->reset();
    }

    public function InsertSequence($Seq, $LinnId)
    {
	$this->InsertSequenceStmt()->bindParam(':Seq', $Seq);
	$this->InsertSequenceStmt()->bindParam(':LinnId', $LinnId);

	$result = $this->InsertSequenceStmt()->execute();

	$r = $this->changes();
	LogWrite("InsertSequence: $Seq, $LinnId -> $r");
	$this->InsertSequenceStmt()->reset();
    }

    public function DeleteSequence()
    {
	$result = $this->DeleteSequenceStmt()->execute();

	$r = $this->changes();
	LogWrite("DeleteSequence: -> $r");

	$this->DeleteSequenceStmt()->reset();
    }

    function NumberOfTracks($Preset)
    {
	$this->NumberOfTracksStmt()->bindValue(":q1", $Preset);

	$result = $this->NumberOfTracksStmt()->execute();

	$R = array();
	$i = 0;

	while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	    $R[$i] = $row;
	    $i++;
	}

	$this->NumberOfTracksStmt()->reset();

	return $R[0][NoTracks];
    }

    function PresetURL($preset)
    {
	$this->PresetURLStmt()->bindValue(":q1", $preset);

	$result = $this->PresetURLStmt()->execute();

	$R = array();
	$i = 0;

	while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	    $R[$i] = $row;
	    $i++;
	}

	$this->PresetURLStmt()->reset();

	return AbsolutePath(ProtectPath($R[0][URI]));
    }


}


function test()
{
    $musicDB = new MusicDB();

    $musicDB->SetState("State1", "Value1");
    $musicDB->SetState("State2", "Value1");

    $musicDB->close();
}
test();



?>
