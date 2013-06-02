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
 * Library of functions used by the bioauth module.
 *
 * This contains functions that are called from within the bioauth module only
 * Functions that are also called by core Moodle are in {@link lib.php}
 *
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once ($CFG -> dirroot . '/local/bioauth/lib.php');

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
 * Compute the Euclidean distance between two vectors of arbitrary size.
 *
 * @param array $p
 * @param array $q
 * @return the Euclidean distance between $p and $q
 */
function euclidean_distance($p, $q) {
    $numargs = func_num_args();
    if ($numargs != 2) {
        die("You must supply 2, and only 2, coordinates, no more, no less.\n");
    } else if (sizeof($p) != sizeof($q)) {
        die("Coordinates do not have the same number of elements.\n");
    } else {
        $c = array_map("subtract_and_square", $p, $q);
        return pow(array_sum($c), .5);
    }
}

function abs_diff($arr1, $arr2) {
  $ret = array();
  foreach ($arr1 as $key => $value) {
    $ret[$key] = abs($arr2[$key]-$arr1[$key]);
  }
  return $ret;
}


function dspace_within($fspace, $user) {
    $idx_combinations = new Combinations(range(0, count($fspace[$user])));
    $dspace = array();
    foreach ($idx_combinations as $idx) {
        $dspace[] = difference($fspace[$idx[0]], $fspace[$idx[1]]);
    }
    
    return $dspace;
}

/**
 * Combinations iterator, modified from the one found on
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
