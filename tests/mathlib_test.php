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
require_once($CFG->dirroot . '/local/bioauth/mathlib.php');
require_once($CFG->dirroot . '/local/bioauth/tests/generator/lib.php');

class local_bioauth_mathlib_testcase extends advanced_testcase {
    
    public function test_euclidean_distance() {
        $a = array(array(0, 0), array(3, 4));
        $this->assertEquals(euclidean_distance($a), 5);
    }
    
    public function test_combinations() {
        $a = array(1,2,3);
        $c = iterator_to_array(new Combinations($a, 2));
        
        $this->assertContains(array(1,2), $c);
        $this->assertContains(array(1,3), $c);
        $this->assertContains(array(2,3), $c);
    }
    
    public function test_product() {
        $a = array(array(1,2), array(3, 4, 5));
        $p = iterator_to_array(new Product($a));
        
        $this->assertContains(array(1,3), $p);
        $this->assertContains(array(2,3), $p);
        $this->assertContains(array(1,4), $p);
        $this->assertContains(array(2,4), $p);
        $this->assertContains(array(1,5), $p);
        $this->assertContains(array(2,5), $p);
    }
}
