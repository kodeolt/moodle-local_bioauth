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


function fetch_user_sessions($users) {
    $sessions = $DB->get_records_list('bioauth_quiz_sessions', 'id', $users);
    return $sessions;
}

function fetch_user_keystrokes($users) {
    global $DB;
    
    $sessions = $DB->get_records_list('bioauth_quiz_sessions', 'id', $users);

    $userkeystrokes = new DefaultArray(new DefaultArray());
    foreach ($sessions as $session) {
        $userkeystrokes[$session->userid][$session->id] = array_values($DB->get_records('bioauth_keystrokes', array('userid' => $session->userid, 'sessionid' => $session->id), 'timepress', '*'));
    }
    
    return $userkeystrokes;
}

function fetch_demo_keystrokes() {
    global $DB;
    // ini_set('memory_limit', '-1');
    // $sessions = $DB->get_records('bioauth_demo_sessions');
    
    //$userdata = new DefaultArray(new DefaultArray());
    $userdata = array();
    $rs = $DB->get_recordset('bioauth_demo_quiz_sessions');
    foreach ($rs as $record) {
        $data = json_decode($record->data);
        $userdata[$record->userid][$record->id] = $data;
    }
    $rs->close();
    return $userdata;
}

function create_keystroke_features($featuresetid) {
    global $DB;
    
    $features = array();
    
    $featureids = $DB->get_record('bioauth_feature_sets', array('id' => $featuresetid), 'keystrokefeatures', MUST_EXIST);
    // print_r($featureids);
    // foreach (explode(',', $featureids->keystrokefeatures) as $id) {
        // $feature = $DB->get_records_list('bioauth_keystroke_features', array('id' => $id));
    // }
    $features = $DB->get_records_list('bioauth_keystroke_features', 'id', explode(',', $featureids->keystrokefeatures));
    // print_r($features);
    foreach ($features as $featureid => $feature) {
        if ($feature->fallback) {
            $feature->fallback =& $features[$feature->fallback];
        }
        
        $feature->group1 = explode(',', $feature->group1);
        $feature->group2 = explode(',', $feature->group2);
    }
    
    return $features;
}
