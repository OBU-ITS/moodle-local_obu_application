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
		
		if ($data->record !== false) {
			
			// Format the fields nicely before we load them into the form
			$date = date_create();
			$date_format = 'd-m-y';
			date_timestamp_set($date, $data->record->birthdate);
			$birthdate = date_format($date, $date_format);
			date_timestamp_set($date, $data->record->prof_date);
			$prof_date = date_format($date, $date_format);
			if ($data->record->criminal_record == '1') {
				$criminal_record = 'Yes';
			} else {
				$criminal_record = 'No';
			}
			if ($data->record->self_funding == '1') {
				$self_funding = '&#10004;'; // Tick
			} else {
				$self_funding = '&#10008;'; // Cross
			}
			$self_funding .= ' ' . get_string('self_funding_text', 'local_obu_application');
			if ($data->record->declaration == '1') {
				$declaration = '&#10004;'; // Tick
			} else {
				$declaration = '&#10008;'; // Cross
			}
			$declaration .= ' ' . get_string('declaration_text', 'local_obu_application');
			
			$fields = [
				'name' => $data->record->title . ' ' . $data->record->firstname . ' ' . $data->record->lastname,
				'title' => $data->record->title,
				'firstname' => $data->record->firstname,
				'lastname' => $data->record->lastname,
				'address' => $data->record->address,
				'postcode' => $data->record->postcode,
				'phone' => $data->record->phone,
				'email' => $data->record->email,
				'birthdate' => $birthdate,
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
				'prof_date' => $prof_date,
				'emp_place' => $data->record->emp_place,
				'emp_area' => $data->record->emp_area,
				'emp_title' => $data->record->emp_title,
				'emp_prof' => $data->record->emp_prof,
				'prof_reg_no' => $data->record->prof_reg_no,
				'criminal_record' => $criminal_record,
				'award_name' => $data->record->award_name,
				'start_date' => $data->record->start_date,
				'module_1_no' => $data->record->module_1_no,
				'module_1_name' => $data->record->module_1_name,
				'module_2_no' => $data->record->module_2_no,
				'module_2_name' => $data->record->module_2_name,
				'module_3_no' => $data->record->module_3_no,
				'module_3_name' => $data->record->module_3_name,
				'statement' => $data->record->statement,
				'self_funding' => $self_funding,
				'manager_email' => $data->record->manager_email,
				'declaration' => $declaration
			];
			$this->set_data($fields);
		}
		
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
		$mform->addElement('static', 'address', get_string('address'));
		$mform->addElement('static', 'postcode', get_string('postcode', 'local_obu_application'));
		$mform->addElement('static', 'phone', get_string('phone', 'local_obu_application'));
		$mform->addElement('static', 'email', get_string('email'));

        // Birth details
		$mform->addElement('header', 'birth_head', get_string('birth_head', 'local_obu_application'), '');
		if ($data->button_text == 'approve') {
			$mform->setExpanded('birth_head');
		}
		$mform->addElement('static', 'birthdate', get_string('birthdate', 'local_obu_application'));
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
		$mform->addElement('static', 'prof_date', get_string('prof_date', 'local_obu_application'));

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
		$mform->addElement('static', 'criminal_record', get_string('criminal_record', 'local_obu_application'));

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
		
        // Authorising manager
        $mform->addElement('header', 'authoriser_head', get_string('authoriser', 'local_obu_application'), '');
		if ($data->button_text == 'approve') {
			$mform->setExpanded('authoriser_head');
		}
		$mform->addElement('static', 'manager_email', get_string('email'));

        // Declaration
		$mform->addElement('header', 'declaration_head', get_string('declaration', 'local_obu_application'), '');
		if ($data->button_text == 'approve') {
			$mform->setExpanded('declaration_head');
		}
		$mform->addElement('static', 'self_funding', get_string('self_funding', 'local_obu_application'));
		$mform->addElement('static', 'declaration', get_string('declaration', 'local_obu_application'));

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
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}
