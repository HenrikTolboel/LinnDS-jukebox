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
	var id = $('#globals').data("musik").id;
	console.log(action + " = " + preset + ", " + volume);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, volume: volume, preset: preset } , function (data) {
		//alert('Load OK' + data);
	    });
	}
	$("#" + id).popup('close');
	return true;
   });

   $('body').delegate("a.pop", "click", function() {
	var preset = $(this).data("musik").preset;
	var id = $(this).data("musik").id;
	console.log(id + " = " + preset);
	$('#globals').data("musik", {preset: preset, id: id }); 
	var t = $('#play-popup').clone();
	$("#" + id).empty().append(t);
	$("#" + id).popup('open', {positionTo: "#" + preset } );
	return true;
   });

   $('body').delegate("a.popsource", "click", function() {
	var id = $(this).data("musik").id;
	$("#" + id).empty().append($('#popupSource').clone());
	$("#" + id).popup('open', {positionTo: "#" + id + "-pos" } );
	return true;
   });

   $('body').delegate("a.popcontrol", "click", function() {
	var id = $(this).data("musik").id;
	var t = $('#popupControl').clone();
	$("#" + id).empty().append(t);
	$("#" + id).popup('open', {positionTo: "#" + id + "-pos" } );
	return true;
   });

   $('body').delegate("a.poppanel", "click", function() {
	var id = $(this).data("musik").id;
	var t = $('#popupPanel').clone();
	$("#" + id).empty().append(t);
	var h = $( window ).height();

	$("#" + id).css( "height", h );
	$("#" + id).popup('open' );
	return true;
   });

    $( ".popupPanel" ).on({
	popupbeforeposition: function() {
	    var h = $( window ).height();

	    $( ".popupPanel" ).css( "height", h );
	}
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

