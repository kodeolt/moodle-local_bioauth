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
 * Main page for the BioAuth reporting capabilities.
 * This gives an overview of courses
 * 
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/local/bioauth/locallib.php');

$biodataid = required_param('id', PARAM_INT);

$PAGE->set_url(new moodle_url('/local/bioauth/biodata/download.php'));

require_login();

global $DB;

$biodata = $DB->get_record('bioauth_biodata', array('id' => $biodataid));
$user = $DB->get_record('user', array('id' => $biodata->userid));
$context = context_user::instance($user->id);
require_capability('moodle/grade:viewall', $context);

$filename = "$biodata->timemodified-$biodata->biometric";
header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=$filename.csv");
header("Pragma: no-cache");
header("Expires: 0");
$output = fopen('php://output', 'w');

$data = json_decode($biodata->jsondata);
$columns = array();

foreach ($data[0] as $colname => $value) {
    $columns[] = $colname;
}

fputcsv($output, $columns, ',', '"');
    
foreach ($data as $row) {
    $values = array();
    foreach ($columns as $c) {
        $values[] = $row->$c;
    }
    
    fputcsv($output, $values, ',', '"');
}