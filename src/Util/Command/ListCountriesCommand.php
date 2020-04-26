<?php declare(strict_types=1);

namespace Covid\Util\Command;

use Covid\Consts;
use Covid\Service\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCountriesCommand extends Command
{
	protected static $defaultName = Consts::COMMAND_LIST_COUNTRIES;

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
			->setDescription('List countries')
			->setHelp('List countries available for use')
			->addOption('with-provinces', 'p', InputOption::VALUE_NONE,
				'also includes provinces on the list')
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
		$service = new Service();
		$countries = $service->listCountries($input->getOption('with-provinces'));

		$output->writeln('Available countries: ' . implode(', ', $countries));

		return 0;
	}
}
