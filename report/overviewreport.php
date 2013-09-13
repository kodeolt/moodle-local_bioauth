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
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/tablelib.php');

require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

require_once($CFG->dirroot . '/local/bioauth/locallib.php');
require_once($CFG->dirroot . '/local/bioauth/report/reportlib.php');

/*
 * Overview report subclass for the bioauth overview report.
 */
class bioauth_report_overview extends bioauth_report {

    /**
     * The courses this user has access to.
     * @var array $courses
     */
    public $courses;

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     * @param int $sortitemid The id of the grade_item by which to sort the table
     */
    public function __construct($context, $page, $sortitemid) {
        global $CFG;
        parent::__construct($context, $page, $sortitemid);
        $this->baseurl = new moodle_url('index.php');
        $this->pbarurl = new moodle_url('/local/bioauth/report/index.php');
        $this->setup_sortitemid();
    }

    /**
     * Get information about which students to show in the report.
     * @return an array
     */
    public function load_course_validations() {

        $enrolcourses = enrol_get_my_courses();
        $viewgradecourses = array();
        $validations = array();
        foreach ($enrolcourses as $course) {
            $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
            if (has_capability('moodle/grade:viewall', $coursecontext)) {
                $viewgradecourses[$course->id] = $course;
                if ($validation = bioauth_get_quiz_validation($course)) {
                    $validations[$course->id] = $validation;
                };
            }
        }

        $this->courses = $viewgradecourses;
        $this->validations = $validations;
    }

    public function get_report_table() {
        global $CFG, $DB, $OUTPUT, $PAGE;

        if (!$this->courses) {
            echo $OUTPUT->notification(get_string('nocoursesyet'));
            return;
        }

        $html = '';

        $rows = $this->get_rows();

        $reporttable = new html_table();
        $reporttable->attributes['class'] = 'gradestable flexible boxaligncenter generaltable';
        $reporttable->id = 'bioauth-overview-report';
        $reporttable->data = $rows;
        $html .= html_writer::table($reporttable);

        return $html;
    }

