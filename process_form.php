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
 * OBU Application - View for application processing
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class process_form extends moodleform {

    function definition() {
        global $USER;

        $mform =& $this->_form;

        $data = new stdClass();
		$data->source = $this->_customdata['source'];
		$data->organisations = $this->_customdata['organisations'];
		$data->record = $this->_customdata['record'];
		$data->status_text = $this->_customdata['status_text'];
		$data->button_text = $this->_customdata['button_text'];

		$approval_sought = 0; // Level at which we are seeking approval from this user (if at all)
		if ($data->record !== false) {

			// Level (if any) at which we are seeking approval from this user
			if ($data->button_text == 'approve') {
				$approval_sought = $data->record->approval_level;
			}

			// Format the fields nicely before we load them into the form
			$date = date_create();
			$date_format = 'd/m/Y';
			date_timestamp_set($date, $data->record->birthdate);
			$birthdate_formatted = date_format($date, $date_format);
			if ($data->record->gender == 'F') {
				$gender = get_string('gender_female', 'local_obu_application');
			} else if ($data->record->gender == 'M') {
				$gender = get_string('gender_male', 'local_obu_application');
			} else {
				$gender = get_string('gender_not_available', 'local_obu_application');
			}
			date_timestamp_set($date, $data->record->prof_date);
			$prof_date_formatted = date_format($date, $date_format);
			if ($data->record->credit == '1') {
				$credit_formatted = '&#10004;'; // Tick
			} else {
				$credit_formatted = '&#10008;'; // Cross
			}
			if ($data->record->criminal_record == '1') {
				$criminal_record_formatted = get_string('yes', 'local_obu_application');
			} else {
				$criminal_record_formatted = get_string('no', 'local_obu_application');
			}
			if ($data->record->studying == '1') {
				$studying_formatted = 'Yes';
			} else {
				$studying_formatted = 'No';
			}
			if ($data->record->visa_requirement == '') {
				$visa_requirement = 'NONE';
			} else {
				$visa_requirement = $data->record->visa_requirement;
			}
			if ($data->record->self_funding == '1') {
				$self_funding_formatted = '&#10004; YES'; // Tick
			} else {
				$self_funding_formatted = '&#10008; NO'; // Cross
			}
			if ($data->record->declaration == '1') {
				$declaration_formatted = '&#10004;'; // Tick
			} else {
				$declaration_formatted = '&#10008;'; // Cross
			}
			$declaration_formatted .= ' ' . get_string('declaration_text', 'local_obu_application', get_string('conditions', 'local_obu_application'));
			if ($data->record->funding_method == 0) { // non-NHS
				$funding_method_formatted = get_string('other', 'local_obu_application') . ' (' . get_string('invoice', 'local_obu_application') . ')';
			} else { // NHS trust
				$funding_method_formatted = get_string('trust', 'local_obu_application') . ' (';
				if ($data->record->funding_method == 1) {
					$funding_method_formatted .= get_string('invoice', 'local_obu_application');
				} else if ($data->record->funding_method == 2) {
					$funding_method_formatted .= get_string('prepaid', 'local_obu_application');
				} else {
					$funding_method_formatted .= get_string('contract', 'local_obu_application');
				}
				$funding_method_formatted .= ')';
			}
			$funder_name_formatted = $data->record->funder_name;
			$invoice_address = $data->record->invoice_address;
			if ($approval_sought == 2) { // Funder
				$funder_name_formatted = $USER->firstname . ' ' . $USER->lastname;
				if ($data->record->funding_organisation != '') { // Get the address to use as the default
					$organisation = read_organisation($data->record->funding_id);
					$invoice_address = $organisation->address;
				}
			}
			if ($data->record->fund_programme == '1') {
				$fund_programme_formatted = '&#10004; YES'; // Tick
			} else {
				$fund_programme_formatted = '&#10008; NO'; // Cross
			}

			$fields = [
				'name' => $data->record->title . ' ' . $data->record->firstname . ' ' . $data->record->lastname,
				'title' => $data->record->title,
				'firstname' => $data->record->firstname,
				'lastname' => $data->record->lastname,
				'address_1' => $data->record->address_1,
				'address_2' => $data->record->address_2,
				'address_3' => $data->record->address_3,
				'city' => $data->record->city,
				'postcode' => $data->record->postcode,
				'domicile_country' => $data->record->domicile_country,
				'home_phone' => $data->record->home_phone,
				'mobile_phone' => $data->record->mobile_phone,
				'email' => $data->record->email,
				'birth_country' => $data->record->birth_country,
				'birthdate' => $birthdate_formatted,
				'nationality' => $data->record->nationality,
				'gender' => $gender,
				'residence_area' => $data->record->residence_area,
				'p16school' => $data->record->p16school,
				'p16schoolperiod' => $data->record->p16schoolperiod,
				'p16fe' => $data->record->p16fe,
				'p16feperiod' => $data->record->p16feperiod,
				'training' => $data->record->training,
				'trainingperiod' => $data->record->trainingperiod,
				'prof_level' => $data->record->prof_level,
				'prof_award' => $data->record->prof_award,
				'prof_date_formatted' => $prof_date_formatted,
				'credit_formatted' => $credit_formatted,
				'credit_name' => $data->record->credit_name,
				'credit_organisation' => $data->record->credit_organisation,
				'emp_place' => $data->record->emp_place,
				'emp_area' => $data->record->emp_area,
				'emp_title' => $data->record->emp_title,
				'emp_prof' => $data->record->emp_prof,
				'prof_reg_no' => $data->record->prof_reg_no,
				'criminal_record_formatted' => $criminal_record_formatted,
				'course_name' => $data->record->course_code . ' ' . $data->record->course_name,
				'course_date' => $data->record->course_date,
				'studying_formatted' => $studying_formatted,
				'student_number' => $data->record->student_number,
				'statement' => nl2br($data->record->statement),
				'visa_requirement' => $visa_requirement,
				'self_funding_formatted' => $self_funding_formatted,
				'manager_email' => $data->record->manager_email,
				'declaration_formatted' => $declaration_formatted,
				'funder_email' => $data->record->funder_email,
				'funding_method' => $funding_method_formatted,
				'funding_organisation' => $data->record->funding_organisation,
				'funder_name' => $funder_name_formatted,
				'invoice_ref' => $data->record->invoice_ref,
				'invoice_address' => $invoice_address,
				'invoice_email' => $data->record->invoice_email,
				'invoice_phone' => $data->record->invoice_phone,
				'invoice_contact' => $data->record->invoice_contact,
				'fund_programme_formatted' => $fund_programme_formatted,
				'fund_module_1' => $data->record->fund_module_1,
				'fund_module_2' => $data->record->fund_module_2,
				'fund_module_3' => $data->record->fund_module_3,
				'fund_module_4' => $data->record->fund_module_4,
				'fund_module_5' => $data->record->fund_module_5,
				'fund_module_6' => $data->record->fund_module_6,
				'fund_module_7' => $data->record->fund_module_7,
				'fund_module_8' => $data->record->fund_module_8,
				'fund_module_9' => $data->record->fund_module_9
			];
			$this->set_data($fields);
		}

		// Start with the required hidden fields
		$mform->addElement('hidden', 'source', $data->source);
		$mform->setType('source', PARAM_RAW);
		$mform->addElement('hidden', 'id', $data->record->id);
		$mform->setType('id', PARAM_RAW);
		$mform->addElement('hidden', 'approval_state', $data->record->approval_state);
		$mform->setType('approval_state', PARAM_RAW);
		$mform->addElement('hidden', 'approval_level', $data->record->approval_level);
		$mform->setType('approval_level', PARAM_RAW);
		$mform->addElement('hidden', 'nhs_trust', $data->record->funding_organisation);
		$mform->setType('nhs_trust', PARAM_RAW);

		// Our own hidden field (for use in form validation)
		$mform->addElement('hidden', 'self_funding', $data->record->self_funding);
		$mform->setType('self_funding', PARAM_RAW);

		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

		// Application status
		if (!empty($data->status_text)) {
			$mform->addElement('header', 'status_head', get_string('status', 'local_obu_application'), '');
			$mform->setExpanded('status_head');
			$mform->addElement('html', $data->status_text); // output any status text
		}

        // Contact details
		if ($data->button_text == 'approve') {
			$mform->addElement('header', 'contactdetails', get_string('applicantdetails', 'local_obu_application'), '');
			$mform->setExpanded('contactdetails');
		} else {
			$mform->addElement('header', 'contactdetails', get_string('contactdetails', 'local_obu_application'), '');
		}
		$mform->addElement('static', 'name', get_string('name', 'local_obu_application'));
		if ($approval_sought != 2) { // All bar the funder
			$mform->addElement('static', 'address_1', get_string('address_1', 'local_obu_application'));
			$mform->addElement('static', 'address_2', get_string('address_2', 'local_obu_application'));
			$mform->addElement('static', 'address_3', get_string('address_3', 'local_obu_application'));
			$mform->addElement('static', 'city', get_string('city', 'local_obu_application'));
			$mform->addElement('static', 'postcode', get_string('postcode', 'local_obu_application'));
			$mform->addElement('static', 'domicile_country', get_string('domicile_country', 'local_obu_application'));
		}
		$mform->addElement('static', 'home_phone', get_string('home_phone', 'local_obu_application'));
		$mform->addElement('static', 'mobile_phone', get_string('mobile_phone', 'local_obu_application'));
		$mform->addElement('static', 'email', get_string('email'));

		if ($approval_sought != 2) { // All bar the funder

			// General details
			$mform->addElement('header', 'general_head', get_string('general_head', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('general_head');
			}
			$mform->addElement('static', 'birth_country', get_string('birth_country', 'local_obu_application'));
			$mform->addElement('static', 'birthdate', get_string('birthdate', 'local_obu_application'));
			$mform->addElement('static', 'nationality', get_string('nationality', 'local_obu_application'));
			$mform->addElement('static', 'gender', get_string('gender', 'local_obu_application'));
			$mform->addElement('static', 'residence_area', get_string('residence_area', 'local_obu_application'));

			// Education
			$mform->addElement('header', 'education_head', get_string('education_head', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('education_head');
			}
			$mform->addElement('static', 'p16school', get_string('p16school', 'local_obu_application'));
			$mform->addElement('static', 'p16schoolperiod', get_string('period', 'local_obu_application'));
			$mform->addElement('static', 'p16fe', get_string('p16fe', 'local_obu_application'));
			$mform->addElement('static', 'p16feperiod', get_string('period', 'local_obu_application'));
			$mform->addElement('static', 'training', get_string('training', 'local_obu_application'));
			$mform->addElement('static', 'trainingperiod', get_string('period', 'local_obu_application'));

			// Professional qualifications
			$mform->addElement('header', 'prof_qual_head', get_string('prof_qual_head', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('prof_qual_head');
			}
			$mform->addElement('static', 'prof_level', get_string('prof_level', 'local_obu_application'));
			$mform->addElement('static', 'prof_award', get_string('prof_award', 'local_obu_application'));
			$mform->addElement('static', 'prof_date_formatted', get_string('prof_date', 'local_obu_application'));
			$mform->addElement('static', 'credit_formatted', get_string('credit', 'local_obu_application'));
			$mform->addElement('static', 'credit_name', get_string('credit_name', 'local_obu_application'));
			$mform->addElement('static', 'credit_organisation', get_string('credit_organisation', 'local_obu_application'));

			// Current employment
			$mform->addElement('header', 'employment_head', get_string('employment_head', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('employment_head');
			}
			$mform->addElement('static', 'emp_place', get_string('emp_place', 'local_obu_application'));
			$mform->addElement('static', 'emp_area', get_string('emp_area', 'local_obu_application'));
			$mform->addElement('static', 'emp_title', get_string('emp_title', 'local_obu_application'));
			$mform->addElement('static', 'emp_prof', get_string('emp_prof', 'local_obu_application'));

			// Professional registration
			$mform->addElement('header', 'prof_reg_head', get_string('prof_reg_head', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('prof_reg_head');
			}
			$mform->addElement('static', 'prof_reg_no', get_string('prof_reg_no', 'local_obu_application'));

			// Criminal record
			$mform->addElement('header', 'criminal_record_head', get_string('criminal_record_head', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('criminal_record_head');
			}
			$mform->addElement('static', 'criminal_record_formatted', get_string('criminal_record', 'local_obu_application'));
		}

        // Course name
		$mform->addElement('header', 'course_head', get_string('course', 'local_obu_application'), '');
		if ($data->button_text == 'approve') {
			$mform->setExpanded('course_head');
		}
		$mform->addElement('static', 'course_name', get_string('name', 'local_obu_application'));
		$mform->addElement('static', 'course_date', get_string('course_date', 'local_obu_application'));

		// Currently studying?
		$mform->addElement('static', 'studying_formatted', get_string('studying', 'local_obu_application'));
		$mform->addElement('static', 'student_number', get_string('student_number', 'local_obu_application'));

        // Supporting statement
		$mform->addElement('header', 'statement_head', get_string('statement_head', 'local_obu_application'), '');
		if ($data->button_text == 'approve') {
			$mform->setExpanded('statement_head');
		}
		$mform->addElement('static', 'statement', get_string('statement', 'local_obu_application'));

		if ($data->record->statement != ""){
            $mform->addElement('submit', 'statementbutton', get_string('export_statement', 'local_obu_application'));
        }

		if ($approval_sought != 2) { // All bar the funder

			if ($data->record->nationality != 'GB') {

				// Visa requirement?
				$mform->addElement('header', 'visa_head', get_string('visa_requirement', 'local_obu_application'), '');
				if ($data->button_text == 'approve') {
					$mform->setExpanded('visa_head');
				}
				$mform->addElement('static', 'visa_requirement', get_string('visa_requirement', 'local_obu_application'));
				if ($visa_requirement != 'NONE') {
					unpack_supplement_data($data->record->visa_data, $fields);
					if (!empty($fields)) {
						$supplement = read_supplement_form($fields['supplement'], $fields['version']);
						if ($supplement !== false) {
							$this->supplement_display($supplement, $fields);
						}
					}
				}
			}

			// Supplementary course information (if any)
			unpack_supplement_data($data->record->supplement_data, $fields);
			if (!empty($fields)) {
				$supplement = read_supplement_form($fields['supplement'], $fields['version']);
				if ($supplement !== false) {
					$mform->addElement('header', 'supplement_head', get_string('course_supplement', 'local_obu_application'), '');
					if ($data->button_text == 'approve') {
						$mform->setExpanded('supplement_head');
					}
					$this->supplement_display($supplement, $fields);
				}
			}

			// Declaration
			$mform->addElement('header', 'declaration_head', get_string('declaration', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('declaration_head');
			}
			$mform->addElement('static', 'self_funding_formatted', get_string('self_funding', 'local_obu_application'));
			$mform->addElement('static', 'declaration_formatted', get_string('declaration', 'local_obu_application'));
		}

		// Funder/funding
		if (($approval_sought > 0) && ($data->record->self_funding == '1')) {
			$mform->addElement('html', '<h2>' . get_string('self_funding', 'local_obu_application') . ' ' . get_string('applicant', 'local_obu_application') . '</h2>');
		} else if (($data->record->approval_level > 0) && ($data->record->self_funding == '0')) { // Approving funder must enter the funding details and Administrator/HLS must see funder/funding
			$mform->addElement('header', 'funding', get_string('funding', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('funding');
			}
			if (($approval_sought != 2) && ($data->record->approval_level < 3)) { // Administrator/Manager
				$mform->addElement('static', 'funding_organisation', get_string('organisation', 'local_obu_application'));
				$mform->addElement('static', 'funder_email', get_string('email'));
			} else if ($approval_sought == 2) { // Funder
				if ($data->record->funding_organisation != '') { // Must be an organisation previously selected
					$mform->addElement('text', 'funder_name', get_string('funder_name', 'local_obu_application'), 'size="40" maxlength="100"');
					$mform->setType('funder_name', PARAM_TEXT);
					$options = [];
//					if ($data->record->funding_method == 0) {
//						$options['0'] = get_string('select', 'local_obu_application');
//					}
					$options['1'] = get_string('invoice', 'local_obu_application');
					if ($data->record->funding_method == 2) {
						$options['2'] = get_string('prepaid', 'local_obu_application');
					}
					if ($data->record->funding_method == 3) {
						$options['3'] = get_string('contract', 'local_obu_application');
					}
					$mform->addElement('select', 'funding_method', get_string('funding_method', 'local_obu_application'), $options);
					$mform->addElement('static', 'invoice_text', get_string('invoice_text', 'local_obu_application'));
					$mform->addElement('text', 'invoice_ref', get_string('invoice_ref', 'local_obu_application'), 'size="40" maxlength="100"');
					$mform->setType('invoice_ref', PARAM_TEXT);
					$mform->disabledIf('invoice_ref', 'funding_method', 'neq', '1');
					$mform->addElement('textarea', 'invoice_address', get_string('address'), 'cols="40" rows="5"');
					$mform->setType('invoice_address', PARAM_TEXT);
					$mform->disabledIf('invoice_address', 'funding_method', 'neq', '1');
					$mform->addElement('text', 'invoice_email', get_string('email'), 'size="40" maxlength="100"');
					$mform->setType('invoice_email', PARAM_RAW_TRIMMED);
					$mform->disabledIf('invoice_email', 'funding_method', 'neq', '1');
					$mform->addElement('text', 'invoice_phone', get_string('phone', 'local_obu_application'), 'size="40" maxlength="100"');
					$mform->setType('invoice_phone', PARAM_TEXT);
					$mform->disabledIf('invoice_phone', 'funding_method', 'neq', '1');
					$mform->addElement('text', 'invoice_contact', get_string('invoice_contact', 'local_obu_application'), 'size="40" maxlength="100"');
					$mform->setType('invoice_contact', PARAM_TEXT);
					$mform->disabledIf('invoice_contact', 'funding_method', 'neq', '1');
				} else { // 'Other Organisation' (must be payable by invoice)
					$mform->addElement('text', 'funding_organisation', get_string('organisation', 'local_obu_application'), 'size="40" maxlength="100"');
					$mform->setType('funding_organisation', PARAM_TEXT);
					$mform->addElement('text', 'invoice_ref', get_string('invoice_ref', 'local_obu_application'), 'size="40" maxlength="100"');
					$mform->setType('invoice_ref', PARAM_TEXT);
					$mform->addElement('textarea', 'invoice_address', get_string('address'), 'cols="40" rows="5"');
					$mform->setType('invoice_address', PARAM_TEXT);
					$mform->addElement('text', 'invoice_email', get_string('email'), 'size="40" maxlength="100"');
					$mform->setType('invoice_email', PARAM_RAW_TRIMMED);
					$mform->addElement('text', 'invoice_phone', get_string('phone', 'local_obu_application'), 'size="40" maxlength="100"');
					$mform->setType('invoice_phone', PARAM_TEXT);
					$mform->addElement('text', 'invoice_contact', get_string('invoice_contact', 'local_obu_application'), 'size="40" maxlength="100"');
					$mform->setType('invoice_contact', PARAM_TEXT);
				}
				if (is_programme($data->record->course_code)) {
					$mform->addElement('html', '<p></p><strong><i>' . get_string('programme_preamble', 'local_obu_application') . '</i></strong><p></p>');
                    $mform->addElement('select', 'fund_programme', get_string('fund_programme', 'local_obu_application'), array("0"=>"No", "1"=>"Yes"));
					$mform->addElement('text', 'fund_module_1', get_string('fund_module', 'local_obu_application'), 'size="8" maxlength="8"');
					$mform->setType('fund_module_1', PARAM_TEXT);
					$mform->disabledIf('fund_module_1', 'fund_programme', 'eq', '1');
					$mform->addElement('text', 'fund_module_2', get_string('fund_module', 'local_obu_application'), 'size="8" maxlength="8"');
					$mform->setType('fund_module_2', PARAM_TEXT);
					$mform->disabledIf('fund_module_2', 'fund_programme', 'eq', '1');
					$mform->addElement('text', 'fund_module_3', get_string('fund_module', 'local_obu_application'), 'size="8" maxlength="8"');
					$mform->setType('fund_module_3', PARAM_TEXT);
					$mform->disabledIf('fund_module_3', 'fund_programme', 'eq', '1');
					$mform->addElement('text', 'fund_module_4', get_string('fund_module', 'local_obu_application'), 'size="8" maxlength="8"');
					$mform->setType('fund_module_4', PARAM_TEXT);
					$mform->disabledIf('fund_module_4', 'fund_programme', 'eq', '1');
					$mform->addElement('text', 'fund_module_5', get_string('fund_module', 'local_obu_application'), 'size="8" maxlength="8"');
					$mform->setType('fund_module_5', PARAM_TEXT);
					$mform->disabledIf('fund_module_5', 'fund_programme', 'eq', '1');
					$mform->addElement('text', 'fund_module_6', get_string('fund_module', 'local_obu_application'), 'size="8" maxlength="8"');
					$mform->setType('fund_module_6', PARAM_TEXT);
					$mform->disabledIf('fund_module_6', 'fund_programme', 'eq', '1');
					$mform->addElement('text', 'fund_module_7', get_string('fund_module', 'local_obu_application'), 'size="8" maxlength="8"');
					$mform->setType('fund_module_7', PARAM_TEXT);
					$mform->disabledIf('fund_module_7', 'fund_programme', 'eq', '1');
					$mform->addElement('text', 'fund_module_8', get_string('fund_module', 'local_obu_application'), 'size="8" maxlength="8"');
					$mform->setType('fund_module_8', PARAM_TEXT);
					$mform->disabledIf('fund_module_8', 'fund_programme', 'eq', '1');
					$mform->addElement('text', 'fund_module_9', get_string('fund_module', 'local_obu_application'), 'size="8" maxlength="8"');
					$mform->setType('fund_module_9', PARAM_TEXT);
					$mform->disabledIf('fund_module_9', 'fund_programme', 'eq', '1');
				}
			} else { // Funding available for HLS to view
				$mform->addElement('static', 'funding_method', get_string('funding_method', 'local_obu_application'));
				$mform->addElement('static', 'funding_organisation', get_string('organisation', 'local_obu_application'));
				if ($data->record->funding_method > 0) { // NHS trust
					$mform->addElement('static', 'funder_name', get_string('funder_name', 'local_obu_application'));
				}
				if ($data->record->funding_method < 2) { // By invoice
					$mform->addElement('static', 'invoice_ref', get_string('invoice_ref', 'local_obu_application'));
					$mform->addElement('static', 'invoice_address', get_string('address'));
					$mform->addElement('static', 'invoice_email', get_string('email'));
					$mform->addElement('static', 'invoice_phone', get_string('phone', 'local_obu_application'));
					$mform->addElement('static', 'invoice_contact', get_string('invoice_contact', 'local_obu_application'));
				}
				if (is_programme($data->record->course_code)) { // Additional funding fields for a programme of study
					$mform->addElement('static', 'fund_programme_formatted', get_string('fund_programme', 'local_obu_application'));
					if ($data->record->fund_programme == '0') {
						if ($data->record->fund_module_1 != '') {
							$mform->addElement('static', 'fund_module_1', get_string('fund_module', 'local_obu_application'));
						}
						if ($data->record->fund_module_2 != '') {
							$mform->addElement('static', 'fund_module_2', get_string('fund_module', 'local_obu_application'));
						}
						if ($data->record->fund_module_3 != '') {
							$mform->addElement('static', 'fund_module_3', get_string('fund_module', 'local_obu_application'));
						}
						if ($data->record->fund_module_4 != '') {
							$mform->addElement('static', 'fund_module_4', get_string('fund_module', 'local_obu_application'));
						}
						if ($data->record->fund_module_5 != '') {
							$mform->addElement('static', 'fund_module_5', get_string('fund_module', 'local_obu_application'));
						}
						if ($data->record->fund_module_6 != '') {
							$mform->addElement('static', 'fund_module_6', get_string('fund_module', 'local_obu_application'));
						}
						if ($data->record->fund_module_7 != '') {
							$mform->addElement('static', 'fund_module_7', get_string('fund_module', 'local_obu_application'));
						}
						if ($data->record->fund_module_8 != '') {
							$mform->addElement('static', 'fund_module_8', get_string('fund_module', 'local_obu_application'));
						}
						if ($data->record->fund_module_9 != '') {
							$mform->addElement('static', 'fund_module_9', get_string('fund_module', 'local_obu_application'));
						}
					}
				}
			}
		}

		// Options
		$buttonarray = array();
		if ($data->button_text != 'cancel' && $data->button_text != 'revoke') {
			$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string($data->button_text, 'local_obu_application'));
		}
		if ($data->button_text != 'continue') {
			if ($data->button_text == 'approve') {
				$mform->addElement('static', 'approval', '');
				$mform->closeHeaderBefore('approval');
                if ($approval_sought == 1){
                    $mform->addElement('html', '<h3>' . get_string('approval_head', 'local_obu_application') . '</h3>');
                    $mform->addElement('html', '<p><strong>' . get_string('manager_comment', 'local_obu_application') . '</strong></p>');
                    $mform->addElement('text', 'comment', 'Comment:', 'size="40" maxlength="100"');
                    $mform->setType('comment', PARAM_TEXT);
                } else if ($approval_sought == 2){
                    $mform->addElement('html', '<h3>' . get_string('approval_head', 'local_obu_application') . '</h3>');
                    $mform->addElement('html', '<p><strong>' . get_string('funder_comment', 'local_obu_application') . '</strong></p>');
                    $mform->addElement('text', 'comment', 'Comment:', 'size="40" maxlength="100"');
                    $mform->setType('comment', PARAM_TEXT);
                } else {
                    $mform->addElement('html', '<h3>' . get_string('approval_head', 'local_obu_application') . '</h3>');
                    $mform->addElement('html', '<p><strong>' . get_string('admin_comment', 'local_obu_application') . '</strong></p>');
                    $mform->addElement('text', 'comment', 'Comment:', 'size="40" maxlength="100"');
                    $mform->setType('comment', PARAM_TEXT);
                }
				$buttonarray[] = &$mform->createElement('submit', 'rejectbutton', get_string('reject', 'local_obu_application'));
				if (is_manager() && (($approval_sought == 1) || ($approval_sought == 3))) { // HLS
                    if ($data->record->supplement_data){
                        $buttonarray[] = &$mform->createElement('submit', 'amendsupplementdocbutton', get_string('amend_supplement_documents', 'local_obu_application'));
                    }
                    if ($data->record->visa_data){
                        $buttonarray[] = &$mform->createElement('submit', 'amendvisabutton', get_string('amend_visa', 'local_obu_application'));
                    }
                    $buttonarray[] = &$mform->createElement('submit', 'amenddetailsbutton', get_string('amend_details', 'local_obu_application'));
					$buttonarray[] = &$mform->createElement('submit', 'amendcoursebutton', get_string('amend_course', 'local_obu_application'));
					if ($data->record->self_funding == '0') {
						if ($approval_sought == 1) {
							$buttonarray[] = &$mform->createElement('submit', 'amendfunderbutton', get_string('amend_funder', 'local_obu_application'));
						}
						else {
							$buttonarray[] = &$mform->createElement('submit', 'amendfundingbutton', get_string('amend_funding', 'local_obu_application'));
						}
					}
				}
			} else if ($data->button_text == 'revoke') { // A manager can revoke or withdraw an HLS-approved application
                $buttonarray[] = &$mform->createElement('submit', 'revokebutton', get_string('revoke', 'local_obu_application'));
				$buttonarray[] = &$mform->createElement('submit', 'withdrawbutton', get_string('withdraw', 'local_obu_application'));
			}
			$buttonarray[] = &$mform->createElement('cancel');
		}
		$mform->addGroup($buttonarray, 'buttonarray', '', array(' '), false);
		$mform->closeHeaderBefore('buttonarray');
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

		// Check that we have been given sufficient information for an approval
		if ($data['submitbutton'] == get_string('approve', 'local_obu_application')) {
			if ($data['approval_level'] == '2') { // Funder must give us the funding details
				if ($data['nhs_trust'] == '') { // Invoice to a non-NHS organisation
					if ($data['funding_organisation'] == '') {
						$errors['funding_organisation'] = get_string('value_required', 'local_obu_application');
					}
					if ($data['invoice_ref'] == '') {
						$errors['invoice_ref'] = get_string('value_required', 'local_obu_application');
					}
					if ($data['invoice_address'] == '') {
						$errors['invoice_address'] = get_string('value_required', 'local_obu_application');
					}
					if ($data['invoice_email'] == '') {
						$errors['invoice_email'] = get_string('value_required', 'local_obu_application');
					}
					if ($data['invoice_phone'] == '') {
						$errors['invoice_phone'] = get_string('value_required', 'local_obu_application');
					}
					if ($data['invoice_contact'] == '') {
						$errors['invoice_contact'] = get_string('value_required', 'local_obu_application');
					}
				} else { // NHS trust
					if ($data['funder_name'] == '') {
						$errors['funder_name'] = get_string('value_required', 'local_obu_application');
					}
					if ($data['funding_method'] == 0) {
						$errors['funding_method'] = get_string('value_required', 'local_obu_application');
					} else if ($data['funding_method'] == 1) { // Invoice
						if ($data['invoice_ref'] == '') {
							$errors['invoice_ref'] = get_string('value_required', 'local_obu_application');
						}
						if ($data['invoice_address'] == '') {
							$errors['invoice_address'] = get_string('value_required', 'local_obu_application');
						}
						if ($data['invoice_email'] == '') {
							$errors['invoice_email'] = get_string('value_required', 'local_obu_application');
						}
						if ($data['invoice_phone'] == '') {
							$errors['invoice_phone'] = get_string('value_required', 'local_obu_application');
						}
						if ($data['invoice_contact'] == '') {
							$errors['invoice_contact'] = get_string('value_required', 'local_obu_application');
						}
					} else { // Contract or Pre-paid
						if ($data['invoice_ref'] != '') {
							$errors['invoice_ref'] = get_string('value_verboten', 'local_obu_application');
						}
						if ($data['invoice_email'] != '') {
							$errors['invoice_email'] = get_string('value_verboten', 'local_obu_application');
						}
						if ($data['invoice_phone'] != '') {
							$errors['invoice_phone'] = get_string('value_verboten', 'local_obu_application');
						}
						if ($data['invoice_contact'] != '') {
							$errors['invoice_contact'] = get_string('value_verboten', 'local_obu_application');
						}
					}
				}
			}
		}

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }

	function supplement_display($form, $fields) {

		$fld_start = '<input ';
		$fld_start_len = strlen($fld_start);
		$fld_end = '>';
		$fld_end_len = strlen($fld_end);
		$offset = 0;
		$date_format = 'd-m-y';

		do {
			$pos = strpos($form->template, $fld_start, $offset);
			if ($pos === false) {
				break;
			}
			if ($pos > $offset) {
				$this->_form->addElement('html', substr($form->template, $offset, ($pos - $offset))); // output any HTML
			}
			$offset = $pos + $fld_start_len;
			$pos = strpos($form->template, $fld_end, $offset);
			if ($pos === false) {
				break;
			}
			$element = split_input_field(substr($form->template, $offset, ($pos - $offset)));
			$offset = $pos + $fld_end_len;
			$text = $fields[$element['id']];
			if ($element['type'] == 'checkbox') { // map a checkbox value to a nice character
				if ($text == '1') {
					$text = '&#10004;';
				} else {
					$text = '';
				}
			}
			if (($element['type'] == 'date') && ($text)) { // map a UNIX date to a nice text string
				$date = date_create();
				date_timestamp_set($date, $text);
				$text = date_format($date, $date_format);
			}
			if (($element['type'] == 'file') && ($text)) { // map a file pathname hash to a URL for the file
				$text = get_file_link($text);
			}
			$this->_form->addElement('static', $element['id'], $element['value'], $text);
		} while(true);

		$this->_form->addElement('html', substr($form->template, $offset)); // output any remaining HTML

		return;
	}
}
