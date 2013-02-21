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

require_once 'lib/getid3/getid3/getid3.php';

class MusicTags {

    private $Arr = array();

    public function __construct($FileName)
    {
	$getID3 = new getID3();
     
	$FileInfo = $getID3->analyze($FileName);
     
	//print_r($FileInfo);

	$this->Arr[FileFormat]   = $FileInfo[fileformat];
	$this->Arr[FileNamePath] = $FileInfo[filenamepath];
	$this->Arr[SampleRate]   = $FileInfo[audio][sample_rate];
	$this->Arr[Duration]     = $FileInfo[playtime_string];
	$this->Arr[Seconds]      = $FileInfo[playtime_seconds];
	$this->Arr[DiscNo]       = 1;
	$this->Arr[DiscCount]    = 1;



	if ($this->Arr[FileFormat] == "flac")
	{
	    $this->Arr[Title]   = $FileInfo[tags][vorbiscomment][title][0];
	    $this->Arr[Artist]  = $FileInfo[tags][vorbiscomment][artist][0];
	    $this->Arr[Album]   = $FileInfo[tags][vorbiscomment][album][0];
	    $this->Arr[TrackNo] = $FileInfo[tags][vorbiscomment][tracknumber][0];
	    $this->Arr[Genre]   = $FileInfo[tags][vorbiscomment][genre][0];
	    $this->Arr[Date]    = $FileInfo[tags][vorbiscomment][date][0];
	    //$this->Arr[DiscNo]   = $FileInfo[tags][vorbiscomment][discnumber][0];
	}
	elseif ($this->Arr[FileFormat] == "mp3")
	{
	    $this->Arr[Title]   = $FileInfo[tags][id3v2][title][0];
	    $this->Arr[Artist]  = $FileInfo[tags][id3v2][artist][0];
	    $this->Arr[Album]   = $FileInfo[tags][id3v2][album][0];
	    $this->Arr[TrackNo] = $FileInfo[tags][id3v2][track_number][0];
	    $this->Arr[Genre]   = $FileInfo[tags][id3v2][genre][0];
	    $this->Arr[Date]    = $FileInfo[tags][id3v2][year][0];
	}
	elseif ($this->Arr[FileFormat] == "asf")
	{
	    $this->Arr[Title]   = $FileInfo[tags][asf][title][0];
	    $this->Arr[Artist]  = $FileInfo[tags][asf][artist][0];
	    $this->Arr[Album]   = $FileInfo[tags][asf][album][0];
	    $this->Arr[TrackNo] = $FileInfo[tags][asf][track][0];
	    $this->Arr[Genre]   = $FileInfo[tags][asf][genre][0];
	    $this->Arr[Date]    = $FileInfo[tags][asf][year][0];
	    //$this->Arr[DiscNo]   = $FileInfo[tags][vorbiscomment][discnumber][0];
	}
	elseif ($this->Arr[FileFormat] == "quicktime")
	{
	    $this->Arr[Title]   = $FileInfo[tags][quicktime][title][0];
	    $this->Arr[Artist]  = $FileInfo[tags][quicktime][artist][0];
	    $this->Arr[Album]   = $FileInfo[tags][quicktime][album][0];
	    $this->Arr[TrackNo] = $FileInfo[tags][quicktime][track][0];
	    $this->Arr[Genre]   = $FileInfo[tags][quicktime][genre][0];
	    $this->Arr[Date]    = $FileInfo[tags][quicktime][year][0];
	    //$this->Arr[DiscNo]   = $FileInfo[tags][vorbiscomment][discnumber][0];
	}

	$this->Arr[TrackNo] = ltrim($this->Arr[TrackNo], "0");
    }

    public function TrackNo()
    {
	return $this->Arr[TrackNo];
    }

    public function Artist()
    {
	return $this->Arr[Artist];
    }

    public function Album()
    {
	return $this->Arr[Album];
    }

    public function Date()
    {
	return $this->Arr[Date];
    }

    public function Genre()
    {
	return $this->Arr[Genre];
    }

    public function dump()
    {
	print_r($this->Arr);
    }

    public function getDIDL()
    {
	$this->Arr[TrackURI] = ProtectPath(RelativePath($this->Arr[FileNamePath]));
	$this->Arr[AlbumArtURI] =  ProtectPath(RelativePath(pathinfo($this->Arr[FileNamePath], PATHINFO_DIRNAME) . "/folder.jpg"));
	return DIDL_Song($this->Arr[TrackURI], $this->Arr[AlbumArtURI], 
	    $this->Arr[Artist], $this->Arr[Album], $this->Arr[Title], $this->Arr[Date], $this->Arr[Genre], 
	    $this->Arr[TrackNo], $this->Arr[Duration], $this->Arr[DiscNo], $this->Arr[DiscCount]);
    }
}

function Test_MusicTags($FileName)
{
    global $NL;

    echo "Test_MusicTags: " . $FileName .$NL . $NL;
    $t = new MusicTags($FileName);
    $t->dump();
}

//Test_MusicTags("test_music/file.flac");
//Test_MusicTags("test_music/fil.mp3");
//Test_MusicTags("test_music/fil.wma");
//Test_MusicTags("test_music/fil.m4a");
//Test_MusicTags("test_music/accents.flac");

?>
