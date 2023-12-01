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
 * OBU Application - Status report on all applications for the user's selected courses [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');

require_login();

$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$applications_course = get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;

$dir = $home . 'local/obu_application/';
$url = $dir . 'mdl_status.php';
$process = $dir . 'mdl_process.php?source=' . urlencode('mdl_status.php') . '&id=';

$table = new html_table();
$table->head = array('Ref', 'Name', 'Phone', 'Email', 'Course', 'Date', 'Employer', 'Status');

$parameter = read_parameter_by_name('U' . $USER->id . 'src');
if ($parameter === false) {
	$selected_courses = '';
} else {
	$selected_courses = $parameter->text;
}
$parameter = read_parameter_by_name('U' . $USER->id . 'srd');
if ($parameter === false) {
	$application_date = 1466377200;
} else {
	$application_date = $parameter->number;
}
$parameter = read_parameter_by_name('U' . $USER->id . 'sro');
if ($parameter === false) {
	$sort_order = '';
} else {
	$sort_order = $parameter->text;
}
$applications = get_applications_for_courses($selected_courses, $application_date, $sort_order);
if ($applications != null) {
	foreach ($applications as $application) {
		if (!isset($_REQUEST['export'])) {
			$ref = '<a href="' . $process . $application->id . '">HLS/' . $application->id . '</a>';
		} else {
			$ref = 'HLS/' . $application->id;
		}
		$name = $application->title . ' ' . $application->firstname . ' ' . $application->lastname;
		if ($application->mobile_phone != '') {
			$phone = $application->mobile_phone;
		} else {
			$phone = $application->home_phone;
		}
		$course = $application->course_code . ': ' . $application->course_name;
		if ($application->approval_state == 1) {
			$status = 'Rejected';
		} else if ($application->approval_state == 2) {
			$status = 'Approved';
		} else if ($application->approval_level == 1) {
			$status = 'Manager to approve';
		} else if ($application->approval_level == 2) {
			$status = 'Funder to approve';
		} else {
			$status = 'HLS to approve';
		}

		$table->data[] = array($ref, $name, $phone, $application->email, $course, $application->course_date, $application->emp_place, $status);
	}
}

if (!isset($_REQUEST['export'])) {
	$title = get_string('applications_management', 'local_obu_application');
	$heading = get_string('status_report', 'local_obu_application');
	$PAGE->set_url($url);
	$PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
	$PAGE->set_heading($title);
	$PAGE->navbar->add($heading);

	echo $OUTPUT->header();
	echo $OUTPUT->heading($heading);

	echo html_writer::table($table);

	echo '<h4><a href="' . $url . '?export"><span class="fa fa-download"></span> Export</a></h4>';
	echo '<h4><a href="' . $back . '"><span class="fa fa-caret-left"></span> Menu</a></h4>';

	echo $OUTPUT->footer();
} else {
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment;filename=status_report.csv');
	$fp = fopen('php://output', 'w');
	fputcsv($fp, $table->head, ',');
	foreach ($table->data as $row) {
        if (substr($row[2], 0, 3) == "+44"){
            $row[2] = "=\"" . "0" . substr($row[2], 3) . "\"";
        } else{
            $row[2] = "=\"" .$row[2]. "\"";
        }
		fputcsv($fp, $row, ',');
	}
	fclose($fp);
}
