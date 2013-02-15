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
require_once("MakePlaylists.php");
require_once("album.php");
require_once("FileUtils.php");



// ########## CLASS: DIDLPreset  ##########################################################

class DIDLPreset 
{
    private static $NoInstances = 0;
    private $Value = array();

    public function __construct($FileName, $RootMenuNo = -1)
    {
	self::$NoInstances++;
	$this->Value[InstanceNo] = self::$NoInstances;
	$this->Value[File] = new SplFileInfo($FileName);
	if ($this->Value[File]->getExtension() == "dpl")
	{
	    $this->Value[Info] = explode("+", $this->Value[File]->getBasename('.dpl'));
	    $this->Value[Path] = explode("/", $this->RelativePath($this->Value[File]->getPath()));
	}
	else
	{
	    $xml = simplexml_load_file($this->Value[File]->getPathname());

	    foreach ($xml->children() as $info) {
		$this->Value[$info->getName()]  = (string) $info;
	    }
	    $this->Value[Path] = explode("/", $this->RelativePath($this->Value[Playlist]));
	}
	$this->Value[RootMenuNo] = $RootMenuNo;
	//print_r($this->Value);
    }

    public function PlaylistFileName()
    {
	if ($this->Value[File]->getExtension() == "dpl")
	    return $this->Value[File]->getPathname();
	else
	    return $this->Value[Playlist];
    }

    public function ImageFileName()
    {
	if ($this->Value[File]->getExtension() == "dpl")
	    return $this->Value[File]->getPath() . "/folder.jpg";
	else
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
	    $RelDir = str_replace("LINN_JUKEBOX_URL/", "", $this->RelativePath($Dir));
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
	return $this->PrepareURI($this->PlaylistFileName());
    }

    public function ImageURI()
    {
	return $this->PrepareURI($this->ImageFileName());
    }

    public function Artist()
    {
	if ($this->Value[File]->getExtension() == "dpl")
	    return $this->Value[Info][0];
	else
	    return $this->Value[Artist];
    }
    public function ArtistFirst()
    {
	$F = strtoupper(substr($this->SortSkipWords($this->Artist()), 0, 1));

	if ($F >= "A" && $F < "Z")
	    return $F;
	else
	    return "#";
    }
    public function Album()
    {
	if ($this->Value[File]->getExtension() == "dpl")
	    return $this->Value[Info][1];
	else
	    return $this->Value[Album];
    }
    public function Date()
    {
	if ($this->Value[File]->getExtension() == "dpl")
	    return $this->Value[Info][2];
	else
	    return $this->Value[Date];
    }
    public function Genre()
    {
	if ($this->Value[File]->getExtension() == "dpl")
	    return $this->Value[Info][3];
	else
	    return $this->Value[Genre];
    }

    private function RelativePath($Path)
    {
	$Path = str_replace("/Users/henrik/Documents", "LINN_JUKEBOX_URL", $Path);
	$Path = str_replace("/Users/henrik/Music/MusicLib", "LINN_JUKEBOX_URL", $Path);

	return $Path;
    }


    private function PrepareURI($Path)
    {
	$Path = $this->RelativePath($Path);
	$encoded = $Path;
	//$encoded = implode("/", array_map("rawurlencode", explode("/", $encoded)));

	//$encoded = str_replace("a%CC%8A", "%C3%A5", $encoded);
	//$encoded = str_replace("&#", "%26%23", $encoded);

	//echo "\n";
	//echo "PrepareURI-path: " . $Path . "\n";
	//echo "PrepareURI-enco: " . $encoded . "\n";
	return $encoded;
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
}

// ########## CLASS: Menus  ###############################################################

class Menus
{
    public $Menu = array();
    public $MenuAlbumCnt = array();
    public $MenuCnt = 0;

    public $RootMenu;
    public $SubMenuType;

