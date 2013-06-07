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
        $ret[$key] = abs($arr2[$key] - $arr1[$key]);
    }

    return $ret;
}

function average(&$arr) {
    if (count($arr) < 1)
        return 0;

    return (double)array_sum($arr) / count($arr);
}

function variance(&$arr) {
    if (count($arr) < 2)
        return 0;

    $mean = average($arr);
    $square_diffs = array_map(function($x) use (&$mean) {
        return pow($x - $mean, 2);
    }, $arr);

    return array_sum($square_diffs) / (count($arr) - 1);
}

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

function n_random($n) {
    $a = array();
    for ($i = 0; $i < $n; $i++) {
        $a[] = mt_rand() / mt_getrandmax();
    }

    return $a;
}

/**
 *
 */
function classify($reference_fspace, $query_fspace, $k) {

    $nn = array();
    foreach ($reference_fspace as $reference_user => $reference_samples) {
        $nn[$reference_user] = array();
        foreach ($query_fspace as $query_user => $query_samples) {
            $nn[$reference_user][$query_user] = array();
            foreach ($query_samples as $query_sample_idx => $query_sample) {
                list($distances, $distance_labels) = sorted_distances($reference_fspace, $query_sample, $reference_user);
                $nn[$reference_user][$query_user][$query_sample_idx] = linear_weighted_decisions($distance_labels, $k);
            }
        }
    }

    return $nn;
}

function loo_cross_validation(&$fspace, $k) {
    $reference_users = array_keys($fspace);
    $query_users = array_keys($fspace);
    $nn = new DefaultArray(new DefaultArray(new ArrayObject()));
    
    foreach ($query_users as $query_user) {
        $loo_fspace = $fspace;
        $query_samples = $loo_fspace[$query_user];
        foreach ($reference_users as $reference_user) {
            foreach ($query_samples as $query_sample_idx => $query_sample) {
                $loo_fspace[$query_user] = array_slice($query_samples, 0, $query_sample_idx, true) + array_slice($query_samples, $query_sample_idx+1, NULL, true);
                list($distances, $distance_labels) = sorted_distances($loo_fspace, $reference_user, $query_sample);
                $nn[$reference_user][$query_user][$query_sample_idx] = linear_weighted_decisions($distance_labels, $k);
            }
        }
    }
    
    return $nn;
}

function classify_loo(&$fspace, $k) {
    $nn = new DefaultArray(new DefaultArray(new ArrayObject()));

    $users_product = new Product(array(array_keys($fspace), array_keys($fspace)));
    foreach ($users_product as $users) {
        list($reference_user, $query_user) = $users;
        foreach ($fspace[$query_user] as $query_sample_idx => $query_sample) {
            list($distances, $distance_labels) = sorted_distances_loo($fspace, $reference_user, $query_sample_idx);
            $nn[$reference_user][$query_user][$query_sample_idx] = linear_weighted_decisions($distance_labels, $k);
        }
    }

    return $nn;
}

function error_rates($nn) {

    // False acceptance and false rejection rates for the population
    $frr = array();
    $far = array();

    // Frequency of the outcome of each decision while varying the model parameter, m
    $fn_counts = array();
    $cp_counts = array();
    $fp_counts = array();
    $cn_counts = array();

    list($ref) = $nn;
    list($query) = $ref;
    list($labels) = $query;
    $m_max = count($labels);

    for ($m = 0; $m < $m_max; $m++) {
        $fn = 0;
        $cp = 0;
        $fp = 0;
        $cn = 0;

        foreach ($nn as $reference_user => $query_nn) {
            foreach ($query_nn as $query_user => $classifications) {
                foreach ($classifications as $classification) {

                    // Same user, an error would increase the FRR
                    if ($reference_user == $query_user) {
                        if ('w' == $classification[$m])
                            $cp += 1;
                        else
                            $fn += 1;
                    } else {// Different users, error would increase the FAR
                        if ('w' == $classification[$m])
                            $fp += 1;
                        else
                            $cn += 1;
                    }
                }
            }
        }

        $fn_counts[$m] = $fn;
        $cp_counts[$m] = $cp;
        $fp_counts[$m] = $fp;
        $cn_counts[$m] = $cn;

        if (0 == $fp)
            $far[$m] = 0;
        else
            $far[$m] = $fp / ($fp + $cn);

        if (0 == $fn)
            $frr[$m] = 0;
        else
            $frr[$m] = $fn / ($fn + $cp);
    }

    return array($far, $frr, $fn_counts, $cp_counts, $fp_counts, $cn_counts);
}

function linear_weighted_decisions(&$neighbors, $k) {
    $decisions = array();

    $w = 0;
    for ($i = 0; $i < $k; $i++) {
        if ('w' == $neighbors[$i])
            $w += $k - $i;
    }

    for ($m = 0; $m < ($k * ($k + 1)) / 2; $m++) {
        $decisions[$m] = ($w >= $m) ? 'w' : 'b';
    }

    return $decisions;
}

function create_user_dspace_within_loo(&$fspace, $user, $query_sample_idx) {
    $dspace = array();
    $samples = $fspace[$user];
    $sample_combinations = new Combinations(array_keys($samples), 2);
    foreach ($sample_combinations as $sample_idxs) {
        if ($sample_idxs[0] != $query_sample_idx && $sample_idxs[1] != $query_sample_idx) {
            $dspace[] = abs_diff($fspace[$user][$sample_idxs[0]], $fspace[$user][$sample_idxs[1]]);
        }
    }
    return $dspace;
}

