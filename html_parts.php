<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2012-2015 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");
require_once("MusicDB.php");

function MainMenuHtml($musicDB)
{
    global $RootMenu;
    global $SubMenuType;
    global $NL;

    static $Str = "";

    if (strlen($Str) > 0)
	return $Str;

    $Str .= '<ul id="main" data-role="listview" data-filter="false">' . $NL;

    foreach ($RootMenu as $No => $Title) {
	if ($SubMenuType[$No] != SUBMENU_TYPE_NEWEST)
	    $Str .= '    <li><a href="#" class="menuclick" data-musik=' . "'" . '{"menu": "' . $No . '", "type": "' . SubMenuType2Str($SubMenuType[$No]) . '", "title": "' . $Title . '"}' . "'>" . $Title . '<span class="ui-li-count">' . $musicDB->NumberOfAlbumsInMenuNo($No) . '</span></a></li>' . $NL;
	else
	    $Str .= '    <li><a href="#" class="menuclick" data-musik=' . "'" . '{"menu": "' . $No . '", "type": "' . SubMenuType2Str($SubMenuType[$No]) . '", "title": "' . $Title . '"}' . "'>" . $Title . '</a></li>' . $NL;
    }
    $Str .= '</ul>' . $NL;

    return $Str;
}

function playpopup_popup($id)
{
    global $NL;
    $html = <<<EOT
	<div class="playpopup" data-role="popup" id="$id-popup" data-history="false">
	    <ul data-role="listview" data-inset="true" style="min-width:180px;">
		<li><a href="#" class="playpopupclick" data-musik='{"action": "PlayNow"}'>Play Now</a></li>
		<li><a href="#" class="playpopupclick" data-musik='{"action": "PlayNext"}'>Play Next</a></li>
		<li><a href="#" class="playpopupclick" data-musik='{"action": "PlayLater"}'>Play Later</a></li>
		<li><a href="#" class="playpopupclick" data-musik='{"action": "Cancel"}'>Cancel</a></li>
	    </ul>
	</div><!-- /popup -->
EOT;

    return $html . $NL;
}

function queuepopup_popup($id)
{
    global $NL;
    $html = <<<EOT
	<div class="queuepopup" data-role="popup" id="$id-popup" data-history="false">
	    <ul data-role="listview" data-inset="true" style="min-width:180px;">
		<li><a href="#" class="queuepopupclick" data-musik='{"action": "QueueDelete"}'>Delete</a></li>
		<li><a href="#" class="queuepopupclick" data-musik='{"action": "QueueJumpTo"}'>Jump To</a></li>
		<li><a href="#" class="queuepopupclick" data-musik='{"action": "QueueJumpToNow"}'>Jump Now</a></li>
		<li><a href="#" class="queuepopupclick" data-musik='{"action": "QueueMoveUp"}'>Move Up</a></li>
		<li><a href="#" class="queuepopupclick" data-musik='{"action": "QueueMoveDown"}'>Move Down</a></li>
		<li><a href="#" class="queuepopupclick" data-musik='{"action": "Cancel"}'>Cancel</a></li>
	    </ul>
	</div><!-- /popup -->
EOT;

    return $html . $NL;
}

function KontrolPanel_button($id)
{
    global $NL;
    $html = <<<EOT
	<a id="$id-KontrolPanel" class="ui-btn-left" href="#$id-KontrolPanelPanel" data-icon="bars">Kontrol</a>
EOT;

    return $html . $NL;
}

