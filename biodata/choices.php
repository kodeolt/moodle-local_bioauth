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

$PAGE->set_url(new moodle_url('/local/bioauth/biodata/choices.php'));

require_login();

global $DB;

$choices = $DB->get_records_sql(
'SELECT u.id,u.email,u.username,c.name,o.text
FROM mdl_user u, mdl_choice_answers a, mdl_choice_options o, mdl_choice c
WHERE a.userid = u.id
AND a.choiceid = c.id
AND a.optionid = o.id');

if (count($choices) === 0) {
    echo 'No data to download';
    die();
}

$filename = 'choices';
$columns = 'userid,email,username,choice,answer';

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=$filename.csv");
header("Pragma: no-cache");
header("Expires: 0");
$output = fopen('php://output', 'w');

fputs($output, $columns . "\n");

foreach ($users as $idx => $choice) {
    $fields = array();
    $fields[] = $choice->userid;
    $fields[] = $choice->email;
    $fields[] = $choice->username;
    $fields[] = $choice->name;
    $fields[] = $choice->text;
    $fields_str = csv_str($fields);
    fputs($output, $fields_str . "," . "\n");
}
