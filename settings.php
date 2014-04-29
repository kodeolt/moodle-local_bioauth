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
 * General and default settings for the BioAuth plugin.
 *
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/bioauth/lib.php');

$pagetitle = get_string('pluginname', 'local_bioauth');

$bioauthsettings = new admin_settingpage('local_bioauth', $pagetitle, 'moodle/site:config');

/******************* General settings *********************/

$bioauthsettings->add(new admin_setting_heading('generalsettings',
get_string('generalsettings', 'local_bioauth'), get_string('generalsettingsdesc', 'local_bioauth')));

$options = array(BIOAUTH_MODE_ENABLED => get_string('enabled', 'local_bioauth'),
                    BIOAUTH_MODE_DISABLED => get_string('disabled', 'local_bioauth'), );

$bioauthsettings->add(new admin_setting_configselect('local_bioauth/mode',
                        get_string('mode', 'local_bioauth'),
                        get_string('modedesc', 'local_bioauth'), BIOAUTH_MODE_ENABLED, $options));

$bioauthsettings->add(new admin_setting_configtext('local_bioauth/autosaveperiod',
                        get_string('autosaveperiod', 'local_bioauth'),
                        get_string('autosaveperioddesc', 'local_bioauth'), 5, PARAM_INT));
                        
$bioauthsettings->add(new admin_setting_configtext('local_bioauth/numbiodatarows',
                        get_string('numbiodatarows', 'local_bioauth'),
                        get_string('numbiodatarowsdesc', 'local_bioauth'), 50, PARAM_INT));

if ($hassiteconfig) {
    $ADMIN->add('localplugins', $bioauthsettings);
}


$settings = null;
