<?php declare(strict_types=1);

namespace Covid\MachineLearning;

use Phpml\Regression\LeastSquares;
use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;

class Learning
{
	public static function testTrain(): void
	{
		$regression = new SVR(Kernel::LINEAR, $degree = 3, $epsilon = 10.0);

		$beforePrevious = 1;
		$previous = 1;
		$setX = [[1], [2]];
		$setY = [1, 1];
		$iterator = 3;
		foreach (range(3, 20) as $iterator)
		{
			$current = $beforePrevious + $previous;

			$beforePrevious = $previous;
			$previous = $current;

			$setX[] = [$iterator];
			$setY[] = $current;
		}


		$samples = [[[60], [61], [62], [63], [65]]];
		$targets = [[3.1, 3.6, 3.8, 4, 4.1]];

		$regression = new SVR(Kernel::LINEAR);
		$regression->train($samples, $targets);

		$samples = [[60], [61], [62], [63], [65]];
		$targets = [3.2, 3.7, 3.9, 4, 4.1];

		$regression->train($samples, $targets);

		print_r($regression->predict([66]));


		print_r("next will be: " . ($beforePrevious + $previous));

		print_r($setX);
		print_r($setY);

		$setX = [[1], [2], [3], [4], [5], [6], [7], [8], [9]];
		$setY = [1, 1, 2, 3, 5, 8, 13, 21, 34];

		$regression = new LeastSquares();
		$regression->train($setX, $setY);
		echo $regression->predict([$iterator + 2]);
	}
}
