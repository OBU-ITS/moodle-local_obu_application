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
 * OBU Application - Course page
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2021, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');
require_once('./course_form.php');

local_obu_application_require_obu_login();

$home = new moodle_url('/local/obu_application/');
$url = $home . 'course.php';
$visa = $home . 'visa.php';
$application_url = $home . 'application.php';
$supplement = $home . 'supplement.php';
$apply = $home . 'apply.php';

$PAGE->add_body_class('limitedwidth');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

$PAGE->set_url($url);

$record = local_obu_application_read_applicant($USER->id, false);
if (($record === false)
    || $record->contact_details_update == 0
    || $record->criminal_record_update == 0
    || $record->current_employment_update == 0
    || $record->edu_establishments_update == 0
    || $record->personal_details_update == 0
    || $record->pro_qualification_update == 0
    || $record->pro_registration_update == 0) {
		$message = get_string('complete_profile', 'local_obu_application');
} else {
	$message = '';
}


$courses = local_obu_application_get_course_names();
//$outside_uk_url = $home . 'outside_uk_residence.php';
$homeResidencies = array('XF', 'XH', 'XI', 'XG', 'JE', 'GG');
if (!in_array($record->residence_code, $homeResidencies)){
    $courses = array_intersect_key($courses, local_obu_application_get_courses_for_international_students());
    //redirect($outside_uk_url);
}

$parameters = [
	'courses' => $courses,
	'dates' => local_obu_application_get_course_dates(),
	'record' => $record
];

$mform = new course_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($application_url);
}

if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save_continue', 'local_obu_application')) {
		$course = local_obu_application_read_course_record($mform_data->course_code);
		$mform_data->course_name = $course->name;
        local_obu_application_write_course($USER->id, $mform_data);
		if ($record->nationality_code != 'GB' && in_array($record->residence_code, $homeResidencies)) {
			redirect($visa);
		} else {
            local_obu_application_write_visa_requirement($USER->id, '');
			if ($course->supplement != '') {
				redirect($supplement);
			} else {
				redirect($apply);
			}
		}
    }
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
            <p>
                Please complete the mandatory fields below. Detailed guidance can be <a href="application_guidance.php" target="_blank">found here</a>.
            </p>
            <hr class="divider">
            <p style="margin-bottom:0">
                If you have any queries, please contact <a href="mailto:hlscpdadmissions@brookes.ac.uk">hlscpdadmissions@brookes.ac.uk</a>.
            </p>
        </div>
    </section>
    <section class="block_html block card mb-3" >
        <div class="card-body p-3">

<?php
if ($message) {
    notice($message, $home);
}
else {
    $mform->display();
}

?>
        </div>
    </section>

<?php

echo $OUTPUT->footer();
