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
require_once($CFG->libdir .'/tablelib.php');

require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

require_once($CFG->dirroot . '/local/bioauth/locallib.php');
require_once($CFG->dirroot . '/local/bioauth/HighRoller/HighRoller.php');
require_once($CFG->dirroot . '/local/bioauth/HighRoller/HighRollerSeriesData.php');
require_once($CFG->dirroot . '/local/bioauth/HighRoller/HighRollerLineChart.php');

/**
 * Base class for the settings form for {@link bioauth_report}s.
 *
 * @copyright Vinnie Monaco
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_bioauth_report_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'preferencesuser',
                get_string('reportoptions', 'bioauth'));

        $this->preference_fields($mform);

        $mform->addElement('submit', 'submitbutton',
                get_string('showreport', 'bioauth'));
    }

    protected function preference_fields(MoodleQuickForm $mform) {
        $mform->addElement('text', 'pagesize', get_string('pagesize', 'bioauth'));
        $mform->setType('pagesize', PARAM_INT);
    }


    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        return $errors;
    }
}

/**
 * Class to store the options for a {@link bioauth_report}.
 *
 * @copyright Vinnie Monaco
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bioauth_report_options {

    /** @var object the settings for the quiz being reported on. */
    public $validation;

    /** @var object the course module objects for the quiz being reported on. */
    public $cm;

    /** @var object the course settings for the course the quiz is in. */
    public $course;

    /** @var int Number of attempts to show per page. */
    public $pagesize = bioauth_report::DEFAULT_PAGE_SIZE;

    /** @var string whether the data should be downloaded in some format, or '' to display it. */
    public $download = '';

    /**
     * Constructor.
     * @param object $validation the settings for the quiz being reported on.
     * @param object $cm the course module objects for the quiz being reported on.
     * @param object $course the course settings for the coures this quiz is in.
     */
    public function __construct($validation, $cm, $course) {
        $this->validation   = $validation;
        $this->cm           = $cm;
        $this->course       = $course;
    }

    /**
     * Get the URL parameters required to show the report with these options.
     * @return array URL parameter name => value.
     */
    protected function get_url_params() {
        $params = array(
            'id'         => $this->cm->id,
        );
        if (groups_get_activity_groupmode($this->cm, $this->course)) {
            $params['group'] = $this->group;
        }
        return $params;
    }

    /**
     * Get the URL to show the report with these options.
     * @return moodle_url the URL.
     */
    public function get_url() {
        return new moodle_url('/local/bioauth/report.php', $this->get_url_params());
    }

    /**
     * Process the data we get when the settings form is submitted. This includes
     * updating the fields of this class, and updating the user preferences
     * where appropriate.
     * @param object $fromform The data from $mform->get_data() from the settings form.
     */
    public function process_settings_from_form($fromform) {
        $this->setup_from_form_data($fromform);
        $this->resolve_dependencies();
        $this->update_user_preferences();
    }

    /**
     * Set up this preferences object using optional_param (using user_preferences
     * to set anything not specified by the params.
     */
    public function process_settings_from_params() {
        $this->setup_from_user_preferences();
        $this->setup_from_params();
        $this->resolve_dependencies();
    }

    /**
     * Get the current value of the settings to pass to the settings form.
     */
    public function get_initial_form_data() {
        $toform = new stdClass();
        $toform->pagesize   = $this->pagesize;
        
        return $toform;
    }

    /**
     * Set the fields of this object from the form data.
     * @param object $fromform The data from $mform->get_data() from the settings form.
     */
    public function setup_from_form_data($fromform) {
        // $this->attempts   = $fromform->attempts;
        // $this->group      = groups_get_activity_group($this->cm, true);
        // $this->onlygraded = !empty($fromform->onlygraded);
        $this->pagesize   = $fromform->pagesize;
    }

    /**
     * Set the fields of this object from the user's preferences.
     */
    public function setup_from_params() {
        // $this->attempts   = optional_param('attempts', $this->attempts, PARAM_ALPHAEXT);
        // $this->group      = groups_get_activity_group($this->cm, true);
        // $this->onlygraded = optional_param('onlygraded', $this->onlygraded, PARAM_BOOL);
        $this->pagesize   = optional_param('pagesize', $this->pagesize, PARAM_INT);

        // $this->download   = optional_param('download', $this->download, PARAM_ALPHA);
    }

    /**
     * Set the fields of this object from the user's preferences.
     * (For those settings that are backed by user-preferences).
     */
    public function setup_from_user_preferences() {
        $this->pagesize = get_user_preferences('bioauth_report_pagesize', $this->pagesize);
    }

    /**
     * Update the user preferences so they match the settings in this object.
     * (For those settings that are backed by user-preferences).
     */
    public function update_user_preferences() {
        set_user_preference('bioauth_report_pagesize', $this->pagesize);
    }

    /**
     * Check the settings, and remove any 'impossible' combinations.
     */
    public function resolve_dependencies() {
        
        if ($this->pagesize < 1) {
            $this->pagesize = bioauth_report::DEFAULT_PAGE_SIZE;
        }
    }
}

