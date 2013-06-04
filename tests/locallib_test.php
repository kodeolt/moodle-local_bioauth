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
    
    public function test_dspace() {
        $n_users = 3;
        $n_user_samples = 5;
        $n_features = 2;
        $query_user = 0;
        
        $datagen = $this->getDataGenerator()->get_plugin_generator('local_bioauth');
        
        list($fspace, $user_means, $user_stds) = $datagen->create_fspace($n_users, $n_user_samples, $n_features);
        print_r($fspace);
        
        // (m-1)*m/2 for each user
        $w_dspace = create_user_dspace_within($fspace, $query_user);
        print_r($w_dspace);
        
        // (n-1)*m*m for each user
        $b_dspace = create_user_dspace_between($fspace, $query_user);
        print_r($b_dspace);
        
        $query_sample = $datagen->create_random_normal_sample($n_features, $user_means[$query_user], $user_stds[$query_user]);
        $q_dspace = create_dspace_query($fspace, 0, $query_sample);
        print_r($q_dspace);
    }
    
    public function test_binary_classification() {
        $n_users = 3;
        $n_user_samples = 3;
        $n_features = 2;
        $query_user = 0;
        
        $datagen = $this->getDataGenerator()->get_plugin_generator('local_bioauth');
        list($fspace, $user_means, $user_stds) = $datagen->create_fspace($n_users, $n_user_samples, $n_features);
        
        $query_sample = $datagen->create_random_normal_sample($n_features, $user_means[$query_user], $user_stds[$query_user]);
        
        list($dists, $labels) = sorted_distances($fspace, $query_sample, 0);
        // print_r($labels);
        // print_r($dists);
        print_r(array_combine($dists, $labels));
    }
    
    public function test_linear_weighted_decisions() {
        $neighbors = array('w', 'w', 'b', 'w', 'b', 'b', 'b', 'b', 'b', 'b');
        
        $decisions = linear_weighted_decisions($neighbors, 5);
        print_r($decisions);
    }
    
    public function test_random_normal() {
        mt_srand(1234);
        $size = 100000;
        $epsilon = 0.001;
        $expected_mean = 0.0;
        $expected_std = 1.0;
        
        $a = n_random_normal($size, $expected_mean, $expected_std);
        
        $actual_mean = average($a);
        $actual_std = sqrt(variance($a));
        
        $this->assertLessThan(abs($expected_mean - $actual_mean), $epsilon);
        $this->assertLessThan(abs($expected_std - $actual_std), $epsilon);
    }
}
