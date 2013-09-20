<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2012-2013 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");
require_once("MusicTags.php");

function TrackNoOrder($a, $b)
{
    global $NL;

    if ($a->TrackNo() < $b->TrackNo())
	$res = -1;
    elseif ($a->TrackNo() > $b->TrackNo())
	$res = 1;
    else
	$res = 0;

    //print "TrackNoOrder: a->TrackNo=" . $a->TrackNo() . ", b->TrackNo=" . $b->TrackNo() . " => " . $res . $NL;
    return $res;
}

function PlayListFromDir($Dir, &$Key, &$Playlist, &$Info)
{
    global $NL;
    global $DIR_DELIM;

    $Arr = new ArrayObject();
    try
    {
	$cnt = 0;
	$MTimeDPL = -1;
	$MaxMTimeMusic = -1;
        $it = new directoryIterator($Dir);
        while( $it->valid())
        {
            if( $it->isFile() )
            {
		$ext = pathinfo($it->current(), PATHINFO_EXTENSION);

		if ($ext == "dpl")
		{
		    $MTimeDPL = $it->getMTime();
		}
		elseif ($ext == "flac" || $ext == "mp3" || $ext == "wma" || $ext == "m4a")
		{
		    if ($MaxMTimeMusic == -1 || $MaxMTimeMusic < $it->getMTime())
			$MaxMTimeMusic = $it->getMTime();
		    $cnt++;
		}
            }
            $it->next();
        }

	if ($cnt == 0 || ($MTimeDPL > 0 && $MTimeDPL > $MaxMTimeMusic))
	    return false;

	$it->rewind();

        while( $it->valid())
        {
            if( $it->isFile() )
            {

		$ext = pathinfo($it->current(), PATHINFO_EXTENSION);

		if ($ext == "dpl")
		    unlink($it->getPathName());
		elseif ($ext == "flac" || $ext == "mp3" || $ext == "wma" || $ext == "m4a")
		{
		    //echo $it->getPathName() . $NL;
		    $Arr->append(new MusicTags($it->getPathName()));
		}
            }
            $it->next();
        }
    }
    catch(Exception $e)
    {
        echo $e->getMessage();
    }
    
    $Tracks = "";
    $cnt = $Arr->count();

    if ($cnt > 1)
	$Arr->uasort('TrackNoOrder');

    $it = $Arr->getIterator();
    while($it->valid()) 
    {
	if (isset($Artist) && $Artist != $it->current()->Artist())
	{
	    $Artist = "Various Artists";
	    $ARTIST = $Artist;
	}
	else
	{
	    $Artist = str_replace("/", ",", $it->current()->Artist());
	    $ARTIST = $it->current()->Artist();
	}
	$Album = str_replace("/", ",", $it->current()->Album());
	$ALBUM = $it->current()->Album();
	$Date = $it->current()->Date();
	$DATE = $Date;
	$Genre = str_replace("/", ",", $it->current()->Genre());
	$GENRE = $it->current()->Genre();

	$Tracks .= $NL . Linn_Track($it->current()->getDIDL());
	$it->next();
    }

    if ($cnt > 0)
    {
	$PLAYLIST = $Dir . $DIR_DELIM . "playlist.dpl";
	$PLAYLIST = str_replace("&", "&amp;", $PLAYLIST);

	$ART = $Dir . $DIR_DELIM . "folder.jpg";
	$ART = str_replace("&", "&amp;", $ART);
	
	$ALBUM = str_replace("&", "&amp;", $ALBUM);
	$GENRE = str_replace("&", "&amp;", $GENRE);
	$ARTIST = str_replace("&", "&amp;", $ARTIST);

	$Info = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<Info>
  <Artist>$ARTIST</Artist>
  <Album>$ALBUM</Album>
  <Date>$DATE</Date>
  <Genre>$GENRE</Genre>
  <MusicTime>$MaxMTimeMusic</MusicTime>
  <Playlist>$PLAYLIST</Playlist>
  <NoTracks>$cnt</NoTracks>
  <Art>$ART</Art>
</Info>

EOT;
	$Key = $Artist . "+" . $Album . "+" . $Date . "+" . $Genre;
	$Playlist = Linn_Playlist($Tracks);
    }
    else
    {
	$Key = "";
	$Info = "";
	$Playlist = "";
    }

    return $cnt > 0;
}

//print PlaylistFromDir('/Users/henrik/Documents/WebProjekter/LinnDS-jukebox', $Key, $Playlist) . $NL;
//print "Key: $Key" . $NL;
//print "Playlist: $Playlist" . $NL;

function PlaylistDir($Dir)
{
    global $NL;
    global $DIR_DELIM;

    $Res = false;

    if (PlaylistFromDir($Dir, $Key, $Playlist, $Info))
    {
	print "Dir: $Dir -> Key: $Key" . $NL;
	//print "Playlist: $Playlist" . $NL;

	//file_put_contents($Dir . $DIR_DELIM . $Key .".dpl", $Playlist);
	file_put_contents($Dir . $DIR_DELIM . "playlist.dpl", $Playlist);
	file_put_contents($Dir . $DIR_DELIM . "info.xml", $Info);

	$Res = true;
    }

    return $Res;
}

function PlaylistHere()
{
    global $NL;
    global $DIR_DELIM;

    PlaylistDir('.');
}

class RecursiveDirectoryReader extends RecursiveDirectoryIterator
{
    function __construct($path)
    {
	parent::__construct($path);
    }

    /*** members are only valid if they are a directory ***/
    function valid()
    {
	if(parent::valid())
	{
	    if (!parent::isDir() || parent::isDot())
	    {
		parent::next();
		return $this->valid();
	    }
	return TRUE;
	}
	return FALSE;
    }
}

function MakePlaylists($TopDirectory)
{
    global $NL;

    $Cnt = 0;

    try
    {
	foreach ($TopDirectory as $Dir => $RootMenuNo)
	{
	    $it = new RecursiveIteratorIterator(new RecursiveDirectoryReader($Dir), RecursiveIteratorIterator::SELF_FIRST);

	    while($it->valid())
	    {
		//echo $it->getPathName().$NL;
		if (PlaylistDir($it->getPathName()))
		    $Cnt++;

		$it->next();
	    }
	}
    }
    catch(Exception $e)
    {
	echo 'No files Found!' . $NL;
    }

    return $Cnt;
}

//MakePlaylists($TopDirectory);
//PlaylistHere();
?>
