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
 * OBU Application - List a user's applications
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

require_obu_login();

$manager = is_manager();

$user_id = optional_param('userid', 0, PARAM_INT);

$url = new moodle_url('/local/obu_application/applications.php', array('userid' => $user_id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

if (($user_id == 0) || ($user_id == $USER->id)) {
    $user = $USER;
	$currentuser = true;
	$title = get_string('my_applications', 'local_obu_application');
	$heading = get_string('my_applications', 'local_obu_application');
} else {
    $user = $DB->get_record('user', array('id' => $user_id));
    if (!$user) {
        print_error('invaliduserid');
    }
    $currentuser = false; // If we're looking at someone else's forms we may need to lock/remove some UI elements
	$heading = get_string('applications', 'local_obu_application') . ': ' . $user->firstname . ' ' . $user->lastname;
	$PAGE->navbar->add($heading);
}

$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);

// The page contents
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$process = new moodle_url('/local/obu_application/process.php');

$applications = get_applications($user->id); // get all applications for the given user
foreach ($applications as $application) {
	get_application_status($USER->id, $application, $text, $button, $manager); // get the approval trail and the next action (from this user's perspective)
	if (($button != 'submit') || $currentuser || $manager) {
		echo '<h4><a href="' . $process . '?source=' . urlencode('applications.php?userid=' . $user_id) . '&id=' . $application->id . '">Ref No ' . $application->id . '</a></h4>';
	} else {
		echo '<h4>Ref No ' . $application->id . '</h4>';
	}
	echo $text;
}

echo $OUTPUT->footer();
