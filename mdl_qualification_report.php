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
 * OBU Application - Return a CSV data file of applicants' highest qualifications [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Emir Kamel
 * @copyright  2025, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./mdl_qualification_report_form.php');

require_login();

$home = new moodle_url('/');
if (!local_obu_application_is_manager()) {
    redirect($home);
}

$applications_course = local_obu_application_get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;
if (!local_obu_application_is_manager()) {
    redirect($back);
}

$dir = $home . 'local/obu_application/';
$url = $dir . 'mdl_qualification_report.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('qualification_report', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$mform = new mdl_qualification_report_form();

if ($mform->is_cancelled()) {
    redirect($back);
}

if ($mform_data = $mform->get_data()) {
    $applicants = local_obu_application_get_qualification_report_info();
    if (empty($applicants)) {
        $message = get_string('no_applicants', 'local_obu_application');
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=HLS_' . get_string('qualification_report', 'local_obu_application') . '.csv');
        $fp = fopen('php://output', 'w');
        $first_record = true;

        foreach ($applicants as $applicant) {
            if (isset($applicant->highest_prof_qualification)) {
                $fields = array();
                $fields['Title'] = $applicant->title;
                $fields['First name'] = $applicant->firstname;
                $fields['Last name'] = $applicant->lastname;
                $fields['Personal email address'] = $applicant->personal_email;
                $fields['Qualification code'] = $applicant->qualification_code;
                $fields['Highest professional qualification'] = $applicant->highest_prof_qualification;
                $fields['Qualification verified?'] = $applicant->qualification_verified;

                if ($first_record) { // Write headings
                    fputcsv($fp, array_keys($fields));
                    $first_record = false;
                }
                fputcsv($fp, $fields);
            }
        }
        fclose($fp);
        exit();
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