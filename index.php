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
require_once ('./profile_contact_details_form.php');
require_once ('./profile_personal_details_form.php');
require_once ('./profile_educational_establishments_form.php');
require_once ('./profile_professional_qualification_form.php');
require_once ('./profile_current_employment_form.php');
require_once ('./profile_professional_registration_form.php');
require_once ('./profile_criminal_record_form.php');

// Try to prevent searching for sites that allow sign-up.
if (!isset($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = '';
}
$CFG->additionalhtmlhead .= '<meta name="robots" content="noindex" />';

require_obu_login();

$process = new moodle_url('/local/obu_application/process.php');

$PAGE->set_url($CFG->httpswwwroot . '/local/obu_application/index.php');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

echo $OUTPUT->header();

$nations = get_nations();
$parameters = [
    'user' => read_user($USER->id),
    'applicant' => read_applicant($USER->id, false),
    'titles' => get_titles(),
    'nations' => $nations,
    'default_domicile_code' => 'GB',
    'record' => $record,
    'areas' => $areas,
    'default_birth_code' => 'GB',
    'default_nationality_code' => 'GB',
    'default_residence_code' => 'XF'
];

$contactDetailsForm = new profile_contact_details_form(null, $parameters);
$contactDetailsForm->display();
$personalDetailsForm = new profile_personal_details_form(null, $parameters);
$personalDetailsForm->display();
$educationalEstablishmentsForm = new profile_educational_establishments_form(null, $parameters);
$educationalEstablishmentsForm->display();
$professionalQualificationForm = new profile_professional_qualification_form(null, $parameters);
$professionalQualificationForm->display();
$currentEmploymentForm = new profile_current_employment_form(null, $parameters);
$currentEmploymentForm->display();
$professionalRegistrationForm = new profile_professional_registration_form(null, $parameters);
$professionalRegistrationForm->display();
$criminalRecordForm = new profile_criminal_record_form(null, $parameters);
$criminalRecordForm->display();

// TODO : Plan on what to do with this
//// Display any outstanding approvals
//$approvals = get_approvals($USER->email); // get outstanding approval requests
//if ($approvals) {
//	echo '<h2>' . get_string('your_approvals', 'local_obu_application') . '</h2>';
//	foreach ($approvals as $approval) {
//		$application = read_application($approval->application_id);
//		$application_title = $application->firstname . ' ' . $application->lastname . ' (Application Ref HLS/' . $application->id . ')';
//		echo '<h4><a href="' . $process . '?id=' . $application->id . '">' . $application_title . '</a></h4>';
//		get_application_status($USER->id, $application, $text, $button); // get the approval trail and the next action (from the user's perspective)
//		echo $text;
//	}
//} else {
//	echo get_string('page_content', 'local_obu_application');
//}

// TODO : Plan on what to do with this
//// Display applications submitted
//$applications = get_applications($USER->id); // get all applications for the user
//if ($applications) {
//	echo '<h2>' . get_string('your_applications', 'local_obu_application') . '</h2>';
//	foreach ($applications as $application) {
//		get_application_status($USER->id, $application, $text, $button); // get the approval trail and the next action (from this user's perspective)
//		$application_title = $application->course_code . ' ' . $application->course_name . ' (Application Ref HLS/' . $application->id . ')';
//		if (($button != 'submit') || $currentuser || $manager) {
//			echo '<h4><a href="' . $process . '?id=' . $application->id . '">' . $application_title . '</a></h4>';
//		} else {
//			echo '<h4>' . $application_title . '</h4>';
//		}
//		echo $text;
//	}
//	echo '<h4>' . get_string('amend_application', 'local_obu_application') . '</h4>';
//} else {
//	echo '<h4>' . get_config('local_obu_application', 'support') . '</h4>';
//}

echo $OUTPUT->footer();
