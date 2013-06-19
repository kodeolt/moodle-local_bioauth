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
require_once ($CFG -> dirroot . '/local/bioauth/util.php');
require_once ($CFG -> dirroot . '/local/bioauth/constants.php');

/**
 * Load all of the key definitions that came with this installation.
 * 
 * This is how the keys for each agent and locale are defined.
 * See [dev doc link] for more information on how to create key files for new agents or locales.
 * 
 * @return array an array with the key ids
 * 
 */
function load_keys() {
    global $CFG;
    global $DB;
    
    $localeit = new DirectoryIterator($CFG -> dirroot . '/local/bioauth/keys');
    $masterkeys = array();
    $localeagentkeys = new DefaultArray(new DefaultArray());
    
    foreach ($localeit as $locale) {
        if ($locale->isDot()) continue;
        $agentit = new DirectoryIterator($locale->getPathname());
        foreach ($agentit as $agent) {
            if ($agent->isDot()) continue;
            
            $keycodes = load_csv($agent->getPathname());
            foreach(array_keys($keycodes) as $keystring) {
                $masterkeys[$keystring] = 0;
            }
            
            $localeagentkeys[$locale->getFilename()][$agent->getBasename('.csv')] = $keycodes;
        }
    }

    // Save the keyid for later
    foreach (array_keys($masterkeys) as $keystring) {
        $masterkeys[$keystring] = $DB -> insert_record('bioauth_keys', array('keystring' => $keystring), true);
    }

     // Mapping of key codes to key ids for various agents and locales
    foreach ($localeagentkeys as $locale => $agentkeys) {
        foreach ($agentkeys as $agent => $keys) {
            foreach ($keys as $keystring => $keycodes) {
                foreach ($keycodes as $keycode) {
                    $DB -> insert_record('bioauth_keycodes', array('locale' => $locale, 'agent' => $agent, 'keycode' => $keycode, 'keyid' => $masterkeys[$keystring]), false);
                }
            }
        }
    }

    return $masterkeys;
}

/**
 * Post-install script
 */
function xmldb_local_bioauth_install() {
    global $DB;

    // Load the key strings/key codes from a csv file
    $keyids = load_keys();

    $csvkeyids = function() use ($keyids) {
        $ids = array();
        foreach (func_get_args() as $key) {
            $ids[] = $keyids[$key];
        }
        return implode(',', $ids);
    };

    $keystrokefeatures = array(
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('A', 'B'), $csvkeyids('A', 'B'), BIOAUTH_MEASURE_MEAN, 0), 
    2 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('B'), $csvkeyids('B'), BIOAUTH_MEASURE_MEAN, 0), 
    3 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('A'), $csvkeyids('A'), BIOAUTH_MEASURE_MEAN, 0), 
    4 => array(BIOAUTH_FEATURE_T1, $csvkeyids('A', 'B'), $csvkeyids('A', 'B'), BIOAUTH_MEASURE_MEAN, 1), 
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('B'), $csvkeyids('A'), BIOAUTH_MEASURE_MEAN, 1), 
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