function KontrolPanel_panel($musicDB, $id)
{
    global $NL;
    $Cnt = $musicDB->NumberOfAlbumsInMenuNo(0);
    $html = <<<EOT
    <div data-role="panel" id="$id-KontrolPanelPanel" data-position="left" data-position-fixed="true">
	<ul data-role="listview" data-theme="a" data-divider-theme="a" style="margin-top:-16px;margin-bottom:16px;" class="nav-search">
	    <li data-icon="delete" style="background-color:#111;">
		<a href="#" data-rel="close">Close</a>
	    </li>
	</ul>
	<h4>Volume</h4>
	<div data-role="controlgroup" data-type="horizontal">
	    <button href="#" class="panelclick" data-mini="true" data-musik='{"action": "DecrVolume5"}'>-5</button>
	    <button href="#" class="panelclick" data-mini="true" data-musik='{"action": "DecrVolume"}'>-1</button>
	    <button href="#" class="panelclick" data-mini="true" data-musik='{"action": "ResetVolume"}'>0</button>
	    <button href="#" class="panelclick" data-mini="true" data-musik='{"action": "IncrVolume"}'>+1</button>
	    <button href="#" class="panelclick" data-mini="true" data-musik='{"action": "IncrVolume5"}'>+5</button>
	</div>
	<h4>Playlist Kontrol</h4>
	<div data-role="controlgroup" data-type="horizontal">
	    <button href="#" class="panelclick" data-mini="true" data-musik='{"action": "Control-Play"}'>Play</button>
	    <button href="#" class="panelclick" data-mini="true" data-musik='{"action": "Control-Pause"}'>Pause</button>
	    <button href="#" class="panelclick" data-mini="true" data-musik='{"action": "Control-Stop"}'>Stop</button>
	</div>
	<div data-role="controlgroup" data-type="horizontal">
	    <button href="#" class="panelclick" data-mini="true" data-musik='{"action": "Control-Previous"}'>Previous</button>
	    <button href="#" class="panelclick" data-mini="true" data-musik='{"action": "Control-Next"}'>Next</button>
	</div>
	<button href="#" class="panelclick" data-mini="true" data-musik='{"action": "PlayRandomTracks", "preset": "1", "track": "$Cnt"}'>Add 50 random tracks</button>
	<h4>Source</h4>
	<button href="#" class="panelclick" data-mini="true" data-musik='{"action": "Source-Playlist"}'>Playlist</button>
	<button href="#" class="panelclick" data-mini="true" data-musik='{"action": "Source-TV"}'>TV</button>
	<button href="#" class="panelclick" data-mini="true" data-musik='{"action": "Source-Radio"}'>Radio</button>
	<button href="#" class="panelclick" data-mini="true" data-musik='{"action": "Source-NetAux"}'>AirPlay</button>
	<button href="#" class="panelclick" data-mini="true" data-musik='{"action": "Source-Off"}'>Off</button>
    </div><!-- /panel -->
EOT;

    return $html . $NL;
}

function QueuePanel_button($id)
{
    global $NL;
    $html = <<<EOT
	<a id="$id-QueuePanel" class="queueclick ui-btn-right" href="#queue" data-icon="bars">Queue</a>
EOT;

    return $html . $NL;
}

function HomePanel_button($id)
{
    global $NL;
    $html = <<<EOT
	<a id="$id-HomePanel" class="queueclick ui-btn-right" href="#musik" data-icon="home">Home</a>
EOT;

    return $html . $NL;
}

function AlphabetList($id)
{
    global $ALPHABET;
    global $ALPHABET_SIZE;
    global $NL;

    $space = "    ";

    $html = $space . $space . '<div class="ui-grid-c">' . $NL;
    $class = "ui-block-a";
    for ($alpha = 0; $alpha < $ALPHABET_SIZE; $alpha++)
    {
	$Letter = $ALPHABET[$alpha];
	$LetterId = $Letter;

	if ($Letter == "#")
	    $LetterId = "NUM";

	$html .= $space . $space . $space . '<div id="' . $id . '-' . $LetterId . '" class="' . $class . '"><a href="#" class="alphabetclick" data-role="button">' . $Letter . '</a></div>' . $NL;

	$class = "ui-block-b";
    }
    $html .= $space . $space . '</div>' . $NL;
    return $html;
}


?>
