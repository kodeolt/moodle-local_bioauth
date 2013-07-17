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
require_once ($CFG -> dirroot . '/local/bioauth/locallib.php');

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
        
        $quizit = new DirectoryIterator($user->getPathname());
        foreach ($quizit as $quiz) {
            if (!$quiz->isDir()) continue;
            
            $sessionit = new DirectoryIterator($quiz->getPathname());
            foreach ($sessionit as $session) {
                if ('json' !== $session->getExtension()) continue;
                
                $jsonstring = file_get_contents($session->getPathname());
                $DB -> insert_record_raw('bioauth_demo_quiz_sessions', array('userid' => $user->getBasename(), 'quizid' => $quiz->getBasename(), 'locale' => 'en_US', 'data' => $jsonstring), false, true);
                $done++;
                $progressbar->update($done, 1187, get_string('install_bootstrap', 'local_bioauth'));
            }
        }
    }
}

function load_keystroke_features() {
    global $DB;
    
    $allkeys = 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,0,1,2,3,4,5,6,7,8,9,comma,period,semicolon,slash,space,backspace,shift,enter';

    $letters = 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z';
    $vowels = 'a,e,i,o,u';
    $cons1 = 't,n,s,r,h';
    $cons2 = 'l,d,c,p,f';
    $cons3 = 'm,w,y,b,g';
    $cons4 = 'j,k,q,v,x,z';
    
    $visiblekeys = 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,0,1,2,3,4,5,6,7,8,9,comma,period,semicolon,slash';
    $invisiblekeys = 'space,backspace,shift,enter';
    
    $lefthand = 'q,w,e,r,t,a,s,d,f,g,z,x,c,v,b,1,2,3,4,5';
    $righthand = 'y,u,i,o,p,h,j,k,l,n,m,6,7,8,9,0';
    
    $leftlittle = 'a,z,1,q';
    $leftring = 's,x,2,w';
    $leftmiddle = 'd,c,4,e';
    $leftindex = 'f,b,g,r,4,t,5,v';
    
    $rightlittle = 'semicolon,slash,0,p';
    $rightring = 'l,period,9,o';
    $rightmiddle = 'k,comma,8,i';
    $rightindex = 'h,m,j,y,6,u,7,n';
    
    $leftletters = 'q,w,e,r,t,a,s,d,f,g,z,x,c,v,b';
    $rightletters = 'y,u,i,o,p,h,j,k,l,n,m';
    
    $keystrokefeatures = array(
    
    /* 
     * mean durations
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
    
    // left little
    12 => array(BIOAUTH_FEATURE_DURATION, 'a', 'a', BIOAUTH_MEASURE_MEAN, 0),
    13 => array(BIOAUTH_FEATURE_DURATION, 'z', 'z', BIOAUTH_MEASURE_MEAN, 0),
    14 => array(BIOAUTH_FEATURE_DURATION, '1', '1', BIOAUTH_MEASURE_MEAN, 0),
    15 => array(BIOAUTH_FEATURE_DURATION, 'q', 'q', BIOAUTH_MEASURE_MEAN, 0),
    // left ring
    16 => array(BIOAUTH_FEATURE_DURATION, 's', 's', BIOAUTH_MEASURE_MEAN, 0),
    17 => array(BIOAUTH_FEATURE_DURATION, 'x', 'x', BIOAUTH_MEASURE_MEAN, 0),
    18 => array(BIOAUTH_FEATURE_DURATION, '2', '2', BIOAUTH_MEASURE_MEAN, 0),
    19 => array(BIOAUTH_FEATURE_DURATION, 'w', 'w', BIOAUTH_MEASURE_MEAN, 0),
    // left middle
    20 => array(BIOAUTH_FEATURE_DURATION, 'd', 'd', BIOAUTH_MEASURE_MEAN, 0),
    21 => array(BIOAUTH_FEATURE_DURATION, 'c', 'c', BIOAUTH_MEASURE_MEAN, 0),
    22 => array(BIOAUTH_FEATURE_DURATION, '3', '3', BIOAUTH_MEASURE_MEAN, 0),
    23 => array(BIOAUTH_FEATURE_DURATION, 'e', 'e', BIOAUTH_MEASURE_MEAN, 0),
    // left index
    24 => array(BIOAUTH_FEATURE_DURATION, 'f', 'f', BIOAUTH_MEASURE_MEAN, 0),
    25 => array(BIOAUTH_FEATURE_DURATION, 'b', 'b', BIOAUTH_MEASURE_MEAN, 0),
    26 => array(BIOAUTH_FEATURE_DURATION, 'g', 'g', BIOAUTH_MEASURE_MEAN, 0),
    27 => array(BIOAUTH_FEATURE_DURATION, 'r', 'r', BIOAUTH_MEASURE_MEAN, 0),
    28 => array(BIOAUTH_FEATURE_DURATION, '4', '4', BIOAUTH_MEASURE_MEAN, 0),
    29 => array(BIOAUTH_FEATURE_DURATION, 't', 't', BIOAUTH_MEASURE_MEAN, 0),
    30 => array(BIOAUTH_FEATURE_DURATION, '5', '5', BIOAUTH_MEASURE_MEAN, 0),
    31 => array(BIOAUTH_FEATURE_DURATION, 'v', 'v', BIOAUTH_MEASURE_MEAN, 0),
    // right index
    32 => array(BIOAUTH_FEATURE_DURATION, 'h', 'h', BIOAUTH_MEASURE_MEAN, 0),
    33 => array(BIOAUTH_FEATURE_DURATION, 'm', 'm', BIOAUTH_MEASURE_MEAN, 0),
    34 => array(BIOAUTH_FEATURE_DURATION, 'j', 'j', BIOAUTH_MEASURE_MEAN, 0),
    35 => array(BIOAUTH_FEATURE_DURATION, 'y', 'y', BIOAUTH_MEASURE_MEAN, 0),
    36 => array(BIOAUTH_FEATURE_DURATION, '6', '6', BIOAUTH_MEASURE_MEAN, 0),
    37 => array(BIOAUTH_FEATURE_DURATION, 'u', 'u', BIOAUTH_MEASURE_MEAN, 0),
    38 => array(BIOAUTH_FEATURE_DURATION, '7', '7', BIOAUTH_MEASURE_MEAN, 0),
    39 => array(BIOAUTH_FEATURE_DURATION, 'n', 'n', BIOAUTH_MEASURE_MEAN, 0),
    // right middle
    40 => array(BIOAUTH_FEATURE_DURATION, 'k', 'k', BIOAUTH_MEASURE_MEAN, 0),
    41 => array(BIOAUTH_FEATURE_DURATION, 'comma', 'comma', BIOAUTH_MEASURE_MEAN, 0),
    42 => array(BIOAUTH_FEATURE_DURATION, '8', '8', BIOAUTH_MEASURE_MEAN, 0),
    43 => array(BIOAUTH_FEATURE_DURATION, 'i', 'i', BIOAUTH_MEASURE_MEAN, 0),
    // right ring
    44 => array(BIOAUTH_FEATURE_DURATION, 'l', 'l', BIOAUTH_MEASURE_MEAN, 0),
    45 => array(BIOAUTH_FEATURE_DURATION, 'period', 'period', BIOAUTH_MEASURE_MEAN, 0),
    46 => array(BIOAUTH_FEATURE_DURATION, '9', '9', BIOAUTH_MEASURE_MEAN, 0),
    47 => array(BIOAUTH_FEATURE_DURATION, 'o', 'o', BIOAUTH_MEASURE_MEAN, 0),
    // right little
    48 => array(BIOAUTH_FEATURE_DURATION, 'semicolon', 'semicolon', BIOAUTH_MEASURE_MEAN, 0),
    49 => array(BIOAUTH_FEATURE_DURATION, 'slash', 'slash', BIOAUTH_MEASURE_MEAN, 0),
    50 => array(BIOAUTH_FEATURE_DURATION, '0', '0', BIOAUTH_MEASURE_MEAN, 0),
    51 => array(BIOAUTH_FEATURE_DURATION, 'p', 'p', BIOAUTH_MEASURE_MEAN, 0),
    
    /*
     * mean type 1 transitions
     */
    52 => array(BIOAUTH_FEATURE_T1, $letters, $letters, BIOAUTH_MEASURE_MEAN, 1),
    53 => array(BIOAUTH_FEATURE_T1, $leftletters, $leftletters, BIOAUTH_MEASURE_MEAN, 1),
    54 => array(BIOAUTH_FEATURE_T1, $rightletters, $rightletters, BIOAUTH_MEASURE_MEAN, 1),
    55 => array(BIOAUTH_FEATURE_T1, $leftletters, $rightletters, BIOAUTH_MEASURE_MEAN, 1),
    56 => array(BIOAUTH_FEATURE_T1, $rightletters, $leftletters, BIOAUTH_MEASURE_MEAN, 1),
    // left/left
    57 => array(BIOAUTH_FEATURE_T1, 'e', 'r', BIOAUTH_MEASURE_MEAN, 1),
    58 => array(BIOAUTH_FEATURE_T1, 'a', 't', BIOAUTH_MEASURE_MEAN, 1),
    59 => array(BIOAUTH_FEATURE_T1, 's', 't', BIOAUTH_MEASURE_MEAN, 1),
    60 => array(BIOAUTH_FEATURE_T1, 'r', 'e', BIOAUTH_MEASURE_MEAN, 1),
    61 => array(BIOAUTH_FEATURE_T1, 'e', 's', BIOAUTH_MEASURE_MEAN, 1),
    62 => array(BIOAUTH_FEATURE_T1, 'e', 'a', BIOAUTH_MEASURE_MEAN, 1),
    // right/right
    63 => array(BIOAUTH_FEATURE_T1, 'i', 'n', BIOAUTH_MEASURE_MEAN, 1),
    64 => array(BIOAUTH_FEATURE_T1, 'o', 'n', BIOAUTH_MEASURE_MEAN, 1),
    // left/right
    65 => array(BIOAUTH_FEATURE_T1, 't', 'h', BIOAUTH_MEASURE_MEAN, 1),
    66 => array(BIOAUTH_FEATURE_T1, 'e', 'n', BIOAUTH_MEASURE_MEAN, 1),
    67 => array(BIOAUTH_FEATURE_T1, 'a', 'n', BIOAUTH_MEASURE_MEAN, 1),
    68 => array(BIOAUTH_FEATURE_T1, 't', 'i', BIOAUTH_MEASURE_MEAN, 1),
    // right/left
    69 => array(BIOAUTH_FEATURE_T1, 'n', 'd', BIOAUTH_MEASURE_MEAN, 1),
    70 => array(BIOAUTH_FEATURE_T1, 'h', 'e', BIOAUTH_MEASURE_MEAN, 1),
    71 => array(BIOAUTH_FEATURE_T1, 'o', 'r', BIOAUTH_MEASURE_MEAN, 1),
    
    );


    $keystrokefeatureids = array();
    $keystrokefeaturefields = array('type', 'group1', 'group2', 'measure', 'distance');
    foreach ($keystrokefeatures as $featureid => $feature) {
        $keystrokefeatureids[$featureid] = $DB -> insert_record('bioauth_keystroke_features', array_combine($keystrokefeaturefields, $feature), true);
    }

    $keystrokefallback = array(2 => 1, 3 => 1, 5 => 4, );
    foreach ($keystrokefallback as $node => $parent) {
        $DB -> update_record('bioauth_keystroke_features', array('id' => $keystrokefeatureids[$node], 'fallback' => $keystrokefeatureids[$parent]));
    }

    $DB -> insert_record('bioauth_feature_sets', array('name' => 'Engish US Basic Keystroke', 'locale' => 'en_US', 'keystrokefeatures' => implode(',', array_keys($keystrokefeatureids)), 'stylometryfeatures' => ''));
    
}

/**
 * Post-install script
 */
function xmldb_local_bioauth_install() {
    
    load_demo_events();
    load_keystroke_features();
    
    return true;
}
