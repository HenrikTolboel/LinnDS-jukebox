<!DOCTYPE html>
<!--
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/ -->

<html>
<head>
    <meta charset="utf-8">

	<title>LinnDS-jukebox</title> 

<?php require("header.inc"); ?>
</head>
<body>

<?php
   require_once("setup.php");
   require_once("Manifest.php");
   require_once("Functions.php");
   flush();
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
    foreach ($manifest->Category as $cat => $catName) 
    {
        if (strpos($catName, " / Album") > 3 && $manifest->GetCategoryCount($cat) > 15)
        {
            $str .= Page("page_cat-" . $cat, "Artist Index",
                MakeArtistIndex2($manifest, "artistindex", $cat),
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

    $str .= Page("page_options", "Options", "<h2>Options</h2>\n<p>I'm only shown when selecting options.</p>\n<p><a href=\"#page_musik\">Back to Musik</a></p>" . '<p><form action="form.php" method="post"><div data-role="fieldcontain"><label for="volume">Volume:</label><input type="range" name="volume" id="volume" value="35" min="0" max="60"  /></form></div></p>', "Page Footer");


    file_put_contents($cachefilename, $str);
    echo $str;
    }
?>
        
<?php require("footer.inc"); ?>

</body>
</html>
