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
 * OBU Application - Course maintenance [Moodle]
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
require_once('./db_update.php');
require_once('./mdl_course_form.php');

require_login();

$home = new moodle_url('/');
if (!local_obu_application_is_manager()) {
	redirect($home);
}

$applications_course = local_obu_application_get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;
if (!local_obu_application_is_administrator()) {
	redirect($back);
}

if (!has_capability('local/obu_application:update', context_system::instance())) {
	redirect($back);
}

$url = $home . 'local/obu_application/mdl_course.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('courses', 'local_obu_application');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$id = '';
$delete = false;
$codes = array();
$courses = array();
$courses_with_suspended = array();
$record = null;
$administrator = null;
$applications = 0;

if (isset($_REQUEST['id']) || isset($_REQUEST['id_not_suspended'])) {

    $show_suspended = $_REQUEST['show_suspended'] ?? true;
    $id =  $show_suspended ? $_REQUEST['id'] : $_REQUEST['id_not_suspended'];
    if($id == '') {
        $id = '0';
    }

	if ($id != '0') {

		$record = local_obu_application_read_course_record_by_id($id);

		if (isset($_REQUEST['delete'])) {
			$delete = true;
		}
		if ($record->administrator != '') {
			$user = local_obu_application_read_user_by_username($record->administrator);
			if ($user == null) {
				$administrator = get_string('user_not_found', 'local_obu_application');
			} else {
				$administrator = $user->firstname . ' ' . $user->lastname;
			}
		}
		$applications = local_obu_application_count_applications_for_course($record->code);
	} else { // Store existing course codes so we can check if any given code is really new
		$recs = local_obu_application_get_course_records();
		foreach ($recs as $rec) {
			$codes[] = $rec->code;
		}
	}
} else {
	$recs = local_obu_application_get_course_records();
	if ($recs) { // Do they have a choice?
        $courses[0] = get_string('new_course', 'local_obu_application'); // The 'New Course' option
        $courses_with_suspended[0] = get_string('new_course', 'local_obu_application'); // The 'New Course' option

		foreach ($recs as $rec) {
			$name = $rec->name . ' [' . $rec->code . ']';
			if ($rec->supplement) {
				$name .= ' {' . $rec->supplement . '}';
			}
			if ($rec->programme) {
				$name .= ' (Programme)';
			}

			if ($rec->suspended) {
				$name .= ' - SUSPENDED';

                $courses_with_suspended[$rec->id] = $name;
			}
            else{
                $courses[$rec->id] = $name;
                $courses_with_suspended[$rec->id] = $name;
            }
		}
	} else { // No, they don't...
		$id = '0'; // ...so it's gottabee a new one
	}
}

$parameters = [
	'id' => $id,
    'show_suspended' => $show_suspended,
	'delete' => $delete,
    'courses' => $courses,
    'courses_with_suspended' => $courses_with_suspended,
	'record' => $record,
	'administrator' => $administrator,
	'applications' => $applications
];

$mform = new mdl_course_form(null, $parameters);

if ($mform->is_cancelled()) {
	if (isset($_REQUEST['show_suspended'])) {
		redirect($back);
	} else {
		redirect($url);
	}
}
else if ($mform_data = $mform->get_data()) {
	if (isset($mform_data->submitbutton)) { // 'Save' or 'Confirm Deletion'
		if ($mform_data->submitbutton == get_string('save', 'local_obu_application')) {

            if (($mform_data->id == '0') && in_array(strtoupper($mform_data->code), $codes)) { // 'New' course already exists
				$message = get_string('existing_course', 'local_obu_application');
			} else {
                local_obu_application_write_course_record($mform_data);
				redirect($url);
			}
		} else if ($mform_data->submitbutton == get_string('confirm_delete', 'local_obu_application')) {
            local_obu_application_delete_course_record($mform_data->id);
			redirect($url);
		}
    } else if (isset($mform_data->deletebutton) && ($mform_data->deletebutton == get_string('delete', 'local_obu_application'))) { // Delete
		redirect($url . '?id=' . $id . '&delete=1'); // Come back and ask for confirmation
	}
}

echo $OUTPUT->header();

if ($message) {
    notice($message, $url);
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
