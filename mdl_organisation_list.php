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
 * OBU Application - List all organisations [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2021, Oxford Brookes University
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
if (!is_administrator()) {
	redirect($back);
}

$dir = $home . 'local/obu_application/';
$url = $dir . 'mdl_organisation_list.php';

$table = new html_table();
$table->head = array('Name', 'Email', 'Code', 'Address', 'Suspended');

$organisations = get_organisation_records();
if ($organisations != null) {
	foreach ($organisations as $organisation) {
		if ($organisation->suspended == 1) {
			$suspended = 'Yes';
		} else {
			$suspended = '';
		}
		$table->data[] = array(
			$organisation->name,
			$organisation->email,
			$organisation->code,
			$organisation->address,
			$suspended
		);
	}
}

if (!isset($_REQUEST['export'])) {
	$title = get_string('applications_management', 'local_obu_application');
	$heading = get_string('organisation_list', 'local_obu_application');
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
	header('Content-Disposition: attachment;filename=organisation_list.csv');
	$fp = fopen('php://output', 'w');
	fputcsv($fp, $table->head, ',');
	foreach ($table->data as $row) {
		fputcsv($fp, $row, ',');
	}
	fclose($fp);
}
