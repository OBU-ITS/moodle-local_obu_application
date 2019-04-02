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
 * OBU Application - db updates acting on the local_obu_application tables
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2018, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

function get_applications_course() {
	global $DB;
	
	$course = $DB->get_record('course', array('idnumber' => 'SUBS_APPLICATIONS'), 'id', MUST_EXIST);
	return $course->id;
}

// Check if the given user has the given role in the applications management course
function has_applications_role($user_id = 0, $role_id_1 = 0, $role_id_2 = 0, $role_id_3 = 0) {
	global $DB;
	
	if (($user_id == 0) || ($role_id_1 == 0)) { // Both mandatory
		return false;
	}
	
	$sql = 'SELECT ue.id'
		. ' FROM {user_enrolments} ue'
		. ' JOIN {enrol} e ON e.id = ue.enrolid'
		. ' JOIN {context} ct ON ct.instanceid = e.courseid'
		. ' JOIN {role_assignments} ra ON ra.contextid = ct.id'
		. ' JOIN {course} c ON c.id = e.courseid'
		. ' WHERE ue.userid = ?'
			. ' AND e.enrol = "manual"'
			. ' AND ct.contextlevel = 50'
			. ' AND ra.userid = ue.userid'
			. ' AND (ra.roleid = ? OR ra.roleid = ? OR ra.roleid = ?)'
			. ' AND c.idnumber = "SUBS_APPLICATIONS"';
	$db_ret = $DB->get_records_sql($sql, array($user_id, $role_id_1, $role_id_2, $role_id_3));
	if (empty($db_ret)) {
		return false;
	} else {
		return true;
	}
}

function is_enroled() {
	global $DB, $USER;
	
	// Establish the initial selection criteria to apply
	$criteria = 'substr(c.shortname, 7, 1) = " " AND substr(c.shortname, 13, 1) = "-" AND length(c.shortname) >= 18';
	$criteria = $criteria . ' AND ue.userid = ' . $USER->id; // Restrict modules to ones in which this user is enroled
	
	// Read the course (module) records that match our chosen criteria
	$sql = 'SELECT c.id, c.fullname, c.shortname '
		. 'FROM {course} c '
		. 'JOIN {enrol} e ON e.courseid = c.id '
		. 'JOIN {user_enrolments} ue ON ue.enrolid = e.id '
		. 'WHERE ' . $criteria;
	$db_ret = $DB->get_records_sql($sql, array());
	
	// Create an array of the current modules with the required type (if given)
	$modules = array();
	$now = time();
	foreach ($db_ret as $row) {
		$module_type = substr($row->fullname, 0, 1);
		$module_start = strtotime('01 ' . substr($row->shortname, 7, 3) . ' ' . substr($row->shortname, 10, 2));
		$module_end = strtotime('31 ' .	substr($row->shortname, 13, 3) . ' ' . substr($row->shortname, 16, 2));
		if ((!$type || ($module_type == $type)) && ($module_end >= $now)) { // Must be the required type and not already ended
			if ($user_id == 0) { // Just need the module code for validation purposes
				$split_pos = strpos($row->fullname, ': ');
				if ($split_pos !== false) {
					$modules[$row->id] = substr($row->fullname, 0, $split_pos);
				}
			} else { // Need the full name
				$split_pos = strpos($row->fullname, ' (');
				if ($split_pos !== false) {
					$modules[$row->id] = substr($row->fullname, 0, $split_pos);
				} else {
					$modules[$row->id] = $row->fullname;
				}
			}
		}
	}

	return $modules;
}

function read_parameter_by_name($name, $strict = false) {
	global $DB;
	
	if ($strict) {
		$strictness = MUST_EXIST;
	} else {
		$strictness = IGNORE_MISSING;
	}
	
	return $DB->get_record('local_obu_param', array('name' => $name), '*', $strictness);
}

function read_parameter_by_id($param_id) {
    global $DB;
    
	$parameter = $DB->get_record('local_obu_param', array('id' => $param_id), '*', MUST_EXIST);
	
	return $parameter;	
}

function write_parameter($parameter) {
	global $DB;
	
    $record = new stdClass();
	$id = $parameter->id;
	$record->name = $parameter->name;
	$record->number = $parameter->number;
	$record->text = $parameter->text;

	if ($id == '0') {
		$id = $DB->insert_record('local_obu_param', $record);
	} else {
		$record->id = $id;
		$DB->update_record('local_obu_param', $record);
	}
	
	return $id;
}

function delete_parameter($param_id) {
    global $DB;
    
	$DB->delete_records('local_obu_param', array('id' => $param_id));
}

function get_parameter_records() {
	global $DB;
	
	return $DB->get_records('local_obu_param', null, 'name');
}

