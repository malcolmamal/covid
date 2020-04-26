<?php declare(strict_types=1);

namespace Covid\Util;

use Covid\Exception\ConfigException;

class Config
{
	/**
	 * @var Config
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	private $configArray = [];

	private function __construct()
	{
		$this->configArray = ConfigParser::readConfig();
	}

	/**
	 * @return Config
	 */
	public static function getInstance(): Config
	{
		if (self::$instance == null)
		{
			self::$instance = new Config();
		}

		return self::$instance;
	}

	/**
	 * @param string $key
	 * @param bool $cryOnFailure
	 * @param string|null $default
	 *
	 * @return string
	 *
	 * @throws ConfigException
	 */
	public static function getValue(string $key, bool $cryOnFailure = false, string $default = ''): string
	{
		$config = self::getInstance();

		if (isset($config->configArray[$key]))
		{
			return $config->configArray[$key];
		}

		if ($cryOnFailure)
		{
			throw new ConfigException('Config key not found: ' . $key);
		}

		/**
		 * TODO: check also in default file
		 */

		return $default;
	}

	/**
	 * @return string
	 */
	public static function getTempPath(): string
	{
		return static::getValue('temp_path', false, '../temp') . DIRECTORY_SEPARATOR;
	}

	/**
	 * @return string
	 */
	public static function getDataPath(): string
	{
		return static::getTempPath()
			. static::getValue('temp_folder', false, 'covid')
			. DIRECTORY_SEPARATOR;
	}

	/**
	 * @return string
	 */
	public static function getOutputPath(): string
	{
		$outputPath = static::getValue('output_path');

		if (!empty($outputPath) && is_dir($outputPath))
		{
			return $outputPath . DIRECTORY_SEPARATOR;
		}

		return static::getDataPath();
	}
}
