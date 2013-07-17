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
require_once ($CFG -> dirroot . '/local/bioauth/util.php');
require_once ($CFG -> dirroot . '/local/bioauth/mathlib.php');
require_once ($CFG -> dirroot . '/local/bioauth/constants.php');
require_once ($CFG -> dirroot . '/local/bioauth/keystrokelib.php');


function feature_with_fallback(&$observations, $feature, $minfrequency) {
    
    $obs = array();
    
    $node =& $feature;
    while ($node && count($obs) < $minfrequency) {
        foreach ($node->group1 as $key1) {
            foreach ($node->group2 as $key2) {
                $obs = array_merge($obs, (array)$observations[$node->distance][$key1][$key2]);
                // $obs = (array)$observations[$node->distance][$key1][$key2]; 
            }
        }
        $node =& $node->fallback;
    }
    
    if (BIOAUTH_MEASURE_MEAN == $feature->measure) {
        return average($obs);
    } elseif (BIOAUTH_MEASURE_STDDEV == $feature->measure) {
        return sqrt(variance($obs));
    }
}

function extract_keystroke_features($keystrokesequence, $keystrokefeatures, $minfrequency) {
    
    $distances = array(1);
    
    $durations = new DefaultArray(new DefaultArray(new DefaultArray(new ArrayObject())));
    $t1 = new DefaultArray(new DefaultArray(new DefaultArray(new ArrayObject())));
    $t2 = new DefaultArray(new DefaultArray(new DefaultArray(new ArrayObject())));
    
    foreach ($keystrokesequence as $idx => $keystroke) {
        $durations[0][$keystroke->keyname][$keystroke->keyname][] = $keystroke->timerelease - $keystroke->timepress;
    }
    
    foreach ($distances as $distance) {
        for ($idx = 0; $idx < count($keystrokesequence) - $distance; $idx += $distance) {
            $firstkey = $keystrokesequence[$idx];
            $secondkey = $keystrokesequence[$idx + $distance];
            $t1[$distance][$firstkey->keyname][$secondkey->keyname][] =  $secondkey->timepress - $firstkey->timerelease;
            $t2[$distance][$firstkey->keyname][$secondkey->keyname][] =  $secondkey->timepress - $firstkey->timepress;
        }
    }
    
    $featurevector = array();
    foreach ($keystrokefeatures as $featureidx => $feature) {
        if (0 == $feature->distance && BIOAUTH_FEATURE_DURATION == $feature->type) {
            // Duration
            $featurevector[$featureidx] = feature_with_fallback($durations, $feature, $minfrequency);
        } elseif ($feature->distance > 0 && BIOAUTH_FEATURE_T1 == $feature->type) {
            // Type 1 transition
            $featurevector[$featureidx] = feature_with_fallback($t1, $feature, $minfrequency);
        } elseif ($feature->distance > 0 && BIOAUTH_FEATURE_T2 == $feature->type) {
            // Type 2 transition
            $featurevector[$featureidx] = feature_with_fallback($t2, $feature, $minfrequency);
        }
    }
    
    return $featurevector;
}


function create_keystroke_fspace($userdata, $keystrokefeatures, $minfrequency) {
    
    //$fspace = new DefaultArray(new ArrayObject());
    $fspace = array();
    
    foreach ($userdata as $userid => $sessions) {
        foreach ($sessions as $sessionid => $data) {
                $fspace[$userid][$sessionid] = extract_keystroke_features($data->keystrokes, $keystrokefeatures, $minfrequency);
        }
    }
    
    return $fspace;
}

/**
 * Classify all of the feature vectors in a query set with the supplied reference
 * set. This is a closed-system: all users in the query set must be present in
 * the reference set.
 * 
 * @param array $reference_fspace the reference feature space of a population
 * @param array $query_fspace the query set to be authentication
 * @return int $k the number of neighbors to use in the KNN binary classification
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

/**
 * 
 * 
 * @param array $fspace the feature space of a population
 * @return int the number of neighbors to use in the KNN binary classification
 * 
 */
