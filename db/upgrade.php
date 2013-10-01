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
 * Upgrade script for the BioAuth plugin.
 *
 * @package    local_bioauth
 * @copyright  2013 Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * BioAuth plugin upgrade function.
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_local_bioauth_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013091600) {
        // Number of mouse events cached.
        $table = new xmldb_table('bioauth_quiz_biodata');

        $field = new xmldb_field('nummouseevents', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2013091600, 'local', 'bioauth');
    }
    
    if ($oldversion < 2013092603) {
        // Add session management so that the plugin can log data from the native java application
        $table = new xmldb_table('bioauth_sessions');

        if (!$dbman->table_exists($table)) {
            $table->addField(new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE));
            $table->addField(new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0));
            $table->addField(new xmldb_field('sesskey', XMLDB_TYPE_CHAR, '100'));
            $table->addField(new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0));
            $table->addKey(new xmldb_key('primary', XMLDB_KEY_PRIMARY, array('id'), null, null));
            $table->addIndex(new xmldb_index('userid', XMLDB_INDEX_UNIQUE, array('userid')));
            $dbman->create_table($table);
        }
        
        upgrade_plugin_savepoint(true, 2013092603, 'local', 'bioauth');
    }

    // Moodle v2.5.0 release upgrade line.
    // Put any upgrade step following this.


    return true;
}

