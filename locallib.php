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
require_once($CFG->dirroot . '/local/bioauth/lib.php');

function launch_biologger_js() {
    global $CFG;
    global $PAGE;
    global $USER;
    global $CONFIG;

    bioauth_save_sesskey();

    $enrollurl = new moodle_url('/local/bioauth/enroll.ajax.php');
    
    $jsdata = array('userid' => $USER->id, 
                    'sesskey' => sesskey(),
                    'enrollURL' => $enrollurl->out(),
                    'flushDelay' => 1000,
                    );
    
    $PAGE->requires->js(new moodle_url('/local/bioauth/biologger/js/jquery-1.10.1.min.js'), true);
    $PAGE->requires->js(new moodle_url('/local/bioauth/biologger/js/jquery.mousewheel.js'), true);
    $PAGE->requires->js(new moodle_url('/local/bioauth/biologger/js/jquery.mobile-events.js'), true);
    $PAGE->requires->js(new moodle_url('/local/bioauth/biologger/js/keymanager.js'), true);
    $PAGE->requires->js(new moodle_url('/local/bioauth/biologger/js/biologger.js'), true);
    
    $PAGE->requires->js_init_call('Biologger', $jsdata);
}

/**
 * Update the sesskey for a user attempting to start the native logger.
 * 
 * @param int $userid the id of the user being logged
 * @param int $timestamp the time the data reached the server
 */
function bioauth_save_sesskey() {
    global $DB;
    global $USER;
    
    $record = new stdClass();
    $record->userid = $USER->id;
    $record->sesskey = sesskey();
    $record->timemodified = time();
    
    if ($DB->record_exists('bioauth_sessions', array('userid' => $record->userid))) {
        $record->id = $DB->get_field('bioauth_sessions', 'id', array('userid' => $record->userid, 'sesskey' => $record->sesskey));
        $DB->update_record('bioauth_sessions', $record);
    } else {
        $DB->insert_record('bioauth_sessions', $record);
    }
}

/**
 * Confirm the sesskey for a user attempting to enroll data.
 * 
 * @param int $userid the id of the user being logged
 * @param int $timestamp the time the data reached the server
 */
function bioauth_confirm_sesskey($userid, $sesskey=NULL) {
    global $DB;
    
    if ($userid > 0 && !$DB->record_exists('bioauth_sessions', array('userid' => $userid))) {
        return false;
    }
    
    if (empty($sesskey)) {
        $sesskey = required_param('sesskey', PARAM_RAW);
    }
    
    return $DB->record_exists('bioauth_sessions', array('userid' => $userid, 'sesskey' => $sesskey));
}

function fputcsv2($handle, $fields, $delimiter = ',', $enclosure = '"') {
    # Check if $fields is an array
    if (!is_array($fields)) {
        return false;
    }
    # Walk through the data array
    for ($i = 0, $n = count($fields); $i < $n; $i ++) {
        # Only 'correct' non-numeric values
        if (!is_numeric($fields[$i])) {
            # Duplicate in-value $enclusure's and put the value in $enclosure's
            $fields[$i] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $fields[$i]) . $enclosure;
        }
        # If $delimiter is a dot (.), also correct numeric values
        if (($delimiter == '.') && (is_numeric($fields[$i]))) {
            # Put the value in $enclosure's
            $fields[$i] = $enclosure . $fields[$i] . $enclosure;
        }
    }
    # Combine the data array with $delimiter and write it to the file
    $line = implode($delimiter, $fields) . "\n";
    fwrite($handle, $line);
    # Return the length of the written data
    return strlen($line);
}

function fputcsv3($fh, array $fields, $delimiter = ',', $enclosure = '"', $mysql_null = false) { 
    $delimiter_esc = preg_quote($delimiter, '/'); 
    $enclosure_esc = preg_quote($enclosure, '/'); 

    $output = array(); 
    foreach ($fields as $field) { 
        if ($field === null && $mysql_null) { 
            $output[] = 'NULL'; 
            continue; 
        } 

        $output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? ( 
            $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure 
        ) : $field; 
    } 

    fwrite($fh, join($delimiter, $output) . "\n"); 
} 

