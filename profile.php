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
 * OBU Application - Profile page
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');
require_once('./profile_form.php');

require_obu_login();

$url = new moodle_url('/local/obu_application/');

$PAGE->set_title($CFG->pageheading . ': ' . get_string('profile', 'local_obu_application'));

$PAGE->set_url('/local/obu_application/profile.php');

$record = read_applicant($USER->id, false); // May not exist yet
if (($record === false) || ($record->domicile_code == '') || ($record->domicile_code == 'ZZ')) { // Must complete the contact details first
	$message = get_string('complete_contact_details', 'local_obu_application');
} else {
	$message = '';
}

$nations = get_nations();
$areas = get_areas();
$parameters = [
	'record' => $record,
	'nations' => $nations,
	'areas' => $areas,
	'default_birth_code' => 'GB',
	'default_nationality_code' => 'GB',
	'default_residence_code' => 'XF'
];

$mform = new profile_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($url);
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save', 'local_obu_application')) {
		$mform_data->birth_country = $nations[$mform_data->birth_code];
		$mform_data->nationality = $nations[$mform_data->nationality_code];
		$mform_data->residence_area = $areas[$mform_data->residence_code];
		write_profile($USER->id, $mform_data);
		redirect($url);
    }
}	

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('profile', 'local_obu_application'));

if ($message) {
    notice($message, $url);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
