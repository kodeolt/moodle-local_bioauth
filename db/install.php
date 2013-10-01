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
 * Disable the assignment module for new installs
 *
 * @package local_bioauth
 * @copyright 2013 Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/bioauth/locallib.php');

/**
 * Load all of the keystroke feature sets that came with the installation.
 *
 */
function load_keystroke_features() {
    global $CFG;
    global $DB;

    $featureit = new DirectoryIterator($CFG->dirroot . '/local/bioauth/features');
    foreach ($featureit as $features) {
        if ($features->isDot()) {
            continue;
        }

        unset($keystrokefeatures);
        unset($featuresetname);
        include($features->getPathname());

        $keystrokefeatureids = array();
        $keystrokefallback = array();
        $keystrokefeaturefields = array('fallback', 'type', 'group1', 'group2', 'measure', 'distance');
        foreach ($keystrokefeatures as $featureid => $feature) {
            $row = array_combine($keystrokefeaturefields, $feature);
            $keystrokefeatureids[$featureid] = $DB->insert_record('bioauth_keystroke_features', $row, true);
            $keystrokefallback[$featureid] = $row['fallback'];
        }

        foreach ($keystrokefallback as $node => $parent) {
            if (null !== $parent) {
                $DB->update_record('bioauth_keystroke_features',
                                    array('id' => $keystrokefeatureids[$node], 'fallback' => $keystrokefeatureids[$parent]));
            }
        }

        $DB->insert_record('bioauth_feature_sets', array('name' => $featuresetname, 'locale' => 'en',
                            'keystrokefeatures' => implode(',', array_keys($keystrokefeatureids)), 'stylometryfeatures' => ''));
    }
}

/**
 * Post-install script
 */
function xmldb_local_bioauth_install() {

    load_keystroke_features();

    return true;
}
