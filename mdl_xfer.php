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
 * OBU Application - Return a CSV data file for transfer to either Admissions or Finance
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
require_once('./mdl_xfer_form.php');

require_login();

$context = context_system::instance();
require_capability('local/obu_application:manage', $context);

$home = new moodle_url('/');
$dir = $home . 'local/obu_application/';
$program = $dir . 'mdl_xfer.php';
$heading = get_string('data_xfer', 'local_obu_application');

$PAGE->set_url($program);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($heading);

$message = '';

$mform = new mdl_xfer_form(null, array());

if ($mform->is_cancelled()) {
    redirect($home);
} 
else if ($mform_data = $mform->get_data()) {
	if (($mform_data->xfer_type == 1) || ($mform_data->xfer_type == 3)) {
		$param = read_parameter('ADM');
	} else {
		$param = read_parameter('FIN');
	}
		
	if ($mform_data->xfer_id == 0) {
		$xfer_id = $param->number + 1; // Batch ID
	} else {
		$xfer_id = $mform_data->xfer_id;
	}
	$applications = get_applications(); // get all applications
	$xfers = array();
	foreach ($applications as $application) {
		if ((($application->approval_level == 3) && ($application->approval_state == 2)) // Approved by HLS so is/was OK to go...
			&& (((($mform_data->xfer_type == 1) ) && (($application->admissions_id == 0) || ($application->admissions_id == $xfer_id)))
			|| (($mform_data->xfer_type == 2) && (($application->finance_id == 0) || ($application->finance_id == $xfer_id)))
			|| (($mform_data->xfer_type == 3) && ($application->admissions_id == $xfer_id)))) {
				$xfers[] = $application->id;
		}
	}
	if (empty($xfers)) {
		$message = get_string('no_xfer', 'local_obu_application');
	} else {
		if ($mform_data->xfer_type < 3) {
			$delimiter = ',';
			$extension = 'csv';
		} else {
			$delimiter = '|';
			$extension = 'txt';
		}
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=HLS_' . $param->name . sprintf('_%05d.', $xfer_id) . $extension);
		$fp = fopen('php://output', 'w');
		foreach ($xfers as $index => $xfer) {
			$application = read_application($xfer);
			$fields = array();
			if ($mform_data->xfer_type == 3) {
				$fields['Form_Released_Date'] = date('d/m/Y');
				$fields['Institution_Label'] = 'Ox Brookes';
				$fields['Course_Type'] = 'HLS';
			}
			$fields['Applicant_Id'] = 'H' . sprintf('%07d', $application->userid);
			$fields['Form_Id'] = 'HLS/' . $application->id;
			$fields['Title'] = $application->title;
			$fields['Surname'] = $application->lastname;
			$fields['First_Name'] = $application->firstname;
			if (($mform_data->xfer_type == 1) || ($mform_data->xfer_type == 3)) { // Admissions
				if ($mform_data->xfer_type == 3) {
					$fields['Middle_Name'] = '';
					$fields['Previous_Family_Name'] = '';
				}
				$fields['Corr_Address_1'] = $application->address_1;
				$fields['Corr_Address_2'] = $application->address_2;
				$fields['Corr_Address_3'] = $application->address_3;
				$fields['Corr_Town'] = $application->town;
				if ($mform_data->xfer_type == 1) {
					$fields['Domicile_Code'] = $application->domicile_code;
				}
				$fields['Corr_County'] = $application->county;
				$fields['Corr_Postcode'] = $application->postcode;
				if ($mform_data->xfer_type == 3) {
					$fields['Corr_Country_Code'] = '';
					$fields['Corr_Country_Label'] = '';
					$fields['Home_Address_1'] = '';
					$fields['Home_Address_2'] = '';
					$fields['Home_Address_3'] = '';
					$fields['Home_Town'] = '';
					$fields['Home_County'] = '';
					$fields['Home_Postcode'] = '';
					$fields['Home_Country_Code'] = '';
					$fields['Home_Country_Label'] = '';
					$fields['Same_Address'] = 'True';
				}
				$fields['Telephone'] = $application->phone;
				$fields['Email'] = $application->email;
				if ($mform_data->xfer_type < 3) {
					$fields['DoB'] = date('d/m/Y', $application->birthdate);
				} else {
					$fields['DoB'] = $application->birthdate;
					$fields['Gender'] = '';
					$fields['Birth_Country_Code'] = '';
					$fields['Birth_Country_Label'] = '';
					$fields['Domicile_Country_Code'] = '';
					$fields['Domicile_Country_Label'] = '';
				}
				$fields['Nationality_Code'] = $application->nationality_code;
				$fields['Nationality_Label'] = $application->nationality;
				if ($mform_data->xfer_type == 3) {
					$fields['Domicile_Code'] = $application->domicile_code;
				}
				$fields['Criminal_Record'] = $application->criminal_record;
				$fields['Course_Code'] = $application->course_code;
				$fields['Course_Name'] = $application->course_name;
				$fields['Course_Date'] = $application->course_date;
			} else { // Finance
				if ($application->self_funding == 1) {
					$fields['Funding_Method'] = 'Self-funding';
					$fields['Organisation'] = '';
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
					$fields['PO_Number'] = 'invoice_ref';
					$fields['Address'] = 'invoice_address';
					$fields['Email'] = 'invoice_email';
					$fields['Phone_No'] = 'invoice_phone';
					$fields['Contact_Name'] = 'invoice_contact';
				}
			}

			if ($index == 0) { // First record
				fputcsv($fp, array_keys($fields), $delimiter);
			}
			fputcsv($fp, $fields, $delimiter);
			
			// Flag the application as processed
			if (($mform_data->xfer_type == 1) && ($application->admissions_id == 0)) {
				$application->admissions_id = $xfer_id;
				update_application($application);
			} else if (($mform_data->xfer_type == 2) && ($application->finance_id == 0)) {
				$application->finance_id = $xfer_id;
				update_application($application);
			}
		}
		fclose($fp);
		
		// Update the parameter record if necessary
		if ($xfer_id > $param->number) {
			write_parameter($param->name, $xfer_id);
		}
		
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