function read_supplement_forms($ref) {
	global $DB;
	
	$supplements = $DB->get_records('local_obu_supplement', array('ref' => $ref), 'version', '*');
	
	return $supplements;
}
	
function read_supplement_form($ref, $version) {
    global $DB;
    
	$supplement = $DB->get_record('local_obu_supplement', array('ref' => $ref, 'version' => $version), '*', IGNORE_MISSING);
	
	return $supplement;	
}

function read_supplement_form_by_id($supplement_id) {
    global $DB;
    
	$supplement = $DB->get_record('local_obu_supplement', array('id' => $supplement_id), '*', MUST_EXIST);
	
	return $supplement;	
}

function write_supplement_form($author, $supplement) {
	global $DB;
	
    $record = new stdClass();
	$record->ref = $supplement->ref;
    $record->version = $supplement->version;
    $record->author = $author;
	$record->date = time();
	$record->published = $supplement->published;
	$record->template = $supplement->template['text'];

	$supplement_form = read_supplement_form($record->ref, $record->version);
	if ($supplement_form !== false) {
		$id = $supplement_form->id;
		$record->id = $id;
		$DB->update_record('local_obu_supplement', $record);
	} else {		
		$id = $DB->insert_record('local_obu_supplement', $record);
	}
	
	return $id;
}

function get_supplement_form($ref, $include_unpublished = false) { // Return the latest version of the supplement form
    global $DB;
    
    // Return the latest version
	$supplement = null;
	$supplements = read_supplement_forms($ref);
	foreach ($supplements as $s) {
		if ($s->published || $include_unpublished) {
			$supplement = $s;
		}
	}
	
	if ($supplement) {
		return $supplement;
	}
	
	return false;
}

function read_course_record_by_id($course_id) {
    global $DB;
    
	return $DB->get_record('local_obu_course', array('id' => $course_id), '*', MUST_EXIST);
}

function read_course_record($course_code) {
    global $DB;
    
	return $DB->get_record('local_obu_course', array('code' => $course_code), '*', MUST_EXIST);
}

function write_course_record($course) {
	global $DB;
	
    $record = new stdClass();
	$id = $course->id;
	$record->code = $course->code;
	$record->name = $course->name;
	$record->supplement = $course->supplement;

	if ($id == '0') {
		$id = $DB->insert_record('local_obu_course', $record);
	} else {
		$record->id = $id;
		$DB->update_record('local_obu_course', $record);
	}
	
	return $id;
}

function delete_course_record($course_id) {
    global $DB;
    
	$DB->delete_records('local_obu_course', array('id' => $course_id));
}

function get_course_records() {
	global $DB;
	
	return $DB->get_records('local_obu_course', null, 'code');
}

function read_organisation($organisation_id) {
    global $DB;
    
	return $DB->get_record('local_obu_organisation', array('id' => $organisation_id), '*');
}

function write_organisation($organisation) {
	global $DB;
	
    $record = new stdClass();
	$id = $organisation->id;
	$record->name = $organisation->name;
	$record->email = $organisation->email;
	$record->code = $organisation->code;
	$record->address = $organisation->address;
	$record->suspended = $organisation->suspended;

	if ($id == '0') {
		$id = $DB->insert_record('local_obu_organisation', $record);
	} else {
		$record->id = $id;
		$DB->update_record('local_obu_organisation', $record);
	}
	
	return $id;
}

function delete_organisation($organisation_id) {
    global $DB;
    
	$DB->delete_records('local_obu_organisation', array('id' => $organisation_id));
}

function get_organisation_records() {
	global $DB;
	
	return $DB->get_records('local_obu_organisation', null, 'name');
}
 
function read_user($user_id) {
    global $DB;
    
	$user = $DB->get_record('user', array('id' => $user_id), '*', MUST_EXIST);
	profile_load_data($user); // Add custom profile data
	
	return $user;	
}

function write_user($user_id, $form_data) {
	global $DB;
	
	$user = read_user($user_id);
	
	if (isset($form_data->username)) {
		$user->username = $form_data->username;
	}
	$user->firstname = $form_data->firstname;
	$user->lastname = $form_data->lastname;
	$user->email = strtolower($form_data->email);
	$user->phone1 = $form_data->phone1;
	$user->city = $form_data->town;
		
	user_update_user($user, false, true);
	profile_save_data($user); // Save custom profile data
}

function get_applicants_by_name($lastname) {
    global $DB;
    
	$sql = 'SELECT DISTINCT a.userid, a.firstname, a.lastname '
		. 'FROM {local_obu_application} a '
		. 'WHERE a.lastname LIKE "' . $lastname . '%" '
		. 'ORDER BY a.userid';
	
	return $DB->get_records_sql($sql, array());
}

