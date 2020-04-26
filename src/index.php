<?php declare(strict_types=1);

require_once 'version.php';

require 'autoload.php';

use Covid\Util\Command\DownloadCommand;
use Covid\Util\Command\GenerateCommand;
use Covid\Util\Command\ListCountriesCommand;
use Covid\Util\Command\MakeConfigCommand;
use Covid\Util\Command\TestCommand;
use Symfony\Component\Console\Application;

$application = new Application('Covid Data Generator', COVID_APP_VERSION);

$application->addCommands([
	new GenerateCommand(),
	new DownloadCommand(),
	new ListCountriesCommand(),
	new MakeConfigCommand(),
	new TestCommand(),
]);

$application->run();