function create_user_dspace_between_loo(&$fspace, $user, $query_sample_idx) {
    $dspace = array();
    foreach ($fspace[$user] as $sample_idx => $sample) {
        if ($sample_idx != $query_sample_idx) {
            foreach (array_keys($fspace) as $diff_user) {
                if ($user != $diff_user) {
                    foreach ($fspace[$diff_user] as $diff_sample) {
                        $dspace[] = abs_diff($sample, $diff_sample);
                    }
                }
            }
        }
    }
    return $dspace;
}

function create_dspace_query_loo(&$fspace, $reference_user, $query_sample_idx) {
    $dspace_query = array();

    foreach ($fspace[$reference_user] as $reference_sample_idx => $reference_sample) {
        if ($reference_sample_idx != $query_sample_idx) {
            $dspace_query[] = abs_diff($reference_sample, $fspace[$reference_user][$query_sample_idx]);
        }
    }

    return $dspace_query;
}

function sorted_distances_loo(&$fspace, $reference_user, $query_sample_idx) {
    $w_dspace = create_user_dspace_within_loo($fspace, $reference_user, $query_sample_idx);
    $b_dspace = create_user_dspace_between_loo($fspace, $reference_user, $query_sample_idx);
    $q_dspace = create_dspace_query_loo($fspace, $reference_user, $query_sample_idx);

    $w_product = iterator_to_array(new Product( array(&$w_dspace, &$q_dspace)));
    $w_distances = array_map('euclidean_distance', $w_product);

    $b_product = iterator_to_array(new Product( array(&$b_dspace, &$q_dspace)));
    $b_distances = array_map('euclidean_distance', $b_product);

    $w_labels = array_fill(0, count($w_distances), 'w');
    $b_labels = array_fill(0, count($b_distances), 'b');

    $distance_labels = array_merge($w_labels, $b_labels);
    $distances = array_merge($w_distances, $b_distances);

    array_multisort($distances, SORT_ASC, $distance_labels);

    return array($distances, $distance_labels);
}

function sorted_distances(&$fspace, $reference_user, $query_sample) {
    $w_dspace = create_user_dspace_within($fspace, $reference_user);
    $b_dspace = create_user_dspace_between($fspace, $reference_user);
    $q_dspace = create_dspace_query($fspace, $reference_user, $query_sample);

    $w_product = iterator_to_array(new Product( array(&$w_dspace, &$q_dspace)));
    $w_distances = array_map('euclidean_distance', $w_product);

    $b_product = iterator_to_array(new Product( array(&$b_dspace, &$q_dspace)));
    $b_distances = array_map('euclidean_distance', $b_product);

    $w_labels = array_fill(0, count($w_distances), 'w');
    $b_labels = array_fill(0, count($b_distances), 'b');

    $distance_labels = array_merge($w_labels, $b_labels);
    $distances = array_merge($w_distances, $b_distances);

    array_multisort($distances, SORT_ASC, $distance_labels);

    return array($distances, $distance_labels);
}

function create_user_dspace_within(&$fspace, $user) {
    $dspace = array();
    $samples = $fspace[$user];
    $sample_combinations = new Combinations(array_keys($samples), 2);
    foreach ($sample_combinations as $sample_idxs) {
        $dspace[] = abs_diff($fspace[$user][$sample_idxs[0]], $fspace[$user][$sample_idxs[1]]);
    }
    return $dspace;
}

function create_user_dspace_between(&$fspace, $user) {
    $dspace = array();
    foreach ($fspace[$user] as $sample_idx => $sample) {
        foreach (array_keys($fspace) as $diff_user) {
            if ($user != $diff_user) {
                foreach ($fspace[$diff_user] as $diff_sample) {
                    $dspace[] = abs_diff($sample, $diff_sample);
                }
            }
        }
    }
    return $dspace;
}

function create_dspace_query(&$fspace, $reference_user, $query_sample) {
    $dspace_query = array();

    foreach ($fspace[$reference_user] as $reference_sample) {
        $dspace_query[] = abs_diff($reference_sample, $query_sample);
    }

    return $dspace_query;
}

function create_dspace_within(&$fspace) {
    $dspace_within = array();
    foreach ($fspace as $user => $samples) {
        $sample_combinations = new Combinations(&$samples, 2);
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
    $user_product = new Product( array(array_keys($fspace), array_keys($fspace)));
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

/**
 * DefaultArray provides functionality similar to Python's defaultdict.
 * A default value is initialized and inserted into the array for any key that
 * does not already exist.
 *
 */
class DefaultArray extends ArrayObject {

    protected $_default_value;

    public function __construct($value = null) {
        $this -> _default_value = $value;
    }

    public function offsetExists($index) {
        return true;
    }

    public function offsetGet($index) {
        if (!parent::offsetExists($index)) {
            if (is_object($this->_default_value))
                parent::offsetSet($index, clone $this->_default_value);
            else
                parent::offsetSet($index, $this->_default_value);
        }
        return parent::offsetGet($index);
    }

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
