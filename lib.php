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
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir.'/tablelib.php');

function local_bioauth_cron() {
    global $CFG;
    $jsondata = json_encode(fetch_demo_keystrokes());
    // $temp = tmpfile();
    // fwrite($temp, $jsondata);
    file_put_contents('/Users/vinnie/data.json', $jsondata);
    
    $jsondata = json_encode(create_keystroke_features(1));
    // $temp = tmpfile();
    // fwrite($temp, $jsondata);
    file_put_contents('/Users/vinnie/features.json', $jsondata);
    
    // $meta_data = stream_get_meta_data($temp);
    // $filename = $meta_data["uri"];
    // print_r($filename);
    // print_r($data);
    // $cmd = '/usr/bin/env java -jar '.$CFG->dirroot.'/local/bioauth/bin/ssi.jar '.escapeshellarg($filename);
    // $output = shell_exec($cmd);
    // print_r($output);

    // fseek($temp, 0);
    // echo fread($temp, 1024);
    
    // fclose($temp);
}

function print_bioauth_page_head($courseid, $return=false, $buttons=false) {
    global $CFG, $OUTPUT, $PAGE;

    $title = $PAGE->course->fullname.': ' . get_string('pluginname', 'local_bioauth');

    $PAGE->set_pagelayout('report');
    $PAGE->set_title(get_string('pluginname', 'local_bioauth'));
    $PAGE->set_heading($title);
    if ($buttons instanceof single_button) {
        $buttons = $OUTPUT->render($buttons);
    }
    $PAGE->set_button($buttons);

    $returnval = $OUTPUT->header();
    if (!$return) {
        echo $returnval;
    }

    if ($return) {
        return $returnval;
    }
}


