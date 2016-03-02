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

$url = new moodle_url('/local/obu_application/');
$process_url = new moodle_url('/local/obu_application/process.php');

$PAGE->set_title($CFG->pageheading . ': ' . get_string('apply', 'local_obu_application'));

// HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

$PAGE->set_url('/local/obu_application/apply.php');

$message = '';
$record = read_applicant($USER->id, false);
if ($record === false) { // Must have completed the profile
	$message = get_string('complete_profile', 'local_obu_application');
}
else if (!isset($record->course_code) || ($record->course_code === '')) { // They must complete the course
	$message = get_string('complete_course', 'local_obu_application');
} else {
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
	'record' => $record
];

$mform = new apply_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($url);
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('apply', 'local_obu_application')) {
		$application_id = write_application($USER->id, $mform_data);
		redirect($process_url . '?id=' . $application_id); // Kick-off the workflow process
    }
}	

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('apply', 'local_obu_application'));

if ($message) {
    notice($message, $url);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
