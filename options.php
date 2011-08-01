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
   require_once("Functions.php");

   $cachefilename = $CACHE_DIR . "/options";

   if ($DO_CACHE && file_exists($cachefilename) && filemtime($cachefilename) > filemtime($manifestfile))
   {
       echo file_get_contents($cachefilename);
   }
   else
   {

      $cont = "";

      $cont .= "<h2>Options</h2>"; 
      $cont .= '<p><form action="form.php" method="post"><div data-role="fieldcontain"><label for="volume">Volume:</label><input type="range" name="volume" id="volume" value="35" min="0" max="60"  /></form></div></p>';


      $str = Page("page_options", "Options", $cont, "Page Footer");

      file_put_contents($cachefilename, $str);
      echo $str;
   }

   require("footer.inc");
?>
