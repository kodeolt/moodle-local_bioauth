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
     * The roles for this report.
     * @var string $gradebookroles
     */
    public $gradebookroles;
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
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     */
    public function __construct($context, $page = null, $sortitemid = null) {
        global $CFG, $COURSE, $DB;
        $this->context = $context;
        $this->page = $page;
        $this->gradebookroles = $CFG->gradebookroles;
        $this->sortitemid = $sortitemid;
    }

    /**
     * Processes a single action against a category, grade_item or grade.
     * @param string $target Sortorder
     * @param string $action Which action to take (edit, delete etc...)
     * @return
     */
    abstract public function process_action($target, $action);

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
    public function get_lang_string($strcode, $section = null) {
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
    protected function get_sort_arrow($direction = 'move', $sortlink = null) {
        global $OUTPUT;
        $pix = array('up' => 't/sort_desc', 'down' => 't/sort_asc', 'move' => 't/sort');
        $matrix = array('up' => 'desc', 'down' => 'asc', 'move' => 'desc');
        $strsort = $this->get_lang_string('sort' . $matrix[$direction]);

        $arrow = $OUTPUT->pix_icon($pix[$direction], $strsort, '', array('class' => 'sorticon'));
        return html_writer::link($sortlink, $arrow, array('title' => $strsort));
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
    public function get_pref($pref, $objectid = null) {
        global $CFG;
        $fullprefname = 'bioauth_report_' . $pref;
        $shortprefname = 'bioauth_' . $pref;

        $retval = null;

        if (!isset($this) OR get_class($this) != 'grade_report') {
            if (!empty($objectid)) {
                $retval = get_user_preferences($fullprefname . $objectid, grade_report::get_pref($pref));
            } else if (isset($CFG->$fullprefname)) {
                $retval = get_user_preferences($fullprefname, $CFG->$fullprefname);
            } else if (isset($CFG->$shortprefname)) {
                $retval = get_user_preferences($fullprefname, $CFG->$shortprefname);
            } else {
                $retval = null;
            }
        } else {
            if (empty($this->prefs[$pref . $objectid])) {

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
                $this->prefs[$pref . $objectid] = $retval;
            } else {
                $retval = $this->prefs[$pref . $objectid];
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
    public function set_pref($pref, $pref_value = 'default', $itemid = null) {
        $fullprefname = 'bioauth_report_' . $pref;
        if ($pref_value == 'default') {
            return unset_user_preference($fullprefname . $itemid);
        } else {
            return set_user_preference($fullprefname . $itemid, $pref_value);
        }
    }

    /**
     * Setting the sort order, this depends on last state.
     */
    protected function setup_sortitemid() {

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
            } else {
                $this->sortitemid = 'lastname';
            }

            if (isset($SESSION->bioauthcoursereport->sort)) {
                $this->sortorder = $SESSION->gradeuserreport->sort;
            } else {
                $this->sortorder = 'ASC';
            }
        }
    }
}
