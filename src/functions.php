<?php declare(strict_types=1);

/**
 * autoloader for Covid
 *
 * @param string $className
 */
function covidAutoload(string $className): void
{
	if (autoloadNamespace($className))
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

/**
 * Loads classes by namespace that is converted into a path
 *
 * @param string $className
 *
 * @return bool
 */
function autoloadNamespace($className): bool
{
	$parts = explode('\\', $className);

	if (count($parts) === 0 || $parts[0] !== 'Covid')
	{
		return false;
	}

	array_shift($parts);

	$filePath = __DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
	if (is_file($filePath))
	{
		/**
		 * @psalm-suppress UnresolvableInclude
		 */
		require_once $filePath;

		return true;
	}

	return false;
}
