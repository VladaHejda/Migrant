<?php

namespace VladaHejda\Migrant;

class LocalRunner extends Runner
{

	public static function run($configFile)
	{
		$globalSettings = self::parseConfig($configFile);
		$settings = $globalSettings['migrant'];
		unset($globalSettings['migrant']);

		// find first deployment section
		reset($globalSettings);
		$deploySettings = & $globalSettings;
		while (!isset($deploySettings['remote'])) {
			$section = key($globalSettings);
			$deploySettings = & $globalSettings[$section];
			next($globalSettings);
		}

		// append before/after procedures
		$deploySettings += [
			'before' => [], 'after' => [],
		];
		$url = sprintf('http://%s/handle-maintenance.php', $settings['siteUrl']);
		$settings['password'] = self::generatePassword();
		$encodedPassword = urlencode($settings['password']);
		$deploySettings['before'][] = sprintf('%s?operation=start&password=%s', $url, $encodedPassword);
		$deploySettings['after'][] = sprintf('%s?operation=migrate&password=%s', $url, $encodedPassword);
		$deploySettings['after'][] = sprintf('%s?operation=stop&password=%s', $url, $encodedPassword);

		// let migrant settings deploy
		$settingsFile = self::getRootDir() . self::$tempSettingsFileName;
		$handle = fopen($settingsFile, 'w');
		fwrite($handle, serialize($settings));
		fclose($handle);

		// deploy
		$deployTempConfig = tempnam(sys_get_temp_dir(), 'mig');
		$tmpFile = fopen($deployTempConfig, 'w');
		fwrite($tmpFile, var_export($globalSettings, true));
		fclose($tmpFile);
		system(sprintf('php %s %s', escapeshellarg(__DIR__ . '/../../../../dg/ftp-deployment/Deployment/deployment'),
			escapeshellarg($deployTempConfig)));

		// cleanup
		unlink($settingsFile);
	}


	private static function parseConfig($settingsFile)
	{
		// check file location
		if (!file_exists($settingsFile)) {
			throw new ConfigurationException('Config file does not exist.');
		}

		$settings = parse_ini_file($settingsFile, true);

		// check migrant ini section
		if (!isset($settings['migrant'])) {
			throw new ConfigurationException('Config: [migrant] section not found, please define it.');
		}
		$section = & $settings['migrant'];
		if (!isset($section['dsn'])) {
			throw new ConfigurationException('Config: DSN setting not found.');
		}

		preg_match('#^(?P<driver>[^:]+)://(?P<username>[^:]+):(?P<password>[^@]+)@(?P<host>[^:/]+)'
			. '(?::(?P<port>[0-9]+))?/(?P<database>.+)$#i', $section['dsn'], $matches);

		if (!$matches) {
			throw new ConfigurationException('Config: Wrong DSN definition, use "<driver>://<username>:'
				. '<password>@<host>:<port>/<database>".');
		}

		$section['pdo'] = [
			'driver' => $matches['driver'],
			'username' => $matches['username'],
			'password' => $matches['password'],
			'host' => $matches['host'] . ($matches['port'] ? ":{$matches['port']}" : ''),
			'database' => $matches['database'],
		];

		$section['allowedIps'] = isset($section['allowedIps']) ? array_filter(array_map('trim', explode(',',
			$section['allowedIps']))) : [];

		$section += [
			'storageDir' => self::getRootDir() . '/',
			'log' => self::getRootDir() . '/log/migrant.log',
			'migrationsDir' => self::getRootDir() . '/migrations',
			'reportingMail' => null,
		];

		return $settings;
	}


	private static function generatePassword()
	{
		$chrLimits = [33, 126];
		$password = '';
		for ($i = 0; $i < 50; $i++) {
			$password .= chr(mt_rand($chrLimits[0], $chrLimits[1]));
		}
		return $password;
	}

}
