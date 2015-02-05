<?php

namespace VladaHejda\Migrant;

use PDO;

class Configuration
{

	/** @var PDO */
	protected $pdo;

	/** @var ILogger */
	protected $logger;

	/** @var IStorage */
	protected $storage;

	/** @var string */
	protected $migrationsDir;

	/** @var string */
	protected $reportingMail;


	/**-
	 * @param PDO $pdo
	 */
	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}


	/**
	 * @return PDO
	 */
	public function getPdo()
	{
		return $this->pdo;
	}


	/**
	 * @return ILogger
	 */
	public function getLogger()
	{
		return $this->logger;
	}


	/**
	 * @return IStorage
	 */
	public function getStorage()
	{
		if (!$this->storage) {
			throw new \BadMethodCallException('Storage is not defined.');
		}
		return $this->storage;
	}


	/**
	 * @return string
	 */
	public function getMigrationsDir()
	{
		return $this->migrationsDir;
	}


	/**
	 * @return string
	 */
	public function getReportingMail()
	{
		return $this->reportingMail;
	}


	/**
	 * @param ILogger $logger
	 * @return self
	 */
	public function setLogger(ILogger $logger)
	{
		$this->logger = $logger;
		return $this;
	}


	/**
	 * @param IStorage $storage
	 * @return self
	 */
	public function setStorage(IStorage $storage)
	{
		$this->storage = $storage;
		return $this;
	}


	/**
	 * @param string $dir
	 * @return self
	 */
	public function setMigrationsDir($dir)
	{
		$this->migrationsDir = $dir;
		return $this;
	}


	/**
	 * @param string $mail
	 * @return self
	 */
	public function setReportingMail($mail)
	{
		$this->reportingMail = $mail;
		return $this;
	}

}
