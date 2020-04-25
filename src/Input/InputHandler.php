<?php declare(strict_types=1);

namespace Covid\Input;

use Covid\Consts;
use Covid\Exception\FileException;
use Covid\Util\Util;

/**
 * https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_time_series/time_series_covid19_confirmed_global.csv
 * https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_time_series/time_series_covid19_deaths_global.csv
 * https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_time_series/time_series_covid19_recovered_global.csv
 */

class InputHandler
{
	const TMP_DIR = '../temp'; // should be set to a place where we download and generate files
	const DATA_DIR = self::TMP_DIR . '/covid/';

	/**
	 * @var string
	 */
	private $baseDataPath = 'https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_time_series/';

	/**
	 * @var string
	 */
	private $prefixPath = 'time_series_covid19_';

	/**
	 * @var string
	 */
	private $suffixPath = '_global.csv';

	/**
	 * @var Data
	 */
	private $data;

	/**
	 * Downloads files from repository
	 */
	public function downloadCsvFiles(): void
	{
		Util::mkdir(self::DATA_DIR);

		foreach ([$this->getConfirmedPath(), $this->getDeathsPath(), $this->getRecoveredPath()] as $path)
		{
			$command = 'wget -O ' . self::DATA_DIR . $path . ' '. $this->baseDataPath . $path;
			exec($command);
		}
	}

	/**
	 * Reads all three types of csv data files
	 */
	public function readCsvFiles(): void
	{
		$this->readCsvFile(self::DATA_DIR . $this->getConfirmedPath(), Consts::TYPE_CONFIRMED);
		$this->readCsvFile(self::DATA_DIR . $this->getDeathsPath(), Consts::TYPE_DEATHS);
		$this->readCsvFile(self::DATA_DIR . $this->getRecoveredPath(), Consts::TYPE_RECOVERED);
	}

	/**
	 * @param string $filePath
	 * @param string $type
	 *
	 * @throws FileException
	 */
	private function readCsvFile(string $filePath, string $type): void
	{
		$row = 1;

		if (!file_exists($filePath))
		{
			throw new FileException('File not found: ' . $filePath . '. Try running the download command first.');
		}

		if (($handle = fopen($filePath, "r")) !== false)
		{
			while (($csvData = fgetcsv($handle, 1000, ",")) !== false)
			{
				if ($row === 1)
				{
					$this->data->addHeadline($csvData);
				}
				else
				{
					$this->data->addRow($csvData, $type);
				}

				$row++;
			}
			fclose($handle);
		}
	}

	/**
	 * @return string
	 */
	private function getConfirmedPath(): string
	{
		return $this->prefixPath . Consts::TYPE_CONFIRMED . $this->suffixPath;
	}

	/**
	 * @return string
	 */
	private function getDeathsPath(): string
	{
		return $this->prefixPath . Consts::TYPE_DEATHS . $this->suffixPath;
	}

	/**
	 * @return string
	 */
	private function getRecoveredPath(): string
	{
		return $this->prefixPath . Consts::TYPE_RECOVERED . $this->suffixPath;
	}

	/**
	 * @param Data $data
	 *
	 * @return InputHandler
	 */
	public function setData(Data $data): self
	{
		$this->data = $data;

		return $this;
	}
}
