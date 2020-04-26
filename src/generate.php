<?php declare(strict_types=1);

use Covid\Input\Data;
use Covid\Output\Excel\ExcelGenerator;
use Covid\Service\Service;
use Covid\Exception\Exception;

ini_set('display_errors', "1");
ini_set('display_startup_errors', "1");
error_reporting(E_ALL);

set_time_limit(0);

include_once('autoload.php');

try
{
	(new Service(new ExcelGenerator(new Data()), true))->setTest()->generateOutput();
}
catch (Exception $e)
{
	print_r($e->getMessage());
}
