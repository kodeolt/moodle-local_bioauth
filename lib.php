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
require_once($CFG->dirroot . '/local/bioauth/constants.php');

/**
 * The cron function modifies the state of validation jobs, depending on whether
 * enough data was collected to run the job, new data is available, or a job is no
 * longer active.
 * 
 * When validation jobs are ready to be run, they are run asynchronously so that
 * the work is not done as part of the cron process.
 * 
 */
function local_bioauth_cron() {
    global $DB;

    // Calculate the amount of data ready for jobs waiting or monitoring
    $jobs = $DB->get_records_list('bioauth_quiz_validations', 'state', array(BIOAUTH_JOB_WAITING, BIOAUTH_JOB_MONITOR));
    foreach ($jobs as $idx => $job) {
        $ready = get_percent_data_ready($job);
        $DB->set_field('bioauth_quiz_validations', 'percentdataready', $ready, array('id' => $job->id));
    }

    // Place complete jobs which are still active into the monitor state.
    $jobs = $DB->get_records('bioauth_quiz_validations', array('state' => BIOAUTH_JOB_COMPLETE));
    foreach ($jobs as $idx => $job) {
        if (time() < $job->activeuntil) {
            mtrace('Keeping job active: ' . $job->id);
            $DB->set_field('bioauth_quiz_validations', 'state', BIOAUTH_JOB_MONITOR, array('id' => $job->id));
        }
    }

    // If a job has enough data, mark it as ready.
    $jobs = $DB->get_records('bioauth_quiz_validations', array('state' => BIOAUTH_JOB_WAITING));
    foreach ($jobs as $idx => $job) {
        if ($job->percentdataready >= $job->percentdataneeded) {
            mtrace('Enough data collected for job ' . $job->id);
            $DB->set_field('bioauth_quiz_validations', 'state', BIOAUTH_JOB_READY, array('id' => $job->id));
        }
    }

    // If a job has enough NEW data, mark it as ready.
    $jobs = $DB->get_records('bioauth_quiz_validations', array('state' => BIOAUTH_JOB_MONITOR));
    foreach ($jobs as $idx => $job) {
        if ($job->percentdataready > $job->percentdataused) {
            mtrace('Enough new data collected for job ' . $job->id);
            $DB->set_field('bioauth_quiz_validations', 'state', BIOAUTH_JOB_READY, array('id' => $job->id));
        }
    }

    // Start ready jobs without exceeding the allowable limit.
    $maxconcurrentjobs = get_config('local_bioauth', 'maxconcurrentjobs');
    $numjobsrunning = $DB->count_records('bioauth_quiz_validations', array('state' => BIOAUTH_JOB_RUNNING));

    $jobs = $DB->get_records('bioauth_quiz_validations', array('state' => BIOAUTH_JOB_READY));
    shuffle($jobs);
    // Ensure all queued jobs have a change of running.
    foreach ($jobs as $idx => $job) {
        if ($numjobsrunning < $maxconcurrentjobs) {
            mtrace('Running quiz validation job ' . $job->id);
            $percentdataused = get_percent_data_ready($job);
            $DB->set_field('bioauth_quiz_validations', 'percentdataused', $percentdataused, array('id' => $job->id));
            $DB->set_field('bioauth_quiz_validations', 'state', BIOAUTH_JOB_RUNNING, array('id' => $job->id));
            run_quiz_validation($job);
            $numjobsrunning += 1;
        }
    }
}

/**
 * Create navigation links in the left sidebar for easy access to settings and
 * course reports. 
 *
 * @param object $navigation the navigation object
 */
function local_bioauth_extends_navigation(global_navigation $navigation) {

    if (!isloggedin()) {
        return;
    }

    global $USER;
    $context = context_user::instance($USER->id);
    $bioauthnode = $navigation->add(get_string('pluginname', 'local_bioauth'));
    $reportnode = $bioauthnode->add(get_string('report', 'local_bioauth'), new moodle_url('/local/bioauth/report/index.php'));
    // TODO: create settings for each individual course, accessable from the report overview page.
    // $settingsnode = $bioauthnode->add(get_string('settings', 'local_bioauth'), new moodle_url('/admin/settings.php', array('section' => 'local_bioauth')));
}

