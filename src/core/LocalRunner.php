<?php

namespace VladaHejda\Migrant;

class LocalRunner extends Runner
{

	public function run($configFile)
	{
		$globalSettings = $this->parseConfig($configFile);
		$settings = $globalSettings['migrant'];
		unset($globalSettings['migrant']);

		$jobs = $this->findJobs($globalSettings);

		$url = sprintf('%s/handle-maintenance.php', rtrim($settings['siteUrl'], '/'));
		$settings['password'] = self::generatePassword();

		// todo přidat settings ignore /.secret ?
		if (!file_exists($settings['secretDir'])) {
			mkdir($settings['secretDir']);
		}
		$temp503File = $settings['secretDir'] . '/' . self::$tempPassword503FileName . '-local';
		$handle = fopen($temp503File, 'a+');
		rewind($handle);
		$password503 = trim(fread($handle, filesize($temp503File) ?: 1));
		if (!empty($password503)) {
			$jobs['before'][] = sprintf('%s?operation=start&password=%s', $url, urlencode($password503));
			ftruncate($handle, 0);
		}
		fwrite($handle, $settings['password']);
		fclose($handle);

		$encodedPassword = urlencode($settings['password']);
		$jobs['after'][] = sprintf('%s?operation=migrate&password=%s', $url, $encodedPassword);
		$jobs['after'][] = sprintf('%s?operation=stop&password=%s', $url, $encodedPassword);

		// let migrant settings deploy
		$settingsFile = self::getRootDir() . '/' . self::$tempSettingsFileName;
		$handle = fopen($settingsFile, 'w');
		fwrite($handle, serialize($settings));
		fclose($handle);

		// deploy
		$deployTempConfig = sys_get_temp_dir() . '/deploy.php';
		$tmpFile = fopen($deployTempConfig, 'w');
		fwrite($tmpFile, '<?php return ' . var_export($globalSettings, true) . ";\n");
		fclose($tmpFile);
		system(sprintf('php %s %s', escapeshellarg(__DIR__ . '/../../../../dg/ftp-deployment/Deployment/deployment'),
			escapeshellarg($deployTempConfig)));

		// cleanup
		unlink($settingsFile);
	}


	/**
	 * @param array $settings
	 * @return array [ array & $before, array & $after ]
	 */
	private function findJobs(array & $settings)
	{
		if (isset($settings['remote'])) {
			return [& $settings, & $settings];
		}

		$before = $after = null;
		foreach ($settings as & $section) {
			if (isset($section['remote'])) {
				if ($before === null) {
					$before = & $section;
				}
				$after = & $section;
				// set local dir if not defined
				$section += [ 'local' => self::getRootDir() ];
			}
		}
		if ($before === null) {
			throw new ConfigurationException('Deployment section not found.');
		}
		$before += [ 'before' => [] ];
		$after += [ 'after' => [] ];
		return [
			'before' => & $before['before'],
			'after' => & $after['after'],
		];
	}


	private function parseConfig($settingsFile)
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
			'storageDir' => self::getRootDir() . '/', // remote only
			'log' => self::getRootDir() . '/log/migrant.log', // remote only
			'migrationsDir' => self::getRootDir() . '/migrations', // client & remote (deployed)
			'secretDir' => self::getRootDir() . '/.secret', // client & remote (not deployed, must persist between deployments)
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
