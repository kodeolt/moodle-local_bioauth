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

$PAGE->set_url(new moodle_url('/local/bioauth/biodata/users.php'));

require_login();

global $DB;

$users = $DB->get_records_sql('SELECT u.id,u.email,u.username FROM mdl_user u');

$filename = 'users';
$columns = 'userid,email,username';

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=$filename.csv");
header("Pragma: no-cache");
header("Expires: 0");
$output = fopen('php://output', 'w');

fputs($output, $columns . "\n");

foreach ($users as $idx => $user) {
    $fields = array();
    $fields[] = $user->id;
    $fields[] = $user->email;
    $fields[] = $user->username;
    $fields_str = csv_str($fields);
    fputs($output, $fields_str . "," . "\n");
}