function fputcsv4($handle, $row, $fd=',', $quot='"') 
{ 
   $str=''; 
   foreach ($row as $cell) { 
       $cell=str_replace(Array($quot,        "\n"), 
                         Array($quot.$quot,  ''), 
                         $cell); 
       if (strchr($cell, $fd)!==FALSE || strchr($cell, $quot)!==FALSE) { 
           $str.=$quot.$cell.$quot.$fd; 
       } else { 
           $str.=$cell.$fd; 
       } 
   } 

   fputs($handle, substr($str, 0, -1)."\n"); 

   return strlen($str); 
}

function concat_biodata($str, $data) {
    $outstream = fopen("php://temp", 'r+');
        fputcsv($outstream, $data, ',', '"');
        rewind($outstream);
        $csv = fgets($outstream);
        fclose($outstream);
        return $csv;
}

$BIOMETRICS = array('keystroke','mousemotion','mouseclick','mousescroll','stylometry');

/**
 * Log data which has been collected from any source
 *
 * The user should already be logged in or authenticated in some other way
 * 
 * @param int $userid the id of the user being logged
 * @param int $quizid the id of the quiz the attempt belongs to
 * @param int $timestamp the time the data reached the server
 */
function bioauth_enroll_data($userid, $time) {
    global $DB;
    global $BIOMETRICS;
    
    $ipaddress = $_SERVER['REMOTE_ADDR'];
    
    $session = required_param('session', PARAM_TEXT);
    $useragent = required_param('useragent', PARAM_RAW);
    $appversion = required_param('appversion', PARAM_RAW);
    
    $task = required_param('task', PARAM_URL);
    $tags = optional_param('tags', '', PARAM_TEXT);
    
    foreach ($BIOMETRICS as $biometric) {
        
        $biodata = optional_param($biometric, '', PARAM_RAW); // PARAM_RAW?
        $fields = optional_param($biometric.'_fields', '', PARAM_RAW); // PARAM_RAW?
        
        // Skip missing data
        if (strlen($biodata) === 0) {
            continue;
        }
        
        // check for existing record
        $unique = array('userid' => $userid, 'session' => $session, 'biometric' => $biometric);

        if ($DB->record_exists('bioauth_biodata', $unique)) {
            // update end time with time received
            $record = $DB->get_record('bioauth_biodata', $unique);
           
           $record->jsondata .= "\n" . $biodata;
           $record->quantity = substr_count($record->jsondata, "\n");
           $record->timeend = $time;
           $record->timemodified = $time;
            
           $DB->update_record('bioauth_biodata', $record);
        } else {
            // for new record, create start time
            $record = new stdClass();
            $record->userid = $userid;
            $record->ipaddress = ip2long($ipaddress);
            $record->session = $session;
            $record->useragent = $useragent;
            $record->appversion = $appversion;
            $record->task = $task;
            $record->tags = $tags;
            $record->biometric = $biometric;
            $record->jsondata = $fields . "\n" . $biodata;
            $record->quantity = substr_count($record->jsondata, "\n");
            $record->timemodified = $time;
            $record->timestart = $time;
            $record->timeend = $time;
            
            $DB->insert_record('bioauth_biodata', $record);
        }
    }
}

function bioauth_enroll_mobile_data($userid, $time) {
    global $DB;

    $platform = required_param('platform', PARAM_TEXT);
    $task = required_param('task', PARAM_TEXT);
    $jsondata = required_param('events', PARAM_TEXT);

    $biodata = new stdClass();
    $biodata->userid = $userid;
    $biodata->task = $task;
    $biodata->jsondata = $jsondata;
    $biodata->platform = $platform;
    $biodata->timemodified = $time;

    $DB->insert_record('bioauth_mobile_biodata', $biodata);
}

