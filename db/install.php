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
 * Disable the assignment module for new installs
 *
 * @package local_bioauth
 * @copyright 2013 Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();


/**
 * Post-install script
 */
function xmldb_local_bioauth_install() {
    global $CFG;
    global $DB;

    // Load the key strings/key codes from a csv file
    $keyids = array();
    $keycodes = array();
    if (($handle = fopen($CFG->dirroot . "/local/bioauth/models/keys.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $keyids[$data[0]] = 0;
            $num = count($data);
            $keycodes[$data[0]] = array_slice($data, 1);
        }
        fclose($handle);
    }
    
    // Save the keyid for later
    foreach (array_keys($keyids) as $keystring) {
        $keyids[$keystring] = $DB->insert_record('bioauth_keys', array('keystring' => $keystring), true);
    }
    
    // Mapping of key codes to key ids for creating keystroke sequences
    foreach ($keycodes as $keystring => $keycodes) {
        foreach ($keycodes as $keycode) {
            $DB->insert_record('bioauth_keycodes', array('keyid' => $keyids[$keystring], 'keycode' => $keycode), false);
        }
    }
    
    function csvkeyids($keys) {
        $ids = array();
        foreach ($keys as $key) {
            $ids[] = $keyids[$key]; 
        }
        return implode(',',$ids);
    }
    
    $keystrokefeaturefields = array('type', 'group1', 'group2', 'measure', 'distance');
    
    $keystrokefeatures = array(
    1 => array(DURATION, csvkeyids('a','b'), csvkeyids('a','b'), MEAN, 0),
    2 => array(T1, csvkeyids('a','b'), csvkeyids('a','b'), MEAN, 1),
    );
    
    $keystrokefallback = array(
    3 => 1,
    4 => 1,
    );
    
    $keystrokefeatureids = array();
    foreach ($keystrokefeatures as $featureid => $feature) {
        $keystrokefeatureids[$featureid] = $DB->insert_record('bioauth_keystroke_features', array_merge($keystrokefeaturefields, $feature), true);
    }

    foreach ($keystrokefallback as $node => $parent) {
        $DB->update_record('bioauth_keystroke_features', array('id' => $keystrokefeatureids[$node], 'fallback' => $keystrokefeatureids[$parent]));
    }
    // $enUSid = $DB->insert_record('bioauth_feature_sets')
    
    return true;
}
