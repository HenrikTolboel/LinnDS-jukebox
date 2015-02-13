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

$preset = $_GET["preset"];

function BuildSelect()
{
    $SelectStmt = "SELECT * FROM Tracks WHERE Preset == :q1 order by TrackSeq";

    //echo "BuildSelect: $SelectStmt\n";

    return $SelectStmt;
}

$SelectStmtAlbum = BuildSelect();
//echo "$SelectStmt\n";


$db = new SQLite3($DATABASE_FILENAME);


$stmtAlbum = $db->prepare($SelectStmtAlbum);

$stmtAlbum->bindValue(":q1", $preset);

$resultAlbum = $stmtAlbum->execute();

$R = array();
$i = 0;
// fetchArray(SQLITE3_NUM | SQLITE_ASSOC | SQLITE_BOTH) - default both
while ($row = $resultAlbum->fetchArray(SQLITE3_ASSOC)) {
    $R[$i] = $row;
    $i++;
}

$stmtAlbum->close();
$db->close();


//print_r($R);
    echo json_encode($R);

?>
