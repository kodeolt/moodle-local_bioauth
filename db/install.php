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

global $CFG;
require_once ($CFG -> dirroot . '/local/bioauth/constants.php');

/**
 * Post-install script
 */
function xmldb_local_bioauth_install() {
    global $CFG;
    global $DB;

    // Load the key strings/key codes from a csv file
    $keyids = array();
    $keycodes = array();
    if (($handle = fopen($CFG -> dirroot . "/local/bioauth/models/keys.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $keyids[$data[0]] = 0;
            $num = count($data);
            $keycodes[$data[0]] = array_slice($data, 1);
        }
        fclose($handle);
    }

    // Save the keyid for later
    foreach (array_keys($keyids) as $keystring) {
        $keyids[$keystring] = $DB -> insert_record('bioauth_keys', array('keystring' => $keystring), true);
    }

    // Mapping of key codes to key ids for creating keystroke sequences
    foreach ($keycodes as $keystring => $keycodes) {
        foreach ($keycodes as $keycode) {
            $DB -> insert_record('bioauth_keycodes', array('keyid' => $keyids[$keystring], 'keycode' => $keycode), false);
        }
    }

    // $csvkeyids = function() use($keyids) {
    // $ids = array_map(function($k) {return $keyids[$k];}, func_get_args());
    // return implode(',',$ids);
    // };

    $csvkeyids = function() use ($keyids) {
        $ids = array();
        foreach (func_get_args() as $key) {
            $ids[] = $keyids[$key];
        }
        return implode(',', $ids);
    };

    $keystrokefeatures = array(
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('a', 'b'), $csvkeyids('a', 'b'), BIOAUTH_MEASURE_MEAN, 0), 
    2 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('b'), $csvkeyids('b'), BIOAUTH_MEASURE_MEAN, 0), 
    3 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('a'), $csvkeyids('a'), BIOAUTH_MEASURE_MEAN, 0), 
    4 => array(BIOAUTH_FEATURE_T1, $csvkeyids('a', 'b'), $csvkeyids('a', 'b'), BIOAUTH_MEASURE_MEAN, 1), 
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('b'), $csvkeyids('a'), BIOAUTH_MEASURE_MEAN, 1), 
    );

    $keystrokefallback = array(2 => 1, 3 => 1, 5 => 4, );

    $keystrokefeatureids = array();
    $keystrokefeaturefields = array('type', 'group1', 'group2', 'measure', 'distance');
    foreach ($keystrokefeatures as $featureid => $feature) {
        $keystrokefeatureids[$featureid] = $DB -> insert_record('bioauth_keystroke_features', array_combine($keystrokefeaturefields, $feature), true);
    }

    foreach ($keystrokefallback as $node => $parent) {
        $DB -> update_record('bioauth_keystroke_features', array('id' => $keystrokefeatureids[$node], 'fallback' => $keystrokefeatureids[$parent]));
    }

    $DB -> insert_record('bioauth_feature_sets', array('name' => 'Engish US Basic Keystroke', 'locale' => 'en_US', 'keystrokefeatures' => implode(',', array_keys($keystrokefeatureids)), 'stylometryfeatures' => ''));

    $sessionid = $DB -> insert_record('bioauth_demo_sessions', array('userid' => 1, 'locale' => 'en_US'));

    $keystrokeevents = array(
    array(1,$sessionid,1,0,110),
    array(1,$sessionid,1,120,210),
    array(1,$sessionid,1,200,300),
    array(1,$sessionid,2,290,350),
    array(1,$sessionid,2,370,400),
    array(1,$sessionid,1,390,450),
    array(1,$sessionid,2,475,520),
    array(1,$sessionid,1,550,630),
    array(1,$sessionid,1,603,660),
    array(1,$sessionid,2,655,700),
    );
    
    $keystrokeeventfields = array('userid', 'sessionid', 'keyid', 'presstime', 'releasetime');
    foreach ($keystrokeevents as $event) {
        $DB -> insert_record('bioauth_demo_keystrokes', array_combine($keystrokeeventfields, $event));
    }

    return true;
}