    public function get_rows() {
        global $CFG, $USER, $OUTPUT;

        $rows = array();

        $arrows = $this->get_sort_arrows();

        $headerrow = new html_table_row();
        $headerrow->attributes['class'] = 'heading';

        $courseheader = new html_table_cell();
        $courseheader->attributes['class'] = 'header';
        $courseheader->scope = 'col';
        $courseheader->header = true;
        $courseheader->id = 'courseheader';
        $courseheader->text = $arrows['coursename'];
        $headerrow->cells[] = $courseheader;

        $statusheader = new html_table_cell();
        $statusheader->attributes['class'] = 'header';
        $statusheader->scope = 'col';
        $statusheader->header = true;
        $statusheader->id = 'statusheader';
        $statusheader->text = get_string('status', 'local_bioauth');
        $headerrow->cells[] = $statusheader;

        $datareadyheader = new html_table_cell();
        $datareadyheader->attributes['class'] = 'header';
        $datareadyheader->scope = 'col';
        $datareadyheader->header = true;
        $datareadyheader->id = 'datareadyheader';
        $datareadyheader->text = get_string('percentdataready', 'local_bioauth');
        $headerrow->cells[] = $datareadyheader;

        $performanceheader = new html_table_cell();
        $performanceheader->attributes['class'] = 'header';
        $performanceheader->scope = 'col';
        $performanceheader->header = true;
        $performanceheader->id = 'performanceheader';
        $performanceheader->text = get_string('performance', 'local_bioauth');
        $headerrow->cells[] = $performanceheader;

        $lastrunheader = new html_table_cell();
        $lastrunheader->attributes['class'] = 'header';
        $lastrunheader->scope = 'col';
        $lastrunheader->header = true;
        $lastrunheader->id = 'lastrunheader';
        $lastrunheader->text = get_string('lastrun', 'local_bioauth');
        $headerrow->cells[] = $lastrunheader;

        $actionheader = new html_table_cell();
        $actionheader->attributes['class'] = 'header';
        $actionheader->scope = 'col';
        $actionheader->header = true;
        $actionheader->id = 'actionheader';
        $actionheader->text = get_string('action', 'local_bioauth');
        $headerrow->cells[] = $actionheader;

        $rows[] = $headerrow;
        $rowclasses = array('even', 'odd');

        foreach ($this->courses as $courseid => $course) {
            $courserow = new html_table_row();
            $courserow->id = 'fixed_course_' . $courseid;
            $courserow->attributes['class'] = 'r' . $this->rowcount++ . ' ' . $rowclasses[$this->rowcount % 2];

            $bioauthenabled = array_key_exists($courseid, $this->validations);
            $state = $bioauthenabled ? $this->validations[$courseid]->state : BIOAUTH_JOB_DISABLED;

            if (BIOAUTH_JOB_WAITING == $state) {
                $statustext = get_string('jobstatewaiting', 'local_bioauth');
            } else if (BIOAUTH_JOB_MONITOR == $state) {
                $statustext = get_string('jobstatemonitor', 'local_bioauth');
            } else if (BIOAUTH_JOB_READY == $state) {
                $statustext = get_string('jobstateready', 'local_bioauth');
            } else if (BIOAUTH_JOB_RUNNING == $state) {
                $statustext = get_string('jobstaterunning', 'local_bioauth', sprintf('%d', $this->validations[$courseid]->percentjobcomplete));
            } else if (BIOAUTH_JOB_COMPLETE == $state) {
                $statustext = get_string('jobstatecomplete', 'local_bioauth');
            } else if (BIOAUTH_JOB_DISABLED == $state) {
                $statustext = get_string('disabled', 'local_bioauth');
            } else {
                $statustext = get_string('jobunknownstate', 'local_bioauth');
            }

            $alreadyran = $bioauthenabled && $this->validations[$courseid]->timefinish > 0;

            $coursecell = new html_table_cell();
            $coursecell->attributes['class'] = 'course';
            $coursecell->header = true;
            $coursecell->scope = 'row';
            $coursecell->text .= html_writer::link(new moodle_url('/local/bioauth/report/quiz.php', array('id' => $course->id)), $course->shortname);
            $courserow->cells[] = $coursecell;

            $statuscell = new html_table_cell();
            $statuscell->attributes['class'] = 'course';
            $statuscell->header = true;
            $statuscell->scope = 'row';
            $statuscell->text .= $statustext;
            $courserow->cells[] = $statuscell;

            $datareadycell = new html_table_cell();
            $datareadycell->attributes['class'] = 'complete';
            $datareadycell->header = true;
            $datareadycell->scope = 'row';
            $datareadycell->text .= $bioauthenabled ? sprintf('%d%%', $this->validations[$courseid]->percentdataready) : '-';
            $courserow->cells[] = $datareadycell;

            $performancecell = new html_table_cell();
            $performancecell->attributes['class'] = 'performance';
            $performancecell->header = true;
            $performancecell->scope = 'row';
            $performancecell->text .= $alreadyran ? sprintf('%.2f%%', 100 - $this->validations[$courseid]->eer) : '-';
            $courserow->cells[] = $performancecell;

            $lastruncell = new html_table_cell();
            $lastruncell->attributes['class'] = 'lastrun';
            $lastruncell->header = true;
            $lastruncell->scope = 'row';
            $lastruncell->text .= $alreadyran ? date('F j, Y, g:i a', $this->validations[$courseid]->timefinish) : '-';
            $courserow->cells[] = $lastruncell;

            $action = $bioauthenabled ? 'disable' : 'enable';
            $actionbutton = new single_button(new moodle_url($this->pbarurl, array('action' => $action, 'target' => $course->id,
                                                                                    'sesskey' => sesskey())), get_string($action, 'local_bioauth'), 'get');
            if ($bioauthenabled) {
                $actionbutton->add_confirm_action(get_string('confirmdisable', 'local_bioauth', $course->shortname));
            }

            $actioncell = new html_table_cell();
            $actioncell->attributes['class'] = 'course';
            $actioncell->header = true;
            $actioncell->scope = 'row';
            $actioncell->text .= $OUTPUT->render($actionbutton);
            $courserow->cells[] = $actioncell;

            $rows[] = $courserow;
        }

        return $rows;
    }

