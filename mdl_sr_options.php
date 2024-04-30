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
 * OBU Application - Status Report options [Moodle]
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
require_once('./db_update.php');
require_once('./mdl_sr_options_form.php');

require_login();

$home = new moodle_url('/');
if (!local_obu_application_is_manager()) {
	redirect($home);
}

$applications_course = local_obu_application_get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;

if (!has_capability('local/obu_application:update', context_system::instance())) {
	redirect($back);
}

$url = $home . 'local/obu_application/mdl_sr_options.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('sr_options', 'local_obu_application');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$option_c = local_obu_application_read_parameter_by_name('U' . $USER->id . 'src');
if ($option_c === false) {
	$selected_courses = array();
} else {
	$selected_courses = explode(',', $option_c->text);
}
$option_d = local_obu_application_read_parameter_by_name('U' . $USER->id . 'srd');
if ($option_d === false) {
	$application_date = 1466377200;
} else {
	$application_date = $option_d->number;
}
$option_o = local_obu_application_read_parameter_by_name('U' . $USER->id . 'sro');
if ($option_o === false) {
	$sort_order = '';
} else {
	$sort_order = $option_o->text;
}

$courses = array();
$recs = local_obu_application_get_course_records();
foreach ($recs as $rec) {
	$courses['"' . $rec->code . '"'] = $rec->name . ' [' . $rec->code . ']';
}
asort($courses);

$sort_orders = array();
$sort_orders[''] = get_string('reference', 'local_obu_application');
$sort_orders['lastname, firstname, id'] = get_string('surname', 'local_obu_application');
$sort_orders['approval_state, approval_level, id'] = get_string('status', 'local_obu_application');
$sort_orders['course_code, id'] = get_string('course', 'local_obu_application');
$sort_orders['course_code, lastname, firstname, id'] = get_string('course_surname', 'local_obu_application');
$sort_orders['course_code, approval_state, approval_level, id'] = get_string('course_status', 'local_obu_application');

$parameters = [
	'selected_courses' => $selected_courses,
	'application_date' => $application_date,
	'sort_order' => $sort_order,
	'courses' => $courses,
	'sort_orders' => $sort_orders
];

$mform = new mdl_sr_options_form(null, $parameters);

if ($mform->is_cancelled()) {
	redirect($back);
} 
else if ($mform_data = $mform->get_data()) {
    $selected_courses = implode(',', $mform_data->selected_courses);
	if ($selected_courses == '') {
		if ($option_c !== false) {
            local_obu_application_delete_parameter($option_c->id);
		}
	} else {
		if ($option_c === false) {
			$option_c = new stdClass();
			$option_c->id = 0;
			$option_c->name = 'U' . $USER->id . 'src';
			$option_c->number = 0;
		}
		$option_c->text = $selected_courses;
        local_obu_application_write_parameter($option_c);
	}

	if ($mform_data->application_date == 1466377200) {
		if ($option_d !== false) {
            local_obu_application_delete_parameter($option_d->id);
		}
	} else {
		if ($option_d === false) {
			$option_d = new stdClass();
			$option_d->id = 0;
			$option_d->name = 'U' . $USER->id . 'srd';
			$option_d->text = '';
		}
		$option_d->number = $mform_data->application_date;
        local_obu_application_write_parameter($option_d);
	}

	if ($mform_data->sort_order == '') {
		if ($option_o !== false) {
            local_obu_application_delete_parameter($option_o->id);
		}
	} else {
		if ($option_o === false) {
			$option_o = new stdClass();
			$option_o->id = 0;
			$option_o->name = 'U' . $USER->id . 'sro';
			$option_o->number = 0;
		}
		$option_o->text = $mform_data->sort_order;
        local_obu_application_write_parameter($option_o);
	}

	redirect($back);
}	

echo $OUTPUT->header();

if ($message) {
    notice($message, $url);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
