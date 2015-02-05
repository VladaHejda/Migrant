<?php

namespace VladaHejda\Migrant;

class FileStorage implements IStorage
{

	/** @var string */
	private $tempDir;

	/** @var string */
	private $prefix;

	/** @var resource[]  */
	private $resources = [];


	public function __construct($tempDir, $prefix = 'migrant-')
	{
		$this->tempDir = $tempDir;
		$this->prefix = $prefix;
	}


	public function getList($var)
	{
		$fileName = $this->generateFileName($var);
		$resource = $this->getResource($fileName);
		rewind($resource);
		return array_filter(array_map('trim', explode("\n", fread($resource, filesize($fileName) ?: 1))));
	}


	public function listAppend($var, $val)
	{
		$fileName = $this->generateFileName($var);
		$resource = $this->getResource($fileName);
		fseek($resource, filesize($fileName));
		fwrite($resource, $val . "\n");
	}


	public function is($var)
	{
		return file_exists($this->generateFileName($var));
	}


	public function on($var)
	{
		fclose(fopen($this->generateFileName($var), 'w'));
	}


	public function off($var)
	{
		@unlink($this->generateFileName($var)); // @ - may not exists
	}


	public function __destruct()
	{
		foreach ($this->resources as $resource) {
			fclose($resource);
		}
	}


	private function getResource($fileName)
	{
		if (!isset($this->resources[$fileName])) {
			$this->resources[$fileName] = fopen($fileName, 'a+');
		}
		return $this->resources[$fileName];
	}


	private function generateFileName($var)
	{
		return sprintf('%s/.%s%s', $this->tempDir, $this->prefix, $var);
	}

}
