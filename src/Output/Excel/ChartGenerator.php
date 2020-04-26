<?php declare(strict_types=1);

namespace Covid\Output\Excel;

use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ChartGenerator
{
	const CHART_CAPTION_CASES = 'Cases';
	const CHART_TITLE_ALL_COUNTRIES = 'Total cases for all countries';
	const CHART_TITLE_FOR_COUNTRY = 'Cases for ';

	/**
	 * @var array
	 */
	private $mainDataSeriesLabels = [];

	/**
	 * @var array
	 */
	private $mainDataSeriesValues = [];

	/**
	 * @var Spreadsheet
	 */
	private $document;

	/**
	 * ChartGenerator constructor.
	 *
	 * @param Spreadsheet $document
	 */
	public function __construct(Spreadsheet $document)
	{
		$this->document = $document;
	}

	/**
	 * @param array $dataSeriesLabels
	 * @param array $xAxisTickValues
	 * @param array $dataSeriesValues
	 * @param string $title
	 *
	 * @return Chart
	 */
	private function generateChart(array $dataSeriesLabels, array $xAxisTickValues, array $dataSeriesValues, string $title): Chart
	{
		$series = new DataSeries(
			DataSeries::TYPE_SCATTERCHART,
			null,
			range(0, count($dataSeriesValues)-1),
			$dataSeriesLabels,
			$xAxisTickValues,
			$dataSeriesValues,
			null,
			null,
			DataSeries::STYLE_MARKER
		);

		$chart = new Chart(
			'chart',
			new Title($title),
			new Legend(Legend::POSITION_TOP, null, false),
			new PlotArea(null, [$series]),
			true,
			0,
			null,
			new Title(self::CHART_CAPTION_CASES)
		);

		return $chart;
	}

	/**
	 * @param string $country
	 * @param int $lastRow
	 */
	public function generateChartForCountry(string $country, int $lastRow): void
	{
		$range = $lastRow - 1; // excel range
		$entries = $lastRow - 2; // quantity

		$this->mainDataSeriesValues[] = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'" . $country . "'" . '!$B$2:$B$' . $range, null, $entries);

		$dataSeriesLabels = [
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'" . $country . "'" . '!$B$1', null, 1),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'" . $country . "'" . '!$C$1', null, 1),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'" . $country . "'" . '!$D$1', null, 1),
		];

		$xAxisTickValues = [
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'" . $country . "'" . '!$A$2:$A$' . $range, null, $entries),
		];

		$dataSeriesValues = [
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'" . $country . "'" . '!$B$2:$B$' . $range, null, $entries),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'" . $country . "'" . '!$C$2:$C$' . $range, null, $entries),
			new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'" . $country . "'" . '!$D$2:$D$' . $range, null, $entries),
		];

		$chart = $this->generateChart(
			$dataSeriesLabels, $xAxisTickValues, $dataSeriesValues,
			self::CHART_TITLE_FOR_COUNTRY . $country
		);

		$chart->setTopLeftPosition('A' . ($range + 2));
		$chart->setBottomRightPosition('O' . ($range + 30));

		$this->document->getActiveSheet()->addChart($chart);
	}

	/**
	 * One big chart with all countries in it
	 *
	 * @param int $maxRow
	 */
	public function generateChartForAllCountries(int $maxRow): void
	{
		$mainSheetName = ExcelGenerator::MAIN_SHEET_NAME;
		$entries = count($this->mainDataSeriesValues);

		for ($i = 2; $i < ($entries + 2); $i++)
		{
			$this->mainDataSeriesLabels[] = new DataSeriesValues(
				DataSeriesValues::DATASERIES_TYPE_STRING, $mainSheetName . '!$R$' . $i, null, 1
			);
		}

		$chart = $this->generateChart(
			$this->mainDataSeriesLabels, [], $this->mainDataSeriesValues,
			self::CHART_TITLE_ALL_COUNTRIES
		);

		$chart->setTopLeftPosition('A' . ($maxRow + 2));
		$chart->setBottomRightPosition('O' . ($maxRow + 60));

		$this->document->setActiveSheetIndexByName($mainSheetName);

		$this->document->getActiveSheet()->addChart($chart);
	}
}
