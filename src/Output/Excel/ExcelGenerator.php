<?php declare(strict_types=1);

namespace Covid\Output\Excel;

use Covid\Input\Data;
use Covid\Output\Generator;
use Covid\Consts;
use Covid\Util\Util;
use PHPExcel;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Cell_DataType;
use PHPExcel_Chart;
use PHPExcel_Chart_DataSeries;
use PHPExcel_Chart_DataSeriesValues;
use PHPExcel_Chart_Legend;
use PHPExcel_Chart_PlotArea;
use PHPExcel_Chart_Title;
use PHPExcel_IOFactory;
use PHPExcel_Settings;
use PHPExcel_Style_Fill;
use PHPExcel_Style_NumberFormat;
use PHPExcel_Worksheet;
use PHPExcel_Writer_Abstract;

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
	];

	const TREND_BACKGROUND_COLORS = [
		Data::TREND_POSITIVE => 'ffb3b3', // red
		Data::TREND_NEGATIVE => 'b3ff99', // green
	];

	const FORMATTING_TYPE_DEFAULT = 'default';
	const FORMATTING_TYPE_NUMERIC = 'numeric';
	const FORMATTING_TYPE_PERCENTAGE = 'percentage';

	const NUMBER_FORMAT = '###,###,###';

	const MAIN_SHEET_NAME = 'Main';
	const CHART_CAPTION_CASES = 'Cases';
	const CHART_TITLE_ALL_COUNTRIES = 'Total cases for all countries';
	const CHART_TITLE_FOR_COUNTRY = 'Cases for ';

	/**
	 * @var PHPExcel
	 */
	private $document;

	/**
	 * @var array
	 */
	private $mainDataSeriesLabels = [];

	/**
	 * @var array
	 */
	private $mainDataSeriesValues = [];

	/**
	 * @var bool
	 */
	private $generateCharts = false;

	/**
	 * Creates an empty excel file.
	 *
	 * @return PHPExcel
	 */
	public function createNewFile()
	{
		PHPExcel_Settings::setCacheStorageMethod(
			PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip
		);

		// Create new PHPExcel object
		return new PHPExcel();
	}

	/**
	 * Sends a created excel file to the browser.
	 * @param PHPExcel $phpExcelFile - excel document
	 * @param string $fileName - file name
	 */
	public function sendExcelFile($phpExcelFile, $fileName)
	{
		$writer = PHPExcel_IOFactory::createWriter($phpExcelFile, 'Excel2007');
		// We'll be outputting an excel file
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		// It will be called file.xls
		header('Content-Disposition: attachment;filename="'.$fileName.'.xlsx"');
		// Write file to the browser
		$writer->save('php://output');
	}

	/**
	 * Generate the Excel file
	 */
	public function generate(): void
	{
		$this->document = $this->createNewFile();
		$this->document->getActiveSheet()->setTitle(self::MAIN_SHEET_NAME);

		foreach ($this->getCountriesForGeneration() as $country)
		{
			$this->createSheet($country);
			$this->generateDataForCountry($country);
		}

		if ($this->generateCharts)
		{
			$this->generateChartForAllCountries();
		}

		$this->document->setActiveSheetIndexByName(Data::COUNTRY_POLAND);

		$this->saveData();
	}

	/**
	 * @param string $generateMode
	 *
	 * @return Generator
	 */
	public function setGenerateMode(string $generateMode): Generator
	{
		parent::setGenerateMode($generateMode);

		$generateCharts = true;
		if ($generateMode === Generator::GENERATE_FOR_ALL)
		{
			$generateCharts = false;
		}
		$this->setGenerateCharts($generateCharts);

		return $this;
	}

	/**
	 * Save the actual data to a file
	 */
	protected function saveData(): void
	{
		$fileName = $this->prepareOutputFileName();

		/**
		 * @var $excelWriter PHPExcel_Writer_Abstract
		 */
		$excelWriter = PHPExcel_IOFactory::createWriter($this->document, 'Excel2007');
		$excelWriter->setIncludeCharts(TRUE);
		$excelWriter->save($fileName);
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
	 * @return PHPExcel_Worksheet
	 */
	private function createSheet(string $country): PHPExcel_Worksheet
	{
		$sheet = $this->document->createSheet();
		$sheet->setTitle($country);
		$sheet->freezePane('A2');

		$this->document->setActiveSheetIndexByName($country);

		$this->generateHeader();

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
		$row = 2; // because of header

		$dataForCountry = $this->data->getDataForCountry($country);

		if (!$this->validateDataForCountry($dataForCountry))
		{
			return;
		}

		$trendParams = [
			'country' => $country
		];

		foreach ($dataForCountry[Consts::TYPE_CONFIRMED] as $dateKey => $confirmed)
		{
			$trendParams['date'] = $dateKey;

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

			$this->writeCellNumberValue(self::COLUMN_CONFIRMED_INCREASE, $row, $dataForCountry[Consts::TYPE_CONFIRMED_INCREASE][$dateKey],
				$trendParams + ['type' => Consts::TYPE_CONFIRMED_DAY], self::FORMATTING_TYPE_PERCENTAGE);
			$this->writeCellNumberValue(self::COLUMN_DEATHS_INCREASE, $row, $dataForCountry[Consts::TYPE_DEATHS_INCREASE][$dateKey],
				$trendParams + ['type' => Consts::TYPE_DEATHS_INCREASE], self::FORMATTING_TYPE_PERCENTAGE);
			$this->writeCellNumberValue(self::COLUMN_RECOVERED_INCREASE, $row, $dataForCountry[Consts::TYPE_RECOVERED_INCREASE][$dateKey],
				$trendParams + ['type' => Consts::TYPE_RECOVERED_INCREASE], self::FORMATTING_TYPE_PERCENTAGE);

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

			$row++;
		}

		if ($this->generateCharts)
		{
			$this->generateChartForCountry($country, $row);
		}
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
	 * @param array $dataSeriesLabels
	 * @param array $xAxisTickValues
	 * @param array $dataSeriesValues
	 * @param string $title
	 *
	 * @return PHPExcel_Chart
	 */
	private function generateChart(array $dataSeriesLabels, array $xAxisTickValues, array $dataSeriesValues, string $title): PHPExcel_Chart
	{
		$series = new PHPExcel_Chart_DataSeries(
			PHPExcel_Chart_DataSeries::TYPE_SCATTERCHART,
			null,
			range(0, count($dataSeriesValues)-1),
			$dataSeriesLabels,
			$xAxisTickValues,
			$dataSeriesValues,
			null,
			null,
			PHPExcel_Chart_DataSeries::STYLE_MARKER
		);

		$chart = new PHPExcel_Chart(
			'chart',
			new PHPExcel_Chart_Title($title),
			new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_RIGHT, null, false),
			new PHPExcel_Chart_PlotArea(null, array($series)),
			true,
			0,
			null,
			new PHPExcel_Chart_Title(self::CHART_CAPTION_CASES)
		);

		return $chart;
	}

	/**
	 * @param string $country
	 * @param int $lastRow
	 */
	private function generateChartForCountry(string $country, int $lastRow): void
	{
		$range = $lastRow - 1; // excel range
		$entries = $lastRow - 2; // quantity

		$this->mainDataSeriesLabels[] = new PHPExcel_Chart_DataSeriesValues('String', null, null, 1, [$country]);
		$this->mainDataSeriesValues[] = new PHPExcel_Chart_DataSeriesValues('Number', "'" . $country . "'" . '!$B$2:$B$' . $range, null, $entries);

		$dataSeriesLabels = [
			new PHPExcel_Chart_DataSeriesValues('String', "'" . $country . "'" . '!$B$1', null, 1),
			new PHPExcel_Chart_DataSeriesValues('String', "'" . $country . "'" . '!$C$1', null, 1),
			new PHPExcel_Chart_DataSeriesValues('String', "'" . $country . "'" . '!$D$1', null, 1),
		];

		$xAxisTickValues = [
			new PHPExcel_Chart_DataSeriesValues('String', "'" . $country . "'" . '!$A$2:$A$' . $range, null, $entries),
		];

		$dataSeriesValues = [
			new PHPExcel_Chart_DataSeriesValues('Number', "'" . $country . "'" . '!$B$2:$B$' . $range, null, $entries),
			new PHPExcel_Chart_DataSeriesValues('Number', "'" . $country . "'" . '!$C$2:$C$' . $range, null, $entries),
			new PHPExcel_Chart_DataSeriesValues('Number', "'" . $country . "'" . '!$D$2:$D$' . $range, null, $entries),
		];

		$chart = $this->generateChart(
			$dataSeriesLabels, $xAxisTickValues, $dataSeriesValues,
			self::CHART_TITLE_FOR_COUNTRY . $country
		);

		$chart->setTopLeftPosition('O3');
		$chart->setBottomRightPosition('AM30');

		$this->document->getActiveSheet()->addChart($chart);
	}

	/**
	 * One big chart with all countries in it
	 */
	private function generateChartForAllCountries(): void
	{
		$chart = $this->generateChart(
			$this->mainDataSeriesLabels, [], $this->mainDataSeriesValues,
			self::CHART_TITLE_ALL_COUNTRIES
		);

		$chart->setTopLeftPosition('B2');
		$chart->setBottomRightPosition('AC58');

		$this->document->setActiveSheetIndexByName(self::MAIN_SHEET_NAME);

		$this->document->getActiveSheet()->addChart($chart);
	}

	/**
	 * @param string $columnKey
	 * @param int $row
	 * @param $value
	 * @param string $dataType
	 */
	private function writeCellValue(string $columnKey, int $row, $value, $dataType = PHPExcel_Cell_DataType::TYPE_STRING): void
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
	private function writeCellNumberValue(string $columnKey, int $row, $value, array $trends = [], $formatting = self::FORMATTING_TYPE_NUMERIC): void
	{
		$this->writeCellValue($columnKey, $row, $value, PHPExcel_Cell_DataType::TYPE_NUMERIC);

		$cellCoordinates = $this->getColumnNumberInExcelFormat($columnKey, $offsetBecauseOfInconsistency = 1) . $row;

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
			->getNumberFormat()->applyFromArray([
					'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00
				]
			);
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

		$this->document->getActiveSheet()->getStyle($cellCoordinates)->applyFromArray(
			[
				'fill' => [
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => ['rgb' => self::TREND_BACKGROUND_COLORS[$trend]]
				]
			]
		);
	}

	/**
	 * Header and columns width
	 */
	private function generateHeader(): void
	{
		$row = 1;
		foreach (self::COLUMNS as $columnName => $column)
		{
			$this->writeCellValue($columnName, $row, $columnName);
			$this->document->getActiveSheet()->getColumnDimension(self::convertColumnNumberToExcelFormat($column))
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
		return self::COLUMNS[$column] - 1; // because rows start from 1 but columns start from 0, really fucking nice :-)
	}

	/**
	 * @param string $column
	 * @param int $increaseBy
	 *
	 * @return string
	 */
	private function getColumnNumberInExcelFormat(string $column, $increaseBy = 0): string
	{
		return self::convertColumnNumberToExcelFormat($this->getColumnNumber($column) + $increaseBy);
	}

	/**
	 * @param int $number
	 *
	 * @return null|string
	 */
	public static function convertColumnNumberToExcelFormat(int $number): string
	{
		if (!isset($GLOBALS['column_number_to_excel_column_name_mapping']) || !Util::validArray($GLOBALS['column_number_to_excel_column_name_mapping']))
		{
			$GLOBALS['column_number_to_excel_column_name_mapping'] = array(
				'1'  => 'A', '2' => 'B', '3' => 'C', '4' => 'D',
				'5'  => 'E', '6' => 'F', '7' => 'G', '8' => 'H',
				'9'  => 'I', '10' => 'J', '11' => 'K', '12' => 'L',
				'13' => 'M', '14' => 'N', '15' => 'O', '16' => 'P',
				'17' => 'Q', '18' => 'R', '19' => 'S', '20' => 'T',
				'21' => 'U', '22' => 'V', '23' => 'W', '24' => 'X',
				'25' => 'Y', '26' => 'Z'
			);
		}
		$letters = $GLOBALS['column_number_to_excel_column_name_mapping'];

		if (!is_int($number) || $number <= 0)
		{
			return '';
		}
		elseif ($number <= 26)
		{
			return $letters[$number];
		}
		else
		{
			$i = $number / 26;
			$i = (int)floor($i);

			$j = $number - ($i * 26);
			$j = (int)$j;

			if ($number % 26 == 0)
			{
				$i--;
				$j = 26;
			}
			return $letters[$i] . $letters[$j];
		}
	}
}
