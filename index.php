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
 * OBU Application - Menu page
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');

// Try to prevent searching for sites that allow sign-up.
if (!isset($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = '';
}
$CFG->additionalhtmlhead .= '<meta name="robots" content="noindex" />';

require_obu_login();

$process = new moodle_url('/local/obu_application/process.php');

$PAGE->set_title($CFG->pageheading . ': ' . get_string('index_page', 'local_obu_application'));

echo $OUTPUT->header();
//echo '<audio autoplay><source src="https://brookes-apps.appspot.com/say.php?' . $USER->firstname . ', please select an option." type="audio/wav"></audio>';

// Display any outstanding approvals
$approvals = get_approvals($USER->email); // get outstanding approval requests
if ($approvals) {
	echo '<h2>' . get_string('your_approvals', 'local_obu_application') . '</h2>';
	foreach ($approvals as $approval) {
		$application = read_application($approval->application_id);
		$application_title = $application->firstname . ' ' . $application->lastname . ' (Application Ref HLS/' . $application->id . ')';
		echo '<h4><a href="' . $process . '?id=' . $application->id . '">' . $application_title . '</a></h4>';
		get_application_status($USER->id, $application, $text, $button); // get the approval trail and the next action (from the user's perspective)
		echo $text;
	}
} else {
	echo get_string('page_content', 'local_obu_application');
}

// Display applications submitted
$applications = get_applications($USER->id); // get all applications for the user
if ($applications) {
	echo '<h2>' . get_string('your_applications', 'local_obu_application') . '</h2>';
	foreach ($applications as $application) {
		get_application_status($USER->id, $application, $text, $button); // get the approval trail and the next action (from this user's perspective)
		$application_title = $application->course_code . ' ' . $application->course_name . ' (Application Ref HLS/' . $application->id . ')';
		if (($button != 'submit') || $currentuser || $manager) {
			echo '<h4><a href="' . $process . '?id=' . $application->id . '">' . $application_title . '</a></h4>';
		} else {
			echo '<h4>' . $application_title . '</h4>';
		}
		echo $text;
	}
}

echo $OUTPUT->footer();
