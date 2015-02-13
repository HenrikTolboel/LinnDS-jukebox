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


function BuildSelect()
{
    $SelectStmt = "SELECT distinct ArtistFirst FROM Album WHERE RootMenuNo == :q1 order by ArtistFirst";

    //echo "BuildSelect: $SelectStmt\n";

    return $SelectStmt;
}

$SelectStmtAlbum = BuildSelect();
//echo "$SelectStmt\n";


$db = new SQLite3($DATABASE_FILENAME);


$stmtAlbum = $db->prepare($SelectStmtAlbum);

$stmtAlbum->bindValue(":q1", $menu);

$resultAlbum = $stmtAlbum->execute();
$R = array();

for ($alpha = 0; $alpha < $ALPHABET_SIZE; $alpha++)
{
    $Letter = $ALPHABET[$alpha];
    if ($Letter == "#")
	$R["NUM"] = 0;
    else
	$R[$Letter] = 0;
}

$i = 0;
// fetchArray(SQLITE3_NUM | SQLITE_ASSOC | SQLITE_BOTH) - default both
while ($row = $resultAlbum->fetchArray(SQLITE3_ASSOC)) {
    if ($row[ArtistFirst] == "#")
	$R["NUM"] = 1;
    else
    $R[$row[ArtistFirst]] = 1;
    $i++;
}

$stmtAlbum->close();
$db->close();


//print_r($R);
    echo json_encode($R);

?>
