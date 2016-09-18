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
require_once("DIDL.php");
require_once("MusicTags.php");

class Playlist
{
    function __construct()
    {
    }

    public function TrackNoOrder($a, $b)
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

    private function PlayListFromDir($Dir, &$Key, &$Playlist, &$Info)
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
	    $Arr->uasort(array('Playlist', 'TrackNoOrder'));

	$it = $Arr->getIterator();
	while($it->valid()) 
	{
	    if (0 && isset($Artist) && $Artist != $it->current()->Artist())
	    {
		$Artist = "Various Artists";
		$ARTIST = $Artist;
	    }
	    else
	    {
		$Artist = str_replace("/", ",", $it->current()->Artist());
		$ARTIST = $it->current()->Artist();
	    }
	    $AlbumArtist = str_replace("/", ",", $it->current()->AlbumArtist());
	    $ALBUMARTIST = $it->current()->AlbumArtist();
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
	    $PLAYLIST = RelativeBuildPath($Dir . $DIR_DELIM . "playlist.dpl");
	    $PLAYLIST = str_replace("&", "&amp;", $PLAYLIST);


	    $img = $Dir . $DIR_DELIM . "folder.png";
	    if (!file_exists($img))
		$img = $Dir . $DIR_DELIM . "folder.jpg";

	    $ART = RelativeBuildPath($img);

	    $img80 = dirname($img) . $DIR_DELIM . "80x80.jpg";
	    $img160 = dirname($img) . $DIR_DELIM . "160x160.jpg";

	    if (file_exists($img))
	    {
		if (!file_exists($img80))
		{
		    $cmd  = 'convert  "' . $img . '" -thumbnail 80x80 +profile "*" "' . $img80 . '"';
		    echo $NL . $cmd . $NL;
		    shell_exec($cmd);
		}
		if (!file_exists($img160))
		{
		    $cmd  = 'convert  "' . $img . '" -thumbnail 160x160 +profile "*" "' . $img160 . '"';
		    echo $NL . $cmd . $NL;
		    shell_exec($cmd);
		}
	    }
	    $ART80 = RelativeBuildPath($img80);
	    $ART160 = RelativeBuildPath($img160);
	    
	    $ART = str_replace("&", "&amp;", $ART);
	    $ART80 = str_replace("&", "&amp;", $ART80);
	    $ART160 = str_replace("&", "&amp;", $ART160);
	    $ALBUM = str_replace("&", "&amp;", $ALBUM);
	    $GENRE = str_replace("&", "&amp;", $GENRE);
	    $ARTIST = str_replace("&", "&amp;", $ARTIST);
	    $ALBUMARTIST = str_replace("&", "&amp;", $ALBUMARTIST);

	    $ART = str_replace('"', "&quot;", $ART);
	    $ART80 = str_replace('"', "&quot;", $ART80);
	    $ART160 = str_replace('"', "&quot;", $ART160);
	    $ALBUM = str_replace('"', "&quot;", $ALBUM);
	    $GENRE = str_replace('"', "&quot;", $GENRE);
	    $ARTIST = str_replace('"', "&quot;", $ARTIST);
	    $Artist = str_replace('"', "&quot;", $Artist);
	    $ALBUMARTIST = str_replace('"', "&quot;", $ALBUMARTIST);
	    $AlbumArtist = str_replace('"', "&quot;", $AlbumArtist);

	    $Key = $AlbumArtist . "+" . $Album . "+" . $Date . "+" . $Genre . "+" . $MaxMTimeMusic;
	    //$KEY = $ALBUMARTIST . "+" . $ALBUM . "+" . $DATE . "+" . $GENRE . "+" . $MaxMTimeMusic;

	    $Info = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<Info>
  <Artist>$ARTIST</Artist>
  <AlbumArtist>$ALBUMARTIST</AlbumArtist>
  <Album>$ALBUM</Album>
  <Date>$DATE</Date>
  <Genre>$GENRE</Genre>
  <MusicTime>$MaxMTimeMusic</MusicTime>
  <Playlist>$PLAYLIST</Playlist>
  <NoTracks>$cnt</NoTracks>
  <Art>$ART</Art>
  <Art80>$ART80</Art80>
  <Art160>$ART160</Art160>
</Info>

EOT;
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

    public function PlaylistDir($Dir)
    {
	global $NL;
	global $DIR_DELIM;

	$Res = false;

	if ($this->PlaylistFromDir($Dir, $Key, $Playlist, $Info))
	{
	    print "PlaylistDir: $Dir -> Key: $Key" . $NL;
	    //print "Playlist: $Playlist" . $NL;

	    //file_put_contents($Dir . $DIR_DELIM . $Key .".dpl", $Playlist);
	    file_put_contents($Dir . $DIR_DELIM . "playlist.dpl", $Playlist);
	    file_put_contents($Dir . $DIR_DELIM . "info.xml", $Info);

	    $Res = true;
	}

	return $Res;
    }

    public function PlaylistHere()
    {
	global $NL;
	global $DIR_DELIM;

	$this->PlaylistDir('.');
    }
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


    $DirPlaylist = new Playlist();

    try
    {
	foreach ($TopDirectory as $Dir => $RootMenuNo)
	{
	    $it = new RecursiveIteratorIterator(new RecursiveDirectoryReader($Dir), RecursiveIteratorIterator::SELF_FIRST);

	    while($it->valid())
	    {
		//echo $it->getPathName().$NL;
		if ($DirPlaylist->PlaylistDir($it->getPathName()))
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

//$DirPlaylist = new Playlist();
//$DirPlaylist->PlaylistHere();

?>
