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
 * OBU Application - Visa page
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2021, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');
require_once('./visa_form.php');

require_obu_login();

$home = new moodle_url('/local/obu_application/');
$url = $home . 'visa.php';
$visa = $home . 'visa_supplement.php';
$supplement = $home . 'supplement.php';
$apply = $home . 'apply.php';

$PAGE->set_title($CFG->pageheading . ': ' . get_string('apply', 'local_obu_application'));
$PAGE->set_url($url);

$message = '';

$record = read_applicant($USER->id, false);
if (!isset($record->course_code) || ($record->course_code === '')) { // Must complete the course first
	$message = get_string('complete_course', 'local_obu_application');
}

if ($record->nationality_code == 'GB') {
	$message = get_string('visa_not_required', 'local_obu_application');
}

if (($record->visa_requirement == 'Tier 4') || ($record->visa_requirement == 'Student')) {
	$visa_requirement = '1';
} else if (($record->visa_requirement == 'Tier 2') || ($record->visa_requirement == 'Other')) {
	$visa_requirement = '2';
} else if ($record->visa_requirement == 'InterDL') {
    $visa_requirement = '3';
} else {
	$visa_requirement = '0';
}

$parameters = [
	'visa_requirement' => $visa_requirement
];

$mform = new visa_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($home);
}

if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save_continue', 'local_obu_application')) {
		if ($mform_data->visa_requirement == '1') {
			$visa_requirement = 'Student';
		} else if ($mform_data->visa_requirement == '2') {
			$visa_requirement = 'Other';
		} else if ($mform_data->visa_requirement == '3') {
            $visa_requirement = 'InterDL';
        } else {
			$visa_requirement = '';
		}
		write_visa_requirement($USER->id, $visa_requirement);
		if ($visa_requirement != '') {
			redirect($visa);
		} else {
			$course = read_course_record($record->course_code);
			if ($course->supplement != '') {
				redirect($supplement); 
			} else {
				redirect($apply);
			}
		}
    }
}	

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('visa_requirement', 'local_obu_application'));

if ($message) {
    notice($message, $home);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
