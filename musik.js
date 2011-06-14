/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

if (typeof HENRIK == "undefined" || !HENRIK) {
   var HENRIK = {};
}


HENRIK.Musik = function() {
    var pub = {};

    var ListingDIV = null;
    var UseJQueryUI = null;
    var Jukebox = null;

    function playPreset(presetNo) {
        jQuery.get("http://192.168.0.105/cgi-bin/musik.cgi/=/jukebox/play_now/"+presetNo);
        //alert("Hello!!"+ presetNo);
    }

    function playPresetLater(presetNo) {
        //jQuery.get("http://192.168.0.105/cgi-bin/musik.cgi/=/jukebox/play_later/"+presetNo);
    }

    function getURISearch(name, defaultValue) {
        // http://www.netlobo.com/url_query_string_javascript.html
        //Examples:
        //var Category = getURISearch("category", -1);
        //var t = getURISearch("hund", "fido");
        //
        name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
        var regexS = "[\\?&]"+name+"=([^&#]*)";
        var regex = new RegExp( regexS );
        var results = regex.exec( document.location.search );
        if( results == null )
            return defaultValue;
        else
            return results[1];
    }    

    function formatPresetName(presetName) {
        return presetName.replace(/\//g, " \/ ");
    }

    function getArtist(presetName) {
        var i = presetName.indexOf("/");

        if (i > 0) {
            return presetName.substring(0, i);
        } else {
            return formatPresetName(presetName);
        }
    }

    function getAlbum(presetName) {
        var i = presetName.lastIndexOf("/");

        if (i > 0) {
            return presetName.substring(i+1);
        } else {
            return formatPresetName(presetName);
        }
    }


    var UseHT = 0;
    var Accordion = 0;
    var Radio = 0;

    // Autocomplete arrays
    var preset_bookmark = new Array();
    var ArtistAutoComplete = new Array();
    var ArtistIndex = new Object();
    var AlbumAutoComplete = new Array();
    var AlbumIndex = new Object();

    function CalcAutocomplete(Jukebox) {
        for (var i = 0; i < Jukebox.Category.length; i++) {
            for (var j = 0; j < Jukebox.Category[i].Preset.length; j++) {
                preset_bookmark[Jukebox.Category[i].Preset[j].Number] = i;
                ArtistIndex[getArtist(Jukebox.Category[i].Preset[j].Name)] = i;
                AlbumIndex[getAlbum(Jukebox.Category[i].Preset[j].Name)] = i;
            }
        }
        var Cnt = 0;
        for (key in ArtistIndex) {
            ArtistIndex[key] = Cnt;
            ArtistAutoComplete[Cnt] = key;
            Cnt = Cnt+1;
        }
        Cnt = 0;
        for (key in AlbumIndex) {
            AlbumIndex[key] = Cnt;
            AlbumAutoComplete[Cnt] = key;
            Cnt = Cnt+1;
        }
    }

    function OnePreset(Preset) {
        var one = document.createElement('li');
        one.setAttribute('id', "li_onepreset-"+Preset.Number);
        one.setAttribute('class', "onepreset bookmark-" + preset_bookmark[Preset.Number] + " artist-" + ArtistIndex[getArtist(Preset.Name)] + " album-" + AlbumIndex[getAlbum(Preset.Name)]);
        one.setAttribute('data-options','{"id":' + Preset.Number + '}');

        var a = document.createElement('a'); //span
        a.setAttribute('id', "a_onepreset-"+Preset.Number);
        a.setAttribute('href', "#");
        a.setAttribute('class', "onepreset bookmark-" + preset_bookmark[Preset.Number] + " artist-" + ArtistIndex[getArtist(Preset.Name)] + " album-" + AlbumIndex[getAlbum(Preset.Name)]);
        a.setAttribute('data-options','{"id":' + Preset.Number + '}');
        
        a.appendChild(document.createTextNode("0" + Preset.Number + " -- " + getArtist(Preset.Name)));
        a.appendChild(document.createElement('br'));
        a.appendChild(document.createTextNode(getAlbum(Preset.Name)));

        one.appendChild(a);
        return one;
    }

    function createDocument(Jukebox) {
        var TheDiv = document.createElement("div");
        TheDiv.setAttribute('class', "manifest");
        TheDiv.setAttribute('id', "manifest");

        // Build document
        var d = document.createElement('ul');
        d.setAttribute('id', "presets");
        d.setAttribute('class', "presets");
        d.setAttribute('data-role', "listview");
        d.setAttribute('data-filter', "true");
        //d.setAttribute('data-inset', "false");
        //d.setAttribute('data-theme', "c");
        for (var i = 0; i < Jukebox.Category.length; i++) {
            var h = document.createElement('li');
            h.setAttribute('id', "bookmark-"+i);
            h.setAttribute('class', "bookmark bookmark-" + i);
            h.setAttribute('data-role', "list-divider");
            h.appendChild(document.createTextNode(Jukebox.Category[i].Bookmark.Name));
            d.appendChild(h);
            
            for (var j = 0; j < Jukebox.Category[i].Preset.length; j++) {
                d.appendChild(OnePreset(Jukebox.Category[i].Preset[j]));
            }
        }
        TheDiv.appendChild(d);
        return TheDiv;
    }

    function myJQueryUIBindActions() {

        (function($){

        //$(document).ready(function() {

            // START: Autocomplete
            $('#searchartist').bind("autocompleteselect", function(event, ui) {
                       jQuery('li.onepreset').hide();
                       var t = ui.item.value;
                       var y = event.view.ArtistIndex[t];
                       jQuery('li.artist-' + y).show();
                    });
            $('#searchalbum').bind("autocompleteselect", function(event, ui) {
                       jQuery('li.onepreset').hide();
                       var t = ui.item.value;
                       var y = event.view.AlbumIndex[t];
                       jQuery('li.album-' + y).show();
                    });
            // END: Autocomplete
            
            //jQuery('li.onepreset').hide();
            //$('#presets').bind("selectableselecting", function(event, ui) {
                    //var u = ui.selecting.id.substring(10);
                    //playPreset(u);
                    //});
            $('#presets').bind("selectableselected", function(event, ui) {
                    var u = ui.selected.id.substring(10);
                    playPreset(u);
                    jQuery('li.onepreset').show();
                    });
        //});
        })(jQuery);
    }

    pub.calculateContent = function(DivToReplace, data) {
        Jukebox = data.Jukebox;

        CalcAutocomplete(Jukebox);
        TheDiv = document.getElementById(DivToReplace);
        if (TheDiv.childNodes.length > 0) {
            TheDiv.removeChild(TheDiv.childNodes[0]);
        }	  
        TheDiv.appendChild(createDocument(Jukebox));

        $(function() {
        $('#presets').delegate("li.onepreset", "tap", function() {
                var id = $(this).data("options").id;
                playPreset(id);
                return false;
                });
        $('#presets').delegate("li.onepreset", "swiperight", function() {
                var id = $(this).data("options").id;
                alert("swiperight " +id);
                return false;
                });
        });
    }

    pub.init = function(initializer) {
        ListingDIV = initializer.ListingDIV;
        UseJQueryUI = initializer.UseJQueryUI;

        jQuery.getJSON("data/manifest.json", function(data){
                pub.calculateContent(ListingDIV, data);

                if (UseJQueryUI) {

                    $(function() {
                        $("#searchartist").autocomplete({
                            source: ArtistAutoComplete,
                            minLength: 1
                        });
                    });

                    $(function() {
                        $("#searchalbum").autocomplete({
                            source: AlbumAutoComplete,
                            minLength: 3
                        });
                    });

                    $(function() {
                        $("#presets").selectable({ 
                            filter: 'li.onepreset', 
                            autoRefresh: false
                        });
                    });
                    myJQueryUIBindActions();
                }
                else
                {
                    $(function() {
                    $('#presets').delegate("li.onepreset", "click", function() {
                            var id = $(this).data("options").id;
                            playPreset(id);
                            });
                    });
                }
        });
    };
    

    return pub;
}();
