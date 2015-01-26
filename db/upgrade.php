<?php

// This file is part of the eportlink module for Moodle - http://moodle.org/
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
 * This file keeps track of upgrades to the certificate module
 *
 * @package    mod
 * @subpackage eportlink
 * @copyright  eportlink <contact@saylor.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_eportlink_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2014111800) {

        // Changing type of field description on table eportlink to text.
        $table = new xmldb_table('eportlink');
        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'achievementid');

        // Launch change of type for field description.
        $dbman->change_field_type($table, $field);

        // eportlink savepoint reached.
        upgrade_mod_savepoint(true, 2014111800, 'eportlink');
    }

    if ($oldversion < 2014112600) {

        // Define field completionactivities to be added to eportlink.
        $table = new xmldb_table('eportlink');
        $field = new xmldb_field('completionactivities', XMLDB_TYPE_TEXT, null, null, null, null, null, 'passinggrade');

        // Conditionally launch add field completionactivities.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // eportlink savepoint reached.
        upgrade_mod_savepoint(true, 2014112600, 'eportlink');
    }

    return true;
}
