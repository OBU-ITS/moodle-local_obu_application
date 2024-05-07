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
 * OBU Application - Process an application
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');
require_once('./process_form.php');
require_once($CFG->libdir . '/moodlelib.php');

local_obu_application_require_obu_login();

$home = new moodle_url('/local/obu_application/');
$logout = $home . 'logout.php';

if (isset($_REQUEST['source'])) {
	$source = $_REQUEST['source'];
} else {
	$source = '';
}
$back = $home . urldecode($source);

// We only handle an existing application (id given)
if (!isset($_REQUEST['id'])) {
	redirect($back);
}

$application = local_obu_application_read_application($_REQUEST['id']);
if ($application === false) {
	redirect($back);
}

// Take managers to where they belong - Moodle
if (local_obu_application_is_manager()) {
	$url = $home . 'mdl_process.php?source=' . $source . '&id=' . $application->id;
	redirect($url);
}

$url = $home . 'process.php?source=' . $source . '&id=' . $application->id;

$title = get_string('process', 'local_obu_application');
$heading = get_string('application', 'local_obu_application', $application->id);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);

$message = '';

// If not awaiting approval by someone, display the current status (prominently)
if (($application->approval_state == 0) && ($application->approval_level == 0)) { // Application not yet submitted
	if ($USER->id != $application->userid) { // no-one else can look at your unsubmitted applications
		$message = get_string('application_unavailable', 'local_obu_application');
	}
	$status_text = get_string('status_not_submitted', 'local_obu_application');

	// We currently auto-submit the application to avoid a two-stage process for the applicant
    local_obu_application_update_workflow($application);
	$status_text = '';

} else if (($application->approval_state == 1) || ($application->approval_state == 3)) { // Application rejected or withdrawn
	$status_text = get_string('status_rejected', 'local_obu_application');
} else if ($application->approval_state == 2) { // Application processed
	$status_text = get_string('status_processed', 'local_obu_application');
} else {
	$status_text = '';
}
if ($status_text) {
	$status_text = '<h3>' . $status_text . '</h3>';
}

$manager = local_obu_application_is_manager();
$status_text .= local_obu_application_get_application_status($USER->id, $application, $manager);
$button_text = local_obu_application_get_application_button_text($USER->id, $application, $manager);

if ($button_text != 'approve') { // If not the next approver, check that this user is the applicant
	if ($USER->id != $application->userid) {
		$message = get_string('application_unavailable', 'local_obu_application');
	}
}

$parameters = [
	'source' => $source,
	'organisations' => local_obu_application_get_organisations(),
	'record' => $application,
	'status_text' => $status_text,
	'button_text' => $button_text
];

$mform = new process_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
}
else if ($mform_data = $mform->get_data()) {
	if (($button_text == 'approve') && ($mform_data->submitbutton != get_string('continue', 'local_obu_application')) // They can do something (and they want to)
		&& ($mform_data->approval_state == $application->approval_state) && ($mform_data->approval_level == $application->approval_level)) { // Check nothing happened while we were away (or they clicked twice)
		if (isset($mform_data->rejectbutton) && ($mform_data->rejectbutton == get_string('reject', 'local_obu_application'))) { // Application rejected
            redirect($home . 'reject.php?source=' . urlencode($url) . "&id=" . $application->id);
		} else {
            local_obu_application_update_workflow($application, true, $mform_data);
		}
		$approvals = local_obu_application_get_approvals($USER->email); // Any more approval requests?
		if (empty($approvals)) { // No there aren't
			redirect($logout);
		}
	}
	redirect($back);
}

echo $OUTPUT->header();

?>
    <div class="hero"></div>
    <style>
        .hero {
            position:absolute;
            top:0;
            left:0;
            height: 15vh;
            width:100%;
        }
        .hero::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url(/local/obu_application/moodle-hls-login-bg.jpg);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center 25%;
            filter: brightness(95%);
        }
        .hero-content {
            width: 100%;
            padding: 0.5rem 1.5rem;
            background-color: rgba(255,255,255,.8);
            backdrop-filter: saturate(180%) blur(20px);
            margin-bottom: 3rem;
        }
        .hero-content h1 {
            z-index: 100;
            position: relative;
            color: black;
        }
    </style>
    <div class="hero-content">
        <h1>Application</h1>
    </div>
    <section class="block_html block card mb-3" >
        <div class="card-body p-3">

<?php


if ($message) {
    notice($message, $back);
}
else {
    $mform->display();
}


?>
        </div>
    </section>

<?php

echo $OUTPUT->footer();
