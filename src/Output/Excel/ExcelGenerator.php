<?php declare(strict_types=1);

namespace Covid\Output\Excel;

use Covid\Input\Data;
use Covid\Output\Generator;
use Covid\Consts;
use Covid\Util\Util;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelGenerator extends Generator
{
	const COLUMN_DATE = 'Date';

	const COLUMN_CONFIRMED_TOTAL = 'Total Confirmed';
	const COLUMN_DEATHS_TOTAL = 'Total Deaths';
	const COLUMN_RECOVERED_TOTAL = 'Total Recovered';

	const COLUMN_CONFIRMED_DAY = 'New Confirmed';
	const COLUMN_DEATHS_DAY = 'New Deaths';
	const COLUMN_RECOVERED_DAY = 'New Recovered';

	const COLUMN_CONFIRMED_INCREASE = 'Confirmed Increase';
	const COLUMN_DEATHS_INCREASE = 'Deaths Increase';
	const COLUMN_RECOVERED_INCREASE = 'Recovered Increase';

	const COLUMN_RATIO_DEATHS = 'Deaths %';
	const COLUMN_RATIO_RECOVERED = 'Recovered %';
	const COLUMN_RATIO_DEATHS_FROM_CLOSED = 'Deaths % From Closed Cases';

	const COLUMN_CONFIRMED_AVG = 'Confirmed Average';
	const COLUMN_DEATHS_AVG = 'Deaths Average';
	const COLUMN_RECOVERED_AVG = 'Recovered Average';

	const COLUMN_MAIN_COUNTRY_NAMES = 'Countries';

	const PREFIX_MAIN_GLOBAL = 'Global ';

	const COLUMN_MAIN_CONFIRMED_TOTAL = self::PREFIX_MAIN_GLOBAL . self::COLUMN_CONFIRMED_TOTAL;
	const COLUMN_MAIN_DEATHS_TOTAL = self::PREFIX_MAIN_GLOBAL . self::COLUMN_DEATHS_TOTAL;
	const COLUMN_MAIN_RECOVERED_TOTAL = self::PREFIX_MAIN_GLOBAL . self::COLUMN_RECOVERED_TOTAL;

	const COLUMNS = [
		self::COLUMN_DATE => 1,

		self::COLUMN_CONFIRMED_TOTAL => 2,
		self::COLUMN_DEATHS_TOTAL => 3,
		self::COLUMN_RECOVERED_TOTAL => 4,

		self::COLUMN_CONFIRMED_DAY => 5,
		self::COLUMN_DEATHS_DAY => 6,
		self::COLUMN_RECOVERED_DAY => 7,

		self::COLUMN_CONFIRMED_INCREASE => 8,
		self::COLUMN_DEATHS_INCREASE => 9,
		self::COLUMN_RECOVERED_INCREASE => 10,

		self::COLUMN_RATIO_DEATHS => 11,
		self::COLUMN_RATIO_RECOVERED => 12,
		self::COLUMN_RATIO_DEATHS_FROM_CLOSED => 13,

		self::COLUMN_CONFIRMED_AVG => 14,
		self::COLUMN_DEATHS_AVG => 15,
		self::COLUMN_RECOVERED_AVG => 16,

		/**
		 * gap
		 */

		self::COLUMN_MAIN_COUNTRY_NAMES => 18,
		self::COLUMN_MAIN_CONFIRMED_TOTAL => 19,
		self::COLUMN_MAIN_DEATHS_TOTAL => 20,
		self::COLUMN_MAIN_RECOVERED_TOTAL => 21,
	];

	const TREND_BACKGROUND_COLORS = [
		Data::TREND_POSITIVE => 'ffb3b3', // red
		Data::TREND_NEGATIVE => 'b3ff99', // green
	];

	const FORMATTING_TYPE_DEFAULT = 'default';
	const FORMATTING_TYPE_NUMERIC = 'numeric';
	const FORMATTING_TYPE_PERCENTAGE = 'percentage';

	const NUMBER_FORMAT = '###,###,###'; // alternatively: '#,##0'

	const MAIN_SHEET_NAME = Consts::MAIN_SECTION;

	/**
	 * @var Spreadsheet
	 */
	private $document;

	/**
	 * @var bool
	 */
	private $generateCharts = false;

	/**
	 * @var ChartGenerator
	 */
	private $chartGenerator;

	/**
	 * @var int
	 */
	private $maxRow = 2;

	/**
	 * @var array
	 */
	private $currentTotals = [];

	/**
	 * Generate the Excel file
	 */
	public function generate(): void
	{
		$this->document = new Spreadsheet();
		$this->document->getActiveSheet()->setTitle(self::MAIN_SHEET_NAME);

		if ($this->generateCharts)
		{
			$this->chartGenerator = new ChartGenerator($this->document);
		}

		$iterator = 0;
		$countries = array_merge($this->getCountriesForGeneration(), [self::MAIN_SHEET_NAME]);
		foreach ($countries as $country)
		{
			$this->createSheet($country);
			$this->generateDataForCountry($country);
			$this->addCountryDataToMainSheet($country, $iterator);

			$iterator++;
		}

		if ($this->generateCharts)
		{
			$this->chartGenerator->generateChartForAllCountries($this->maxRow);
		}

		$this->document->setActiveSheetIndexByName(Data::COUNTRIES_ALL);
		if ($this->document->sheetNameExists(Data::COUNTRY_POLAND))
		{
			/**
			 * I'm not sorry, if Poland is on the list, it comes first ;-)
			 */
			$this->document->setActiveSheetIndexByName(Data::COUNTRY_POLAND);
		}

		$this->saveData();
	}

	/**
	 * @param string $country
	 * @param int $row
	 */
	private function addCountryDataToMainSheet(string $country, int $row): void
	{
		if ($country === self::MAIN_SHEET_NAME)
		{
			return;
		}

		$this->document->setActiveSheetIndexByName(self::MAIN_SHEET_NAME);
		$this->writeCellValue(self::COLUMN_MAIN_COUNTRY_NAMES, ($row + 2), $country);

		$this->writeCellNumberValue(self::COLUMN_MAIN_CONFIRMED_TOTAL, ($row + 2), $this->currentTotals[Consts::TYPE_CONFIRMED]);
		$this->writeCellNumberValue(self::COLUMN_MAIN_DEATHS_TOTAL, ($row + 2), $this->currentTotals[Consts::TYPE_DEATHS]);
		$this->writeCellNumberValue(self::COLUMN_MAIN_RECOVERED_TOTAL, ($row + 2), $this->currentTotals[Consts::TYPE_RECOVERED]);
	}

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
		parent::setGenerateMode($generateMode, $countries);

		if ($generateMode === Consts::GENERATE_FOR_ALL)
		{
			$withCharts = false;
		}
		$this->setGenerateCharts($withCharts);

		$this->setAverageType($averageType);

		return $this;
	}

	/**
	 * Save the actual data to a file
	 */
	protected function saveData(): void
	{
		$fileName = $this->prepareOutputFileName();

		$excelWriter = new Xlsx($this->document);

		if ($this->generateCharts)
		{
			$excelWriter->setIncludeCharts(true);
		}

		$excelWriter->save($fileName);

		$this->outputResultLocation = $fileName;
	}

	/**
	 * @return string
	 */
	private function prepareOutputFileName(): string
	{
		return $this->getBaseFileName() . '.xlsx';
	}

	/**
	 * @param string $country
	 *
	 * @return Worksheet
	 */
	private function createSheet(string $country): Worksheet
	{
		if ($this->document->sheetNameExists($country))
		{
			$sheet = $this->document->getSheetByName($country);
		}
		else
		{
			$sheet = $this->document->createSheet();
			$sheet->setTitle($country);
		}
		$sheet->freezePane('A2');

		$this->document->setActiveSheetIndexByName($country);

		$this->generateHeader($country);

		return $sheet;
	}

	/**
	 * @param array|null $dataForCountry
	 *
	 * @return bool
	 */
	private function validateDataForCountry(array $dataForCountry): bool
	{
		if (empty($dataForCountry))
		{
			return false;
		}

		if (empty($dataForCountry[Consts::TYPE_CONFIRMED]))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param string $country
	 */
	private function generateDataForCountry(string $country): void
	{
		$row = 2; // because of the header
		$this->maxRow = $row;

		$this->currentTotals = [
			Consts::TYPE_CONFIRMED => 0,
			Consts::TYPE_DEATHS => 0,
			Consts::TYPE_RECOVERED => 0,
		];

		$dataForCountry = $this->data->getDataForCountry($country);

		if (!$this->validateDataForCountry($dataForCountry))
		{
			return;
		}

		foreach ($dataForCountry[Consts::TYPE_CONFIRMED] as $dateKey => $confirmed)
		{
			$this->currentTotals = $this->addRowForCountry($country, $dataForCountry, $dateKey, $row);
		}

		if ($this->generateCharts && $country != Data::COUNTRIES_ALL)
		{
			$this->chartGenerator->generateChartForCountry($country, $row);
		}

		$this->maxRow = $row;
	}

	/**
	 * @param string $country
	 * @param array $dataForCountry
	 * @param string $dateKey
	 * @param int $row
	 *
	 * @return array
	 */
	private function addRowForCountry(string $country, array $dataForCountry, string $dateKey, int &$row): array
	{
		$trendParams = [
			'country' => $country,
			'date' => $dateKey
		];

		$this->writeCellValue(self::COLUMN_DATE, $row, $this->getProperlyFormattedDate($dateKey));

		$confirmedTotal = $dataForCountry[Consts::TYPE_CONFIRMED][$dateKey];
		$deathsTotal = $dataForCountry[Consts::TYPE_DEATHS][$dateKey];
		$recoveredTotal = $dataForCountry[Consts::TYPE_RECOVERED][$dateKey];

		$this->writeCellNumberValue(self::COLUMN_CONFIRMED_TOTAL, $row, $confirmedTotal);
		$this->writeCellNumberValue(self::COLUMN_DEATHS_TOTAL, $row, $deathsTotal);
		$this->writeCellNumberValue(self::COLUMN_RECOVERED_TOTAL, $row, $recoveredTotal);

		$this->writeCellNumberValue(self::COLUMN_CONFIRMED_DAY, $row, $dataForCountry[Consts::TYPE_CONFIRMED_DAY][$dateKey],
			$trendParams + ['type' => Consts::TYPE_CONFIRMED_DAY]);
		$this->writeCellNumberValue(self::COLUMN_DEATHS_DAY, $row, $dataForCountry[Consts::TYPE_DEATHS_DAY][$dateKey],
			$trendParams + ['type' => Consts::TYPE_DEATHS_DAY]);
		$this->writeCellNumberValue(self::COLUMN_RECOVERED_DAY, $row, $dataForCountry[Consts::TYPE_RECOVERED_DAY][$dateKey],
			$trendParams + ['type' => Consts::TYPE_RECOVERED_DAY]);

		/**
		 * @TODO: maybe apply formula instead? then we could also have data for COUNTRIES_ALL
		 */
		if ($country != Data::COUNTRIES_ALL)
		{
			$this->writeCellNumberValue(self::COLUMN_CONFIRMED_INCREASE, $row, $dataForCountry[Consts::TYPE_CONFIRMED_INCREASE][$dateKey],
				$trendParams + ['type' => Consts::TYPE_CONFIRMED_DAY], self::FORMATTING_TYPE_PERCENTAGE);
			$this->writeCellNumberValue(self::COLUMN_DEATHS_INCREASE, $row, $dataForCountry[Consts::TYPE_DEATHS_INCREASE][$dateKey],
				$trendParams + ['type' => Consts::TYPE_DEATHS_INCREASE], self::FORMATTING_TYPE_PERCENTAGE);
			$this->writeCellNumberValue(self::COLUMN_RECOVERED_INCREASE, $row, $dataForCountry[Consts::TYPE_RECOVERED_INCREASE][$dateKey],
				$trendParams + ['type' => Consts::TYPE_RECOVERED_INCREASE], self::FORMATTING_TYPE_PERCENTAGE);
		}

		$deathsPercentage = 0;
		$recoveredPercentage = 0;
		if ($confirmedTotal != 0)
		{
			$deathsPercentage = $deathsTotal / $confirmedTotal;
			$recoveredPercentage = $recoveredTotal / $confirmedTotal;
		}

		$deathsPercentageFromClosed = 0;
		$finalizedTotal = $deathsTotal + $recoveredTotal;
		if ($finalizedTotal != 0)
		{
			$deathsPercentageFromClosed = $deathsTotal / $finalizedTotal;
		}

		$this->writeCellNumberValue(self::COLUMN_RATIO_DEATHS, $row, $deathsPercentage,
			[], self::FORMATTING_TYPE_PERCENTAGE);
		$this->writeCellNumberValue(self::COLUMN_RATIO_RECOVERED, $row, $recoveredPercentage,
			[], self::FORMATTING_TYPE_PERCENTAGE);

		$this->writeCellNumberValue(self::COLUMN_RATIO_DEATHS_FROM_CLOSED, $row, $deathsPercentageFromClosed,
			[], self::FORMATTING_TYPE_PERCENTAGE);

		if ($country === Data::COUNTRIES_ALL)
		{
			$row++;

			return [];
		}

		$this->writeCellNumberValue(self::COLUMN_CONFIRMED_AVG, $row,
			$this->data->getRollingAverageValue($country, Consts::TYPE_CONFIRMED, $dateKey, $this->averageType),
			$trendParams + ['type' => Consts::TYPE_CONFIRMED_AVERAGE]);
		$this->writeCellNumberValue(self::COLUMN_DEATHS_AVG, $row,
			$this->data->getRollingAverageValue($country, Consts::TYPE_DEATHS, $dateKey, $this->averageType),
			$trendParams + ['type' => Consts::TYPE_DEATHS_AVERAGE]);
		$this->writeCellNumberValue(self::COLUMN_RECOVERED_AVG, $row,
			$this->data->getRollingAverageValue($country, Consts::TYPE_RECOVERED, $dateKey, $this->averageType),
			$trendParams + ['type' => Consts::TYPE_RECOVERED_AVERAGE]);

		$row++;

		return [
			Consts::TYPE_CONFIRMED => $confirmedTotal,
			Consts::TYPE_DEATHS => $deathsTotal,
			Consts::TYPE_RECOVERED => $recoveredTotal,
		];
	}

	/**
	 * @param bool $generateCharts
	 *
	 * @return ExcelGenerator
	 */
	public function setGenerateCharts(bool $generateCharts): self
	{
		$this->generateCharts = $generateCharts;

		return $this;
	}

	/**
	 * @param string $averageType
	 *
	 * @return ExcelGenerator
	 */
	public function setAverageType(string $averageType): self
	{
		if (in_array($averageType, [Consts::DAYS_AVG_TYPE_WEEK, Consts::DAYS_AVG_TYPE_FORTNIGHT]))
		{
			$this->averageType = $averageType;
		}

		return $this;
	}

	/**
	 * @param string $columnKey
	 * @param int $row
	 * @param $value
	 * @param string $dataType
	 */
	private function writeCellValue(string $columnKey, int $row, $value, $dataType = DataType::TYPE_STRING): void
	{
		$this->document->getActiveSheet()->setCellValueExplicitByColumnAndRow($this->getColumnNumber($columnKey), $row, $value, $dataType);
	}

	/**
	 * @param string $columnKey
	 * @param int $row
	 * @param $value
	 * @param array $trends
	 * @param string $formatting
	 */
	private function writeCellNumberValue(string $columnKey, int $row, $value, array $trends = [], string $formatting = self::FORMATTING_TYPE_NUMERIC): void
	{
		$this->writeCellValue($columnKey, $row, $value, DataType::TYPE_NUMERIC);

		$cellCoordinates = $this->getColumnNumberInExcelFormat($columnKey) . $row;

		switch ($formatting)
		{
			case self::FORMATTING_TYPE_PERCENTAGE:
			{
				$this->applyPercentageFormatting($cellCoordinates);

				break;
			}
			case self::FORMATTING_TYPE_NUMERIC:
			{
				$this->applyNumericFormatting($cellCoordinates);

				break;
			}
		}

		if (!empty($trends))
		{
			$this->applyTrendBackground($cellCoordinates, $trends);
		}
	}

	/**
	 * @param string $cellCoordinates
	 */
	private function applyPercentageFormatting(string $cellCoordinates): void
	{
		$this->document->getActiveSheet()->getStyle($cellCoordinates)
			->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
	}

	/**
	 * @param string $cellCoordinates
	 */
	private function applyNumericFormatting(string $cellCoordinates): void
	{
		$this->document->getActiveSheet()->getStyle($cellCoordinates)
			->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
	}

	/**
	 * @param string $cellCoordinates
	 * @param array $trends
	 */
	private function applyTrendBackground(string $cellCoordinates, array $trends): void
	{
		$trend = $this->data->getSpecificTrend($trends['country'], $trends['date'], $trends['type']);
		if (empty($trend))
		{
			return;
		}

		$this->document->getActiveSheet()->getStyle($cellCoordinates)
			->getFill()->setFillType(Fill::FILL_SOLID)
			->getStartColor()->setARGB(self::TREND_BACKGROUND_COLORS[$trend]);
	}

	/**
	 * Header and columns width
	 *
	 * @param string $country
	 */
	private function generateHeader(string $country): void
	{
		$row = 1;

		/**
		 * @var int $column
		 */
		foreach (self::COLUMNS as $columnName => $column)
		{
			if (in_array(
					$column, [
						self::COLUMNS[self::COLUMN_MAIN_COUNTRY_NAMES],
						self::COLUMNS[self::COLUMN_MAIN_CONFIRMED_TOTAL],
						self::COLUMNS[self::COLUMN_MAIN_DEATHS_TOTAL],
						self::COLUMNS[self::COLUMN_MAIN_RECOVERED_TOTAL],
					]
				)
				&& $country != self::MAIN_SHEET_NAME)
			{
				continue;
			}

			$this->writeCellValue($columnName, $row, $columnName);
			$this->document->getActiveSheet()->getColumnDimension(ExcelHelper::convertColumnNumberToExcelFormat($column))
				->setAutoSize(true);
		}
		$this->document->getActiveSheet()->calculateColumnWidths();
	}

	/**
	 * @param string $column
	 *
	 * @return int
	 */
	private function getColumnNumber(string $column): int
	{
		return self::COLUMNS[$column];
	}

	/**
	 * @param string $column
	 *
	 * @return string
	 */
	private function getColumnNumberInExcelFormat(string $column): string
	{
		return ExcelHelper::convertColumnNumberToExcelFormat($this->getColumnNumber($column));
	}
}
