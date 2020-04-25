<?php declare(strict_types=1);

namespace Covid\Service;

use Covid\Input\Data;
use Covid\Input\InputHandler;
use Covid\Output\Generator;

class Service
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
	 */
	public function __construct(Generator $generator)
	{
		$this->generator = $generator;
		$this->data = new Data();
		$this->data->setExcelFriendly();

		$this->inputHandler = new InputHandler();

		$this->inputHandler->downloadCsvFiles();
	}

	/**
	 * reading, processing, generating and saving
	 */
	public function generateOutput(): void
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
		$this->generator->setGenerateMode(Generator::GENERATE_FOR_ALL);
	}

	/**
	 * main preselected countries with charts
	 */
	private function setMain(): void
	{
		$this->generator->setGenerateMode(Generator::GENERATE_FOR_MAIN);
	}

	/**
	 * only few countries with charts, for speed
	 */
	private function setTest(): void
	{
		$this->generator->setGenerateMode(Generator::GENERATE_FOR_TEST);
	}
}
