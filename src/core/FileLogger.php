<?php

namespace VladaHejda\Migrant;

class FileLogger implements ILogger
{

	/** @var resource */
	private $resource;


	public function __construct($logFile)
	{
		$this->resource = fopen($logFile, 'a');
	}


	public function log($val)
	{
		fwrite($this->resource, $val);
	}


	public function __destruct()
	{
		fclose($this->resource);
	}

}
