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

function Tracks(&$DB, $DIDLFile, $preset)
{
    global $NL;

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
			    return AbsolutePath(ProtectPath($R[0][URI]));

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

	// Sequence number inside playlist file
	$TrackSeq++;

	$DB[INSERT_TRACKS_STMT]->bindParam(':Preset', $preset);
	$DB[INSERT_TRACKS_STMT]->bindParam(':TrackSeq', $TrackSeq);
	$DB[INSERT_TRACKS_STMT]->bindParam(':URL', $URL);
	$DB[INSERT_TRACKS_STMT]->bindParam(':Duration', $DURATION);
	$DB[INSERT_TRACKS_STMT]->bindParam(':Title', $TITLE);
	$DB[INSERT_TRACKS_STMT]->bindParam(':Year', $YEAR);
	$DB[INSERT_TRACKS_STMT]->bindParam(':AlbumArt', $AlbumArt);
	$DB[INSERT_TRACKS_STMT]->bindParam(':ArtWork', $ArtWork);
	$DB[INSERT_TRACKS_STMT]->bindParam(':Genre', $Genre);
	$DB[INSERT_TRACKS_STMT]->bindParam(':ArtistPerformer', $Artist_Performer);
	$DB[INSERT_TRACKS_STMT]->bindParam(':ArtistComposer', $Artist_Composer);
	$DB[INSERT_TRACKS_STMT]->bindParam(':ArtistAlbumArtist', $Artist_AlbumArtist);
	$DB[INSERT_TRACKS_STMT]->bindParam(':ArtistConductor', $Artist_Conductor);
	$DB[INSERT_TRACKS_STMT]->bindParam(':Album', $ALBUM);
	$DB[INSERT_TRACKS_STMT]->bindParam(':TrackNumber', $TRACK_NUMBER);
	$DB[INSERT_TRACKS_STMT]->bindParam(':DiscNumber', $DISC_NUMBER);
	$DB[INSERT_TRACKS_STMT]->bindParam(':DiscCount', $DISC_COUNT);


	$result = $DB[INSERT_TRACKS_STMT]->execute();

	$DB[INSERT_TRACKS_STMT]->reset();
    }
}
?>
