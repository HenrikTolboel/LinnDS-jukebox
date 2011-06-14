/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

<?php
   class Manifest
   {
       public $Category = array();
       public $CategoryFirstPreset = array();
       public $PresetImage = array();
       public $PresetImage80x80 = array();
       public $PresetArtist = array();
       public $PresetAlbum = array();
       public $PresetYear = array();
       public $PresetCategory = array();
       public $ImageBase;
       public $NumberOfPresets = 0;

       private $LastCategory = 0;

       public function __construct($ManifestFile)
       {
           $CurCategory = 0;
           $xml = simplexml_load_file($ManifestFile);

           foreach ($xml->children('urn:linn-co-uk/jukebox') as $child) { // -> ImageBase, Bookmark or Preset
               if ($child->getName() == "ImageBase") {
                   $this->ImageBase = $child;
               }
               else if ($child->getName() == "Bookmark") {
                   $b = $child->Name[0];
                   $n = (int)$child->Number[0];
                   $CurCategory = $CurCategory +1;
                   $this->Category[$CurCategory] = $b;
                   $this->CategoryFirstPreset[$CurCategory] = $n;
               }
               else
               {
                   $a = $child->Name[0];
                   $i1 = strpos($a, "/");
                   $i2 = strpos($a, "/", $i1+1);
                   if ($i2 === false)
                   {
                       $artist = "Various";
                       $year = substr($a, 0, $i1);
                       $album = substr($a, $i1+1);
                   }
                   else
                   {
                       $artist = substr($a, 0, $i1);
                       $year = substr($a, $i1+1, $i2-$i1-1);
                       $album = substr($a, $i2+1);
                   }

                   $this->NumberOfPresets = (int)$child->Number[0];
                   $image = $child->Image[0];

                   if (strlen($image) > 4) {
                       $t = strpos($image, ".105/musik/");
                       if ($t === false)
                       {
                           $url = $image;
                       }
                       else
                       {
                           $url= substr($image, 11);
                       }

                       $dir = dirname($url);
                       $filename = basename($url);
                       $url = $dir . "/80x80/" . $filename;
                   }
                   else
                   {
                       $url = "#";
                   }

                   $this->PresetImage[$this->NumberOfPresets] = $image;
                   $this->PresetImage80x80[$this->NumberOfPresets] = $url;
                   $this->PresetArtist[$this->NumberOfPresets] = $artist;
                   $this->PresetAlbum[$this->NumberOfPresets] = $album;
                   $this->PresetYear[$this->NumberOfPresets] = $year;
                   $this->PresetCategory[$this->NumberOfPresets] = $CurCategory;

               }
           }
           $this->LastCategory = $CurCategory;
       }

       public function GetCategoryCount($Category)
       {
           $c = 0;
           if ($Category < $this->LastCategory)
           {
               $c = $this->CategoryFirstPreset[$Category + 1] - $this->CategoryFirstPreset[$Category];
           }
           else
           {
               $c = $this->NumberOfPresets - $this->CategoryFirstPreset[$Category] + 1;
           }
           return $c;
       }
   }

   //$manifest = new Manifest("_Presets/manifest.xml");
?>
