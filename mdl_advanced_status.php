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
 * OBU Application - Return a CSV data file of a student's application data [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Emir Kamel
 * @copyright  2024, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./mdl_advanced_status_form.php');

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
$url = $dir . 'mdl_advanced_status.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('advanced_status_report', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$courses = array();
$recs = get_course_records();
foreach ($recs as $rec) {
    $courses['"' . $rec->code . '"'] = $rec->name . ' [' . $rec->code . ']';
}
asort($courses);

$parameters = [
    'courses' => $courses
];

$mform = new mdl_advanced_status_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
}

if ($mform_data = $mform->get_data()) {
    $selected_courses = implode(',', $mform_data->selected_courses);
    $applications = [];
    $applications = get_applications_for_courses($selected_courses, $mform_data->application_date);

    if (empty($applications)) {
        $message = get_string('no_applications', 'local_obu_application');
    } else {
        header('Content-Type: text/csv');
        if (count($mform_data->selected_courses) == 1) {
            header('Content-Disposition: attachment;filename=adv_status_report' . $selected_courses . date('Ymd', $mform_data->application_date) . '.csv');
        } else {
            header('Content-Disposition: attachment;filename=adv_status_report_' . 'multiple_' . date('Ymd', $mform_data->application_date) . '.csv');
        }

        $fp = fopen('php://output', 'w');
        $first_record = true;
        foreach ($applications as $application) {
            $fields = array();
            $fields['Ref'] = 'HLS/' . $application->id;
            $fields['Name'] = $application->title . " " . $application->firstname . " " . $application->lastname;
            $fields['Email'] = $application->email;
            $fields['Course'] = $application->course_code . ': ' . $application->course_name;
            $fields['Course_Date'] = $application->course_date;
            $fields['Employer'] = $application->emp_place;
            $fields['Funder_Email'] = $application->funder_email;
            $fields['Personal_Statement'] = $application->statement;

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