/**
 * 
 *
 * @copyright 
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


    /** @var context the quiz context. */
    protected $context;

    /** @var object mod_quiz_attempts_report_options the options affecting this report. */
    protected $options;

    /** @var object the ids of the students in the currently selected group, if applicable. */
    protected $groupstudents;

    /** @var object the ids of the students in the course. */
    protected $students;

    /** @var quizzes that that are in this course */
    protected $quizzes;
    
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
    public function __construct($uniqueid, $context, $groupstudents, $students, $quizauths, $reporturl, $m) {
        parent::__construct($uniqueid);
        $this->context = $context;
        $this->groupstudents = $groupstudents;
        $this->students = $students;
        $this->quizauths = $quizauths;
        $this->reporturl = $reporturl;
        $this->m = $m;
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
        
        return $html;
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
    public function make_decision_output($decision, $attempt, $slot) {
        global $OUTPUT;
        
        $decisionclass = 'w' === $decision ? 'correct' : 'incorrect';
        $img = $OUTPUT->pix_icon('i/grade_' . $decisionclass, get_string($decisionclass, 'question'),
                                'moodle', array('class' => 'icon'));
                
        $output = html_writer::tag('span', $img);
        
        return $output;
    }

    /**
     * @param string $colname the name of the column.
     * @param object $attempt the row of data - see the SQL in display() in
     * mod/quiz/report/overview/report.php to see what fields are present,
     * and what they are called.
     * @return string the contents of the cell.
     */
    public function other_cols($colname, $attempt) {
        if (!preg_match('/^qsquiz(\d+)$/', $colname, $matches)) {
            return null;
        }
        $slot = $matches[1];
        
        if (array_key_exists($attempt->userid, $this->quizauths) && array_key_exists($slot, $this->quizauths[$attempt->userid])) {
            $neighbors = $this->quizauths[$attempt->userid][$slot];
            $decisions = explode(",", $neighbors);
            return $this->make_decision_output($decisions[$this->m], $attempt, $slot);
            
        } else {
            return '-';
            
        }
    }
    
    /**
     * Contruct all the parts of the main database query.
     * @param array $reportstudents list if userids of users to include in the report.
     * @return array with 4 elements ($fields, $from, $where, $params) that can be used to
     *      build the actual database query.
     */
    public function base_sql($reportstudents) {
        global $DB;

        $extrafields = get_extra_user_fields_sql($this->context, 'u', '',
                array('id', 'idnumber', 'firstname', 'lastname', 'picture',
                'imagealt', 'institution', 'department', 'email'));
                
        $fields = '
                u.id AS userid,
                u.idnumber,
                u.firstname,
                u.lastname,
                u.picture,
                u.imagealt,
                u.institution,
                u.department,
                u.email' . $extrafields;
                
        $from = "\n{user} u";

        list($usql, $uparams) = $DB->get_in_or_equal(
                $reportstudents, SQL_PARAMS_NAMED, 'u');
        $params = $uparams;
        $where = "u.id $usql";

        return array($fields, $from, $where, $params);
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

    /**
     *  Initialise various aspects of this report.
     *
     * @param string $mode
     * @param string $formclass
     * @param object $quiz
     * @param object $cm
     * @param object $course
     */
    protected function init($cm, $course) {
        
        $this->context = context_module::instance($cm->id);
        $this->usercanseegrades = true; //quiz_report_should_show_grades($quiz, context_module::instance($cm->id));
        
        list($currentgroup, $students, $groupstudents, $allowed) =
                $this->load_relevant_students($cm, $course);

        $quizzes = $this->load_relevant_quizzes($course);
        $quizauths = $this->load_relevant_quizauths($course);
        
        $validation = $this->load_validation($course);
        
        $this->form = new local_bioauth_report_form($this->get_base_url(),
                array('course' => $course,
                'currentgroup' => $currentgroup, 'context' => $this->context));
        
        return array($currentgroup, $students, $groupstudents, $allowed, $quizzes, $quizauths, $validation);
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
     * 
     */
    public function load_relevant_quizzes($course) {
        global $DB;
        return $DB->get_records('quiz', array('course' => $course->id));
    }
    
    public function load_relevant_quizauths($course) {
        global $DB;
        
        $records = $DB->get_records('bioauth_quiz_neighbors', array('courseid' => $course->id));
        $quizauths = array();
        foreach ($records as $record) {
            $quizauths[$record->userid][$record->quizid] = $record->neighbors;
        }
        
        return $quizauths;
    }
    
    public function load_validation($course) {
        global $DB;
        return $DB->get_record('bioauth_quiz_validations', array('courseid' => $course->id));
    }

    /**
     * Add all the user-related columns to the $columns and $headers arrays.
     * @param table_sql $table the table being constructed.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_user_columns($table, &$columns, &$headers) {
        $columns[] = 'picture';
        $headers[] = '';
   
        $columns[] = 'fullname';
        $headers[] = get_string('name');
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

        $table->set_attribute('id', 'attempts');

        $table->collapsible($collapsible);
    }

    
    /**
     * Initialise some parts of $PAGE and start output.
     *
     * @param object $cm the course_module information.
     * @param object $coures the course settings.
     * @param object $quiz the quiz settings.
     * @param string $reportmode the report name.
     */
    public function print_header_and_tabs($cm, $course) {
        global $PAGE, $OUTPUT;

        // Print the page header.
        $PAGE->set_title(get_string('pluginname', 'local_bioauth'));
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
    }
    
    public function display_graph($frr, $far) {
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
        
        echo '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>';
        echo "<script type='text/javascript' src='highcharts/highcharts.js'></script>";
        echo '<div id="linechart"></div><script type="text/javascript">'.$linechart->renderChart().'</script>';
    }

    public function display($cm, $course) {
        global $CFG, $DB, $OUTPUT, $PAGE;

        list($currentgroup, $students, $groupstudents, $allowed, $quizzes, $quizauths, $validation) =
                $this->init($cm, $course);
                
        $options = new bioauth_report_options($validation, $cm, $course);

        if ($fromform = $this->form->get_data()) {
            $options->process_settings_from_form($fromform);

        } else {
            $options->process_settings_from_params();
        }

        $this->form->set_data($options->get_initial_form_data());

        // Prepare for downloading, if applicable.
        $courseshortname = format_string($course->shortname, true,
                array('context' => context_course::instance($course->id)));
        $table = new bioauth_report_table('local-bioauth-report', $this->context, $groupstudents, $students, $quizauths, $this->get_base_url(), $validation->m);
        $this->print_header_and_tabs($cm, $course);

        if ($groupmode = groups_get_activity_groupmode($cm)) {
            // Groups are being used, so output the group selector if we are not downloading.
            groups_print_activity_menu($cm, $options->get_url());
        }

        if (!$students) {
            echo $OUTPUT->notification(get_string('nostudentsyet'));
        } else if ($currentgroup && !$groupstudents) {
            echo $OUTPUT->notification(get_string('nostudentsingroup'));
        }
        
        if ($strperformance = bioauth_performance_summary($validation, $course)) {
            echo '<div class="performancesummary">' . $strperformance . '</div>';
        }
        
        $this->form->display();
        
        $hasquizzes = !empty($quizzes);
        $hasstudents = $students && (!$currentgroup || $groupstudents);
        if ($hasquizzes && $hasstudents) {
            // Construct the SQL.

            list($fields, $from, $where, $params) = $table->base_sql($allowed);

            $table->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);

            $table->set_sql($fields, $from, $where, $params);

            // Output the authenticate button.
            $authenticatelabel = 'Re-authenticate Students';
            $displayurl = new moodle_url($this->get_base_url(), array('sesskey' => sesskey()));
            echo '<div class="mdl-align">';
            echo '<form action="'.$displayurl->out_omit_querystring().'">';
            echo '<div>';
            echo html_writer::input_hidden_params($displayurl);
            echo '<input type="submit" name="reauthenticate" value="'.$authenticatelabel.'"/>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            
            $frrstring = explode(',', $validation->frr);
        $farstring = explode(',', $validation->far);
        
        $frr = array();
        $far = array();
        foreach (array_keys($frrstring) as $m) {
            $frr[] = (float)$frrstring[$m];
            $far[] = (float)$farstring[$m];
        }
        
        $this->display_graph($frr, $far);
            
            // Define table columns.
            $columns = array();
            $headers = array();

            $this->add_user_columns($table, $columns, $headers);

            foreach ($quizzes as $slot => $quiz) {
                // Ignore questions of zero length.
                $columns[] = 'qsquiz' . $slot;
                $headers[] = $quiz->name;
            }
            

            $this->set_up_table_columns($table, $columns, $headers, $this->get_base_url(), false);
            $table->set_attribute('class', 'generaltable generalbox grades');

            $table->out(bioauth_report::DEFAULT_PAGE_SIZE, true);
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
    
    // protected function process_actions($quiz, $cm, $currentgroup, $groupstudents, $allowed, $redirecturl) {
        // parent::process_actions($quiz, $cm, $currentgroup, $groupstudents, $allowed, $redirecturl);
// 
        // if (empty($currentgroup) || $groupstudents) {
            // if (optional_param('regrade', 0, PARAM_BOOL) && confirm_sesskey()) {
                // if ($attemptids = optional_param_array('attemptid', array(), PARAM_INT)) {
                    // require_capability('mod/quiz:regrade', $this->context);
                    // $this->regrade_attempts($quiz, false, $groupstudents, $attemptids);
                    // redirect($redirecturl, '', 5);
                // }
            // }
        // }
// 
        // if (optional_param('regradeall', 0, PARAM_BOOL) && confirm_sesskey()) {
            // require_capability('mod/quiz:regrade', $this->context);
            // $this->regrade_attempts($quiz, false, $groupstudents);
            // redirect($redirecturl, '', 5);
// 
        // } else if (optional_param('regradealldry', 0, PARAM_BOOL) && confirm_sesskey()) {
            // require_capability('mod/quiz:regrade', $this->context);
            // $this->regrade_attempts($quiz, true, $groupstudents);
            // redirect($redirecturl, '', 5);
// 
        // } else if (optional_param('regradealldrydo', 0, PARAM_BOOL) && confirm_sesskey()) {
            // require_capability('mod/quiz:regrade', $this->context);
            // $this->regrade_attempts_needing_it($quiz, $groupstudents);
            // redirect($redirecturl, '', 5);
        // }
    // }
// 
// /**
     // * Regrade attempts for this quiz, exactly which attempts are regraded is
     // * controlled by the parameters.
     // * @param object $quiz the quiz settings.
     // * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     // * @param array $groupstudents blank for all attempts, otherwise regrade attempts
     // * for these users.
     // * @param array $attemptids blank for all attempts, otherwise only regrade
     // * attempts whose id is in this list.
     // */
    // protected function regrade_attempts($quiz, $dryrun = false,
            // $groupstudents = array(), $attemptids = array()) {
        // global $DB;
        // $this->unlock_session();
// 
        // $where = "quiz = ? AND preview = 0";
        // $params = array($quiz->id);
// 
        // if ($groupstudents) {
            // list($usql, $uparams) = $DB->get_in_or_equal($groupstudents);
            // $where .= " AND userid $usql";
            // $params = array_merge($params, $uparams);
        // }
// 
        // if ($attemptids) {
            // list($asql, $aparams) = $DB->get_in_or_equal($attemptids);
            // $where .= " AND id $asql";
            // $params = array_merge($params, $aparams);
        // }
// 
        // $attempts = $DB->get_records_select('quiz_attempts', $where, $params);
        // if (!$attempts) {
            // return;
        // }
// 
        // $this->clear_regrade_table($quiz, $groupstudents);
// 
        // foreach ($attempts as $attempt) {
            // $this->regrade_attempt($attempt, $dryrun);
        // }
// 
        // if (!$dryrun) {
            // $this->update_overall_grades($quiz);
        // }
    // }
}
