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
 * OBU Application - Delete applicant [Moodle]
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
require_once('./mdl_delete_applicant_form.php');

require_login();

$home = new moodle_url('/');
if (!is_siteadmin() || !isset($_REQUEST['userid'])) {
	redirect($home);
}

$dir = $home . 'local/obu_application/';
$back = $dir . 'mdl_site_admin.php';

if (!has_capability('local/obu_application:update', context_system::instance())) {
	redirect($back);
}

$user = local_obu_application_read_user($_REQUEST['userid']);
if ($user === false) {
	redirect($back);
}
$applicant = local_obu_application_read_applicant($user->id, false);

$url = $dir . 'mdl_delete_applicant.php?userid=' . $user->id;

$title = get_string('applications_administration', 'local_obu_application');
$heading = get_string('delete_applicant', 'local_obu_application');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$parameters = [
	'user' => $user,
	'applicant' => $applicant
];

$mform = new mdl_delete_applicant_form(null, $parameters);

if ($mform->is_cancelled()) {
	redirect($back);
} else if ($mform_data = $mform->get_data()) {
	if (isset($mform_data->submitbutton) && ($mform_data->submitbutton == get_string('confirm_delete', 'local_obu_application'))) {
        local_obu_application_application_user_delete($user);
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
