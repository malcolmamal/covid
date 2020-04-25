<?php declare(strict_types=1);

namespace Covid\Input;

use Covid\Service\Service;

class Data
{
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

	const TREND_POSITIVE = 'positive';
	const TREND_NEGATIVE = 'negative';

	const TREND = 'trend';
	const TREND_TYPE_DAY = self::TREND . Service::DAY_SUFFIX;
	const TREND_TYPE_INCREASE = self::TREND . Service::INCREASE_SUFFIX;

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
	 * @var int
	 */
	private $columns = 0;

	/**
	 * @var array
	 */
	private $trends = [];

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

			$this->applyTrends(
				$country, $day, $type,
				$currentDay, $previousDay,
				$currentIncrease, $previousIncrease
			);

			$previous = $current;
			$previousDay = $currentDay;
			$previousIncrease = $currentIncrease;
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
		foreach ($this->getCountryNames() as $country)
		{
			$this->perCountries[$country] = [
				Service::TYPE_CONFIRMED => $this->confirmed[$country] ?? 0,
				Service::TYPE_DEATHS => $this->deaths[$country] ?? 0,
				Service::TYPE_RECOVERED => $this->recovered[$country] ?? 0,

				Service::TYPE_CONFIRMED_DAY => $this->confirmedDay[$country] ?? 0,
				Service::TYPE_DEATHS_DAY => $this->deathsDay[$country] ?? 0,
				Service::TYPE_RECOVERED_DAY => $this->recoveredDay[$country] ?? 0,

				Service::TYPE_CONFIRMED_INCREASE => $this->confirmedIncrease[$country] ?? 0,
				Service::TYPE_DEATHS_INCREASE => $this->deathsIncrease[$country] ?? 0,
				Service::TYPE_RECOVERED_INCREASE => $this->recoveredIncrease[$country] ?? 0,

				Service::TYPE_TRENDS => $this->trends[$country] ?? [],
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
	 * @param int $currentDay
	 * @param int $previousDay
	 * @param float $currentIncrease
	 * @param float $previousIncrease
	 */
	private function applyTrends(
		string $country, string $day, string $type,
		int $currentDay, int $previousDay,
		float $currentIncrease, float $previousIncrease
	): void
	{
		$positive = self::TREND_POSITIVE;
		$negative = self::TREND_NEGATIVE;

		if ($type === Service::TYPE_RECOVERED)
		{
			// increase in recoveries is a positive trend so we reverse it
			$positive = self::TREND_NEGATIVE;
			$negative = self::TREND_POSITIVE;
		}

		if ($previousDay != 0 || $currentDay != 0)
		{
			$this->trends[$country][$day][$type . Service::DAY_SUFFIX] = ($currentDay > $previousDay) ? $positive : $negative;
		}

		if ($previousIncrease != 0 || $currentIncrease != 0)
		{
			$this->trends[$country][$day][$type . Service::INCREASE_SUFFIX] = ($currentIncrease > $previousIncrease) ? $positive : $negative;
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
}