function authenticate_users(&$fspace, $k) {
    $reference_users = array_keys($fspace);
    $query_users = array_keys($fspace);
    $nn = new DefaultArray(new DefaultArray(new ArrayObject()));

    foreach ($query_users as $query_user) {
        $loo_fspace = $fspace;
        $query_samples = $loo_fspace[$query_user];
        foreach ($query_samples as $query_sample_idx => $query_sample) {
            echo ' user ', $query_user, ' sample ', $query_sample_idx, ' '; 
            $loo_fspace[$query_user] = array_slice($query_samples, 0, $query_sample_idx, true) + array_slice($query_samples, $query_sample_idx + 1, NULL, true);
            list($distances, $distance_labels) = sorted_distances($loo_fspace, $query_user, $query_sample);
            $nn[$query_user][$query_user][$query_sample_idx] = linear_weighted_decisions($distance_labels, $k);
        }
        
    }

    return $nn;
}

/**
 * Perform a leave-one-out cross validation to evaluate the model performance 
 * and get authentication results on every user.
 * 
 * @param array $fspace the feature space of a population
 * @return int the number of neighbors to use in the KNN binary classification
 * 
 */
function loo_cross_validation(&$fspace, $k) {
    $reference_users = array_keys($fspace);
    $query_users = array_keys($fspace);
    $nn = new DefaultArray(new DefaultArray(new ArrayObject()));

    foreach ($query_users as $query_user) {
        $loo_fspace = $fspace;
        $query_samples = $loo_fspace[$query_user];
        foreach ($reference_users as $reference_user) {
            foreach ($query_samples as $query_sample_idx => $query_sample) {
                $loo_fspace[$query_user] = array_slice($query_samples, 0, $query_sample_idx, true) + array_slice($query_samples, $query_sample_idx + 1, NULL, true);
                list($distances, $distance_labels) = sorted_distances($loo_fspace, $reference_user, $query_sample);
                $nn[$reference_user][$query_user][$query_sample_idx] = linear_weighted_decisions($distance_labels, $k);
            }
        }
    }

    return $nn;
}

/**
 * Compute the population error rates for decisions made on a bunch of 
 * reference/query combinations
 * 
 * This is one of the uglier functions here, will probably refactor at some point.
 * 
 * @param array $nn the array of reference/query decisions
 * @return array the false rejection and false acceptance rates. Also the frequency
 * of each classification outcome.
 * 
 */
