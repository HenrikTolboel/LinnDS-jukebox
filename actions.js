/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2012 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

$(function() {
   $('body').delegate("a.onepreset", "click", function() {
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
	var value = $(this).data("musik").preset;
	var action = $(this).data("musik").action;
	console.log(action + " = " + value);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, value: value } , function (data) {
		//alert('Load OK' + data);
	    });
	}
	$('.ui-popup').popup('close');
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

