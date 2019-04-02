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
 * OBU Application - Return a CSV data file of funding data [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./mdl_funding_form.php');

require_login();

$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$applications_course = get_applications_course();
require_login($applications_course);
$back = $home . 'course/view.php?id=' . $applications_course;
if (!is_administrator()) {
	redirect($back);
}

$dir = $home . 'local/obu_application/';
$url = $dir . 'mdl_funding.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('funding_report', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$organisations = array();
$organisations[0] = get_string('self_funding', 'local_obu_application');
$orgs = get_organisations();
foreach ($orgs as $id => $name) {
	$organisations[$id] = $name;
}

$parameters = [
	'organisations' => $organisations
];

$mform = new mdl_funding_form(null, $parameters);

if ($mform->is_cancelled()) {
    redirect($back);
} 

if ($mform_data = $mform->get_data()) {
	$applications = get_applications_for_funder($mform_data->organisation, $mform_data->application_date); // Get the applications
	if (empty($applications)) {
		$message = get_string('no_applications', 'local_obu_application');
	} else {
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=HLS_' . get_string('applications', 'local_obu_application') . '.csv');
		$fp = fopen('php://output', 'w');
		$first_record = true;
		foreach ($applications as $application) {
			$fields = array();
			$fields['Form_Id'] = 'HLS/' . $application->id;
			$fields['Title'] = $application->title;
			$fields['First_Name'] = $application->firstname;
			$fields['Surname'] = $application->lastname;
			$fields['Course'] = $application->course_code . ': ' . $application->course_name;
			$fields['Course_Date'] = $application->course_date;
			if ($application->self_funding == 1) {
				$fields['Funding_Method'] = 'Self-funding';
				$fields['Organisation'] = '';
				$fields['Contract'] = '';
				$fields['Funder_Name'] = '';
			} else {
				if ($application->funding_method < 2) {
					$fields['Funding_Method'] = 'Invoice';
				} else if ($application->funding_method == 2) {
					$fields['Funding_Method'] = 'Pre-paid';
				} else {
					$fields['Funding_Method'] = 'Contract';
				}
				$fields['Organisation'] = $application->funding_organisation;
				if ($application->funding_method < 3) {
					$fields['Contract'] = '';
				} else {
					$organisation = read_organisation($application->funding_id);
					if ($organisation == null) {
						$fields['Contract'] = 'NONE';
					} else {
						$fields['Contract'] = $organisation->code;
					}
				}
				if ($application->funding_method == 0) {
					$fields['Funder_Name'] = '';
				} else {
					$fields['Funder_Name'] = $application->funder_name;
				}
			}
			if (($application->self_funding == 1) || ($application->funding_method > 2)){
				$fields['PO_Number'] = '';
				$fields['Address'] = '';
				$fields['Email'] = '';
				$fields['Phone_No'] = '';
				$fields['Contact_Name'] = '';
			} else {
				$fields['PO_Number'] = $application->invoice_ref;
				$fields['Address'] = $application->invoice_address;
				$fields['Email'] = $application->invoice_email;
				$fields['Phone_No'] = $application->invoice_phone;
				$fields['Contact_Name'] = $application->invoice_contact;
			}
			if (($application->self_funding == 0) && is_programme($application->course_code)) {
				if ($application->fund_programme) {
					$fields['Fund_Programme'] = 'Y';
				} else {
					$fields['Fund_Programme'] = 'N';
				}
				$fields['Fund_Module_1'] = $application->fund_module_1;
				$fields['Fund_Module_2'] = $application->fund_module_2;
				$fields['Fund_Module_3'] = $application->fund_module_3;
				$fields['Fund_Module_4'] = $application->fund_module_4;
				$fields['Fund_Module_5'] = $application->fund_module_5;
				$fields['Fund_Module_6'] = $application->fund_module_6;
				$fields['Fund_Module_7'] = $application->fund_module_7;
				$fields['Fund_Module_8'] = $application->fund_module_8;
				$fields['Fund_Module_9'] = $application->fund_module_9;
			} else {
				$fields['Fund_Programme'] = '';
				$fields['Fund_Module_1'] = '';
				$fields['Fund_Module_2'] = '';
				$fields['Fund_Module_3'] = '';
				$fields['Fund_Module_4'] = '';
				$fields['Fund_Module_5'] = '';
				$fields['Fund_Module_6'] = '';
				$fields['Fund_Module_7'] = '';
				$fields['Fund_Module_8'] = '';
				$fields['Fund_Module_9'] = '';
			}
			if ($application->approval_state == 1) {
				$fields['Status'] = 'Rejected';
			} else if ($application->approval_state == 2) {
				$fields['Status'] = 'Approved';
			} else if ($application->approval_level == 1) {
				$fields['Status'] = 'Manager to approve';
			} else if ($application->approval_level == 2) {
				$fields['Status'] = 'Funder to approve';
			} else {
				$fields['Status'] = 'HLS to approve';
			}

			if ($first_record) { // Write headings
				fputcsv($fp, array_keys($fields));
				$first_record = false;
			}
			fputcsv($fp, $fields);
			
		}
		fclose($fp);
		
		exit();
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

