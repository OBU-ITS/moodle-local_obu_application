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
 * OBU Application - Amend funding form [Moodle]
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class mdl_amend_funding_form extends moodleform {

    function definition() {
		
        $mform =& $this->_form;

        $data = new stdClass();
		$data->organisations = $this->_customdata['organisations'];
		$data->application = $this->_customdata['application'];
		
		if ($data->application->funding_method == 0) { // non-NHS
			$funding_method_formatted = get_string('other', 'local_obu_application') . ' (' . get_string('invoice', 'local_obu_application') . ')';
		} else { // NHS trust
			$funding_method_formatted = get_string('trust', 'local_obu_application') . ' (';
			if ($data->application->funding_method == 1) {
				$funding_method_formatted .= get_string('invoice', 'local_obu_application');
			} else if ($data->application->funding_method == 2) {
				$funding_method_formatted .= get_string('prepaid', 'local_obu_application');
			} else {
				$funding_method_formatted .= get_string('contract', 'local_obu_application');
			}
			$funding_method_formatted .= ')';
		}
		
		$fields = [
			'current_funding_method' => $funding_method_formatted,
			'current_funding_organisation' => $data->application->funding_organisation,
			'current_funder_name' => $data->application->funder_name,
			'current_invoice_ref' => $data->application->invoice_ref,
			'current_invoice_address' => $data->application->invoice_address,
			'current_invoice_email' => $data->application->invoice_email,
			'current_invoice_phone' => $data->application->invoice_phone,
			'current_invoice_contact' => $data->application->invoice_contact,
			'funding_organisation' => $data->application->funding_organisation,
			'funder_name' => $data->application->funder_name,
			'invoice_ref' => $data->application->invoice_ref,
			'invoice_address' => $data->application->invoice_address,
			'invoice_email' => $data->application->invoice_email,
			'invoice_phone' => $data->application->invoice_phone,
			'invoice_contact' => $data->application->invoice_contact
		];
		$this->set_data($fields);
		
		$mform->addElement('hidden', 'id', $data->application->id);
		$mform->setType('id', PARAM_RAW);
		
		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

        // Current funding
		$mform->addElement('header', 'current_funding_head', get_string('funding', 'local_obu_application'), '');
		$mform->setExpanded('current_funding_head');
		$mform->addElement('static', 'current_funding_method', get_string('funding_method', 'local_obu_application'));
		$mform->addElement('static', 'current_funding_organisation', get_string('organisation', 'local_obu_application'));
		if ($data->application->funding_method > 0) { // NHS trust
			$mform->addElement('static', 'current_funder_name', get_string('funder_name', 'local_obu_application'));
		}
		if ($data->application->funding_method < 2) { // By invoice
			$mform->addElement('static', 'current_invoice_ref', get_string('invoice_ref', 'local_obu_application'));
			$mform->addElement('static', 'current_invoice_address', get_string('address'));
			$mform->addElement('static', 'current_invoice_email', get_string('email'));
			$mform->addElement('static', 'current_invoice_phone', get_string('phone', 'local_obu_application'));
			$mform->addElement('static', 'current_invoice_contact', get_string('invoice_contact', 'local_obu_application'));
		}

        // Amended funding
		$mform->addElement('html', '<h1>' . get_string('funding_organisation', 'local_obu_application') . '</h1>');
		$mform->addElement('header', 'trust_head', get_string('trust', 'local_obu_application'), '');
		$mform->addElement('select', 'funding_organisation', get_string('organisation', 'local_obu_application'), $data->organisations, null);
		$mform->addElement('text', 'funder_name', get_string('funder_name', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('funder_name', PARAM_TEXT);
		$radioarray = array();
		$radioarray[] = $mform->createElement('radio', 'funding_method', '', get_string('contract', 'local_obu_application') . ' | ', 3);
		$radioarray[] = $mform->createElement('radio', 'funding_method', '', get_string('prepaid', 'local_obu_application') . ' | ', 2);
		$radioarray[] = $mform->createElement('radio', 'funding_method', '', get_string('invoice', 'local_obu_application'), 1);
		if ($data->application->funding_method > 0) {
			$mform->setDefault('funding_method', $data->application->funding_method); // Current method
		} else {
			$mform->setDefault('funding_method', 3); // Contract
		}
		$mform->addGroup($radioarray, 'funding_methods', get_string('funding_method', 'local_obu_application'), array(' '), false);
		$mform->addElement('static', 'invoice_text', get_string('invoice_text', 'local_obu_application'));
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
		$mform->addElement('header', 'other_head', get_string('other', 'local_obu_application'), '');
		$mform->addElement('text', 'other_organisation', get_string('organisation', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('other_organisation', PARAM_TEXT);
		$mform->addElement('text', 'other_ref', get_string('invoice_ref', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('other_ref', PARAM_TEXT);
		$mform->addElement('textarea', 'other_address', get_string('address'), 'cols="40" rows="5"');
		$mform->setType('other_address', PARAM_TEXT);
		$mform->addElement('text', 'other_email', get_string('email'), 'size="40" maxlength="100"');
		$mform->setType('other_email', PARAM_RAW_TRIMMED);
		$mform->addElement('text', 'other_phone', get_string('phone', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('other_phone', PARAM_TEXT);
		$mform->addElement('text', 'other_contact', get_string('invoice_contact', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('other_contact', PARAM_TEXT);
		
		$this->add_action_buttons(true, get_string('save', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

		if (($data['other_organisation'] != '') || ($data['other_ref'] != '') || ($data['other_address'] != '')
			|| ($data['other_email'] != '') || ($data['other_phone'] != '') || ($data['other_contact'] != '')) { // Invoice to a non-NHS organisation
			if ($data['other_organisation'] == '') {
				$errors['other_organisation'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['other_ref'] == '') {
				$errors['other_ref'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['other_address'] == '') {
				$errors['other_address'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['other_email'] == '') {
				$errors['other_email'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['other_phone'] == '') {
				$errors['other_phone'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['other_contact'] == '') {
				$errors['other_contact'] = get_string('value_required', 'local_obu_application');
			}
		} else { // NHS trust
			if ($data['funder_name'] == '') {
				$errors['funder_name'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['funding_method'] == 1) { // Invoice
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
				if ($data['invoice_address'] != '') {
					$errors['invoice_address'] = get_string('value_verboten', 'local_obu_application');
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

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}
