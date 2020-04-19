<?php declare(strict_types=1);

namespace Covid;

class CovidService
{
	const TYPE_CONFIRMED = 'confirmed';
	const TYPE_DEATHS = 'deaths';
	const TYPE_RECOVERED = 'recovered';

	const DAY_SUFFIX = '_day';
	const INCREASE_SUFFIX = '_increase';

	const TYPE_CONFIRMED_DAY = self::TYPE_CONFIRMED . self::DAY_SUFFIX;
	const TYPE_DEATHS_DAY = self::TYPE_DEATHS . self::DAY_SUFFIX;
	const TYPE_RECOVERED_DAY = self::TYPE_RECOVERED . self::DAY_SUFFIX;

	const TYPE_CONFIRMED_INCREASE = self::TYPE_CONFIRMED . self::INCREASE_SUFFIX;
	const TYPE_DEATHS_INCREASE = self::TYPE_DEATHS . self::INCREASE_SUFFIX;
	const TYPE_RECOVERED_INCREASE = self::TYPE_RECOVERED . self::INCREASE_SUFFIX;

	const TYPE_TRENDS = 'trends';

	/**
	 * @var CovidData
	 */
	private $data;

	/**
	 * @var CovidGenerator
	 */
	private $generator;

	/**
	 * @var CovidInputHandler
	 */
	private $inputHandler;

	public function __construct()
	{
		set_time_limit(0);

		$this->generator = new CovidExcelGenerator();
		$this->data = new CovidData();
		$this->data->setExcelFriendly();

		$this->inputHandler = new CovidInputHandler();

		$this->inputHandler->downloadCsvFiles();
	}

	/**
	 * reading, processing, generating and saving
	 */
	public function createExcel(): void
	{
		//CovidLearning::testTrain();

		$this->inputHandler->setData($this->data);
		$this->inputHandler->readCsvFiles();
		$this->data->arrangeData();

		$this->setMain();

		$this->generator->setData($this->data);
		$this->generator->generate();
	}

	/**
	 * all countries but no charts because it goes boom
	 */
	private function setAll(): void
	{
		$this->generator->setGenerateMode(CovidGenerator::GENERATE_FOR_ALL);
		$this->generator->setGenerateCharts(false);
	}

	/**
	 * main preselected countries with charts
	 */
	private function setMain(): void
	{
		$this->generator->setGenerateMode(CovidGenerator::GENERATE_FOR_MAIN);
		$this->generator->setGenerateCharts(true);
	}

	/**
	 * only few countries with charts, for speed
	 */
	private function setTest(): void
	{
		$this->generator->setGenerateMode(CovidGenerator::GENERATE_FOR_TEST);
		$this->generator->setGenerateCharts(true);
	}
}
