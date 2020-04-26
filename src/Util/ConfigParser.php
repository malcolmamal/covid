<?php declare(strict_types=1);

namespace Covid\Util;

class ConfigParser
{
	public static function readConfig(): array
	{
		$configPath = __DIR__ . '/../../config.ini';
		if (!file_exists($configPath))
		{
			$configPath = __DIR__ . '/../../config.default.ini';
		}
		$config = parse_ini_file($configPath);

		return $config;
	}
}
