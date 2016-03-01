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
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');
require_once('./course_form.php');

require_obu_login();

$url = new moodle_url('/local/obu_application/');

$PAGE->set_title($CFG->pageheading . ': ' . get_string('course', 'local_obu_application'));

// HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

$PAGE->set_url($url . 'course.php');
$PAGE->set_context(context_system::instance());

$record = read_applicant($USER->id, false);
if ($record === false) { // Must complete the profile first
	$message = get_string('complete_profile', 'local_obu_application');
} else {
	$message = '';
}

$courses = get_course_names();

$parameters = [
	'courses' => $courses,
	'record' => $record
];

$mform = new course_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($url);
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save', 'local_obu_application')) {
		$course = read_course_record($mform_data->course_code);
		$mform_data->course_name = $course->name;
		write_course($USER->id, $mform_data);
		if ($course->supplement != '') {
			$url .= 'supplement.php'; 
		}
		redirect($url);
    }
}	

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course', 'local_obu_application'));

if ($message) {
    notice($message, $url);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
