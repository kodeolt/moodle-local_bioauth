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
require_once($CFG->dirroot . '/local/bioauth/lib.php');
require_once($CFG->dirroot . '/local/bioauth/biodata/biodatalib.php');

$page          = optional_param('page', 0, PARAM_INT);
$sortitemid    = optional_param('sortitemid', 0, PARAM_ALPHANUM);

$PAGE->set_url(new moodle_url('/local/bioauth/biodata/index.php'));

require_login();
$context = context_user::instance($USER->id);
$PAGE->set_context($context);

// Print header.
print_bioauth_page_head('biodata', get_string('downloadyourdata', 'local_bioauth'));

// Initialize the bioauth report object that produces the table.
$biodata = new bioauth_biodata_overview($context, $page, $sortitemid);
$biodata->load_biodata();
$numdata = $biodata->get_numrows();
$dataperpage = $biodata->get_rows_per_page();

// Don't use paging if rows per page is empty or 0.
if (!empty($dataperpage)) {
    echo $OUTPUT->paging_bar($numdata, $page, $dataperpage, $biodata->pbarurl);
}

$biodatahtml = $biodata->get_report_table();
echo $biodatahtml;

// Print paging bar at bottom for large pages.
if (!empty($dataperpage) && $dataperpage >= 20) {
    echo $OUTPUT->paging_bar($numdata, $biodata->page, $dataperpage, $biodata->pbarurl);
}

echo $OUTPUT->footer();