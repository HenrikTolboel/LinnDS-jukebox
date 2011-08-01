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

    foreach ($manifest->Category as $cat => $catName) 
    {
	if ($cat != $category)
	    continue;
	if (strpos($catName, " / Album") > 3 && $manifest->GetCategoryCount($cat) > 15)
	{
	    $str .= Page("page_cat-" . $cat, "Artist Index",
		MakeArtistIndex3($manifest, "artistindex", $cat),
		"Page Footer");
	}
	else
	{
	    $str .= Page("page_cat-" . $cat, $catName, 
		MakePresetList($manifest, "presets-" . $cat, "#page-cat-" . $cat,
		    $manifest->CategoryFirstPreset[$cat], 
		    min(21, $manifest->GetCategoryCount($cat))), 
		"Page Footer");
	}
    }

    file_put_contents($cachefilename, $str);
    echo $str;
}

require("footer.inc");

?>
