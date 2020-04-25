<?php declare(strict_types=1);

namespace Covid;

class Consts
{
	const TYPE_CONFIRMED = 'confirmed';
	const TYPE_DEATHS = 'deaths';
	const TYPE_RECOVERED = 'recovered';

	const DAY_SUFFIX = '_day';
	const INCREASE_SUFFIX = '_increase';

	const TYPE_CONFIRMED_DAY = self::TYPE_CONFIRMED . self::DAY_SUFFIX;
	const TYPE_DEATHS_DAY = self::TYPE_DEATHS . self::DAY_SUFFIX;
	const TYPE_RECOVERED_DAY = self::TYPE_RECOVERED . self::DAY_SUFFIX;

	const TYPE_CONFIRMED_INCREASE = self::TYPE_CONFIRMED . self::INCREASE_SUFFIX;
	const TYPE_DEATHS_INCREASE = self::TYPE_DEATHS . self::INCREASE_SUFFIX;
	const TYPE_RECOVERED_INCREASE = self::TYPE_RECOVERED . self::INCREASE_SUFFIX;

	const TYPE_TRENDS = 'trends';
}
