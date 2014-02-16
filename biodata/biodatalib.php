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
 * 
 * 
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/tablelib.php');

require_once($CFG->dirroot . '/local/bioauth/locallib.php');
require_once($CFG->dirroot . '/local/bioauth/report/reportlib.php');

/*
 * Overview report subclass for the bioauth overview report.
 */
class bioauth_biodata_overview {

    /**
     * The data this user has access to.
     * @var array $courses
     */
    public $biodata;
    
    public $users;
    
    /**
     * A count of the rows, used for css classes.
     * @var int $rowcount
     */
    public $rowcount = 0;
    
    public $numrows = 0;
    
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
        $this->context = $context;
        $this->page = $page;
        $this->sortitemid = $sortitemid;
        
        $this->baseurl = new moodle_url('index.php');
        $this->pbarurl = new moodle_url('/local/bioauth/biodata/index.php');
    }

    /**
     * Get information about which students to show in the report.
     * @return an array
     */
    public function load_biodata() {
         global $DB, $USER;
        
        $sort = 'id';
        $fields = 'id,userid,task,tags,biometric,quantity,timemodified';
        $rowsperpage = $this->get_rows_per_page();

        $this->numrows = $biodata = $DB->count_records('bioauth_biodata', array('userid' => $USER->id)); 
       
        $biodata = $DB->get_records('bioauth_biodata', array('userid' => $USER->id), $sort, $fields, $rowsperpage * $this->page, $rowsperpage);
       $this->biodata = $biodata;
      
        return $this->biodata;
    }

    public function get_report_table() {
        global $CFG, $DB, $OUTPUT, $PAGE;

        if (!$this->biodata) {
            echo $OUTPUT->notification(get_string('nodatayet', 'local_bioauth'));
            return;
        }

        $html = '';

        $rows = $this->get_rows();

        $datatable = new html_table();
        $datatable->attributes['class'] = 'gradestable flexible boxaligncenter generaltable';
        $datatable->id = 'bioauth-biodata-overview';
        $datatable->data = $rows;
        $html .= html_writer::table($datatable);

        return $html;
    }

    public function get_rows() {
        global $CFG, $USER, $OUTPUT, $DB;

        $rows = array();
         
        $headerrow = new html_table_row();
        $headerrow->attributes['class'] = 'heading';

        $userheader = new html_table_cell();
        $userheader->attributes['class'] = 'header';
        $userheader->scope = 'col';
        $userheader->header = true;
        $userheader->id = 'userheader';
        $userheader->text = get_string('name');
        $headerrow->cells[] = $userheader;

        $taskheader = new html_table_cell();
        $taskheader->attributes['class'] = 'header';
        $taskheader->scope = 'col';
        $taskheader->header = true;
        $taskheader->id = 'taskheader';
        $taskheader->text = get_string('task', 'local_bioauth');
        $headerrow->cells[] = $taskheader;

        $tagsheader = new html_table_cell();
        $tagsheader->attributes['class'] = 'header';
        $tagsheader->scope = 'col';
        $tagsheader->header = true;
        $tagsheader->id = 'tagsheader';
        $tagsheader->text = get_string('tags', 'local_bioauth');
        $headerrow->cells[] = $tagsheader;

        $biometricheader = new html_table_cell();
        $biometricheader->attributes['class'] = 'header';
        $biometricheader->scope = 'col';
        $biometricheader->header = true;
        $biometricheader->id = 'biometricheader';
        $biometricheader->text = get_string('biometric', 'local_bioauth');
        $headerrow->cells[] = $biometricheader;

        $quantityheader = new html_table_cell();
        $quantityheader->attributes['class'] = 'header';
        $quantityheader->scope = 'col';
        $quantityheader->header = true;
        $quantityheader->id = 'quantityheader';
        $quantityheader->text = get_string('quantity', 'local_bioauth');
        $headerrow->cells[] = $quantityheader;

        $timemodifiedheader = new html_table_cell();
        $timemodifiedheader->attributes['class'] = 'header';
        $timemodifiedheader->scope = 'col';
        $timemodifiedheader->header = true;
        $timemodifiedheader->id = 'timemodifiedheader';
        $timemodifiedheader->text = get_string('timemodified', 'local_bioauth');
        $headerrow->cells[] = $timemodifiedheader;

        $rows[] = $headerrow;
        $rowclasses = array('even', 'odd');

        foreach ($this->biodata as $biodataid => $biodata) {
            
            $user = $DB->get_record('user', array('id' => $biodata->userid));
            
              $row = new html_table_row();
            $row->id = 'fixed_biodata_' . $biodataid;
            $row->attributes['class'] = 'r' . $this->rowcount++ . ' ' . $rowclasses[$this->rowcount % 2];

            $usercell = new html_table_cell();
            $usercell->attributes['class'] = 'user';

            $usercell->header = true;
            $usercell->scope = 'row';

            $usercell->text .= html_writer::link(new moodle_url('/user/view.php', array('id' => $user->id)), fullname($user));

            if (!empty($user->suspendedenrolment)) {
                $usercell->attributes['class'] .= ' usersuspended';

                // May be lots of suspended users so only get the string once
                if (empty($suspendedstring)) {
                    $suspendedstring = get_string('userenrolmentsuspended', 'grades');
                }
                $usercell->text .= html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/enrolmentsuspended'),
                                                            'title' => $suspendedstring, 'alt' => $suspendedstring, 'class' => 'usersuspendedicon'));
            }

            $row->cells[] = $usercell;
            
            $taskcell = new html_table_cell();
            $taskcell->attributes['class'] = 'task';
            $taskcell->header = true;
            $taskcell->scope = 'row';
            $taskcell->text .= $biodata->task;
            $row->cells[] = $taskcell;
            
            $tagscell = new html_table_cell();
            $tagscell->attributes['class'] = 'tags';
            $tagscell->header = true;
            $tagscell->scope = 'row';
            $tagscell->text .= strlen($biodata->tags) > 0 ? $biodata->tags : '-';
            $row->cells[] = $tagscell;
            
            $biometriccell = new html_table_cell();
            $biometriccell->attributes['class'] = 'biometric';
            $biometriccell->header = true;
            $biometriccell->scope = 'row';
            $biometriccell->text .= get_string($biodata->biometric, 'local_bioauth');
            $row->cells[] = $biometriccell;
            
            $quantitycell = new html_table_cell();
            $quantitycell->attributes['class'] = 'quantity';
            $quantitycell->header = true;
            $quantitycell->scope = 'row';
            $quantitycell->text .= $biodata->quantity;
            $row->cells[] = $quantitycell;

            $timemodifiedcell = new html_table_cell();
            $timemodifiedcell->attributes['class'] = 'timemodified';
            $timemodifiedcell->header = true;
            $timemodifiedcell->scope = 'row';
            $timemodifiedcell->text .= date('F j, Y, g:i a', $biodata->timemodified);
            $row->cells[] = $timemodifiedcell;
            
            $downloadcell = new html_table_cell();
            $downloadcell->attributes['class'] = 'download';
            $downloadcell->header = true;
            $downloadcell->scope = 'row';
            $downloadcell->text .= html_writer::link(new moodle_url('/local/bioauth/biodata/download.php', array('id' => $biodataid)), get_string('download'));
            $row->cells[] = $downloadcell;

            $rows[] = $row;
        }

        return $rows;
    }

    public function get_numrows() {
        return $this->numrows;
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
     * @return int The maximum number of students to display per page
     */
    public function get_rows_per_page() {
        return get_config('local_bioauth', 'numbiodatarows');
    }

}
