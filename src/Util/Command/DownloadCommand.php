<?php declare(strict_types=1);

namespace Covid\Util\Command;

use Covid\Consts;
use Covid\Input\Data;
use Covid\Input\InputHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadCommand extends Command
{
	protected static $defaultName = Consts::COMMAND_DOWNLOAD;

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
			->setDescription('Download data')
			->setHelp('Downloads covid data')
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
		(new InputHandler(new Data()))->downloadCsvFiles();

		return 0;
	}
}
