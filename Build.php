#!/usr/bin/php
<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2012-2016 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");
require_once("MakePlaylists.php");
require_once("tracks.php");
require_once("FileUtils.php");
require_once("MusicDB.php");



// ########## CLASS: DIDL_Album  ##########################################################

class DIDL_Album 
{
    private static $NoInstances = 0;
    private $Value = array();

    public function __construct($FileName, $RootMenuNo = -1)
    {
	self::$NoInstances++;
	$this->Value[InstanceNo] = self::$NoInstances;
	$this->Value[File] = new SplFileInfo($FileName);

	$xml = simplexml_load_file(ProtectPath($this->Value[File]->getPathname()));

	foreach ($xml->children() as $info) {
	    $this->Value[$info->getName()]  = (string) $info;
	}
	$this->Value[Path] = explode("/", RelativePath($this->Value[Playlist]));
	
	$this->Value[RootMenuNo] = $RootMenuNo;
	//print_r($this->Value);
    }

    public function PlaylistFileName()
    {
	return $this->Value[Playlist];
    }

    public function ImageFileName()
    {
	return $this->Value[Art];
    }

    public function setSequenceNo($No)
    {
	$this->Value[SequenceNo] = $No;
    }

    public function SequenceNo()
    {
	return $this->Value[SequenceNo];
    }

    public function TopDirectory()
    {
	return $this->Value[Path][1];
    }

    public function RootMenuNo()
    {
	global $NL;
	global $TopDirectory;

	if ($this->Value[RootMenuNo] != -1)
	    return $this->Value[RootMenuNo];

	foreach ($TopDirectory as $Dir => $RootMenuNo)
	{
	    $RelDir = str_replace("LINN_JUKEBOX_URL/", "", RelativePath($Dir));
	    if ($RelDir == $this->TopDirectory())
		return $RootMenuNo;
	}
	return 0;
    }

    public function dump()
    {
	print_r($this->Value);
    }

    public function URI()
    {
	return RelativePath($this->PlaylistFileName());
    }

    public function ImageURI()
    {
	return RelativePath($this->ImageFileName());
    }

    public function Artist()
    {
	return $this->Value[AlbumArtist];
    }
    public function ArtistFirst()
    {
	$F = strtoupper(substr($this->SortSkipWords($this->Artist()), 0, 1));

	if ($F >= "A" && $F <= "Z")
	    return $F;
	else
	    return "#";
    }
    public function SortArtist()
    {
	$SA = $this->SortSkipWords($this->Artist());

	return $SA;
    }
    public function Album()
    {
	return $this->Value[Album];
    }
    public function Date()
    {
	return $this->Value[Date];
    }
    public function Genre()
    {
	return $this->Value[Genre];
    }

    public function MusicTime()
    {
	return (int)$this->Value[MusicTime];
    }

    public function NoTracks()
    {
	return (int)$this->Value[NoTracks];
    }

    public function Key()
    {
	$Key =  $this->Artist() . "+" . $this->Album() . "+" . $this->Date() . "+" . $this->Genre() . "+" . $this->Value[MusicTime];
	return $Key;
    }

    private function SortSkipWords($Str)
    {
	global $SortSkipList;

	foreach ($SortSkipList as $w) 
	{
	    if (!strncmp($w, $Str, strlen($w))) 
	    {
		return substr($Str, strlen($w));
	    }
	}
	return $Str;
    }

    public function compare($a, $b) {
	$cmp = strcmp($a->SortSkipWords($a->Artist()), $b->SortSkipWords($b->Artist()));
	if ($cmp != 0)
	    return $cmp;

	$cmp = strcmp($a->Date(), $b->Date());

	if ($cmp != 0)
	    return $cmp;

	$cmp = strcmp($a->SortSkipWords($a->Album()), $b->SortSkipWords($b->Album()));

	return $cmp;
    }

    public function newest($a, $b) {
	$aMT = $a->MusicTime();
	$bMT = $b->MusicTime();

	if ($aMT < $bMT)
	    return 1;

	if ($aMT > $bMT)
	    return -1;

	return 0;
    }
}


function Make_Tracks(&$didl, &$musicDB)
{
    return Tracks($musicDB, AbsoluteBuildPath($didl->PlaylistFileName()), $didl->Key(), $didl->SequenceNo());
}


function Make_Album(&$didl, &$musicDB)
{
    $rowid = $musicDB->InsertAlbum($didl->Key(), $didl->SequenceNo(), $didl->NoTracks(), $didl->URI(), 
		    $didl->ArtistFirst(), $didl->Artist(), $didl->SortArtist(), 
		    $didl->Album(), $didl->Date(), $didl->Genre(), $didl->MusicTime(), 
		    $didl->ImageURI(), $didl->TopDirectory(), $didl->RootMenuNo());

    $didl->SetSequenceNo($rowid);
    return $rowid;
}