    public function __construct($RootMenu, $SubMenuType)
    {
	global $ALPHABET;
	global $ALPHABET_SIZE;

	$this->RootMenu = $RootMenu;
	$this->SubMenuType = $SubMenuType;

	$this->MenuCnt = count($this->RootMenu);
	for ($i=0; $i < $this->MenuCnt; $i++)
	{
	    if ($this->SubMenuType[$i] == SUBMENU_TYPE_NONE) 
	    {
		$this->Menu[$i] = new ArrayObject();
	    }
	    elseif ($this->SubMenuType[$i] == SUBMENU_TYPE_ALPHABET)
	    {
		for ($alpha = 0; $alpha < $ALPHABET_SIZE; $alpha++)
		{
		    $this->Menu[$i][$ALPHABET[$alpha]] = new ArrayObject();
		}
	    }
	}
    }

    public function sort()
    {
	global $ALPHABET;
	global $ALPHABET_SIZE;

	// Sort lists in Menu tree
	for ($i=0; $i < $this->MenuCnt; $i++)
	{
	    if ($this->SubMenuType[$i] == SUBMENU_TYPE_NONE) 
	    {
		$this->Menu[$i]->uasort(array('DIDLPreset', 'compare'));
		$this->MenuAlbumCnt[$i] = $this->Menu[$i]->count();
	    }
	    elseif ($this->SubMenuType[$i] == SUBMENU_TYPE_ALPHABET)
	    {
		$cnt = 0;
		for ($alpha = 0; $alpha < $ALPHABET_SIZE; $alpha++)
		{
		    $this->Menu[$i][$ALPHABET[$alpha]]->uasort(array('DIDLPreset', 'compare'));
		    $cnt += $this->Menu[$i][$ALPHABET[$alpha]]->count();
		}
		$this->MenuAlbumCnt[$i] = $cnt;
	    }
	}
	$this->OrderSequenceNo();
    }

    public function Add($didl)
    {
	global $NL;

	$RootMenuNo = $didl->RootMenuNo();
	if ($this->SubMenuType[$RootMenuNo] == SUBMENU_TYPE_NONE) 
	{
	    $this->Menu[$RootMenuNo]->append($didl);
	}
	elseif ($this->SubMenuType[$RootMenuNo] == SUBMENU_TYPE_ALPHABET)
	{
	    $ArtFirst = $didl->ArtistFirst();
	    //echo "ArtFirst = " . $ArtFirst . $NL;
	    $this->Menu[$RootMenuNo][$ArtFirst]->append($didl);
	}
    }

    public function OrderSequenceNo()
    {
	global $ALPHABET;
	global $ALPHABET_SIZE;

	$SeqNo = 1;
	for ($i=0; $i < $this->MenuCnt; $i++)
	{
	    if ($this->SubMenuType[$i] == SUBMENU_TYPE_NONE) 
	    {
		for ($it = $this->Menu[$i]->getIterator(); $it->valid(); $it->next())
		{
		    $it->current()->setSequenceNo($SeqNo);
		    $SeqNo++;
		}
	    }
	    elseif ($this->SubMenuType[$i] == SUBMENU_TYPE_ALPHABET)
	    {
		$cnt = 0;
		for ($alpha = 0; $alpha < $ALPHABET_SIZE; $alpha++)
		{
		    for ($it = $this->Menu[$i][$ALPHABET[$alpha]]->getIterator(); $it->valid(); $it->next())
		    {
			$it->current()->setSequenceNo($SeqNo);
			$SeqNo++;
		    }
		    $cnt += $this->Menu[$i][$ALPHABET[$alpha]]->count();
		}
	    }
	}
    }

    private function user_func_func($callback, &$ArrayList, &$res)
    {
	$it = $ArrayList->getIterator();
	while($it->valid())
	{
	    $Arr[$it->current()->SequenceNo()] = $it->current()->URI();

	    call_user_func_array($callback, array($it->current(), &$res));

	    $it->next();
	}
    }

