<?php declare(strict_types=1);

namespace Covid\Service;

use Covid\Consts;
use Covid\Input\Data;
use Covid\Input\InputHandler;
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
		$this->data = new Data();
		$this->data->setExcelFriendly();

		$this->inputHandler = new InputHandler();

		if ($downloadFiles)
		{
			$this->inputHandler->downloadCsvFiles();
		}
	}

	/**
	 * reading, processing, generating and saving
	 */
	public function generateOutput(): void
	{
		//Learning::testTrain();

		$this->inputHandler->setData($this->data);
		$this->inputHandler->readCsvFiles();
		$this->data->arrangeData();

		$this->generator->setData($this->data);
		$this->generator->generate();
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