function OldCollectFolderImgs(&$didl)
{
    global $AppDir;

    $img = AbsoluteBuildPath($didl->ImageFileName());
    
    if (strlen($img) <= 4 || !file_exists($img))
    {
	$img = "grey.jpg";
    }
    $newfile = sprintf($AppDir . "folder/folder_%04d.jpg", $didl->SequenceNo());
    copy($img, $newfile);
    $newfile = sprintf($AppDir . "folder/80x80_%04d.jpg", $didl->SequenceNo());
    if (strpos($img, 'folder.jpg') !== FALSE)
	copy(str_replace('folder.jpg', '80x80.jpg', $img), $newfile);
    else
	copy($img, $newfile);
    
    $newfile = sprintf($AppDir . "folder/160x160_%04d.jpg", $didl->SequenceNo());
    if (strpos($img, 'folder.jpg') !== FALSE)
	copy(str_replace('folder.jpg', '160x160.jpg', $img), $newfile);
    else
	copy($img, $newfile);
}

function CreateAllGreyImgs($MaxPreset)
{
    global $AppDir;

    for ($i = 1; $i <= $MaxPreset; $i++)
    {
	$newfile = sprintf($AppDir . "folder/80x80_%04d.jpg", $i);
	copy("grey.jpg", $newfile);

	$newfile = sprintf($AppDir . "folder/160x160_%04d.jpg", $i);
	copy("grey.jpg", $newfile);
    }
}

function CollectFolderImgs(&$didl)
{
    global $NL;
    global $AppDir;

    $img = AbsoluteBuildPath($didl->ImageFileName());
    
    if (strlen($img) <= 4 || !file_exists($img))
    {
	$newfile = sprintf($AppDir . "folder/80x80_%04d.jpg", $didl->SequenceNo());
	copy("grey.jpg", $newfile);

	$newfile = sprintf($AppDir . "folder/160x160_%04d.jpg", $didl->SequenceNo());
	copy("grey.jpg", $newfile);
	return;
    }
    if (!file_exists(dirname($img) . "/80x80.jpg"))
    {
        $cmd  = 'convert  "' . $img . '" -thumbnail 80x80 +profile "*" "' . dirname($img) . '/80x80.jpg"';
	echo $NL . $cmd . $NL;
	shell_exec($cmd);
    }
    if (!file_exists(dirname($img) . "/160x160.jpg"))
    {
        $cmd  = 'convert  "' . $img . '" -thumbnail 160x160 +profile "*" "' . dirname($img) . '/160x160.jpg"';
	echo $NL . $cmd . $NL;
	shell_exec($cmd);
    }
    $newfile = sprintf($AppDir . "folder/80x80_%04d.jpg", $didl->SequenceNo());
    copy(dirname($img) . "/80x80.jpg", $newfile);
    $newfile = sprintf($AppDir . "folder/160x160_%04d.jpg", $didl->SequenceNo());
    copy(dirname($img) . "/160x160.jpg", $newfile);
}

function Make_CSS($MaxPreset, $CSS1, $CSS2)
{
    global $NL;
    global $AppDir;
    global $NEWEST_COUNT;

    $ImgSize = 80;
    $TileW = 10;
    $TileH = 10;

    $SpriteW = $ImgSize * $TileW + 2 * $TileW;
    $SpriteH = $ImgSize * $TileH + 2 * $TileH;

    // On an ipad somehow the size of a sprite image should be < 1024 pixels 
    // wide / high - otherwise the display of sprite elements are distorted.

    $cmd1 = "montage -background transparent -tile " . $TileW . "x" . $TileH . " -geometry 80x80+1+1 " . $AppDir . "folder/80x80_* " . $AppDir . "sprites/sprite.jpg";
    echo $cmd1 . $NL;
    $cmd2 = "montage -background transparent -tile " . $TileW . "x" . $TileH . " -geometry 160x160+1+1 " . $AppDir . "folder/160x160_* " . $AppDir . "sprites/sprite@2x.jpg";

    shell_exec($cmd1);
    echo $cmd2 . $NL;
    shell_exec($cmd2);

    $css = "";
    $cnt = 0;
    for ($k = 0; $cnt < $MaxPreset; $k++)
    {
	for ($i = 0; $i < $TileW && $cnt < $MaxPreset; $i++)
	{
	    for ($j = 0; $j < $TileH && $cnt < $MaxPreset; $j++)
	    {
		$cnt++;
		$posx = -1 * ($i * $ImgSize + $i*2 +1);
		$posy = -1 * ($j * $ImgSize + $j*2 +1);
		$css .= ".sprite_" . $cnt . $NL;
		$css .= "{\n";
		$css .= "   width: " . $ImgSize . "px;\n";
		$css .= "   height: " . $ImgSize . "px;\n";
		$css .= "   background: url(sprite-" . $k . ".jpg) no-repeat top left;\n";
		$css .= "   background-position: " . $posy . "px " . $posx . "px;\n";
		$css .= "}\n";
	    }
	}
    }

    file_put_contents($CSS1, $css);

    $css = "";
    $cnt = 0;
    for ($k = 0; $cnt < $MaxPreset; $k++)
    {
	for ($i = 0; $i < $TileW && $cnt < $MaxPreset; $i++)
	{
	    for ($j = 0; $j < $TileH && $cnt < $MaxPreset; $j++)
	    {
		$cnt++;
		$posx = -1 * ($i * $ImgSize + $i*2 +1);
		$posy = -1 * ($j * $ImgSize + $j*2 +1);
		$css .= ".sprite_" . $cnt . $NL;
		$css .= "{\n";
		$css .= "   width: " . $ImgSize . "px;\n";
		$css .= "   height: " . $ImgSize . "px;\n";
		$css .= "   background: url(sprite@2x-" . $k . ".jpg) no-repeat top left;\n";
		$css .= "   background-size: " . $SpriteW . "px " . $SpriteH . "px;\n";
		$css .= "   background-position: " . $posy . "px " . $posx . "px;\n";
		$css .= "}\n";
	    }
	}
    }

    file_put_contents($CSS2, $css);
}


