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
require_once($CFG->libdir.'/formslib.php');

/**
 * Form to launch the native logger.
 *
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bioauth_bbl_form extends moodleform {

    
    protected function definition() {
        global $CFG, $DB, $USER;
        $mform =& $this->_form;

        $returnurl = new moodle_url('/');
        $codebase = new moodle_url('/local/bioauth/biologger/java/');
        $launchurl = new moodle_url('/local/bioauth/launch/launchbbl.php');
        $enrollurl = new moodle_url('/local/bioauth/enroll.ajax.php', array('sesskey' => sesskey()));
        
        $tags = "";
        
        $mform->addElement('header', 'launchheader',
                get_string('launchopen', 'local_bioauth'));
        
        $mform->addElement('select', 'task',
                get_string('activity', 'local_bioauth'),
                array(
                    'web search' => get_string('websearch', 'local_bioauth'),
                    'online game star bubbles' => get_string('onlinegamestarbubbles', 'local_bioauth'),
                    'online game spider solitaire' => get_string('onlinegamespidersolitaire', 'local_bioauth'),
                    'edit paragraph' => get_string('editparagraph', 'local_bioauth'),
                    'free input' => get_string('freeinput', 'local_bioauth'),
                ));
        
        $mform->addElement('submit', 'launchbbl', get_string('launch', 'local_bioauth'));
        
        $mform->addElement('hidden', 'returnurl', $returnurl);
        $mform->setType('returnurl', PARAM_URL);
        $mform->addElement('hidden', 'username', $USER->username);
        $mform->setType('username', PARAM_USERNAME);
        $mform->addElement('hidden', 'codebase', $codebase);
        $mform->setType('codebase', PARAM_URL);
        $mform->addElement('hidden', 'enrollurl', $enrollurl);
        $mform->setType('enrollurl', PARAM_LOCALURL);
        $mform->addElement('hidden', 'tags', $tags);
        $mform->setType('tags', PARAM_ALPHAEXT);
    }

    public function validation($fromform, $files) {
        $errors= array();

        if (0 == $fromform['task']) {
            $errors['task'] = get_string('pleaseselect', 'local_bioauth');
        }
        
        return $errors;
    }
}