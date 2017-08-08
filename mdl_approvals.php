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
 * OBU Application - List all approvals requested (excluding HLS) or just those for a given approver (including HLS) [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2017, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');

require_login();

$context = context_system::instance();
require_capability('local/obu_application:manage', $context);

$approver_username = optional_param('approver', '', PARAM_TEXT);
if ($approver_username) {
	$approver = get_complete_user_data('username', $approver_username);
	$approver_email = $approver->email;
	$url = new moodle_url('/local/obu_application/mdl_approvals.php', array('approver' => $approver_username));
	$heading = get_string('approvals', 'local_obu_application') . ': ' . $approver->firstname . ' ' . $approver->lastname;
} else {
	$approver = get_complete_user_data('username', 'hls'); // So that we can exclude them later
	$approver_email = '';
	$url = new moodle_url('/local/obu_application/mdl_approvals.php');
	$heading = get_string('approvals', 'local_obu_application');
}

$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$process = new moodle_url('/local/obu_application/mdl_process.php');
$redirect = new moodle_url('/local/obu_application/mdl_redirect.php');
$approvals = get_approvals($approver_email); // get outstanding approval requests

foreach ($approvals as $approval) {
	if (($approver_email != '') || ($approval->approver != $approver->email)) {
		$application = read_application($approval->application_id);
		get_application_status($USER->id, $application, $text, $button); // get the approval trail and the next action (from the user's perspective)
		echo '<h4><a href="' . $process . '?id=' . $application->id . '">' . $application->course_code . ' ' . $application->course_name . ' (' . $application->lastname . ' - HLS/' . $application->id . ')</a></h4>';
		echo $text;
		if (has_capability('local/obu_application:update', $context) && ($application->approval_level < 3)) { // Can't redirect away from final HLS approval/processing
			echo '<p><a href="' . $redirect . '?id=' . $application->id . '">' . get_string('redirect_application', 'local_obu_application') . '</a></p>';
		}
	}
}

echo $OUTPUT->footer();


