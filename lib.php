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
 * Create navigation links in the left sidebar for easy access to settings and
 * course reports. 
 *
 * @param object $navigation the navigation object
 */
function local_bioauth_extends_navigation(global_navigation $navigation) {
    if (BIOAUTH_MODE_ENABLED == get_config('local_bioauth', 'mode')) {
        launch_biologger_js();
    }

    // global $USER;
    // $context = context_user::instance($USER->id);
    // $bioauthnode = $navigation->add(get_string('pluginname', 'local_bioauth'));
    // $reportnode = $bioauthnode->add(get_string('report', 'local_bioauth'), new moodle_url('/local/bioauth/report/index.php'));
    // $launch = $bioauthnode->add(get_string('launch', 'local_bioauth'), new moodle_url('/local/bioauth/launch/bbl.php'));
    // $data = $bioauthnode->add(get_string('mydata', 'local_bioauth'), new moodle_url('/local/bioauth/biodata/index.php'));
    // TODO: create settings for each individual course, accessable from the report overview page.
    // $settingsnode = $bioauthnode->add(get_string('settings', 'local_bioauth'), new moodle_url('/admin/settings.php', array('section' => 'local_bioauth')));
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
