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
 * OBU Application - Course page
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
require_once('./course_form.php');

require_obu_login();

$home = new moodle_url('/local/obu_application/');
$url = $home . 'course.php';
$visa = $home . 'visa.php';
$supplement = $home . 'supplement.php';
$apply = $home . 'apply.php';

$PAGE->set_title("HLS CPD Application Portal", false);

$PAGE->set_url($url);

$record = read_applicant($USER->id, false);
if (($record === false)
	|| ($record->birth_code == '') || ($record->birth_code == 'ZZ')
	|| ($record->nationality_code == '') || ($record->nationality_code == 'ZZ')
	|| ($record->residence_code == '') || ($record->residence_code == 'ZZ')) {
		$message = get_string('complete_profile', 'local_obu_application');
} else {
	$message = '';
}

$parameters = [
	'courses' => get_course_names(),
	'dates' => get_dates(),
	'record' => $record
];

$mform = new course_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($home);
}

if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save_continue', 'local_obu_application')) {
		$course = read_course_record($mform_data->course_code);
		$mform_data->course_name = $course->name;
		write_course($USER->id, $mform_data);
		if ($record->nationality_code != 'GB') {
			redirect($visa);
		} else {
			write_visa_requirement($USER->id, '');
			if ($course->supplement != '') {
				redirect($supplement); 
			} else {
				redirect($apply);
			}
		}
    }
}	

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course', 'local_obu_application'));

if ($message) {
    notice($message, $home);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
