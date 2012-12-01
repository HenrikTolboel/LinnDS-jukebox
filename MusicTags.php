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
	    $this->Arr[Title]   = $FileInfo[tags_html][vorbiscomment][title][0];
	    $this->Arr[Artist]  = $FileInfo[tags_html][vorbiscomment][artist][0];
	    $this->Arr[Album]   = $FileInfo[tags_html][vorbiscomment][album][0];
	    $this->Arr[TrackNo] = $FileInfo[tags_html][vorbiscomment][tracknumber][0];
	    $this->Arr[Genre]   = $FileInfo[tags_html][vorbiscomment][genre][0];
	    $this->Arr[Date]    = $FileInfo[tags_html][vorbiscomment][date][0];
	    //$this->Arr[DiscNo]   = $FileInfo[tags_html][vorbiscomment][discnumber][0];
	}
	elseif ($this->Arr[FileFormat] == "mp3")
	{
	    $this->Arr[Title]   = $FileInfo[tags_html][id3v2][title][0];
	    $this->Arr[Artist]  = $FileInfo[tags_html][id3v2][artist][0];
	    $this->Arr[Album]   = $FileInfo[tags_html][id3v2][album][0];
	    $this->Arr[TrackNo] = $FileInfo[tags_html][id3v2][track_number][0];
	    $this->Arr[Genre]   = $FileInfo[tags_html][id3v2][genre][0];
	    $this->Arr[Date]    = $FileInfo[tags_html][id3v2][year][0];
	}
	elseif ($this->Arr[FileFormat] == "asf")
	{
	    $this->Arr[Title]   = $FileInfo[tags_html][asf][title][0];
	    $this->Arr[Artist]  = $FileInfo[tags_html][asf][artist][0];
	    $this->Arr[Album]   = $FileInfo[tags_html][asf][album][0];
	    $this->Arr[TrackNo] = $FileInfo[tags_html][asf][track][0];
	    $this->Arr[Genre]   = $FileInfo[tags_html][asf][genre][0];
	    $this->Arr[Date]    = $FileInfo[tags_html][asf][year][0];
	    //$this->Arr[DiscNo]   = $FileInfo[tags_html][vorbiscomment][discnumber][0];
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

    private function PrepareURI($Path)
    {
	$Path = str_replace("/Users/henrik/Documents", "LINN_JUKEBOX_URL", $Path);
	$Path = str_replace("/Users/henrik/Music/MusicLib", "LINN_JUKEBOX_URL", $Path);
	$encoded = implode("/", array_map("rawurlencode", explode("/", $Path)));
	return $encoded;
    }

    public function getDIDL()
    {
	$this->Arr[TrackURI] = $this->PrepareURI($this->Arr[FileNamePath]);
	$this->Arr[AlbumArtURI] =  $this->PrepareURI(pathinfo($this->Arr[FileNamePath], PATHINFO_DIRNAME) . "/folder.jpg");
	return DIDL_Song($this->Arr[TrackURI], $this->Arr[AlbumArtURI], 
	    $this->Arr[Artist], $this->Arr[Album], $this->Arr[Title], $this->Arr[Date], $this->Arr[Genre], 
	    $this->Arr[TrackNo], $this->Arr[Duration], $this->Arr[DiscNo], $this->Arr[DiscCount]);
    }

}

function Test_MusicTags($FileName)
{
    $t = new MusicTags($FileName);
    $t->dump();
}

//Test_MusicTags("test_music/file.flac");
//Test_MusicTags("test_music/fil.mp3");
//Test_MusicTags("test_music/fil.wma");

?>
