<!DOCTYPE html>
<!--
/*!
* LinnDS-jukebox
*
* Copyright (c) 2011-2013 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/ -->

<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1">

	<title>LinnDS-jukebox</title> 

<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.css" />
<link rel="stylesheet" href="sprites/sprites.css" />
<link rel="stylesheet" href="sprites/sprites@2x.css" media="(-webkit-min-device-pixel-ratio: 2)"/>
<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
<link rel="stylesheet" href="musik.css" />
<script src="actions.js"></script>
<script>
    $(document).bind("mobileinit", function(){
	    //$.extend($.mobile, {
		    //defaultPageTransition: 'none',
		    //defaultDialogTransition: 'none'
	    //});
    });
    getStatus();

</script>
<script src="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.js"></script>

</head>
<body>
