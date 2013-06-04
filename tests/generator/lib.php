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

defined('MOODLE_INTERNAL') || die();

/**
 * BioAuth module test data generator class
 *
 * @package local_bioauth
 * @copyright Vinnie Monaco
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_bioauth_generator extends testing_module_generator {

    /**
     * Create new quiz module instance.
     * @param array|stdClass $record
     * @param array $options (mostly course_module properties)
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once("$CFG->dirroot/local/bioauth/locallib.php");
    }
    
    
    public function create_random_normal_sample($n_features, $means, $stds) {
        $sample = array();
        for ($feature_idx = 0; $feature_idx < $n_features; $feature_idx++) {
            list($x1, $x2) = random_normal($means[$feature_idx], $stds[$feature_idx]);
            $sample[$feature_idx] = $x1;
        }
        return $sample;
    }
    
    public function create_random_sample($n_features) {
        $sample = array();
        for ($feature_idx = 0; $feature_idx < $n_features; $feature_idx++) {
                $sample[$feature_idx] = mt_rand()/mt_getrandmax();
        }
        return $sample;
    }
    
    public function create_fspace($n_users, $n_user_samples, $n_features, $user_spread=0.1) {
        $fspace = array();
        $user_means = array();
        $user_stds = array();
        for ($user_idx = 0; $user_idx < $n_users; $user_idx++) {
            $means = n_random($n_features);
            $stds = array_fill(0, $n_features, $user_spread);
            $samples = array();
            for ($sample_idx = 0; $sample_idx < $n_user_samples; $sample_idx++) {
                $samples[] = $this->create_random_normal_sample($n_features, $means, $stds);
            }
            $fspace[$user_idx] = $samples;
            $user_means[$user_idx] = $means;
            $user_stds[$user_idx] = $stds;
        }
        
        return array($fspace, $user_means, $user_stds);
    }
}
