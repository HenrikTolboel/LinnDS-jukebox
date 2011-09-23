<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/



function Page($id, $title, $content, $footer)
{
    $str = '<div data-role="page" data-dom-cache="true" id="' . $id . '">' . "\n";

    $str .= '<div data-role="header">' . "\n";
    $str .= '<h1>' . $title . '</h1>'. "\n";
    if ($id != "page_options" && $id != "dialog_play")
        $str .= '<a href="options.php" data-icon="gear" class="ui-btn-right">Options</a>' . "\n";
    $str .= '</div><!-- /header -->' ."\n";

    $str .= '<div data-role="content">' . "\n";
    $str .= $content;
    $str .= "\n" . '</div><!-- /content -->' ."\n";

    $str .= '<div data-role="footer">' . "\n";
    $str .= '<h4>' . $footer . "</h4>\n";
    $str .= "</div><!-- /footer -->\n";

    $str .= "</div><!-- /page -->\n\n";
    return $str;
}

function Dialog($id, $title, $content)
{
    $str = '<div data-role="page" id="' . $id . '">'. "\n";

    $str .= '<div data-role="header" data-position="inline">' . "\n";
    $str .= '<h1>' . $title . '</h1>' . "\n";
    $str .= '</div><!-- /header -->' ."\n";

    $str .= '<div data-role="content">' . "\n";
    $str .= $content;
    $str .= "\n" . '</div><!-- /content -->' ."\n";

    $str .= "</div><!-- /page -->\n\n";
    return $str;
}

function CategoryList($id, &$manifest)
{
    $str= '<ul id="' . $id . '" data-role="listview" data-filter="false">' . "\n";
    foreach ($manifest->Category as $cat => $catName) 
    {
        $str .= '<li><a href="pagecategory.php?category=' . $cat . '">' . $catName .'</a>';

        $str .= '<span class="ui-li-count">' . $manifest->GetCategoryCount($cat) .'</span>';
        $str .= '</li>' . "\n";
    }

    $str .= "</ul>";

    return $str;
}

function MakePresetList(&$manifest, $id, $FromPage, $FirstPreset, $PresetCount)
{
    $str = '<ul id="' . $id . '" class="presets" data-role="listview" data-filter="false">' . "\n";

    for ($i = $FirstPreset; 
    $i < $FirstPreset + $PresetCount;
    $i++)
    {
        $str .= '<li class="onepreset">';
        //$str .= '<a href="PlayDialog.php?preset=' . $i . "&frompage=" . $FromPage . "&firstpreset=" . $FirstPreset . "&count=" . $PresetCount . '" data-rel="dialog">';
        $str .= '<a href="PlayDialog.php?preset=' . $i . '" data-rel="dialog">';
        $str .= '<img class="onepreset" src="' . "../" . $manifest->PresetImage80x80[$i] . '" />';
        $str .= '<h3>';
        //$str .= '0' . $i . '<br />';
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
        //$str .= '<p class="ui-li-aside"><strong>0' . $i .'</strong></p>';
        $str .= '<a href="album.php?preset=' . $i . '"></a>';

        $str .= "</li>\n";
    }

    $str .= "</ul>";

    return $str;
}

function MakeArtistIndex1(&$manifest, $id, $Category)
{
    $str = '<ul id="' . $id . '" class="artistindex" data-role="listview" data-filter="false">' . "\n";

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

        $str .= "</li>\n";
    }

    $str .= "</ul>";

    return $str;
}

function MakeArtistIndex2(&$manifest, $id, $Category)
{
   // Keep same artist on a page
   //
   $str = '<ul id="' . $id . '" class="artistindex" data-role="listview" data-filter="false">' . "\n";

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

      $str .= "</li>\n";

      $fp += $cnt;
   }

   $str .= "</ul>";

   return $str;
}

function MakeArtistIndex3(&$manifest, $id, $Category)
{
   // Keep artist after first letter on same page
   //
   $str = '<ul id="' . $id . '" class="artistindex" data-role="listview" data-filter="false">' . "\n";

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
      //$str .= '<li class="artistindex">';
      //$str .= '<a href="presets.php?firstpreset=' . $fp . '&count=' . $cnt . '">';
      //$str .= strtoupper($manifest->PresetArtist[$fp][0]) . '</a>';

      //$str .= '<span class="ui-li-count">' . $cnt .'</span>';
      //$str .= "</li>\n";

      $fp += $cnt;
   }
    $FP[$Index] = -1;
    $CNT[$Index] = 0;

    for ($i = 1; $i < $Index; $i++)
    {
	$str .= '<li class="artistindex">';
	$str .= '<a href="presets.php?firstpreset=' . $FP[$i] . '&count=' . $CNT[$i];
	//$str .= '&prevfp=' . $FP[$i-1] . '&prevcnt=' . $CNT[$i-1];
	//$str .= '&nextfp=' . $FP[$i+1] . '&nextcnt=' . $CNT[$i+1];
	$str .= '">';
	$str .= strtoupper($manifest->PresetArtistSkip[$FP[$i]][0]) . '</a>';

	$str .= '<span class="ui-li-count">' . $CNT[$i] .'</span>';
	$str .= "</li>\n";
    }

    $str .= "</ul>";

    return $str;
}

?>
