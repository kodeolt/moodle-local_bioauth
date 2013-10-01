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
 * This script processes events logged from the biologger module.
 *
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/bioauth/locallib.php');

$timenow = time();

$userid = optional_param('userid', 0, PARAM_INT);

if (empty($userid)) {
    global $DB;
    $username = required_param('username', PARAM_USERNAME);
    $userid = $DB->get_field('user', 'id', array('username' => $username));
}

if (bioauth_confirm_sesskey($userid)) {
    bioauth_enroll_data($userid, $timenow);
    echo 'Data received for '.$username;
} else {
    echo 'Unable to authenticate '.$username;
}
