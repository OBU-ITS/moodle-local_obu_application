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
 * @copyright  2018, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./mdl_xfer_form.php');

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
$url = $dir . 'mdl_xfer.php';

$title = get_string('applications_management', 'local_obu_application');
$heading = get_string('data_xfer', 'local_obu_application');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$mform = new mdl_xfer_form(null, array());

if ($mform->is_cancelled()) {
    redirect($back);
} 
else if ($mform_data = $mform->get_data()) {
		
	if (($mform_data->xfer_type == 1) || ($mform_data->xfer_type == 3)) {
		$param_name = 'ADM'; // Admissions
	} else {
		$param_name = 'FIN'; // Finance
	}
	
	if ($mform_data->xfer_id != '') { // A re-run
		$xfer_id = $mform_data->xfer_id; // Re-run batch ID
		$batch_number = 0; // No new batch number
	} else {
		$param = read_parameter_by_name($param_name, true);
		if ($mform_data->xfer_type == 3) {
			$xfer_id = $param->number; // Default to last Admissions batch ID
			$batch_number = 0; // No new batch number
		} else {
			$xfer_id = 0; // No existing batch number
			$batch_number = $param->number + 1;
		}
	}
	if ($xfer_id != 0) {
		$file_id = $xfer_id;
	} else {
		$file_id = $batch_number;
	}
	
	$applications = get_applications(); // Get all applications
	$xfers = array();
	foreach ($applications as $application) {
		if ((($application->approval_level == 3) && ($application->approval_state == 2)) // Approved by HLS so is/was OK to go...
			&& (((($mform_data->xfer_type == 1) || ($mform_data->xfer_type == 3)) && ($application->admissions_xfer == $xfer_id)) // Admissions or Process (Admissions data processing)
			|| (($mform_data->xfer_type == 2) && ($application->finance_xfer == $xfer_id)))) { // Finance
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
		header('Content-Disposition: attachment;filename=HLS_' . $param_name . sprintf('_%05d.', $file_id) . $extension);
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
				$fields['Course_Code'] = $application->course_code;
				$fields['Course_Name'] = $application->course_name;
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
			}

			if ($index == 0) { // First record
				fputcsv($fp, array_keys($fields), $delimiter);
			}
			fputcsv($fp, $fields, $delimiter);
			
			// If a new batch, flag the application as processed
			if ($batch_number > 0) {
				if ($mform_data->xfer_type == 1) {
					$application->admissions_xfer = $batch_number;
				} else {
					$application->finance_xfer = $batch_number;
				}
				update_application($application);
			}
		}
		fclose($fp);
		
		// If a new batch, update the parameter record
		if ($batch_number > 0) {
			$param->number = $batch_number;
			write_parameter($param);
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

