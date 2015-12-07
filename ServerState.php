<?php
/*!
* LinnDS-jukebox
*
* Copyright (c) 2015-2016 Henrik Tolbøl, http://tolbøl.dk
*
* Licensed under the MIT license:
* http://www.opensource.org/licenses/mit-license.php
*/


require_once("setup.php");

class ServerState 
{
    protected $State = array();

    private function __construct()
    {
	$this->State['MAX_VOLUME'] = 60;
	$this->State['Volume'] = -1;
	$this->State['IdArray'] = array('0');
	$this->State['Id'] = 0;
	$this->State['NewId'] = 0;
	$this->State['RevNo'] = 0;
	$this->State['SourceName'] = array();

	$this->State['PlaylistURLs'] = array();
	$this->State['PlaylistXMLs'] = array();
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
	static $inst = null;

	if ($inst === null) 
	{
	     $inst = new ServerState();
	}

	return $inst;
    }

    public function setState($name, $value)
    {
	$this->State[$name] = $value;
	LogWrite("ServerState:setState[$name]=$value");
    }

    public function getState($name)
    {
	if (! isset($this->State[$name]))
	    return false;
	return $this->State[$name];
    }

    public function setStateArray($name, $subName, $value)
    {
	$this->State[$name][$subName] = $value;
	LogWrite("ServerState:setStateArray[$name][$subName]=$value");
    }

    public function getStateArray($name, $subName)
    {
	if (! isset($this->State[$name][$subName]))
	    return false;
	return $this->State[$name][$subName];
    }

    public function deleteAll()
    {
	$this->State['PlaylistURLs'] = array();
	$this->State['PlaylistXMLs'] = array();
	$this->State['IdArray'] = array('0');
	$this->State['Id'] = 0;
	$this->State['NewId'] = 0;
    }

    public function Serialize()
    {
	$tmpURLs = $this->State['PlaylistURLs'];
	$tmpXMLs = $this->State['PlaylistXMLs'];
	$this->State['PlaylistURLs'] = array();
	$this->State['PlaylistXMLs'] = array();
	$s = serialize($this->State);
	$this->State['PlaylistURLs'] = $tmpURLs;
	$this->State['PlaylistXMLs'] = $tmpXMLs;

	return $s;
    }

    public function dump()
    {
	$s = print_r($this->State, true);
	return $s;
    }

}



?>