    /**
     * Refactored function for generating HTML of sorting links with matching arrows.
     * Returns an array with 'studentname' and 'idnumber' as keys, with HTML ready
     * to inject into a table header cell.
     * @param array $extrafields Array of extra fields being displayed, such as
     *   user idnumber
     * @return array An associative array of HTML sorting links+arrows
     */
    public function get_sort_arrows() {
        global $OUTPUT;
        $arrows = array();

        $strsortasc = $this->get_lang_string('sortasc', 'local_bioauth');
        $strsortdesc = $this->get_lang_string('sortdesc', 'local_bioauth');
        $strcoursename = $this->get_lang_string('course');
        $iconasc = $OUTPUT->pix_icon('t/sort_asc', $strsortasc, '', array('class' => 'iconsmall sorticon'));
        $icondesc = $OUTPUT->pix_icon('t/sort_desc', $strsortdesc, '', array('class' => 'iconsmall sorticon'));

        $coursenamelink = html_writer::link(new moodle_url($this->baseurl, array('sortitemid' => 'coursename')), $strcoursename);

        $arrows['coursename'] = $coursenamelink;

        if ($this->sortitemid === 'lastname') {
            if ($this->sortorder == 'ASC') {
                $arrows['coursename'] .= $iconasc;
            } else {
                $arrows['coursename'] .= $icondesc;
            }
        }

        return $arrows;
    }

    public function get_numrows() {
        return count($this->courses);
    }

    public function process_action($target, $action) {
        return self::do_process_action($target, $action);
    }

    /**
     * Processes a single action against a category, grade_item or grade.
     * @param string $target eid ({type}{id}, e.g. c4 for category4)
     * @param string $action Which action to take (edit, delete etc...)
     * @return
     */
    public static function do_process_action($target, $action) {

        switch ($action) {
            case 'enable' :
                bioauth_enable_course($target);
                break;

            case 'disable' :
                bioauth_disable_course($target);
                break;

            default :
                break;
        }

        return true;
    }

    /**
     * Processes the data sent by the form (grades and feedbacks).
     * Caller is responsible for all access control checks
     * @param array $data form submission (with magic quotes)
     * @return array empty array if success, array of warnings if something fails.
     */
    public function process_data($data) {
        global $DB;
        $warnings = array();

        return $warnings;
    }

    /**
     * Returns the maximum number of students to be displayed on each page
     *
     * Takes into account the 'studentsperpage' user preference and the 'max_input_vars'
     * PHP setting. Too many fields is only a problem when submitting grades but
     * we respect 'max_input_vars' even when viewing grades to prevent students disappearing
     * when toggling editing on and off.
     *
     * @return int The maximum number of students to display per page
     */
    public function get_rows_per_page() {
        global $USER;
        static $rowsperpage = null;

        if ($rowsperpage === null) {
            $originalstudentsperpage = $rowsperpage = $this->get_pref('rowsperpage');

            // Will this number of students result in more fields that we are allowed?
            $maxinputvars = ini_get('max_input_vars');
            if ($maxinputvars !== false) {

                if ($rowsperpage >= $maxinputvars) {
                    $rowsperpage = $maxinputvars - 1;
                    // Subtract one to be on the safe side
                    if ($rowsperpage < 1) {
                        // Make sure students per page doesn't fall below 1, though if your
                        // max_input_vars is only 1 you've got bigger problems!
                        $rowsperpage = 1;
                    }
                }
            }
        }

        return $rowsperpage;
    }

}
