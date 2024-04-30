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
 * OBU Application - Return a CSV data file of a course combo codes [Moodle]
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
require_once('./mdl_combo_code_report_form.php');

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
$url = $dir . 'mdl_combo_code_report.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('combo_code_report', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$mform = new mdl_combo_code_report_form(null);

if ($mform->is_cancelled()) {
    redirect($back);
}

if ($mform_data = $mform->get_data()) {
    $courses = local_obu_application_get_course_records();
    if (empty($courses)) {
        $message = get_string('no_courses', 'local_obu_application');
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=HLS_' . get_string('combo_code_report', 'local_obu_application') . '.csv');
        $fp = fopen('php://output', 'w');
        $first_record = true;

        foreach ($courses as $course) {
            $fields = array();
            $fields['Course name'] = $course->name;
            $fields['Code'] = $course->code;
            $fields['Sep'] = $course->course_start_sep ? 'Y' : 'N';
            $fields['Jan'] = $course->course_start_jan ? 'Y' : 'N';
            $fields['Jun'] = $course->course_start_jun ? 'Y' : 'N';
            $fields['Combo code'] = $course->programme_code . $course->major_code . $course->campus;

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