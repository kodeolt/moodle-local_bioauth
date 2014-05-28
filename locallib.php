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
    $PAGE->requires->js(new moodle_url('/local/bioauth/biologger/js/biologger_1.0.1.js'), true);
    
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

function csv_str($data) {
    $outstream = fopen("php://temp", 'r+');
    fputcsv($outstream, $data, ',', '"');
    rewind($outstream);
    $csv = fgets($outstream);
    fclose($outstream);
    return strtok($csv, "\n");
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
           
           $record->csvdata .= "\n" . $biodata;
           $record->quantity = substr_count($record->csvdata, "\n");
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
            $record->csvdata = $fields . "\n" . $biodata;
            $record->quantity = substr_count($record->csvdata, "\n");
            $record->timemodified = $time;
            $record->timestart = $time;
            $record->timeend = $time;
            
            $DB->insert_record('bioauth_biodata', $record);
        }
    }
}

function bioauth_enroll_mobile_data($time) {
    global $DB;
    
    $ipaddress = $_SERVER['REMOTE_ADDR'];
    
    $identity = required_param('identity', PARAM_TEXT);
    $session = required_param('session', PARAM_TEXT);
    $platform = required_param('platform', PARAM_TEXT);
    $task = required_param('task', PARAM_TEXT);
    $quantity = required_param('quantity', PARAM_INT);
    $jsondata = required_param('events', PARAM_TEXT);

    $record = new stdClass();
    $record->identity = $identity;
    $record->session = $session;
    $record->ipaddress = ip2long($ipaddress);
    $record->platform = $platform;
    $record->task = $task;
    $record->quantity = $quantity;
    $record->jsondata = $jsondata;
    $record->timemodified = $time;

    $DB->insert_record('bioauth_mobiledata', $record);
}