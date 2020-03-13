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
 * @copyright  2020, Oxford Brookes University
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
	
	$months = [ 'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6, 'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12 ];
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
			$fields['First_Name(s)'] = $application->firstname;
			if (($mform_data->xfer_type == 1) || ($mform_data->xfer_type == 3)) { // Admissions
				if ($mform_data->xfer_type == 3) {
					$fields['Middle_Name'] = '';
					$fields['Previous_Family_Name'] = '';
				}
				$fields['Address_Type'] = 'HO';
				$fields['Address_1'] = $application->address_1;
				$fields['Address_2'] = $application->address_2;
				$fields['Address_3'] = $application->address_3;
				$fields['City'] = $application->city;
				$fields['Domicile'] = $application->domicile_code;
				$fields['Residence'] = $application->residence_code;
				$fields['Postcode'] = $application->postcode;
				if ($application->mobile_phone != '') {
					$fields['Telephone_Type'] = 'MO';
					$fields['Telephone'] = $application->mobile_phone;
				} else {
					$fields['Telephone_Type'] = 'HO';
					$fields['Telephone'] = $application->home_phone;
				}
				if (strpos($application->email, '@brookes.ac.uk') !== false) {
					$fields['Email_Type'] = 'BRKS';
				} else {
					$fields['Email_Type'] = 'PERS';
				}
				$fields['Email'] = $application->email;
				$fields['Date_of_Birth'] = date('d/m/Y', $application->birthdate);
				$fields['Gender'] = $application->gender;
				$fields['Country_of_Birth'] = $application->birth_code;
				$fields['Nationality'] = $application->nationality_code;
			}
			$course = read_course_record(trim($application->course_code));
			$fields['Programme_Code'] = $course->programme_code;
			$fields['Major_Code'] = $course->major_code;
			$fields['Level'] = $course->level;
			$fields['Campus'] = $course->campus;
			$fields['Student_Type'] = 'P';
			$course_date = $application->course_date;
			$month = substr($course_date, 0, 3);
			if (isset($months[$month])) {
				$year = substr($course_date, 3);
				if ((strlen($year) == 2) && is_numeric($year)) {
					$course_date = '20' . $year;
					if ($months[$month] <= 5) {
						$course_date .= '01';
					} else if ($months[$month] <= 8) {
						$course_date .= '06';
					} else {
						$course_date .= '09';
					}					
				}
			}
			$fields['Admit_Term'] = $course_date;
			$fields['Admit_Type'] = '60';
			$fields['Residency_Type'] = 'H';
			$fields['Cohort'] = $course->cohort_code;
			$fields['Programme_Stage'] = 'S1';
			$fields['Module_Subject'] = $course->module_subject;
			$fields['Module_Number'] = $course->module_number;
			if ($application->settled_status == '0') {
				$settled_status_formatted = '';
			} else if ($application->settled_status == '1') {
				$settled_status_formatted = get_string('yes', 'local_obu_application');
			} else {
				$settled_status_formatted = get_string('no', 'local_obu_application');
			}
			$fields['Settled_Status'] = $settled_status_formatted;
			if ($application->studying == '1') {
				$studying_formatted = get_string('yes', 'local_obu_application');
			} else {
				$studying_formatted = get_string('no', 'local_obu_application');
			}
			$fields['Currently_Studying'] = $studying_formatted;
			if (($mform_data->xfer_type == 1) || ($mform_data->xfer_type == 2)) { // Admissions & Finance (combined) or Finance
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
					$fields['Contact_Email'] = '';
					$fields['Phone_No'] = '';
					$fields['Contact_Name'] = '';
				} else {
					$fields['PO_Number'] = $application->invoice_ref;
					$fields['Address'] = $application->invoice_address;
					$fields['Contact_Email'] = $application->invoice_email;
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

