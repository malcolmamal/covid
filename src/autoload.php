<?php declare(strict_types=1);

require_once 'version.php';

require __DIR__ . '/../vendor/autoload.php';

spl_autoload_register('covidAutoload');

/**
 * autoloader for Covid
 *
 * @param string $className
 */
function covidAutoload(string $className)
{
	if (Autoloader::autoloadNamespace($className))
	{
		return;
	}

	if (strpos($className, 'Covid\\') != 0)
	{
		return;
	}

	/**
	 * currently everything is in the same folder
	 */

	$explodedClassName = explode('\\', $className);
	$classFileName = end($explodedClassName) . '.php';
	$filePath = __DIR__ . DIRECTORY_SEPARATOR . $classFileName;

	if (file_exists($filePath))
	{
		require_once $filePath;
	}
}

class Autoloader
{
	/**
	 * Loads classes by namespace that is converted into a path
	 *
	 * @param string $className
	 *
	 * @return bool
	 */
	public static function autoloadNamespace($className): bool
	{
		$parts = explode('\\', $className);

		if (empty($parts) || $parts[0] !== 'Covid')
		{
			return false;
		}

		array_shift($parts);

		$filePath = __DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
		if (is_file($filePath))
		{
			require_once $filePath;

			return true;
		}

		return false;
	}
}
