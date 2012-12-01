<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2012 Henrik Tolbøl, http://tolbøl.dk
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

$LINN_JUKEBOX_URL = "http://192.168.0.105/musik";


// These directories are scanned for ".dpl" files
// Each directory maps into one of the RootMenu entries.
$TopDirectory = array();
$TopDirectory["/Users/henrik/Music/MusicLib/EAC"]                = 0;
$TopDirectory["/Users/henrik/Music/MusicLib/Linn"]               = 1;
$TopDirectory["/Users/henrik/Music/MusicLib/Opsamlinger"]        = 2;
$TopDirectory["/Users/henrik/Music/MusicLib/EAC Classical"]      = 3;
$TopDirectory["/Users/henrik/Music/MusicLib/Børn"]               = 4;
$TopDirectory["/Users/henrik/Music/MusicLib/Børn - opsamlinger"] = 5;
$TopDirectory["/Users/henrik/Music/MusicLib/Diverse"]            = 6;
$TopDirectory["/Users/henrik/Music/MusicLib/Jul"]                = 6;


// These are the RootMenu entries. The names are those displayed there
$RootMenu = array();
$RootMenu[0] = "Kunstner / Album";
$RootMenu[1] = "Linn / Album";
$RootMenu[2] = "Opsamlinger";
$RootMenu[3] = "Klassisk / Album";
$RootMenu[4] = "Børn - Kunstner / Album";
$RootMenu[5] = "Børn - Opsamlinger";
$RootMenu[6] = "Diverse";


// Currently we have to types of sub menus of the root menus.
define("SUBMENU_TYPE_NONE", 0);
define("SUBMENU_TYPE_ALPHABET", 1);


// This is the type of submenu for each of the RootMenu entries.
$SubMenuType = array();
$SubMenuType[0] = SUBMENU_TYPE_ALPHABET;
$SubMenuType[1] = SUBMENU_TYPE_ALPHABET;
$SubMenuType[2] = SUBMENU_TYPE_NONE;
$SubMenuType[3] = SUBMENU_TYPE_ALPHABET;
$SubMenuType[4] = SUBMENU_TYPE_ALPHABET;
$SubMenuType[5] = SUBMENU_TYPE_NONE;
$SubMenuType[6] = SUBMENU_TYPE_NONE;

// The ALPHABET submenus are ordered into these groups
$ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ#";
$ALPHABET_SIZE = strlen($ALPHABET);


// Words to be skipped when sorting e.g. Artist names
// Meaning that "The Beatles" sorts as "Beatles"
$SortSkipList = array("The ", "the ");

//print "TopDirectory: "; print_r($TopDirectory);
//print "RootMenu    : "; print_r($RootMenu);
//print "SubMenuType : "; print_r($SubMenuType);

?>
