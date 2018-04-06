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
 * @copyright  2017, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require_once('../../config.php');
require_once('./locallib.php');
require_once('./db_update.php');
require_once('./mdl_organisation_form.php');

require_login();
$context = context_system::instance();
require_capability('local/obu_application:update', $context);
require_capability('local/obu_application:manage', $context);

$program = '/local/obu_application/mdl_organisation.php';
$url = new moodle_url($program);

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('plugintitle', 'local_obu_application') . ': ' . get_string('organisations', 'local_obu_application'));
$PAGE->set_url($program);
$PAGE->set_heading($SITE->fullname);

$message = '';

$id = '';
$delete = false;
$organisations = array();
$record = null;

if (isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
	if ($id != '0') {
		$record = read_organisation($id);
		if (isset($_REQUEST['delete'])) {
			$delete = true;
		}		
	}
} else {
	$recs = get_organisation_records();
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
	'record' => $record
];

$mform = new mdl_organisation_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($url);
} 
else if ($mform_data = $mform->get_data()) {
	if (isset($mform_data->submitbutton)) { // 'Save' or 'Confirm Deletion'
		if ($mform_data->submitbutton == get_string('save', 'local_obu_application')) {
			write_organisation($mform_data);
			redirect($url);
		} else if ($mform_data->submitbutton == get_string('confirm_delete', 'local_obu_application')) {
			delete_organisation($mform_data->id);
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
