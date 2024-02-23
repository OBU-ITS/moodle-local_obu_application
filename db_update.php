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
 * @copyright  2021, Oxford Brookes University
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

// Get users with the given role(s) in the applications management course
function get_users_by_role($role_id_1 = 0, $role_id_2 = 0, $role_id_3 = 0) {
	global $DB;

	if ($role_id_1 == 0) { // Mandatory
		return false;
	}

	$sql = 'SELECT u.id, u.lastname, u.firstname, u.username, ra.roleid, FROM_UNIXTIME(ula.timeaccess, "%b-%y") AS access'
		. ' FROM {user_enrolments} ue'
		. ' JOIN {enrol} e ON e.id = ue.enrolid'
		. ' JOIN {context} ct ON ct.instanceid = e.courseid'
		. ' JOIN {role_assignments} ra ON ra.contextid = ct.id'
		. ' JOIN {course} c ON c.id = e.courseid'
		. ' JOIN {user} u ON u.id = ue.userid'
		. ' LEFT JOIN {user_lastaccess} ula ON ula.userid = u.id AND ula.courseid = c.id'
		. ' WHERE e.enrol = "manual"'
			. ' AND ct.contextlevel = 50'
			. ' AND ra.userid = ue.userid'
			. ' AND (ra.roleid = ? OR ra.roleid = ? OR ra.roleid = ?)'
			. ' AND c.idnumber = "SUBS_APPLICATIONS"'
		. ' ORDER BY u.lastname, u.firstname, u.username';

	return $DB->get_records_sql($sql, array($role_id_1, $role_id_2, $role_id_3));
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

function read_supplement_form_by_version($ref, $version) {
    global $DB;

    $supplement = $DB->get_record('local_obu_supplement', array('ref' => $ref, 'version' => $version),'*');

    return $supplement;
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

function get_supplement_form_by_version($ref, $version) {
    global $DB;

    $supplement = read_supplement_form_by_version($ref, $version);
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
	$record->programme = $course->programme;
	$record->suspended = $course->suspended;
	$record->administrator = $course->administrator;
	$record->module_subject = $course->module_subject;
	$record->module_number = $course->module_number;
	$record->campus = $course->campus;
	$record->programme_code = $course->programme_code;
	$record->major_code = $course->major_code;
	$record->level = $course->level;
	$record->cohort_code = $course->cohort_code;
    $record->course_start_sep = $course->course_start_sep;
    $record->course_start_jan = $course->course_start_jan;
    $record->course_start_jun = $course->course_start_jun;

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

    return $DB->get_records('local_obu_course', null, 'name');
}

function get_course_admins() {
    global $DB;

    $sql = "SELECT DISTINCT 
        u.username, 
        u.firstname, 
        u.lastname
    FROM {local_obu_course} c 
    INNER JOIN {user} u ON u.username = c.administrator";

    return $DB->get_records_sql($sql);
}

function is_programme($course_code) {
    global $DB;

	$course = $DB->get_record('local_obu_course', array('code' => $course_code), 'programme', IGNORE_MISSING);
	if (($course == NULL) || ($course->programme == 0)) {
		return false;
	}

	return true;
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

function read_user_by_username($username) {
    global $DB;

	return $DB->get_record('user', array('username' => $username), '*', IGNORE_MISSING);
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
	$user->phone2 = $form_data->phone2;
	$user->city = $form_data->city;

	user_update_user($user, false, true);
	profile_save_data($user); // Save custom profile data
}

function get_applicants_by_first_name($firstname) {
    global $DB;

    $sql = 'SELECT a.userid, u.firstname, u.lastname '
        . 'FROM {local_obu_applicant} a '
        . 'JOIN {user} u ON u.id = a.userid '
        . 'WHERE u.firstname LIKE "' . $firstname . '%" '
        . 'ORDER BY u.firstname, u.lastname, a.userid';

    return $DB->get_records_sql($sql, array());
}

function get_applicants_by_last_name($lastname) {
    global $DB;

	$sql = 'SELECT a.userid, u.firstname, u.lastname '
		. 'FROM {local_obu_applicant} a '
		. 'JOIN {user} u ON u.id = a.userid '
		. 'WHERE u.lastname LIKE "' . $lastname . '%" '
		. 'ORDER BY u.lastname, u.firstname, a.userid';

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

function delete_applicant($user_id) {
    global $DB;

	// Delete any outstanding approvals
	$recs = $DB->get_records('local_obu_application', ['userid' => $user_id]);
	foreach ($recs as $rec) {
		$DB->delete_records('local_obu_approval', ['application_id' => $rec->id]);
	}

	// Delete any applications
	$DB->delete_records('local_obu_application', ['userid' => $user_id]);

	// Delete the applicant record
	$DB->delete_records('local_obu_applicant', ['userid' => $user_id]);

	return;
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
	if ($form_data->address_1 != '') { // Check we are updating full Contact Details and not Sign-in sub-set
		$record->address_1 = $form_data->address_1;
		$record->address_2 = $form_data->address_2;
		$record->address_3 = $form_data->address_3;
		$record->city = $form_data->city;
		$record->postcode = $form_data->postcode;
		$record->domicile_code = $form_data->domicile_code;
		$record->domicile_country = $form_data->domicile_country;
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
    $record->birth_code = $form_data->birth_code;
    $record->birth_country = $form_data->birth_country;
    $record->birthdate = $form_data->birthdate;
    $record->nationality_code = $form_data->nationality_code;
    $record->nationality = $form_data->nationality;
    $record->gender = $form_data->gender;
    $record->residence_code = $form_data->residence_code;
    $record->residence_area = $form_data->residence_area;
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
    $record->studying = $form_data->studying;
    if ($record->studying == 1) {
		$record->student_number = $form_data->student_number;
	} else {
		$record->student_number = '';
	}
    $record->studying = $form_data->studying;
    $record->statement = $form_data->statement;
    $record->course_update = time();

	return $DB->update_record('local_obu_applicant', $record);
}

function write_visa_requirement($user_id, $visa_requirement) {
	global $DB;

	$record = read_applicant($user_id, true); // Must already exist

	// Only update requirement/data if necessary
	if ($record->visa_requirement == $visa_requirement) {
		return;
	}

	$record->visa_requirement = $visa_requirement;
	$record->visa_data = '';
	$record->course_update = time();

	return $DB->update_record('local_obu_applicant', $record);
}

function write_visa_data($user_id, $visa_data) {
	global $DB;

	$record = read_applicant($user_id, true); // Must already exist

	// Update the visa data for the applicant's course
    $record->visa_data = $visa_data;
    $record->course_update = time();

	return $DB->update_record('local_obu_applicant', $record);
}

function write_visa_data_by_id($application_id, $visa_data) {
    global $DB;

    $application = read_application($application_id, true);

    $application->visa_data = $visa_data;

    return update_application($application);
}

function write_supplement_data($user_id, $supplement_data) {
	global $DB;

	$record = read_applicant($user_id, true); // Must already exist

	// Update the supplement data for the applicant's course
    $record->supplement_data = $supplement_data;
    $record->course_update = time();

	return $DB->update_record('local_obu_applicant', $record);
}

function write_supplement_data_by_id($application_id, $supplement_data) {
    global $DB;

    $application = read_application($application_id, true);

    $application->supplement_data = $supplement_data;

    return update_application($application);
}

function read_application($application_id, $must_exist = true) {
    global $DB;

	$application = $DB->get_record('local_obu_application', array('id' => $application_id), '*', $must_exist);

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
	$record->city = $applicant->city;
	$record->domicile_code = $applicant->domicile_code;
	$record->domicile_country = $applicant->domicile_country;
	$record->postcode = $applicant->postcode;
	$record->home_phone = $user->phone1;
	$record->mobile_phone = $user->phone2;
	$record->email = $user->email;

	// Profile
	$record->birth_code = $applicant->birth_code;
    $record->birth_country = $applicant->birth_country;
    $record->birthdate = $applicant->birthdate;
	$record->nationality_code = $applicant->nationality_code;
    $record->nationality = $applicant->nationality;
    $record->gender = $applicant->gender;
	$record->residence_code = $applicant->residence_code;
    $record->residence_area = $applicant->residence_area;
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
    $record->studying = $applicant->studying;
    $record->student_number = $applicant->student_number;
    $record->statement = $applicant->statement;
    $record->visa_requirement = $applicant->visa_requirement;
	if ($record->visa_requirement != '') { // There should be visa data
		$record->visa_data = $applicant->visa_data;
	}
	$course = read_course_record($record->course_code);
	if ($course->supplement != '') { // There should be supplementary data
		$record->supplement_data = $applicant->supplement_data;
	}
	if ($course->administrator != '') {
		$record->manager_email = $course->administrator . '@brookes.ac.uk';
	}

	// Funding
	$record->self_funding = $form_data->self_funding;
	if ($record->self_funding == 0) {
		$record->funding_id = $form_data->funding_organisation;
		if ($record->funding_id == 0) { // 'Other Organisation'
			$record->funding_organisation = '';
			$record->funder_email = $form_data->funder_email; // Must have been given
		} else { // A known organisation with a fixed email address
			$organisation = read_organisation($record->funding_id);
			$record->funding_organisation = $organisation->name;
			$record->funder_email = $organisation->email;
		}
	}

	// Declaration
	if (isset($form_data->declaration)) { // Only set if checked
		$record->declaration = 1;
	} else {
		$record->declaration = 0;
	}

    $record->application_date = time();

	return $DB->insert_record('local_obu_application', $record); // The remaining fields will have default values
}

function get_name_and_email($current_user_id, $id) {
    global $DB;

    try {
        $user = $DB->get_record('user', array("id" => $id), "firstname, lastname, email", MUST_EXIST);
    }
    catch (dml_exception $exception) {
        return "Unknown User";
    }

    return $user->firstname . ' ' . $user->lastname . ' (' . $user->email . ')';
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

function get_applications_for_manager($manager_username, $application_from_date = 0, $application_to_date = 0, $sort_order = '') {
    if ($manager_username == '') {
        return [];
    }
    global $DB;
    $sql = 'SELECT * FROM {local_obu_application} WHERE application_date >= ? AND application_date < ? AND manager_email LIKE ?';
    if ($sort_order != '') {
        $sql .= ' ORDER BY ' . $sort_order;
    }
    $sql .= ';';

    return $DB->get_records_sql($sql, array($application_from_date, $application_to_date, $manager_username . "%"));
}

function get_applications_for_organisation_range($organisation, $application_from_date = 0, $application_to_date = 0, $sort_order = '') {
    global $DB;

    $sql = 'SELECT * FROM {local_obu_application} WHERE application_date >= ? AND application_date < ? AND funding_organisation = ?';

    if ($sort_order != '') {
        $sql .= ' ORDER BY ' . $sort_order;
    }
    $sql .= ';';
    return $DB->get_records_sql($sql, array($application_from_date, $application_to_date, $organisation));
}

function count_applications_for_course($code) {
    global $DB;

	return $DB->count_records('local_obu_application', array('course_code' => $code));
}

function count_applications_for_funder($id) {
    global $DB;

	return $DB->count_records('local_obu_application', array('funding_id' => $id));
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
