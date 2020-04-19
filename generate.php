<?php declare(strict_types=1);

ini_set('display_errors', "1");
ini_set('display_startup_errors', "1");
error_reporting(E_ALL);

include_once('autoload.php');

try
{
	(new Covid\CovidService())->createExcel();
}
catch (Exception $e)
{
	var_dump($e->getMessage());
}