/**
 * Base class for the table used by a {@link quiz_attempts_report}.
 *
 * @copyright 2010 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bioauth_report_table extends table_sql {
    public $useridfield = 'userid';

    /** @var moodle_url the URL of this report. */
    protected $reporturl;

    /** @var array the display options. */
    protected $displayoptions;

    /**
     * @var array information about the latest step of each question.
     * Loaded by {@link load_question_latest_steps()}, if applicable.
     */
    protected $lateststeps = null;

    /** @var object the quiz settings for the quiz we are reporting on. */
    protected $quiz;

    /** @var context the quiz context. */
    protected $context;

    /** @var object mod_quiz_attempts_report_options the options affecting this report. */
    protected $options;

    /** @var object the ids of the students in the currently selected group, if applicable. */
    protected $groupstudents;

    /** @var object the ids of the students in the course. */
    protected $students;

    /** @var object the questions that comprise this quiz.. */
    protected $questions;

    /** @var bool whether to include the column with checkboxes to select each attempt. */
    protected $includecheckboxes;

    protected $regradedqs = array();

    /**
     * Constructor
     * @param string $uniqueid
     * @param object $quiz
     * @param context $context
     * @param string $qmsubselect
     * @param mod_quiz_attempts_report_options $options
     * @param array $groupstudents
     * @param array $students
     * @param array $questions
     * @param moodle_url $reporturl
     */
    public function __construct($uniqueid, $quiz, $context, $groupstudents, $students, $questions, $reporturl) {
        parent::__construct($uniqueid);
        $this->quiz = $quiz;
        $this->context = $context;
        $this->groupstudents = $groupstudents;
        $this->students = $students;
        $this->questions = $questions;
        $this->reporturl = $reporturl;
    }

    public function build_table() {
        global $DB;

        if (!$this->rawdata) {
            return;
        }

        $this->strtimeformat = str_replace(',', ' ', get_string('strftimedatetime'));
        parent::build_table();

        // End of adding the data from attempts. Now add averages at bottom.
        $this->add_separator();

        if ($this->groupstudents) {
            $this->add_average_row(get_string('groupavg', 'grades'), $this->groupstudents);
        }

        if ($this->students) {
            $this->add_average_row(get_string('overallaverage', 'grades'), $this->students);
        }
    }

    /**
     * Add an average grade over the attempts of a set of users.
     * @param string $label the title ot use for this row.
     * @param array $users the users to average over.
     */
    protected function add_average_row($label, $users) {
        global $DB;

        list($fields, $from, $where, $params) = $this->base_sql($users);
        $record = $DB->get_record_sql("
                SELECT AVG(quiza.sumgrades) AS grade, COUNT(quiza.sumgrades) AS numaveraged
                  FROM $from
                 WHERE $where", $params);
        $record->grade = quiz_rescale_grade($record->grade, $this->quiz, false);

        if ($this->is_downloading()) {
            $namekey = 'lastname';
        } else {
            $namekey = 'fullname';
        }
        $averagerow = array(
            $namekey    => $label,
            'sumgrades' => $this->format_average($record),
            'feedbacktext'=> strip_tags(quiz_report_feedback_for_grade(
                                        $record->grade, $this->quiz->id, $this->context))
        );

            $dm = new question_engine_data_mapper();
            $qubaids = new qubaid_join($from, 'quiza.uniqueid', $where, $params);
            $avggradebyq = $dm->load_average_marks($qubaids, array_keys($this->questions));

            $averagerow += $this->format_average_grade_for_questions($avggradebyq);
        

        $this->add_data_keyed($averagerow);
    }

    /**
     * Helper userd by {@link add_average_row()}.
     * @param array $gradeaverages the raw grades.
     * @return array the (partial) row of data.
     */
    protected function format_average_grade_for_questions($gradeaverages) {
        $row = array();

        if (!$gradeaverages) {
            $gradeaverages = array();
        }

        foreach ($this->questions as $question) {
            if (isset($gradeaverages[$question->slot]) && $question->maxmark > 0) {
                $record = $gradeaverages[$question->slot];
                $record->grade = quiz_rescale_grade(
                        $record->averagefraction * $question->maxmark, $this->quiz, false);

            } else {
                $record = new stdClass();
                $record->grade = null;
                $record->numaveraged = 0;
            }

            $row['qsgrade' . $question->slot] = $this->format_average($record, true);
        }

        return $row;
    }

    /**
     * Format an entry in an average row.
     * @param object $record with fields grade and numaveraged
     */
    protected function format_average($record, $question = false) {
        if (is_null($record->grade)) {
            $average = '-';
        } else if ($question) {
            $average = quiz_format_question_grade($this->quiz, $record->grade);
        } else {
            $average = quiz_format_grade($this->quiz, $record->grade);
        }

        if ($this->download) {
            return $average;
        } else if (is_null($record->numaveraged) || $record->numaveraged == 0) {
            return html_writer::tag('span', html_writer::tag('span',
                    $average, array('class' => 'average')), array('class' => 'avgcell'));
        } else {
            return html_writer::tag('span', html_writer::tag('span',
                    $average, array('class' => 'average')) . ' ' . html_writer::tag('span',
                    '(' . $record->numaveraged . ')', array('class' => 'count')),
                    array('class' => 'avgcell'));
        }
    }


    public function col_sumgrades($attempt) {
        if ($attempt->state != quiz_attempt::FINISHED) {
            return '-';
        }

        $grade = quiz_rescale_grade($attempt->sumgrades, $this->quiz);
        if ($this->is_downloading()) {
            return $grade;
        }

        if (isset($this->regradedqs[$attempt->usageid])) {
            $newsumgrade = 0;
            $oldsumgrade = 0;
            foreach ($this->questions as $question) {
                if (isset($this->regradedqs[$attempt->usageid][$question->slot])) {
                    $newsumgrade += $this->regradedqs[$attempt->usageid]
                            [$question->slot]->newfraction * $question->maxmark;
                    $oldsumgrade += $this->regradedqs[$attempt->usageid]
                            [$question->slot]->oldfraction * $question->maxmark;
                } else {
                    $newsumgrade += $this->lateststeps[$attempt->usageid]
                            [$question->slot]->fraction * $question->maxmark;
                    $oldsumgrade += $this->lateststeps[$attempt->usageid]
                            [$question->slot]->fraction * $question->maxmark;
                }
            }
            $newsumgrade = quiz_rescale_grade($newsumgrade, $this->quiz);
            $oldsumgrade = quiz_rescale_grade($oldsumgrade, $this->quiz);
            $grade = html_writer::tag('del', $oldsumgrade) . '/' .
                    html_writer::empty_tag('br') . $newsumgrade;
        }
        return html_writer::link(new moodle_url('/mod/quiz/review.php',
                array('attempt' => $attempt->attempt)), $grade,
                array('title' => get_string('reviewattempt', 'quiz')));
    }

    /**
     * @param string $colname the name of the column.
     * @param object $attempt the row of data - see the SQL in display() in
     * mod/quiz/report/overview/report.php to see what fields are present,
     * and what they are called.
     * @return string the contents of the cell.
     */
    public function other_cols($colname, $attempt) {
        if (!preg_match('/^qsgrade(\d+)$/', $colname, $matches)) {
            return null;
        }
        $slot = $matches[1];

        $question = $this->questions[$slot];
        if (!isset($this->lateststeps[$attempt->usageid][$slot])) {
            return '-';
        }

        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        $state = question_state::get($stepdata->state);

        if ($question->maxmark == 0) {
            $grade = '-';
        } else if (is_null($stepdata->fraction)) {
            if ($state == question_state::$needsgrading) {
                $grade = get_string('requiresgrading', 'question');
            } else {
                $grade = '-';
            }
        } else {
            $grade = quiz_rescale_grade(
                    $stepdata->fraction * $question->maxmark, $this->quiz, 'question');
        }

        if ($this->is_downloading()) {
            return $grade;
        }

        if (isset($this->regradedqs[$attempt->usageid][$slot])) {
            $gradefromdb = $grade;
            $newgrade = quiz_rescale_grade(
                    $this->regradedqs[$attempt->usageid][$slot]->newfraction * $question->maxmark,
                    $this->quiz, 'question');
            $oldgrade = quiz_rescale_grade(
                    $this->regradedqs[$attempt->usageid][$slot]->oldfraction * $question->maxmark,
                    $this->quiz, 'question');

            $grade = html_writer::tag('del', $oldgrade) . '/' .
                    html_writer::empty_tag('br') . $newgrade;
        }

        return $this->make_review_link($grade, $attempt, $slot);
    }

    public function col_regraded($attempt) {
        if ($attempt->regraded == '') {
            return '';
        } else if ($attempt->regraded == 0) {
            return get_string('needed', 'quiz_overview');
        } else if ($attempt->regraded == 1) {
            return get_string('done', 'quiz_overview');
        }
    }


    /**
     * Generate the display of the checkbox column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_checkbox($attempt) {
        if ($attempt->attempt) {
            return '<input type="checkbox" name="attemptid[]" value="'.$attempt->attempt.'" />';
        } else {
            return '';
        }
    }

    /**
     * Generate the display of the user's picture column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_picture($attempt) {
        global $OUTPUT;
        $user = new stdClass();
        $user->id = $attempt->userid;
        $user->lastname = $attempt->lastname;
        $user->firstname = $attempt->firstname;
        $user->imagealt = $attempt->imagealt;
        $user->picture = $attempt->picture;
        $user->email = $attempt->email;
        return $OUTPUT->user_picture($user);
    }

    /**
     * Generate the display of the user's full name column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($attempt) {
        $html = parent::col_fullname($attempt);
        if ($this->is_downloading()) {
            return $html;
        }

        return $html . html_writer::empty_tag('br') . html_writer::link(
                new moodle_url('/mod/quiz/review.php', array('attempt' => $attempt->attempt)),
                get_string('reviewattempt', 'quiz'), array('class' => 'reviewlink'));
    }

    /**
     * Generate the display of the attempt state column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_state($attempt) {
        if (!is_null($attempt->attempt)) {
            return quiz_attempt::state_name($attempt->state);
        } else {
            return  '-';
        }
    }

    /**
     * Generate the display of the start time column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timestart($attempt) {
        if ($attempt->attempt) {
            return userdate($attempt->timestart, $this->strtimeformat);
        } else {
            return  '-';
        }
    }

    /**
     * Generate the display of the finish time column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_timefinish($attempt) {
        if ($attempt->attempt && $attempt->timefinish) {
            return userdate($attempt->timefinish, $this->strtimeformat);
        } else {
            return  '-';
        }
    }

    /**
     * Generate the display of the time taken column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_duration($attempt) {
        if ($attempt->timefinish) {
            return format_time($attempt->timefinish - $attempt->timestart);
        } else {
            return '-';
        }
    }

    /**
     * Generate the display of the feedback column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_feedbacktext($attempt) {
        if ($attempt->state != quiz_attempt::FINISHED) {
            return '-';
        }

        $feedback = quiz_report_feedback_for_grade(
                quiz_rescale_grade($attempt->sumgrades, $this->quiz, false),
                $this->quiz->id, $this->context);

        if ($this->is_downloading()) {
            $feedback = strip_tags($feedback);
        }

        return $feedback;
    }

    public function get_row_class($attempt) {
        
            return '';
        
    }

    /**
     * Make a link to review an individual question in a popup window.
     *
     * @param string $data HTML fragment. The text to make into the link.
     * @param object $attempt data for the row of the table being output.
     * @param int $slot the number used to identify this question within this usage.
     */
    public function make_review_link($data, $attempt, $slot) {
        global $OUTPUT;

        $stepdata = $this->lateststeps[$attempt->usageid][$slot];
        $state = question_state::get($stepdata->state);

        $flag = '';
        if ($stepdata->flagged) {
            $flag = $OUTPUT->pix_icon('i/flagged', get_string('flagged', 'question'),
                    'moodle', array('class' => 'questionflag'));
        }

        $feedbackimg = '';
        if ($state->is_finished() && $state != question_state::$needsgrading) {
            $feedbackimg = $this->icon_for_fraction($stepdata->fraction);
        }

        $output = html_writer::tag('span', $feedbackimg . html_writer::tag('span',
                $data, array('class' => $state->get_state_class(true))) . $flag, array('class' => 'que'));

        $url = new moodle_url('/mod/quiz/reviewquestion.php',
                array('attempt' => $attempt->attempt, 'slot' => $slot));
        $output = $OUTPUT->action_link($url, $output,
                new popup_action('click', $url, 'reviewquestion',
                        array('height' => 450, 'width' => 650)),
                array('title' => get_string('reviewresponse', 'quiz')));

        return $output;
    }

    /**
     * Return an appropriate icon (green tick, red cross, etc.) for a grade.
     * @param float $fraction grade on a scale 0..1.
     * @return string html fragment.
     */
    protected function icon_for_fraction($fraction) {
        global $OUTPUT;

        $feedbackclass = question_state::graded_state_for_fraction($fraction)->get_feedback_class();
        return $OUTPUT->pix_icon('i/grade_' . $feedbackclass, get_string($feedbackclass, 'question'),
                'moodle', array('class' => 'icon'));
    }

    /**
     * Load information about the latest state of selected questions in selected attempts.
     *
     * The results are returned as an two dimensional array $qubaid => $slot => $dataobject
     *
     * @param qubaid_condition $qubaids used to restrict which usages are included
     * in the query. See {@link qubaid_condition}.
     * @return array of records. See the SQL in this function to see the fields available.
     */
    protected function load_question_latest_steps(qubaid_condition $qubaids) {
        $dm = new question_engine_data_mapper();
        $latesstepdata = $dm->load_questions_usages_latest_steps(
                $qubaids, array_keys($this->questions));

        $lateststeps = array();
        foreach ($latesstepdata as $step) {
            $lateststeps[$step->questionusageid][$step->slot] = $step;
        }

        return $lateststeps;
    }

    /**
     * Does this report require the detailed information for each question from the
     * question_attempts_steps table?
     * @return bool should {@link query_db()} call {@link load_question_latest_steps}?
     */
    protected function requires_latest_steps_loaded() {
        return false;
    }

    /**
     * Is this a column that depends on joining to the latest state information?
     * If so, return the corresponding slot. If not, return false.
     * @param string $column a column name
     * @return int false if no, else a slot.
     */
    protected function is_latest_step_column($column) {
        return false;
    }

    /**
     * Get any fields that might be needed when sorting on date for a particular slot.
     * @param int $slot the slot for the column we want.
     * @param string $alias the table alias for latest state information relating to that slot.
     */
    protected function get_required_latest_state_fields($slot, $alias) {
        return '';
    }

    /**
     * Contruct all the parts of the main database query.
     * @param array $reportstudents list if userids of users to include in the report.
     * @return array with 4 elements ($fields, $from, $where, $params) that can be used to
     *      build the actual database query.
     */
    public function base_sql($reportstudents) {
        global $DB;

        $fields = $DB->sql_concat('u.id', "'#'", 'COALESCE(quiza.attempt, 0)') . ' AS uniqueid,';

        $extrafields = get_extra_user_fields_sql($this->context, 'u', '',
                array('id', 'idnumber', 'firstname', 'lastname', 'picture',
                'imagealt', 'institution', 'department', 'email'));
        $fields .= '
                quiza.uniqueid AS usageid,
                quiza.id AS attempt,
                u.id AS userid,
                u.idnumber,
                u.firstname,
                u.lastname,
                u.picture,
                u.imagealt,
                u.institution,
                u.department,
                u.email' . $extrafields . ',
                quiza.state,
                quiza.sumgrades,
                quiza.timefinish,
                quiza.timestart,
                CASE WHEN quiza.timefinish = 0 THEN null
                     WHEN quiza.timefinish > quiza.timestart THEN quiza.timefinish - quiza.timestart
                     ELSE 0 END AS duration';
            // To explain that last bit, timefinish can be non-zero and less
            // than timestart when you have two load-balanced servers with very
            // badly synchronised clocks, and a student does a really quick attempt.

        // This part is the same for all cases. Join the users and quiz_attempts tables.
        $from = "\n{user} u";
        $from .= "\nLEFT JOIN {quiz_attempts} quiza ON
                                    quiza.userid = u.id AND quiza.quiz = :quizid";
        $params = array('quizid' => $this->quiz->id);

        // Show all students with or without attempts.
        list($usql, $uparams) = $DB->get_in_or_equal(
                $reportstudents, SQL_PARAMS_NAMED, 'u');
        $params += $uparams;
        $where = "u.id $usql AND (quiza.preview = 0 OR quiza.preview IS NULL)";

        $states = array(quiz_attempt::IN_PROGRESS, quiz_attempt::OVERDUE,
            quiz_attempt::FINISHED, quiz_attempt::ABANDONED);
            
        if ($states) {
            list($statesql, $stateparams) = $DB->get_in_or_equal($states,
                    SQL_PARAMS_NAMED, 'state');
            $params += $stateparams;
            $where .= " AND (quiza.state $statesql OR quiza.state IS NULL)";
        }

        return array($fields, $from, $where, $params);
    }

    /**
     * Add the information about the latest state of the question with slot
     * $slot to the query.
     *
     * The extra information is added as a join to a
     * 'table' with alias qa$slot, with columns that are a union of
     * the columns of the question_attempts and question_attempts_states tables.
     *
     * @param int $slot the question to add information for.
     */
    protected function add_latest_state_join($slot) {
        $alias = 'qa' . $slot;

        $fields = $this->get_required_latest_state_fields($slot, $alias);
        if (!$fields) {
            return;
        }

        // This condition roughly filters the list of attempts to be considered.
        // It is only used in a subselect to help crappy databases (see MDL-30122)
        // therefore, it is better to use a very simple join, which may include
        // too many records, than to do a super-accurate join.
        $qubaids = new qubaid_join("{quiz_attempts} {$alias}quiza", "{$alias}quiza.uniqueid",
                "{$alias}quiza.quiz = :{$alias}quizid", array("{$alias}quizid" => $this->sql->params['quizid']));

        $dm = new question_engine_data_mapper();
        list($inlineview, $viewparams) = $dm->question_attempt_latest_state_view($alias, $qubaids);

        $this->sql->fields .= ",\n$fields";
        $this->sql->from .= "\nLEFT JOIN $inlineview ON " .
                "$alias.questionusageid = quiza.uniqueid AND $alias.slot = :{$alias}slot";
        $this->sql->params[$alias . 'slot'] = $slot;
        $this->sql->params = array_merge($this->sql->params, $viewparams);
    }

    /**
     * Get an appropriate qubaid_condition for loading more data about the
     * attempts we are displaying.
     * @return qubaid_condition
     */
    protected function get_qubaids_condition() {
        if (is_null($this->rawdata)) {
            throw new coding_exception(
                    'Cannot call get_qubaids_condition until the main data has been loaded.');
        }

        if ($this->is_downloading()) {
            // We want usages for all attempts.
            return new qubaid_join($this->sql->from, 'quiza.uniqueid',
                    $this->sql->where, $this->sql->params);
        }

        $qubaids = array();
        foreach ($this->rawdata as $attempt) {
            if ($attempt->usageid > 0) {
                $qubaids[] = $attempt->usageid;
            }
        }

        return new qubaid_list($qubaids);
    }

    public function query_db($pagesize, $useinitialsbar = true) {
        $doneslots = array();
        foreach ($this->get_sort_columns() as $column => $notused) {
            $slot = $this->is_latest_step_column($column);
            if ($slot && !in_array($slot, $doneslots)) {
                $this->add_latest_state_join($slot);
                $doneslots[] = $slot;
            }
        }

        parent::query_db($pagesize, $useinitialsbar);

        if ($this->requires_latest_steps_loaded()) {
            $qubaids = $this->get_qubaids_condition();
            $this->lateststeps = $this->load_question_latest_steps($qubaids);
        }
    }

    public function get_sort_columns() {
        // Add attemptid as a final tie-break to the sort. This ensures that
        // Attempts by the same student appear in order when just sorting by name.
        $sortcolumns = parent::get_sort_columns();
        $sortcolumns['quiza.id'] = SORT_ASC;
        return $sortcolumns;
    }

    public function wrap_html_start() {
        if ($this->is_downloading() || !$this->includecheckboxes) {
            return;
        }

        $url = $this->options->get_url();
        $url->param('sesskey', sesskey());

        echo '<div id="tablecontainer">';
        echo '<form id="attemptsform" method="post" action="' . $url->out_omit_querystring() . '">';

        echo html_writer::input_hidden_params($url);
        echo '<div>';
    }

    public function wrap_html_finish() {
        if ($this->is_downloading() || !$this->includecheckboxes) {
            return;
        }

        echo '<div id="commands">';
        echo '<a href="javascript:select_all_in(\'DIV\', null, \'tablecontainer\');">' .
                get_string('selectall', 'quiz') . '</a> / ';
        echo '<a href="javascript:deselect_all_in(\'DIV\', null, \'tablecontainer\');">' .
                get_string('selectnone', 'quiz') . '</a> ';
        echo '&nbsp;&nbsp;';
        $this->submit_buttons();
        echo '</div>';

        // Close the form.
        echo '</div>';
        echo '</form></div>';
    }


}



