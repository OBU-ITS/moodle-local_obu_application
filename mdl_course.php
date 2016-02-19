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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require_once('../../config.php');
require_once('./locallib.php');
require_once('./db_update.php');
require_once('./course_input.php');

require_login();

$context = context_system::instance();
require_capability('local/obu_application:manage', $context);

$home = new moodle_url('/');
$dir = $home . 'local/obu_application/';
$program = $dir . 'mdl_course_code.php';
$heading = get_string('list_applications', 'local_obu_application');

$PAGE->set_url($program);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($heading);

$message = '';

$parameters = [
	'record' => $record
];

$mform = new user_input(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($home);
} 
else if ($mform_data = $mform->get_data()) {
	if ($mform_data->submitbutton == get_string('save', 'local_obu_application')) {
		write_course($USER->id, $mform_data);
		redirect($url);
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
