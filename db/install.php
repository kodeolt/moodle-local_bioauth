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
require_once ($CFG -> dirroot . '/local/bioauth/keystrokelib.php');

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
        $masterkeys[$keystring] = $DB -> insert_record_raw('bioauth_keys', array('keystring' => $keystring), true, true);
    }

     // Mapping of key codes to key ids for various agents and locales
    foreach ($localeagentkeys as $locale => $agentkeys) {
        foreach ($agentkeys as $agent => $keys) {
            foreach ($keys as $keystring => $keycodes) {
                foreach ($keycodes as $keycode) {
                    $DB -> insert_record_raw('bioauth_keycodes', array('locale' => $locale, 'agent' => $agent, 'keycode' => $keycode, 'keyid' => $masterkeys[$keystring]), false, true);
                }
            }
        }
    }

    return $masterkeys;
}

function load_demo_events() {
    global $CFG;
    global $DB;
    
    //$userevents = new DefaultArray(new DefaultArray(new ArrayObject()));
    $fields = array('userid', 'sessionid', 'keyid', 'timepress', 'timerelease');
    if (($handle = fopen($CFG -> dirroot . '/local/bioauth/bootstrap/events.csv', "r")) !== FALSE) {
        while (($csvdata = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (5 === count($csvdata)) {
                    $csvdata[2] = translate_keycode('native', 'en_US', $csvdata[2]);
                    //$userevents[$csvdata[0]][$csvdata[1]][] = array_slice($csvdata, 2);
                    $DB -> insert_record_raw('bioauth_demo_keystrokes', array_combine($fields, $csvdata), false, true);
                }
        }
        fclose($handle);
    }
}

/**
 * Post-install script
 */
function xmldb_local_bioauth_install() {
    global $DB;

    // Load the key strings/key codes from a csv file
    $keyids = load_keys();

    load_demo_events();

    $csvkeyids = function($csvkeystring) use ($keyids) {
        $ids = array();
        foreach (explode(',', $csvkeystring) as $key) {
            $ids[] = $keyids[$key];
        }
        return implode(',', $ids);
    };

    $alphabet = $csvkeyids('A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z');
    $vowels = $csvkeyids('A,E,I,O,U');
    $cons1 = $csvkeyids('T,N,S,R,H');
    $cons2 = $csvkeyids('L,D,C,P,F');
    $cons3 = $csvkeyids('M,W,Y,B,G');
    $cons4 = $csvkeyids('J,K,Q,V,X,Z');
    
    $allkeys = $csvkeyids('A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,0,1,2,3,4,5,6,7,8,9,COMMA,PERIOD,SEMICOLON,SLASH,SPACE,BACKSPACE,SHIFT,RETURN');
    
    $visiblekeys = $csvkeyids('A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,0,1,2,3,4,5,6,7,8,9,COMMA,PERIOD,SEMICOLON,SLASH');
    $invisiblekeys = $csvkeyids('SPACE,BACKSPACE,SHIFT,RETURN');
    
    $lefthand = $csvkeyids('Q,W,E,R,T,A,S,D,F,G,Z,X,C,V,B');
    $righthand = $csvkeyids('Y,U,I,O,P,H,J,K,L,N,M');
    
    $leftlittle = $csvkeyids('A,Z,1,Q');
    $leftring = $csvkeyids('S,X,2,W');
    $leftmiddle = $csvkeyids('D,C,4,E');
    $leftindex = $csvkeyids('F,B,G,R,4,T,5,V');
    
    $rightlittle = $csvkeyids('SEMICOLON,SLASH,0,P');
    $rightring = $csvkeyids('L,PERIOD,9,O');
    $rightmiddle = $csvkeyids('K,COMMA,8,I');
    $rightindex = $csvkeyids('H,M,J,Y,6,U,7,N');
    
    $keystrokefeatures = array(
    
    /* 
     * Mean Durations 
     */
    1 => array(BIOAUTH_FEATURE_DURATION, $visiblekeys, $visiblekeys, BIOAUTH_MEASURE_MEAN, 0),
    
    1 => array(BIOAUTH_FEATURE_DURATION, $lefthand, $lefthand, BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $righthand, $righthand, BIOAUTH_MEASURE_MEAN, 0),
    
    1 => array(BIOAUTH_FEATURE_DURATION, $leftlittle, $leftlittle, BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $leftring, $leftring, BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $leftmiddle, $leftmiddle, BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $leftindex, $leftindex, BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $rightlittle, $rightlittle, BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $rightring, $rightring, BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $rightmiddle, $rightmiddle, BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $rightindex, $rightindex, BIOAUTH_MEASURE_MEAN, 0),
    
    // Left little
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('A'), $csvkeyids('A'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('Z'), $csvkeyids('Z'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('1'), $csvkeyids('1'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('Q'), $csvkeyids('Q'), BIOAUTH_MEASURE_MEAN, 0),
    // Left ring
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('S'), $csvkeyids('S'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('X'), $csvkeyids('X'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('2'), $csvkeyids('2'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('W'), $csvkeyids('W'), BIOAUTH_MEASURE_MEAN, 0),
    // Left middle
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('D'), $csvkeyids('D'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('C'), $csvkeyids('C'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('3'), $csvkeyids('3'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('E'), $csvkeyids('E'), BIOAUTH_MEASURE_MEAN, 0),
    // Left index
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('F'), $csvkeyids('F'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('B'), $csvkeyids('B'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('G'), $csvkeyids('G'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('R'), $csvkeyids('R'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('4'), $csvkeyids('4'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('T'), $csvkeyids('T'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('5'), $csvkeyids('5'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('V'), $csvkeyids('V'), BIOAUTH_MEASURE_MEAN, 0),
    // Right index
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('H'), $csvkeyids('H'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('M'), $csvkeyids('M'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('J'), $csvkeyids('J'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('Y'), $csvkeyids('Y'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('6'), $csvkeyids('6'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('U'), $csvkeyids('U'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('7'), $csvkeyids('7'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('N'), $csvkeyids('N'), BIOAUTH_MEASURE_MEAN, 0),
    // Right middle
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('K'), $csvkeyids('K'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('COMMA'), $csvkeyids('COMMA'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('8'), $csvkeyids('8'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('I'), $csvkeyids('I'), BIOAUTH_MEASURE_MEAN, 0),
    // Right ring
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('L'), $csvkeyids('L'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('PERIOD'), $csvkeyids('PERIOD'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('9'), $csvkeyids('9'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('O'), $csvkeyids('0'), BIOAUTH_MEASURE_MEAN, 0),
    // Right little
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('SEMICOLON'), $csvkeyids('SEMICOLON'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('SLASH'), $csvkeyids('SLASH'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('0'), $csvkeyids('0'), BIOAUTH_MEASURE_MEAN, 0),
    1 => array(BIOAUTH_FEATURE_DURATION, $csvkeyids('P'), $csvkeyids('P'), BIOAUTH_MEASURE_MEAN, 0),
    
    /*
     * Mean type 1 transitions
     */
    5 => array(BIOAUTH_FEATURE_T1, $letters, $letters, BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $leftletters, $leftletters, BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $rightletters, $rightletters, BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $leftletters, $rightletters, BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $rightletters, $leftletters, BIOAUTH_MEASURE_MEAN, 1),
    // Left/Left
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('E'), $csvkeyids('R'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('A'), $csvkeyids('T'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('S'), $csvkeyids('T'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('R'), $csvkeyids('E'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('E'), $csvkeyids('S'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('E'), $csvkeyids('A'), BIOAUTH_MEASURE_MEAN, 1),
    // Right/Right
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('I'), $csvkeyids('N'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('O'), $csvkeyids('N'), BIOAUTH_MEASURE_MEAN, 1),
    // Left/Right
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('T'), $csvkeyids('H'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('E'), $csvkeyids('N'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('A'), $csvkeyids('N'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('T'), $csvkeyids('I'), BIOAUTH_MEASURE_MEAN, 1),
    // Right/Left
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('N'), $csvkeyids('D'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('H'), $csvkeyids('E'), BIOAUTH_MEASURE_MEAN, 1),
    5 => array(BIOAUTH_FEATURE_T1, $csvkeyids('O'), $csvkeyids('R'), BIOAUTH_MEASURE_MEAN, 1),
    
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
    
    $keystrokeeventfields = array('userid', 'sessionid', 'keyid', 'timepress', 'timerelease');
    foreach ($keystrokeevents as $event) {
        $DB -> insert_record('bioauth_demo_keystrokes', array_combine($keystrokeeventfields, $event));
    }

    return true;
}
