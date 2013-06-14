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
 * Library of functions used by the bioauth module.
 *
 * This contains functions that are called from within the bioauth module only
 * Functions that are also called by core Moodle are in {@link lib.php}
 *
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;


class keystroke_sequence {
    
    protected $session;
    protected $keystrokes;
    
    public function __construct($session, $keystrokes) {
        $this->session = $session;
        $this->keystrokes = $keystrokes;
    }
    
    public static function create($sessionid) {
        global $DB;
        
        $session = $DB->get_record('bioauth_sessions', array('id' => $sessionid), '*', MUST_EXIST);
        $keystrokes = $DB->get_records('bioauth_keystroke_events', array('userid' => $session->userid, 'sessionid' => $session->id), 'press_time', '*');
        
        return new keystroke_sequence($session, $keystrokes);
    }
}

class keystroke_feature {
    
    protected $features;
    
    public function __construct($features) {
        $this->features = $features;
    }
    
    public static function create($featureset) {
        $features = $DB->get_records('bioauth_keystroke_features', array('featureset' => $featureset), '', '*');
        
        return new keystroke_features($features);
    }
}

function create_keystroke_features($featuresetid) {
    global $DB;
    
    $records = $DB->get_records('bioauth_keystroke_features', array('featureset' => $featureset), '', '*');
    $features = array();
    
    foreach ($records as $record) {
        
    }
    
    return $features;
}
