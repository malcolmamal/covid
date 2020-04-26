<?php declare(strict_types=1);

namespace Covid\Input;

use Covid\Consts;

class Data
{
	const COUNTRIES_ALL = Consts::MAIN_SECTION;

	const COUNTRY_POLAND = 'Poland';
	const COUNTRY_USA = 'US';
	const COUNTRY_GERMANY = 'Germany';
	const COUNTRY_FRANCE = 'France';
	const COUNTRY_ITALY = 'Italy';
	const COUNTRY_SPAIN = 'Spain';
	const COUNTRY_UK = 'United Kingdom';
	const COUNTRY_IRAN = 'Iran';
	const COUNTRY_TURKEY = 'Turkey';
	const COUNTRY_SWEDEN = 'Sweden';
	const COUNTRY_BELGIUM = 'Belgium';
	const COUNTRY_NETHERLANDS = 'Netherlands';
	const COUNTRY_BRAZIL = 'Brazil';
	const COUNTRY_RUSSIA = 'Russia';
	const COUNTRY_JAPAN = 'Japan';
	const COUNTRY_INDIA = 'India';
	const COUNTRY_ISRAEL = 'Israel';

	const TREND_POSITIVE = 'positive';
	const TREND_NEGATIVE = 'negative';

	const TREND = 'trend';
	const TREND_TYPE_DAY = self::TREND . Consts::DAY_SUFFIX;
	const TREND_TYPE_INCREASE = self::TREND . Consts::INCREASE_SUFFIX;

	/**
	 * @var array
	 */
	private $confirmed = [];

	/**
	 * @var array
	 */
	private $deaths = [];

	/**
	 * @var array
	 */
	private $recovered = [];

	/**
	 * @var array
	 */
	private $confirmedDay = [];

	/**
	 * @var array
	 */
	private $deathsDay = [];

	/**
	 * @var array
	 */
	private $recoveredDay = [];

	/**
	 * @var array
	 */
	private $confirmedIncrease = [];

	/**
	 * @var array
	 */
	private $deathsIncrease = [];

	/**
	 * @var array
	 */
	private $recoveredIncrease = [];

	/**
	 * @var array
	 */
	private $countries = [];

	/**
	 * TODO: some countries are split between provinces, might be worth doing the splits at some point
	 *
	 * Currently we just fill the provinces array and we're happy
	 *
	 * @var array
	 */
	private $provinces = [];

	/**
	 * @var array
	 */
	private $dates = [];

	/**
	 * @var array
	 */
	private $perCountries = [];

	/**
	 * @var bool
	 */
	private $headlineInitialized = false;

	/**
	 * @var bool
	 */
	private $excelFriendly = false;

	/**
	 * @var string
	 */
	private $averageType = Consts::DAYS_AVG_TYPE_WEEK;

	/**
	 * @var int
	 */
	private $columns = 0;

	/**
	 * @var array
	 */
	private $trends = [];

	/**
	 * @var RollingAverageData[]
	 */
	private $rollingAverages = [];

