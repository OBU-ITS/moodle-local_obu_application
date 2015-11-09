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

    if ($oldversion < 2015110500) {

		// Define table local_obu_applicant
		$table = new xmldb_table('local_obu_applicant');

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
		$table->add_field('profile_update', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('award_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('start_date', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_1_no', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_1_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_2_no', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_2_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_3_no', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_3_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('statement', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('course_update', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('userid', XMLDB_INDEX_UNIQUE, array('userid'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
    }
	
    if ($oldversion < 2015110900) {

		// Define table local_obu_application
		$table = new xmldb_table('local_obu_application');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('title', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('address', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('postcode', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('phone', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('email', XMLDB_TYPE_CHAR, '100', null, null, null, null);
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
		$table->add_field('award_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('start_date', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_1_no', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_1_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_2_no', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_2_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_3_no', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('module_3_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('statement', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('self_funding', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('manager_email', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('disclaimer', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('application_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('approval_1_status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('approval_1_comments', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('approval_1_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('tel_email', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('approval_2_status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('approval_2_comments', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('approval_2_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('application', XMLDB_INDEX_UNIQUE, array('userid', 'id'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

        // obu_application savepoint reached
        upgrade_plugin_savepoint(true, 2015110900, 'local', 'obu_application');
    }
    
    return $result;
}
