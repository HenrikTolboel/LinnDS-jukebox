<!DOCTYPE html>
<!--
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/ -->

<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1">

	<title>LinnDS-jukebox</title> 

<link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.0/jquery.mobile-1.1.0.min.css" />
<link rel="stylesheet" href="sprites/sprites.css" />
<script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
<script src="actions.js"></script>
<script src="jquery.lazyload.js"></script>
<script>
    //$(document).bind("mobileinit", function(){
	    //$.extend($.mobile, {
		    //defaultPageTransition: 'slide'
	    //});
    //});

    $(document).live("pageshow", function(){
	  $("img.lazy").lazyload({translate_url : function (value) {
		      if (getDevicePixelRatio() != 1)
			  value = value.replace("80x80", "160x160");
		      return value;
	      }
      });
    });
</script>
<script src="http://code.jquery.com/mobile/1.1.0/jquery.mobile-1.1.0.min.js"></script>

</head>
<body>
