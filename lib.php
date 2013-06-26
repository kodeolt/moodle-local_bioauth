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

global $CFG;
require_once ($CFG -> dirroot . '/local/bioauth/locallib.php');


function local_bioauth_cron() {
    ini_set('memory_limit', '-1');
    global $DB;
    
    // $DB -> insert_record('bioauth_quiz_validations', array('userid' => 100, 'locale' => 'en_US', 'keystrokes' => 'jsonstring', 'stylometry' => ''));
    
    echo "Creating keystroke features ", time();
    $keystrokefeatures = create_keystroke_features(1);
    
    echo ". Fetching demo keystrokes ", time();
    
    $userkeystrokes = fetch_demo_keystrokes();
    //print_r($userkeystrokes[1]);
    echo ". Extracting features ", time();
    $fspace = create_keystroke_fspace($userkeystrokes, $keystrokefeatures, 2);
    
    //print_r($fspace);
    
    echo ". Done ", time();
}

function get_key($identifier, $locale, $agent = NULL) {
    global $CFG;
    $result = get_key_manager()->get_keystring($identifier, $locale, $agent);
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
        $singleton = new key_manager(!empty($CFG->langstringcache));
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
     * Load all strings for one component
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
        
        // first load english key codes
        if (!file_exists("$CFG->dirroot/local/bioauth/keys/en/$file.php")) {
            return array(array());
        }
        
        include("$CFG->dirroot/local/bioauth/keys/en/$file.php");

        // now loop through all langs in correct order
        // $deps = get_string_manager()->get_language_dependencies($lang);
        // foreach ($deps as $dep) {
            // if (file_exists("$CFG->dirroot/local/bioauth/keys/$dep/$file.php")) {
                // include("$CFG->dirroot/local/bioauth/keys/$dep/$file.php");
            // }
        // }

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
    public function keycode_exists($identifier, $agent = NULL) {
        if ($agent === NULL) {
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
    public function get_key($identifier, $lang = NULL, $agent = NULL) {
        $this->countgetstring++;
        
        if ($lang === NULL) {
            $lang = current_language();
        }
        
        if ($agent === NULL) {
            $agent = 'default';
        }

        $keycode = $this->load_keys($identifier, $lang, $agent);

        if (!isset($keycode[$agent][$identifier])) {
            if ($this->usecache) {
                // maybe the on-disk cache is dirty - let the last attempt be to find the string in original sources,
                // do NOT write the results to disk cache because it may end up in race conditions see MDL-31904
                $this->usecache = false;
                $keycode = $this->load_keys($component, $lang, true);
                $this->usecache = true;
            }
            
            if (!isset($keycode[$agent][$identifier])) {
                debugging("Invalid get_key() identifier: '{$identifier}' or agent '{$agent}'. " .
                        "Perhaps you are missing \$keycode['{$identifier}'] = ''; in {$file}?", DEBUG_DEVELOPER);
                 return 'UNKOWN';
            }
        }

        $keycode = $keycode[$agent][$identifier];
        
        return $keycode;
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
