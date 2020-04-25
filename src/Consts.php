<?php declare(strict_types=1);

namespace Covid;

class Consts
{
	const TYPE_CONFIRMED = 'confirmed';
	const TYPE_DEATHS = 'deaths';
	const TYPE_RECOVERED = 'recovered';

	const DAY_SUFFIX = '_day';
	const INCREASE_SUFFIX = '_increase';
	const AVERAGE_SUFFIX = '_avg';

	const TYPE_CONFIRMED_DAY = self::TYPE_CONFIRMED . self::DAY_SUFFIX;
	const TYPE_DEATHS_DAY = self::TYPE_DEATHS . self::DAY_SUFFIX;
	const TYPE_RECOVERED_DAY = self::TYPE_RECOVERED . self::DAY_SUFFIX;

	const TYPE_CONFIRMED_INCREASE = self::TYPE_CONFIRMED . self::INCREASE_SUFFIX;
	const TYPE_DEATHS_INCREASE = self::TYPE_DEATHS . self::INCREASE_SUFFIX;
	const TYPE_RECOVERED_INCREASE = self::TYPE_RECOVERED . self::INCREASE_SUFFIX;

	const TYPE_CONFIRMED_AVERAGE = self::TYPE_CONFIRMED . self::AVERAGE_SUFFIX;
	const TYPE_DEATHS_AVERAGE = self::TYPE_DEATHS . self::AVERAGE_SUFFIX;
	const TYPE_RECOVERED_AVERAGE = self::TYPE_RECOVERED . self::AVERAGE_SUFFIX;

	const TYPE_TRENDS = 'trends';

	const GENERATE_FOR_ALL = 'all';
	const GENERATE_FOR_MAIN = 'main';
	const GENERATE_FOR_TEST = 'test';

	const COMMAND_GENERATE = 'generate';
	const COMMAND_DOWNLOAD = 'download';
	const COMMAND_TEST = 'test';

	const DAYS_WEEK = 7;
	const DAYS_FORTNIGHT = 14;

	const DAYS_AVG_TYPE_WEEK = 'week';
	const DAYS_AVG_TYPE_FORTNIGHT = 'fortnight';
}
