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
 * OBU Application - Finalise application and apply
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
require_once('./apply_form.php');

local_obu_application_require_obu_login();

$home = new moodle_url('/local/obu_application/');
$url = $home . 'apply.php';
$process_url = $home . 'process.php';

$PAGE->add_body_class('limitedwidth');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_url($url);

$message = '';
$record = local_obu_application_read_applicant($USER->id, false);

// Must have completed the profile
if (($record === false)
	|| ($record->birth_code == '') || ($record->birth_code == 'ZZ')
	|| ($record->nationality_code == '') || ($record->nationality_code == 'ZZ')
	|| ($record->residence_code == '') || ($record->residence_code == 'ZZ')) {
		$message = get_string('complete_profile', 'local_obu_application');
} else if (!isset($record->course_code) || ($record->course_code === '') || ($record->course_code === '0')) { // They must complete the course
	$message = get_string('complete_course', 'local_obu_application');
}

if (($message == '') && ($record->visa_requirement != '')) {
	$supplement = local_obu_application_get_supplement_form($record->visa_requirement, is_siteadmin());
	if (!$supplement) {
		$message = get_string('invalid_data', 'local_obu_application'); // Shouldn't be here
	} else {
        local_obu_application_unpack_supplement_data($record->visa_data, $fields);
		if (($fields['supplement'] != $supplement->ref) || ($fields['version'] != $supplement->version)) {
			$message = get_string('complete_course', 'local_obu_application'); // Shouldn't be here
		}
	}
}

if ($message == '') {
	$course = local_obu_application_read_course_record($record->course_code);
	if ($course->supplement != '') {
		$supplement = local_obu_application_get_supplement_form($course->supplement, is_siteadmin());
		if (!$supplement) {
			$message = get_string('invalid_data', 'local_obu_application'); // Shouldn't be here
		} else {
            local_obu_application_unpack_supplement_data($record->supplement_data, $fields);
			if (($fields['supplement'] != $supplement->ref) || ($fields['version'] != $supplement->version)) {
				$message = get_string('complete_course', 'local_obu_application'); // Shouldn't be here
			}
		}
	}
}

$parameters = [
	'organisations' => local_obu_application_get_organisations(),
	'record' => $record
];

$mform = new apply_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($home);
}
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('apply', 'local_obu_application')) {
		$application_id = local_obu_application_write_application($USER->id, $mform_data);
		redirect($process_url . '?id=' . $application_id); // Kick-off the processing
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
        <h1><?php echo get_string('apply', 'local_obu_application') ?></h1>
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
