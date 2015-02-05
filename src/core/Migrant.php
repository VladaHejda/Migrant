<?php

namespace VladaHejda;

/**
 * @todo tag repo with new version
 */
class Migrant
{

	/** @var Migrant\Configuration */
	private $configuration;


	/**
	 * @param Migrant\Configuration $configuration
	 */
	public function __construct(Migrant\Configuration $configuration)
	{
		$this->configuration = $configuration;
	}


	public function migrate()
	{
		$logStarted = false;
		$this->configuration->getStorage()->off('error');

		$migrated = $this->configuration->getStorage()->getList('list');
		$migrations = opendir($this->configuration->getMigrationsDir());

		while ($migration = readdir($migrations)) {
			$filename = $this->configuration->getMigrationsDir() . '/' . $migration;

			if (!is_file($filename) || substr($migration, 0, 4) === '.git' || in_array($migration, $migrated, true)) {
				continue;
			}

			preg_match('/\.([^\.]+)$/', $migration, $matches);
			$fileExtension = !empty($matches[1]) ? $matches[1] : '';

			set_error_handler(function ($severity, $message, $file, $line) {
				throw new \ErrorException($message, 0, $severity, $file, $line);
			});

			try {
				if ($fileExtension === 'php') {
					$pdo = $this->configuration->getPdo();
					require $filename;
					$logMessage = 'executed';

				} elseif ($fileExtension === 'sql') {
					$this->configuration->getPdo()->query(file_get_contents($filename));
					$logMessage = 'executed';

				} else {
					$logMessage = 'unknown';
				}

			} catch (\Exception $e) {
				$this->configuration->getStorage()->on('error');
				$this->notifyError(sprintf("%s (%d): %s in %s:%d\n\n%s", get_class($e), $e->getCode(),
					$e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
				break;
			}

			$this->configuration->getStorage()->listAppend('list', $migration);
			if (!$logStarted) {
				$this->log(sprintf("Migrations started at %s:\n", date('Y-m-d (H:i:s)')));
				$logStarted = true;
			}
			$this->log(sprintf("%s: %s\n", $migration, $logMessage));
		}
		if ($logStarted) {
			$this->log("\n");
		}
	}


	public function start()
	{
		$this->configuration->getStorage()->on('lock');
	}


	public function stop()
	{
		if (!$this->configuration->getStorage()->is('error')) {
			$this->configuration->getStorage()->off('lock');
		}
	}


	private function log($val)
	{
		if ($logger = $this->configuration->getLogger()) {
			$logger->log($val);
		}
	}


	// todo INotifier
	private function notifyError($message)
	{
		if ($this->configuration->getReportingMail()) {
			mail($this->configuration->getReportingMail(), sprintf('%s deployment error', $_SERVER['HTTP_HOST']), "$message\n",
				sprintf("From: noreply@%s\n", $_SERVER['HTTP_HOST']));
		}
	}

}
