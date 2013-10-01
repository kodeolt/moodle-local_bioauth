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
 * Strings for component 'local_bioauth', language 'en'
 *
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['autosaveperiod'] = 'Autosave period';
$string['autosaveperioddesc'] = 'Duration (in seconds) between auto save attempts in the biologger.';
$string['bioauth'] = 'Biometric Authentication';
$string['disabled'] = 'Disabled';
$string['enabled'] = 'Enabled';
$string['mode'] = 'Mode';
$string['mode_desc'] = 'Enable Biometric Authentication for verifying the identity of students taking quizzes.';
$string['pluginname'] = 'BioAuth';
$string['pluginname_help'] = 'BioAuth help.';
$string['performancegraph'] = 'FRR vs FAR';
$string['install_bootstrap'] = 'Installing bootstrap data.';
$string['performancesummary'] = 'Estimated performance is {$a->performance}% on {$a->numauths} quiz attempts';
$string['settings'] = 'Settings';
$string['report'] = 'Report';
$string['coursequizauths'] = '{$a} Quiz Authentications';
$string['generalsettings'] = 'General Settings';
$string['generalsettingsdesc'] = 'These settings affect all authentication jobs.';
$string['modedesc'] = 'Enable or disable the plugin for newly created courses.';
$string['usealldata'] = 'Use all data';
$string['usealldatadesc'] = 'Use all of the data available for a particular user.';
$string['dontuserejected'] = 'Don\'t use rejected';
$string['dontuserejecteddesc'] = 'Don\'t use enrol samples which have been determined unauthentic.';
$string['weekskeepactive'] = 'Weeks keep active';
$string['weekskeepactivedesc'] = 'How many weeks to keep a job active and monitor new data.';
$string['percentdataneeded'] = 'Percent data needed';
$string['percentdataneededdesc'] = 'The percent of data needed to start a job, where 100% would be every available quiz completed by every enrolled student.';
$string['maxconcurrentjobs'] = 'Max concurrent jobs';
$string['maxconcurrentjobsdesc'] = 'The number of jobs allowed to run simultaneously.';
$string['defaultsettings'] = 'Default Settings';
$string['defaultsettingsdesc'] = 'The default settings for newly created jobs';
$string['knn'] = 'KNN';
$string['knndesc'] = 'K-nearest neighbors to use in classification.';
$string['minkeyfrequency'] = 'Min key frequency';
$string['minkeyfrequencydesc'] = 'The minimum number of occurances needed before enabling the fallback hierachy for any particular feature.';
$string['decisionmode'] = 'Decision mode';
$string['decisionmodedesc'] = 'Authentication decision preference when determining the authenticity of a sample.';
$string['neutral'] = 'Neutral';
$string['convenience'] = 'Convenience';
$string['security'] = 'Security';
$string['featureset'] = 'Feature set';
$string['featuresetdesc'] = 'Which feature set to use in authentications.';
$string['cachekeycodes'] = 'Cache keycodes';
$string['cachekeycodesdesc'] = 'Use cache when looking up keycodes';
$string['sortasc'] = 'Sort ascending';
$string['sortdesc'] = 'Sort descending';
$string['lastrun'] = 'Last run';
$string['status'] = 'Status';
$string['performance'] = 'Performance';
$string['percentcomplete'] = 'Job complete';
$string['action'] = 'Action';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
$string['confirmdisable'] = 'Are you sure you want to disable the plugin for {$a}? All jobs and authentication decisions will be removed.';

$string['jobstatewaiting'] = 'Waiting for data...';
$string['jobstatemonitor'] = 'Looking for NEW data';
$string['jobstateready'] = 'Queued to run';
$string['jobstaterunning'] = 'Running, {$a}% complete';
$string['jobstatecomplete'] = 'Complete';
$string['jobstateunknown'] = 'Unknown state';
$string['percentdataready'] = 'Data ready';
$string['minkeystrokesperquiz'] = 'Min keystrokes/quiz';
$string['minkeystrokesperquizdesc'] = 'The minimum number of keystrokes needed per quiz in order to achieve 100% data available.';

$string['bbl'] = 'Behavioral Biometrics Logger';
$string['activity'] = 'Activity';
$string['selectanactivity'] = 'Select an activity';
$string['pleaseselect'] = 'Please select an activity before launching.';
$string['onlinegamestarbubbles'] = 'Game: Star Bubbles';
$string['onlinegamespidersolitaire'] = 'Game: Spider Solitaire';
$string['editparagraph'] = 'Edit paragraph';
$string['browsersearch'] = 'Browser search';
$string['freeinput'] = 'Free input';
$string['launch'] = 'Launch';
$string['launchopen'] = 'Select an activity and then click launch. Open the file "bbl.jnlp" with Java Web Start';
