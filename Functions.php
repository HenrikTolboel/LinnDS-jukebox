<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2012 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");

function Page($id, $title, $content, $footer, $cache)
{
    global $SQ;
    global $NL;

    $str = '<div data-role="page" data-dom-cache="' . $cache . '" id="' . $id . '">' . $NL;
    //$str = '<div data-role="page" id="' . $id . '">' . $NL;

    $str .= '<div data-role="header" data-position="fixed">' . $NL;
    $str .= '<h1>' . $title . '</h1>'. $NL;

$str .= '<a id="' . $id . '-popupSource-pos" class="popsource" href="#" data-rel="popup" data-history="false" data-icon="gear" data-musik=' . $SQ . '{"id": "' . $id . '-popupSource"}' . $SQ . '>Kilde</a>' . $NL;

$str .= '<a id="' . $id . '-popupControl-pos" class="popcontrol" href="#" data-rel="popup" data-history="false" data-icon="gear" data-musik=' . $SQ . '{"id": "' . $id . '-popupControl"}' . $SQ . '>Kontrol</a>' . $NL;


    $str .= '<div data-role="popup" id="' . $id . '-popupSource">' . $NL;
    $str .= '</div>' . $NL;


    $str .= '<div data-role="popup" id="' . $id . '-popupControl">' . $NL;
    $str .= '</div> <!-- popup: popupControl -->' . $NL;


    $str .= '</div><!-- /header -->' . $NL;

    $str .= '<div data-role="content">' . $NL;
    $str .= $content;
    $str .= $NL . '</div><!-- /content -->' . $NL;

    //$str .= '<div data-role="footer" data-position="fixed">' . $NL;
    $str .= '<div data-role="footer">' . $NL;
    $str .= '<h4>' . $footer . "</h4>" . $NL;
    $str .= "</div><!-- /footer -->" . $NL;

    $str .= "</div><!-- /page -->" . $NL . $NL;
    return $str;
}

function Dialog($id, $title, $content)
{
    global $SQ;
    global $NL;

    $str = '<div data-role="page" id="' . $id . '">'. $NL;

    $str .= '<div data-role="header" data-position="inline">' . $NL;
    $str .= '<h1>' . $title . '</h1>' . $NL;
    $str .= '</div><!-- /header -->' . $NL;

    $str .= '<div data-role="content">' . $NL;
    $str .= $content;
    $str .= $NL . '</div><!-- /content -->' . $NL;

    $str .= "</div><!-- /page -->" . $NL . $NL;
    return $str;
}

function data_uri($file, $mime) 
{
    $contents = file_get_contents($file);
    $base64 = base64_encode($contents);
    return "data:$mime;base64,$base64";
}

function CategoryList($id, &$manifest)
{
    global $SQ;
    global $NL;

    $str= '<ul id="' . $id . '" data-role="listview" data-filter="false">' . $NL;
    foreach ($manifest->Category as $cat => $catName) 
    {
	if ($cat == "1") 
	{
	    $prefetch = " data-prefetch";
	}
	else
	{
	    $prefetch = "";
	}
        $str .= '<li><a href="pagecategory.php?category=' . $cat . '"' . $prefetch .'>' . $catName .'</a>';

        $str .= '<span class="ui-li-count">' . $manifest->GetCategoryCount($cat) .'</span>';
        $str .= '</li>' . $NL;
    }

    $str .= "</ul>";

    return $str;
}

function MakeDialogPresetList(&$manifest, $id, $FromPage, $FirstPreset, $PresetCount)
{
    global $SQ;
    global $NL;

    $str = '<ul id="' . $id . '" class="presets" data-role="listview" data-filter="false">' . $NL;

    for ($i = $FirstPreset; 
	$i < $FirstPreset + $PresetCount;
	$i++)
    {
	$str .= '<li class="onepreset">';
        $str .= '<a href="PlayDialog.php?preset=' . $i . '" data-rel="dialog">';
	if (true || file_exists("../" . $manifest->PresetImage80x80[$i]))
	{
	    $str .= '<img class="sprite_' . $i . '" src="Transparent.gif"/>';
	}
	elseif (file_exists("../" . $manifest->PresetImage[$i]))
	{
	    $str .= '<img class="lazy" src="Transparent.gif" data-original="' . "../" . $manifest->PresetImage[$i] . '" />';
	}
	else
	{
	    $str .= '<img src="Transparent.gif" />';
	}
        $str .= '<h3>';
        if ($manifest->PresetArtist[$i] == "Various")
        {
            $str .= $manifest->PresetAlbum[$i];
            $str .= '</h3>';
            $str .= '<p>' . ' (' . $manifest->PresetYear[$i] . ')</p>';  
            $str .= '</a>';
        }
        else
        {
            $str .= $manifest->PresetArtist[$i];
            $str .= '</h3>';
            $str .= '<p>' . $manifest->PresetAlbum[$i] . ' (' . $manifest->PresetYear[$i] . ')</p>';  
            $str .= '</a>';
        }
        $str .= '<a href="album.php?preset=' . $i . '"></a>';

        $str .= "</li>" . $NL;
    }

    $str .= "</ul>";

    return $str;
}

