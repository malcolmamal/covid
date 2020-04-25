<?php declare(strict_types=1);

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
	(new Service(new ExcelGenerator(), true))->setTest()->generateOutput();
}
catch (Exception $e)
{
	var_dump($e->getMessage());
}
