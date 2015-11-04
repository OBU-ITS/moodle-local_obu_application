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
	
    $record = new stdClass();
    $record->userid = $user_id;
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
    $record->update_date = time();

	$profile = read_profile($record->userid);
	if ($profile !== false) {
		$id = $profile->id;
		$record->id = $id;
		$DB->update_record('local_obu_profile', $record);
	} else {		
		$id = $DB->insert_record('local_obu_profile', $record);
	}
	
	return $id;
}

function read_profile($user_id) {
    global $DB;
    
	$profile = $DB->get_record('local_obu_profile', array('userid' => $user_id), '*');
	
	return $profile;	
}
