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

$menu = $_GET["menu"];
$artistfirst = $_GET["artistfirst"];

if ($artistfirst == "newest")
{
    $SelectStmtAlbum = "SELECT * FROM Album order by MusicTime DESC Limit 50";
}
elseif ($artistfirst != "*") {
    $SelectStmtAlbum = "SELECT * FROM Album WHERE RootMenuNo == :q1 and ArtistFirst == :q2 order by SortArtist, Date, Album";
}
else
{
    $SelectStmtAlbum = "SELECT * FROM Album WHERE RootMenuNo == :q1 order by SortArtist, Date, Album";
}


$db = new SQLite3($DATABASE_FILENAME);


$stmtAlbum = $db->prepare($SelectStmtAlbum);


if ($artistfirst != "newest") {
    $stmtAlbum->bindValue(":q1", $menu);
    if ($artistfirst != "*") {
	$stmtAlbum->bindValue(":q2", $artistfirst);
    }
}

$resultAlbum = $stmtAlbum->execute();

$R = array();
$i = 0;
// fetchArray(SQLITE3_NUM | SQLITE_ASSOC | SQLITE_BOTH) - default both
while ($row = $resultAlbum->fetchArray(SQLITE3_ASSOC)) {
    $R[$i] = AbsoluteURL($row);
    $i++;
}

$stmtAlbum->close();
$db->close();


//print_r($R);
    echo json_encode($R);

?>
