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
 * OBU Application - Get an application by reference [Moodle]
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
require_once('./mdl_reference_form.php');

require_login();

$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$applications_course = get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;

$dir = $home . 'local/obu_application/';
$url = $dir . 'mdl_reference.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('application_by_ref', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';
$applicants = null;

$mform = new mdl_reference_form(null, array());

if ($mform->is_cancelled()) {
    redirect($back);
} 
else if ($mform_data = $mform->get_data()) {
	$url = $dir . 'mdl_process.php?source=mdl_reference.php&id=' . $mform_data->id;
	redirect($url);
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