/**
 * Count the number of keystrokes collected (over all students/attempts)
 * for one quiz.
 *
 * @param object $quiz the quiz to count keystrokes for
 * @return int the number of keystrokes logged for the quiz
 */
function count_quiz_keystrokes($quiz) {
    global $DB;

    $numuserkeystrokes = array();

    $datarecords = $DB->get_records('bioauth_biodata', array('biometric' => 'keystroke', 'task' => 'quiz'.$quiz->id));
    foreach ($datarecords as $idx => $biodata) {
        if (!array_key_exists($biodata->userid, $numuserkeystrokes)) {
            $numuserkeystrokes[$biodata->userid] = 0;
        }
        $numuserkeystrokes[$biodata->userid] += $biodata->quantity;
        $numuserkeystrokes[$biodata->userid] = min(array($numuserkeystrokes[$biodata->userid], get_config('local_bioauth', 'minkeystrokesperquiz')));
    }

    return $numuserkeystrokes;
}

/**
 * Calculate the percent of data that is ready to be used for a
 * validation job. It is calculated as: 
 * (#keystrokes ready for each quiz)/(#quizzes * #students * #keystrokes required for each quiz)
 * Each student can only contribute 1/(#quizzes*#students)% so that if more
 * than enough keystrokes have been collected for a student, it will not
 * increase the percent of data that is needed. 
 *
 * @param object $job the validation job
 * @return int the percent of data ready, between 0 and 100
 */
function get_percent_data_ready($job) {
    global $DB;

    $coursecontext = get_context_instance(CONTEXT_COURSE, $job->courseid);
    if (!$students = get_users_by_capability($coursecontext, array('mod/quiz:attempt'), 'u.id, 1', '', '', '', '', '', false)) {
        return 0; // No students, cannot count data
    } else {
        $students = array_keys($students);
    }

    $quizzes = $DB->get_records('quiz', array('course' => $job->courseid));

    $numquizzes = count($quizzes);
    $numstudents =  count($students);

    if ($numstudents < 2) {
        return 0; // Need at least 2 students
    }

    $minkeystrokes = get_config('local_bioauth', 'minkeystrokesperquiz');
    $totalkeystrokes = $minkeystrokes * $numquizzes * $numstudents;
    $availablekeystrokes = 0;
    $quizkeystrokes = array();

    foreach ($quizzes as $quizidx => $quiz) {
        $availablekeystrokes += array_sum(count_quiz_keystrokes($quiz));
    }

    if ($totalkeystrokes > 0) {
        $percentdata = 100 * $availablekeystrokes/$totalkeystrokes;
    } else {
        $percentdata = 0;
    }

    return (int)$percentdata;
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
 * Run a quiz validation job that has been determined to have enough data ready
 * and is still active.
 * 
 * This returns immediately, starting the job as another process. The number of
 * jobs running should be monitored elsewhere.
 *
 * @param object $job the validation job
 */
function run_quiz_validation($job) {
    global $CFG;

    $errorratios = array(BIOAUTH_DECISION_NEUTRAL => 1.0, BIOAUTH_DECISION_CONVENIENT => 0.5, BIOAUTH_DECISION_SECURE => 1.5, );

    $jobparams = json_decode($job->jobparams);
    $errorratio = $errorratios[$jobparams->decisionmode];

    shell_exec("nohup java -Xmx512m -jar $CFG->dirroot/local/bioauth/bin/ssi.jar \
        $CFG->dbhost $CFG->dbname $CFG->dbuser $CFG->dbpass $CFG->prefix $job->courseid $jobparams->featureset $jobparams->knn $jobparams->minkeyfrequency $errorratio\
        >/dev/null 2>&1 & ");
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

        if (empty($agent)) {
            $agent = 'default';
        }

        if (isset($keycode[$agent]) && isset($keycode[$agent][$identifier])) {
            return $keycode[$agent][$identifier];
        } elseif (isset($keycode['default'][$identifier])) {
            return $keycode['default'][$identifier];
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
        } elseif (isset($keycode['default'][$identifier])) {
            return $keycode['default'][$identifier];
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
