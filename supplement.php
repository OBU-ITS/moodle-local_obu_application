<?php

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
 * OBU Application - Handler for supplementary forms
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
require_once('./supplement_form.php');

require_obu_login();

$url = new moodle_url('/local/obu_application/');
$context = context_user::instance($USER->id);

$PAGE->set_title($CFG->pageheading . ': ' . get_string('course', 'local_obu_application'));

// HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

$PAGE->set_url($url . 'supplement.php');
$PAGE->set_context(context_system::instance());

$message = '';
$record = read_applicant($USER->id, false);
if ($record === false) { // Must complete the profile first
	$message = get_string('complete_profile', 'local_obu_application');
} else if (!isset($record->course_code) || ($record->course_code === '')) { // Must have completed the course
	$message = get_string('complete_course', 'local_obu_application');
} else {
	$course = read_course_record($record->course_code);
	if ($course->supplement == '') {
		$message = get_string('invalid_data', 'local_obu_application'); // No supplement required so we shouldn't be here
	} else {
		$supplement = get_supplement_form($course->supplement, is_siteadmin());
		if (!$supplement) {
			$message = get_string('invalid_data', 'local_obu_application'); // Summutsup
		}
	}
}

unpack_supplement_data($record->supplement_data, $fields);
if (!empty($fields) && (($fields['supplement'] != $supplement->ref) || ($fields['version'] != $supplement->version))) {
	$fields = array();
}

$parameters = [
	'supplement' => $supplement,
	'fields' => $fields
];
	
$mform = new supplement_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($url);
} 
else if ($mform_data = (array)$mform->get_data()) {
	$files = get_file_elements($supplement->template); // Get the list of the 'file' elements from the supplementary form's template
	$fields = array();
	foreach ($mform_data as $key => $value) {
		if ($key != 'submitbutton') { // Ignore the standard field
			if (in_array($key, $files)) { // Is this element a 'file' one?
				$file = $mform->save_stored_file($key, $context->id, 'local_obu_application', 'file', $value, '/', null, true, null); // Save it to the Moodle pool
				$fields[$key] = $file->get_itemid(); // Store the file ID
			} else {
				$fields[$key] = $value;
			}
		}
	}
	write_supplement_data($USER->id, pack_supplement_data($fields));
	redirect($url); // Perform initial form processing
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course_supplement', 'local_obu_application'));

if ($message) {
    notice($message, $home);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
