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
 * Unit tests for (some of) local/bioauth/locallib.php.
 *
 * @package    local_bioauth
 * @category   phpunit
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/bioauth/locallib.php');
require_once($CFG->dirroot . '/local/bioauth/tests/generator/lib.php');


class local_bioauth_locallib_testcase extends advanced_testcase {
    public function test_euclidean_distance() {
        $u = array(0, 0);
        $v = array(3, 4);
        $this->assertEquals(euclidean_distance($u, $v), 5);
    }
    
    public function test_combinations() {
        $c = new Combinations(array(1, 2, 3), 2);
        $this->assertContains(array(1,2), $c);
        $this->assertContains(array(1,3), $c);
        $this->assertContains(array(2,3), $c);
    }
    
    public function test_dspace_within() {
        $fspace = $this->getDataGenerator()->get_plugin_generator('local_bioauth')->create_fspace(3, 2, 4);
        
        print_r($fspace);
    }
}
