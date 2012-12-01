/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2012 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

$(function() {
   $('body').delegate("a.dialogclick", "click", function() {
	var value = $(this).data("musik").preset;
	var action = $(this).data("musik").action;
	console.log(action + " = " + value);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, value: value } , function (data) {
		//alert('Load OK' + data);
	    });
	}
	//$('.ui-dialog').dialog('close');
	return true;
   });

   $('body').delegate("a.popupclick", "click", function() {
	var volume = $(this).data("musik").volume;
	var action = $(this).data("musik").action;
	var preset = $('#globals').data("musik").preset;
	console.log(action + " = " + preset + ", " + volume);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, volume: volume, preset: preset } , function (data) {
		//alert('Load OK' + data);
	    });
	}
	$('.ui-popup').popup('close');
	return true;
   });

   $('body').delegate("a.pop", "click", function() {
	var preset = $(this).data("musik").preset;
	var id = $(this).data("musik").id; // id of the popup menu to calls
	console.log(id + " = " + preset);
	$('#globals').data("musik", {preset: preset, last: id }); 
	$(id).html($('#play-popup'));
	//var t = $('#globals').data("musik");
	$(id).popup('open', {positionTo: "#" + preset } );
	return true;
   });

   $('body').delegate("a.popsource", "click", function() {
	var id = $(this).data("musik").id; // id of the popup menu to calls
	$(id).html($('#popupSource'));
	$(id).popup('open');
	return true;
   });

   $('body').delegate("a.popcontrol", "click", function() {
	var id = $(this).data("musik").id; // id of the popup menu to calls
	$(id).html($('#popupControl'));
	$(id).popup('open');
	return true;
   });

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
    
   $("input#volume").live("change", function() {
      console.log("volume = " + $(this).val());
      jQuery.get("Send.php", { action: "SetVolume", value: $(this).val() } , function (data) {
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