function read_applicant($user_id, $must_exist) {
    global $DB;
    
	if ($must_exist) {
		$strictness = MUST_EXIST;
	} else {
		$strictness = IGNORE_MISSING;
	}
	
	$applicant = $DB->get_record('local_obu_applicant', array('userid' => $user_id), '*', $strictness);
	
	return $applicant;	
}

function write_contact_details($user_id, $form_data) {
	global $DB;
	
	$record = read_applicant($user_id, false); // May not exist yet
	if ($record === false) {
		$record = new stdClass();
		$record->id = 0;
		$record->userid = $user_id;
	}
	
	// Update the applicant's title and address details
    $record->title = $form_data->title;
	if ($form_data->address_1 != '') { // Check we are updating full Contact Details and not Signin sub-set
		$record->address_1 = $form_data->address_1;
		$record->address_2 = $form_data->address_2;
		$record->address_3 = $form_data->address_3;
		$record->town = $form_data->town;
		$record->domicile_code = $form_data->domicile_code;
		$record->county = $form_data->county;
		$record->postcode = $form_data->postcode;
	}

	if ($record->id == 0) { // New record
		$id = $DB->insert_record('local_obu_applicant', $record);
	} else {		
		$id = $record->id;
		$DB->update_record('local_obu_applicant', $record);
	}
	
	return $id;
}

function write_profile($user_id, $form_data) {
	global $DB;
	
	$record = read_applicant($user_id, false); // May not exist yet
	if ($record === false) {
		$record = new stdClass();
		$record->id = 0;
		$record->userid = $user_id;
	}
	
	// Update the applicant's profile fields
    $record->birthdate = $form_data->birthdate;
    $record->nationality_code = $form_data->nationality_code;
    $record->nationality = $form_data->nationality;
	$record->p16school = $form_data->p16school;
    $record->p16schoolperiod = $form_data->p16schoolperiod;
    $record->p16fe = $form_data->p16fe;
    $record->p16feperiod = $form_data->p16feperiod;
    $record->training = $form_data->training;
    $record->trainingperiod = $form_data->trainingperiod;
    $record->prof_level = $form_data->prof_level;
    $record->prof_award = $form_data->prof_award;
    $record->prof_date = $form_data->prof_date;
    $record->credit = $form_data->credit;
	if ($record->credit == '0') {
		$record->credit_name = '';
		$record->credit_organisation = '';
	} else {
		$record->credit_name = $form_data->credit_name;
		$record->credit_organisation = $form_data->credit_organisation;
	}	
    $record->emp_place = $form_data->emp_place;
    $record->emp_area = $form_data->emp_area;
    $record->emp_title = $form_data->emp_title;
    $record->emp_prof = $form_data->emp_prof;
    $record->prof_reg_no = $form_data->prof_reg_no;
    $record->criminal_record = $form_data->criminal_record;
    $record->profile_update = time();

	if ($record->id == 0) { // New record
		$id = $DB->insert_record('local_obu_applicant', $record);
	} else {		
		$id = $record->id;
		$DB->update_record('local_obu_applicant', $record);
	}
	
	return $id;
}

function write_course($user_id, $form_data) {
	global $DB;
	
	$record = read_applicant($user_id, true); // Must already exist

	// Update the applicant's course fields
    $record->course_code = $form_data->course_code;
    $record->course_name = $form_data->course_name;
    $record->course_date = $form_data->course_date;
    $record->statement = $form_data->statement;
    $record->course_update = time();

	return $DB->update_record('local_obu_applicant', $record);
}

function write_supplement_data($user_id, $supplement_data) {
	global $DB;
	
	$record = read_applicant($user_id, true); // Must already exist

	// Update the supplement data for the applicant's course
    $record->supplement_data = $supplement_data;
    $record->course_update = time();

	return $DB->update_record('local_obu_applicant', $record);
}

function read_application($application_id) {
    global $DB;
    
	$application = $DB->get_record('local_obu_application', array('id' => $application_id), '*', MUST_EXIST);
	
	return $application;	
}

