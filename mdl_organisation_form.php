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
 * OBU Application - Organisation maintenance form
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2021, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class mdl_organisation_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->id = $this->_customdata['id'];
		$data->delete = $this->_customdata['delete'];
		$data->organisations = $this->_customdata['organisations'];
		$data->record = $this->_customdata['record'];
		$data->applications = $this->_customdata['applications'];

		if ($data->record != null) {
			$fields = [
				'name' => $data->record->name,
				'email' => $data->record->email,
				'code' => $data->record->code,
				'address' => $data->record->address,
				'suspended' => $data->record->suspended
			];
			$this->set_data($fields);
		}
		
		$mform->addElement('html', '<h2>' . get_string('update_organisation', 'local_obu_application') . '</h2>');

		if ($data->id == '') {
			$select = $mform->addElement('select', 'id', get_string('organisation', 'local_obu_application'), $data->organisations, null);
			$select->setSelected(0);
			$this->add_action_buttons(true, get_string('continue', 'local_obu_application'));
			return;
		}
		
		$mform->addElement('hidden', 'id', $data->id);
		$mform->setType('id', PARAM_RAW);
		
		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');
		
		if ($data->delete) {
			$mform->addElement('static', 'name', get_string('name', 'local_obu_application'));
			$mform->addElement('static', 'email', get_string('funder_email', 'local_obu_application'));
			$mform->addElement('static', 'code', get_string('contract_code', 'local_obu_application'));
			$mform->addElement('static', 'address', get_string('address'));
			if ($data->record->suspended == '1') {
				$suspended_formatted = '&#10004;'; // Tick
			} else {
				$suspended_formatted = '&#10008;'; // Cross
			}
			$mform->addElement('static', 'suspended_formatted', get_string('suspended', 'local_obu_application'), $suspended_formatted);
		} else {
			$mform->addElement('text', 'name', get_string('name', 'local_obu_application'), 'size="50" maxlength="100"');
			$mform->setType('name', PARAM_TEXT);
			$mform->addElement('text', 'email', get_string('funder_email', 'local_obu_application'), 'size="50" maxlength="100"');
			$mform->setType('email', PARAM_TEXT);
			$mform->addElement('text', 'code', get_string('contract_code', 'local_obu_application'), 'size="10" maxlength="10"');
			$mform->setType('code', PARAM_TEXT);
			$mform->addElement('textarea', 'address', get_string('address'), 'cols="40" rows="5"');
			$mform->setType('address', PARAM_TEXT);
			$mform->addElement('advcheckbox', 'suspended', get_string('suspended', 'local_obu_application'), null, null, array(0, 1));
			$mform->addElement('static', 'applications', get_string('applications', 'local_obu_application'), $data->applications);
		}

		// Options
		$buttonarray = array();
		if ($data->delete) {
			$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('confirm_delete', 'local_obu_application'));
		} else {
			$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'local_obu_application'));
			if (($data->id != '0') && ($data->applications == 0)) {
				$buttonarray[] = &$mform->createElement('submit', 'deletebutton', get_string('delete', 'local_obu_application'));
			}
		}
		$buttonarray[] = &$mform->createElement('cancel');
		$mform->addGroup($buttonarray, 'buttonarray', '', array(' '), false);
		$mform->closeHeaderBefore('buttonarray');
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

		// Check that we have been given sufficient information
		if (isset($data['submitbutton']) && ($data['submitbutton'] == get_string('save', 'local_obu_application'))) {
			if ($data['name'] == '') {
				$errors['name'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['email'] == '') {
				$errors['email'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['code'] == '') {
				$errors['code'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['address'] == '') {
				$errors['address'] = get_string('value_required', 'local_obu_application');
			}
		}
		
		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}