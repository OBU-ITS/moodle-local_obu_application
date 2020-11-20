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
 * OBU Application - Finalise application and apply
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');
require_once('./apply_form.php');

require_obu_login();

$home = new moodle_url('/local/obu_application/');
$url = $home . 'apply.php';
$process_url = $home . 'process.php';

$PAGE->set_title($CFG->pageheading . ': ' . get_string('apply', 'local_obu_application'));

$PAGE->set_url($url);

$message = '';
$record = read_applicant($USER->id, false);
if (($record === false) || ($record->residence_code == '')) { // Must have completed the profile
	$message = get_string('complete_profile', 'local_obu_application');
}
else if (!isset($record->course_code) || ($record->course_code === '')) { // They must complete the course
	$message = get_string('complete_course', 'local_obu_application');
}

if (($message == '') && ($record->visa_requirement != '')) {
	$supplement = get_supplement_form($record->visa_requirement, is_siteadmin());
	if (!$supplement) {
		$message = get_string('invalid_data', 'local_obu_application'); // Shouldn't be here
	} else {
		unpack_supplement_data($record->visa_data, $fields);
		if (($fields['supplement'] != $supplement->ref) || ($fields['version'] != $supplement->version)) {
			$message = get_string('complete_course', 'local_obu_application'); // Shouldn't be here
		}
	}
}

if ($message == '') {
	$course = read_course_record($record->course_code);
	if ($course->supplement != '') {
		$supplement = get_supplement_form($course->supplement, is_siteadmin());
		if (!$supplement) {
			$message = get_string('invalid_data', 'local_obu_application'); // Shouldn't be here
		} else {
			unpack_supplement_data($record->supplement_data, $fields);
			if (($fields['supplement'] != $supplement->ref) || ($fields['version'] != $supplement->version)) {
				$message = get_string('complete_course', 'local_obu_application'); // Shouldn't be here
			}
		}
	}
}

$parameters = [
	'organisations' => get_organisations(),
	'record' => $record
];

$mform = new apply_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($home);
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('apply', 'local_obu_application')) {
		$application_id = write_application($USER->id, $mform_data);
		redirect($process_url . '?id=' . $application_id); // Kick-off the processing
    }
}	

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('apply', 'local_obu_application'));

if ($message) {
    notice($message, $home);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
