<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2013 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/


require_once("setup.php");

function Album($id, $DIDLFile, $preset, $FolderImg)
{
    global $LINN_JUKEBOX_URL;
    global $NL;
    global $SQ;

    $cont = "";
    $first = true;
    $TrackSeq = 0;

    //echo "Album: " .  $DIDLFile . $NL;
    $xml = simplexml_load_file($DIDLFile);


    foreach ($xml->children('urn:linn-co-uk/playlist') as $track) {
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
	    foreach ($didl->children() as $item) {
		foreach ($item->children() as $t) {
		    if ($t->getName() == "res") {
			foreach($t->attributes() as $a => $b) {
			    if ($a == "duration") {
				$DURATION = $b;
			    }
			}
		    }
		}
		foreach ($item->children('http://purl.org/dc/elements/1.1/') as $t) {
		    if ($t->getName() == "title") {
			$TITLE = $t;
		    }
		    else if ($t->getName() == "date") {
			$YEAR = (int) $t;
		    }
		}
		foreach ($item->children('urn:schemas-upnp-org:metadata-1-0/upnp/') as $t) {
		    if ($t->getName() == "albumArtURI") {
			$AlbumArt = $t;
		    }
		    else if ($t->getName() == "artworkURI") {
			$ArtWork = $t;
		    }
		    else if ($t->getName() == "genre") {
			$Genre = $t;
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
		    }
		    else if ($t->getName() == "originalTrackNumber") {
			$TRACK_NUMBER = (int) $t;
		    }
		    else if ($t->getName() == "originalDiscNumber") {
			$DISC_NUMBER = (int) $t;
		    }
		    else if ($t->getName() == "originalDiscCount") {
			$DISC_COUNT = (int) $t;
		    }
		    else if ($t->getName() == "artist") {
			foreach($t->attributes() as $a => $b) {
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

	$TrackSeq++;
	if ($first) {
	    $cont .= '<div class="ui-grid-a">' . $NL;
	    $cont .= '<div class="ui-block-a"><div class="ui-bar">' . $NL;
	    $cont .= '<img class="album" style="width: 100%;" src="' . $FolderImg . '" />' . $NL;
	    $cont .= '</div></div>' . $NL;
	    $cont .= '<div class="ui-block-b"><div class="ui-bar">' . $NL;
	    $cont .= '<button href="#" class="panelclick" data-mini="false" data-musik=' . $SQ . '{"action": "PlayNow", "preset": "' . $preset . '"}' . $SQ . '>Play Now</button>' . $NL;
	    $cont .= '<button href="#" class="panelclick" data-mini="false" data-musik=' . $SQ . '{"action": "PlayNext", "preset": "' . $preset . '"}' . $SQ . '>Play Next</button>' . $NL;
	    $cont .= '<button href="#" class="panelclick" data-mini="false" data-musik=' . $SQ . '{"action": "PlayLater", "preset": "' . $preset . '"}' . $SQ . '>Play Later</button>' . $NL;
	    $cont .= '</div></div>' . $NL;
	    $cont .= '</div><!-- /grid-a -->' . $NL;

	    $cont .= '<h3>' . $Artist_Performer . '</h3>' . $NL;
	    $cont .= '<p>' . $ALBUM . ' ('. $YEAR . ')</p>' . $NL;
	    $cont .= '<ul id="' . $id . '" data-role="listview" data-inset="true" data-filter="false">' .$NL;
	    $first = false;
	}

	$cont .= '<li>';

	$cont .= '<a id ="' . $id . '-' . $TrackSeq . '" href="#" class="playpopup" data-rel="popup" data-musik=' . $SQ . '{"popupid": "' . $id . '-popup", "preset": "' . $preset . '", "track": "' . $TrackSeq . '"}' . $SQ . '><h3>' . $TRACK_NUMBER . '. ' . $TITLE . '</h3>';
	$cont .= '<p>' . $DURATION . '</p></a>';

	$cont .= "</li>" . $NL;
    }

    $cont .= "</ul>";
    $cont .= PlayPopup($id . "-popup");

    return $cont;
}
?>
