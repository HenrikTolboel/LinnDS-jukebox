<!DOCTYPE html>
<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2015 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");
require_once("MusicDB.php");
require_once("html_parts.php");

$musicDB = new MusicDB();
?>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1">

	<title>LinnDS-jukebox</title> 

<link rel="stylesheet" href="../resources/jquery.mobile-1.4.5.min.css" />
<link rel="stylesheet" href="sprites/sprites.css" />
<link rel="stylesheet" href="sprites/sprites@2x.css" media="(-webkit-min-device-pixel-ratio: 2)"/>
<script src="../resources/jquery-1.11.2.min.js"></script>
<link rel="stylesheet" href="musik.css" />
<script src="actions.js"></script>
<script>
    $(document).bind("mobileinit", function(){
	    //$.extend($.mobile, {
		    //defaultPageTransition: 'none',
		    //defaultDialogTransition: 'none'
	    //});
    });
    //getStatus();

</script>
<script src="../resources/jquery.mobile-1.4.5.min.js"></script>

</head>
<body>
<div data-role="page" data-dom-cache="true" id="musik">
    <div data-role="header" data-position="fixed">
	<h1>Musik</h1>
<?php
echo KontrolPanel_button("musik");
echo QueuePanel_button("musik");
?>

    </div><!-- /header -->

    <div data-role="content">
	<form class="ui-filterable">
	    <input id="autocomplete-input" data-type="search" placeholder="Søg...">
	</form>
	<ul id="autocomplete" data-role="listview" data-inset="true" data-filter="true" data-input="#autocomplete-input"></ul>

<?php
echo MainMenuHtml($musicDB);
echo playpopup_popup("musik");
?>

    </div><!-- /content -->

    <div data-role="footer">
	<h4>LinnDS-jukebox</h4>
    </div><!-- /footer -->


<?php
echo KontrolPanel_panel($musicDB, "musik");
?>

</div><!-- /page -->

<div data-role="page" data-dom-cache="false" id="alphabet">
    <div data-role="header" data-position="fixed">
	<h1 id="alphabet-title">Alphabet</h1>
<?php
echo KontrolPanel_button("alphabet");
echo QueuePanel_button("alphabet");
?>

    </div><!-- /header -->

    <div data-role="content">
<?php
echo AlphabetList("alphabet");
?>
    </div><!-- /content -->

    <div data-role="footer">
	<h4>LinnDS-jukebox</h4>
    </div><!-- /footer -->


<?php
echo KontrolPanel_panel($musicDB, "alphabet");
?>

</div><!-- /page -->

<div data-role="page" data-dom-cache="false" id="albumlist">
    <div data-role="header" data-position="fixed">
	<h1 id="albumlist-title">Kunstner / Album - A</h1>
<?php
echo KontrolPanel_button("albumlist");
echo QueuePanel_button("albumlist");
?>

    </div><!-- /header -->

    <div data-role="content">
	<ul id="albumlist-list" data-role="listview" data-filter="false">
<!--
	    <li><a id="albumlist-1" class="playpopup" data-rel="popup" href="#" data-musik='{"popupid": "albumlist-popup", "preset": "1"}'><img class="sprite_1" src="Transparent.gif"/><h3>Artist</h3><p>Album (Year)</p></a><a href="#"></a></li>
-->
	</ul>

<?php
echo playpopup_popup("albumlist");
?>

    </div><!-- /content -->

    <div data-role="footer">
	<h4>LinnDS-jukebox</h4>
    </div><!-- /footer -->


<?php
echo KontrolPanel_panel($musicDB, "albumlist");
?>

</div><!-- /page -->


<div data-role="page" data-dom-cache="false" id="album">
    <div data-role="header" data-position="fixed">
	<h1 id="album-title">Album</h1>
<?php
echo KontrolPanel_button("album");
echo QueuePanel_button("album");
?>

    </div><!-- /header -->

    <div data-role="content">
	<div class="ui-grid-a">
	<div class="ui-block-a"><div class="ui-bar">
	<img class="album" style="width: 100%;" src="Transparent.gif" />
	</div></div>
	<div class="ui-block-b"><div class="ui-bar">
	<button href="#" class="albumclick" data-mini="false" data-musik='{"action": "PlayNow", "preset": "194"}'>Play Now</button>
	<button href="#" class="albumclick" data-mini="false" data-musik='{"action": "PlayNext", "preset": "194"}'>Play Next</button>
	<button href="#" class="albumclick" data-mini="false" data-musik='{"action": "PlayLater", "preset": "194"}'>Play Later</button>
	</div></div>
	</div><!-- /grid-a -->
	<h3 id="album-artist">Artist</h3>
	<p id="album-album">Album (Year)</p>
	<ul id="album-list" data-role="listview" data-inset="true" data-filter="false">
<!--
	<li><a id ="album-1" href="#" class="playpopup" data-rel="popup" data-musik='{"popupid": "album-popup", "preset": "194", "track": "1"}'><h3>1. Title</h3><p>Duration</p></a></li>
	<li><a id ="album-2" href="#" class="playpopup" data-rel="popup" data-musik='{"popupid": "album-popup", "preset": "194", "track": "2"}'><h3>2. Title</h3><p>Duration</p></a></li>
-->
	</ul>

<?php
echo playpopup_popup("album");
?>

    </div><!-- /content -->

    <div data-role="footer">
	<h4>LinnDS-jukebox</h4>
    </div><!-- /footer -->


<?php
echo KontrolPanel_panel($musicDB, "album");
?>

</div><!-- /page -->


<div data-role="page" data-dom-cache="false" id="queue">
    <div data-role="header" data-position="fixed">
	<h1 id="queue-title">Queue</h1>
<?php
echo KontrolPanel_button("queue");
echo HomePanel_button("queue");
?>

    </div><!-- /header -->

    <div data-role="content">
	<ul id="queue-list" data-role="listview" data-filter="false">
<!--
	<li><a id ="album-1" href="#" class="playpopup" data-rel="popup" data-musik='{"popupid": "queue-popup", "preset": "194", "track": "1"}'><h3>1. Title</h3><p>Duration</p></a></li>
	<li><a id ="album-2" href="#" class="playpopup" data-rel="popup" data-musik='{"popupid": "queue-popup", "preset": "194", "track": "2"}'><h3>2. Title</h3><p>Duration</p></a></li>
-->
	</ul>

<?php
echo queuepopup_popup("queue");
?>

    </div><!-- /content -->

    <div data-role="footer">
	<h4>LinnDS-jukebox</h4>
    </div><!-- /footer -->


<?php
echo KontrolPanel_panel($musicDB, "queue");

$musicDB->close();
?>

</div><!-- /page -->


</body>
</html>
