<?php declare(strict_types=1);

namespace Covid\Input;

use Covid\Consts;

class RollingAverageData
{
	/**
	 * @var array
	 */
	private $lastWeek = [];

	/**
	 * @var array
	 */
	private $lastFortnight = [];

	/**
	 * @var float
	 */
	private $maxValue = 0;

	/**
	 * @param int $value
	 */
	public function addValue(int $value): void
	{
		if (count($this->lastWeek) === Consts::DAYS_WEEK)
		{
			array_shift($this->lastWeek);
		}
		$this->lastWeek[] = $value;

		if (count($this->lastFortnight) === Consts::DAYS_FORTNIGHT)
		{
			array_shift($this->lastFortnight);
		}
		$this->lastFortnight[] = $value;

		if ($value > $this->maxValue)
		{
			$this->maxValue = $value;
		}
	}

	/**
	 * @return int
	 */
	public function getWeekAverage(): int
	{
		$quantity = count($this->lastWeek);

		if ($quantity === 0)
		{
			return 0;
		}

		return (int)(array_sum($this->lastWeek) / $quantity);
	}

	/**
	 * @return int
	 */
	public function getFortnightAverage(): int
	{
		$quantity = count($this->lastFortnight);

		if ($quantity === 0)
		{
			return 0;
		}

		return (int)(array_sum($this->lastFortnight) / $quantity);
	}

	/**
	 * @param string $averageType
	 *
	 * @return int
	 */
	public function getAverageForType(string $averageType): int
	{
		if ($averageType === Consts::DAYS_AVG_TYPE_FORTNIGHT)
		{
			return $this->getFortnightAverage();
		}

		return $this->getWeekAverage();
	}
}
