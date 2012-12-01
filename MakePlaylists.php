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

function PlayListFromDir($Dir, &$Key, &$Playlist)
{
    global $NL;

    $Arr = new ArrayObject();
    try
    {
        $it = new directoryIterator($Dir);
        while( $it->valid())
        {
            if( $it->isFile() )
            {

		$ext = pathinfo($it->current(), PATHINFO_EXTENSION);

		if ($ext == "flac" || $ext == "mp3" || $ext == "wma")
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
	    $Artist = "Various Artists";
	else
	    $Artist = str_replace("/", ",", $it->current()->Artist());
	$Album = str_replace("/", ",", $it->current()->Album());
	$Date = $it->current()->Date();
	$Genre = str_replace("/", ",", $it->current()->Genre());

	$Tracks .= $NL . Linn_Track($it->current()->getDIDL());
	$it->next();
    }

    if ($cnt > 0)
    {
	$Key = $Artist . "+" . $Album . "+" . $Date . "+" . $Genre;
	$Playlist = Linn_Playlist($Tracks);
    }
    else
    {
	$Key = "";
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

    if (PlaylistFromDir($Dir, $Key, $Playlist))
    {
	print "Dir: $Dir -> Key: $Key" . $NL;
	//print "Playlist: $Playlist" . $NL;

	file_put_contents($Dir . $DIR_DELIM . $Key .".dpl", $Playlist);
    }
}

function PlaylistHere()
{
    global $NL;
    global $DIR_DELIM;

    if (PlaylistFromDir('.', $Key, $Playlist))
    {
	print "Key: $Key" . $NL;
	print "Playlist: $Playlist" . $NL;

	file_put_contents($Key .".dpl", $Playlist);
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

    try
    {
	foreach ($TopDirectory as $Dir => $RootMenuNo)
	{
	    $it = new RecursiveIteratorIterator(new RecursiveDirectoryReader($Dir), RecursiveIteratorIterator::SELF_FIRST);

	    while($it->valid())
	    {
		//echo $it->getPathName().$NL;
		PlaylistDir($it->getPathName());

		$it->next();
	    }
	}
    }
    catch(Exception $e)
    {
	echo 'No files Found!' . $NL;
    }
}

//MakePlaylists($TopDirectory);
PlaylistHere();
?>
