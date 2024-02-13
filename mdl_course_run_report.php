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
 * @author     Joe Souch
 * @copyright  2024, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./mdl_course_run_report_form.php');

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
$url = $dir . 'mdl_manager_report.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('manager_report', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$semesters = array();
$semesters[0] = "";
$semesters['course_start_sep'] = get_string('course_start_sep', 'local_obu_application');
$semesters['course_start_jan'] = get_string('course_start_jan', 'local_obu_application');
$semesters['course_start_jun'] = get_string('course_start_jun', 'local_obu_application');

$parameters = [
    'semesters' => $semesters
];

$mform = new mdl_course_run_report_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
}

if ($mform_data = $mform->get_data()) {
    $courses = get_course_records();
    if (empty($courses)) {
        $message = get_string('no_courses', 'local_obu_application');
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=HLS_' . get_string('course_run_report', 'local_obu_application') . '_' .
            $mform_data->semester . '.csv');
        $fp = fopen('php://output', 'w');
        $first_record = true;

        $semester_column_name = 'Runs in ' . $semesters[$mform_data->semester];

        foreach ($courses as $course) {
            $fields = array();
            $fields['Code'] = $course->code;
            $fields['Name'] = $course->name;
            $fields['Campus'] = $course->campus;
            $fields['Administrator'] = $course->administrator;
            $fields['Cohort Code'] = $course->cohort_code;
            $fields['Suspended'] = $course->suspended ? 'Y' : 'N';

            if ($mform_data->semester == 'course_start_sep') {
                $fields[$semester_column_name] = $course->course_start_sep;
            }
            else if ($mform_data->semester == 'course_start_jan') {
                $fields[$semester_column_name] = $course->course_start_jan;
            }
            else if ($mform_data->semester == 'course_start_jun') {
                $fields[$semester_column_name] = $course->course_start_jun;
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