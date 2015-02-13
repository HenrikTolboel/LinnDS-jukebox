/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2013 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

$(function() {

    // These variables contains information regarding the actual navigation
    // through pages.
    var SubMenuData = "";
    var AlbumListData = "";
    var PlayData = "";

    // This one is called when clicking to open a playpopup.
    $('body').delegate("a.playpopup", "click", function() {
	var id = $(this).attr('id');
	var preset = $(this).data("musik").preset;
	var track  = $(this).data("musik").track;
	if (track === undefined) track = 0;
	var popupid = $(this).data("musik").popupid;
	PlayData = new Object();
	PlayData.preset = preset;
	PlayData.track = track;
	PlayData.popupid = popupid;
	console.log("a.playpopup: " + id + ", " + preset + ", " + track + ", " + popupid);
	//$("#" + popupid).data("musik", {preset: preset, popupid: popupid, track: track }); 
	$("#" + popupid).popup('open', {positionTo: "#" + id } );
	return true;
    });

    // This one is called when an entry in the playpopup is clicked
    $('body').delegate("a.playpopupclick", "click", function() {
	var volume = $(this).data("musik").volume;
	var action = $(this).data("musik").action;
	//var t = $(this).closest("div.playpopup");
	//var preset = t.data("musik").preset;
	//var track  = t.data("musik").track;
	//if (track === undefined) track = 0;
	//var popupid = t.data("musik").popupid;
	console.log("a.playpopupclick: " + action + " = " + PlayData.preset + ", " + PlayData.track + ", " + volume);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, volume: volume, preset: PlayData.preset, track: PlayData.track } , function (data) {
	    //alert('Load OK' + data);
	    });
	}
	$("#" + PlayData.popupid).popup('close');
	return true;
    });

    // Click a button in single album list
    $('body').delegate("button.albumclick", "click", function() {
	var action = $(this).data("musik").action;
	var preset = PlayData.preset;
	var track  = 0;
	console.log("button.albumclick: " + action + " = " + preset + ", " + track);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, preset: preset, track: track } , function (data) {
		//alert('Load OK' + data);
	    });
	}
	return true;
    });
    
    // This one is called when an entry in the playpopup is clicked
    $('body').delegate("a.playpopupclick_old", "click", function() {
	var volume = $(this).data("musik").volume;
	var action = $(this).data("musik").action;
	var t = $(this).closest("div.playpopup");
	var preset = t.data("musik").preset;
	var track  = t.data("musik").track;
	if (track === undefined) track = 0;
	var popupid = t.data("musik").popupid;
	console.log("a.playpopupclick: " + action + " = " + preset + ", " + track + ", " + volume);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, volume: volume, preset: preset, track: track } , function (data) {
	    //alert('Load OK' + data);
	    });
	}
	$("#" + popupid).popup('close');
	return true;
    });

    // This one is called when an entry in the mainmenu is clicked
    $('body').delegate("a.menuclick", "click", function() {
	var menu = $(this).data("musik").menu;
	var type = $(this).data("musik").type;
	var title = $(this).data("musik").title;
	var html = "";
	SubMenuData = $(this).data("musik");
	var ul = $(this).closest("ul");
	console.log("a.menuclick: menu = " + menu + ", type = " + type + ", title = " + title);
	
	if (type == "alphabet") {
	    $("#alphabet-title").html(title);
	    //$("#alphabet-A").addClass("ui-disabled");
	    //$("#alphabet-F").addClass("ui-disabled");
	    //$("#alphabet-G").removeClass("ui-disabled");

	    jQuery.getJSON("QueryAlphabetPresent.php", { menu: SubMenuData.menu }, function ( response ) {                 
		$.each( response, function ( i, val ) {
		    if (val == 1)
			$("#alphabet-"  + i).removeClass("ui-disabled");
		    else
			$("#alphabet-"  + i).addClass("ui-disabled");
		});
	    });
	    $("body").pagecontainer("change", "#alphabet");
	}
	else
	{
	    AlbumListData = new Object();
	    if (type == "newest") {
		AlbumListData.ArtistFirst = "newest";
	    }
	    else
	    {
		AlbumListData.ArtistFirst = "*";
	    }
	    
	    $("#albumlist-title").html(SubMenuData.title);
	    jQuery.getJSON("QueryAlbumList.php", { menu: SubMenuData.menu, artistfirst: AlbumListData.ArtistFirst }, function ( response ) {                 
		$.each( response, function ( i, val ) {
		    html += AlbumListEntry("albumlist", val.Preset, val);
		});
		$("#albumlist-list").html( html );
		$("#albumlist-list").listview( "refresh" );
		//$("#albumlist-list").trigger( "updatelayout");
	    });
	    $("body").pagecontainer("change", "#albumlist");
	}
	return true;
    });

    // This one is called when an entry in the alphabetmenu is clicked
    $('body').delegate("a.alphabetclick", "click", function() {
	var html = "";
	AlbumListData = new Object();
	AlbumListData.ArtistFirst = $(this).html();
	console.log("a.alphabetclick: menu: " + SubMenuData.menu + ", type = " + SubMenuData.type + ", artistfirst = " + AlbumListData.ArtistFirst);
	
	$("#albumlist-title").html(SubMenuData.title + " - " + AlbumListData.ArtistFirst);
	jQuery.getJSON("QueryAlbumList.php", { menu: SubMenuData.menu, artistfirst: AlbumListData.ArtistFirst }, function ( response ) {                 
	    $.each( response, function ( i, val ) {
		html += AlbumListEntry("albumlist", val.Preset, val);
	    });
	    $("#albumlist-list").html( html );
	    $("#albumlist-list").listview( "refresh" );
	    //$("#albumlist-list").trigger( "updatelayout");
	});
	$("body").pagecontainer("change", "#albumlist");
	return true;
    });

    // This one is called when an entry in the albummenu is clicked
    $('body').delegate("a.showalbumclick", "click", function() {
	PlayData = new Object();
	PlayData.preset = $(this).data("musik").preset;
	var html = "";
	var first = true;
	console.log("a.showalbumclick: menu: " + SubMenuData.menu + ", type = " + SubMenuData.type + ", preset = " + PlayData.preset);
	
	jQuery.getJSON("QueryAlbum.php", { preset: PlayData.preset }, function ( response ) {                 
	    $.each( response, function ( i, val ) {
		if (first) {
		    $("img.album").attr("src", AbsoluteURL(val.AlbumArt));
		    $("#album-title").html("Album");
		    $("#album-artist").html(val.ArtistPerformer);
		    $("#album-album").html(val.Album + " (" + val.Year + ")");

		    first = false;
		}
		html += AlbumEntry("album", val.Preset, val.TrackSeq, val);
	    });
	    $("#album-list").html( html );
	    $("#album-list").listview( "refresh" );
	    //$("#album-list").trigger( "updatelayout");
	});
	$("body").pagecontainer("change", "#album");
	return true;
    });





