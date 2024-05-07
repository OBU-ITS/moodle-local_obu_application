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
 * OBU Application - Get a user ID and execute an action [Moodle]
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
require_once('./mdl_applicant_form.php');

require_login();

$home = new moodle_url('/');
if (!local_obu_application_is_manager() || !isset($_REQUEST['role']) || !isset($_REQUEST['action'])) {
	redirect($home);
}

$role = $_REQUEST['role'];
if (!is_siteadmin() && ($role == 'administration')) {
	redirect($home);
}

$action = $_REQUEST['action'];

$dir = $home . 'local/obu_application/';
$page_url = 'mdl_applicant.php?role=' . $role . '&action=' . $action;
$url = $dir . $page_url;

if ($role == 'administration') {
	$title = get_string('applications_administration', 'local_obu_application');
	$back = $dir . 'mdl_site_admin.php';
} else {
	$applications_course = local_obu_application_get_applications_course();
	require_login($applications_course);
	$title = get_string('applications_management', 'local_obu_application');
	$back = $home . 'course/view.php?id=' . $applications_course;
}

$heading = get_string('namerefsearch', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';
$applicants = null;
$parameters = [
	'role' => $role,
	'action' => $action
];

$mform = new mdl_applicant_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
}
else if ($mform_data = $mform->get_data()) {
    if (preg_match('~^[0-9]+~', $mform_data->nameref) || preg_match('~^HLS/[0-9]+~', $mform_data->nameref)) {
        $ref = $mform_data->nameref;
        if(preg_match('~^HLS/[0-9]+~', $ref)) {
            $ref = substr($ref, 4);
        }

        $redirect_url = $dir . 'mdl_process.php?source=' . urlencode($page_url) . '&id=' . $ref;

        redirect($redirect_url);
    }
    $applicants = local_obu_application_get_applicants_by_last_name($mform_data->nameref);
    if (count($applicants) == 1) {
        $redirect_url = $dir . 'mdl_' . $action . '.php?userid=' . array_values($applicants)[0]->userid;
        redirect($redirect_url);
    } else if (count($applicants) == 0){
        $applicants = local_obu_application_get_applicants_by_first_name($mform_data->nameref);
        if (count($applicants) == 1) {
            $redirect_url = $dir . 'mdl_' . $action . '.php?userid=' . array_values($applicants)[0]->userid;
            redirect($redirect_url);
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($message) {
    notice($message, $url);
}
else {
    $mform->display();

	if ($applicants != null) {
		foreach ($applicants as $applicant) {
			$url = $dir . 'mdl_' . $action . '.php?userid=' . $applicant->userid;
			echo '<h4><a href="' . $url . '">' . $applicant->firstname . ' ' . $applicant->lastname . '</a></h4>';
		}
	}
}

echo $OUTPUT->footer();