function write_application($user_id, $form_data) {
	global $DB;
	
	$user = read_user($user_id); // Contact details
	$applicant = read_applicant($user_id, true); // Profile & course must exist
	
	// Initialise the new record
	$record = new stdClass();
	$record->id = 0;
	$record->userid = $user_id;
	
	// Contact details
	$record->title = $applicant->title;
	$record->firstname = $user->firstname;
	$record->lastname = $user->lastname;
	$record->address_1 = $applicant->address_1;
	$record->address_2 = $applicant->address_2;
	$record->address_3 = $applicant->address_3;
	$record->town = $applicant->town;
	$record->domicile_code = $applicant->domicile_code;
	$record->county = $applicant->county;
	$record->postcode = $applicant->postcode;
	$record->phone = $user->phone1;
	$record->email = $user->email;

	// Profile
    $record->birthdate = $applicant->birthdate;
	$record->nationality_code = $applicant->nationality_code;
    $record->nationality = $applicant->nationality;
    $record->p16school = $applicant->p16school;
    $record->p16schoolperiod = $applicant->p16schoolperiod;
    $record->p16fe = $applicant->p16fe;
    $record->p16feperiod = $applicant->p16feperiod;
    $record->training = $applicant->training;
    $record->trainingperiod = $applicant->trainingperiod;
    $record->prof_level = $applicant->prof_level;
    $record->prof_award = $applicant->prof_award;
    $record->prof_date = $applicant->prof_date;
    $record->credit = $applicant->credit;
    $record->credit_name = $applicant->credit_name;
    $record->credit_organisation = $applicant->credit_organisation;
    $record->emp_place = $applicant->emp_place;
    $record->emp_area = $applicant->emp_area;
    $record->emp_title = $applicant->emp_title;
    $record->emp_prof = $applicant->emp_prof;
    $record->prof_reg_no = $applicant->prof_reg_no;
    if ($applicant->criminal_record == '1') { // '1' = yes, '2' = no
		$record->criminal_record = '1'; // Yes
	} else {
		$record->criminal_record = '0'; // No
	}
	
	// Course
    $record->course_code = $applicant->course_code;
    $record->course_name = $applicant->course_name;
    $record->course_date = $applicant->course_date;
    $record->statement = $applicant->statement;
	$course = read_course_record($applicant->course_code);
	if ($course->supplement != '') { // There should be supplementary data
		$record->supplement_data = $applicant->supplement_data;
	}
	
	// Final details
	$record->self_funding = $form_data->self_funding;
//	$record->manager_email = $form_data->email;
	if (isset($form_data->declaration)) { // Only set if checked
		$record->declaration = 1;
	} else {
		$record->declaration = 0;
	}
	
    $record->application_date = time();

	return $DB->insert_record('local_obu_application', $record); // The remaining fields will have default values
}

function update_application($application) {
    global $DB;
    
	return $DB->update_record('local_obu_application', $application);
}

function get_applications($user_id = null) {
    global $DB;
    
	if ($user_id != null) { // Required for just this user
		$applications = $DB->get_records('local_obu_application', array('userid' => $user_id), 'application_date DESC');
	} else { // All applications
		$applications = $DB->get_records('local_obu_application');
	}
	
	return $applications;
}

function get_applications_for_courses($selected_courses = '', $application_date = 0, $sort_order = '') {
    global $DB;

	$sql = 'SELECT * FROM {local_obu_application} WHERE application_date >= ?';
	if ($selected_courses != '') {
		$sql .= ' AND course_code IN (' . $selected_courses . ')';
	}
	if ($sort_order != '') {
		$sql .= ' ORDER BY ' . $sort_order;
	}
	$sql .= ';';

	return $DB->get_records_sql($sql, array($application_date));
}

function get_applications_for_funder($funding_id = 0, $application_date = 0, $sort_order = '') {
    global $DB;

	$sql = 'SELECT * FROM {local_obu_application} WHERE application_date >= ? AND ';
	if ($funding_id == 0) {
		$sql .= 'self_funding = 1';
	} else {
		$sql .= 'funding_id = ?';
	}
	if ($sort_order != '') {
		$sql .= ' ORDER BY ' . $sort_order;
	}
	$sql .= ';';

	return $DB->get_records_sql($sql, array($application_date, $funding_id));
}

function read_approval($application_id, &$approval) {
    global $DB;
    
	$approval = $DB->get_record('local_obu_approval', array('application_id' => $application_id), '*', IGNORE_MISSING);
	if ($approval === false) {
		$approval = new stdClass();
		$approval->id = 0;
		$approval->application_id = $application_id;
		$approval->approver = '';
		$approval->date = 0;
	}
}

function write_approval($approval) {
    global $DB;
    
	if ($approval->id == 0) {
		$id = $DB->insert_record('local_obu_approval', $approval);
	} else {
		$id = $approval->id;
		$DB->update_record('local_obu_approval', $approval);
	}
	
	return $id;
}

function delete_approval($approval) {
    global $DB;
    
	if ($approval->id != 0) {
		$DB->delete_records('local_obu_approval', array('id' => $approval->id));
	}
}

function get_approvals($approver_email) {
    global $DB;

	$conditions = array();
	
	if ($approver_email != '') {
		$conditions['approver'] = strtolower($approver_email);
	}

	return $DB->get_records('local_obu_approval', $conditions, 'request_date ASC');
}
