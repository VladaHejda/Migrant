<?php

namespace VladaHejda\Migrant;

abstract class Runner
{

	/** @var string */
	static $tempSettingsFileName = '.migrant';

	/** @var string */
	static $tempPassword503FileName = '.mig503passw';


	protected static function tryCall(callable $try, $check = null)
	{
		$prev = set_error_handler(function($severity, $message, $file, $line) use (& $prev, $check) {
			restore_error_handler();
			if ($check === null || strpos($message, $check) !== false) {
				throw new \ErrorException($message, 0, $severity, $file, $line);
			}
			if ($prev) {
				return $prev(...func_get_args());
			}
			return false;
		});

		$r = $try();
		restore_error_handler();
		return $r;
	}


	protected static function getRootDir()
	{
		// the root dir where composer's "vendor" dir is placed
		return __DIR__ . '/../../../../..';
	}

}
