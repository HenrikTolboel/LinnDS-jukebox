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

$RevNo = $_GET["RevNo"];

$db = new SQLite3($DATABASE_FILENAME);

$Queue = array();
$Queue[0] = array();

$Stmt = $db->prepare("SELECT MAX(Seq) As MaxSeq, MAX(LinnId) AS MaxLinnId FROM Sequence");
$result = $Stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) 
{
    $Queue[0][MaxSeq] = $row[MaxSeq];
    $Queue[0][MaxLinnId] = $row[MaxLinnId];
}
$Stmt->close();

$Stmt = $db->prepare("SELECT * FROM State");
$result = $Stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) 
{
    $Queue[0][$row[Id]] = $row[Value];
}
$Stmt->close();

if ($RevNo == -1 || $Queue[0][RevNo] != $RevNo)
{
    $QueueStmt = $db->prepare("SELECT S.Seq-L.Seq AS PlayState, Q.LinnId AS LinnId, S.Seq AS Seq, T.* FROM Queue Q, Sequence S, Tracks T, (SELECT S2.Seq FROM Sequence S2, (SELECT ST.value FROM State ST where ST.Id == 'LinnId') L2 WHERE S2.LinnId == L2.value) L WHERE Q.LinnId == S.LinnId AND Q.Preset == T.Preset AND Q.TrackSeq == T.TrackSeq ORDER BY S.Seq");


    $result = $QueueStmt->execute();

    $QueueCount = 1;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
	if ($row[PlayState] < 0)
	    $row[PlayState] = "Played";
	else if ($row[PlayState] > 0)
	    $row[PlayState] = "Pending";
	else
	    $row[PlayState] = "Playing";
	$Queue[$QueueCount] = AbsoluteURL($row);
	$QueueCount++;
    }

    $QueueStmt->close();
}

$db->close();

//print_r($Queue);
echo json_encode($Queue);
?>
