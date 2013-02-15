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

$category = $_GET["category"];

$cachefilename = $CACHE_DIR . "/pagecategory-" . $category;

if ($DO_CACHE && file_exists($cachefilename) && filemtime($cachefilename) > filemtime($manifestfile))
{
    echo file_get_contents($cachefilename);
}
else
{
    $manifest = new Manifest($manifestfile);

    MakePageCategories($manifest);

    echo file_get_contents($cachefilename);
}

require("footer.inc");

?>
