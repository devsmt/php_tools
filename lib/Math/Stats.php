<?php
/**
 * Statistics Library.
 *
 */
class Stats {
    /**
     * Calculates the sum for a given set of values.
     *
     * @param array $values The input values
     * @return float|int The sum of values as an integer or float
     */
    public static function sum($values) {
        return array_sum($values);
    }
    /**
     * Calculates the minimum for a given set of values.
     *
     * @param array $values The input values
     * @return float|int The minimum of values as an integer or float
     */
    public static function min($values) {
        return min($values);
    }
    /**
     * Calculates the maximum for a given set of values.
     *
     * @param array $values The input values
     * @return float|int The maximum of values as an integer or float
     */
    public static function max($values) {
        return max($values);
    }
    /**
     * Calculates the mean for a given set of values.
     *
     * @param array $values The input values
     * @return float|int The mean of values as an integer or float
     */
    public static function mean($values) {
        $numberOfValues = count($values);
        return self::sum($values) / $numberOfValues;
    }
    /**
     * Calculates the frequency table for a given set of values.
     *
     * @param array $values The input values
     * @return array The frequency table
     */
    public static function frequency($values) {
        $frequency = [];
        foreach ($values as $value) {
            // Floats cannot be indices
            if (is_float($value)) {
                $value = strval($value);
            }
            if (!isset($frequency[$value])) {
                $frequency[$value] = 1;
            } else {
                $frequency[$value] += 1;
            }
        }
        asort($frequency);
        return $frequency;
    }
    /**
     * Calculates the mode for a given set of values.
     *
     * @param array $values The input values
     * @return float|int The mode of values as an integer or float
     * @throws \Oefenweb\Stats\StatsError
     */
    public static function mode($values) {
        $frequency = self::frequency($values);
        if (count($frequency) === 1) {
            return key($frequency);
        }
        $lastTwo = array_slice($frequency, -2, 2, true);
        $firstFrequency = current($lastTwo);
        $lastFrequency = next($lastTwo);
        if ($firstFrequency !== $lastFrequency) {
            return key($lastTwo);
        }
        throw new \Exception('There is not exactly one most common value.');
    }
    /**
     * Calculates the square of value - mean.
     *
     * @param float|int $value The input value
     * @param float|int $mean The mean
     * @return float|int The square of value - mean
     */
    protected static function squaredDifference($value, $mean) {
        return pow($value - $mean, 2);
    }
    /**
     * Calculates the variance for a given set of values.
     *
     * @param array $values The input values
     * @param bool $sample Whether or not to compensate for small samples (n - 1), defaults to true
     * @return float|int The variance of values as an integer or float
     */
    public static function variance($values, $sample = true) {
        $numberOfValues = count($values);
        $mean = self::mean($values);
        $squaredDifferences = [];
        foreach ($values as $value) {
            $squaredDifferences[] = self::squaredDifference($value, $mean);
        }
        $sumOfSquaredDifferences = self::sum($squaredDifferences);
        if ($sample) {
            $variance = $sumOfSquaredDifferences / ($numberOfValues - 1);
        } else {
            $variance = $sumOfSquaredDifferences / $numberOfValues;
        }
        return $variance;
    }
    /**
     * Calculates the standard deviation for a given set of values.
     *
     * @param array $values The input values
     * @param bool $sample Whether or not to compensate for small samples (n - 1), defaults to true
     * @return float|int The standard deviation of values as an integer or float
     */
    public static function standardDeviation($values, $sample = true) {
        return sqrt(self::variance($values, $sample));
    }
    /**
     * Calculates the range for a given set of values.
     *
     * @param array $values The input values
     * @return float|int The range of values as an integer or float
     */
    public static function range($values) {
        return self::max($values) - self::min($values);
    }
}
require_once __DIR__.'/../Test.php';
class StatsTest {
    /**
     * Tests `sum`.
     *
     *  Integer values.
     *
     * @return void
     */
    public function testSumIntegers() {
        $values = [1, 2, 3, 4, 4];
        $result = Stats::sum($values);
        $expected = 14;
        ok($expected, $result);
    }
    /**
     * Tests `sum`.
     *
     *  Float values.
     *
     * @return void
     */
    public function testSum() {
        $values = [-1.0, 2.5, 3.25, 5.75];
        $result = Stats::sum($values);
        $expected = 10.5;
        ok($expected, $result);
    }
    /**
     * Tests `sum`.
     *
     *  Mixed values.
     *
     * @return void
     */
    public function testSumMixed() {
        $values = [-2, 2.5, 3.25, 5.75, 0];
        $result = Stats::sum($values);
        $expected = 9.5;
        ok($expected, $result);
    }
    /**
     * Tests `min`.
     *
     *  Integer values.
     *
     * @return void
     */
    public function testMinIntegers() {
        $values = [1, 2, 3, 4, 4];
        $result = Stats::min($values);
        $expected = 1;
        ok($expected, $result);
    }
    /**
     * Test for `min`.
     *
     *  Float values.
     *
     * @return void
     */
    public function testMinIntegersFloats() {
        $values = [-1.0, 2.5, 3.25, 5.75];
        $result = Stats::min($values);
        $expected = -1.0;
        ok($expected, $result);
    }
    /**
     * Tests `max`.
     *
     *  Integer values.
     *
     * @return void
     */
    public function testMaxIntegers() {
        $values = [1, 2, 3, 4, 4];
        $result = Stats::max($values);
        $expected = 4;
        ok($expected, $result);
    }
    /**
     * Tests `max`.
     *
     *  Float values.
     *
     * @return void
     */
    public function testMaxFloats() {
        $values = [-1.0, 2.5, 3.25, 5.75];
        $result = Stats::max($values);
        $expected = 5.75;
        ok($expected, $result);
    }
    /**
     * Tests `mean`.
     *
     *  Integer values.
     *
     * @return void
     */
    public function testMeanIntegers() {
        $values = [1, 2, 3, 4, 4];
        $result = Stats::mean($values);
        $expected = 2.8;
        ok($expected, $result);
    }
    /**
     * Tests `mean`.
     *
     *  Float values.
     *
     * @return void
     */
    public function testMeanFloats() {
        $values = [-1.0, 2.5, 3.25, 5.75];
        $result = Stats::mean($values);
        $expected = 2.625;
        ok($expected, $result);
    }
    /**
     * Tests `mean`.
     *
     *  Mixed values.
     *
     * @return void
     */
    public function testMeanMixed() {
        $values = [-2, 2.5, 3.25, 5.75, 0];
        $result = Stats::mean($values);
        $expected = 1.9;
        ok($expected, $result);
    }
    /**
     * Tests `frequency`.
     *
     *  Integer values.
     *
     * @return void
     */
    public function testFrequencyIntegers() {
        $values = [1, 1, 2, 3, 3, 3, 3, 4];
        $result = Stats::frequency($values);
        $expected = [
            4 => 1,
            2 => 1,
            1 => 2,
            3 => 4,
        ];
        ok($expected, $result);
    }
    /**
     * Tests `frequency`.
     *
     *  Float values.
     *
     * @return void
     */
    public function testFrequencyFloats() {
        $values = [1, 3, 6, 6, 6, 6, 7.12, 7.12, 12, 12, 17];
        $result = Stats::frequency($values);
        $expected = [
            17 => 1,
            1 => 1,
            3 => 1,
            12 => 2,
            '7.12' => 2,
            6 => 4,
        ];
        ok($expected, $result);
    }
    /**
     * Tests `frequency`.
     *
     *  String values.
     *
     * @return void
     */
    public function testFrequencyStrings() {
        $values = ['red', 'blue', 'blue', 'red', 'green', 'red', 'red'];
        $result = Stats::frequency($values);
        $expected = [
            'green' => 1,
            'blue' => 2,
            'red' => 4,
        ];
        ok($expected, $result);
    }
    /**
     * Tests `mode`.
     *
     *  Integer values.
     *
     * @return void
     */
    public function testModeIntegers() {
        $values = [3];
        $result = Stats::mode($values);
        $expected = 3;
        ok($expected, $result);
        $values = [1, 1, 2, 3, 3, 3, 3, 4];
        $result = Stats::mode($values);
        $expected = 3;
        ok($expected, $result);
        $values = [1, 3, 6, 6, 6, 6, 7, 7, 12, 12, 17];
        $result = Stats::mode($values);
        $expected = 6;
        ok($expected, $result);
    }
    /**
     * Tests `mode`.
     *
     *  String values.
     *
     * @return void
     */
    public function testModeStrings() {
        $values = ['red', 'blue', 'blue', 'red', 'green', 'red', 'red'];
        $result = Stats::mode($values);
        $expected = 'red';
        ok($expected, $result);
    }
    /**
     * Tests `mode`.
     *
     * @return void
     */
    public function testModeNotExactlyOne() {
        $values = [1, 1, 2, 4, 4];
        Stats::mode($values);
    }
    /**
     * Tests `variance`.
     *
     *  Sample (default), integer values.
     *
     * @return void
     */
    public function testVarianceSampleIntegers() {
        $values = [2, 4, 4, 4, 5, 5, 7, 9];
        $sample = true;
        $result = Stats::variance($values, $sample);
        $expected = 4.571429;
        ok($expected, $result, '', pow(10, -4));
    }
    /**
     * Tests `variance`.
     *
     *  Sample (default), float values.
     *
     * @return void
     */
    public function testVarianceSampleFloats() {
        $values = [0.0, 0.25, 0.25, 1.25, 1.5, 1.75, 2.75, 3.25];
        $sample = true;
        $result = Stats::variance($values, $sample);
        $expected = 1.428571;
        ok($expected, $result, '', pow(10, -4));
    }
    /**
     * Tests `variance`.
     *
     *  Population, integer values.
     *
     * @return void
     */
    public function testVariancePopulationIntegers() {
        $values = [2, 4, 4, 4, 5, 5, 7, 9];
        $sample = false;
        $result = Stats::variance($values, $sample);
        $expected = 4;
        ok($expected, $result);
    }
    /**
     * Tests `variance`.
     *
     *  Population, float values.
     *
     * @return void
     */
    public function testVariancePopulationFloats() {
        $values = [0.0, 0.25, 0.25, 1.25, 1.5, 1.75, 2.75, 3.25];
        $sample = false;
        $result = Stats::variance($values, $sample);
        $expected = 1.25;
        ok($expected, $result, '', pow(10, -4));
    }
    /**
     * Tests `standardDeviation`.
     *
     *  Sample (default), integers values.
     *
     * @return void
     */
    public function testStandardDeviationSampleIntegers() {
        $values = [2, 4, 4, 4, 5, 5, 7, 9];
        $sample = true;
        $result = Stats::standardDeviation($values, $sample);
        $expected = 2.13809;
        ok($expected, $result, '', pow(10, -4));
    }
    /**
     * Tests `standardDeviation`.
     *
     *  Sample (default), float values.
     *
     * @return void
     */
    public function testStandardDeviationSampleFloats() {
        $values = [1.5, 2.5, 2.5, 2.75, 3.25, 4.75];
        $sample = true;
        $result = Stats::standardDeviation($values, $sample);
        $expected = 1.081087;
        ok($expected, $result, '', pow(10, -4));
    }
    /**
     * Tests `standardDeviation`.
     *
     *  Population, integer values.
     *
     * @return void
     */
    public function testStandardDeviationPopulationIntegers() {
        $values = [2, 4, 4, 4, 5, 5, 7, 9];
        $sample = false;
        $result = Stats::standardDeviation($values, $sample);
        $expected = 2.0;
        ok($expected, $result);
    }
    /**
     * Tests `standardDeviation`.
     *
     *  Population, floats values.
     *
     * @return void
     */
    public function testStandardDeviationPopulationFloats() {
        $values = [1.5, 2.5, 2.5, 2.75, 3.25, 4.75];
        $sample = false;
        $result = Stats::standardDeviation($values, $sample);
        $expected = 0.9868;
        ok($expected, $result, '', pow(10, -4));
    }
    /**
     * Tests `range`.
     *
     *  (Unsigned) integer values.
     *
     * @return void
     */
    public function testRangeIntUnsigned() {
        $values = [4, 6, 10, 15, 18];
        $result = Stats::range($values);
        $expected = 14;
        ok($expected, $result);
    }
    /**
     * Tests `range`.
     *
     *  (Signed) integer values.
     *
     * @return void
     */
    public function testRangeIntSigned() {
        $values = [4, 6, 10, 15, 18, -18];
        $result = Stats::range($values);
        $expected = 36;
        ok($expected, $result);
    }
    /**
     * Tests `range`.
     *
     *  Float values.
     *
     * @return void
     */
    public function testRangeFloats() {
        $values = [11, 13, 4.3, 15.5, 14];
        $result = Stats::range($values);
        $expected = 11.2;
        ok($expected, $result);
    }
    //
    public function run_all_tests() {
        $class_methods = get_class_methods($this);
        foreach ($class_methods as $method_name) {
            echo "$method_name\n";
            if (substr($method_name, 0, $len = strlen('test')) == 'test') {
                try {
                    $this->$method_name();
                } catch (\Exception $e) {
                    $fmt = 'Exception: %s  file:%s line:%s  trace: %s ';
                    $msg = sprintf($fmt, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
                    echo $msg;
                }
            }
        }
    }
}
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {

    require_once __DIR__ . '/../Test.php';

    ok(0, 0, 'ok for same value'); // should pass
    ok(0, null, 'type warning'); //should pass with type warning
    ok(['b' => 2, 'a' => 1], ['a' => 1, 'b' => '2']);
    ok([1, 2], [2, 1]);
    ok(['a', 'b'], ['b', 'a']);
    // this should be true in all impelemetations
    ok([1, 2], [1, 2]);
    ok(['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2]);
    ok('aaa000', '/^[A-Z0-1]*$/i');
    (new StatsTest)->run_all_tests();
    // @see https://github.com/montanaflynn/stats
}
