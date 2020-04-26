<?php declare(strict_types=1);

namespace Covid\Util;

use Closure;
use Covid\Exception\FileException;

class Util
{
	/**
	 * runs closure with notices, warnings and errors converted to exception
	 *
	 * @param Closure $closure
	 *
	 * @return mixed
	 */
	public static function runClosureWithExceptions(Closure $closure)
	{
		set_error_handler(
			function (int $errNo, string $errStr, string $errFile, int $errLine)
			{
				throw new FileException($errStr);
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
				}
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
