<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2015-2016 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/


function strposall($haystack,$needle)
{ 
    /** 
    * strposall 
    * 
    * Find all occurrences of a needle in a haystack 
    * 
    * @param string $haystack 
    * @param string $needle 
    * @return array or false 
    */ 

    $s=0; 
    $i=0; 

    while (is_integer($i))
    { 
	
	$i = strpos($haystack,$needle,$s); 
	
	if (is_integer($i)) 
	{ 
	    $aStrPos[] = $i; 
	    $s = $i+strlen($needle); 
	} 
    } 
    if (isset($aStrPos)) 
    { 
	return $aStrPos; 
    } 
    else 
    { 
	return false; 
    } 
} 

function getParameters($str) 
{
    //format: [ACTION | RESPONSE] "Param1" "Param2" "Param3" ...
    //Result: array or false
    //        a[0] = Param1 ...

    $a = strposall($str, '"');

    if ($a === false)
	return false;

    //LogWrite("getParameters: " . print_r($a, true));

    $i = 0;
    $cnt = count($a);

    while ($i < $cnt) 
    {
	$aStr[] = substr($str, $a[$i]+1, $a[$i+1] - $a[$i] -1);
	$i += 2;
    }

    if (isset($aStr)) 
    { 
	//LogWrite("getParameters: " . print_r($aStr, true));
	return $aStr; 
    } 
    else 
    { 
	return false; 
    } 
}

function getEvent($str) 
{
    //format: EVENT <subscribe-no> <seq-no> Key1 "Value1" Key2 "Value2" ...
    //Result: associative array
    //        b[Key1] = Value1 ...

    $a = strposall($str, " ");
    $start = $a[2]+1;

    $b = array();

    $end = strlen($str);

    $i = $start;

    while ($i < $end) 
    {
	$e = strpos($str, " ", $i);
	$key = substr($str, $i, $e-$i);
	$i = $e+2;
	$e = strpos($str, "\"", $i);
	$value=substr($str, $i, $e-$i);
	$i = $e+2;

	$b[$key] = $value;
    }

    LogWrite("getEvent: " . print_r($b, true));
    return $b;
}

?>
