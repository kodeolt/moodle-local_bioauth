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
 * Unit tests for local/bioauth/keystrokelib.php.
 *
 * @package    local_bioauth
 * @category   phpunit
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/bioauth/locallib.php');
require_once($CFG->dirroot . '/local/bioauth/keystrokelib.php');
require_once($CFG->dirroot . '/local/bioauth/tests/generator/lib.php');

class local_bioauth_keystrokelib_testcase extends advanced_testcase {
    
    function test_keystroke_features() {
        global $DB;
        
        $keystrokefeatures = create_keystroke_features(1);
        // var_dump($keystrokefeatures); 
        
        $userkeystrokes = fetch_demo_keystrokes();
        // var_dump($userkeystrokes);
        $fspace = create_keystroke_fspace($userkeystrokes, $keystrokefeatures, 2);
        var_dump($fspace);
    }
}
