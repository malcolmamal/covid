<?php declare(strict_types=1);

namespace Covid\Util\Command;

use Covid\Consts;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
	protected static $defaultName = Consts::COMMAND_TEST;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Configuration
	 */
	protected function configure(): void
	{
		$this
			->setDescription('Test data')
			->setHelp('test covid data')
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
		$this->doTest();

		return 0;
	}

	public function doTest(): void
	{
		// duh
	}
}
