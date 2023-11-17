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
 * OBU Application - Amend the Funder [Moodle]
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
require_once('./mdl_amend_funder_form.php');
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

$url = $home . 'local/obu_application/mdl_amend_funder.php?id=' . $application->id;
$process = $home . 'local/obu_application/mdl_process.php?id=' . $application->id;
if (($application->approval_level != 1) || ($application->approval_state != 0)) { // Must be awaiting approval/rejection by Administrator
	redirect($process);
}

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('application', 'local_obu_application', $application->id);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->set_url($url);
$PAGE->navbar->add($heading);

$message = '';

$parameters = [
	'record' => $application,
	'organisations' => get_organisations()
];
	
$mform = new mdl_amend_funder_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($process);
}

if ($mform_data = $mform->get_data()) {
		
	// Update the application's funder fields
	$application->funding_id = $mform_data->funding_organisation;
	if ($application->funding_id == 0) { // 'Other Organisation'
		$application->funding_organisation = '';
		$application->funder_email = $mform_data->funder_email; // Must have been given
	} else { // A known organisation with a fixed email address
		$organisation = read_organisation($application->funding_id);
		$application->funding_organisation = $organisation->name;
		$application->funder_email = $organisation->email;
	}
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
