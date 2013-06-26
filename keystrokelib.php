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
require_once ($CFG -> dirroot . '/local/bioauth/keys/en_US/keys.php');

function translate_keycode($locale, $keycode, $agent) {
    global $keycodes;
    
    if (array_key_exists($agent, $keycodes) && array_key_exists($keycode, $keycodes[$agent])) {
        return $keycodes[$agent][$keycode];
    } elseif (array_key_exists($keycode, $keycodes['default'])) {
        return $keycodes['default'][$keycode];
    } else {
        // error
        return 0;
    }
}

function translate_keystring($locale, $keystring) {
    global $keymap;
    
    if (array_key_exists($keystring, $keymap)) {
        return $keymap[$keystring];
    } else {
        // error
    }
}

function fetch_user_sessions($users) {
    $sessions = $DB->get_records_list('bioauth_sessions', 'id', $users);
    return $sessions;
}

function fetch_user_keystrokes($users) {
    global $DB;
    
    $sessions = $DB->get_records_list('bioauth_sessions', 'id', $users);

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
    
    $userkeystrokes = new DefaultArray(new DefaultArray());
    $rs = $DB->get_recordset('bioauth_demo_sessions');
    foreach ($rs as $record) {
        $keystrokes = json_decode($record->keystrokes);
        $userkeystrokes[$record->userid][$record->id] = $keystrokes;
    }
    $rs->close();

    return $userkeystrokes;
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
