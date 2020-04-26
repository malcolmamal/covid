<?php declare(strict_types=1);

namespace Covid\Util;

use Covid\Exception\Exception;

class ConfigParser
{
	/**
	 * @return array
	 *
	 * @throws Exception
	 */
	public static function readConfig(): array
	{
		$configPath = __DIR__ . '/../../config.ini';
		if (!file_exists($configPath))
		{
			$configPath = __DIR__ . '/../../config.default.ini';
		}
		$config = parse_ini_file($configPath);

		if (!$config)
		{
			throw new Exception('Could not load config: ' . $configPath);
		}

		return $config;
	}
}
