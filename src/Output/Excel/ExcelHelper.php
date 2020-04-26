<?php declare(strict_types=1);

namespace Covid\Output\Excel;

class ExcelHelper
{
	const LETTERS_MAPPING = [
		'1'  => 'A', '2' => 'B', '3' => 'C', '4' => 'D',
		'5'  => 'E', '6' => 'F', '7' => 'G', '8' => 'H',
		'9'  => 'I', '10' => 'J', '11' => 'K', '12' => 'L',
		'13' => 'M', '14' => 'N', '15' => 'O', '16' => 'P',
		'17' => 'Q', '18' => 'R', '19' => 'S', '20' => 'T',
		'21' => 'U', '22' => 'V', '23' => 'W', '24' => 'X',
		'25' => 'Y', '26' => 'Z'
	];

	/**
	 * @param int $number
	 *
	 * @return string
	 */
	public static function convertColumnNumberToExcelFormat(int $number): string
	{
		if ($number <= 0)
		{
			return '';
		}
		elseif ($number <= 26)
		{
			return self::LETTERS_MAPPING[$number];
		}
		else
		{
			$firstLetter = $number / 26;
			$firstLetter = (int)floor($firstLetter);

			$secondLetter = $number - ($firstLetter * 26);
			$secondLetter = (int)$secondLetter;

			if ($number % 26 == 0)
			{
				$firstLetter--;
				$secondLetter = 26;
			}
			return self::LETTERS_MAPPING[$firstLetter] . self::LETTERS_MAPPING[$secondLetter];
		}
	}
}
