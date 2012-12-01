<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2012 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/

require_once("setup.php");

function UnlinkDPL()
{
    global $TopDirectory;
    try
    {
	$TopDirectoryCnt = count($TopDirectory);
	//print "TopDirectoryCnt: " . $TopDirectoryCnt . $NL;
	foreach ($TopDirectory as $Dir => $RM)
	{
	    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Dir));
	    while($it->valid())
	    {
		if($it->isFile())
		{
		    $ext = pathinfo($it->current(), PATHINFO_EXTENSION);

		    if ($ext == "dpl")
		    {
			unlink($it->getPathName());
		    }
		}
		$it->next();
	    }
	}
    }
    catch(Exception $e)
    {
	echo $e->getMessage();
    }
}

//UnlinkDPL();

?>
