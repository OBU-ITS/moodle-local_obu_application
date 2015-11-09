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
