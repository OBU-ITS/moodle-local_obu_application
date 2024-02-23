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
 * @copyright  2018, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./process_form.php');
require_once($CFG->libdir . '/moodlelib.php');

require_login();

$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$applications_course = get_applications_course();
require_login($applications_course);
$source = '';
if (isset($_REQUEST['source'])) {
	$source = $_REQUEST['source'];
}
if ($source) {
	$back = $home . 'local/obu_application/' . urldecode($source);
} else {
	$back = $home . 'course/view.php?id=' . $applications_course;
}

if (!has_capability('local/obu_application:update', context_system::instance())) {
	redirect($back);
}

// We only handle an existing application (id given)
if (!isset($_REQUEST['id'])) {
	redirect($back);
}

$application = read_application($_REQUEST['id']);
if ($application === false) {
	redirect($back);
}

$url = $home . 'local/obu_application/mdl_process.php?source=' . $source . '&id=' . $application->id;

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('application', 'local_obu_application', $application->id);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

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

} else if ($application->approval_state == 1) { // Application rejected
	$status_text = get_string('status_rejected', 'local_obu_application');
} else if ($application->approval_state == 2) { // Application processed
	$status_text = get_string('status_processed', 'local_obu_application');
} else if ($application->approval_state == 3) { // Application withdrawn
	$status_text = get_string('status_withdrawn', 'local_obu_application');
} else {
	$status_text = '';
}
if ($status_text) {
	$status_text = '<h3>' . $status_text . '</h3>';
}

get_application_status($USER->id, $application, $text, $button_text, true); // get the approval trail and the next action (from user's perspective)
$status_text .= $text;

$redirect = new moodle_url('/local/obu_application/mdl_redirect.php');
if (has_capability('local/obu_application:update', context_system::instance()) && ($application->approval_level < 3)) { // Can't redirect away from final HLS approval/processing
    $status_text .= '<p><a href="' . $redirect . '?id=' . $application->id . '">' . get_string('redirect_application', 'local_obu_application') . '</a></p>';
}

$parameters = [
	'source' => $source,
	'organisations' => get_organisations(),
	'record' => $application,
	'status_text' => $status_text,
	'button_text' => $button_text
];

$mform = new process_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
}

if ($mform_data = $mform->get_data()) {
	if (isset($mform_data->submitbutton) && ($mform_data->submitbutton != get_string('continue', 'local_obu_application'))) {
		update_workflow($application, true, $mform_data); // Approved / Revoked / Reinstated
	} else if (isset($mform_data->rejectbutton) && ($mform_data->rejectbutton == get_string('reject', 'local_obu_application'))) {
        redirect($home . 'local/obu_application/mdl_reject.php?source=' . urlencode($url) . "&id=" . $application->id);
	} else if (isset($mform_data->withdrawbutton) && ($mform_data->withdrawbutton == get_string('withdraw', 'local_obu_application'))) {
		update_workflow($application, false, $mform_data); // Withdrawn
	} else if (isset($mform_data->amenddetailsbutton) && ($mform_data->amenddetailsbutton == get_string('amend_details', 'local_obu_application'))) {
		redirect($home . 'local/obu_application/mdl_amend_details.php?id=' . $application->id); // Amend the personal details
	} else if (isset($mform_data->amendcoursebutton) && ($mform_data->amendcoursebutton == get_string('amend_course', 'local_obu_application'))) {
		redirect($home . 'local/obu_application/mdl_amend_course.php?id=' . $application->id); // Amend the course
	} else if (isset($mform_data->amendsupplementdocbutton) && ($mform_data->amendsupplementdocbutton == get_string('amend_supplement_documents', 'local_obu_application'))) {
        redirect($home . 'local/obu_application/mdl_amend_supplement.php?id=' . $application->id); // Amend the supplementary document
    } else if (isset($mform_data->amendvisabutton) && ($mform_data->amendvisabutton == get_string('amend_visa', 'local_obu_application'))) {
        redirect($home . 'local/obu_application/mdl_amend_visa.php?id=' . $application->id); // Amend the supplementary document
    } else if (isset($mform_data->amendfunderbutton) && ($mform_data->amendfunderbutton == get_string('amend_funder', 'local_obu_application'))) {
		redirect($home . 'local/obu_application/mdl_amend_funder.php?id=' . $application->id); // Amend the funder
	} else if (isset($mform_data->amendfundingbutton) && ($mform_data->amendfundingbutton == get_string('amend_funding', 'local_obu_application'))) {
		redirect($home . 'local/obu_application/mdl_amend_funding.php?id=' . $application->id); // Amend the funding
	}

	redirect($back);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($message) {
    notice($message, $back);
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
