<?php declare(strict_types=1);

require 'autoload.php';

use Covid\Util\Command\DownloadCommand;
use Covid\Util\Command\GenerateCommand;
use Covid\Util\Command\TestCommand;
use Symfony\Component\Console\Application;

$application = new Application('Covid Data Generator', COVID_APP_VERSION);

$application->addCommands([
	new GenerateCommand(),
	new DownloadCommand(),
	new TestCommand(),
]);

$application->run();