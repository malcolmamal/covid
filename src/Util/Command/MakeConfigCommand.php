<?php declare(strict_types=1);

namespace Covid\Util\Command;

use Covid\Consts;
use Covid\Exception\FileException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeConfigCommand extends Command
{
	protected static $defaultName = Consts::COMMAND_MAKE_CONFIG;

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
			->setDescription('Makes a local config')
			->setHelp('Regenerates local config out of default config')
			->addOption('force', 'f', InputOption::VALUE_NONE,
				'overrides existing local config')
		;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 *
	 * @throws FileException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$defaultConfig = __DIR__ . '/../../../config.default.ini';
		if (!file_exists($defaultConfig))
		{
			throw new FileException('Default config not found: ' . realpath($defaultConfig));
		}

		$localConfig = __DIR__ . '/../../../config.ini';
		if (file_exists($localConfig) && !$input->getOption('force'))
		{
			$output->writeln('Local config already exists. Please remove it or use the --force');

			return 0;
		}

		$result = copy($defaultConfig, $localConfig);
		if (!$result)
		{
			throw new FileException('Failed creating local config. Might be permission issues.');
		}

		$contents = file($localConfig, FILE_IGNORE_NEW_LINES);
		if ($contents === false)
		{
			throw new FileException('Failed processing local config.');
		}

		$firstLine = array_shift($contents);
		$secondLine = array_shift($contents);

		if (!($firstLine !== null && strpos($firstLine, ';') === 0 && empty($secondLine)))
		{
			throw new FileException('Original default config might be corrupted or was modified.');
		}

		file_put_contents($localConfig, implode("\r\n", $contents));

		$output->writeln('Local config created at ' . realpath($localConfig));

		return 0;
	}
}
