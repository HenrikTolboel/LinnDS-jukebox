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

// action is a space seperated list of words... We split in words and search for them individually.

$words = explode(" ", $action);


$first = true;
$SelectStmt = "SELECT * FROM Tracks WHERE ";
foreach ($words as $key => $value) {
    //echo "key: $key, value: $value\n";

    $Add = "(Title LIKE :q$key OR Album LIKE :q$key OR ArtistPerformer LIKE :q$key)";

    if ($first) {
	$SelectStmt .= "$Add";
    }
    else
    {
	$SelectStmt .= "AND " . "$Add";
    }
    $first = false;
}

//echo "$SelectStmt\n";


$db = new SQLite3('mysqlitedb.db');


$stmt = $db->prepare($SelectStmt);

foreach ($words as $key => $value) {
    //echo "key: $key, value: $value\n";

    $stmt->bindValue(":q$key", "%$value%");
}

$result = $stmt->execute();

$i = 0;
while ($row = $result->fetchArray()) {
    $R[$i] = $row;
    $i++;
}

    echo json_encode($R);

?>
