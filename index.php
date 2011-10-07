<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require("header.inc");
require_once("setup.php");
require_once("Manifest.php");
require_once("Functions.php");
$cachefilename = $CACHE_DIR . "/manifest-pages";

if ($DO_CACHE && file_exists($cachefilename) && filemtime($cachefilename) > filemtime($manifestfile))
{
    echo file_get_contents($cachefilename);
}
else
{
    $manifest = new Manifest($manifestfile);
    $str = '<div class="play"></div>';
    $str .= Page("page_musik", "Musik", CategoryList("categorylist", $manifest), "Page Footer");

    file_put_contents($cachefilename, $str);
    echo $str;
    MakePageCategories($manifest);
}
    
require("footer.inc");

?>