// Bind to the navigate event
//$( window ).on( "navigate", function() {
	//console.log( "navigated!" );
//});
$( window ).on( "navigate", function( event, data ) {
		console.log( "Navigate: " + data.state );
		//console.log( data.state.info );
		//console.log( data.state.direction );
		//console.log( data.state.url );
		//console.log( data.state.hash );
		//if (false && data.state.info !== undefined) {
		    //console.log("Updating ul...");
		//var ul = $("main");
    //            ul.html( data.state.info );
    //            ul.listview( "refresh" );
    //            ul.trigger( "updatelayout");
		//}
	});
   

    // Click a button in left Kontrol panel
    $('body').delegate("button.panelclick", "click", function() {
	var volume = $(this).data("musik").volume;
	var action = $(this).data("musik").action;
	var preset = $(this).data("musik").preset;
	var track  = $(this).data("musik").track;
	if (track === undefined) track = 0;
	console.log("button.panelclick: " + action + " = " + preset + ", " + track + ", " + volume);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, volume: volume, preset: preset, track: track } , function (data) {
		//alert('Load OK' + data);
	    });
	}
	return true;
    });
    
    // Change Kontrol volume slider
    $(document).on("change", "input#volume", function() {
	var vol = $(this).val();
	console.log("volume = " + vol);
	jQuery.get("Send.php", { action: "SetVolume", volume: vol } , function (data) {
	    //alert('Load OK' + data);
	});
    });

    //$("img.onepreset").lazyload({placeholder : "webapp/tuupola-jquery_lazyload-3f123e9/img/grey.gif"});
});

// Query the device pixel ratio. 
//------------------------------- 
function getDevicePixelRatio() { 
   if(window.devicePixelRatio === undefined) 
      return 1; // No pixel ratio available. Assume 1:1. 
   return window.devicePixelRatio; 
};

function getStatus() {
    $.getJSON("Send.php", { action: "State", volume: 0, preset: 0 } , function (data) {
	//$('div#status').html(data.status);
	//$('div#lastupdate').html(data.lastupdate);
	var myslider = $('input.volume');
	//if (myslider.val() != data.Volume)
	//{
	    myslider.val(data.Volume);
	    myslider.attr('max', data.MAX_VOLUME);
	    myslider.slider('refresh');
	//}
    });
    setTimeout("getStatus()",10000);
}

function AbsoluteURL(Path)
{
    var LINN_JUKEBOX_URL = "http://192.168.0.179/MusicLib";

    return Path.replace("LINN_JUKEBOX_URL", LINN_JUKEBOX_URL);
}


