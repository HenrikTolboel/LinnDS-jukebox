/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2013 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

$(function() {
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

   $('body').delegate("a.popupclick", "click", function() {
	var volume = $(this).data("musik").volume;
	var action = $(this).data("musik").action;
	var preset = $('#globals').data("musik").preset;
	var id = $('#globals').data("musik").id;
	console.log("a.popupclick: " + action + " = " + preset + ", " + volume);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, volume: volume, preset: preset } , function (data) {
		//alert('Load OK' + data);
	    });
	}
	$("#" + id).popup('close');
	return true;
   });

   $('body').delegate("button.panelclick", "click", function() {
	var volume = $(this).data("musik").volume;
	var action = $(this).data("musik").action;
	var preset = $('#globals').data("musik").preset;
	var id = $('#globals').data("musik").id;
	console.log("button.panelclick: " + action + " = " + preset + ", " + volume);
	if (action != "Cancel") {
	    jQuery.get("Send.php", { action: action, volume: volume, preset: preset } , function (data) {
		//alert('Load OK' + data);
	    });
	}
	//$("#" + id).popup('close');
	return true;
   });

   $('body').delegate("a.pop", "click", function() {
	var preset = $(this).data("musik").preset;
	var id = $(this).data("musik").id;
	console.log("a.pop: " + id + " = " + preset);
	$('#globals').data("musik", {preset: preset, id: id }); 
	var t = $('#play-popup').clone();
	$("#" + id).empty().append(t);
	$("#" + id).popup('open', {positionTo: "#" + preset } );
	return true;
   });

   $('body').delegate("a.popsource", "click", function() {
	var id = $(this).data("musik").id;
	console.log("a.popsource: " + id);
	$("#" + id).empty().append($('#popupSource').clone());
	$("#" + id).popup('open', {positionTo: "#" + id + "-pos" } );
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
	var myslider = $('#volume');
	if (myslider.val() != data.Volume)
	{
	    myslider.val(data.Volume);
	    myslider.attr('max', data.MAX_VOLUME);
	    myslider.slider('refresh');
	}
    });
    setTimeout("getStatus()",10000);
}