function MakePopupPresetList(&$manifest, $id, $FromPage, $FirstPreset, $PresetCount)
{
    global $SQ;
    global $NL;

   $str = '<ul id="' . $id . '" data-role="listview" data-filter="false">' . $NL;

   for ($i = $FirstPreset; $i < $FirstPreset + $PresetCount; $i++)
   {
      $str .= '<li>';
      $str .= '<a href="#popupMenu-' . $i . '" data-rel="popup" data-history="false">';

      $str .= '<img class="sprite_' . $i . '" src="Transparent.gif"/>';

      $str .= '<h3>';

      if ($manifest->PresetArtist[$i] == "Various")
      {
         $str .= $manifest->PresetAlbum[$i];
         $str .= '</h3>';
         $str .= '<p>' . ' (' . $manifest->PresetYear[$i] . ')</p>';  
         $str .= '</a>';
      }
      else
      {
         $str .= $manifest->PresetArtist[$i];
         $str .= '</h3>';
         $str .= '<p>' . $manifest->PresetAlbum[$i] . ' (' . $manifest->PresetYear[$i] . ')</p>';  
         $str .= '</a>';
      }

      $str .= '<a href="album.php?preset=' . $i . '"></a>';

      $str .= "</li>" . $NL;
   }

   $str .= "</ul>";

   for ($i = $FirstPreset; $i < $FirstPreset + $PresetCount; $i++)
   {
		$str .= '<div data-role="popup" id="popupMenu-' . $i . '">' . $NL;
      $str .= '<ul data-role="listview" data-inset="true" style="min-width:180px;">' . $NL;
      $str .= '<li><a href="#" class="popupclick" data-musik=' . $SQ . '{"action": "PlayNow", "preset": "' . $i . '"}' . $SQ . '">Play Now</a></li>' . $NL;
      $str .= '<li><a href="#" class="popupclick" data-musik=' . $SQ . '{"action": "PlayNext", "preset": "' . $i . '"}' . $SQ . '">Play Next</a></li>' . $NL;
      $str .= '<li><a href="#" class="popupclick" data-musik=' . $SQ . '{"action": "PlayLater", "preset": "' . $i . '"}' . $SQ . '">Play Later</a></li>' . $NL;
      $str .= '<li><a href="#" class="popupclick" data-musik=' . $SQ . '{"action": "Cancel", "preset": "' . $i . '"}' . $SQ . '">Cancel</a></li>' . $NL;
   $str .= "</ul>" . $NL;
		$str .= '</div>' . $NL;
   }

   return $str;
}

function MakePresetList(&$manifest, $id, $FromPage, $FirstPreset, $PresetCount)
{
   global $USE_DIALOG;
    global $SQ;
    global $NL;


   if ($USE_DIALOG)
   {
      return MakePopupPresetList(&$manifest, $id, $FromPage, $FirstPreset, $PresetCount);
   }
   else
   {
      return MakeDialogPresetList(&$manifest, $id, $FromPage, $FirstPreset, $PresetCount);
   }
}

function MakeArtistIndex1(&$manifest, $id, $Category)
{
    global $SQ;
    global $NL;

    $str = '<ul id="' . $id . '" class="artistindex" data-role="listview" data-filter="false">' . $NL;

    $c = min(20, ceil($manifest->GetCategoryCount($Category)/4)); // When doing index always at least 4 categories

    $end = $manifest->CategoryFirstPreset[$Category] + $manifest->GetCategoryCount($Category);

    for ($fp = $manifest->CategoryFirstPreset[$Category]; 
    $fp < $end;
    $fp= $fp + $c)
    {
        if ($fp + $c > $end)
            $c = $end - $fp;
        $str .= '<li class="artistindex">';
        //$str .= '<img class="artistindex" src="' . $manifest->ImageBase . "/" . $manifest->PresetImage80x80[$fp] . '" />';
        $str .= '<a href="presets.php?firstpreset=' . $fp . '&count=' . $c . '">';
        $str .= '0' . $fp . ' -- ' . $manifest->PresetArtist[$fp] . '</a>';

        $str .= "</li>" . $NL;
    }

    $str .= "</ul>";

    return $str;
}

