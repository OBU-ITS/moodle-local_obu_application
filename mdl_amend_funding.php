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
 * @copyright  2017, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./mdl_amend_funding_form.php');
require_once($CFG->libdir . '/moodlelib.php');

require_login();

$context = context_system::instance();
$home = new moodle_url('/');

// We only allow 'administrator' level access and only to an existing application (id given)
if (!has_capability('local/obu_application:update', $context) || !has_capability('local/obu_application:admin', $context) || !isset($_REQUEST['id'])) {
	redirect($home);
}

$application = read_application($_REQUEST['id']);
if (($application === false) || ($application->approval_level != 3) || ($application->approval_state != 0)) { // Must be awaiting approval/rejection by HLS
	redirect($home);
}

$program = $home . 'local/obu_application/mdl_amend_funding.php?id=' . $application->id;
$process = $home . 'local/obu_application/mdl_process.php?id=' . $application->id;

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('plugintitle', 'local_obu_application') . ': ' . get_string('process', 'local_obu_application')); // Part of application processing
$PAGE->set_url($program);
$PAGE->navbar->add(get_string('application', 'local_obu_application', $application->id));

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
	update_application($application);

	redirect($process);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('application', 'local_obu_application', $application->id));

if ($message) {
    notice($message, $process);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
