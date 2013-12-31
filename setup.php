<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2013 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

$PRESETS_DIR  = "../_Presets";
$CACHE_DIR  = "./tmp";

$DO_CACHE = false;
$DO_CACHE = true;

$USE_DIALOG = true;

$NL = "\n";
$SQ = "'";
$DQ = '"';
$DIR_DELIM = "/";

$manifestfile = $PRESETS_DIR ."/" . "manifest.xml";

// These 2 "path" are equal on your music server. I.e. files search in the 
// http url are found locally in the path...
$LINN_JUKEBOX_URL = "http://192.168.0.105/musik";
$LINN_JUKEBOX_PATH = "/musik";

$LINN_JUKEBOX_URL = "http://192.168.0.179/musik";
$LINN_JUKEBOX_PATH = "/volume1/web/musik";

// This is where your linn is in the network.
$LINN_HOST = "192.168.0.108";
$LINN_PORT = 23;

$IsJul = 0;

$BUILD_PATH = "/Users/henrik/nobackup/MusicLib";

// These directories are scanned for ".dpl" files
// Each directory maps into one of the RootMenu entries.
$TopDirectory = array();
$TopDirectory["$BUILD_PATH/EAC"]                = 0;
$TopDirectory["$BUILD_PATH/Linn"]               = 1;
$TopDirectory["$BUILD_PATH/Opsamlinger"]        = 2;
$TopDirectory["$BUILD_PATH/EAC Classical"]      = 3;
$TopDirectory["$BUILD_PATH/Børn"]               = 4;
$TopDirectory["$BUILD_PATH/Børn - opsamlinger"] = 5;
$TopDirectory["$BUILD_PATH/Diverse"]            = 6;
if ($IsJul == 1)
{
    $TopDirectory["$BUILD_PATH/Jul"]                = 8;
}
else
{
    $TopDirectory["$BUILD_PATH/Jul"]                = 6;
}


// These are the RootMenu entries. The names are those displayed there
$RootMenu = array();
$RootMenu[0] = "Kunstner / Album";
$RootMenu[1] = "Linn / Album";
$RootMenu[2] = "Opsamlinger";
$RootMenu[3] = "Klassisk / Album";
$RootMenu[4] = "Børn - Kunstner / Album";
$RootMenu[5] = "Børn - Opsamlinger";
$RootMenu[6] = "Diverse";
$RootMenu[7] = "Newest";
if ($IsJul == 1)
{
    $RootMenu[8] = "Jul";
}


// Currently we have 3 types of sub menus of the root menus.
define("SUBMENU_TYPE_NONE", 0);
define("SUBMENU_TYPE_ALPHABET", 1);
define("SUBMENU_TYPE_NEWEST", 2);


// This is the type of submenu for each of the RootMenu entries.
$SubMenuType = array();
$SubMenuType[0] = SUBMENU_TYPE_ALPHABET;
$SubMenuType[1] = SUBMENU_TYPE_ALPHABET;
$SubMenuType[2] = SUBMENU_TYPE_NONE;
$SubMenuType[3] = SUBMENU_TYPE_ALPHABET;
$SubMenuType[4] = SUBMENU_TYPE_ALPHABET;
$SubMenuType[5] = SUBMENU_TYPE_NONE;
$SubMenuType[6] = SUBMENU_TYPE_NONE;
$SubMenuType[7] = SUBMENU_TYPE_NEWEST;
if ($IsJul == 1)
{
    $SubMenuType[8] = SUBMENU_TYPE_NONE;
}

// The ALPHABET submenus are ordered into these groups
$ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ#";
$ALPHABET_SIZE = strlen($ALPHABET);

// The NEWEST submenus contain this amount newest added albums
$NEWEST_COUNT = 50;


// Words to be skipped when sorting e.g. Artist names
// Meaning that "The Beatles" sorts as "Beatles"
$SortSkipList = array("The ", "the ");

//print "TopDirectory: "; print_r($TopDirectory);
//print "RootMenu    : "; print_r($RootMenu);
//print "SubMenuType : "; print_r($SubMenuType);

// These functions are used to protect directory paths and filenames. Making 
// such paths path relative - to make it possible to use different machines 
// for Your music. The relativity of file is done by replacing the front of 
// paths with the string "LINN_JUKEBOX_URL".

function RelativePath($Path)
{
    global $LINN_JUKEBOX_URL;
    global $BUILD_PATH;

    $Path = str_replace("/Users/henrik/Documents", "LINN_JUKEBOX_URL", $Path);
    $Path = str_replace("$BUILD_PATH", "LINN_JUKEBOX_URL", $Path);
    return $Path;
}

function RelativeBuildPath($Path)
{
    global $LINN_JUKEBOX_URL;
    global $BUILD_PATH;

    $Path = str_replace("/Users/henrik/Documents", "BUILD_PATH", $Path);
    $Path = str_replace("$BUILD_PATH", "BUILD_PATH", $Path);
    return $Path;
}

function ProtectPath($Path)
{
    global $NL;

    //echo "ProtectPath-beg: " . $Path . $NL;
    $Path = str_replace("+", "XXXplusXXX", $Path);
    $Path = implode("/", array_map("rawurlencode", explode("/", $Path)));
    $Path = str_replace("a%CC%8A", "%C3%A5", $Path); // danish å encoded wrong
    $Path = str_replace("A%CC%8A", "%C3%85", $Path); // danish Å encoded wrong
    $Path = str_replace("e%CC%81", "%C3%A9", $Path); // é encoded wrong
    $Path = str_replace("U%CC%88", "%C3%9C", $Path); // Ü encoded wrong
    $Path = str_replace("u%CC%88", "%C3%BC", $Path); // ü encoded wrong
    $Path = str_replace("XXXplusXXX", "+", $Path);
    //http://www.w3schools.com/tags/ref_urlencode.asp
    //echo "ProtectPath-res: " . $Path . $NL;
    return $Path;
	////$dpl = str_replace("&#", "%26%23", $dpl);
	////$dpl = str_replace(" ", "%20", $dpl);
	////$dpl = htmlentities($dpl);
	////$dpl = str_replace("'", "&amp;#39;", $dpl);

	//$dpl = str_replace("&", "\&", $dpl);
	//$dpl = str_replace("#", "\#", $dpl);
	//$dpl = str_replace(";", "\;", $dpl);
	//$dpl = str_replace("'", "\'", $dpl);
	//$dpl = str_replace(" ", "\ ", $dpl);
}

function AbsolutePath($Path)
{
    global $LINN_JUKEBOX_PATH;
    global $BUILD_PATH;

    $Path = str_replace("LINN_JUKEBOX_URL", $LINN_JUKEBOX_PATH, $Path);
    $Path = str_replace("BUILD_PATH", $LINN_JUKEBOX_PATH, $Path);

    return $Path;
}
function AbsoluteBuildPath($Path)
{
    global $LINN_JUKEBOX_PATH;
    global $BUILD_PATH;

    $Path = str_replace("LINN_JUKEBOX_URL", $LINN_JUKEBOX_PATH, $Path);
    $Path = str_replace("BUILD_PATH", $BUILD_PATH, $Path);

    return $Path;
}

function AbsoluteURL($Path)
{
    global $LINN_JUKEBOX_URL;

    $Path = str_replace("LINN_JUKEBOX_URL", $LINN_JUKEBOX_URL, $Path);

    return $Path;
}

?>
