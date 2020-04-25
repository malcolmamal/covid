<?php declare(strict_types=1);

namespace Covid\MachineLearning;

use Phpml\Regression\LeastSquares;
use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;

class Learning
{
	public static function testTrain()
	{
		//$regression = new SVR(Kernel::LINEAR, $degree = 3, $epsilon=10.0);

		$beforePrevious = 1;
		$previous = 1;
		$x = [[1], [2]];
		$y = [1, 1];
		$i = 3;
		foreach (range(3, 20) as $i)
		{
			$current = $beforePrevious + $previous;
			//var_dump($current);

			$beforePrevious = $previous;
			$previous = $current;

			$x[] = [$i];
			$y[] = $current;
		}


		$samples = [[[60], [61], [62], [63], [65]]];
		$targets = [[3.1, 3.6, 3.8, 4, 4.1]];

		$regression = new SVR(Kernel::LINEAR);
		$regression->train($samples, $targets);

		//$samples = [[60], [61], [62], [63], [65]];
		//$targets = [3.2, 3.7, 3.9, 4, 4.1];

		//$regression->train($samples, $targets);

		var_dump($regression->predict([66]));
		die();



		var_dump("next will be: " . ($beforePrevious + $previous));

		var_dump($x, $y);

		//$x = [[1], [2], [3], [4], [5], [6], [7], [8], [9]];
		//$y = [1, 1, 2, 3, 5, 8, 13, 21, 34];

		$regression = new LeastSquares();
		$regression->train($x, $y);
		echo $regression->predict([$i+2]);
	}
}
