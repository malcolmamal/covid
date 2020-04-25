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
			->addOption('mode', 'm', InputOption::VALUE_OPTIONAL, '', Consts::GENERATE_FOR_MAIN)
			->addOption('download', 'd', InputOption::VALUE_NONE)
			->addOption('with-charts', 'c', InputOption::VALUE_NONE)
			->addOption('avg', 'a', InputOption::VALUE_OPTIONAL, '', Consts::DAYS_AVG_TYPE_WEEK)
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
				$input->getOption('with-charts'),
				$input->getOption('avg')
			))
		);
		$service->generateOutput();

		$output->writeln('Generated file located at ' . $service->getOutputPath());

		return 0;
	}
}
