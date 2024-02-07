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
 * OBU Application - Return a CSV data file of a manager's courses applications data [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Emir Kamel
 * @copyright  2023, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./mdl_organisation_report_form.php');

require_login();

$home = new moodle_url('/');
if (!is_manager()) {
    redirect($home);
}

$applications_course = get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;
if (!is_manager()) {
    redirect($back);
}

$dir = $home . 'local/obu_application/';
$url = $dir . 'mdl_organisation_report.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('organisation_report', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$parameters = [
    'organisations' => get_organisations()
];

$mform = new mdl_organisation_report_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
}

if ($mform_data = $mform->get_data()) {
    $applications = [];

    if ($mform_data->application_date == $mform_data->application_second_date){
        $mform_data->application_second_date = strtotime('+1 day', $mform_data->application_second_date);
    }
    $applications = get_applications_for_organisation_range($mform_data->organisation1, $mform_data->application_date, $mform_data->application_second_date);
    //if ($mform_data->organisation2 != get_string('select', 'local_obu_application') && $mform_data->organisation2 != "No selection"){
        $applications = get_applications_for_organisation_range($mform_data->organisation2, $mform_data->application_date, $mform_data->application_second_date);
    //}
    //if ($mform_data->organisation3 != get_string('select', 'local_obu_application') && $mform_data->organisation3 != "No selection"){
        $applications = get_applications_for_organisation_range($mform_data->organisation3, $mform_data->application_date, $mform_data->application_second_date);
    //}

    if (empty($applications)) {
        $message = get_string('no_applications', 'local_obu_application');
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=HLS_' . get_string('organisations_report', 'local_obu_application') . '_' .
            $mform_data->organisation . '_' . date("Ymd", $mform_data->application_date) . '-' . date("Ymd", $mform_data->application_second_date) . '.csv');
        $fp = fopen('php://output', 'w');
        $first_record = true;
        foreach ($applications as $application) {
            if ($application->self_funding == 1){
                continue;
            }
            $fields = array();
            $fields['Form_Id'] = 'HLS/' . $application->id;
            $fields['Title'] = $application->title;
            $fields['First_Name'] = $application->firstname;
            $fields['Surname'] = $application->lastname;
            $fields['Course'] = $application->course_code . ': ' . $application->course_name;
            $fields['Course_Date'] = $application->course_date;
            $fields['Funding_Organisation'] = $application->funding_organisation;
            $fields['Funder_Email'] = $application->funder_email;

            if ($application->approval_state == 1) {
                $fields['Status'] = get_string('rejected', 'local_obu_application');
            } else if ($application->approval_state == 2) {
                $fields['Status'] = get_string('approved', 'local_obu_application');
            } else if ($application->approval_state == 3) {
                $fields['Status'] = get_string('withdrawn', 'local_obu_application');
            } else if ($application->approval_level == 1) {
                $fields['Status'] = 'Administrator pre-check';
            } else if ($application->approval_level == 2) {
                $fields['Status'] = 'Funder to approve';
            } else {
                $fields['Status'] = 'HLS to approve';
            }

            if ($first_record) { // Write headings
                fputcsv($fp, array_keys($fields));
                $first_record = false;
            }
            fputcsv($fp, $fields);
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