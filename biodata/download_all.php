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

$biometric = required_param('biometric', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/local/bioauth/biodata/download_all.php'));

require_login();

global $DB;

$biodata = $DB->get_records_sql('SELECT mdl_bioauth_biodata.id,userid,email,username,session,ipaddress,useragent,appversion,task,tags,csvdata FROM mdl_bioauth_biodata,mdl_user WHERE biometric=? AND mdl_bioauth_biodata.userid=mdl_user.id', array($biometric));

if (count($biodata) === 0) {
    echo 'No data to download';
    die();
}

$filename = $biometric;

$biofields = array();
$columns = array();

$fixed_columns = 'userid,email,username,session,ipaddress,useragent,appversion,task,tags';
$biodata_idx = array_keys($biodata);
$data_columns = strtok($biodata[$biodata_idx[0]]->csvdata, "\n");

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=$filename.csv");
header("Pragma: no-cache");
header("Expires: 0");
$output = fopen('php://output', 'w');

fputs($output, $fixed_columns . "," . $data_columns . "\n");

foreach ($biodata as $idx => $session) {
    $data = $session->csvdata;
    
    $fixed = array();
    $fixed[] = $session->userid;
    $fixed[] = $session->email;
    $fixed[] = $session->username;
    $fixed[] = $session->session;
    $fixed[] = $session->ipaddress;
    $fixed[] = $session->useragent;
    $fixed[] = $session->appversion;
    $fixed[] = $session->task;
    $fixed[] = $session->tags;
    $fixed_str = csv_str($fixed);
    
    foreach (explode("\n", $data) as $row_idx => $row) {
        if ($row_idx === 0) {
            continue;
        }
        $values = array();
       fputs($output, $fixed_str . "," . $row . "\n");
    }
}