	/**
	 * @param array $dataRow
	 * @param string $type
	 */
	public function addRow(array $dataRow, string $type): void
	{
		if (!$this->headlineInitialized)
		{
			return;
		}

		$province = $dataRow[0];
		$country = $dataRow[1];

		if (!empty($province))
		{
			$country .= ' - ' . $province;
		}

		if ($this->excelFriendly)
		{
			$country = str_replace("*", "", $country);
			$country = substr($country, 0, 31);
		}

		$this->countries[$country] = true;
		$this->provinces[$province] = true;

		$previous = 0;
		$previousDay = 0;
		$previousIncrease = 0;
		$previousAverage = 0;

		$rollingAverage = new RollingAverageData();
		$this->rollingAverages[$country][$type] = [];

		$array = $type;
		$arrayDay = $array . 'Day';
		$arrayIncrease = $array . 'Increase';
		for ($i = 4; $i < $this->columns; $i++)
		{
			$day = $this->getDay($i);

			$current = (int)$dataRow[$i];
			$currentDay = $current - $previous;
			$currentIncrease = $this->computeIncreasePercentage($current, $previous);

			$this->$array[$country][$day] = $current;
			$this->$arrayDay[$country][$day] = $currentDay;
			$this->$arrayIncrease[$country][$day] = $currentIncrease;

			@$this->$array[self::COUNTRIES_ALL][$day] += $current;
			@$this->$arrayDay[self::COUNTRIES_ALL][$day] += $currentDay;

			$rollingAverage->addValue($currentDay);
			$currentAverage = $rollingAverage->getAverageForType($this->averageType);
			$this->rollingAverages[$country][$type][$day][$this->averageType] = $currentAverage;

			$this->applyTrends(
				$country, $day, $type, Consts::DAY_SUFFIX,
				$currentDay, $previousDay
			);

			$this->applyTrends(
				$country, $day, $type, Consts::INCREASE_SUFFIX,
				$currentIncrease, $previousIncrease
			);

			$this->applyTrends(
				$country, $day, $type, Consts::AVERAGE_SUFFIX,
				$currentAverage, $previousAverage
			);

			$previous = $current;
			$previousDay = $currentDay;
			$previousIncrease = $currentIncrease;
			$previousAverage = $currentAverage;
		}
	}

	/**
	 * This will be formatted as a percentage value later on
	 *
	 * @param int $newValue
	 * @param int $originalValue
	 *
	 * @return float
	 */
	private function computeIncreasePercentage(int $newValue, int $originalValue): float
	{
		if ($originalValue == 0)
		{
			return 0;
		}

		$increase = $newValue - $originalValue;
		$increasePercentage = $increase / $originalValue;

		return $increasePercentage;
	}

	/**
	 * @param string $country
	 * @param string $type
	 * @param string $day
	 * @param string $avgType
	 *
	 * @return int
	 */
	public function getRollingAverageValue(string $country, string $type, string $day, string $avgType): int
	{
		if (empty($this->rollingAverages[$country][$type][$day][$avgType]))
		{
			return 0;
		}

		return $this->rollingAverages[$country][$type][$day][$avgType];
	}

	/**
	 * @param array $dataRow
	 */
	public function addHeadline(array $dataRow):void
	{
		if ($this->headlineInitialized)
		{
			return;
		}

		$this->columns = count($dataRow);
		for ($i = 4; $i < $this->columns; $i++)
		{
			$this->dates[$i] = $dataRow[$i];
		}

		$this->headlineInitialized = true;
	}

	/**
	 * @param int $i
	 *
	 * @return string
	 */
	private function getDay(int $i): string
	{
		return $this->dates[$i];
	}

	/**
	 * @return array
	 */
	public function getConfirmed(): array
	{
		return $this->confirmed;
	}

	/**
	 * @return array
	 */
	public function getDeaths(): array
	{
		return $this->deaths;
	}

	/**
	 * @return array
	 */
	public function getRecovered(): array
	{
		return $this->recovered;
	}

	/**
	 * @return array
	 */
	public function getConfirmedDay(): array
	{
		return $this->confirmedDay;
	}

	/**
	 * @return array
	 */
	public function getDeathsDay(): array
	{
		return $this->deathsDay;
	}

	/**
	 * @return array
	 */
	public function getRecoveredDay(): array
	{
		return $this->recoveredDay;
	}

	/**
	 * @return array
	 */
	public function getConfirmedIncrease(): array
	{
		return $this->confirmedIncrease;
	}

	/**
	 * @return array
	 */
	public function getDeathsIncrease(): array
	{
		return $this->deathsIncrease;
	}

	/**
	 * @return array
	 */
	public function getRecoveredIncrease(): array
	{
		return $this->recoveredIncrease;
	}

	/**
	 * @return array
	 */
	public function getCountries(): array
	{
		return $this->countries;
	}

	/**
	 * @return array
	 */
	public function getCountryNames(): array
	{
		return array_keys($this->countries);
	}

	/**
	 * @return array
	 */
	public function getTrends(): array
	{
		return $this->trends;
	}

	/**
	 * @return array
	 */
	public function getTestCountryNames(): array
	{
		return [
			self::COUNTRY_POLAND,
			self::COUNTRY_USA,
			self::COUNTRY_GERMANY,
			self::COUNTRY_ITALY,
		];
	}

