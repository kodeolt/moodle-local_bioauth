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
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/bioauth/lib.php');

$pagetitle = get_string('pluginname', 'local_bioauth');

$bioauthsettings = new admin_settingpage('local_bioauth', $pagetitle, 'moodle/site:config');

$bioauthsettings->add(new admin_setting_heading('generalsettings', get_string('generalsettings', 'local_bioauth'), get_string('generalsettingsdesc', 'local_bioauth')));

// Enable for new quizzes
$options = array(
     BIOAUTH_MODE_ENABLED => get_string('enabled', 'local_bioauth'),
     BIOAUTH_MODE_DISABLED => get_string('disabled', 'local_bioauth'),
);

$bioauthsettings->add(new admin_setting_configselect('local_bioauth/mode',
        get_string('mode', 'local_bioauth'), get_string('modedesc', 'local_bioauth'), BIOAUTH_MODE_ENABLED, $options));

// Utilize all data collected for a user (from other courses)
$bioauthsettings->add(new admin_setting_configcheckbox('local_bioauth/usealldata',
        get_string('usealldata', 'local_bioauth'), get_string('usealldatadesc', 'local_bioauth'), 0));

// Don't use samples which have been determined to be nonauthentic
$bioauthsettings->add(new admin_setting_configcheckbox('local_bioauth/dontuserejected',
        get_string('dontuserejected', 'local_bioauth'), get_string('dontuserejecteddesc', 'local_bioauth'), 0));

// How many weeks to keep a quiz validation active (look for new data)
$bioauthsettings->add(new admin_setting_configtext('local_bioauth/weekskeepactive',
        get_string('weekskeepactive', 'local_bioauth'), get_string('weekskeepactivedesc', 'local_bioauth'), 2, PARAM_INT));

// Percent of available data to trigger a course validation
$bioauthsettings->add(new admin_setting_configtext('local_bioauth/percentdataneeded',
        get_string('percentdataneeded', 'local_bioauth'), get_string('percentdataneededdesc', 'local_bioauth'), 50, PARAM_INT));
        
// Maximum number of jobs to run concurrently
$bioauthsettings->add(new admin_setting_configtext('local_bioauth/maxconcurrentjobs',
        get_string('maxconcurrentjobs', 'local_bioauth'), get_string('maxconcurrentjobsdesc', 'local_bioauth'), 2, PARAM_INT));


$bioauthsettings->add(new admin_setting_heading('defaultsettings', get_string('defaultsettings', 'local_bioauth'), get_string('defaultsettingsdesc', 'local_bioauth')));

// knn
$bioauthsettings->add(new admin_setting_configtext('local_bioauth/knn',
        get_string('knn', 'local_bioauth'), get_string('knndesc', 'local_bioauth'), 11, PARAM_INT));

// min key frequency
$bioauthsettings->add(new admin_setting_configtext('local_bioauth/minkeyfrequency',
        get_string('minkeyfrequency', 'local_bioauth'), get_string('minkeyfrequencydesc', 'local_bioauth'), 5, PARAM_INT));

// ROC operating point
$options = array(
     BIOAUTH_DECISION_NEUTRAL => get_string('neutral', 'local_bioauth'),
     BIOAUTH_DECISION_CONVENIENT => get_string('convenience', 'local_bioauth'),
     BIOAUTH_DECISION_SECURE => get_string('security', 'local_bioauth'),
);

$bioauthsettings->add(new admin_setting_configselect('local_bioauth/decisionmode',
        get_string('decisionmode', 'local_bioauth'), get_string('decisionmodedesc', 'local_bioauth'), BIOAUTH_DECISION_NEUTRAL, $options));

$options = get_feature_sets(current_language());

$bioauthsettings->add(new admin_setting_configselect('local_bioauth/featureset',
        get_string('featureset', 'local_bioauth'), get_string('featuresetdesc', 'local_bioauth'), 0, $options));


$ADMIN->add('localplugins', $bioauthsettings);
