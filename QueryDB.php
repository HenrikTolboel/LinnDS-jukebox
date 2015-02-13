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

$action = $_GET["action"];

//$action = "cougar america";
//$action = "yes land";
//$action = "look in";

// action is a space seperated list of words... We split in words and search for them individually.

$words = explode(" ", $action);


define("SINGLE_TRACKS_ONLY", 0);
define("ALBUMS_ONLY", 1);
define("ALBUM_PRESET_ONLY", 2);
function BuildSelect($words, $mode)
{
    $first = true;
    if ($mode == SINGLE_TRACKS_ONLY)
    {
	$SelectStmt = "SELECT 'Track' as Type, * FROM Tracks WHERE ";
	// Dont take those where artist and album matches
	$SelectStmt .= " (Preset NOT IN (" . BuildSelect($words, 2) . "))";
	$first = false;
    }
    if ($mode == ALBUMS_ONLY)
    {
	$SelectStmt = "SELECT 'Album' as Type, * FROM Tracks WHERE ";
	$SelectStmt .= "(TrackSeq = 1)"; // Take first track as representative for the album
	$first = false;
    }
    if ($mode == ALBUM_PRESET_ONLY)
    {
	$SelectStmt = "SELECT Preset FROM Tracks WHERE ";
	$SelectStmt .= "(TrackSeq = 1)"; // Take first track as representative for the album
	$first = false;
    }

    foreach ($words as $key => $value) {
	//echo "key: $key, value: $value\n";

	if ($mode == SINGLE_TRACKS_ONLY)
	    $Add = "(Title LIKE :q$key OR Album LIKE :q$key OR ArtistPerformer LIKE :q$key)";
	else
	    $Add = "(Album LIKE :q$key OR ArtistPerformer LIKE :q$key)";

	if ($first) {
	    $SelectStmt .= "$Add";
	}
	else
	{
	    $SelectStmt .= " AND " . "$Add";
	}
	$first = false;
    }

    //echo "BuildSelect: $SelectStmt\n";

    return $SelectStmt;
}

$SelectStmtAlbum = BuildSelect($words, ALBUMS_ONLY);
$SelectStmt = BuildSelect($words, SINGLE_TRACKS_ONLY);
//echo "$SelectStmt\n";


$db = new SQLite3($DATABASE_FILENAME);


$stmtAlbum = $db->prepare($SelectStmtAlbum);
$stmt = $db->prepare($SelectStmt);

foreach ($words as $key => $value) {
    //echo "key: $key, value: $value\n";

    $stmtAlbum->bindValue(":q$key", "%$value%");
    $stmt->bindValue(":q$key", "%$value%");
}

$resultAlbum = $stmtAlbum->execute();
$result = $stmt->execute();

$R = array();
$i = 0;
// fetchArray(SQLITE3_NUM | SQLITE_ASSOC | SQLITE_BOTH) - default both
while ($row = $resultAlbum->fetchArray(SQLITE3_ASSOC)) {
    $R[$i] = $row;
    $i++;
}
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $R[$i] = $row;
    $i++;
}

$stmtAlbum->close();
$stmt->close();
$db->close();


//print_r($R);
    echo json_encode($R);

?>