function MakeArtistIndex2(&$manifest, $id, $Category)
{
    global $SQ;
    global $NL;

   // Keep same artist on a page
   //
   $str = '<ul id="' . $id . '" class="artistindex" data-role="listview" data-filter="false">' . $NL;

   $c = min(20, ceil($manifest->GetCategoryCount($Category)/4)); // When doing index always at least 4 categories

   $end = $manifest->CategoryFirstPreset[$Category] + $manifest->GetCategoryCount($Category);

   $fp = $manifest->CategoryFirstPreset[$Category]; 
   while ($fp < $end)
   {
      $cnt = $c;
      if ($fp + $c > $end)
         $cnt = $end - $fp;
      else
      {
         $tmp = $cnt;
         while (strcmp($manifest->PresetArtist[$fp+$tmp-1], $manifest->PresetArtist[$fp+$cnt]) == 0 && $fp + $cnt < $end)
            $cnt++;
      }

      $str .= '<li class="artistindex">';
      //$str .= '<img class="artistindex" src="' . $manifest->ImageBase . "/" . $manifest->PresetImage80x80[$fp] . '" />';
      //$str .= '<a href="../../presets.php?firstpreset=' . $fp . '&count=' . $cnt . '">';
      $str .= '<a href="presets.php?firstpreset=' . $fp . '&count=' . $cnt . '">';
      $str .= '0' . $fp . ' -- ' . $manifest->PresetArtist[$fp] . '</a>';

      $str .= "</li>" . $NL;

      $fp += $cnt;
   }

   $str .= "</ul>";

   return $str;
}

function MakeArtistIndex3(&$manifest, $id, $Category)
{
    global $SQ;
    global $NL;

   // Keep artist after first letter on same page
   //

   $c = 1;

   $end = $manifest->CategoryFirstPreset[$Category] + $manifest->GetCategoryCount($Category);

   $fp = $manifest->CategoryFirstPreset[$Category]; 
   $FP = array();
   $CNT = array();
   $Index = 1;
   $FP[0] = -1;
   $CNT[0] = 0;
   while ($fp < $end)
   {
      $cnt = $c;
      if ($fp + $c > $end)
         $cnt = $end - $fp;
      else
      {
         $tmp = $cnt;
         while (strcmp(strtoupper($manifest->PresetArtistSkip[$fp+$tmp-1][0]), strtoupper($manifest->PresetArtistSkip[$fp+$cnt][0])) == 0 && $fp + $cnt < $end)
            $cnt++;
      }

      $FP[$Index] = $fp;
      $CNT[$Index] = $cnt;
      $Index = $Index + 1;

      $fp += $cnt;
   }
    $FP[$Index] = -1;
    $CNT[$Index] = 0;

   $str = '<ul id="' . $id . '" class="artistindex" data-role="listview" data-filter="false">' . $NL;

    for ($i = 1; $i < $Index; $i++)
    {
	$str .= '<li class="artistindex">';
	$href = 'presets.php?firstpreset=' . $FP[$i] . '&count=' . $CNT[$i];
	$str .= '<a href="' . $href . '">';
	$str .= strtoupper($manifest->PresetArtistSkip[$FP[$i]][0]) . '</a>';

	$str .= '<span class="ui-li-count">' . $CNT[$i] .'</span>';
	$str .= "</li>" . $NL;
        $tmp = MakeOnePreset($manifest, $FP[$i], $CNT[$i]);
    }

    $str .= "</ul>";

    return $str;
}

