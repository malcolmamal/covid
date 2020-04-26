<?php declare(strict_types=1);

namespace Covid\Service;

use Covid\Consts;
use Covid\Input\Data;
use Covid\Input\InputHandler;
use Covid\Output\Excel\ExcelGenerator;
use Covid\Output\Generator;

class Service
{
	/**
	 * @var Data
	 */
	private $data;

	/**
	 * @var Generator
	 */
	private $generator;

	/**
	 * @var InputHandler
	 */
	private $inputHandler;

	/**
	 * Service constructor.
	 *
	 * @param Generator $generator
	 * @param bool $downloadFiles
	 */
	public function __construct(Generator $generator, bool $downloadFiles = false)
	{
		$this->generator = $generator;

		$this->data = $generator->getData();
		$this->data->setExcelFriendly();
		$this->data->setAverageType($this->generator->getAverageType());

		$this->inputHandler = new InputHandler($this->data);

		if ($downloadFiles)
		{
			$this->inputHandler->downloadCsvFiles();
		}
	}

	/**
	 * @param bool $withProvinces
	 *
	 * @return array
	 */
	public function listCountries(bool $withProvinces = false): array
	{
		$this->inputHandler->readCsvFiles();

		$countryNames = $this->data->getCountryNames();

		if (!$withProvinces)
		{
			$separationString = ' - ';
			foreach ($countryNames as $key => $countryName)
			{
				if (strpos($countryName, $separationString) !== false)
				{
					unset($countryNames[$key]);
				}
			}
		}

		return $countryNames;
	}

	/**
	 * reading, processing, generating and saving
	 */
	public function generateOutput(): void
	{
		$this->inputHandler->readCsvFiles();

		$this->generator->setData($this->data);
		$this->generator->generate();
	}

	/**
	 * @return string
	 */
	public function getOutputResultLocation(): string
	{
		return realpath($this->generator->getOutputResultLocation());
	}

	/**
	 * all countries but no charts because it goes boom
	 *
	 * @return Service
	 */
	public function setAll(): self
	{
		$this->generator->setGenerateMode(Consts::GENERATE_FOR_ALL);

		return $this;
	}

	/**
	 * main preselected countries with charts
	 *
	 * @return Service
	 */
	public function setMain(): self
	{
		$this->generator->setGenerateMode(Consts::GENERATE_FOR_MAIN);

		return $this;
	}

	/**
	 * only few countries with charts, for speed
	 *
	 * @return Service
	 */
	public function setTest(): self
	{
		$this->generator->setGenerateMode(Consts::GENERATE_FOR_TEST);

		return $this;
	}
}
