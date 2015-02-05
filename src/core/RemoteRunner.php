<?php

namespace VladaHejda\Migrant;

use PDO;

class RemoteRunner extends Runner
{

	const START = 'start';
	const MIGRATE = 'migrate';
	const STOP = 'stop';


	public static function run($operation, $password)
	{
		$settingsFile = __DIR__ . '/../../../../../' . static::$tempSettingsFileName;
		if (!file_exists($settingsFile)) {
			if ($operation === self::START) {
				// probably the first deploy ever
				return;
			}
			throw new RequestException('Temporary Migrant configuration not found.');
		}

		try {
			$settings = static::tryCall(function () use ($settingsFile) {
				return unserialize(file_get_contents($settingsFile));
			}, 'unserialize');
		} catch (\ErrorException $e) {
			throw new RequestException('Broken temporary Migrant configuration.', 0, $e);
		}
		unlink($settingsFile);

		self::check($settings['allowedIps'], $settings['password'], $password);

		$pdoDsn = sprintf('%s:host=%s;dbname=%s', $settings['pdo']['driver'], $settings['pdo']['host'], $settings['pdo']['database']);
		$configuration = new Configuration(new PDO($pdoDsn, $settings['pdo']['username'], $settings['pdo']['password']));

		if (!file_exists($settings['storageDir'])) {
			mkdir($settings['storageDir']);
		}
		if (!file_exists($logDir = preg_replace('#/[^/]+$#', '', $settings['log']))) {
			mkdir($logDir);
		}
		$configuration->setStorage(new FileStorage($settings['storageDir']));
		$configuration->setLogger(new FileLogger($settings['log']));
		$configuration->setMigrationsDir($settings['migrationsDir']);
		$configuration->setReportingMail($settings['reportingMail']);

		$migrant = new \VladaHejda\Migrant($configuration);

		switch ($operation) {
			case 'start':
				// todo nemá konfiguraci, protože se provádí před deployem
//				$migrant->start();
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


	private static function check(array $allowedIps, $passwordA, $passwordB)
	{
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

		if (!in_array($ip, $allowedIps, true)) {
			throw new RequestException('Request from disallowed IP address.');
		}

		if (empty($passwordA) || $passwordA !== $passwordB) {
			throw new RequestException('Empty or wrong password.');
		}
	}

}
