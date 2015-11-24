<?php

// This file is part of Moodle - http://moodle.org/
//
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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class process_view extends moodleform {

    function definition() {
        global $USER;
		
        $mform =& $this->_form;

        $data = new stdClass();
		$data->record = $this->_customdata['record'];
		$data->status_text = $this->_customdata['status_text'];
		$data->button_text = $this->_customdata['button_text'];
		
		$approval_sought = 0; // Level at which we are is seeking approval from this user (if at all)
		if ($data->record !== false) {
			
			// Level (if any) at which we are seeking approval from this user
			if ($data->button_text == 'approve') {
				$approval_sought = $data->record->approval_level;
			}
			
			// Format the fields nicely before we load them into the form
			$date = date_create();
			$date_format = 'd-m-y';
			date_timestamp_set($date, $data->record->birthdate);
			$birthdate_formatted = date_format($date, $date_format);
			date_timestamp_set($date, $data->record->prof_date);
			$prof_date_formatted = date_format($date, $date_format);
			if ($data->record->criminal_record == '1') {
				$criminal_record_formatted = 'Yes';
			} else {
				$criminal_record_formatted = 'No';
			}
			if ($data->record->self_funding == '1') {
				$self_funding_formatted = '&#10004;'; // Tick
			} else {
				$self_funding_formatted = '&#10008;'; // Cross
			}
			$self_funding_formatted .= ' ' . get_string('self_funding_text', 'local_obu_application');
			if ($data->record->declaration == '1') {
				$declaration_formatted = '&#10004;'; // Tick
			} else {
				$declaration_formatted = '&#10008;'; // Cross
			}
			$declaration_formatted .= ' ' . get_string('declaration_text', 'local_obu_application');
			
			$fields = [
				'name' => $data->record->title . ' ' . $data->record->firstname . ' ' . $data->record->lastname,
				'title' => $data->record->title,
				'firstname' => $data->record->firstname,
				'lastname' => $data->record->lastname,
				'address' => $data->record->address,
				'postcode' => $data->record->postcode,
				'phone' => $data->record->phone,
				'email' => $data->record->email,
				'birthdate_formatted' => $birthdate_formatted,
				'birthcountry' => $data->record->birthcountry,
				'firstentrydate' => $data->record->firstentrydate,
				'lastentrydate' => $data->record->lastentrydate,
				'residencedate' => $data->record->residencedate,
				'support' => $data->record->support,
				'p16school' => $data->record->p16school,
				'p16schoolperiod' => $data->record->p16schoolperiod,
				'p16fe' => $data->record->p16fe,
				'p16feperiod' => $data->record->p16feperiod,
				'training' => $data->record->training,
				'trainingperiod' => $data->record->trainingperiod,
				'prof_level' => $data->record->prof_level,
				'prof_award' => $data->record->prof_award,
				'prof_date_formatted' => $prof_date_formatted,
				'emp_place' => $data->record->emp_place,
				'emp_area' => $data->record->emp_area,
				'emp_title' => $data->record->emp_title,
				'emp_prof' => $data->record->emp_prof,
				'prof_reg_no' => $data->record->prof_reg_no,
				'criminal_record_formatted' => $criminal_record_formatted,
				'award_name' => $data->record->award_name,
				'start_date' => $data->record->start_date,
				'module_1_no' => $data->record->module_1_no,
				'module_1_name' => $data->record->module_1_name,
				'module_2_no' => $data->record->module_2_no,
				'module_2_name' => $data->record->module_2_name,
				'module_3_no' => $data->record->module_3_no,
				'module_3_name' => $data->record->module_3_name,
				'statement' => $data->record->statement,
				'self_funding_formatted' => $self_funding_formatted,
				'manager_email' => $data->record->manager_email,
				'declaration_formatted' => $declaration_formatted,
				'contract_trust' => $data->record->contract_trust,
				'contract_tel' => $data->record->contract_tel,
				'contract_percentage' => $data->record->contract_percentage,
				'invoice_name' => $data->record->invoice_name,
				'invoice_ref' => $data->record->invoice_ref,
				'invoice_address' => $data->record->invoice_address,
				'invoice_email' => $data->record->invoice_email,
				'invoice_phone' => $data->record->invoice_phone,
				'invoice_contact' => $data->record->invoice_contact,
				'invoice_percentage' => $data->record->invoice_percentage,
				'prepaid_trust' => $data->record->prepaid_trust,
				'prepaid_tel' => $data->record->prepaid_tel,
				'prepaid_percentage' => $data->record->prepaid_percentage
			];
			$this->set_data($fields);
		}
		
		// Start with the required hidden field
		$mform->addElement('hidden', 'id', $data->record->id);

		// Our own hidden fields (for use in form validation)
		$mform->addElement('hidden', 'approval_level', $data->record->approval_level);
		$mform->addElement('hidden', 'self_funding', $data->record->self_funding);

		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');
		
		// Application status
		if (!empty($data->status_text)) {
			$mform->addElement('header', 'status_head', get_string('status', 'local_obu_application'), '');
			$mform->setExpanded('status_head');
			$mform->addElement('html', '<p /><strong>' . $data->status_text . '</strong>'); // output any status text
		}

        // Contact details
		$mform->addElement('header', 'contactdetails', get_string('contactdetails', 'local_obu_application'), '');
		if ($data->button_text == 'approve') {
			$mform->setExpanded('contactdetails');
		}
		$mform->addElement('static', 'name', get_string('name', 'local_obu_application'));
		if (($approval_sought == 0) || ($approval_sought == 3)) {
			$mform->addElement('static', 'address', get_string('address'));
			$mform->addElement('static', 'postcode', get_string('postcode', 'local_obu_application'));
		}
		$mform->addElement('static', 'phone', get_string('phone', 'local_obu_application'));
		$mform->addElement('static', 'email', get_string('email'));

        if (($approval_sought == 0) || ($approval_sought == 3)) {
			
			// Birth details
			$mform->addElement('header', 'birth_head', get_string('birth_head', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('birth_head');
			}
			$mform->addElement('static', 'birthdate_formatted', get_string('birthdate', 'local_obu_application'));
			$mform->addElement('static', 'birthcountry', get_string('birthcountry', 'local_obu_application'));

			// Non-EU details
			$mform->addElement('header', 'non_eu_head', get_string('non_eu_head', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('non_eu_head');
			}
			$mform->addElement('static', 'firstentrydate', get_string('firstentrydate', 'local_obu_application'));
			$mform->addElement('static', 'lastentrydate', get_string('lastentrydate', 'local_obu_application'));
			$mform->addElement('static', 'residencedate', get_string('residencedate', 'local_obu_application'));

			// Support needs
			$mform->addElement('header', 'needs_head', get_string('needs_head', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('needs_head');
			}
			$mform->addElement('static', 'support', get_string('support', 'local_obu_application'));

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

        // Award name
		$mform->addElement('header', 'award_head', get_string('award_head', 'local_obu_application'), '');
		if ($data->button_text == 'approve') {
			$mform->setExpanded('award_head');
		}
		$mform->addElement('static', 'award_name', get_string('award_name', 'local_obu_application'));
		
        // Modules
		$mform->addElement('header', 'module_head', get_string('module_head', 'local_obu_application'), '');
		if ($data->button_text == 'approve') {
			$mform->setExpanded('module_head');
		}
		$mform->addElement('static', 'start_date', get_string('start_date', 'local_obu_application'));
		$mform->addElement('static', 'module_1_no', get_string('module_no', 'local_obu_application'));
		$mform->addElement('static', 'module_1_name', get_string('module_name', 'local_obu_application'));
		$mform->addElement('static', 'module_2_no', get_string('module_no', 'local_obu_application'));
		$mform->addElement('static', 'module_2_name', get_string('module_name', 'local_obu_application'));
		$mform->addElement('static', 'module_3_no', get_string('module_no', 'local_obu_application'));
		$mform->addElement('static', 'module_3_name', get_string('module_name', 'local_obu_application'));
		
        // Supporting statement
		$mform->addElement('header', 'statement_head', get_string('statement_head', 'local_obu_application'), '');
		if ($data->button_text == 'approve') {
			$mform->setExpanded('statement_head');
		}
		$mform->addElement('static', 'statement', get_string('statement', 'local_obu_application'));
		
        if (($approval_sought == 0) && ($data->record->approval_level == 1)) {
			// Manager to approve
			$mform->addElement('header', 'manager_head', get_string('manager_to_approve', 'local_obu_application'), '');
			$mform->addElement('static', 'manager_email', get_string('email'));
		}
		
        if (($approval_sought == 0) || ($approval_sought == 3)) {
			// Declaration
			$mform->addElement('header', 'declaration_head', get_string('declaration', 'local_obu_application'), '');
			if ($data->button_text == 'approve') {
				$mform->setExpanded('declaration_head');
			}
			$mform->addElement('static', 'self_funding_formatted', get_string('self_funding', 'local_obu_application'));
			$mform->addElement('static', 'declaration_formatted', get_string('declaration', 'local_obu_application'));
		}

		if (($approval_sought > 0) && ($data->record->self_funding == '1')) {
			$mform->addElement('html', '<h2>' . get_string('self_funding', 'local_obu_application') . ' ' . get_string('applicant', 'local_obu_application') . '</h2>');
		} else if (($approval_sought == 1) && ($data->record->self_funding == '0')) { // Approving manager must enter the email of TEL to approve
			$mform->addElement('header', 'tel_head', get_string('tel_to_approve', 'local_obu_application'), '');
			$mform->setExpanded('tel_head');
			$mform->addElement('text', 'tel_email', get_string('email'), 'size="40" maxlength="100"');
			$mform->setType('tel_email', PARAM_RAW_TRIMMED);
			$mform->addElement('text', 'tel_email2', get_string('emailagain'), 'size="40" maxlength="100"');
			$mform->setType('tel_email2', PARAM_RAW_TRIMMED);
		} else if (($approval_sought > 1) && ($data->record->self_funding == '0')) { // Approving TEL must enter the complete funding details and HLS approver must see them
			$mform->addElement('html', '<h1>' . get_string('funding', 'local_obu_application') . '</h1>');
			$mform->addElement('header', 'contract_head', get_string('contract', 'local_obu_application'), '');
			$mform->setExpanded('contract_head');
			if ($approval_sought == 2) { // TEL
				$mform->addElement('text', 'contract_trust', get_string('trust', 'local_obu_application'), 'size="40" maxlength="100"');
				$mform->addElement('text', 'contract_tel', get_string('tel', 'local_obu_application'), 'size="40" maxlength="100"');
				$mform->addElement('text', 'contract_percentage', get_string('percentage', 'local_obu_application'), 'size="3" maxlength="3"');
				$mform->setType('contract_percentage', PARAM_INT);
			} else { // HLS
				$mform->addElement('static', 'contract_trust', get_string('trust', 'local_obu_application'));
				$mform->addElement('static', 'contract_tel', get_string('tel', 'local_obu_application'));
				$mform->addElement('static', 'contract_percentage', get_string('percentage', 'local_obu_application'));
			}
			$mform->addElement('header', 'invoice_head', get_string('invoice', 'local_obu_application'), '');
			$mform->setExpanded('invoice_head');
			if ($approval_sought == 2) { // TEL
				$mform->addElement('text', 'invoice_name', get_string('invoice_name', 'local_obu_application'), 'size="40" maxlength="100"');
				$mform->addElement('text', 'invoice_ref', get_string('invoice_ref', 'local_obu_application'), 'size="40" maxlength="100"');
				$mform->addElement('textarea', 'invoice_address', get_string('address'), 'cols="40" rows="5"');
				$mform->addElement('text', 'invoice_email', get_string('email'), 'size="40" maxlength="100"');
				$mform->addElement('text', 'invoice_phone', get_string('phone', 'local_obu_application'), 'size="40" maxlength="100"');
				$mform->addElement('text', 'invoice_contact', get_string('invoice_contact', 'local_obu_application'), 'size="40" maxlength="100"');
				$mform->addElement('text', 'invoice_percentage', get_string('percentage', 'local_obu_application'), 'size="3" maxlength="3"');
				$mform->setType('invoice_percentage', PARAM_INT);
			} else { // HLS
				$mform->addElement('static', 'invoice_name', get_string('invoice_name', 'local_obu_application'));
				$mform->addElement('static', 'invoice_ref', get_string('invoice_ref', 'local_obu_application'));
				$mform->addElement('static', 'invoice_address', get_string('address'));
				$mform->addElement('static', 'invoice_email', get_string('email'));
				$mform->addElement('static', 'invoice_phone', get_string('phone', 'local_obu_application'));
				$mform->addElement('static', 'invoice_contact', get_string('invoice_contact', 'local_obu_application'));
				$mform->addElement('static', 'invoice_percentage', get_string('percentage', 'local_obu_application'));
			}
			if ($approval_sought == 2) { // TEL
				$mform->addElement('text', 'prepaid_trust', get_string('trust', 'local_obu_application'), 'size="40" maxlength="100"');
				$mform->addElement('text', 'prepaid_tel', get_string('tel', 'local_obu_application'), 'size="40" maxlength="100"');
				$mform->addElement('text', 'prepaid_percentage', get_string('percentage', 'local_obu_application'), 'size="3" maxlength="3"');
				$mform->setType('prepaid_percentage', PARAM_INT);
			} else { // HLS
				$mform->addElement('static', 'prepaid_trust', get_string('trust', 'local_obu_application'));
				$mform->addElement('static', 'prepaid_tel', get_string('tel', 'local_obu_application'));
				$mform->addElement('static', 'prepaid_percentage', get_string('percentage', 'local_obu_application'));
			}
		}

		// Options
		$buttonarray = array();
		if ($data->button_text != 'cancel') {
			$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string($data->button_text, 'local_obu_application'));
		}
		if ($data->button_text != 'continue') {
			if ($data->button_text == 'approve') {
				$mform->addElement('text', 'comment', get_string('comment', 'local_obu_application'));
				$buttonarray[] = &$mform->createElement('submit', 'rejectbutton', get_string('reject', 'local_obu_application'));
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
			if (($data['approval_level'] == '1') && ($data['self_funding'] == '0')) { // Manager must give us the email of the TEL to approve
				if (empty($data['tel_email'])) {
					$errors['tel_email'] = get_string('missingemail');
				} else if (!validate_email($data['tel_email'])) {
					$errors['tel_email'] = get_string('invalidemail');
				}
		
				if (empty($data['tel_email2'])) {
					$errors['tel_email2'] = get_string('missingemail');
				} else if ($data['tel_email2'] != $data['tel_email']) {
					$errors['tel_email2'] = get_string('invalidemail');
				}
			} else if ($data['approval_level'] == '2') { // TEL must give us the complete funding details
			}
		}

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}
