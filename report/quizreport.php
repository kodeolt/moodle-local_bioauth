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
require_once($CFG->dirroot . '/local/bioauth/highroller/HighRoller.php');
require_once($CFG->dirroot . '/local/bioauth/highroller/HighRollerSeriesData.php');
require_once($CFG->dirroot . '/local/bioauth/highroller/HighRollerLineChart.php');

/*
 * Quiz report subclass for the bioauth quiz report.
 *
 */
class bioauth_report_quiz extends bioauth_report {

    /**
     * The course this report and quiz belong to
     * @var object $course
     */
    private $course;
    /**
     * Array of students who have access to the course
     * @var array $students
     */
    private $students;
    /**
     * The validation object for this course
     * @var object $validation
     */
    private $validation;
    /**
     * The quizzes contained in this course
     * @var array $quizzes
     */
    private $quizzes;
    /**
     * The quiz authentication decisions
     * @var array $quizauths
     */
    private $quizauths;
    /**
     * A count of the rows, used for css classes.
     * @var int $rowcount
     */
    public $rowcount = 0;

    /**
     * Constructor. Sets local copies of user preferences and initialises the report.
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     * @param int $sortitemid The id of the grade_item by which to sort the table
     */
    public function __construct($context, $page, $sortitemid) {
        global $CFG;
        parent::__construct($context, $page, $sortitemid);
        $this->baseurl = new moodle_url('quiz.php');
        $this->pbarurl = new moodle_url('/local/bioauth/report/quiz.php');
        $this->setup_sortitemid();
    }

    /**
     * Get information about which students to show in the report.
     * @return an array
     */
    public function load_validation($context, $course) {
        global $CFG, $DB;

        $this->course = $course;

        $this->validation = $DB->get_record('bioauth_quiz_validations', array('courseid' => $course->id));
        $this->quizzes = $DB->get_records('quiz', array('course' => $course->id));

        $records = $DB->get_records('bioauth_quiz_neighbors', array('courseid' => $course->id));
        $quizauths = array();
        foreach ($records as $record) {
            $quizauths[$record->userid][$record->quizid] = explode(',', $record->neighbors);
        }

        $this->quizauths = $quizauths;

        if (!empty($this->users)) {
            return;
        }

        // Limit to users with a gradeable role.
        list($gradebookrolessql, $gradebookrolesparams) = $DB->get_in_or_equal(explode(',', $this->gradebookroles), SQL_PARAMS_NAMED, 'grbr0');

        // Limit to users with an active enrollment.
        list($enrolledsql, $enrolledparams) = get_enrolled_sql($this->context);

        // Fields we need from the user table.
        $userfields = user_picture::fields('u', get_extra_user_fields($this->context));

        $sortjoin = $sort = $params = null;

        // If the user has clicked one of the sort asc/desc arrows.
        $sortjoin = '';
        switch($this->sortitemid) {
            case 'lastname' :
                $sort = "u.lastname $this->sortorder, u.firstname $this->sortorder";
                break;
            case 'firstname' :
                $sort = "u.firstname $this->sortorder, u.lastname $this->sortorder";
                break;
            case 'email' :
                $sort = "u.email $this->sortorder";
                break;
            case 'idnumber' :
            default :
                $sort = "u.idnumber $this->sortorder";
                break;
        }

        $params = array_merge($gradebookrolesparams, $enrolledparams);

        $sql = "SELECT $userfields
                  FROM {user} u
                  JOIN ($enrolledsql) je ON je.id = u.id
                       $sortjoin
                  JOIN (
                           SELECT DISTINCT ra.userid
                             FROM {role_assignments} ra
                            WHERE ra.roleid IN ($this->gradebookroles)
                              AND ra.contextid " . get_related_contexts_string($this->context) . "
                       ) rainner ON rainner.userid = u.id
                   AND u.deleted = 0
              ORDER BY $sort";

        $studentsperpage = $this->get_rows_per_page();
        $this->users = $DB->get_records_sql($sql, $params, $studentsperpage * $this->page, $studentsperpage);

        if (empty($this->users)) {
            $this->userselect = '';
            $this->users = array();
            $this->userselect_params = array();
        } else {
            list($usql, $uparams) = $DB->get_in_or_equal(array_keys($this->users), SQL_PARAMS_NAMED, 'usid0');
            $this->userselect = "AND g.userid $usql";
            $this->userselect_params = $uparams;

            // Add a flag to each user indicating whether their enrolment is active.
            $sql = "SELECT ue.userid
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON e.id = ue.enrolid
                     WHERE ue.userid $usql
                           AND ue.status = :uestatus
                           AND e.status = :estatus
                           AND e.courseid = :courseid
                  GROUP BY ue.userid";
            $coursecontext = get_course_context($this->context);
            $params = array_merge($uparams, array('estatus' => ENROL_INSTANCE_ENABLED, 'uestatus' => ENROL_USER_ACTIVE, 'courseid' => $coursecontext->instanceid));
            $useractiveenrolments = $DB->get_records_sql($sql, $params);

            $defaultgradeshowactiveenrol = !empty($CFG->grade_report_showonlyactiveenrol);
            $showonlyactiveenrol = get_user_preferences('grade_report_showonlyactiveenrol', $defaultgradeshowactiveenrol);
            $showonlyactiveenrol = $showonlyactiveenrol || !has_capability('moodle/course:viewsuspendedusers', $coursecontext);
            foreach ($this->users as $user) {
                // If we are showing only active enrolments, then remove suspended users from list.
                if ($showonlyactiveenrol && !array_key_exists($user->id, $useractiveenrolments)) {
                    unset($this->users[$user->id]);
                } else {
                    $this->users[$user->id]->suspendedenrolment = !array_key_exists($user->id, $useractiveenrolments);
                }
            }
        }

        return $this->users;
    }

