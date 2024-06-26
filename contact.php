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
 * OBU Application - Contact details
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
require_once('./contact_form.php');

local_obu_application_require_obu_login();

$home = new moodle_url('/local/obu_application/');
$url = $home . 'contact.php';

$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

$PAGE->set_url($url);

$message ='';

$nations = local_obu_application_get_nations();
$parameters = [
	'user' => local_obu_application_read_user($USER->id),
	'applicant' => local_obu_application_read_applicant($USER->id, false),
	'titles' => local_obu_application_get_titles(),
	'nations' => $nations,
	'default_domicile_code' => 'GB'
];

$mform = new contact_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($home);
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save', 'local_obu_application')) {
		$mform_data->domicile_country = $nations[$mform_data->domicile_code];
        local_obu_application_write_user($USER->id, $mform_data);
        local_obu_application_write_contact_details($USER->id, $mform_data);
    }
	redirect($home);
}	

echo $OUTPUT->header();

if ($message) {
    notice($message, $home);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
