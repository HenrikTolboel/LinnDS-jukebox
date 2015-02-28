<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2015 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");

$queuedb = new SQLite3($QUEUEDB_FILENAME);

$QueueStmt = $queuedb->prepare("SELECT Q.LinnId, Q.Preset, Q.TrackSeq FROM Queue Q, Sequence S WHERE Q.LinnId == S.LinnId ORDER BY S.Seq");


$result = $QueueStmt->execute();

$Queue = array();
$QueueCount = 0;
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $Queue[$QueueCount] = $row;
    $QueueCount++;
}

$QueueStmt->close();

$QueueStateStmt = $queuedb->prepare("SELECT * FROM State");

$result = $QueueStateStmt->execute();

$State = array();
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $State[$row[Id]] = $row[Value];
}

$QueueStateStmt->close();

$queuedb->close();

$db = new SQLite3($DATABASE_FILENAME);

$TrackStmt = $db->prepare("SELECT :PlayState AS PlayState, * FROM Tracks WHERE Preset == :Preset AND TrackSeq == :TrackSeq");

$PlayState = "Played";
for ($i = 0; $i < $QueueCount; $i++)
{
    if ($Queue[$i][LinnId] == $State[LinnId])
    {
	$PlayState = "Playing";
	$TrackStmt->bindValue(":PlayState", $PlayState);
	$PlayState = "Pending";
    }
    else
    {
	$TrackStmt->bindValue(":PlayState", $PlayState);
    }
    $TrackStmt->bindValue(":Preset", $Queue[$i][Preset]);
    $TrackStmt->bindValue(":TrackSeq", $Queue[$i][TrackSeq]);

    $result = $TrackStmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	$R[$i] = $row;
    }

    $TrackStmt->reset();
}

$TrackStmt->close();

$db->close();


//print_r($Queue);
//print_r($State);
//print_r($R);

echo json_encode($R);

?>
