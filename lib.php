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
 * Library of functions for the bioauth module.
 *
 * This contains functions that are called also from outside the biaouth module
 * Functions that are only called by the biaouth module itself are in {@link locallib.php}
 *
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once ($CFG -> dirroot . '/local/bioauth/locallib.php');


function local_bioauth_cron() {
    //ini_set('memory_limit', '-1');
    global $DB;
    
    // $DB -> insert_record('bioauth_quiz_validations', array('userid' => 100, 'locale' => 'en_US', 'keystrokes' => 'jsonstring', 'stylometry' => ''));
    
    echo 'Creating keystroke features ', time();
    $keystrokefeatures = create_keystroke_features(1);
    
    echo '. Fetching demo keystrokes ', time();
    
    $userdata = fetch_demo_keystrokes();
    //print_r($userkeystrokes[1]);
    echo '. Extracting features ', time();
    $fspace = create_keystroke_fspace($userdata, $keystrokefeatures, 2);
    file_put_contents('fspace.txt', print_r($fspace, TRUE));
    //print_r($fspace);
    
    echo '. Done ', time();
}

