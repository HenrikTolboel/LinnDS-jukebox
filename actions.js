/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2013 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

$(function() {

   // This one is called when clicking to open a playpopup.
   $('body').delegate("a.playpopup", "click", function() {
	var id = $(this).attr('id');
	var preset = $(this).data("musik").preset;
	var track  = $(this).data("musik").track;
	if (track === undefined) track = 0;
	var popupid = $(this).data("musik").popupid;
	console.log("a.playpopup: " + id + ", " + preset + ", " + track + ", " + popupid);
	$("#" + popupid).data("musik", {preset: preset, popupid: popupid, track: track }); 
	$("#" + popupid).popup('open', {positionTo: "#" + id } );
	return true;
   });

   // This one is called when an entry in the playpopup is clicked
   $('body').delegate("a.playpopupclick", "click", function() {
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

   /*
   $('body').delegate("a.dialogclick", "click", function() {
	var value = $(this).data("musik").preset;
	var action = $(this).data("musik").action;
	console.log("a.dialogclick: " + action + " = " + value);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, value: value } , function (data) {
		//alert('Load OK' + data);
	    });
	}
	//$('.ui-dialog').dialog('close');
	return true;
   });
   */

   /*
   $('body').delegate("a.popsource", "click", function() {
	var id = $(this).data("musik").id;
	console.log("a.popsource: " + id);
	$("#" + id).empty().append($('#popupSource').clone());
	$("#" + id).popup('open', {positionTo: "#" + id + "-pos" } );
	return true;
   });
   */

    //$('ul.presets').delegate("a.onepreset", "swipeleft", function() {
    /*
    $('.play,body,div.ui-dialog,ul.presets').delegate("a.onepreset", "swipeleft", function() {
            var preset = $(this).data("musik").preset;
            alert("swipeleft " +preset);
            return true;
            });
	    */

    /*
    $('div.ui-page').live("swipeleft", function(){
	alert("swipeleft ");
	var nextpage = $(this).next('div[data-role="page"]');
	// swipe using id of next page if exists
	if (nextpage.length > 0) {
	    $.mobile.changePage(nextpage, 'slide');
	}
    });
    $('div.ui-page').live("swiperight", function(){
	alert("swiperight ");
	var prevpage = $(this).prev('div[data-role="page"]');
	// swipe using id of next page if exists
	if (prevpage.length > 0) {
	    $.mobile.changePage(prevpage, 'slide', true);
	}
    });
    */

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