    public function user_func($callback, &$res)
    {
	global $NL;
	global $ALPHABET;
	global $ALPHABET_SIZE;

	for ($i = 0; $i < $this->MenuCnt; $i++)
	{
	    if ($this->SubMenuType[$i] == SUBMENU_TYPE_NONE) 
	    {
		$this->user_func_func($callback, $this->Menu[$i], $res);
	    }
	    elseif ($this->SubMenuType[$i] == SUBMENU_TYPE_ALPHABET)
	    {
		for ($alpha = 0; $alpha < $ALPHABET_SIZE; $alpha++)
		{
		    $this->user_func_func($callback, $this->Menu[$i][$ALPHABET[$alpha]], $res);
		}
	    }
	}
    }

}

// ########## HTML  #######################################################################

function Page($id, $title, $content, $footer, $cache, $widgets)
{
    global $SQ;
    global $NL;

    $str = '<div data-role="page" data-dom-cache="' . $cache . '" id="' . $id . '">' . $NL;

    $str .= '<div data-role="header" data-position="fixed">' . $NL;
    $str .= '<h1>' . $title . '</h1>'. $NL;


    $str .= '<a id="' . $id . '-KontrolPanel" class="poppanel KontrolPanel ui-btn-left" href="#KontrolPanel" data-icon="bars">Kontrol</a>' . $NL;

    $str .= '</div><!-- /header -->' . $NL;

    $str .= '<div data-role="content">' . $NL;
    $str .= $content;
    $str .= $NL . '</div><!-- /content -->' . $NL;

    $str .= '<div data-role="footer">' . $NL;
    $str .= '<h4>' . $footer . "</h4>" . $NL;
    $str .= "</div><!-- /footer -->" . $NL;

    $str .= $widgets . $NL;

    $str .= "</div><!-- /page -->" . $NL . $NL;
    return $str;
}

function RootMenu($id, $RootMenu, &$Menu)
{
    global $SQ;
    global $DQ;
    global $NL;

    $str= '<ul data-role="listview" data-filter="false">' . $NL;
    for ($i=0; $i < $Menu->MenuCnt; $i++)
    {
	if ($i == 0) 
	{
	    $prefetch = " data-prefetch";
	}
	else
	{
	    $prefetch = "";
	}
	if ($Menu->SubMenuType[$i] == SUBMENU_TYPE_NONE) 
	    $str .= '<li><a href="p' . $i . ".html" . $DQ . $prefetch .'>' . $RootMenu[$i] .'</a>';
	else
	    $str .= '<li><a href="#p' . $i . $DQ . $prefetch .'>' . $RootMenu[$i] .'</a>';

        $str .= '<span class="ui-li-count">' . $Menu->MenuAlbumCnt[$i] .'</span>';
        $str .= '</li>' . $NL;
    }

    $str .= "</ul>" . $NL;

    return $str;
}

function DummyPopups()
{
    global $SQ;
    global $DQ;
    global $NL;

    $str= $NL;

    // The play menu is not used here, but it is copied from 
    // here on activation to other pages - done by actions.jp "a.pop" 
    // delegate
    $str .= '<div data-role="popup" id="play-popup" data-history="false">' . $NL;
    $str .= '<ul data-role="listview" data-inset="true" style="min-width:180px;">' . $NL;
    $str .= '<li><a href="#" class="popupclick" data-musik=' . $SQ . '{"action": "PlayNow"}' . $SQ . '">Play Now</a></li>' . $NL;
    $str .= '<li><a href="#" class="popupclick" data-musik=' . $SQ . '{"action": "PlayNext"}' . $SQ . '">Play Next</a></li>' . $NL;
    $str .= '<li><a href="#" class="popupclick" data-musik=' . $SQ . '{"action": "PlayLater"}' . $SQ . '">Play Later</a></li>' . $NL;
    $str .= '<li><a href="#" class="popupclick" data-musik=' . $SQ . '{"action": "Cancel"}' . $SQ . '">Cancel</a></li>' . $NL;
    $str .= "</ul>" . $NL;
    $str .= '</div>' . $NL;
    // End of play menu

    return $str;
}

function PageWidgets()
{
    global $SQ;
    global $DQ;
    global $NL;

    $str= $NL;

    // KontrolPanel...
    $str .= '<div data-role="panel" id="KontrolPanel" data-position="left" data-position-fixed="true">' . $NL;
	$str .= '<ul data-role="listview" data-theme="a" data-divider-theme="a" style="margin-top:-16px;margin-bottom:16px;" class="nav-search">' . $NL;
	    $str .= '<li data-icon="delete" style="background-color:#111;">' . $NL;
		$str .= '<a href="#" data-rel="close">Close</a>' . $NL;
	    $str .= '</li>' . $NL;
	$str .= '</ul>' . $NL;

        $str .= '<h4>Source</h4>' . $NL;
	$str .= '<button href="#" class="panelclick" data-mini="true" data-musik=' . $SQ . '{"action": "Source-Playlist"}' . $SQ . '">Playlist</button>' . $NL;
	$str .= '<button href="#" class="panelclick" data-mini="true" data-musik=' . $SQ . '{"action": "Source-TV"}' . $SQ . '">TV</button>' . $NL;
	$str .= '<button href="#" class="panelclick" data-mini="true" data-musik=' . $SQ . '{"action": "Source-Radio"}' . $SQ . '">Radio</button>' . $NL;
	$str .= '<button href="#" class="panelclick" data-mini="true" data-musik=' . $SQ . '{"action": "Source-NetAux"}' . $SQ . '">AirPlay</button>' . $NL;
	$str .= '<button href="#" class="panelclick" data-mini="true" data-musik=' . $SQ . '{"action": "Source-Off"}' . $SQ . '">Off</button>' . $NL;



        $str .= '<h4>Kontrol</h4>' . $NL;
	$str .= '<button href="#" class="panelclick" data-mini="true" data-musik=' . $SQ . '{"action": "Control-Play"}' . $SQ . '">Play</button>' . $NL;
	$str .= '<button href="#" class="panelclick" data-mini="true" data-musik=' . $SQ . '{"action": "Control-Pause"}' . $SQ . '">Pause</button>' . $NL;
	$str .= '<button href="#" class="panelclick" data-mini="true" data-musik=' . $SQ . '{"action": "Control-Stop"}' . $SQ . '">Stop</button>' . $NL;
        $str .= '<label for="volume">Volume:</label>' . $NL;
        $str .= '<input type="range" name="volume" id="volume" value="35" min="20" max="60" data-mini="true"/>' . $NL;
	$str .= '<button href="#" class="panelclick" data-mini="true" data-musik=' . $SQ . '{"action": "Control-Next"}' . $SQ . '">Next</button>' . $NL;
	$str .= '<button href="#" class="panelclick" data-mini="true" data-musik=' . $SQ . '{"action": "Control-Previous"}' . $SQ . '">Previous</button>' . $NL;
	
    $str .= '</div><!-- /panel -->' . $NL;
    //End of KontrolPanel
    
    return $str;
}


function MenuAlbumList($id, &$ArrayList)
{
    global $SQ;
    global $NL;

    $str = '<ul data-role="listview" data-filter="false">' . $NL;
    $funcs = "";

    $it = $ArrayList->getIterator();
    while($it->valid())
    {
	$str .= '<li>';

	//$str .= '<a href="#'. $id . '-' . $c . '" data-rel="popup" data-history="false">';
	$str .= '<a id="' . $it->current()->SequenceNo() . '" class="pop" data-rel=popup" href="#" data-musik=' . $SQ . '{"id": "' . $id . '-popup", "preset": "' . $it->current()->SequenceNo() . '"}' . $SQ . '>';

	$str .= '<img class="sprite_' . $it->current()->SequenceNo() . '" src="Transparent.gif"/>';

	$str .= '<h3>';

	if ($it->current()->Artist() == "Various")
	{
	    $str .= $it->current()->Album();
	    $str .= '</h3>';
	    $str .= '<p>' . ' (' . $it->current()->Date() . ')</p>';  
	    $str .= '</a>';
	}
	else
	{
	    $str .= $it->current()->Artist();
	    $str .= '</h3>';
	    $str .= '<p>' . $it->current()->Album() . ' (' . $it->current()->Date() . ')</p>';  
	    $str .= '</a>';
	}

	$str .= '<a href="album_' . $it->current()->SequenceNo() . '.html"></a>';

	$str .= "</li>" . $NL;

	$it->next();
    }

    $str .= "</ul>" . $NL;

    $str .= '<div data-role="popup" id="' . $id . '-popup">' . $NL;
    $str .= '</div>' . $NL;

    return $str;
}

function MenuAlphabetPage($id, &$ArrayListList)
{
    global $SQ;
    global $NL;
    global $ALPHABET;
    global $ALPHABET_SIZE;

    $str = '<div class="ui-grid-c">' . $NL;

    $class = "ui-block-a";

    for ($alpha = 0; $alpha < $ALPHABET_SIZE; $alpha++)
    {
	if ($ArrayListList[$ALPHABET[$alpha]]->count() < 1)
	    $class .= " ui-disabled";
	$str .= '<div class="' . $class . '">';
	//$href = '#' . $id . "_" . $ALPHABET[$alpha];
	if ($ALPHABET[$alpha] == "#")
	    $href = $id . "_%23" . ".html";
	else
	    $href = $id . "_" . $ALPHABET[$alpha] . ".html";
	$str .= '<a href="' . $href . '" data-role="button">';
	$str .= strtoupper($ALPHABET[$alpha]);
	$str .= '</a>';
	$str .= "</div>" . $NL;
	$class = "ui-block-b";
    }

    $str .= "</div>" . $NL;

    return $str;
}


function HTMLDocument($WrapStr)
{
    global $NL;

    $str = file_get_contents("header.inc");
    $str .= '<div id="globals" data-musik=""></div>' . $NL . $NL;

    $str .= $WrapStr;

    $str .= file_get_contents("footer.inc");

    return $str;
}

function MainMenu(&$Menu)
{
    global $NL;
    global $AppDir;
    global $ALPHABET;
    global $ALPHABET_SIZE;
    global $RootMenu;

    $str .= Page("page_musik", "Musik", RootMenu("RootMenu", $Menu->RootMenu, $Menu), "LinnDS-jukebox", "true", DummyPopups() . PageWidgets());

    for ($i = 0; $i < $Menu->MenuCnt; $i++)
    {
	if ($Menu->SubMenuType[$i] == SUBMENU_TYPE_NONE) 
	{
	    file_put_contents($AppDir . "p" . $i . ".html", 
		HTMLDocument(Page("p" . $i, $RootMenu[$i], MenuAlbumList("p" . $i, $Menu->Menu[$i]), "LinnDS-jukebox", "false", DummyPopups() . PageWidgets())));
	}
	elseif ($Menu->SubMenuType[$i] == SUBMENU_TYPE_ALPHABET)
	{
	    $str .= Page("p" . $i, $RootMenu[$i], MenuAlphabetPage("p" . $i, $Menu->Menu[$i]), "LinnDS-jukebox", "false", PageWidgets());

	    for ($alpha = 0; $alpha < $ALPHABET_SIZE; $alpha++)
	    {
		if ($Menu->Menu[$i][$ALPHABET[$alpha]]->count() > 0)
		{
		    file_put_contents($AppDir . "p" . $i . "_" . $ALPHABET[$alpha] . ".html", 
			HTMLDocument(Page("p" . $i . "_" . $ALPHABET[$alpha], 
			$Menu->RootMenu[$i] . " - " . $ALPHABET[$alpha],
			MenuAlbumList("p" . $i . "_" . $ALPHABET[$alpha], $Menu->Menu[$i][$ALPHABET[$alpha]]),
			"LinnDS-jukebox", "false", DummyPopups() . PageWidgets())));
		}
	    }
	}
    }

    return $str;
}

function user_func_example(&$didl, &$res)
{
    $res[$didl->SequenceNo()] = $didl->URI();
}

function Make_AlbumHTML(&$didl, &$AlbumCnt)
{
    global $AppDir;

    $FolderImg = sprintf("folder/folder_%04d.jpg", $didl->SequenceNo());
    file_put_contents($AppDir . 'album_' . $didl->SequenceNo() . '.html', 
	HTMLDocument(
	    Page("album-" . $didl->SequenceNo(), "Album", 
	    Album($didl->PlaylistFileName(), $FolderImg),
	    "LinnDS-jukebox", "false", DummyPopups() . PageWidgets())));
    $AlbumCnt++;
}

function Make_URI_Array(&$didl, &$URI_Array)
{
    $URI_Array[$didl->SequenceNo()] = $didl->URI();
}

function CollectFolderImgs(&$didl, &$res)
{
    global $AppDir;

    $img = $didl->ImageFileName();
    
    if (strlen($img) <= 4 || !file_exists($img))
    {
	$img = "grey.jpg";
    }
    $newfile = sprintf($AppDir . "folder/folder_%04d.jpg", $didl->SequenceNo());
    copy($img, $newfile);
}

function Make_CSS($AlbumCnt, $CSS1, $CSS2)
{
    global $NL;
    global $AppDir;

    $ImgSize = 80;
    $TileW = 10;
    $TileH = 10;

    $SpriteW = $ImgSize * $TileW + 2 * $TileW;
    $SpriteH = $ImgSize * $TileH + 2 * $TileH;

    // On an ipad somehow the size of a sprite image should be < 1024 pixels 
    // wide / high - otherwise the display of sprite elements are distorted.

    $cmd1 = "montage -background transparent -tile " . $TileW . "x" . $TileH . " -geometry 80x80+1+1 " . $AppDir . "folder/folder_* " . $AppDir . "sprites/sprite.jpg";
    echo $cmd1 . $NL;
    $cmd2 = "montage -background transparent -tile " . $TileW . "x" . $TileH . " -geometry 160x160+1+1 " . $AppDir . "folder/folder_* " . $AppDir . "sprites/sprite@2x.jpg";

    shell_exec($cmd1);
    echo $cmd2 . $NL;
    shell_exec($cmd2);

    $css = "";
    $cnt = 0;
    for ($k = 0; $cnt < $AlbumCnt; $k++)
    {
	for ($i = 0; $i < $TileW && $cnt < $AlbumCnt; $i++)
	{
	    for ($j = 0; $j < $TileH && $cnt < $AlbumCnt; $j++)
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
    for ($k = 0; $cnt < $AlbumCnt; $k++)
    {
	for ($i = 0; $i < $TileW && $cnt < $AlbumCnt; $i++)
	{
	    for ($j = 0; $j < $TileH && $cnt < $AlbumCnt; $j++)
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

    //Create a didl file in each directory containing music
    if ($DoLevel > 3) 
    {
	echo "Removing old .dpl files" . $NL;
	UnlinkDPL();
    }
    if ($DoLevel > 0) 
    {
	echo "Making a didl file in each directory..." . $NL;
	MakePlaylists($TopDirectory);
    }

    //Build Menu tree
    echo "Building Menu tree..." . $NL;
    $Menu = new Menus($RootMenu, $SubMenuType);




     echo "Find all didl files and add to Menu tree..." . $NL;
    // Find all didl files and add it to the menus
    try
    {
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
			$didl = new DIDLPreset($it->getPathName(), $RootMenuNo);
			$Menu->Add($didl);
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

    echo "Sort menu tree..." . $NL;
    $Menu->sort();

    //print_r($Menu);

    $AppDir = "site/";

    if ($DoLevel > 1) 
    {
	$cmd = "rm " . $AppDir . "* " . $AppDir . "*/*";
	echo "executing " . $cmd . $NL;
	shell_exec($cmd);
    }

    copy("actions.js", $AppDir . "actions.js");
    copy("musik.css", $AppDir . "musik.css");
    copy("daemon/LinnDS-jukebox-daemon.php", $AppDir . "LinnDS-jukebox-daemon.php");
    copy("daemon/S98linn_lpec", $AppDir . "S98linn_lpec");
    copy("Transparent.gif", $AppDir . "Transparent.gif");
    copy("setup.php", $AppDir . "setup.php");
    copy("Send.php", $AppDir . "Send.php");

    echo "Writing MainMenu to " . $AppDir . $NL;
    file_put_contents($AppDir . "index.html", HTMLDocument(MainMenu($Menu)));

    echo "Making URI_Index in " . $AppDir . $NL;
    $URI_Array = array();
    $Menu->user_func('Make_URI_Array', $URI_Array);
    file_put_contents($AppDir . "URI_index", serialize($URI_Array));

    echo "Making album files in " . $AppDir . $NL;
    $AlbumCnt = 0;
    $Menu->user_func('Make_AlbumHTML', $AlbumCnt);

    if ($DoLevel > 1) 
    {
	echo "Collecting directory images in " . $AppDir . $NL;
	$Menu->user_func('CollectFolderImgs', $dummy);
    }

    if ($DoLevel > 1) 
    {
	echo "Making sprites and css file in " . $AppDir . $NL;
	Make_CSS($AlbumCnt, $AppDir . "sprites/sprites.css", $AppDir . "sprites/sprites@2x.css");
    }

    echo "Finished..." . $NL;
}

//Main(1);
Main(1);

?>