function MakeArtistIndexAlphabet(&$manifest, $id, $Category)
{
    global $SQ;
    global $NL;

    // Keep artist after first letter on same page
    //
    $ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    $c = 1;

    $end = $manifest->CategoryFirstPreset[$Category] + $manifest->GetCategoryCount($Category);

    $fp = $manifest->CategoryFirstPreset[$Category]; 
    $FP = array();
    $CNT = array();
    $Index = 1;
    $FP[0] = -1;
    $CNT[0] = 0;
    while ($fp < $end)
    {
	$cnt = $c;
	if ($fp + $c > $end)
	    $cnt = $end - $fp;
	else
	{
	    $tmp = $cnt;
	    if (is_numeric($manifest->PresetArtistSkip[$fp+$tmp-1][0]))
	    {
		while (is_numeric($manifest->PresetArtistSkip[$fp+$cnt][0]) && $fp + $cnt < $end)
		    $cnt++;
	    }
	    else
	    {
		while (strcmp(strtoupper($manifest->PresetArtistSkip[$fp+$tmp-1][0]), strtoupper($manifest->PresetArtistSkip[$fp+$cnt][0])) == 0 && $fp + $cnt < $end)
		    $cnt++;
	    }
	}

	$FP[$Index] = $fp;
	$CNT[$Index] = $cnt;
	$Index = $Index + 1;

	$fp += $cnt;
    }
    $FP[$Index] = -1;
    $CNT[$Index] = 0;

    $str = '<div class="ui-grid-c">' . $NL;

    $class = "ui-block-a";

    $start = 1;
    if (is_numeric($manifest->PresetArtistSkip[$FP[1]][0]))
	$start = 2;
    $alpha = 0;
    for ($i = $start; $i < $Index; $i++)
    {
	$letter = strtoupper($manifest->PresetArtistSkip[$FP[$i]][0]);

	while ($alpha < strlen($ALPHABET) && strcmp($ALPHABET[$alpha], $letter) < 0)
	{
	    $class .= " ui-disabled";
	    $str .= '<div class="' . $class . '">';
	    $href = '#';
	    $str .= '<a href="' . $href . '" data-role="button">';
	    $str .= strtoupper($ALPHABET[$alpha]);
	    $str .= '</a>';
	    $str .= "</div>" . $NL;
	    $class = "ui-block-b";
	    $alpha++;
	}

	$str .= '<div class="' . $class . '">';
	$href = 'presets.php?firstpreset=' . $FP[$i] . '&count=' . $CNT[$i];
	$str .= '<a href="' . $href . '" data-role="button">';
	if (is_numeric($letter))
	    $str .= '#';
	else
	    $str .= strtoupper($letter);
	$str .= '</a>';

	$str .= "</div>" . $NL;
        $tmp = MakeOnePreset($manifest, $FP[$i], $CNT[$i]);
	$class = "ui-block-b";
	$alpha++;
    }

    while ($alpha < strlen($ALPHABET))
    {
	$class .= " ui-disabled";
	$str .= '<div class="' . $class . '">';
	$href = '#';
	$str .= '<a href="' . $href . '" data-role="button">';
	$str .= strtoupper($ALPHABET[$alpha]);
	$str .= '</a>';
	$str .= "</div>" . $NL;
	$class = "ui-block-b";
	$alpha++;
    }
    if ($start == 2)
    {
	$class = "ui-block-b";
	$str .= '<div class="' . $class . '">';
	$href = 'presets.php?firstpreset=' . $FP[1] . '&count=' . $CNT[1];
	$str .= '<a href="' . $href . '" data-role="button">';
	$str .= '#';
	$str .= '</a>';
	$str .= "</div>" . $NL;
	$class = "ui-block-b";
	$alpha++;
    }

    $str .= "</div>" . $NL;

    return $str;
}

function MakePageCategories($manifest)
{
    global $CACHE_DIR;
    global $SQ;
    global $NL;


    foreach ($manifest->Category as $cat => $catName) 
    {
	if (strpos($catName, " / Album") > 3 && $manifest->GetCategoryCount($cat) > 15)
	{
	    $str = Page("page_cat-" . $cat, "Artist Index",
		MakeArtistIndexAlphabet($manifest, "artistindex", $cat),
		"LinnDS-jukebox", "true");
	}
	else
	{
	    $str = Page("page_cat-" . $cat, $catName, 
		MakePresetList($manifest, "presets-" . $cat, "#page-cat-" . $cat,
		    $manifest->CategoryFirstPreset[$cat], 
		    min(21, $manifest->GetCategoryCount($cat))), 
		"LinnDS-jukebox", "false");
	}
	$cachefile = $CACHE_DIR . "/pagecategory-" . $cat;
	file_put_contents($cachefile, $str);
    }
}

function MakeOnePreset($manifest, $FirstPreset, $PresetCount)
{
    global $CACHE_DIR;
    global $SQ;
    global $NL;


    $id = "presets-" . $FirstPreset . "-" . $PresetCount;
    $str .= Page("page_presets-" . $FirstPreset . "-" . $PresetCount, "Artist / Album", 
	MakePresetList($manifest, $id, "presets.php", $FirstPreset, $PresetCount),
	"LinnDS-jukebox", "false");

    $cachefilename = $CACHE_DIR . "/presets-" . $FirstPreset . "-" . $PresetCount;
    file_put_contents($cachefilename, $str);
    return $str;
}

?>