	/**
	 * @return array
	 */
	public function getMainCountryNames(): array
	{
		return [
			self::COUNTRY_POLAND,
			self::COUNTRY_USA,
			self::COUNTRY_GERMANY,
			self::COUNTRY_FRANCE,
			self::COUNTRY_ITALY,
			self::COUNTRY_SPAIN,
			self::COUNTRY_UK,
			self::COUNTRY_IRAN,
			self::COUNTRY_TURKEY,
			self::COUNTRY_SWEDEN,
			self::COUNTRY_BELGIUM,
			self::COUNTRY_NETHERLANDS,
			self::COUNTRY_BRAZIL,
			self::COUNTRY_RUSSIA,
			self::COUNTRY_JAPAN,
			self::COUNTRY_INDIA,
			self::COUNTRY_ISRAEL,
		];
	}

	/**
	 * @return array
	 */
	public function getPerCountriesData(): array
	{
		return $this->perCountries;
	}

	/**
	 * @param string $country
	 *
	 * @return array
	 */
	public function getDataForCountry(string $country): array
	{
		return $this->perCountries[$country] ?? [];
	}

	/**
	 * @return array
	 */
	public function getPolandData(): array
	{
		return $this->perCountries[self::COUNTRY_POLAND];
	}

	/**
	 * Fills data array with actual data
	 */
	public function arrangeData(): void
	{
		$countries = array_merge($this->getCountryNames(), [self::COUNTRIES_ALL]);
		foreach ($countries as $country)
		{
			$this->perCountries[$country] = [
				Consts::TYPE_CONFIRMED => $this->confirmed[$country] ?? 0,
				Consts::TYPE_DEATHS => $this->deaths[$country] ?? 0,
				Consts::TYPE_RECOVERED => $this->recovered[$country] ?? 0,

				Consts::TYPE_CONFIRMED_DAY => $this->confirmedDay[$country] ?? 0,
				Consts::TYPE_DEATHS_DAY => $this->deathsDay[$country] ?? 0,
				Consts::TYPE_RECOVERED_DAY => $this->recoveredDay[$country] ?? 0,

				Consts::TYPE_CONFIRMED_INCREASE => $this->confirmedIncrease[$country] ?? 0,
				Consts::TYPE_DEATHS_INCREASE => $this->deathsIncrease[$country] ?? 0,
				Consts::TYPE_RECOVERED_INCREASE => $this->recoveredIncrease[$country] ?? 0,

				Consts::TYPE_TRENDS => $this->trends[$country] ?? [],
			];
		}
	}

	/**
	 * @param bool $excelFriendly
	 *
	 * @return Data
	 */
	public function setExcelFriendly(bool $excelFriendly = true): self
	{
		$this->excelFriendly = $excelFriendly;

		return $this;
	}

	/**
	 * @param string $country
	 * @param string $day
	 * @param string $type
	 * @param string $valueType
	 * @param int|float $current
	 * @param int|float $previous
	 */
	private function applyTrends(
		string $country, string $day, string $type, string $valueType,
		$current, $previous
	): void
	{
		$positive = self::TREND_POSITIVE;
		$negative = self::TREND_NEGATIVE;

		if ($type === Consts::TYPE_RECOVERED)
		{
			// increase in recoveries is a positive trend so we reverse it
			$positive = self::TREND_NEGATIVE;
			$negative = self::TREND_POSITIVE;
		}

		if ($previous != 0 || $current != 0)
		{
			$this->trends[$country][$day][$type . $valueType] = ($current > $previous) ? $positive : $negative;
		}
	}

	/**
	 * @param string $country
	 * @param string $day
	 * @param string $type
	 *
	 * @return null|string
	 */
	public function getSpecificTrend(string $country, string $day, string $type): string
	{
		if (isset($this->trends[$country][$day][$type]))
		{
			return $this->trends[$country][$day][$type];
		}

		return '';
	}

	/**
	 * @param string $averageType
	 *
	 * @return Data
	 */
	public function setAverageType(string $averageType): self
	{
		$this->averageType = $averageType;

		return $this;
	}
}
