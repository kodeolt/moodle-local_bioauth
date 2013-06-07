<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of functions for the bioauth module.
 *
 * This contains functions that are called also from outside the biaouth module
 * Functions that are only called by the biaouth module itself are in {@link locallib.php}
 *
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Subtract and square two numbers (Used in euclidean_distance)
 *
 * @param array $n
 * @param array $n
 * @return (n - m)**2
 */
function subtract_and_square($n, $m) {
    return (pow($n - $m, 2));
}

/**
 * Compute the Euclidean distance between two vectors.
 * This takes a multidimensional array as a parameter since it is mapped to 
 * several arrays. The alternative would be to transpose the arrays and used a
 * distance function which takes two args. See sorted_distances
 *
 * @param array $arr
 * @return int the Euclidean distance between $a[0] and $a[1]
 */
function euclidean_distance(&$arr) {
    $c = array_map("subtract_and_square", $arr[0], $arr[1]);

    return pow(array_sum($c), .5);
}

/**
 * Compute the absolute value of the element-wise difference between two vectors
 * 
 * @param array $arr1
 * @param array $arr1
 * @return array the absolute difference between $arr1 and $arr2
 */
function abs_diff($arr1, $arr2) {
    $ret = array();
    foreach ($arr1 as $key => $value) {
        $ret[$key] = abs($arr2[$key] - $arr1[$key]);
    }

    return $ret;
}

/**
 * Compute the average value of a vector
 * 
 * @param array $arr
 * @return int the average value of $arr
 */
function average(&$arr) {
    if (count($arr) < 1)
        return 0;

    return (double)array_sum($arr) / count($arr);
}

/**
 * Compute the variance of a vector
 * 
 * @param array $arr
 * @return int the variance of $arr
 */
function variance(&$arr) {
    if (count($arr) < 2)
        return 0;

    $mean = average($arr);
    $square_diffs = array_map(function($x) use (&$mean) {
        return pow($x - $mean, 2);
    }, $arr);

    return array_sum($square_diffs) / (count($arr) - 1);
}

/**
 * Generate Gaussian pseudo-random numbers sampled from the normal distribution 
 * with mean $mean and standard deviation $std, or N($mean, $std).
 * 
 * This is the polar form of the Box-Muller transformation (faster, more robust
 * than the basic form). 
 * 
 * @param int $mean the mean of the distribution to draw from
 * @param int $std the standard deviation of the distribution to draw from
 * @return array containing two Gaussian pseudo-random numbers
 */
function random_normal($mean, $std) {
    do {
        $x1 = 2.0 * (mt_rand() / mt_getrandmax()) - 1.0;
        $x2 = 2.0 * (mt_rand() / mt_getrandmax()) - 1.0;
        $w = $x1 * $x1 + $x2 * $x2;
    } while ($w >= 1.0);

    $w = sqrt((-2.0 * log($w)) / $w);
    $y1 = $std * $x1 * $w + $mean;
    $y2 = $std * $x2 * $w + $mean;

    return array($y1, $y2);
}

/**
 * Generate an array of n Gaussian pseudo-random numbers
 * 
 * @param int $n the size of the random array
 * @param int $mean the mean of the distribution to draw from
 * @param int $std the standard deviation of the distribution to draw from
 * @return array the array of pseudo-random numbers
 */
function n_random_normal($n, $mean, $std) {
    $a = array();

    for ($i = 0; $i < ($n - 1) / 2; $i++) {
        list($n1, $n2) = random_normal($mean, $std);
        $a[] = $n1;
        $a[] = $n2;
    }

    // Handle even/odd n since random normal come in pairs
    list($n1, $n2) = random_normal($mean, $std);
    $a[] = $n1;
    if (0 == $n % 2) {
        $a[] = $n2;
    }

    return $a;
}

/**
 * Generate an array of n uniformly distributed pseudo-random numbers
 * 
 * @param int $n the size of the random array
 * @return array the array of pseudo-random numbers
 */
function n_random($n) {
    $a = array();
    for ($i = 0; $i < $n; $i++) {
        $a[] = mt_rand() / mt_getrandmax();
    }

    return $a;
}


/**
 * An iterator on all of the combinations of elements in an array
 *
 * Modified implementation of the one found on
 * http://stackoverflow.com/questions/3742506/php-array-combinations
 *
 */
class Combinations implements Iterator {
    protected $c = null;
    protected $s = null;
    protected $n = 0;
    protected $k = 0;
    protected $pos = 0;

    function __construct($s, $k) {
        if (is_array($s)) {
            $this -> s = array_values($s);
            $this -> n = count($this -> s);
        } else {
            $this -> s = (string)$s;
            $this -> n = strlen($this -> s);
        }
        $this -> k = $k;
        $this -> rewind();
    }

    function key() {
        return $this -> pos;
    }

    function current() {
        $r = array();
        for ($i = 0; $i < $this -> k; $i++)
            $r[] = $this -> s[$this -> c[$i]];
        return is_array($this -> s) ? $r : implode('', $r);
    }

    function next() {
        if ($this -> _next())
            $this -> pos++;
        else
            $this -> pos = -1;
    }

    function rewind() {
        $this -> c = range(0, $this -> k);
        $this -> pos = 0;
    }

    function valid() {
        return $this -> pos >= 0;
    }

    protected function _next() {
        $i = $this -> k - 1;
        while ($i >= 0 && $this -> c[$i] == $this -> n - $this -> k + $i)
            $i--;
        if ($i < 0)
            return false;
        $this -> c[$i]++;
        while ($i++ < $this -> k - 1)
            $this -> c[$i] = $this -> c[$i - 1] + 1;
        return true;
    }

}

/**
 * An iterator on the Cartesion product of several arrays.
 *
 * Construct with a multidimensional array to get the Cartesian product of all
 * the iterables contained in $s
 *
 */
class Product implements Iterator {
    protected $c = null;
    protected $s = null;
    protected $n = 0;
    protected $k = 0;
    protected $pos = 0;
    protected $indices = null;
    protected $dimensions = null;

    function __construct($s) {
        if (is_array($s)) {
            $this -> s = array_values($s);
        } else {
            throw new Exception('Must provide a multidimensional array.');
        }
        $this -> n = 1;
        foreach ($this->s as $p) {
            $this -> n *= count($p);
        }
        $this -> k = count($this -> s);
        $this -> indices = array();
        $this -> dimensions = array();

        $this -> rewind();
    }

    function key() {
        return $this -> pos;
    }

    function current() {
        $r = array();
        foreach ($this->indices as $idx => &$pos) {
            $r[] = $this -> s[$idx][$pos];
        }
        return $r;
    }

    function next() {
        if ($this -> _next())
            $this -> pos++;
        else
            $this -> pos = -1;
    }

    function rewind() {
        for ($i = 0; $i < $this -> k; $i++) {
            $this -> indices[$i] = 0;
            $this -> dimensions[$i] = count($this -> s[$i]);
        }
    }

    function valid() {
        return $this -> pos >= 0;
    }

    protected function _next() {

        if ($this -> pos + 1 >= $this -> n)
            return false;

        foreach ($this->indices as $idx => &$pos) {
            if (!0 == ($pos = ($pos + 1) % $this -> dimensions[$idx])) {
                break;
            }
        }

        return true;
    }
}