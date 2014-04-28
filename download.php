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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/bioauth/locallib.php');

$biometric = required_param('biometric', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/local/bioauth/biodata/download.php'));

global $DB;

$biodata = $DB->get_records('bioauth_biodata', array('biometric' => $biometric));

$filename = $biometric;

$biofields = array();
$columns = array();

$columns[] = 'user';
$columns[] = 'session';
$columns[] = 'ipaddress';
$columns[] = 'useragent';
$columns[] = 'appversion';
$columns[] = 'task';
$columns[] = 'tags';

foreach ($biodata as $idx => $session) {
    $data = json_decode($session->jsondata);
    foreach ($data[0] as $colname => $value) {
        if (!array_key_exists($colname, $biofields)) {
            $columns[] = $colname;
            $biofields[$colname] = $colname;
        }
    }
}

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=$filename.csv");
header("Pragma: no-cache");
header("Expires: 0");
$output = fopen('php://output', 'w');

fputcsv($output, $columns, ',', '"');

foreach ($biodata as $idx => $session) {
    $data = json_decode($session->jsondata);
    
    foreach ($data as $row) {
        $values = array();
        
        $values[] = $session->userid;
        $values[] = $session->session;
        $values[] = $session->ipaddress;
        $values[] = $session->useragent;
        $values[] = $session->appversion;
        $values[] = $session->task;
        $values[] = $session->tags;
        
        foreach ($biofields as $c) {
            $values[] = (isset($row->$c) ? $row->$c : '');
        }
        
        fputcsv($output, $values, ',', '"');
    }
}