    public function get_report_graph() {
        $html = '';

        $frrstring = explode(',', $this->validation->frr);
        $farstring = explode(',', $this->validation->far);

        $frr = array();
        $far = array();
        foreach (array_keys($frrstring) as $m) {
            $frr[] = (float)$frrstring[$m];
            $far[] = (float)$farstring[$m];
        }

        $linechart = new HighRollerLineChart();
        $linechart->chart->renderTo = 'linechart';
        $linechart->title->text = 'FRR and FAR vs M';
        $linechart->xAxis->title->text = 'M';
        $linechart->yAxis->min = 0;
        $linechart->yAxis->max = 100;
        $linechart->xAxis->min = 0;
        $linechart->xAxis->max = count($frr);

        $linechart->yAxis->title->text = 'Error (%)';

        $linechart->chart->width = 600;
        $linechart->chart->height = 300;

        $frrseries = new HighRollerSeriesData();
        $frrseries->addName('FRR')->addData($frr);
        $frrseries->marker->enabled = false;

        $farseries = new HighRollerSeriesData();
        $farseries->addName('FAR')->addData($far);
        $farseries->marker->enabled = false;

        $linechart->addSeries($frrseries);
        $linechart->addSeries($farseries);

        // $html .= html_writer::script(null, 'https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js');
        // $html .= html_writer::script(null, '../highcharts/highcharts.js');
        // $html .= html_writer::div(html_writer::script($linechart->renderChart()), 'linechart');
        $html .= '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>';
        $html .= "<script type='text/javascript' src='../highcharts/highcharts.js'></script>";
        $html .= '<div id="linechart"></div><script type="text/javascript">' . $linechart->renderChart() . '</script>';

        return $html;
    }

