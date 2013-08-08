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

require_once($CFG->dirroot . '/local/bioauth/lib.php');
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
class bioauth_quiz_report {
        
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
        
        $this->process_actions($course, $options->get_url());

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
    
    protected function process_actions($course, $redirecturl) {

        if (optional_param('reauthenticate', 0, PARAM_BOOL) && confirm_sesskey()) {
            $this->reauthenticate($course);
            redirect($redirecturl, '', 5);
        }
    }

/**
     * Regrade attempts for this quiz, exactly which attempts are regraded is
     * controlled by the parameters.
     * @param object $quiz the quiz settings.
     */
    protected function reauthenticate($course) {
        global $DB;
        $this->unlock_session();
    
        run_validation($course);
    }
}




/*
 * Quiz report subclass for the overview (grades) report.
 *
 * @copyright 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bioauth_report_overview extends bioauth_report {
     
    /**
     * The final grades.
     * @var array $grades
     */
    public $courses;

//// SQL-RELATED

    /**
     * The id of the grade_item by which this report will be sorted.
     * @var int $sortitemid
     */
    public $sortitemid;

    /**
     * Sortorder used in the SQL selections.
     * @var int $sortorder
     */
    public $sortorder;

    /**
     * A count of the rows, used for css classes.
     * @var int $rowcount
     */
    public $rowcount = 0;

    
    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     * @param int $sortitemid The id of the grade_item by which to sort the table
     */
    public function __construct($context, $page) {
        global $CFG;
        parent::__construct($context, $page, $sortitemid=null);

        $this->sortitemid = $sortitemid;

        // base url for sorting
        $this->baseurl = new moodle_url('index.php');

        $this->pbarurl = new moodle_url('/local/bioauth/report/index.php');
        
        $this->setup_sortitemid();
    }

        /**
     * Setting the sort order, this depends on last state
     * all this should be in the new table class that we might need to use
     * for displaying grades.
     */
    private function setup_sortitemid() {

        global $SESSION;

        if (!isset($SESSION->bioauthcoursereport)) {
            $SESSION->bioauthcoursereport = new stdClass();
        }

        if ($this->sortitemid) {
            if (!isset($SESSION->bioauthcoursereport->sort)) {
                if ($this->sortitemid == 'firstname' || $this->sortitemid == 'lastname') {
                    $this->sortorder = $SESSION->bioauthcoursereport->sort = 'ASC';
                } else {
                    $this->sortorder = $SESSION->bioauthcoursereport->sort = 'DESC';
                }
            } else {
                // this is the first sort, i.e. by last name
                if (!isset($SESSION->bioauthcoursereport->sortitemid)) {
                    if ($this->sortitemid == 'firstname' || $this->sortitemid == 'lastname') {
                        $this->sortorder = $SESSION->bioauthcoursereport->sort = 'ASC';
                    } else {
                        $this->sortorder = $SESSION->bioauthcoursereport->sort = 'DESC';
                    }
                } else if ($SESSION->bioauthcoursereport->sortitemid == $this->sortitemid) {
                    // same as last sort
                    if ($SESSION->bioauthcoursereport->sort == 'ASC') {
                        $this->sortorder = $SESSION->bioauthcoursereport->sort = 'DESC';
                    } else {
                        $this->sortorder = $SESSION->bioauthcoursereport->sort = 'ASC';
                    }
                } else {
                    if ($this->sortitemid == 'firstname' || $this->sortitemid == 'lastname') {
                        $this->sortorder = $SESSION->bioauthcoursereport->sort = 'ASC';
                    } else {
                        $this->sortorder = $SESSION->bioauthcoursereport->sort = 'DESC';
                    }
                }
            }
            $SESSION->bioauthcoursereport->sortitemid = $this->sortitemid;
        } else {
            // not requesting sort, use last setting (for paging)

            if (isset($SESSION->bioauthcoursereport->sortitemid)) {
                $this->sortitemid = $SESSION->gradeuserreport->sortitemid;
            }else{
                $this->sortitemid = 'lastname';
            }

            if (isset($SESSION->bioauthcoursereport->sort)) {
                $this->sortorder = $SESSION->gradeuserreport->sort;
            } else {
                $this->sortorder = 'ASC';
            }
        }
    }

    /**
     * Get information about which students to show in the report.
     * @return an array 
     */
    public function load_course_validations() {
        
        $enrolcourses = enrol_get_my_courses();
        $viewgradecourses = array();
        foreach ($enrolcourses as $course) {
            $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
            if (has_capability('moodle/grade:viewall', $coursecontext)) {
                $viewgradecourses[] = $course;
            }
        }
        
        $this->courses = $viewgradecourses;
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

        $statusheader = new html_table_cell();
        $statusheader->attributes['class'] = 'header';
        $statusheader->scope = 'col';
        $statusheader->header = true;
        $statusheader->id = 'statusheader';
        $statusheader->text = get_string('status', 'local_bioauth');

        $headerrow->cells[] = $statusheader;
        
        $courseheader = new html_table_cell();
        $courseheader->attributes['class'] = 'header';
        $courseheader->scope = 'col';
        $courseheader->header = true;
        $courseheader->id = 'courseheader';
        $courseheader->text = $arrows['coursename'];

        $headerrow->cells[] = $courseheader;
        
        $rows[] = $headerrow;
        $rowclasses = array('even', 'odd');
        
        foreach ($this->courses as $courseid => $course) {
            $courserow = new html_table_row();
            $courserow->id = 'fixed_course_'.$courseid;
            $courserow->attributes['class'] = 'r'.$this->rowcount++.' '.$rowclasses[$this->rowcount % 2];

            $statuscell = new html_table_cell();
            $statuscell->attributes['class'] = 'course';
            $statuscell->header = true;
            $statuscell->scope = 'row';
            $action = $course->bioauthenabled ? 'disable' : 'enable';
            $statuscell->text .= html_writer::link(new moodle_url($this->pbarurl, array('action' => $action, 'target' => $course->id, 'sesskey' => sesskey())), get_string($action, 'local_bioauth'));
            $courserow->cells[] = $statuscell;
            
            $coursecell = new html_table_cell();
            $coursecell->attributes['class'] = 'course';
            $coursecell->header = true;
            $coursecell->scope = 'row';
            $coursecell->text .= html_writer::link(new moodle_url('/local/bioauth/report/quiz.php', array('id' => $course->id)), $course->shortname);
            $courserow->cells[] = $coursecell;

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

        $strsortasc   = $this->get_lang_string('sortasc', 'local_bioauth');
        $strsortdesc  = $this->get_lang_string('sortdesc', 'local_grades');
        $strcoursename = $this->get_lang_string('course');
        $iconasc = $OUTPUT->pix_icon('t/sort_asc', $strsortasc, '', array('class' => 'iconsmall sorticon'));
        $icondesc = $OUTPUT->pix_icon('t/sort_desc', $strsortdesc, '', array('class' => 'iconsmall sorticon'));

        $coursenamelink = html_writer::link(new moodle_url($this->baseurl, array('sortitemid'=>'coursename')), $strcoursename);

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
            case 'enable':
                bioauth_enable_course($target);
                break;
                
            case 'disable':
                bioauth_disable_course($target);
                break;
                
            default:
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
                    $rowsperpage = $maxinputvars - 1; // Subtract one to be on the safe side
                    if ($rowsperpage<1) {
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

/**
 * An abstract class containing variables and methods used by all or most reports.
 * @package core_grades
 */
abstract class bioauth_report {

    /**
     * The context.
     * @var int $context
     */
    public $context;

    /**
     * User preferences related to this report.
     * @var array $prefs
     */
    public $prefs = array();
    /**
     * base url for sorting by first/last name.
     * @var string $baseurl
     */
    public $baseurl;

    /**
     * base url for paging.
     * @var string $pbarurl
     */
    public $pbarurl;

    /**
     * Current page (for paging).
     * @var int $page
     */
    public $page;

    /**
     * Array of cached language strings (using get_string() all the time takes a long time!).
     * @var array $lang_strings
     */
    public $lang_strings = array();

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     */
    public function __construct($context, $page=null) {
        global $CFG, $COURSE, $DB;
        $this->context   = $context;
        $this->page      = $page;
    }

    /**
     * Handles form data sent by this report for this report. Abstract method to implement in all children.
     * @abstract
     * @param array $data
     * @return mixed True or array of errors
     */
    abstract function process_data($data);

    /**
     * Processes a single action against a category, grade_item or grade.
     * @param string $target Sortorder
     * @param string $action Which action to take (edit, delete etc...)
     * @return
     */
    abstract function process_action($target, $action);

    /**
     * Fetches and returns a count of all the users that will be shown on this page.
     * @param boolean $groups include groups limit
     * @return int Count of users
     */
    abstract public function get_numrows();
    
    /**
     * First checks the cached language strings, then returns match if found, or uses get_string()
     * to get it from the DB, caches it then returns it.
     * @param string $strcode
     * @param string $section Optional language section
     * @return string
     */
    public function get_lang_string($strcode, $section=null) {
        if (empty($this->lang_strings[$strcode])) {
            $this->lang_strings[$strcode] = get_string($strcode, $section);
        }
        return $this->lang_strings[$strcode];
    }

    /**
     * Returns an arrow icon inside an <a> tag, for the purpose of sorting a column.
     * @param string $direction
     * @param moodle_url $sort_link
     * @param string HTML
     */
    protected function get_sort_arrow($direction='move', $sortlink=null) {
        global $OUTPUT;
        $pix = array('up' => 't/sort_desc', 'down' => 't/sort_asc', 'move' => 't/sort');
        $matrix = array('up' => 'desc', 'down' => 'asc', 'move' => 'desc');
        $strsort = $this->get_lang_string('sort' . $matrix[$direction]);

        $arrow = $OUTPUT->pix_icon($pix[$direction], $strsort, '', array('class' => 'sorticon'));
        return html_writer::link($sortlink, $arrow, array('title'=>$strsort));
    }
    
    /**
     * Given the name of a user preference (without grade_report_ prefix), locally saves then returns
     * the value of that preference. If the preference has already been fetched before,
     * the saved value is returned. If the preference is not set at the User level, the $CFG equivalent
     * is given (site default).
     * @static (Can be called statically, but then doesn't benefit from caching)
     * @param string $pref The name of the preference (do not include the grade_report_ prefix)
     * @param int $objectid An optional itemid or categoryid to check for a more fine-grained preference
     * @return mixed The value of the preference
     */
    public function get_pref($pref, $objectid=null) {
        global $CFG;
        $fullprefname = 'bioauth_report_' . $pref;
        $shortprefname = 'bioauth_' . $pref;

        $retval = null;

        if (!isset($this) OR get_class($this) != 'grade_report') {
            if (!empty($objectid)) {
                $retval = get_user_preferences($fullprefname . $objectid, grade_report::get_pref($pref));
            } elseif (isset($CFG->$fullprefname)) {
                $retval = get_user_preferences($fullprefname, $CFG->$fullprefname);
            } elseif (isset($CFG->$shortprefname)) {
                $retval = get_user_preferences($fullprefname, $CFG->$shortprefname);
            } else {
                $retval = null;
            }
        } else {
            if (empty($this->prefs[$pref.$objectid])) {

                if (!empty($objectid)) {
                    $retval = get_user_preferences($fullprefname . $objectid);
                    if (empty($retval)) {
                        // No item pref found, we are returning the global preference
                        $retval = $this->get_pref($pref);
                        $objectid = null;
                    }
                } else {
                    $retval = get_user_preferences($fullprefname, $CFG->$fullprefname);
                }
                $this->prefs[$pref.$objectid] = $retval;
            } else {
                $retval = $this->prefs[$pref.$objectid];
            }
        }

        return $retval;
    }

    /**
     * Uses set_user_preferences() to update the value of a user preference. If 'default' is given as the value,
     * the preference will be removed in favour of a higher-level preference.
     * @static
     * @param string $pref_name The name of the preference.
     * @param mixed $pref_value The value of the preference.
     * @param int $itemid An optional itemid to which the preference will be assigned
     * @return bool Success or failure.
     */
    public function set_pref($pref, $pref_value='default', $itemid=null) {
        $fullprefname = 'bioauth_report_' . $pref;
        if ($pref_value == 'default') {
            return unset_user_preference($fullprefname.$itemid);
        } else {
            return set_user_preference($fullprefname.$itemid, $pref_value);
        }
    }
}


