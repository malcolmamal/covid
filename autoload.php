<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

spl_autoload_register('covidAutoload');

/**
 * autoloader for Covid
 *
 * @param string $className
 */
function covidAutoload(string $className)
{
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
