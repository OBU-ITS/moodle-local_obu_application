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
 * OBU Application - Amend the Personal Details [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./mdl_amend_details_form.php');
require_once($CFG->libdir . '/moodlelib.php');

require_login();

$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$applications_course = get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;
//if (!is_administrator()) {
//	redirect($back);
//}

if (!has_capability('local/obu_application:update', context_system::instance())) {
	redirect($back);
}

// We only allow access to an existing application (id given)
if (!isset($_REQUEST['id'])) {
	redirect($back);
}

$application = read_application($_REQUEST['id']);
if ($application === false) {
	redirect($back);
}

$url = $home . 'local/obu_application/mdl_amend_details.php?id=' . $application->id;
$process = $home . 'local/obu_application/mdl_process.php?id=' . $application->id;
if ((($application->approval_level != 1) && ($application->approval_level != 3)) || ($application->approval_state != 0)) { // Must be awaiting approval/rejection by HLS staff
	redirect($process);
}

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('application', 'local_obu_application', $application->id);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($url);
$PAGE->navbar->add($heading);

$message = '';

$nations = get_nations();
$areas = get_areas();
$parameters = [
	'record' => $application,
	'nations' => $nations,
	'areas' => $areas
];
	
$mform = new mdl_amend_details_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($process);
}

if ($mform_data = $mform->get_data()) {
		
	// Update the applications's details fields
	$application->birth_code = $mform_data->birth_code;
	$application->birth_country = $nations[$mform_data->birth_code];
	$application->birthdate = $mform_data->birthdate;
	$application->nationality_code = $mform_data->nationality_code;
	$application->nationality = $nations[$mform_data->nationality_code];
	$application->gender = $mform_data->gender;
	$application->residence_code = $mform_data->residence_code;
	$application->residence_area = $areas[$mform_data->residence_code];
	update_application($application);

	redirect($process);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($message) {
    notice($message, $process);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
