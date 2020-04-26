<?php declare(strict_types=1);

namespace Covid\Output;

use Covid\Consts;
use Covid\Input\Data;
use Covid\Input\InputHandler;
use Covid\Util\Util;
use DateTimeImmutable;

abstract class Generator
{
	/**
	 * @var string
	 */
	protected $generateMode = Consts::GENERATE_FOR_MAIN;

	/**
	 * @var array
	 */
	protected $countriesToGenerate = [];

	/**
	 * @var string
	 */
	protected $averageType = Consts::DAYS_AVG_TYPE_WEEK;

	/**
	 * @var Data
	 */
	protected $data;

	/**
	 * @var string
	 */
	protected $outputPath;

	abstract public function generate();

	abstract protected function saveData();

	/**
	 * @param string $generateMode
	 * @param array $countries
	 * @param string $averageType
	 * @param bool $withCharts
	 *
	 * @return Generator
	 */
	public function setGenerateMode(
		string $generateMode,
		array $countries = [],
		string $averageType = Consts::DAYS_AVG_TYPE_WEEK,
		bool $withCharts = false
	): Generator
	{
		$this->generateMode = $generateMode;
		$this->countriesToGenerate = $countries;

		return $this;
	}

	/**
	 * @param Data $data
	 *
	 * @return Generator
	 */
	public function setData(Data $data): self
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * @param string $americanDate
	 *
	 * @return string
	 */
	protected function getProperlyFormattedDate(string $americanDate): string
	{
		return DateTimeImmutable::createFromFormat('m/d/y', $americanDate)->format('Y-m-d');
	}

	/**
	 * @return array
	 */
	protected function getCountriesForGeneration(): array
	{
		if (Util::validArray($this->countriesToGenerate))
		{
			$correctCountries = array_intersect($this->data->getCountryNames(), $this->countriesToGenerate);
			if (Util::validArray($correctCountries))
			{
				$this->countriesToGenerate = $correctCountries;
				$this->generateMode = Consts::GENERATE_FOR_MANUAL;
			}
		}

		switch ($this->generateMode)
		{
			case Consts::GENERATE_FOR_ALL:
			{
				return $this->data->getCountryNames();
			}
			case Consts::GENERATE_FOR_TEST:
			{
				return $this->data->getTestCountryNames();
			}
			case Consts::GENERATE_FOR_MANUAL:
			{
				if (count($this->countriesToGenerate) === 1)
				{
					$this->generateMode = strtolower(reset($this->countriesToGenerate));
				}

				return $this->countriesToGenerate;
			}
			case Consts::GENERATE_FOR_MAIN:
			default:
			{
				return $this->data->getMainCountryNames();
			}
		}
	}

	/**
	 * @return string
	 */
	protected function getBaseFileName(): string
	{
		return InputHandler::DATA_DIR . 'covid_' . $this->generateMode . '_' . date('Y-m-d');
	}

	/**
	 * @return string
	 */
	public function getOutputPath(): string
	{
		return $this->outputPath;
	}

	/**
	 * @return string
	 */
	public function getAverageType(): string
	{
		return $this->averageType;
	}
}
