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
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require_once('../../config.php');
require_once('./locallib.php');
require_once('./db_update.php');
require_once('./mdl_course_form.php');

require_login();
$context = context_system::instance();
require_capability('local/obu_application:manage', $context);

$program = '/local/obu_application/mdl_course.php';
$url = new moodle_url($program);

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('plugintitle', 'local_obu_application') . ': ' . get_string('courses', 'local_obu_application'));
$PAGE->set_url($program);
$PAGE->set_heading($SITE->fullname);

$message = '';

$id = '';
$delete = false;
$courses = array();
$record = null;

if (isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
	if ($id != '0') {
		$record = read_course_record_by_id($id);
		if (isset($_REQUEST['delete'])) {
			$delete = true;
		}		
	}
} else {
	$recs = get_course_records();
	if ($recs) { // Do they have a choice?
		$courses[0] = get_string('new_course', 'local_obu_application'); // The 'New Course' option
		foreach ($recs as $rec) {
			$name = $rec->code . ' ' . $rec->name;
			if ($rec->supplement) {
				$name .= ' [' . $rec->supplement . ']';
			}
			$courses[$rec->id] = $name;
		}
	} else { // No, they don't...
		$id = '0'; // ...so it's gottabee a new one
	}
}

$parameters = [
	'id' => $id,
	'delete' => $delete,
	'courses' => $courses,
	'record' => $record
];

$mform = new mdl_course_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($url);
} 
else if ($mform_data = $mform->get_data()) {
	if (isset($mform_data->submitbutton)) { // 'Save' or 'Confirm Deletion'
		if ($mform_data->submitbutton == get_string('save', 'local_obu_application')) {
			write_course_record($mform_data);
			redirect($url);
		} else if ($mform_data->submitbutton == get_string('confirm_delete', 'local_obu_application')) {
			delete_course_record($mform_data->id);
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