    public function get_report_table() {
        global $CFG, $DB, $OUTPUT, $PAGE;

        if (!$this->quizzes) {
            echo $OUTPUT->notification(get_string('noquizzesyet'));
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

        $showuserimage = $this->get_pref('showuserimage');

        $extrafields = get_extra_user_fields($this->context);

        $arrows = $this->get_sort_arrows($extrafields);

        $headerrow = new html_table_row();
        $headerrow->attributes['class'] = 'heading';

        $studentheader = new html_table_cell();
        $studentheader->attributes['class'] = 'header';
        $studentheader->scope = 'col';
        $studentheader->header = true;
        $studentheader->id = 'studentheader';
        $studentheader->text = $arrows['studentname'];

        $headerrow->cells[] = $studentheader;

        foreach ($this->quizzes as $quizid => $quiz) {
            $quizheader = new html_table_cell();
            $quizheader->attributes['class'] = 'header';
            $quizheader->scope = 'col';
            $quizheader->header = true;
            $quizheader->id = 'quizheader';
            $quizheader->text = $quiz->name;

            $headerrow->cells[] = $quizheader;
        }

        $rows[] = $headerrow;

        $rowclasses = array('even', 'odd');

        foreach ($this->users as $userid => $user) {
            $userrow = new html_table_row();
            $userrow->id = 'fixed_user_' . $userid;
            $userrow->attributes['class'] = 'r' . $this->rowcount++ . ' ' . $rowclasses[$this->rowcount % 2];

            $usercell = new html_table_cell();
            $usercell->attributes['class'] = 'user';

            $usercell->header = true;
            $usercell->scope = 'row';

            if ($showuserimage) {
                $usercell->text = $OUTPUT->user_picture($user);
            }

            $usercell->text .= html_writer::link(new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $this->course->id)), fullname($user));

            if (!empty($user->suspendedenrolment)) {
                $usercell->attributes['class'] .= ' usersuspended';

                // May be lots of suspended users so only get the string once
                if (empty($suspendedstring)) {
                    $suspendedstring = get_string('userenrolmentsuspended', 'grades');
                }
                $usercell->text .= html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/enrolmentsuspended'),
                                                            'title' => $suspendedstring, 'alt' => $suspendedstring, 'class' => 'usersuspendedicon'));
            }

            $userrow->cells[] = $usercell;

            foreach ($this->quizzes as $quizid => $quiz) {
                $quizcell = new html_table_cell();
                $quizcell->attributes['class'] = 'quiz';

                $quizcell->header = true;
                $quizcell->scope = 'row';

                if (array_key_exists($userid, $this->quizauths) && array_key_exists($quizid, $this->quizauths[$userid])) {
                    $quizcell->text .= $this->make_decision_output($this->quizauths[$userid][$quizid][$this->validation->m]);
                } else {
                    $quizcell->text .= '-';
                }

                $userrow->cells[] = $quizcell;
            }

            $rows[] = $userrow;
        }

        return $rows;
    }

    /**
     * Make a link to review an individual question in a popup window.
     *
     * @param string $data HTML fragment. The text to make into the link.
     */
    public function make_decision_output($decision) {
        global $OUTPUT;

        $decisionclass = 'w' === $decision ? 'correct' : 'incorrect';
        $img = $OUTPUT->pix_icon('i/grade_' . $decisionclass, get_string($decisionclass, 'question'), 'moodle', array('class' => 'icon'));

        $output = html_writer::tag('span', $img);

        return $output;
    }

    /**
     * Refactored function for generating HTML of sorting links with matching arrows.
     * Returns an array with 'studentname' and 'idnumber' as keys, with HTML ready
     * to inject into a table header cell.
     * @param array $extrafields Array of extra fields being displayed, such as
     *   user idnumber
     * @return array An associative array of HTML sorting links+arrows
     */
    public function get_sort_arrows(array $extrafields = array()) {
        global $OUTPUT;
        $arrows = array();

        $strsortasc = $this->get_lang_string('sortasc', 'grades');
        $strsortdesc = $this->get_lang_string('sortdesc', 'grades');
        $strfirstname = $this->get_lang_string('firstname');
        $strlastname = $this->get_lang_string('lastname');
        $iconasc = $OUTPUT->pix_icon('t/sort_asc', $strsortasc, '', array('class' => 'iconsmall sorticon'));
        $icondesc = $OUTPUT->pix_icon('t/sort_desc', $strsortdesc, '', array('class' => 'iconsmall sorticon'));

        $firstlink = html_writer::link(new moodle_url($this->baseurl, array('sortitemid' => 'firstname')), $strfirstname);
        $lastlink = html_writer::link(new moodle_url($this->baseurl, array('sortitemid' => 'lastname')), $strlastname);

        $arrows['studentname'] = $lastlink;

        if ($this->sortitemid === 'lastname') {
            if ($this->sortorder == 'ASC') {
                $arrows['studentname'] .= $iconasc;
            } else {
                $arrows['studentname'] .= $icondesc;
            }
        }

        $arrows['studentname'] .= ' ' . $firstlink;

        if ($this->sortitemid === 'firstname') {
            if ($this->sortorder == 'ASC') {
                $arrows['studentname'] .= $iconasc;
            } else {
                $arrows['studentname'] .= $icondesc;
            }
        }

        foreach ($extrafields as $field) {
            $fieldlink = html_writer::link(new moodle_url($this->baseurl, array('sortitemid' => $field)), get_user_field_name($field));
            $arrows[$field] = $fieldlink;

            if ($field == $this->sortitemid) {
                if ($this->sortorder == 'ASC') {
                    $arrows[$field] .= $iconasc;
                } else {
                    $arrows[$field] .= $icondesc;
                }
            }
        }

        return $arrows;
    }

    public function get_numrows() {
        return count($this->students);
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
            
            default :
                break;
        }

        return true;
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