/**
 *  Look for a quiz validation job for a particular course.
 *
 * @param object $course the course to find a quiz validation for.
 * @return object the quiz validation object 
 */
function bioauth_get_quiz_validation($course) {
    global $DB;

    return $DB->get_record('bioauth_quiz_validations', array('courseid' => $course->id));
}

/**
 * Enable biometric authentication for a course.
 *
 * @param int $courseid the id of the course
 */
function bioauth_enable_course($courseid) {
    create_quiz_validation_job($courseid);
}

/**
 * Disable biometric authentication for a course
 *
 * @param int $courseid the id of the course
 */
function bioauth_disable_course($courseid) {
    remove_quiz_validation_job($courseid);
}

/**
 * Return a textual summary of the number of attempts that have been made at a particular quiz,
 * returns '' if no attempts have been made yet, unless $returnzero is passed as true.
 *
 * @param object $quiz the quiz object. Only $quiz->id is used at the moment.
 * @param object $cm the cm object. Only $cm->course, $cm->groupmode and
 *      $cm->groupingid fields are used at the moment.
 * @param bool $returnzero if false (default), when no attempts have been
 *      made '' is returned instead of 'Attempts: 0'.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *         (e.g. a report) pass it in here. Default 0 which means no current group.
 * @return string a string like "Attempts: 123"
 */
function bioauth_performance_summary($validation, $course) {
    global $DB, $USER;

    $a = new stdClass();
    $a->performance = number_format(100 - $validation->eer, 2);
    $a->numauths = $DB->count_records('bioauth_quiz_neighbors', array('courseid' => $course->id));
    return get_string('performancesummary', 'local_bioauth', $a);
}

/**
 * Prints the page headers, breadcrumb trail, page heading, (optional) dropdown navigation menu and
 * (optional) navigation tabs for any gradebook page. All gradebook pages MUST use these functions
 * in favour of the usual print_header(), print_header_simple(), print_heading() etc.
 * !IMPORTANT! Use of tabs.php file in gradebook pages is forbidden unless tabs are switched off at
 * the site level for the gradebook ($CFG->grade_navmethod = GRADE_NAVMETHOD_DROPDOWN).
 *
 * @param int     $courseid Course id
 * @param string  $active_type The type of the current page (report, settings,
 *                             import, export, scales, outcomes, letters)
 * @param string  $active_plugin The plugin of the current page (grader, fullview etc...)
 * @param string  $heading The heading of the page. Tries to guess if none is given
 * @param boolean $return Whether to return (true) or echo (false) the HTML generated by this function
 * @param string  $bodytags Additional attributes that will be added to the <body> tag
 * @param string  $buttons Additional buttons to display on the page
 * @param boolean $shownavigation should the gradebook navigation drop down (or tabs) be shown?
 *
 * @return string HTML code or nothing if $return == false
 */
function print_bioauth_page_head($active_type, $heading = false, $return = false, $buttons = false, $shownavigation = true) {
    global $CFG, $OUTPUT, $PAGE;

    $title = get_string($active_type, 'local_bioauth');

    if ($active_type == 'report') {
        $PAGE->set_pagelayout('report');
    } else {
        $PAGE->set_pagelayout('admin');
    }
    $PAGE->set_title(get_string('pluginname', 'local_bioauth') . ' : ' . $title);
    $PAGE->set_heading($heading);
    if ($buttons instanceof single_button) {
        $buttons = $OUTPUT->render($buttons);
    }
    $PAGE->set_button($buttons);

    $returnval = $OUTPUT->header();
    if (!$return) {
        echo $returnval;
    }

    // Guess heading if not given explicitly.
    if (!$heading) {
        $heading = $stractive_plugin;
    }

    if ($shownavigation) {

        if ($return) {
            $returnval .= $OUTPUT->heading($heading);
        } else {
            echo $OUTPUT->heading($heading);
        }
    }

    if ($return) {
        return $returnval;
    }
}
