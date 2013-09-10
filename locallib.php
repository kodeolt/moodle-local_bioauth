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
require_once($CFG->dirroot . '/local/bioauth/constants.php');

/**
 * Log data which has been collected during a quiz attempt 
 *
 * The data should be in $_POST['biodata']
 * 
 * @param int $userid the id of the user being logged
 * @param int $quizid the id of the quiz the attempt belongs to
 * @param int $timestamp the time the data reached the server
 */
function bioauth_enroll_quiz_data($userid, $quizid, $timestamp) {
    global $DB;

    $source = required_param('source', PARAM_TEXT);
    $useragent = required_param('useragent', PARAM_TEXT);
    $platform = required_param('platform', PARAM_TEXT);
    $jsondata = required_param('biodata', PARAM_TEXT);
    $numkeystrokes = required_param('numkeystrokes', PARAM_TEXT);
    $numstylometry = required_param('numstylometry', PARAM_TEXT);

    // TODO: check precision of keystroke timestamps

    $biodata = new stdClass();

    $biodata->userid = $userid;
    $biodata->quizid = $quizid;
    $biodata->timemodified = time();
    $biodata->locale = current_language();
    $biodata->useragent = $useragent;
    $biodata->platform = $platform;
    $biodata->data = $jsondata;
    $biodata->numkeystrokes = $numkeystrokes;
    $biodata->numstylometry = $numstylometry;

    $DB->insert_record('bioauth_quiz_biodata', $biodata);
}

/**
 * Get a list of the feature sets available for a particular locale
 *
 * @param string $language the language feature sets should be compatible with
 * @return array an array of the feature sets belonging to $language
 */
function get_feature_sets($locale) {
    global $DB;

    $records = $DB->get_records('bioauth_feature_sets', array('locale' => $locale), 'name', 'id, name');

    $featuresets = array();
    foreach ($records as $id => $record) {
        $featuresets[$id] = $record->name;
    }

    return $featuresets;
}

/**
 * Create a quiz validation job for a course.
 * This will allow a validation job to be run once enough data has been
 * collected from quizzes
 *
 * @param int $courseid the course to start logging biometric data for
 */
function create_quiz_validation_job($courseid) {
    global $DB;

    if ($DB->record_exists('bioauth_quiz_validations', array('courseid' => $courseid))) {
        return;
    }

    $jobparams = new stdClass();
    $jobparams->knn = get_config('local_bioauth', 'knn');
    $jobparams->minkeyfrequency = get_config('local_bioauth', 'minkeyfrequency');
    $jobparams->decisionmode = get_config('local_bioauth', 'decisionmode');
    $jobparams->featureset = get_config('local_bioauth', 'featureset');

    $jobrecord = array();
    $jobrecord['state'] = BIOAUTH_JOB_WAITING;
    $jobrecord['courseid'] = $courseid;
    $jobrecord['activeuntil'] = (time() + get_config('local_bioauth', 'weekskeepactive') * (7 * 24 * 60 * 60));
    $jobrecord['percentdataneeded'] = get_config('local_bioauth', 'percentdataneeded');
    $jobrecord['jobparams'] = json_encode($jobparams);

    $DB->insert_record('bioauth_quiz_validations', $jobrecord);
}

/**
 * Remove a quiz validation job and all of the decisions that have been made
 * for a particular course.
 *
 * @param int $courseid the id of the course
 */
function remove_quiz_validation_job($courseid) {
    global $DB;

    $DB->delete_records('bioauth_quiz_validations', array('courseid' => $courseid));
    $DB->delete_records('bioauth_quiz_neighbors', array('courseid' => $courseid));
}

/**
 * Handle the quiz_attempt_started event.
 *
 * This creates a new bioauth_biodata row for the quiz attempt if necessary
 *
 * @param object $event the event object.
 */
function course_created_handler($course) {

    if (BIOAUTH_MODE_ENABLED == get_config('local_bioauth', 'mode')) {
        create_quiz_validation_job($course->id);
    }
}

/**
 * Lookup the name of a key which was pressed. This function's behavior
 * is very similar to get_string in the string API.
 *
 * @param int $keycode the keycode of the physical key
 * @param string $agent the source which generated the keycode
 * @return string the name of the key which was pressed
 */
function get_key($keycode, $agent = '') {
    global $CFG;
    $result = get_key_manager()->get_key($keycode, $agent);
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
function get_key_manager($forcereload = false) {
    global $CFG;

    static $singleton = null;

    if ($forcereload) {
        $singleton = null;
    }
    if ($singleton === null) {
        $singleton = new key_manager(get_config('local_bioauth', 'cachekeycodes'));
    }

    return $singleton;
}

/**
 * 
 *
 */
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
        $this->usecache = $usecache;

        if ($this->usecache) {
            // We can use a proper cache, establish the cache using the 'String cache' definition.
            $this->cache = cache::make('local_bioauth', 'keycode');
        } else {
            // We only want a cache for the length of the request, create a static cache.
            $options = array('simplekeys' => true, 'simpledata' => true);
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
    public function load_keys($lang, $disablecache = false) {
        global $CFG;

        $cachekey = $lang;

        if (!$disablecache) {
            $keycode = $this->cache->get($cachekey);
            if ($keycode) {
                return $keycode;
            }
        }

        $file = 'keycode';
        $keycode = array();

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
     * @param string $lang moodle translation language, null means use current
     * @return string The String !
     */
    public function get_key($identifier, $agent = '', $lang = null) {
        $this->countgetstring++;

        if ($lang === null) {
            $lang = current_language();
        }

        $keycode = $this->load_keys($lang);

        if (empty($agent) || !array_key_exists($agent, $keycode)) {
            $agent = 'default';
        }

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

        debugging("Invalid get_key() identifier: '{$identifier}' or agent '{$agent}' using language: {$lang}. "
                . "Perhaps you are missing \$keycode['{$identifier}'] = ''; in {$lang}/keycode.php?", DEBUG_DEVELOPER);
        return 'UNKNOWN';
    }

    /**
     * Clears both in-memory and on-disk caches
     * @param bool $phpunitreset true means called from our PHPUnit integration test reset
     */
    public function reset_caches($phpunitreset = false) {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        // Clear the on-disk disk with aggregated string files.
        $this->cache->purge();

        if (!$phpunitreset) {
            // Increment the revision counter.
            $langrev = get_config('local_bioauth', 'keycoderev');
            $next = time();
            if ($langrev !== false and $next <= $langrev and $langrev - $next < 60 * 60) {
                // This resolves problems when reset is requested repeatedly within 1s,
                // the < 1h condition prevents accidental switching to future dates
                // because we might not recover from it.
                $next = $langrev + 1;
            }
            set_config('keycoderev', $next);
        }
    }

}
