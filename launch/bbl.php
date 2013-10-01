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
require_once($CFG->dirroot . '/local/bioauth/launch/bblform.php');

$PAGE->set_url(new moodle_url('/local/bioauth/launch/bbl.php'));

$launchurl = new moodle_url('/local/bioauth/launch/launchbbl.php');

require_login();
$context = context_user::instance($USER->id);
$PAGE->set_context($context);

print_bioauth_page_head('bbl', 'General Purpose Logger');

bioauth_save_sesskey($USER->id);

$mform = new bioauth_bbl_form($launchurl);
$mform->display();

echo $OUTPUT->footer();
