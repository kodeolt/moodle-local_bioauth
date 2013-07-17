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
require_once($CFG->dirroot . '/local/bioauth/locallib.php');

function local_bioauth_cron() {
    global $CFG;
    $jsondata = json_encode(fetch_demo_keystrokes());
    // $temp = tmpfile();
    // fwrite($temp, $jsondata);
    file_put_contents('/Users/vinnie/data.json', $jsondata);
    
    $jsondata = json_encode(create_keystroke_features(1));
    // $temp = tmpfile();
    // fwrite($temp, $jsondata);
    file_put_contents('/Users/vinnie/features.json', $jsondata);
    
    // $meta_data = stream_get_meta_data($temp);
    // $filename = $meta_data["uri"];
    // print_r($filename);
    // print_r($data);
    // $cmd = '/usr/bin/env java -jar '.$CFG->dirroot.'/local/bioauth/bin/ssi.jar '.escapeshellarg($filename);
    // $output = shell_exec($cmd);
    // print_r($output);

    // fseek($temp, 0);
    // echo fread($temp, 1024);
    
    // fclose($temp);
}