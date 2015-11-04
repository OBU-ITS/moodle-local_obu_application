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
 * OBU Application - Database upgrade
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

function xmldb_local_obu_application_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2015102800) {

		// Define table local_obu_users
		$table = new xmldb_table('local_obu_profile');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('birthdate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('birthcountry', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('firstentrydate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('lastentrydate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('residencedate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('support', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('p16school', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('p16schoolperiod', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('p16fe', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('p16feperiod', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('training', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('trainingperiod', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('prof_level', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('prof_award', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('prof_date', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('emp_place', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('emp_area', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('emp_title', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('emp_prof', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('prof_reg_no', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('criminal_record', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
		$table->add_field('update_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('userid', XMLDB_INDEX_UNIQUE, array('userid'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

        // obu_application savepoint reached
        upgrade_plugin_savepoint(true, 2015102800, 'local', 'obu_application');
    }
    
    return $result;
}
