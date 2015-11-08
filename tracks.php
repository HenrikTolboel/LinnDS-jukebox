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
require_once("MusicDB.php");

function Tracks(&$musicDB, $DIDLFile, $preset)
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
	$BITRATE = -1;
	$SAMPLE_FREQUENCY = -1;
	$BITS_PER_SAMPLE = -1;
	$SIZE = -1;
	foreach ($track->children('urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/') as $didl) {
	    foreach ($didl->children() as $item) {
		foreach ($item->children() as $t) {
		    if ($t->getName() == "res") {
			foreach($t->attributes() as $a => $b) {
			    if ($a == "duration") {
				$DURATION = $b;
			    }
			    elseif ($a == "bitrate") {
				$BITRATE = $b;
			    }
			    elseif ($a == "sampleFrequency") {
				$SAMPLE_FREQUENCY = $b;
			    }
			    elseif ($a == "bitsPerSample") {
				$BITS_PER_SAMPLE = $b;
			    }
			    elseif ($a == "size") {
				$SIZE = $b;
			    }
			}
			$URL = $t;
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

	// Sequence number inside playlist file
	$TrackSeq++;

        $musicDB->InsertTracks($preset, $TrackSeq, $URL, $DURATION, $TITLE, $YEAR, 
	    $AlbumArt, $ArtWork, $Genre, $Artist_Performer, $Artist_Composer, 
	    $Artist_AlbumArtist, $Artist_Conductor, $ALBUM, $TRACK_NUMBER, 
	    $DISC_NUMBER, $DISC_COUNT, $BITRATE, $SAMPLE_FREQUENCY, $BITS_PER_SAMPLE, $SIZE);
    }
}
?>
