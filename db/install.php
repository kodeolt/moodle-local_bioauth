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
    
    $progressbar = new progress_bar('local_bioauth_load_demo_events');
    $progressbar->create();
    $done = 0;

    $userit = new DirectoryIterator($CFG -> dirroot . '/local/bioauth/bootstrap');
    
    foreach ($userit as $user) {
        if (!$user->isDir()) continue;
        
        $sessionit = new DirectoryIterator($user->getPathname());
        foreach ($sessionit as $session) {
            if ('json' !== $session->getExtension()) continue;
            
            $jsonstring = file_get_contents($session->getPathname());
            
            $keystrokes = json_decode($jsonstring, true);
            for ($i = 0; $i < count($keystrokes); $i++) {
                $keystrokes[$i]['keyid'] = translate_keycode('en_US', (int)$keystrokes[$i]['keycode'], 'native');
                unset($keystrokes[$i]['keycode']);
            }
            
            $DB -> insert_record_raw('bioauth_demo_sessions', array('userid' => $user->getBasename(), 'locale' => 'en_US', 'keystrokes' => json_encode($keystrokes), 'stylometry' => ''), false, true);
            
            $done++;
            $progressbar->update($done, 1187, get_string('install_bootstrap', 'local_bioauth'));
        }
    }
}

