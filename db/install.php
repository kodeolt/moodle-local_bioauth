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
function load_keystroke_features() {
    global $CFG;
    global $DB;
    
    $featureit = new DirectoryIterator($CFG -> dirroot . '/local/bioauth/features');
    foreach ($featureit as $features) {
        if ($features->isDot()) continue;
        
        unset($KEYSTROKE_FEATURES);
        unset($FEATURES_NAME);
        include($features->getPathname());
    
        $keystrokefeatureids = array();
        $keystrokefallback = array();
        $keystrokefeaturefields = array('fallback', 'type', 'group1', 'group2', 'measure', 'distance');
        foreach ($KEYSTROKE_FEATURES as $featureid => $feature) {
            $row = array_combine($keystrokefeaturefields, $feature);
            $keystrokefeatureids[$featureid] = $DB -> insert_record('bioauth_keystroke_features', $row, true);
            $keystrokefallback[$featureid] = $row['fallback'];
        }
    
        foreach ($keystrokefallback as $node => $parent) {
            if (NULL !== $parent) {
                $DB -> update_record('bioauth_keystroke_features', array('id' => $keystrokefeatureids[$node], 'fallback' => $keystrokefeatureids[$parent]));
            }
        }
    
        $DB -> insert_record('bioauth_feature_sets', array('name' => $FEATURES_NAME, 'locale' => 'en', 'keystrokefeatures' => implode(',', array_keys($keystrokefeatureids)), 'stylometryfeatures' => ''));
    }
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
                $DB -> insert_record_raw('bioauth_demo_biodata', array('userid' => $user->getBasename(), 'quizid' => $quiz->getBasename(), 'locale' => 'en_US', 'data' => $jsonstring), false, true);
                $done++;
                $progressbar->update($done, 1187, get_string('install_bootstrap', 'local_bioauth'));
            }
        }
    }
}

/**
 * Post-install script
 */
function xmldb_local_bioauth_install() {
    
    load_keystroke_features();
    load_demo_events();
    
    return true;
}
