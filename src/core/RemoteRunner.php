<?php

namespace VladaHejda\Migrant;

use PDO;

class RemoteRunner extends Runner
{

	const START = 'start';
	const MIGRATE = 'migrate';
	const STOP = 'stop';


	/**
	 * @param string $operation one of START, MIGRATE or STOP constants
	 * @param string $password
	 * @throws RequestException
	 */
	public static function run($operation, $password)
	{
		$settingsFile = self::getRootDir() . '/' . self::$tempSettingsFileName;
		if (!file_exists($settingsFile)) {
			throw new RequestException('Migrant configuration not found.');
		}

		try {
			$settings = static::tryCall(function () use ($settingsFile) {
				return unserialize(file_get_contents($settingsFile));
			}, 'unserialize');
		} catch (\ErrorException $e) {
			throw new RequestException('Broken Migrant configuration.', 0, $e);
		}
		unlink($settingsFile);

		self::checkIp($settings['allowedIps']);

		if ($operation === self::START) {
			if (!file_exists($settings['secretDir'])) {
				mkdir($settings['secretDir']);
			}
			$temp503File = $settings['secretDir'] . '/' . self::$tempPassword503FileName;
			if (file_exists($temp503File . '-local')) {
				// security - should not exist, but accidentally might be deployed
				unlink($temp503File . '-local');
			}
			$password503tempFile = $settings['secretDir'] . '/' . self::$tempPassword503FileName . '-remote';
			$handle = fopen($password503tempFile, 'a+');
			rewind($handle);
			$password503 = trim(fread($handle, filesize($password503tempFile) ?: 1));

			self::checkCredentials($password503, $password);

			ftruncate($handle, 0);
			fwrite($handle, $settings['password']);
			fclose($handle);

		} else {
			self::checkCredentials($settings['password'], $password);
		}

		if ($settings['storageDir'] && !file_exists($settings['storageDir'])) {
			mkdir($settings['storageDir']);
		}
		if ($settings['log'] && !file_exists($logDir = preg_replace('#/[^/]+$#', '', $settings['log']))) {
			mkdir($logDir);
		}

		$migrant = self::createMigrant($settings);

		switch ($operation) {
			case 'start':
				$migrant->start();
				break;
			case 'stop':
				$migrant->stop();
				break;
			case 'migrate':
				$migrant->migrate();
				break;
			default:
				throw new RequestException('Unknown operation.');
		}
	}


	/**
	 * @param array $settings
	 * @return \VladaHejda\Migrant
	 */
	protected function createMigrant(array $settings)
	{
		$pdoDsn = sprintf('%s:host=%s;dbname=%s', $settings['pdo']['driver'], $settings['pdo']['host'], $settings['pdo']['database']);
		$configuration = new Configuration(new PDO($pdoDsn, $settings['pdo']['username'], $settings['pdo']['password']));

		$configuration->setStorage(new FileStorage($settings['storageDir']));
		if ($settings['log']) {
			$configuration->setLogger(new FileLogger($settings['log']));
		}
		$configuration->setMigrationsDir($settings['migrationsDir']);
		$configuration->setReportingMail($settings['reportingMail']);

		return new \VladaHejda\Migrant($configuration);
	}


	private static function checkIp(array $allowedIps)
	{
		if (empty($_SERVER['REMOTE_ADDR'])) {
			throw new RequestException('Cannot detect IP address.');
		}

		if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIps, true)) {
			throw new RequestException('Request from disallowed IP address.');
		}
	}


	private static function checkCredentials($passwordA, $passwordB)
	{
		if (empty($passwordA) || $passwordA !== $passwordB) {
			throw new RequestException('Empty or wrong password.');
		}
	}

}