function error_rates($nn) {

    // False acceptance and false rejection rates for the population
    $frr = array();
    $far = array();

    // Frequency of the outcome of each decision while varying the model parameter, m
    $fn_counts = array();
    $cp_counts = array();
    $fp_counts = array();
    $cn_counts = array();

    // Find the number of decisions, the maximum value of the model parameter m
    list(list(list($decisions))) = $nn;
    $m_max = count($decisions);

    // For every reference/query combination and value of m,
    // Count the frequency of each error type
    for ($m = 0; $m < $m_max; $m++) {
        // false negative
        $fn = 0;
        // correct positive
        $cp = 0;
        // false positive
        $fp = 0;
        // correct negative
        $cn = 0;

        foreach ($nn as $reference_user => $query_nn) {
            foreach ($query_nn as $query_user => $classifications) {
                foreach ($classifications as $classification) {
                    if ($reference_user == $query_user) {
                        // Same user, an error would increase the FRR
                        if ('w' == $classification[$m])
                            $cp += 1;
                        else
                            $fn += 1;
                    } else {
                        // Different users, error would increase the FAR
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

    return array($frr, $far, $fn_counts, $cp_counts, $fp_counts, $cn_counts);
}

/**
 * Make a linear-weighted decision for a list of labeled neighbors.
 *
 * The model parameter varies from 0 to k*(k-1)/2 and a within/between decision
 * is made for each value, based on the k neighbors observed.
 *
 * @param array $neighbors the labeled neighbors
 * @param int $k the number of neighbors to use
 * @return array the within/between decisions over all values of the model parameter
 */
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

/**
 * Compute and sort the distances between the query sample and the feature space.
 *
 * This maps the classification to a binary classification (authentication) by
 * operating in a difference space. The differences between the query sample and
 * the claimed user's samples are compared to the known within and between-class
 * difference vectors.
 *
 * @param array $fspace the feature space
 * @param key $reference_user the user to create the difference space for
 * @param array $query_sample the query_sample which must be classified
 * @return array the between-user difference space
 */
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

/**
 * Create the query difference space for a sample claimed to be a particular user.
 *
 * This takes the difference between the query sample and all of the user's
 * enrolled samples
 *
 * @param array $fspace the feature space
 * @param key $reference_user the claimed owner of the query sample
 * @param array $query_sample the query_sample which must be classified
 * @return array the query difference space
 */
function create_dspace_query(&$fspace, $reference_user, $query_sample) {
    $dspace_query = array();

    foreach ($fspace[$reference_user] as $reference_sample) {
        $dspace_query[] = abs_diff($reference_sample, $query_sample);
    }

    return $dspace_query;
}

/**
 * Create the within-user difference space for a particular user in feature
 * space. This takes the differences between each of $user's feature vectors.
 *
 * @param array $fspace the feature space
 * @param key $user the user to create the difference space for
 * @return array the within-user difference space
 */
function create_user_dspace_within(&$fspace, $user) {
    $dspace = array();
    $samples = $fspace[$user];
    $sample_combinations = new Combinations(array_keys($samples), 2);
    foreach ($sample_combinations as $sample_idxs) {
        $dspace[] = abs_diff($fspace[$user][$sample_idxs[0]], $fspace[$user][$sample_idxs[1]]);
    }
    return $dspace;
}

/**
 * Create the between-user difference space for a particular user in feature
 * space. This takes the differences between $user's feature vectors and every
 * other user's feature vectors.
 *
 * This would be equivalent to taking the between differenc space for the entire
 * feature space and then only keeping the between difference vectors of $user
 *
 * @param array $fspace the feature space
 * @param key $user the user to create the difference space for
 * @return array the between-user difference space
 */
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

/**
 * Create the within-user difference space for a feature space
 *
 * @param array $fspace the feature space
 * @return array the within-user difference space
 */
function create_dspace_within(&$fspace) {
    $dspace_within = array();
    foreach ($fspace as $user => $samples) {
        $sample_combinations = new Combinations(array_keys($samples), 2);
        $user_dspace = array();
        print_r(iterator_to_array($sample_combinations));
        return;
        foreach ($sample_combinations as $sample_idxs) {
            $user_dspace[] = abs_diff($samples[$sample_idxs[0]], $samples[$sample_idxs[1]]);
        }
        $dspace_within[$user] = $user_dspace;
    }

    return $dspace_within;
}

/**
 * Create the between-user difference space for a feature space
 *
 * @param array $fspace the feature space
 * @return array the between-user difference space
 */
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


function get_key($identifier, $agent = '') {
    global $CFG;
    $result = get_key_manager()->get_key($identifier, $agent);
    return $result;
}

/**
 * Returns current key_manager instance.
 *
 * The param $forcereload is needed for CLI installer only where the string_manager instance
 * must be replaced during the install.php script life time.
 *
 * @category string
 * @param bool $forcereload shall the singleton be released and new instance created instead?
 * @return string_manager
 */
function get_key_manager($forcereload=false) {
    global $CFG;

    static $singleton = null;

    if ($forcereload) {
        $singleton = null;
    }
    if ($singleton === null) {
        //$singleton = new key_manager(!empty($CFG->langstringcache));
        $singleton = new key_manager(TRUE);
    }

    return $singleton;
}

class key_manager {

    /** @var cache lang string cache - it will be optimised more later */
    protected $cache;
    /** @var int get_string() counter */
    protected $countgetstring = 0;
    /** @var bool use disk cache */
    protected $usecache;

    /**
     * Create new instance of string manager
     *
     * @param string $localroot location of downlaoded lang packs - usually $CFG->dataroot/lang
     * @param bool $usecache use disk cache
     * @param array $translist limit list of visible translations
     * @param string $menucache the location of a file that caches the list of available translations
     */
    public function __construct($usecache) {
        $this->usecache     = $usecache;

        if ($this->usecache) {
            // We can use a proper cache, establish the cache using the 'String cache' definition.
            $this->cache = cache::make('local_bioauth', 'keycode');
        } else {
            // We only want a cache for the length of the request, create a static cache.
            $options = array(
                'simplekeys' => true,
                'simpledata' => true
            );
            $this->cache = cache::make_from_params(cache_store::MODE_REQUEST, 'local_bioauth', 'keycode', array(), $options);
        }
    }

    /**
     * 
     *
     * @param string $keycode
     * @param string $lang
     * @param bool $disablecache Do not use caches, force fetching the strings from sources
     * @return array of all string for given component and lang
     */
    public function load_keys($lang, $disablecache=false) {
        global $CFG;

        $cachekey = $lang;

        if (!$disablecache) {
            $keycode = $this->cache->get($cachekey);
            if ($keycode) {
                return $keycode;
            }
        }
        
        $file = 'keycode';
        $keycode = array(array());
        
        include("$CFG->dirroot/local/bioauth/keys/$lang/$file.php");

        if (!$disablecache) {
            $this->cache->set($cachekey, $keycode);
        }

        return $keycode;
    }

    /**
     * Does the string actually exist?
     *
     * get_string() is throwing debug warnings, sometimes we do not want them
     * or we want to display better explanation of the problem.
     * Note: Use with care!
     *
     * @param string $identifier The identifier of the string to search for
     * @param string $component The module the string is associated with
     * @return boot true if exists
     */
    public function keycode_exists($identifier, $agent = '') {
        if (empty($agent)) {
            $agent = 'default';
        }
        $lang = current_language();
        $keycode = $this->load_keys($lang);
        return isset($keycode[$agent][$identifier]);
    }

    /**
     * Get String returns a requested string
     *
     * @param string $identifier The identifier of the string to search for
     * @param string $component The module the string is associated with
     * @param string|object|array $a An object, string or number that can be used
     *      within translation strings
     * @param string $lang moodle translation language, NULL means use current
     * @return string The String !
     */
    public function get_key($identifier, $agent = '', $lang = NULL) {
        $this->countgetstring++;
        
        if (empty($agent)) {
            $agent = 'default';
        }
        
        if ($lang === NULL) {
            $lang = current_language();
        }
        
        $keycode = $this->load_keys($lang);
        
        if (isset($keycode[$agent]) && isset($keycode[$agent][$identifier])) {
            return $keycode[$agent][$identifier];
        }
        
        if ($this->usecache) {
            // maybe the on-disk cache is dirty - let the last attempt be to find the string in original sources,
            // do NOT write the results to disk cache because it may end up in race conditions see MDL-31904
            $this->usecache = false;
            $keycode = $this->load_keys($lang, true);
            $this->usecache = true;
        }
        
        if (isset($keycode[$agent]) && isset($keycode[$agent][$identifier])) {
            return $keycode[$agent][$identifier];
        }

        debugging("Invalid get_key() identifier: '{$identifier}' or agent '{$agent}' using language: {$lang}. " .
                "Perhaps you are missing \$keycode['{$identifier}'] = ''; in {$lang}/keycode.php?", DEBUG_DEVELOPER);
         return 'UNKNOWN';
    }

    /**
     * Clears both in-memory and on-disk caches
     * @param bool $phpunitreset true means called from our PHPUnit integration test reset
     */
    public function reset_caches($phpunitreset = false) {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        // clear the on-disk disk with aggregated string files
        $this->cache->purge();

        if (!$phpunitreset) {
            // Increment the revision counter.
            $langrev = get_config('local_bioauth', 'keycoderev');
            $next = time();
            if ($langrev !== false and $next <= $langrev and $langrev - $next < 60*60) {
                // This resolves problems when reset is requested repeatedly within 1s,
                // the < 1h condition prevents accidental switching to future dates
                // because we might not recover from it.
                $next = $langrev+1;
            }
            set_config('keycoderev', $next);
        }
    }
}
