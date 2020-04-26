<?php declare(strict_types=1);

namespace Covid\Util;

use Closure;
use Covid\Exception\Exception;

class Util
{
	/**
	 * runs closure with notices, warnings and errors converted to exception
	 *
	 * @param Closure $closure
	 * @param string $exception
	 *
	 * @return null|mixed
	 */
	public static function runClosureWithExceptions(Closure $closure, $exception = Exception::class)
	{
		$return = null;
		set_error_handler(
			function ($errNo, $errStr, $errFile, $errLine) use ($exception)
			{
				throw new $exception($errStr);
			}
		);

		try
		{
			$return = $closure();
		}
		finally
		{
			restore_error_handler();
		}

		return $return;
	}

	/**
	 * Creates a directory (and sets specific permissions if defined)
	 *
	 * @param string $directoryName path
	 * @param bool $suppressWarningForAlreadyExists
	 */
	public static function mkdir($directoryName, $suppressWarningForAlreadyExists = true): void
	{
		if ($suppressWarningForAlreadyExists && file_exists($directoryName))
		{
			/**
			 *  maybe log that we already have this directory,
			 *  since we should be sure not to make a dir if it's already there
			 */
		}
		else
		{
			self::runClosureWithExceptions(
				function () use ($directoryName)
				{
					mkdir($directoryName, 0777, true);
				},
				Exception::class
			);
		}

		if (defined('TEMP_DIRECTORY_PERMISSIONS'))
		{
			self::runClosureWithExceptions(
				function () use ($directoryName)
				{
					chmod($directoryName, TEMP_DIRECTORY_PERMISSIONS);
				},
				Exception::class
			);
		}
	}

	/**
	 * @param mixed $value
	 * @param bool $strict
	 *
	 * @return bool
	 */
	public static function validArray($value, bool $strict = false): bool
	{
		if (is_array($value) && !empty($value) && (!$strict || $value[key($value)]))
		{
			return true;
		}

		return false;
	}
}
