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
 * OBU Application - Process an application [Moodle]
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
require_once('./process_view.php');
require_once($CFG->libdir . '/moodlelib.php');

require_login();

$context = context_system::instance();
$manager = has_capability('local/obu_application:manage', $context);
$home = new moodle_url('/');

// We only handle an existing application (id given)
if (!isset($_REQUEST['id'])) {
	redirect($home);
}

$application = read_application($_REQUEST['id']);
if ($application === false) {
	redirect($home);
}
$process = $home . 'local/obu_application/mdl_process.php?id=' . $application->id;

$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('plugintitle', 'local_obu_application') . ': ' . get_string('process', 'local_obu_application'));
$PAGE->set_url($process);
$PAGE->set_context($context);
$PAGE->navbar->add(get_string('application', 'local_obu_application', $application->id));

$message = '';

// If not awaiting approval by someone, display the current status (prominently)
if (($application->approval_state == 0) && ($application->approval_level == 0)) { // Application not yet submitted
	if ($USER->id != $application->userid) { // no-one else can look at your unsubmitted applications
		$message = get_string('application_unavailable', 'local_obu_application');
	}
	$status_text = get_string('status_not_submitted', 'local_obu_application');
	
	// We currently auto-submit the application to avoid a two-stage process for the applicant
	update_workflow($application);
	$status_text = '';
	
} else if ($application->authorisation_state == 1) { // Application rejected
	$status_text = get_string('status_rejected', 'local_obu_application');
} else if ($application->authorisation_state == 2) { // Application processed
	$status_text = get_string('status_processed', 'local_obu_application');
} else {
	$status_text = '';
}
if ($status_text) {
	$status_text = '<h3>' . $status_text . '</h3>';
}

get_application_status($USER->id, $application, $text, $button_text); // get the approval trail and the next action (from user's perspective)
$status_text .= $text;

if ($button_text != 'approve') { // If not the next approver, check that this user can view the application
	if (!$manager && ($USER->id != $application->userid)) {
		$message = get_string('application_unavailable', 'local_obu_application');
	}
}

$parameters = [
	'record' => $application,
	'status_text' => $status_text,
	'button_text' => $button_text
];
	
$mform = new process_view(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($home);
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton != get_string('continue', 'local_obu_application')) {
		if ($mform_data->rejectbutton != get_string('reject', 'local_obu_application')) {
			update_workflow($application, true, $mform_data->comment);
		} else {
			update_workflow($application, false, $mform_data->comment);
		}
	}
	redirect($home);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('application', 'local_obu_application', $application->id));

if ($message) {
    notice($message, $home);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