// ########## Main  #######################################################################

function Main($DoLevel)
{
    global $NL;
    global $RootMenu;
    global $SubMenuType;
    global $TopDirectory;
    global $AppDir;
    global $DATABASE_FILENAME;

    $AppDir = "site/";

    if (!file_exists($AppDir . "folder"))
	mkdir($AppDir . "folder");
    if (!file_exists($AppDir . "sprites"))
	mkdir($AppDir . "sprites");

    $NumNewPlaylists = 0;

    //Create a didl file in each directory containing music
    if ($DoLevel > 3) 
    {
	echo "Removing old .dpl files" . $NL;
	UnlinkDPL();
    }
    
    echo "Making a didl file in each directory..." . $NL;
    $NumNewPlaylists = MakePlaylists($TopDirectory);
    echo " - found $NumNewPlaylists new playlists" . $NL;

    unlink($DATABASE_FILENAME);
    $musicDB = new MusicDB();

    echo "Find all didl files and add to Menu tree..." . $NL;
    // Find all didl files and add it to the menus
    try
    {
	CreateAllGreyImgs($musicDB->MaxPreset());
	foreach ($TopDirectory as $Dir => $RootMenuNo)
	{
	    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Dir));
	    while($it->valid())
	    {
		if($it->isFile())
		{
		    $ext = pathinfo($it->current(), PATHINFO_EXTENSION);

		    if ($ext == "xml")
		    {
			$didl = new DIDL_Album($it->getPathName(), $RootMenuNo);
			$exist = $musicDB->CheckURLExist($didl->URI());
			if ($exist === false)
			{
			    $rowid = Make_Album($didl, $musicDB);
			    Make_Tracks($didl, $musicDB);
			    //$didl->dump();
			}
			else
			{
			    $didl->SetSequenceNo($exist);
			}

			CollectFolderImgs($didl);
			echo ".";
		    }
		}
		$it->next();
	    }
	}
    }
    catch(Exception $e)
    {
	echo $e->getMessage();
    }

    copy("index.php", $AppDir . "index.php");
    copy("html_parts.php", $AppDir . "html_parts.php");
    copy("actions.js", $AppDir . "actions.js");
    copy("musik.css", $AppDir . "musik.css");
    copy("LinnDS-jukebox-daemon.php", $AppDir . "LinnDS-jukebox-daemon.php");
    copy("ServerState.php", $AppDir . "ServerState.php");
    copy("LPECClientSocket.php", $AppDir . "LPECClientSocket.php");
    copy("LinnDSClientSocket.php", $AppDir . "LinnDSClientSocket.php");
    copy("StringUtils.php", $AppDir . "StringUtils.php");
    copy("SocketServer.php", $AppDir . "SocketServer.php");
    copy("LinnDS-jukebox-daemon-old.php", $AppDir . "LinnDS-jukebox-daemon-old.php");
    copy("S98linn_lpec", $AppDir . "S98linn_lpec");
    copy("Transparent.gif", $AppDir . "Transparent.gif");
    copy("setup.php", $AppDir . "setup.php");
    copy("Send.php", $AppDir . "Send.php");
    copy("MusicDB.php", $AppDir . "MusicDB.php");
    copy("QueryAlbum.php", $AppDir . "QueryAlbum.php");
    copy("QueryAlbumList.php", $AppDir . "QueryAlbumList.php");
    copy("QueryAlphabetPresent.php", $AppDir . "QueryAlphabetPresent.php");
    copy("QueryDB.php", $AppDir . "QueryDB.php");
    copy("QueryPlayingNowDB.php", $AppDir . "QueryPlayingNowDB.php");

    echo "Making sprites and css file in " . $AppDir . $NL;
    Make_CSS($musicDB->MaxPreset(), $AppDir . "sprites/sprites.css", $AppDir . "sprites/sprites@2x.css");

    $musicDB->close();
    copy($DATABASE_FILENAME, $AppDir . $DATABASE_FILENAME);

    echo "Finished..." . $NL;
}

//Main(1);
Main(1);

?>
