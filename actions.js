/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

$(function() {
    $('.play,body,div.ui-dialog,ul.presets').delegate("a.onepreset", "click", function() {
            var preset = $(this).data("musik").preset;
            //alert("click " +preset);
            //jQuery.get("http://192.168.0.105/cgi-bin/musik.cgi/=/jukebox/play_now/"+preset);
            jQuery.get("PlayNow.php", { preset: preset } , function (data) {
                //alert('Load OK' + data);
            });
            //$('.ui-dialog').dialog('close');
            return true;
            });

    /*
    $('a.onepreset').live("click", function() {
            var preset = $(this).data("musik").preset;
            //alert("click " +preset);
            //jQuery.get("http://192.168.0.105/cgi-bin/musik.cgi/=/jukebox/play_now/"+preset);
            jQuery.get("PlayNow.php", { preset: preset } , function (data) {
                //alert('Load OK' + data);
            });
            //$('.ui-dialog').dialog('close');
            return false;
            });
            */

    /*
    $('ul.presets,#artist_album').delegate("a.onepreset2", "click", function() {
            var preset = $(this).data("musik").preset;
            jQuery.get("album.php", { preset: preset } , function (data) {
                $("#album_content").empty().append(data);
            });
            return true;
            });
            */

    /*
    $('ul.artistindex').delegate("a.artistindex", "click", function() {
            var firstpreset = $(this).data("musik").firstpreset;
            var count = $(this).data("musik").count;
            var id = $(this).data("musik").id;
            $("#artist_album").empty().append("");
            jQuery.get("presets.php", { firstpreset: firstpreset, count: count, id: id } , function (data) {
                $("#artist_album").empty().append(data);
                //$('#presets').listview('refresh');
                $('#presets').listview();
            });
            return true;
            });
            */

    $('ul.presets').delegate("a.onepreset", "swipeleft", function() {
            var preset = $(this).data("musik").preset;
            alert("swipeleft " +preset);
            return false;
            });

    //$("img.onepreset").lazyload({placeholder : "webapp/tuupola-jquery_lazyload-3f123e9/img/grey.gif"});
});

