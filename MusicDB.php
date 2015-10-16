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

    private $insertAlbumStmt = 0;
    private $insertTracksStmt = 0;

    function __construct()
    {
	global $DATABASE_FILENAME;

	$this->open($DATABASE_FILENAME, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
	$this->CreateTables();
    }

    private function CreateTables()
    {
	static $TablesChecked = 0;
	if ($TablesChecked != 0)
	    return;
	$TablesChecked++;

	LogWrite("MusicDB::CreateTables - checking existance of tables and indexes...");

	$this->exec('CREATE TABLE IF NOT EXISTS Tracks (Preset INTEGER, TrackSeq INTEGER, URL STRING, Duration STRING, Title STRING, Year STRING, AlbumArt STRING, ArtWork STRING, Genre STRING, ArtistPerformer STRING, ArtistComposer STRING, ArtistAlbumArtist STRING, ArtistConductor STRING, Album STRING, TrackNumber STRING, DiscNumber STRING, DiscCount STRING)');

	$this->exec('CREATE TABLE IF NOT EXISTS Album (Preset INTEGER, NoTracks INTEGER, URI STRING, ArtistFirst STRING, SortArtist STRING, Artist STRING, Album STRING, Date STRING, Genre STRING, MusicTime INTEGER, ImageURI STRING, TopDirectory STRING, RootMenuNo INTEGER)');

	// Tables used in LinnDS-jukebox-daemon.php
	$this->exec('CREATE TABLE IF NOT EXISTS Queue (LinnId INTEGER, Preset INTEGER, TrackSeq INTEGER, URL STRING, XML STRING)');
	$this->exec('CREATE TABLE IF NOT EXISTS State (Id STRING, Value STRING)');
	$this->exec('CREATE TABLE IF NOT EXISTS Sequence (Seq INTEGER, LinnId INTEGER)');


	// Create indexes
	$this->exec('CREATE INDEX IF NOT EXISTS Album_idx1 ON Album (Preset)');
	$this->exec('CREATE INDEX IF NOT EXISTS Tracks_idx1 ON Tracks (Preset, TrackSeq)');
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

    function InsertAlbumStmt()
    {
	if ($this->insertAlbumStmt == 0)
	    $this->insertAlbumStmt = $this->prepare('INSERT INTO Album (Preset, NoTracks, URI, ArtistFirst, SortArtist, Artist, Album, Date, Genre, MusicTime, ImageURI, TopDirectory, RootMenuNo) VALUES  (:Preset, :NoTracks, :URI, :ArtistFirst, :SortArtist, :Artist, :Album, :Date, :Genre, :MusicTime, :ImageURI, :TopDirectory, :RootMenuNo)');

	return $this->insertAlbumStmt;
    }

    function InsertTracksStmt()
    {
	if ($this->insertTracksStmt == 0)
	    $this->insertTracksStmt = $this->prepare('INSERT INTO Tracks (Preset, TrackSeq, URL, Duration, Title, Year, AlbumArt, ArtWork, Genre, ArtistPerformer, ArtistComposer, ArtistAlbumArtist, ArtistConductor, Album, TrackNumber, DiscNumber, DiscCount) VALUES  (:Preset, :TrackSeq, :URL, :Duration, :Title, :Year, :AlbumArt, :ArtWork, :Genre, :ArtistPerformer, :ArtistComposer, :ArtistAlbumArtist, :ArtistConductor, :Album, :TrackNumber, :DiscNumber, :DiscCount)');

	return $this->insertTracksStmt;
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

    public function NumberOfTracks($Preset)
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

    public function PresetURL($preset)
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

    public function InsertAlbum($Preset, $NoTracks, $URI, $ArtistFirst, $Artist, $SortArtist, 
	$Album, $Date, $Genre, $MusicTime, $ImageURI, $TopDirectory, $RootMenuNo)
    {
	$this->InsertAlbumStmt()->bindParam(':Preset', $Preset);
	$this->InsertAlbumStmt()->bindParam(':NoTracks', $NoTracks);
	$this->InsertAlbumStmt()->bindParam(':URI', $URI);
	$this->InsertAlbumStmt()->bindParam(':ArtistFirst', $ArtistFirst);
	$this->InsertAlbumStmt()->bindParam(':Artist', $Artist);
	$this->InsertAlbumStmt()->bindParam(':SortArtist', $SortArtist);
	$this->InsertAlbumStmt()->bindParam(':Album', $Album);
	$this->InsertAlbumStmt()->bindParam(':Date', $Date);
	$this->InsertAlbumStmt()->bindParam(':Genre', $Genre);
	$this->InsertAlbumStmt()->bindParam(':MusicTime', $MusicTime);
	$this->InsertAlbumStmt()->bindParam(':ImageURI', $ImageURI);
	$this->InsertAlbumStmt()->bindParam(':TopDirectory', $TopDirectory);
	$this->InsertAlbumStmt()->bindParam(':RootMenuNo', $RootMenuNo);

	$result = $this->InsertAlbumStmt()->execute();

	$this->InsertAlbumStmt()->reset();
    }

    public function InsertTracks($Preset, $TrackSeq, $URL, $DURATION, $TITLE, $YEAR, 
	$AlbumArt, $ArtWork, $Genre, $Artist_Performer, $Artist_Composer, 
	$Artist_AlbumArtist, $Artist_Conductor, $ALBUM, $TRACK_NUMBER, 
	$DISC_NUMBER, $DISC_COUNT)
    {
	$this->InsertTracksStmt()->bindParam(':Preset', $Preset);
	$this->InsertTracksStmt()->bindParam(':TrackSeq', $TrackSeq);
	$this->InsertTracksStmt()->bindParam(':URL', $URL);
	$this->InsertTracksStmt()->bindParam(':Duration', $DURATION);
	$this->InsertTracksStmt()->bindParam(':Title', $TITLE);
	$this->InsertTracksStmt()->bindParam(':Year', $YEAR);
	$this->InsertTracksStmt()->bindParam(':AlbumArt', $AlbumArt);
	$this->InsertTracksStmt()->bindParam(':ArtWork', $ArtWork);
	$this->InsertTracksStmt()->bindParam(':Genre', $Genre);
	$this->InsertTracksStmt()->bindParam(':ArtistPerformer', $Artist_Performer);
	$this->InsertTracksStmt()->bindParam(':ArtistComposer', $Artist_Composer);
	$this->InsertTracksStmt()->bindParam(':ArtistAlbumArtist', $Artist_AlbumArtist);
	$this->InsertTracksStmt()->bindParam(':ArtistConductor', $Artist_Conductor);
	$this->InsertTracksStmt()->bindParam(':Album', $ALBUM);
	$this->InsertTracksStmt()->bindParam(':TrackNumber', $TRACK_NUMBER);
	$this->InsertTracksStmt()->bindParam(':DiscNumber', $DISC_NUMBER);
	$this->InsertTracksStmt()->bindParam(':DiscCount', $DISC_COUNT);

	$result = $this->InsertTracksStmt()->execute();

	$this->InsertTracksStmt()->reset();
    }

}


function test()
{
    $musicDB = new MusicDB();

    $musicDB->SetState("State1", "Value1");
    $musicDB->SetState("State2", "Value1");

    $musicDB->close();
}
//test();
//test();
?>
