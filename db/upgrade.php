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
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

function xmldb_local_obu_application_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2016050600) {

		// Define table local_obu_param
		$table = new xmldb_table('local_obu_param');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('name', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('number', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('text', XMLDB_TYPE_CHAR, '100', null, null, null, null);

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('name', XMLDB_INDEX_UNIQUE, array('name'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Define table local_obu_course
		$table = new xmldb_table('local_obu_course');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('code', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
		$table->add_field('supplement', XMLDB_TYPE_CHAR, '10', null, null, null, null);

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('code', XMLDB_INDEX_UNIQUE, array('code'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Define table local_obu_supplement
		$table = new xmldb_table('local_obu_supplement');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('ref', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('version', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('author', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('published', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('template', XMLDB_TYPE_TEXT, 'small', null, null, null, null);

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('supplement', XMLDB_INDEX_UNIQUE, array('ref', 'version'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Define table local_obu_organisation
		$table = new xmldb_table('local_obu_organisation');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
		$table->add_field('email', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
		$table->add_field('code', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('name', XMLDB_INDEX_UNIQUE, array('name'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Define table local_obu_applicant
		$table = new xmldb_table('local_obu_applicant');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('title', XMLDB_TYPE_CHAR, '10', null, null, null, null);
		$table->add_field('address_1', XMLDB_TYPE_CHAR, '50', null, null, null, null);
		$table->add_field('address_2', XMLDB_TYPE_CHAR, '50', null, null, null, null);
		$table->add_field('address_3', XMLDB_TYPE_CHAR, '50', null, null, null, null);
		$table->add_field('town', XMLDB_TYPE_CHAR, '50', null, null, null, null);
		$table->add_field('domicile_code', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
		$table->add_field('county', XMLDB_TYPE_CHAR, '30', null, null, null, null);
		$table->add_field('postcode', XMLDB_TYPE_CHAR, '20', null, null, null, null);
		$table->add_field('birthdate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('nationality_code', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
		$table->add_field('nationality', XMLDB_TYPE_CHAR, '100', null, null, null, null);
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
		$table->add_field('course_code', XMLDB_TYPE_CHAR, '10', null, null, null, null);
		$table->add_field('course_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('course_date', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('statement', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('supplement_data', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('course_update', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('userid', XMLDB_INDEX_UNIQUE, array('userid'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Define table local_obu_application
		$table = new xmldb_table('local_obu_application');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('title', XMLDB_TYPE_CHAR, '10', null, null, null, null);
		$table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('address_1', XMLDB_TYPE_CHAR, '50', null, null, null, null);
		$table->add_field('address_2', XMLDB_TYPE_CHAR, '50', null, null, null, null);
		$table->add_field('address_3', XMLDB_TYPE_CHAR, '50', null, null, null, null);
		$table->add_field('town', XMLDB_TYPE_CHAR, '50', null, null, null, null);
		$table->add_field('domicile_code', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
		$table->add_field('county', XMLDB_TYPE_CHAR, '30', null, null, null, null);
		$table->add_field('postcode', XMLDB_TYPE_CHAR, '20', null, null, null, null);
		$table->add_field('phone', XMLDB_TYPE_CHAR, '20', null, null, null, null);
		$table->add_field('email', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('birthdate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('nationality_code', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
		$table->add_field('nationality', XMLDB_TYPE_CHAR, '100', null, null, null, null);
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
		$table->add_field('course_code', XMLDB_TYPE_CHAR, '10', null, null, null, null);
		$table->add_field('course_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('course_date', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('statement', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('supplement_data', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('self_funding', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('manager_email', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('declaration', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('funder_email', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('funding_method', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('funding_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('funding_organisation', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('funder_name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('invoice_ref', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('invoice_address', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('invoice_email', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('invoice_phone', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('invoice_contact', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('application_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('approval_level', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('approval_state', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('approval_1_comment', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('approval_1_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('approval_2_comment', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('approval_2_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('approval_3_comment', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
		$table->add_field('approval_3_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('admissions_xfer', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('finance_xfer', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('application', XMLDB_INDEX_UNIQUE, array('userid', 'id'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Define table local_obu_approval
		$table = new xmldb_table('local_obu_approval');

		// Add fields
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('application_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('approver', XMLDB_TYPE_CHAR, '100', null, null, null, null);
		$table->add_field('request_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

		// Add keys
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Add indexes
		$table->add_index('application', XMLDB_INDEX_NOTUNIQUE, array('application_id'));
		$table->add_index('approver', XMLDB_INDEX_NOTUNIQUE, array('approver'));

		// Conditionally create table
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

        // obu_application savepoint reached
        upgrade_plugin_savepoint(true, 2016050600, 'local', 'obu_application');
    }

	if ($oldversion < 2016110100) {

		// Update local_obu_organisation
		$table = new xmldb_table('local_obu_organisation');
		$field = new xmldb_field('address', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'code');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2016110100, 'local', 'obu_application');
    }

	if ($oldversion < 2017072800) {

		// Update local_obu_applicant
		$table = new xmldb_table('local_obu_applicant');
		$field = new xmldb_field('credit', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'prof_date');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('credit_name', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'credit');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('credit_organisation', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'credit_name');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Update local_obu_application
		$table = new xmldb_table('local_obu_application');
		$field = new xmldb_field('credit', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'prof_date');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('credit_name', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'credit');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('credit_organisation', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'credit_name');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2017072800, 'local', 'obu_application');
    }

	if ($oldversion < 2017112000) {

		// Update local_obu_application
		$table = new xmldb_table('local_obu_application');
		$field = new xmldb_field('fund_programme', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'invoice_contact');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('fund_module_1', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fund_programme');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('fund_module_2', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fund_module_1');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('fund_module_3', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fund_module_2');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('fund_module_4', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fund_module_3');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('fund_module_5', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fund_module_4');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('fund_module_6', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fund_module_5');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('fund_module_7', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fund_module_6');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('fund_module_8', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fund_module_7');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('fund_module_9', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fund_module_8');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2017112000, 'local', 'obu_application');
    }

	if ($oldversion < 2018040400) {

		// Update local_obu_organisation
		$table = new xmldb_table('local_obu_organisation');
		$field = new xmldb_field('suspended', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'address');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2018040400, 'local', 'obu_application');
    }

	if ($oldversion < 2019112800) {

		// Update local_obu_course
		$table = new xmldb_table('local_obu_course');
		$field = new xmldb_field('programme', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'supplement');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('suspended', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'programme');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2019112800, 'local', 'obu_application');
    }

	if ($oldversion < 2020022000) {

		// Update local_obu_applicant
		$table = new xmldb_table('local_obu_applicant');
		$field = new xmldb_field('town', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'address_3');
		if ($dbman->field_exists($table, $field)) {
			$dbman->rename_field($table, $field, 'city');
		}
		$field = new xmldb_field('domicile_code', XMLDB_TYPE_CHAR, '4', null, null, null, null, 'city');
		if ($dbman->field_exists($table, $field)) {
			$dbman->change_field_type($table, $field);
		}
		$field = new xmldb_field('county', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'domicile_code');
		if ($dbman->field_exists($table, $field)) {
			$dbman->rename_field($table, $field, 'domicile_country');
		}
		$field = new xmldb_field('domicile_country', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'domicile_code');
		if ($dbman->field_exists($table, $field)) {
			$dbman->change_field_type($table, $field);
		}
		$field = new xmldb_field('birth_code', XMLDB_TYPE_CHAR, '4', null, null, null, null, 'postcode');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('birth_country', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'birth_code');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('nationality_code', XMLDB_TYPE_CHAR, '4', null, null, null, null, 'birthdate');
		if ($dbman->field_exists($table, $field)) {
			$dbman->change_field_type($table, $field);
		}
		$field = new xmldb_field('gender', XMLDB_TYPE_CHAR, '1', null, null, null, 'N', 'nationality');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Update local_obu_application
		$table = new xmldb_table('local_obu_application');
		$field = new xmldb_field('town', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'address_3');
		if ($dbman->field_exists($table, $field)) {
			$dbman->rename_field($table, $field, 'city');
		}
		$field = new xmldb_field('domicile_code', XMLDB_TYPE_CHAR, '4', null, null, null, null, 'city');
		if ($dbman->field_exists($table, $field)) {
			$dbman->change_field_type($table, $field);
		}
		$field = new xmldb_field('county', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'domicile_code');
		if ($dbman->field_exists($table, $field)) {
			$dbman->rename_field($table, $field, 'domicile_country');
		}
		$field = new xmldb_field('domicile_country', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'domicile_code');
		if ($dbman->field_exists($table, $field)) {
			$dbman->change_field_type($table, $field);
		}
		$field = new xmldb_field('phone', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'postcode');
		if ($dbman->field_exists($table, $field)) {
			$dbman->rename_field($table, $field, 'home_phone');
		}
		$field = new xmldb_field('mobile_phone', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'home_phone');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('birth_code', XMLDB_TYPE_CHAR, '4', null, null, null, null, 'email');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('birth_country', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'birth_code');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('nationality_code', XMLDB_TYPE_CHAR, '4', null, null, null, null, 'birthdate');
		if ($dbman->field_exists($table, $field)) {
			$dbman->change_field_type($table, $field);
		}
		$field = new xmldb_field('gender', XMLDB_TYPE_CHAR, '1', null, null, null, 'N', 'nationality');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2020022000, 'local', 'obu_application');
    }

	if ($oldversion < 2020022100) {

		// Update local_obu_applicant
		$table = new xmldb_table('local_obu_applicant');
		$field = new xmldb_field('residence_code', XMLDB_TYPE_CHAR, '4', null, null, null, null, 'gender');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('residence_area', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'residence_code');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Update local_obu_application
		$table = new xmldb_table('local_obu_application');
		$field = new xmldb_field('residence_code', XMLDB_TYPE_CHAR, '4', null, null, null, null, 'gender');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('residence_area', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'residence_code');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2020022100, 'local', 'obu_application');
    }

	if ($oldversion < 2020022200) {

		// Update local_obu_course
		$table = new xmldb_table('local_obu_course');
		$field = new xmldb_field('module_subject', XMLDB_TYPE_CHAR, '4', null, null, null, null, 'suspended');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('module_number', XMLDB_TYPE_CHAR, '5', null, null, null, null, 'module_subject');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('campus', XMLDB_TYPE_CHAR, '3', null, null, null, null, 'module_number');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('programme_code', XMLDB_TYPE_CHAR, '12', null, null, null, null, 'campus');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('major_code', XMLDB_TYPE_CHAR, '4', null, null, null, null, 'programme_code');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('level', XMLDB_TYPE_CHAR, '2', null, null, null, null, 'major_code');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('cohort_code', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'level');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2020022200, 'local', 'obu_application');
    }

	if ($oldversion < 2020022300) {

		// Update local_obu_applicant
		$table = new xmldb_table('local_obu_applicant');
		$field = new xmldb_field('studying', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'course_date');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Update local_obu_application
		$table = new xmldb_table('local_obu_application');
		$field = new xmldb_field('studying', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'course_date');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2020022300, 'local', 'obu_application');
    }

	if ($oldversion < 2020040900) {

		// Update local_obu_applicant
		$table = new xmldb_table('local_obu_applicant');
		$field = new xmldb_field('settled_status', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'residence_area');
		if ($dbman->field_exists($table, $field)) {
			$dbman->drop_field($table, $field);
		}

		// Update local_obu_application
		$table = new xmldb_table('local_obu_application');
		$field = new xmldb_field('settled_status', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'residence_area');
		if ($dbman->field_exists($table, $field)) {
			$dbman->drop_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2020040900, 'local', 'obu_application');
    }

	if ($oldversion < 2020042800) {

		// Update local_obu_applicant
		$table = new xmldb_table('local_obu_applicant');
		$field = new xmldb_field('visa_requirement', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, '', 'statement');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('visa_data', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'visa_requirement');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Update local_obu_application
		$table = new xmldb_table('local_obu_application');
		$field = new xmldb_field('visa_requirement', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, '', 'statement');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('visa_data', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'visa_requirement');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2020042800, 'local', 'obu_application');
    }

	if ($oldversion < 2020080200) {

		// Update local_obu_course
		$table = new xmldb_table('local_obu_course');
		$field = new xmldb_field('cohort_code', XMLDB_TYPE_CHAR, '25', null, null, null, null, 'level');
		if ($dbman->field_exists($table, $field)) {
			$dbman->change_field_type($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2020080200, 'local', 'obu_application');
    }

	if ($oldversion < 2020100100) {

		// Update local_obu_course
		$table = new xmldb_table('local_obu_course');
		$field = new xmldb_field('administrator', XMLDB_TYPE_CHAR, '8', null, null, null, null, 'suspended');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2020100100, 'local', 'obu_application');
    }

	if ($oldversion < 2021030300) {

		// Update local_obu_applicant
		$table = new xmldb_table('local_obu_applicant');
		$field = new xmldb_field('student_number', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, '', 'studying');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Update local_obu_application
		$table = new xmldb_table('local_obu_application');
		$field = new xmldb_field('student_number', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, '', 'studying');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// obu_application savepoint reached
		upgrade_plugin_savepoint(true, 2021030300, 'local', 'obu_application');
    }

    if($oldversion < 2024021301) {
        $table = new xmldb_table('local_obu_course');
        $field = new xmldb_field('course_start_sep', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0','cohort_code');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('course_start_jan', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'course_start_Sep');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('course_start_jun', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'course_start_Jan');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2024021301, 'local', 'obu_application');
    }

    if($oldversion < 2024022601) {
        // Identify funder users
        $sql = "UPDATE mdl_user
                INNER JOIN mdl_local_obu_application ON mdl_local_obu_application.funder_email = mdl_user.email
                SET mdl_user.institution = 'funder'
                WHERE mdl_local_obu_application.funder_email IS NOT NULL 
                    AND mdl_local_obu_application.funder_email <> ''
                    AND mdl_user.username == mdl_user.email";

        $DB->execute($sql);

        // Add new columns for last updated
        $table = new xmldb_table('local_obu_applicant');
        $field = new xmldb_field('contact_details_update', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'profile_update');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('criminal_record_update', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'contact_details_update');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('current_employment_update', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'criminal_record_update');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('edu_establishments_update', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'current_employment_update');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('personal_details_update', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'edu_establishments_update');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('pro_qualification_update', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'personal_details_update');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('pro_registration_update', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'pro_qualification_update');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2024022601, 'local', 'obu_application');
    }

    if($oldversion < 2024022602) {
        $table = new xmldb_table('local_obu_applicant');
        $field = new xmldb_field('profile_update', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        if($dbman->field_exists($table, $field)) {
            $sql = "UPDATE mdl_local_obu_applicant
                SET contact_details_update = profile_update,
                    criminal_record_update = profile_update,
                    current_employment_update = profile_update,
                    edu_establishments_update = profile_update,
                    personal_details_update = profile_update,
                    pro_qualification_update = profile_update,
                    pro_registration_update = profile_update";

            $DB->execute($sql);

            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2024022602, 'local', 'obu_application');
    }

    if($oldversion < 2024030100) {
        $table = new xmldb_table('local_obu_applicant');
        $field = new xmldb_field('personal_email', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'postcode');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2024030100, 'local', 'obu_application');
    }

    if($oldversion < 2024030804) {
        $table = new xmldb_table('local_obu_applicant');
        $field = new xmldb_field('professional_registration', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'emp_prof');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2024030804, 'local', 'obu_application');
    }

    if($oldversion < 2024030807) {
        $table = new xmldb_table('local_obu_xfer');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('xfer_number', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('xfer_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2024030807, 'local', 'obu_application');
    }

    if($oldversion < 2024030808) {
        $sql = "UPDATE mdl_user
                SET institution = ''
                WHERE institution = 'funder' 
                    AND username <> email";

        $DB->execute($sql);

        upgrade_plugin_savepoint(true, 2024030808, 'local', 'obu_application');
    }

    if($oldversion < 2024030809) {
        $table = new xmldb_table('local_obu_application');
        $field = new xmldb_field('personal_email', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'email');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2024030809, 'local', 'obu_application');
    }

    if($oldversion < 2024052001) {
        $sql = "DELETE FROM mdl_local_obu_course WHERE code IN ('PGC-ACZ C', 'NURS7027' , 'NURS6021' , 'NURS6070' , 'NURS6071' , 'PGC-AIC' , 'MSC-ACPHE' , 'MSC-ACPHEE' , 'NURS7077' , 'CMNR7007DL' , 'CMNR7007HE' , 'NURS7030SU' , 'NURS7030SO' , 'MSCANP60' , 'MSC-ANP60' , 'MSC-ANP' , 'PGD-ANP' , 'PGC-ANP' , 'NURS7006' , 'HCTR6001' , 'CMNR7003HE' , 'NURS7098' , 'CMNR7008HE' , 'CMNR7024HE' , 'HCTR6002' , 'HPED7002' , 'ADNR5014' , 'NURS7029' , 'NURS7060' , 'CMNR7004HE' , 'PGD-DSN B' , 'PGD-DSN S' , 'PGC-ENP' , 'CMNR7006HE' , 'CMNR7022HE' , 'NUTR7101 C' , 'CMNR7005HE' , 'HCTR6001' , 'CMNR7020 C' , 'CMNR7020HE' , 'MSC-HSIARC' , 'MSC-HSI60' , 'MSC-HSI80' , 'BSC-HSI' , 'BSCH-HSI' , 'MSC-HSI PH' , 'HCTR6003' , 'HESC6018' , 'NURS7058B' , 'NURS7058FR' , 'NURS7058HE' , 'NURS7058OH' , 'NURS7057OH' , 'NURS7057B' , 'NURS7057F' , 'NURS7057HE' , 'NURS6019' , 'MSC-MHS' , 'MSC-HSC' , 'PGC-MHS' , 'PGD-MHS' , 'HESC7015' , 'NURS7040' , 'PGC-NNP' , 'NURS7008' , 'NURS7030SM' , 'RHAB7002' , 'PGC-PMH-C' , 'PGD-PMH-C' , 'PGC-PPH-C' , 'PGD-PPH-C' , 'MHNR6003' , 'HESC7014' , 'HESC6020' , 'NURS7039' , 'HESC6002 M' , 'HESC6002-M' , 'HESC6002N' , 'HESC6002SF' , 'PGD-CPS BE' , 'PGD-CPS BU' , 'PGD-CPH BE' , 'PGD-CPH BU' , 'RHAB7003')";

        $DB->execute($sql);

        upgrade_plugin_savepoint(true, 2024052001, 'local', 'obu_application');
    }

    return $result;
}
