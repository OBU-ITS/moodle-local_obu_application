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
 * OBU Application - Organisation maintenance [Moodle]
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
require_once('./mdl_organisation_form.php');

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

$url = $home . 'local/obu_application/mdl_organisation.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('organisations', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$id = '';
$delete = false;
$organisations = array();
$record = null;
$applications = 0;

if (isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
	if ($id != '0') {
		$record = local_obu_application_read_organisation($id);
		if (isset($_REQUEST['delete'])) {
			$delete = true;
		}		
		$applications = local_obu_application_count_applications_for_funder($id);
	}
} else {
	$recs = local_obu_application_get_organisation_records();
	if ($recs) { // Do they have a choice?
		$organisations[0] = get_string('new_organisation', 'local_obu_application'); // The 'New Organisation' option
		foreach ($recs as $rec) {
			$name = $rec->name;
			if ($rec->suspended) {
				$name .= ' [SUSPENDED]';
			}
			$organisations[$rec->id] = $name;
		}
	} else { // No, they don't...
		$id = '0'; // ...so it's gottabee a new one
	}
}

$parameters = [
	'id' => $id,
	'delete' => $delete,
	'organisations' => $organisations,
	'record' => $record,
	'applications' => $applications
];

$mform = new mdl_organisation_form(null, $parameters);

if ($mform->is_cancelled()) {
	if ($id == '0') {
		redirect($back);
	} else {
		redirect($url);
	}
} 
else if ($mform_data = $mform->get_data()) {
	if (isset($mform_data->submitbutton)) { // 'Save' or 'Confirm Deletion'
		if ($mform_data->submitbutton == get_string('save', 'local_obu_application')) {
            local_obu_application_write_organisation($mform_data);
			redirect($url);
		} else if ($mform_data->submitbutton == get_string('confirm_delete', 'local_obu_application')) {
            local_obu_application_delete_organisation($mform_data->id);
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
