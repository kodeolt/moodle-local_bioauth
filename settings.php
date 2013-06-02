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
 * Administration settings definitions for the quiz module.
 *
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/bioauth/lib.php');

$pagetitle = get_string('modulename', 'local_bioauth');

$bioauthsettings = new admin_settingpage('modsettingbioauth', $pagetitle, 'moodle/site:config');

// Biometric Authentication
$options = array(
     true => get_string('enabled', 'local_bioauth'),
     false => get_string('disabled', 'local_bioauth'),
);
$bioauthsettings->add(new admin_setting_configselect('local_bioauth/mode',
        get_string('mode', 'local_bioauth'), get_string('mode_desc', 'local_bioauth'), 1, $options));

$ADMIN->add('localplugins', $bioauthsettings);
