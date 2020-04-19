<?php declare(strict_types=1);

namespace Covid;

use DateTimeImmutable;

abstract class CovidGenerator
{
	const GENERATE_FOR_ALL = 'all';
	const GENERATE_FOR_MAIN = 'main';
	const GENERATE_FOR_TEST = 'test';

	/**
	 * @var string
	 */
	protected $generateMode = self::GENERATE_FOR_MAIN;

	/**
	 * @var CovidData
	 */
	protected $data;

	abstract public function generate();

	abstract protected function saveData();

	/**
	 * @param string $generateMode
	 *
	 * @return CovidGenerator
	 */
	public function setGenerateMode(string $generateMode): self
	{
		$this->generateMode = $generateMode;

		return $this;
	}

	/**
	 * @param CovidData $data
	 *
	 * @return CovidGenerator
	 */
	public function setData(CovidData $data): self
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
		switch ($this->generateMode)
		{
			case self::GENERATE_FOR_ALL:
			{
				return $this->data->getCountryNames();
			}
			case self::GENERATE_FOR_TEST:
			{
				return $this->data->getTestCountryNames();
			}
			case self::GENERATE_FOR_MAIN:
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
		return CovidInputHandler::DATA_DIR . 'covid_' . $this->generateMode . '_' . date('Y-m-d');
	}
}