function AlbumListEntry(id, preset, values) {
    //<li><a id="p0_D-187" class="playpopup" data-rel="popup" href="#" data-musik='{"popupid": "p0_D-popup", "preset": "187"}'>
    //<img class="sprite_187" src="Transparent.gif"/><h3>DAD</h3><p>Call Of The Wild (1986)</p></a>
    //<a href="album_187.html"></a></li>
    var html = "";

    html += '<li>';
    html += '<a id="' + id + '-' + preset + '" class="playpopup" data-rel="popup" href="#" data-musik=' + "'" + '{"popupid": "' + id + '-popup", "preset": "' + preset + '"}' + "'" + '>';
    html += '<img class="sprite_' + preset + '" src="Transparent.gif"/>';

    html += '<h3>';

    if (values.Artist == "Various")
    {
	html += values.Album;
	html += '</h3>';
	html += '<p>' + ' (' + values.Year + ')</p>';  
	html += '</a>';
    }
    else
    {
	html += values.Artist;
	html += '</h3>';
	html += '<p>' + values.Album + ' (' + values.Date + ')</p>';  
	html += '</a>';
    }

    html += '<a href="#" class="showalbumclick" data-musik=' + "'" + '{"preset": "' + preset + '"}' + "'" + '></a>';

    html += '</li>';

    return html;
}

function AlbumEntry(id, preset, trackseq, values) {
    //<li><a id ="album-194-1" href="#" class="playpopup" data-rel="popup" 
    //       data-musik='{"popupid": "album-popup", "preset": "194", "track": "1"}'>
    //       <h3>1. Revolution</h3><p>3:23</p></a></li>
    var html = "";

    html += '<li>';
    html += '<a id="' + id + '-' + preset + '-' + trackseq + '" class="playpopup" data-rel="popup" href="#" data-musik=' + "'" + '{"popupid": "' + id + '-popup", "preset": "' + preset + '", "track": "' + trackseq + '"}' + "'" + '>';

    html += '<h3>';

    html += values.TrackNumber + '. ';
    html += values.Title;
    html += '</h3>';
    html += '<p>' + values.Duration + '</p>';  
    html += '</a>';

    html += '</li>';

    return html;
}

function SearchAlbumEntry(id, preset, filtertext, values) {
    var html = "";

    html += '<li data-filtertext="' + filtertext + '">';
    html += '<a id="' + id + '-' + preset + '" class="playpopup" data-rel="popup" href="#" data-musik=' + "'" + '{"popupid": "' + id + '-popup", "preset": "' + preset + '"}' + "'" + '>';
    html += '<img class="sprite_' + preset + '" src="Transparent.gif"/>';

    html += '<h3>';

    if (values.ArtistPerformer == "Various")
    {
	html += values.Album;
	html += '</h3>';
	html += '<p>' + ' (' + values.Year + ')</p>';  
	html += '</a>';
    }
    else
    {
	html += values.ArtistPerformer;
	html += '</h3>';
	html += '<p>' + values.Album + ' (' + values.Year + ')</p>';  
	html += '</a>';
    }

    html += '<a href="album_' + preset + '.html"></a>';

    html += '</li>';

    return html;
}

function SearchTrackEntry(id, preset, trackseq, filtertext, values) {
    var html = "";
    html += '<li data-filtertext="' + filtertext + '">';
    html += '<a id ="' + id  + '-' + preset + '-' + trackseq + '" href="#" class="playpopup" data-rel="popup" data-musik=' + "'" + '{"popupid": "' + id + '-popup", "preset": "' + preset + '", "track": "' + trackseq + '"}' + "'" + '>';
    html += '<img class="sprite_' + preset + '" src="Transparent.gif"/>';
    html += '<h3>' + values.ArtistPerformer + ' - ' + values.Album + '</h3>';
    html += '<h3>' + values.TrackNumber + '. ' + values.Title + '</h3> ';
    html += '<p>' + values.Duration + '</p>';
    html += '</a>';
    html += '<a href="album_' + preset + '.html"></a>';
    html += '</li>';

    return html;
}


$( document ).on( "pagecreate", "#musik", function() {
    $( "#autocomplete" ).on( "filterablebeforefilter", function ( e, data ) {
	var $ul = $( this ),
            $input = $( data.input ),
            filtertext = $input.val(),
            html = "",
            id = "musik";
        $ul.html( "" );
        if ( filtertext && filtertext.length > 2 ) {
            $ul.html( "<li><div class='ui-loader'><span class='ui-icon ui-icon-loading'></span></div></li>" );
            $ul.listview( "refresh" );
            jQuery.getJSON("QueryDB.php", { action: filtertext }, function ( response ) {                 
		$.each( response, function ( i, val ) {
		    if (val.Type == "Album") {
			html += SearchAlbumEntry(id, val.Preset, filtertext, val);
		    }
		    else
		    {
			html += SearchTrackEntry(id, val.Preset, val.TrackSeq, filtertext, val);
		    }
                });
                $ul.html( html );
                $ul.listview( "refresh" );
//                $ul.trigger( "updatelayout");
            });
        }
    });
});
