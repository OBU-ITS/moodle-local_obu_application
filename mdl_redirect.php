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
 * OBU Application - Redirect a user's application to a different approver [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./mdl_redirect_form.php');

require_login();

$context = context_system::instance();
require_capability('local/obu_application:manage', $context);

// We only handle an existing application (id given)
if (isset($_REQUEST['id'])) {
	$application_id = $_REQUEST['id'];
} else {
	echo(get_string('invalid_data', 'local_obu_application'));
	die;
}

// We may have been given the email of the new approver
if (isset($_REQUEST['approver_email'])) {
	$approver_email = $_REQUEST['approver_email'];
	$approver = get_complete_user_data('email', $approver_email);
	if ($approver) {
		$approver_name = $approver->firstname . ' ' . $approver->lastname;
	} else {
		$approver_name = 'Not Registered';
	}
} else {
	$approver_email = '';
	$approver_name = '';
}

$home = new moodle_url('/');
$dir = $home . 'local/obu_application/';
$program = $dir . 'mdl_redirect.php?id=' . $application_id;
$heading = get_string('redirect_application', 'local_obu_application');

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url($program);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($heading);

$application = read_application($application_id);
$application_title = $application->course_code . ' ' . $application->course_name . ' (Application Ref HLS/' . $application->id . ')';
get_application_status($USER->id, $application, $text, $button); // get the approval trail and the next action (from the user's perspective)
$application_status = '<h4>' . $application_title . '</h4>' . $text;

$parameters = [
	'application_id' => $application_id,
	'application_status' => $application_status,
	'approver_email' => $approver_email,
	'approver_name' => $approver_name
];

$message = '';

$mform = new mdl_redirect_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($home);
}

if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save', 'local_obu_application')) {
		if ($application->approval_level == 1) {
			$application->manager_email = $approver_email;
		} else {
			$application->funder_email = $approver_email;
		} 
		update_application($application);
		update_approver($application, $approver_email); // Update the approvals and send notification emails
		
		redirect($home);
	}
}	

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($message) {
    notice($message, $url);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