/*
 * Quiz report subclass for the overview (grades) report.
 *
 * @copyright 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bioauth_report {
        
    const NO_GROUPS_ALLOWED = -2;
    
    /** @var int default page size for reports. */
    const DEFAULT_PAGE_SIZE = 30;

    /** @var string constant used for the options, means all users with attempts. */
    const ALL_WITH = 'all_with';
    /** @var string constant used for the options, means only enrolled users with attempts. */
    const ENROLLED_WITH = 'enrolled_with';
    /** @var string constant used for the options, means only enrolled users without attempts. */
    const ENROLLED_WITHOUT = 'enrolled_without';
    /** @var string constant used for the options, means all enrolled users. */
    const ENROLLED_ALL = 'enrolled_any';

    /** @var string the mode this report is. */
    protected $mode;

    /** @var object the quiz context. */
    protected $context;

    /** @var mod_bioauth_overview_report_form The settings form to use. */
    protected $form;

    /** @var string SQL fragment for selecting the attempt that gave the final grade,
     * if applicable. */
    protected $qmsubselect;

    /** @var boolean caches the results of {@link should_show_grades()}. */
    protected $showgrades = null;

    protected $usercanseegrades;

    /**
     *  Initialise various aspects of this report.
     *
     * @param string $mode
     * @param string $formclass
     * @param object $quiz
     * @param object $cm
     * @param object $course
     */
    protected function init($quiz, $cm, $course) {
        
        $this->context = context_module::instance($cm->id);
        $this->usercanseegrades = quiz_report_should_show_grades($quiz, context_module::instance($cm->id));
        
        list($currentgroup, $students, $groupstudents, $allowed) =
                $this->load_relevant_students($cm, $course);

        return array($currentgroup, $students, $groupstudents, $allowed);
    }

    /**
     * Get the base URL for this report.
     * @return moodle_url the URL.
     */
    protected function get_base_url() {
        return new moodle_url('/local/bioauth/report.php',
                array('id' => $this->context->instanceid, 'mode' => $this->mode));
    }

    /**
     * Get information about which students to show in the report.
     * @param object $cm the coures module.
     * @param object $course the course settings.
     * @return an array with four elements:
     *      0 => integer the current group id (0 for none).
     *      1 => array ids of all the students in this course.
     *      2 => array ids of all the students in the current group.
     *      3 => array ids of all the students to show in the report. Will be the
     *              same as either element 1 or 2.
     */
    protected function load_relevant_students($cm, $course = null) {
        $currentgroup = $this->get_current_group($cm, $course, $this->context);

        if ($currentgroup == self::NO_GROUPS_ALLOWED) {
            return array($currentgroup, array(), array(), array());
        }

        if (!$students = get_users_by_capability($this->context,
                array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'),
                'u.id, 1', '', '', '', '', '', false)) {
            $students = array();
        } else {
            $students = array_keys($students);
        }

        if (empty($currentgroup)) {
            return array($currentgroup, $students, array(), $students);
        }

        // We have a currently selected group.
        if (!$groupstudents = get_users_by_capability($this->context,
                array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'),
                'u.id, 1', '', '', '', $currentgroup, '', false)) {
            $groupstudents = array();
        } else {
            $groupstudents = array_keys($groupstudents);
        }

        return array($currentgroup, $students, $groupstudents, $groupstudents);
    }

    /**
     * Add all the user-related columns to the $columns and $headers arrays.
     * @param table_sql $table the table being constructed.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_user_columns($table, &$columns, &$headers) {
        global $CFG;
        if (!$table->is_downloading() && $CFG->grade_report_showuserimage) {
            $columns[] = 'picture';
            $headers[] = '';
        }
        if (!$table->is_downloading()) {
            $columns[] = 'fullname';
            $headers[] = get_string('name');
        } else {
            $columns[] = 'lastname';
            $headers[] = get_string('lastname');
            $columns[] = 'firstname';
            $headers[] = get_string('firstname');
        }

        // When downloading, some extra fields are always displayed (because
        // there's no space constraint) so do not include in extra-field list.
        $extrafields = get_extra_user_fields($this->context,
                $table->is_downloading() ? array('institution', 'department', 'email') : array());
        foreach ($extrafields as $field) {
            $columns[] = $field;
            $headers[] = get_user_field_name($field);
        }

        if ($table->is_downloading()) {
            $columns[] = 'institution';
            $headers[] = get_string('institution');

            $columns[] = 'department';
            $headers[] = get_string('department');

            $columns[] = 'email';
            $headers[] = get_string('email');
        }
    }

    /**
     * Set the display options for the user-related columns in the table.
     * @param table_sql $table the table being constructed.
     */
    protected function configure_user_columns($table) {
        $table->column_suppress('picture');
        $table->column_suppress('fullname');
        $table->column_suppress('idnumber');

        $table->column_class('picture', 'picture');
        $table->column_class('lastname', 'bold');
        $table->column_class('firstname', 'bold');
        $table->column_class('fullname', 'bold');
    }

    /**
     * Add the state column to the $columns and $headers arrays.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_state_column(&$columns, &$headers) {
        $columns[] = 'state';
        $headers[] = get_string('attemptstate', 'quiz');
    }

    /**
     * Add all the time-related columns to the $columns and $headers arrays.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_time_columns(&$columns, &$headers) {
        $columns[] = 'timestart';
        $headers[] = get_string('startedon', 'quiz');

        $columns[] = 'timefinish';
        $headers[] = get_string('timecompleted', 'quiz');

        $columns[] = 'duration';
        $headers[] = get_string('attemptduration', 'quiz');
    }

    /**
     * Add all the grade and feedback columns, if applicable, to the $columns
     * and $headers arrays.
     * @param object $quiz the quiz settings.
     * @param bool $usercanseegrades whether the user is allowed to see grades for this quiz.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     * @param bool $includefeedback whether to include the feedbacktext columns
     */
    protected function add_grade_columns($quiz, $usercanseegrades, &$columns, &$headers, $includefeedback = true) {
        if ($usercanseegrades) {
            $columns[] = 'sumgrades';
            $headers[] = get_string('grade', 'quiz') . '/' .
                    quiz_format_grade($quiz, $quiz->grade);
        }

        if ($includefeedback && quiz_has_feedback($quiz)) {
            $columns[] = 'feedbacktext';
            $headers[] = get_string('feedback', 'quiz');
        }
    }

    /**
     * Set up the table.
     * @param table_sql $table the table being constructed.
     * @param array $columns the list of columns.
     * @param array $headers the columns headings.
     * @param moodle_url $reporturl the URL of this report.
     * @param mod_bioauth_overview_report_options $options the display options.
     * @param bool $collapsible whether to allow columns in the report to be collapsed.
     */
    protected function set_up_table_columns($table, $columns, $headers, $reporturl, $collapsible) {
        $table->define_columns($columns);
        $table->define_headers($headers);
        $table->sortable(true, 'uniqueid');

        $table->define_baseurl($this->get_base_url());

        $this->configure_user_columns($table);

        $table->no_sorting('feedbacktext');
        $table->column_class('sumgrades', 'bold');

        $table->set_attribute('id', 'attempts');

        $table->collapsible($collapsible);
    }

    /**
     * Delete the quiz attempts
     * @param object $quiz the quiz settings. Attempts that don't belong to
     * this quiz are not deleted.
     * @param object $cm the course_module object.
     * @param array $attemptids the list of attempt ids to delete.
     * @param array $allowed This list of userids that are visible in the report.
     *      Users can only delete attempts that they are allowed to see in the report.
     *      Empty means all users.
     */
    protected function delete_selected_attempts($quiz, $cm, $attemptids, $allowed) {
        global $DB;

        foreach ($attemptids as $attemptid) {
            $attempt = $DB->get_record('quiz_attempts', array('id' => $attemptid));
            if (!$attempt || $attempt->quiz != $quiz->id || $attempt->preview != 0) {
                // Ensure the attempt exists, and belongs to this quiz. If not skip.
                continue;
            }
            if ($allowed && !in_array($attempt->userid, $allowed)) {
                // Ensure the attempt belongs to a student included in the report. If not skip.
                continue;
            }
            add_to_log($quiz->course, 'quiz', 'delete attempt', 'report.php?id=' . $cm->id,
                    $attemptid, $cm->id);
            quiz_delete_attempt($attempt, $quiz);
        }
    }
    
    /**
     * Initialise some parts of $PAGE and start output.
     *
     * @param object $cm the course_module information.
     * @param object $coures the course settings.
     * @param object $quiz the quiz settings.
     * @param string $reportmode the report name.
     */
    public function print_header_and_tabs($cm, $course, $quiz, $reportmode = 'overview') {
        global $PAGE, $OUTPUT;

        // Print the page header.
        $PAGE->set_title(format_string($quiz->name));
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
    }

    public function display($quiz, $cm, $course) {
        global $CFG, $DB, $OUTPUT, $PAGE;

        list($currentgroup, $students, $groupstudents, $allowed) =
                $this->init($quiz, $cm, $course);

        // Load the required questions.
        $questions = quiz_report_get_significant_questions($quiz);

        // Prepare for downloading, if applicable.
        $courseshortname = format_string($course->shortname, true,
                array('context' => context_course::instance($course->id)));
        $table = new bioauth_report_table('local-bioauth-report', $quiz, $this->context, $groupstudents, $students, $questions, $this->get_base_url());
        $filename = quiz_report_download_filename(get_string('overviewfilename', 'quiz_overview'),
                $courseshortname, $quiz->name);
        $table->is_downloading('', $filename,
                $courseshortname . ' ' . format_string($quiz->name, true));
        if ($table->is_downloading()) {
            raise_memory_limit(MEMORY_EXTRA);
        }
        // Start output.
        if (!$table->is_downloading()) {
            // Only print headers if not asked to download data.
            $this->print_header_and_tabs($cm, $course, $quiz, $this->mode);
        }

        if ($groupmode = groups_get_activity_groupmode($cm)) {
            // Groups are being used, so output the group selector if we are not downloading.
            if (!$table->is_downloading()) {
                groups_print_activity_menu($cm, $options->get_url());
            }
        }

        // Print information on the number of existing attempts.
        if (!$table->is_downloading()) {
            // Do not print notices when downloading.
            if ($strattemptnum = quiz_num_attempt_summary($quiz, $cm, true, $currentgroup)) {
                echo '<div class="quizattemptcounts">' . $strattemptnum . '</div>';
            }
        }

        $hasquestions = quiz_questions_in_quiz($quiz->questions);
        if (!$table->is_downloading()) {
            if (!$hasquestions) {
                echo quiz_no_questions_message($quiz, $cm, $this->context);
            } else if (!$students) {
                echo $OUTPUT->notification(get_string('nostudentsyet'));
            } else if ($currentgroup && !$groupstudents) {
                echo $OUTPUT->notification(get_string('nostudentsingroup'));
            }
        }

        $hasstudents = $students && (!$currentgroup || $groupstudents);
        if ($hasquestions && ($hasstudents || $options->attempts == self::ALL_WITH)) {
            // Construct the SQL.
            $fields = $DB->sql_concat('u.id', "'#'", 'COALESCE(quiza.attempt, 0)') .
                    ' AS uniqueid, ';
            if ($this->qmsubselect) {
                $fields .=
                    "(CASE " .
                    "   WHEN {$this->qmsubselect} THEN 1" .
                    "   ELSE 0 " .
                    "END) AS gradedattempt, ";
            }

            list($fields, $from, $where, $params) = $table->base_sql($allowed);

            $table->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);

            // Test to see if there are any regraded attempts to be listed.
            $fields .= ", COALESCE((
                                SELECT MAX(qqr.regraded)
                                  FROM {quiz_overview_regrades} qqr
                                 WHERE qqr.questionusageid = quiza.uniqueid
                          ), -1) AS regraded";

            $table->set_sql($fields, $from, $where, $params);

            if (!$table->is_downloading()) {
                // Output the regrade buttons.
                if (has_capability('mod/quiz:regrade', $this->context)) {
                    $regradesneeded = $this->count_question_attempts_needing_regrade(
                            $quiz, $groupstudents);
                    if ($currentgroup) {
                        $a= new stdClass();
                        $a->groupname = groups_get_group_name($currentgroup);
                        $a->coursestudents = get_string('participants');
                        $a->countregradeneeded = $regradesneeded;
                        $regradealldrydolabel =
                                get_string('regradealldrydogroup', 'quiz_overview', $a);
                        $regradealldrylabel =
                                get_string('regradealldrygroup', 'quiz_overview', $a);
                        $regradealllabel =
                                get_string('regradeallgroup', 'quiz_overview', $a);
                    } else {
                        $regradealldrydolabel =
                                get_string('regradealldrydo', 'quiz_overview', $regradesneeded);
                        $regradealldrylabel =
                                get_string('regradealldry', 'quiz_overview');
                        $regradealllabel =
                                get_string('regradeall', 'quiz_overview');
                    }
                    $displayurl = new moodle_url($this->get_base_url(), array('sesskey' => sesskey()));
                    echo '<div class="mdl-align">';
                    echo '<form action="'.$displayurl->out_omit_querystring().'">';
                    echo '<div>';
                    echo html_writer::input_hidden_params($displayurl);
                    echo '<input type="submit" name="regradeall" value="'.$regradealllabel.'"/>';
                    echo '<input type="submit" name="regradealldry" value="' .
                            $regradealldrylabel . '"/>';
                    if ($regradesneeded) {
                        echo '<input type="submit" name="regradealldrydo" value="' .
                                $regradealldrydolabel . '"/>';
                    }
                    echo '</div>';
                    echo '</form>';
                    echo '</div>';
                }
                // Print information on the grading method.
                if ($strattempthighlight = quiz_report_highlighting_grading_method(
                        $quiz, $this->qmsubselect, false)) {
                    echo '<div class="quizattemptcounts">' . $strattempthighlight . '</div>';
                }
            }

            // Define table columns.
            $columns = array();
            $headers = array();

            $this->add_user_columns($table, $columns, $headers);
            $this->add_state_column($columns, $headers);
            $this->add_time_columns($columns, $headers);

            $this->add_grade_columns($quiz, $this->usercanseegrades, $columns, $headers, false);

            if (!$table->is_downloading() && has_capability('mod/quiz:regrade', $this->context) &&
                    $this->has_regraded_questions($from, $where, $params)) {
                $columns[] = 'regraded';
                $headers[] = get_string('regrade', 'quiz_overview');
            }

                foreach ($questions as $slot => $question) {
                    // Ignore questions of zero length.
                    $columns[] = 'qsgrade' . $slot;
                    $header = get_string('qbrief', 'quiz', $question->number);
                    if (!$table->is_downloading()) {
                        $header .= '<br />';
                    } else {
                        $header .= ' ';
                    }
                    $header .= '/' . quiz_rescale_grade($question->maxmark, $quiz, 'question');
                    $headers[] = $header;
                }
            

            $this->set_up_table_columns($table, $columns, $headers, $this->get_base_url(), false);
            $table->set_attribute('class', 'generaltable generalbox grades');

            $table->out(bioauth_report::DEFAULT_PAGE_SIZE, true);
        }

        if (!$table->is_downloading() && $this->usercanseegrades) {
            $output = $PAGE->get_renderer('mod_quiz');
            if ($currentgroup && $groupstudents) {
                list($usql, $params) = $DB->get_in_or_equal($groupstudents);
                $params[] = $quiz->id;
                if ($DB->record_exists_select('quiz_grades', "userid $usql AND quiz = ?",
                        $params)) {
                    $imageurl = new moodle_url('/mod/quiz/report/overview/overviewgraph.php',
                            array('id' => $quiz->id, 'groupid' => $currentgroup));
                    $graphname = get_string('overviewreportgraphgroup', 'quiz_overview',
                            groups_get_group_name($currentgroup));
                    echo $output->graph($imageurl, $graphname);
                }
            }

            if ($DB->record_exists('quiz_grades', array('quiz'=> $quiz->id))) {
                $imageurl = new moodle_url('/mod/quiz/report/overview/overviewgraph.php',
                        array('id' => $quiz->id));
                $graphname = get_string('overviewreportgraph', 'quiz_overview');
                echo $output->graph($imageurl, $graphname);
            }
        }
        return true;
    }

    /**
     * Unlock the session and allow the regrading process to run in the background.
     */
    protected function unlock_session() {
        session_get_instance()->write_close();
        ignore_user_abort(true);
    }

    /**
     * Regrade a particular quiz attempt. Either for real ($dryrun = false), or
     * as a pretend regrade to see which fractions would change. The outcome is
     * stored in the quiz_overview_regrades table.
     *
     * Note, $attempt is not upgraded in the database. The caller needs to do that.
     * However, $attempt->sumgrades is updated, if this is not a dry run.
     *
     * @param object $attempt the quiz attempt to regrade.
     * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     * @param array $slots if null, regrade all questions, otherwise, just regrade
     *      the quetsions with those slots.
     */
    protected function regrade_attempt($attempt, $dryrun = false, $slots = null) {
        global $DB;
        // Need more time for a quiz with many questions.
        set_time_limit(300);

        $transaction = $DB->start_delegated_transaction();

        $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);

        if (is_null($slots)) {
            $slots = $quba->get_slots();
        }

        $finished = $attempt->state == quiz_attempt::FINISHED;
        foreach ($slots as $slot) {
            $qqr = new stdClass();
            $qqr->oldfraction = $quba->get_question_fraction($slot);

            $quba->regrade_question($slot, $finished);

            $qqr->newfraction = $quba->get_question_fraction($slot);

            if (abs($qqr->oldfraction - $qqr->newfraction) > 1e-7) {
                $qqr->questionusageid = $quba->get_id();
                $qqr->slot = $slot;
                $qqr->regraded = empty($dryrun);
                $qqr->timemodified = time();
                $DB->insert_record('quiz_overview_regrades', $qqr, false);
            }
        }

        if (!$dryrun) {
            question_engine::save_questions_usage_by_activity($quba);
        }

        $transaction->allow_commit();

        // Really, PHP should not need this hint, but without this, we just run out of memory.
        $quba = null;
        $transaction = null;
        gc_collect_cycles();
    }

    /**
     * Regrade attempts for this quiz, exactly which attempts are regraded is
     * controlled by the parameters.
     * @param object $quiz the quiz settings.
     * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     * @param array $groupstudents blank for all attempts, otherwise regrade attempts
     * for these users.
     * @param array $attemptids blank for all attempts, otherwise only regrade
     * attempts whose id is in this list.
     */
    protected function regrade_attempts($quiz, $dryrun = false,
            $groupstudents = array(), $attemptids = array()) {
        global $DB;
        $this->unlock_session();

        $where = "quiz = ? AND preview = 0";
        $params = array($quiz->id);

        if ($groupstudents) {
            list($usql, $uparams) = $DB->get_in_or_equal($groupstudents);
            $where .= " AND userid $usql";
            $params = array_merge($params, $uparams);
        }

        if ($attemptids) {
            list($asql, $aparams) = $DB->get_in_or_equal($attemptids);
            $where .= " AND id $asql";
            $params = array_merge($params, $aparams);
        }

        $attempts = $DB->get_records_select('quiz_attempts', $where, $params);
        if (!$attempts) {
            return;
        }

        $this->clear_regrade_table($quiz, $groupstudents);

        foreach ($attempts as $attempt) {
            $this->regrade_attempt($attempt, $dryrun);
        }

        if (!$dryrun) {
            $this->update_overall_grades($quiz);
        }
    }

    /**
     * Regrade those questions in those attempts that are marked as needing regrading
     * in the quiz_overview_regrades table.
     * @param object $quiz the quiz settings.
     * @param array $groupstudents blank for all attempts, otherwise regrade attempts
     * for these users.
     */
    protected function regrade_attempts_needing_it($quiz, $groupstudents) {
        global $DB;
        $this->unlock_session();

        $where = "quiza.quiz = ? AND quiza.preview = 0 AND qqr.regraded = 0";
        $params = array($quiz->id);

        // Fetch all attempts that need regrading.
        if ($groupstudents) {
            list($usql, $uparams) = $DB->get_in_or_equal($groupstudents);
            $where .= " AND quiza.userid $usql";
            $params += $uparams;
        }

        $toregrade = $DB->get_records_sql("
                SELECT quiza.uniqueid, qqr.slot
                FROM {quiz_attempts} quiza
                JOIN {quiz_overview_regrades} qqr ON qqr.questionusageid = quiza.uniqueid
                WHERE $where", $params);

        if (!$toregrade) {
            return;
        }

        $attemptquestions = array();
        foreach ($toregrade as $row) {
            $attemptquestions[$row->uniqueid][] = $row->slot;
        }
        $attempts = $DB->get_records_list('quiz_attempts', 'uniqueid',
                array_keys($attemptquestions));

        $this->clear_regrade_table($quiz, $groupstudents);

        foreach ($attempts as $attempt) {
            $this->regrade_attempt($attempt, false, $attemptquestions[$attempt->uniqueid]);
        }

        $this->update_overall_grades($quiz);
    }

    /**
     * Count the number of attempts in need of a regrade.
     * @param object $quiz the quiz settings.
     * @param array $groupstudents user ids. If this is given, only data relating
     * to these users is cleared.
     */
    protected function count_question_attempts_needing_regrade($quiz, $groupstudents) {
        global $DB;

        $usertest = '';
        $params = array();
        if ($groupstudents) {
            list($usql, $params) = $DB->get_in_or_equal($groupstudents);
            $usertest = "quiza.userid $usql AND ";
        }

        $params[] = $quiz->id;
        $sql = "SELECT COUNT(DISTINCT quiza.id)
                FROM {quiz_attempts} quiza
                JOIN {quiz_overview_regrades} qqr ON quiza.uniqueid = qqr.questionusageid
                WHERE
                    $usertest
                    quiza.quiz = ? AND
                    quiza.preview = 0 AND
                    qqr.regraded = 0";
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Are there any pending regrades in the table we are going to show?
     * @param string $from tables used by the main query.
     * @param string $where where clause used by the main query.
     * @param array $params required by the SQL.
     * @return bool whether there are pending regrades.
     */
    protected function has_regraded_questions($from, $where, $params) {
        global $DB;
        $qubaids = new qubaid_join($from, 'uniqueid', $where, $params);
        return $DB->record_exists_select('quiz_overview_regrades',
                'questionusageid ' . $qubaids->usage_id_in(),
                $qubaids->usage_id_in_params());
    }

    /**
     * Remove all information about pending/complete regrades from the database.
     * @param object $quiz the quiz settings.
     * @param array $groupstudents user ids. If this is given, only data relating
     * to these users is cleared.
     */
    protected function clear_regrade_table($quiz, $groupstudents) {
        global $DB;

        // Fetch all attempts that need regrading.
        $where = '';
        $params = array();
        if ($groupstudents) {
            list($usql, $params) = $DB->get_in_or_equal($groupstudents);
            $where = "userid $usql AND ";
        }

        $params[] = $quiz->id;
        $DB->delete_records_select('quiz_overview_regrades',
                "questionusageid IN (
                    SELECT uniqueid
                    FROM {quiz_attempts}
                    WHERE $where quiz = ?
                )", $params);
    }

    /**
     * Update the final grades for all attempts. This method is used following
     * a regrade.
     * @param object $quiz the quiz settings.
     * @param array $userids only update scores for these userids.
     * @param array $attemptids attemptids only update scores for these attempt ids.
     */
    protected function update_overall_grades($quiz) {
        quiz_update_all_attempt_sumgrades($quiz);
        quiz_update_all_final_grades($quiz);
        quiz_update_grades($quiz);
    }
    
    /**
     * Get the current group for the user user looking at the report.
     *
     * @param object $cm the course_module information.
     * @param object $coures the course settings.
     * @param context $context the quiz context.
     * @return int the current group id, if applicable. 0 for all users,
     *      NO_GROUPS_ALLOWED if the user cannot see any group.
     */
    public function get_current_group($cm, $course, $context) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        $currentgroup = groups_get_activity_group($cm, true);

        if ($groupmode == SEPARATEGROUPS && !$currentgroup && !has_capability('moodle/site:accessallgroups', $context)) {
            $currentgroup = self::NO_GROUPS_ALLOWED;
        }

        return $currentgroup;
    }
}
