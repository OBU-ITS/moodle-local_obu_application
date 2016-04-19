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
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

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
    
	return $DB->get_record('local_obu_organisation', array('id' => $organisation_id), '*', MUST_EXIST);
}

function write_organisation($organisation) {
	global $DB;
	
    $record = new stdClass();
	$id = $organisation->id;
	$record->name = $organisation->name;
	$record->email = $organisation->email;
	$record->code = $organisation->code;

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
	
	return $user;	
}

function write_user($user_id, $form_data) {
	global $DB;
	
	$user = read_user($user_id);
	
	$user->username = $form_data->username;
	$user->idnumber = $form_data->idnumber;
	$user->firstname = $form_data->firstname;
	$user->lastname = $form_data->lastname;
	$user->address = $form_data->address;
	$user->city = $form_data->city;
	$user->phone1 = $form_data->phone1;
	$user->email = strtolower($form_data->email);
		
	user_update_user($user, false, true);
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

function write_profile($user_id, $form_data) {
	global $DB;
	
	$record = read_applicant($user_id, false); // May not exist yet
	if ($record === false) {
		$record = new stdClass();
		$record->id = 0;
		$record->userid = $user_id;
	}
		// Zero dates that weren't actually input
		$today = floor(time() / 86400) * 86400;
		if ($data['firstentrydate'] == $today) {
			$data['firstentrydate'] = 0;
		}
		if ($data['lastentrydate'] == $today) {
			$data['lastentrydate'] = 0;
		}
		if ($data['residencedate'] == $today) {
			$data['residencedate'] = 0;
		}
		
		$data['lastentrydate'] = time();
		$data['residencedate'] = 0;
	
	// Update the applicant's profile fields
    $record->birthdate = $form_data->birthdate;
    $record->birthcountry = $form_data->birthcountry;
	$today = floor(time() / 86400) * 86400; // Time stamp at 00:00 today
    if ($form_data->firstentrydate == $today) { // Not actually entered
		$record->firstentrydate = 0;
	} else {
		$record->firstentrydate = $form_data->firstentrydate;
	}
    if ($form_data->lastentrydate == $today) { // Not actually entered
		$record->lastentrydate = 0;
	} else {
		$record->lastentrydate = $form_data->lastentrydate;
	}
    if ($form_data->residencedate == $today) { // Not actually entered
		$record->residencedate = 0;
	} else {
		$record->residencedate = $form_data->residencedate;
	}
    $record->support = $form_data->support;
    $record->p16school = $form_data->p16school;
    $record->p16schoolperiod = $form_data->p16schoolperiod;
    $record->p16fe = $form_data->p16fe;
    $record->p16feperiod = $form_data->p16feperiod;
    $record->training = $form_data->training;
    $record->trainingperiod = $form_data->trainingperiod;
    $record->prof_level = $form_data->prof_level;
    $record->prof_award = $form_data->prof_award;
    $record->prof_date = $form_data->prof_date;
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
	$record->title = $user->idnumber;
	$record->firstname = $user->firstname;
	$record->lastname = $user->lastname;
	$record->address = $user->address;
	$record->postcode = $user->city;
	$record->phone = $user->phone1;
	$record->email = $user->email;

	// Profile
    $record->birthdate = $applicant->birthdate;
    $record->birthcountry = $applicant->birthcountry;
    $record->firstentrydate = $applicant->firstentrydate;
    $record->lastentrydate = $applicant->lastentrydate;
    $record->residencedate = $applicant->residencedate;
    $record->support = $applicant->support;
    $record->p16school = $applicant->p16school;
    $record->p16schoolperiod = $applicant->p16schoolperiod;
    $record->p16fe = $applicant->p16fe;
    $record->p16feperiod = $applicant->p16feperiod;
    $record->training = $applicant->training;
    $record->trainingperiod = $applicant->trainingperiod;
    $record->prof_level = $applicant->prof_level;
    $record->prof_award = $applicant->prof_award;
    $record->prof_date = $applicant->prof_date;
    $record->emp_place = $applicant->emp_place;
    $record->emp_area = $applicant->emp_area;
    $record->emp_title = $applicant->emp_title;
    $record->emp_prof = $applicant->emp_prof;
    $record->prof_reg_no = $applicant->prof_reg_no;
    $record->criminal_record = $applicant->criminal_record;
	
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
    $record->manager_email = $form_data->email;
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

function get_applications($user_id) {
    global $DB;
    
	return $DB->get_records('local_obu_application', array('userid' => $user_id), 'application_date DESC');
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
