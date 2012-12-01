<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2012 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");

function DIDL_Song($TrackURI, $AlbumArtURI, $Artist, $Album, $Title, $Date, $Genre, $TrackNo, $Duration, $DiscNo, $DiscCount)
{
    $ext = pathinfo($TrackURI, PATHINFO_EXTENSION);

    $TRACK_URI = $TrackURI;
    $TRACK_DURATION = $Duration;
    $TRACK_PROTOCOLINFO = "http-get:*:taglib/" . $ext . ":*";
    $ALBUMART_URI = $AlbumArtURI;
    $ARTWORK_URI = $AlbumArtURI;
    $TITLE = $Title;
    $ARTIST_PERFORMER =	$Artist;
    $GENRE = $Genre;
    $ARTIST_PERFORMER = $Artist;
    $ARTIST_COMPOSER = "Unknown";
    $ARTIST_ALBUMARTIST = "Unknown";
    $ARTIST_CONDUCTOR = "Unknown";
    $ALBUM = $Date ."/" . $Album;
    $TRACKNO = $TrackNo;
    $DATE = $Date;
    $DISC_NO = $DiscNo;
    $DISC_COUNT = $DiscCount;
    $PARENTID = $Artist . "+" . $Album . "+" . $Date;
    $ID = $PARENTID . "/" .$TrackNo;

    $DIDL = <<<EOT
    <DIDL-Lite xmlns="urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/">
      <item id="$ID" parentID="$PARENTID" restricted="False">
        <dc:title xmlns:dc="http://purl.org/dc/elements/1.1/">$TITLE</dc:title>
        <upnp:class xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">object.item.audioItem.musicTrack</upnp:class>
        <res duration="$TRACK_DURATION" protocolInfo="$TRACK_PROTOCOLINFO">$TRACK_URI</res>
        <upnp:albumArtURI xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$ALBUMART_URI</upnp:albumArtURI>
        <upnp:artworkURI xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$ARTWORK_URI</upnp:artworkURI>
        <upnp:genre xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$GENRE</upnp:genre>
        <upnp:artist role="Performer" xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$ARTIST_PERFORMER</upnp:artist>
        <upnp:artist role="Composer" xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$ARTIST_COMPOSER</upnp:artist>
        <upnp:artist role="AlbumArtist" xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$ARTIST_ALBUMARTIST</upnp:artist>
        <upnp:artist role="Conductor" xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$ARTIST_CONDUCTOR</upnp:artist>
        <upnp:album xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$ALBUM</upnp:album>
        <upnp:originalTrackNumber xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$TRACKNO</upnp:originalTrackNumber>
        <dc:date xmlns:dc="http://purl.org/dc/elements/1.1/">$DATE</dc:date>
        <upnp:originalDiscNumber xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$DISC_NO</upnp:originalDiscNumber>
        <upnp:originalDiscCount xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/">$DISC_COUNT</upnp:originalDiscCount>
      </item>
    </DIDL-Lite>
EOT;

    return $DIDL;
}

function Linn_Track($Str)
{
    global $NL;

    $LinnTrack = <<<EOT
  <linn:Track>
EOT;

    $LinnTrack .= $NL . $Str . $NL;

    $LinnTrack .= <<<EOT
  </linn:Track>
EOT;

    return $LinnTrack;
}

function Linn_Playlist($Str)
{
    global $NL;

    $LinnPlaylist = <<<EOT
<linn:Playlist version="3" xmlns:linn="urn:linn-co-uk/playlist">
EOT;

    $LinnPlaylist .= $NL . $Str . $NL;

    $LinnPlaylist .= <<<EOT
</linn:Playlist>
EOT;

    return $LinnPlaylist;
}

function DIDL_Example()
{
    global $NL;

    $Res = "";
    $Res .= Linn_Track(
	DIDL_Song("http://192.168.0.105/musik/EAC/Al%20Di%20Meola/Electric%20Rendezvous/Al%20Di%20Meola+Electric%20Rendezvous+01%20God%20Bird%20Change.flac",
		  "http://192.168.0.105/musik/EAC/Al%20Di%20Meola/Electric%20Rendezvous/folder.jpg",
		  "Al Di Meola",
		  "Electric Rendezvous",
		  "God Bird Change",
		  1982,
		  "Fusion",
		  1, "00:00:00", 1, 1));
    $Res .= $NL;
    $Res .= Linn_Track(
	DIDL_Song("http://192.168.0.105/musik/EAC/Al%20Di%20Meola/Electric%20Rendezvous/Al%20Di%20Meola+Electric%20Rendezvous+01%20God%20Bird%20Change.flac",
		  "http://192.168.0.105/musik/EAC/Al%20Di%20Meola/Electric%20Rendezvous/folder.jpg",
		  "Al Di Meola",
		  "Electric Rendezvous",
		  "God Bird Change",
		  1982,
		  "Fusion",
		  2, "00:00:00", 1, 1));

    print Linn_Playlist($Res) . $NL;
}

//DIDL_Example();

?>
