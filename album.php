<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/


require_once("setup.php");
require_once("Functions.php");

function Album($DIDLFile, $FolderImg)
{
    global $LINN_JUKEBOX_URL;
    global $NL;

    $cont = "";
    $first = true;

    $xml = simplexml_load_file($DIDLFile);


    foreach ($xml->children('urn:linn-co-uk/playlist') as $track) {
	//$cont .= $track->getName() . "<br />";
	$URL = "";
	$DURATION = "";
	$TITLE = "";
	$YEAR = -1;
	$AlbumArt = "";
	$ArtWork = "";
	$Genre = "";
	$Artist_Performer = "";
	$Artist_Composer = "";
	$Artist_AlbumArtist = "";
	$Artist_Conductor = "";
	$ALBUM = "";
	$TRACK_NUMBER = -1;
	$DISC_NUMBER = -1;
	$DISC_COUNT = -1;
	foreach ($track->children('urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/') as $didl) {
	    //$cont .= $didl->getName() . "<br />";
	    foreach ($didl->children() as $item) {
		//$cont .= $item->getName() ."<br />";
		foreach ($item->children() as $t) {
		    //$cont .= $t->getName() . " ";
		    if ($t->getName() == "res") {
			//$cont .= "URL = " . $t . "<br />";
			foreach($t->attributes() as $a => $b) {
			    if ($a == "duration") {
				$DURATION = $b;
				//$cont .= "DURATION = " . $b . "<br />";
			    }
			}
		    }
		}
		foreach ($item->children('http://purl.org/dc/elements/1.1/') as $t) {
		    //$cont .= $t->getName() . " ";
		    if ($t->getName() == "title") {
			$TITLE = $t;
			//$cont .= "TITLE = " . $t ."<br />";
		    }
		    else if ($t->getName() == "date") {
			$YEAR = (int) $t;
			//$cont .= "YEAR = " . $t ."<br />";
		    }
		}
		foreach ($item->children('urn:schemas-upnp-org:metadata-1-0/upnp/') as $t) {
		    //$cont .= $t->getName() . " ";
		    if ($t->getName() == "albumArtURI") {
			$AlbumArt = $t;
			//$cont .= "AlbumArt = " . $t ."<br />";
		    }
		    else if ($t->getName() == "artworkURI") {
			$ArtWork = $t;
			//$cont .= "Artwork = " . $t ."<br />";
		    }
		    else if ($t->getName() == "genre") {
			$Genre = $t;
			//$cont .= "Genre = " . $t ."<br />";
		    }
		    else if ($t->getName() == "album") {
			$i1 = strpos($t, "/");
			if ($i1 === false) {
			    $ALBUM = $t;
			}
			else
			{
			    $ALBUM = substr($t, $i1+1);
			}
			//$cont .= "ALBUM = " . $t ."<br />";
		    }
		    else if ($t->getName() == "originalTrackNumber") {
			$TRACK_NUMBER = (int) $t;
			//$cont .= "TRACK NUMBER = " . $t ."<br />";
		    }
		    else if ($t->getName() == "originalDiscNumber") {
			$DISC_NUMBER = (int) $t;
			//$cont .= "DISC NUMBER = " . $t ."<br />";
		    }
		    else if ($t->getName() == "originalDiscCount") {
			$DISC_COUNT = (int) $t;
			//$cont .= "DISC COUNT = " . $t ."<br />";
		    }
		    else if ($t->getName() == "artist") {
			//$cont .= "ARTIST = " . $t ."<br />";
			foreach($t->attributes() as $a => $b) {
			    //$cont .=  $b . " = " . $t . "<br />";
			    if ($a == "role" && $b == "Performer") {
				$Artist_Performer = $t;
			    }
			    else if ($a == "role" && $b == "Composer") {
				$Artist_Composer = $t;
			    }
			    else if ($a == "role" && $b == "AlbumArtist") {
				$Artist_AlbumArtist = $t;
			    }
			    else if ($a == "role" && $b == "Conductor") {
				$Artist_Conductor = $t;
			    }
			}
		    }
		}
	    }
	}
	// Skriv noget ud...
	/*
	$cont .= $URL . "<br />";
	$cont .= $DURATION . "<br />";
	$cont .= $TITLE . "<br />";
	$cont .= $YEAR . "<br />";
	$cont .= $AlbumArt . "<br />";
	$cont .= $ArtWork . "<br />";
	$cont .= $Genre . "<br />";
	$cont .= $Artist_Performer . "<br />";
	$cont .= $Artist_Composer . "<br />";
	$cont .= $Artist_AlbumArtist . "<br />";
	$cont .= $Artist_Conductor . "<br />";
	$cont .= $ALBUM . "<br />";
	$cont .= $TRACK_NUMBER . "<br />";
	$cont .= $DISC_NUMBER . "<br />";
	$cont .= $DISC_COUNT . "<br />";
	$cont .= "<br />";
	*/

	if ($first) {
	    //$cont .= '<img class="album" width="250" src="' . str_replace("LINN_JUKEBOX_URL", $LINN_JUKEBOX_URL, $AlbumArt) . '" />' . $NL;
	    $cont .= '<img class="album" width="250" src="' . $FolderImg . '" />' . $NL;
	    //$cont .= '<h3 class="album">0' . $preset . '<br />' . $Artist_Performer . '</h3>' . $NL;
	    $cont .= '<h3>' . $Artist_Performer . '</h3>' . $NL;
	    $cont .= '<p>' . $ALBUM . ' ('. $YEAR . ')</p>' . $NL;
	    $cont .= '<ul id="' . $id . '" data-role="listview" data-inset="true" data-filter="false">' .$NL;
	    $first = false;
	}

	$cont .= '<li>';

	$cont .= '<a href="#"><h3>' . $TRACK_NUMBER . '. ' . $TITLE . '</h3>';
	$cont .= '<p>' . $DURATION . '</p></a>';

	$cont .= "</li>" . $NL;
    }

    $cont .= "</ul>";

    return $cont;
}
?>
