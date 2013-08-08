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

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/local/bioauth/lib.php');
require_once($CFG->dirroot . '/local/bioauth/report/reportlib.php');


$page          = optional_param('page', 0, PARAM_INT);   // active page
$sortitemid    = optional_param('sortitemid', 0, PARAM_ALPHANUM); // sort by which grade item
$action        = optional_param('action', 0, PARAM_ALPHAEXT);
$target        = optional_param('target', 0, PARAM_ALPHANUM);

$PAGE->set_url(new moodle_url('/local/bioauth/report/overview.php'));

require_login();
$context = context_user::instance($USER->id);

//require_capability('bioauthreport/report:view', $context);
require_capability('moodle/grade:viewall', $context);


// Perform actions
if (!empty($target) && !empty($action) && confirm_sesskey()) {
    bioauth_report_overview::do_process_action($target, $action);
}

$reportname = get_string('pluginname', 'local_bioauth');

/// Print header
print_bioauth_page_head('report', $reportname);

//Initialise the grader report object that produces the table
$report = new bioauth_report_overview($context, $page, $sortitemid);

/// processing posted grades & feedback here
if ($data = data_submitted() and confirm_sesskey() and has_capability('moodle/grade:edit', $context)) {
    $warnings = $report->process_data($data);
} else {
    $warnings = array();
}

$report->load_course_validations();
$numcourses = $report->get_numrows();

$coursesperpage = $report->get_rows_per_page();
// Don't use paging if studentsperpage is empty or 0 at course AND site levels
if (!empty($coursessperpage)) {
    echo $OUTPUT->paging_bar($numcourses, $report->page, $coursesperpage, $report->pbarurl);
}

$reporthtml = $report->get_report_table();
echo $reporthtml;

// prints paging bar at bottom for large pages
if (!empty($coursesperpage) && $coursesperpage >= 20) {
    echo $OUTPUT->paging_bar($numcourses, $report->page, $coursesperpage, $report->pbarurl);
}
echo $OUTPUT->footer();
