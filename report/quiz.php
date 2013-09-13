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
require_once($CFG->dirroot . '/local/bioauth/report/quizreport.php');

$courseid      = required_param('id', PARAM_INT);
$page          = optional_param('page', 0, PARAM_INT);
$sortitemid    = optional_param('sortitemid', 0, PARAM_ALPHANUM);
$action        = optional_param('action', 0, PARAM_ALPHAEXT);
$target        = optional_param('target', 0, PARAM_ALPHANUM);

$PAGE->set_url(new moodle_url('/local/bioauth/report/quiz.php', array('id'=>$courseid)));

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($course->id);

require_capability('moodle/grade:viewall', $context);

// Perform actions.
if (!empty($target) && !empty($action) && confirm_sesskey()) {
    bioauth_report_quiz::do_process_action($target, $action);
}

$reportname = get_string('coursequizauths', 'local_bioauth', $course->shortname);

// Print header.
print_bioauth_page_head('report', $reportname);

// Initialize the bioauth report object that produces the table
$report = new bioauth_report_quiz($context, $page, $sortitemid);

$report->load_validation($context, $course);
$numcourses = $report->get_numrows();

$coursesperpage = $report->get_rows_per_page();
// Don't use paging if rows per page is empty or 0
if (!empty($coursessperpage)) {
    echo $OUTPUT->paging_bar($numcourses, $report->page, $coursesperpage, $report->pbarurl);
}

$graphhtml = $report->get_report_graph();
echo $graphhtml;

$reporthtml = $report->get_report_table();
echo $reporthtml;

// Print paging bar at bottom for large pages.
if (!empty($coursesperpage) && $coursesperpage >= 20) {
    echo $OUTPUT->paging_bar($numcourses, $report->page, $coursesperpage, $report->pbarurl);
}
echo $OUTPUT->footer();
