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
 * OBU Application - Amend the Funding [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2018, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./mdl_amend_funding_form.php');
require_once($CFG->libdir . '/moodlelib.php');

require_login();

$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$applications_course = get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;
//if (!is_administrator()) {
//	redirect($back);
//}

if (!has_capability('local/obu_application:update', context_system::instance())) {
	redirect($back);
}

// We only allow access to an existing application (id given)
if (!isset($_REQUEST['id'])) {
	redirect($back);
}

$application = read_application($_REQUEST['id']);
if ($application === false) {
	redirect($back);
}

$url = $home . 'local/obu_application/mdl_amend_funding.php?id=' . $application->id;
$process = $home . 'local/obu_application/mdl_process.php?id=' . $application->id;
if (($application->approval_level != 3) || ($application->approval_state != 0)) { // Must be awaiting approval/rejection by HLS
	redirect($process);
}

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('application', 'local_obu_application', $application->id);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);
$PAGE->set_heading($title);
$PAGE->set_url($url);
$PAGE->navbar->add($heading);

$message = '';

$organisations = get_organisations();
$parameters = [
	'organisations' => $organisations,
	'application' => $application
];
	
$mform = new mdl_amend_funding_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($process);
}

if ($mform_data = $mform->get_data()) {
		
	// Update the applications's funding fields
	$application->funding_id = $mform_data->funding_id;
	if ($application->funding_id == 0) { // Must be an invoice to a non-NHS organisation
		$application->funding_method = 0; // Invoice to a non-NHS organisation
		$application->funding_organisation = $mform_data->funding_organisation; // Organisation as input
		$application->funder_name = ''; // N/A
	} else { // NHS trust
		$application->funding_method = $mform_data->funding_method; // 1 - Invoice, 2- Pre-paid, 3 - Contract
		$application->funding_organisation = $organisations[$application->funding_id];
		$application->funder_name = $mform_data->funder_name;
	}
	$application->invoice_ref = $mform_data->invoice_ref;
	$application->invoice_address = $mform_data->invoice_address;
	$application->invoice_email = $mform_data->invoice_email;
	$application->invoice_phone = $mform_data->invoice_phone;
	$application->invoice_contact = $mform_data->invoice_contact;

	// Add the additional funding fields for a programme of study
	$application->fund_programme = $mform_data->fund_programme;
	if ($mform_data->fund_programme) {
		$application->fund_module_1 = '';
		$application->fund_module_2 = '';
		$application->fund_module_3 = '';
		$application->fund_module_4 = '';
		$application->fund_module_5 = '';
		$application->fund_module_6 = '';
		$application->fund_module_7 = '';
		$application->fund_module_8 = '';
		$application->fund_module_9 = '';
	} else {
		$application->fund_module_1 = $mform_data->fund_module_1;
		$application->fund_module_2 = $mform_data->fund_module_2;
		$application->fund_module_3 = $mform_data->fund_module_3;
		$application->fund_module_4 = $mform_data->fund_module_4;
		$application->fund_module_5 = $mform_data->fund_module_5;
		$application->fund_module_6 = $mform_data->fund_module_6;
		$application->fund_module_7 = $mform_data->fund_module_7;
		$application->fund_module_8 = $mform_data->fund_module_8;
		$application->fund_module_9 = $mform_data->fund_module_9;
	}

	update_application($application);

	redirect($process);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($message) {
    notice($message, $process);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
