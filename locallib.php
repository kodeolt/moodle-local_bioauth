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
function euclidean_distance(&$a) {
    $c = array_map("subtract_and_square", $a[0], $a[1]);
    return pow(array_sum($c), .5);
}

function abs_diff($arr1, $arr2) {
  $ret = array();
  foreach ($arr1 as $key => $value) {
    $ret[$key] = abs($arr2[$key]-$arr1[$key]);
  }
  return $ret;
}

function sorted_distances(&$fspace, &$query_sample, $query_user) {
    
    $w_dspace = create_user_dspace_within($fspace, $query_user);
    $b_dspace = create_user_dspace_between($fspace, $query_user);
    $q_dspace = create_dspace_query($fspace, $query_user, $query_sample);
    
    $a = array($w_dspace, $q_dspace);
    $w_product = iterator_to_array(new Product($a));
    $w_distances = array_map('euclidean_distance', $w_product);
    
    $a = array($b_dspace, $q_dspace);
    $b_product = iterator_to_array(new Product($a));
    $b_distances = array_map('euclidean_distance', $b_product);
    
    $w_labels = array_fill(0, count($w_distances), 'w');
    $b_labels = array_fill(0, count($b_distances), 'b');
    
    $distance_labels = array_merge($w_labels, $b_labels);
    $distances = array_merge($w_distances, $b_distances);
    
    array_multisort($distances, SORT_ASC, $distance_labels);
    
    return $distance_labels;
}

function create_user_dspace_within(&$fspace, $user) {
    $dspace = array();
    $samples = $fspace[$user];
    $sample_combinations = new Combinations($samples, 2);
    foreach ($sample_combinations as $samples) {
            $dspace[] = abs_diff($samples[0], $samples[1]);
    }
    return $dspace;
}

function create_user_dspace_between(&$fspace, $user) {
    
    $dspace = array();
    foreach ($fspace[$user] as $sample) {
        foreach (array_keys($fspace) as $diff_user) {
            foreach ($fspace[$diff_user] as $diff_sample) {
                $dspace[] = abs_diff($sample, $diff_sample);
            }
        }
    }
    return $dspace;
}

function create_dspace_within(&$fspace) {
    $dspace_within = array();
    foreach ($fspace as $user => $samples) {
        $sample_combinations = new Combinations($samples, 2);
        $user_dspace = array();
        
        foreach ($sample_combinations as $idx) {
            $user_dspace[] = abs_diff($samples[0], $samples[1]);
        }
        $dspace_within[$user] = $user_dspace;
    }
    
    return $dspace_within;
}

function create_dspace_between(&$fspace) {
    $dspace_between = array();
    $a = array(array_keys($fspace), array_keys($fspace));
    $user_product = new Product($a);
    foreach ($user_product as $users) {
        if ($users[0] == $users[1])
            continue;
            
        $user_dspace = array();
        foreach ($fspace[$users[0]] as $sample) {
            foreach ($fspace[$users[1]] as $diff_sample) {
                $user_dspace[] = abs_diff($sample, $diff_sample);
            }
        }
        $dspace_between[$users[0]] = $user_dspace;
    }
    
    return $dspace_between;
}

function create_dspace_query(&$fspace, $reference_user, $query_sample) {
    $dspace_query = array();
    
    foreach ($fspace[$reference_user] as $reference_sample) {
        $dspace_query[] = abs_diff($reference_sample, $query_sample);
    }
    
    return $dspace_query;
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

    function __construct(&$s, $k) {
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
 * Cartesian product iterator
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
    
    function __construct(&$s) {
        if (is_array($s)) {
            $this->s = array_values($s);
        } else {
            throw new Exception('Must provide a multidimensional array.');
        }
        $this->n = 1;
        foreach ($this->s as $p) {
            $this->n *= count($p);
        }
        $this->k = count($this->s);
        $this->indices = array();
        $this->dimensions = array();
        
        $this->rewind();
    }

    function key() {
        return $this->pos;
    }

    function current() {
        $r = array();
        foreach ($this->indices as $idx => &$pos) {
            $r[] = $this->s[$idx][$pos];
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
        for ($i = 0; $i < $this->k; $i++) {
            $this->indices[$i] = 0;
            $this->dimensions[$i] = count($this->s[$i]);
        }
    }

    function valid() {
        return $this -> pos >= 0;
    }

    protected function _next() {
        
        if ($this->pos + 1 >= $this->n)
            return false;
        
        foreach ($this->indices as $idx => &$pos) {
            if (! 0 == ($pos = ($pos + 1) % $this->dimensions[$idx])) {
                break;
            }
        }
        
        return true;
    }

}