function load_keystroke_features() {
    $allkeys = 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,0,1,2,3,4,5,6,7,8,9,COMMA,PERIOD,SEMICOLON,SLASH,SPACE,BACKSPACE,SHIFT,ENTER';

    $letters = 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z';
    $vowels = 'A,E,I,O,U';
    $cons1 = 'T,N,S,R,H';
    $cons2 = 'L,D,C,P,F';
    $cons3 = 'M,W,Y,B,G';
    $cons4 = 'J,K,Q,V,X,Z';
    
    $visiblekeys = 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,0,1,2,3,4,5,6,7,8,9,COMMA,PERIOD,SEMICOLON,SLASH';
    $invisiblekeys = 'SPACE,BACKSPACE,SHIFT,ENTER';
    
    $lefthand = 'Q,W,E,R,T,A,S,D,F,G,Z,X,C,V,B,1,2,3,4,5';
    $righthand = 'Y,U,I,O,P,H,J,K,L,N,M,6,7,8,9,0';
    
    $leftlittle = 'A,Z,1,Q';
    $leftring = 'S,X,2,W';
    $leftmiddle = 'D,C,4,E';
    $leftindex = 'F,B,G,R,4,T,5,V';
    
    $rightlittle = 'SEMICOLON,SLASH,0,P';
    $rightring = 'L,PERIOD,9,O';
    $rightmiddle = 'K,COMMA,8,I';
    $rightindex = 'H,M,J,Y,6,U,7,N';
    
    $leftletters = 'Q,W,E,R,T,A,S,D,F,G,Z,X,C,V,B';
    $rightletters = 'Y,U,I,O,P,H,J,K,L,N,M';
    
    $keystrokefeatures = array(
    
    /* 
     * Mean Durations
     */
    1 => array(BIOAUTH_FEATURE_DURATION, $visiblekeys, $visiblekeys, BIOAUTH_MEASURE_MEAN, 0),
    
    2 => array(BIOAUTH_FEATURE_DURATION, $lefthand, $lefthand, BIOAUTH_MEASURE_MEAN, 0),
    3 => array(BIOAUTH_FEATURE_DURATION, $righthand, $righthand, BIOAUTH_MEASURE_MEAN, 0),
    
    4 => array(BIOAUTH_FEATURE_DURATION, $leftlittle, $leftlittle, BIOAUTH_MEASURE_MEAN, 0),
    5 => array(BIOAUTH_FEATURE_DURATION, $leftring, $leftring, BIOAUTH_MEASURE_MEAN, 0),
    6 => array(BIOAUTH_FEATURE_DURATION, $leftmiddle, $leftmiddle, BIOAUTH_MEASURE_MEAN, 0),
    7 => array(BIOAUTH_FEATURE_DURATION, $leftindex, $leftindex, BIOAUTH_MEASURE_MEAN, 0),
    8 => array(BIOAUTH_FEATURE_DURATION, $rightlittle, $rightlittle, BIOAUTH_MEASURE_MEAN, 0),
    9 => array(BIOAUTH_FEATURE_DURATION, $rightring, $rightring, BIOAUTH_MEASURE_MEAN, 0),
    10 => array(BIOAUTH_FEATURE_DURATION, $rightmiddle, $rightmiddle, BIOAUTH_MEASURE_MEAN, 0),
    11 => array(BIOAUTH_FEATURE_DURATION, $rightindex, $rightindex, BIOAUTH_MEASURE_MEAN, 0),
    
    // Left little
    12 => array(BIOAUTH_FEATURE_DURATION, 'A', 'A', BIOAUTH_MEASURE_MEAN, 0),
    13 => array(BIOAUTH_FEATURE_DURATION, 'Z', 'Z', BIOAUTH_MEASURE_MEAN, 0),
    14 => array(BIOAUTH_FEATURE_DURATION, '1', '1', BIOAUTH_MEASURE_MEAN, 0),
    15 => array(BIOAUTH_FEATURE_DURATION, 'Q', 'Q', BIOAUTH_MEASURE_MEAN, 0),
    // Left ring
    16 => array(BIOAUTH_FEATURE_DURATION, 'S', 'S', BIOAUTH_MEASURE_MEAN, 0),
    17 => array(BIOAUTH_FEATURE_DURATION, 'X', 'X', BIOAUTH_MEASURE_MEAN, 0),
    18 => array(BIOAUTH_FEATURE_DURATION, '2', '2', BIOAUTH_MEASURE_MEAN, 0),
    19 => array(BIOAUTH_FEATURE_DURATION, 'W', 'W', BIOAUTH_MEASURE_MEAN, 0),
    // Left middle
    20 => array(BIOAUTH_FEATURE_DURATION, 'D', 'D', BIOAUTH_MEASURE_MEAN, 0),
    21 => array(BIOAUTH_FEATURE_DURATION, 'C', 'C', BIOAUTH_MEASURE_MEAN, 0),
    22 => array(BIOAUTH_FEATURE_DURATION, '3', '3', BIOAUTH_MEASURE_MEAN, 0),
    23 => array(BIOAUTH_FEATURE_DURATION, 'E', 'E', BIOAUTH_MEASURE_MEAN, 0),
    // Left index
    24 => array(BIOAUTH_FEATURE_DURATION, 'F', 'F', BIOAUTH_MEASURE_MEAN, 0),
    25 => array(BIOAUTH_FEATURE_DURATION, 'B', 'B', BIOAUTH_MEASURE_MEAN, 0),
    26 => array(BIOAUTH_FEATURE_DURATION, 'G', 'G', BIOAUTH_MEASURE_MEAN, 0),
    27 => array(BIOAUTH_FEATURE_DURATION, 'R', 'R', BIOAUTH_MEASURE_MEAN, 0),
    28 => array(BIOAUTH_FEATURE_DURATION, '4', '4', BIOAUTH_MEASURE_MEAN, 0),
    29 => array(BIOAUTH_FEATURE_DURATION, 'T', 'T', BIOAUTH_MEASURE_MEAN, 0),
    30 => array(BIOAUTH_FEATURE_DURATION, '5', '5', BIOAUTH_MEASURE_MEAN, 0),
    31 => array(BIOAUTH_FEATURE_DURATION, 'V', 'V', BIOAUTH_MEASURE_MEAN, 0),
    // Right index
    32 => array(BIOAUTH_FEATURE_DURATION, 'H', 'H', BIOAUTH_MEASURE_MEAN, 0),
    33 => array(BIOAUTH_FEATURE_DURATION, 'M', 'M', BIOAUTH_MEASURE_MEAN, 0),
    34 => array(BIOAUTH_FEATURE_DURATION, 'J', 'J', BIOAUTH_MEASURE_MEAN, 0),
    35 => array(BIOAUTH_FEATURE_DURATION, 'Y', 'Y', BIOAUTH_MEASURE_MEAN, 0),
    36 => array(BIOAUTH_FEATURE_DURATION, '6', '6', BIOAUTH_MEASURE_MEAN, 0),
    37 => array(BIOAUTH_FEATURE_DURATION, 'U', 'U', BIOAUTH_MEASURE_MEAN, 0),
    38 => array(BIOAUTH_FEATURE_DURATION, '7', '7', BIOAUTH_MEASURE_MEAN, 0),
    39 => array(BIOAUTH_FEATURE_DURATION, 'N', 'N', BIOAUTH_MEASURE_MEAN, 0),
    // Right middle
    40 => array(BIOAUTH_FEATURE_DURATION, 'K', 'K', BIOAUTH_MEASURE_MEAN, 0),
    41 => array(BIOAUTH_FEATURE_DURATION, 'COMMA', 'COMMA', BIOAUTH_MEASURE_MEAN, 0),
    42 => array(BIOAUTH_FEATURE_DURATION, '8', '8', BIOAUTH_MEASURE_MEAN, 0),
    43 => array(BIOAUTH_FEATURE_DURATION, 'I', 'I', BIOAUTH_MEASURE_MEAN, 0),
    // Right ring
    44 => array(BIOAUTH_FEATURE_DURATION, 'L', 'L', BIOAUTH_MEASURE_MEAN, 0),
    45 => array(BIOAUTH_FEATURE_DURATION, 'PERIOD', 'PERIOD', BIOAUTH_MEASURE_MEAN, 0),
    46 => array(BIOAUTH_FEATURE_DURATION, '9', '9', BIOAUTH_MEASURE_MEAN, 0),
    47 => array(BIOAUTH_FEATURE_DURATION, 'O', '0', BIOAUTH_MEASURE_MEAN, 0),
    // Right little
    48 => array(BIOAUTH_FEATURE_DURATION, 'SEMICOLON', 'SEMICOLON', BIOAUTH_MEASURE_MEAN, 0),
    49 => array(BIOAUTH_FEATURE_DURATION, 'SLASH', 'SLASH', BIOAUTH_MEASURE_MEAN, 0),
    50 => array(BIOAUTH_FEATURE_DURATION, '0', '0', BIOAUTH_MEASURE_MEAN, 0),
    51 => array(BIOAUTH_FEATURE_DURATION, 'P', 'P', BIOAUTH_MEASURE_MEAN, 0),
    
    /*
     * Mean type 1 transitions
     */
    52 => array(BIOAUTH_FEATURE_T1, $letters, $letters, BIOAUTH_MEASURE_MEAN, 1),
    53 => array(BIOAUTH_FEATURE_T1, $leftletters, $leftletters, BIOAUTH_MEASURE_MEAN, 1),
    54 => array(BIOAUTH_FEATURE_T1, $rightletters, $rightletters, BIOAUTH_MEASURE_MEAN, 1),
    55 => array(BIOAUTH_FEATURE_T1, $leftletters, $rightletters, BIOAUTH_MEASURE_MEAN, 1),
    56 => array(BIOAUTH_FEATURE_T1, $rightletters, $leftletters, BIOAUTH_MEASURE_MEAN, 1),
    // Left/Left
    57 => array(BIOAUTH_FEATURE_T1, 'E', 'R', BIOAUTH_MEASURE_MEAN, 1),
    58 => array(BIOAUTH_FEATURE_T1, 'A', 'T', BIOAUTH_MEASURE_MEAN, 1),
    59 => array(BIOAUTH_FEATURE_T1, 'S', 'T', BIOAUTH_MEASURE_MEAN, 1),
    60 => array(BIOAUTH_FEATURE_T1, 'R', 'E', BIOAUTH_MEASURE_MEAN, 1),
    61 => array(BIOAUTH_FEATURE_T1, 'E', 'S', BIOAUTH_MEASURE_MEAN, 1),
    62 => array(BIOAUTH_FEATURE_T1, 'E', 'A', BIOAUTH_MEASURE_MEAN, 1),
    // Right/Right
    63 => array(BIOAUTH_FEATURE_T1, 'I', 'N', BIOAUTH_MEASURE_MEAN, 1),
    64 => array(BIOAUTH_FEATURE_T1, 'O', 'N', BIOAUTH_MEASURE_MEAN, 1),
    // Left/Right
    65 => array(BIOAUTH_FEATURE_T1, 'T', 'H', BIOAUTH_MEASURE_MEAN, 1),
    66 => array(BIOAUTH_FEATURE_T1, 'E', 'N', BIOAUTH_MEASURE_MEAN, 1),
    67 => array(BIOAUTH_FEATURE_T1, 'A', 'N', BIOAUTH_MEASURE_MEAN, 1),
    68 => array(BIOAUTH_FEATURE_T1, 'T', 'I', BIOAUTH_MEASURE_MEAN, 1),
    // Right/Left
    69 => array(BIOAUTH_FEATURE_T1, 'N', 'D', BIOAUTH_MEASURE_MEAN, 1),
    70 => array(BIOAUTH_FEATURE_T1, 'H', 'E', BIOAUTH_MEASURE_MEAN, 1),
    71 => array(BIOAUTH_FEATURE_T1, 'O', 'R', BIOAUTH_MEASURE_MEAN, 1),
    
    );


    $keystrokefeatureids = array();
    $keystrokefeaturefields = array('type', 'group1', 'group2', 'measure', 'distance');
    foreach ($keystrokefeatures as $featureid => $feature) {
        $keystrokefeatureids[$featureid] = $DB -> insert_record('bioauth_keystroke_features', array_combine($keystrokefeaturefields, $feature), true);
    }

    // $keystrokefallback = array(2 => 1, 3 => 1, 5 => 4, );
    // foreach ($keystrokefallback as $node => $parent) {
        // $DB -> update_record('bioauth_keystroke_features', array('id' => $keystrokefeatureids[$node], 'fallback' => $keystrokefeatureids[$parent]));
    // }

    $DB -> insert_record('bioauth_feature_sets', array('name' => 'Engish US Basic Keystroke', 'locale' => 'en_US', 'keystrokefeatures' => implode(',', array_keys($keystrokefeatureids)), 'stylometryfeatures' => ''));
    
}

/**
 * Post-install script
 */
function xmldb_local_bioauth_install() {
    global $DB;

    // load_demo_events();

    
    return true;
}
