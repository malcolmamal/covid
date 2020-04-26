<?php declare(strict_types=1);

namespace Covid\Util\Command;

use Covid\Consts;
use Covid\Output\Excel\ExcelGenerator;
use Covid\Service\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
	protected static $defaultName = Consts::COMMAND_GENERATE;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Configuration
	 */
	protected function configure()
	{
		$this
			->setDescription('Generate data')
			->setHelp('Generates covid data')
			->addOption('mode', 'm', InputOption::VALUE_OPTIONAL,
				'picks for which group of countries the data will be processed for: all|main|test',
				Consts::GENERATE_FOR_MAIN)
			->addOption('avg', 'a', InputOption::VALUE_OPTIONAL,
				'picks between the periods of rolling averages: week|fortnight', Consts::DAYS_AVG_TYPE_WEEK)
			->addOption('country', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
				'picks for which countries the data should be generated')
			->addOption('download', 'd', InputOption::VALUE_NONE,
				'decides whether the statistics data sources should also be downloaded')
			->addOption('with-charts', 'w', InputOption::VALUE_NONE,
				'decides whether the charts should be also generated')
		;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($input->getOption('download'))
		{
			$downloadCommand = $this->getApplication()->find(Consts::COMMAND_DOWNLOAD);
			$downloadCommand->run(
				new ArrayInput(['command' => Consts::COMMAND_DOWNLOAD,]),
				new NullOutput()
			);
		}

		$service = (new Service((
			new ExcelGenerator())->setGenerateMode(
				$input->getOption('mode'),
				$input->getOption('country'),
				$input->getOption('avg'),
				$input->getOption('with-charts')
			))
		);
		$service->generateOutput();

		$output->writeln('Generated file located at ' . $service->getOutputResultLocation());

		return 0;
	}
}
