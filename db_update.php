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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
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
	$user->email = $form_data->email;
		
	user_update_user($user, false, true);
}

function read_user($user_id) {
    global $DB;
    
	$user = $DB->get_record('user', array('id' => $user_id), '*', MUST_EXIST);
	
	return $user;	
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
    $record->birthcountry = $form_data->birthcountry;
    $record->firstentrydate = $form_data->firstentrydate;
    $record->lastentrydate = $form_data->lastentrydate;
    $record->residencedate = $form_data->residencedate;
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
    $record->award_name = $form_data->award_name;
    $record->start_date = $form_data->start_date;
    $record->module_1_no = $form_data->module_1_no;
    $record->module_1_name = $form_data->module_1_name;
    $record->module_2_no = $form_data->module_2_no;
    $record->module_2_name = $form_data->module_2_name;
    $record->module_3_no = $form_data->module_3_no;
    $record->module_3_name = $form_data->module_3_name;
    $record->statement = $form_data->statement;
    $record->course_update = time();

	return $DB->update_record('local_obu_applicant', $record);
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
    $record->award_name = $applicant->award_name;
    $record->start_date = $applicant->start_date;
    $record->module_1_no = $applicant->module_1_no;
    $record->module_1_name = $applicant->module_1_name;
    $record->module_2_no = $applicant->module_2_no;
    $record->module_2_name = $applicant->module_2_name;
    $record->module_3_no = $applicant->module_3_no;
    $record->module_3_name = $applicant->module_3_name;
    $record->statement = $applicant->statement;
	
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

function read_application($application_id) {
    global $DB;
    
	$application = $DB->get_record('local_obu_application', array('id' => $application_id), '*', MUST_EXIST);
	
	return $application;	
}

function get_applications($user_id) {
    global $DB;
    
	return $DB->get_records('local_obu_application', array('userid' => $user_id), 'application_date DESC');
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

function get_approvals($approver_email) {
    global $DB;

	$conditions = array();
	
	if ($approver_email != '') {
		$conditions['approver'] = $approver_email;
	}

	return $DB->get_records('local_obu_approval', $conditions, 'request_date ASC');
}

function delete_approval($approval) {
    global $DB;
    
	if ($approval->id != 0) {
		$DB->delete_records('local_obu_approval', array('id' => $approval->id));
	}
}
