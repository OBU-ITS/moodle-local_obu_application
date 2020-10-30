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
 * OBU Application - Course maintenance form
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

class mdl_course_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->id = $this->_customdata['id'];
		$data->delete = $this->_customdata['delete'];
		$data->courses = $this->_customdata['courses'];
		$data->record = $this->_customdata['record'];
		$data->administrator = $this->_customdata['administrator'];
		
		if ($data->record != null) {
			$fields = [
				'code' => $data->record->code,
				'name' => $data->record->name,
				'supplement' => $data->record->supplement,
				'programme' => $data->record->programme,
				'suspended' => $data->record->suspended,
				'administrator' => $data->record->administrator,
				'module_subject' => $data->record->module_subject,
				'module_number' => $data->record->module_number,
				'campus' => $data->record->campus,
				'programme_code' => $data->record->programme_code,
				'major_code' => $data->record->major_code,
				'level' => $data->record->level,
				'cohort_code' => $data->record->cohort_code
			];
			$this->set_data($fields);
		}
		
		$mform->addElement('html', '<h2>' . get_string('update_course', 'local_obu_application') . '</h2>');

		// If we don't have a course yet, let them select one
		if ($data->id == '') {
			$select = $mform->addElement('select', 'id', get_string('course', 'local_obu_application'), $data->courses, null);
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
			$mform->addElement('static', 'code', get_string('code', 'local_obu_application'));
			$mform->addElement('static', 'name', get_string('name', 'local_obu_application'));
			$mform->addElement('static', 'supplement', get_string('supplement', 'local_obu_application'));
			if ($data->record->programme == '1') {
				$programme_formatted = '&#10004;'; // Tick
			} else {
				$programme_formatted = '&#10008;'; // Cross
			}
			$mform->addElement('static', 'programme_formatted', get_string('programme', 'local_obu_application'), $programme_formatted);
			if ($data->record->suspended == '1') {
				$suspended_formatted = '&#10004;'; // Tick
			} else {
				$suspended_formatted = '&#10008;'; // Cross
			}
			$mform->addElement('static', 'suspended_formatted', get_string('suspended', 'local_obu_application'), $suspended_formatted);
		} else {
			if (($data->id != '0') && !is_siteadmin()) {
				$mform->addElement('text', 'code', get_string('code', 'local_obu_application'), 'size="10" disabled="disabled"');
			} else {
				$mform->addElement('text', 'code', get_string('code', 'local_obu_application'), 'size="10" maxlength="10"');
			}
			$mform->setType('code', PARAM_TEXT);
			$mform->addElement('text', 'name', get_string('name', 'local_obu_application'), 'size="50" maxlength="100"');
			$mform->setType('name', PARAM_TEXT);
			$mform->addElement('text', 'supplement', get_string('supplement', 'local_obu_application'), 'size="2" maxlength="2"');
			$mform->setType('supplement', PARAM_TEXT);
			$mform->addElement('advcheckbox', 'programme', get_string('programme', 'local_obu_application'), null, null, array(0, 1));
			$mform->addElement('advcheckbox', 'suspended', get_string('suspended', 'local_obu_application'), null, null, array(0, 1));
			$mform->addElement('text', 'administrator', get_string('administrator', 'local_obu_application'), 'size="8" maxlength="8"');
			$mform->setType('administrator', PARAM_TEXT);
			if ($data->administrator != '') {
				$mform->addElement('static', 'administrator_name', null, $data->administrator);
			}
			$mform->addElement('text', 'module_subject', get_string('module_subject', 'local_obu_application'), 'size="4" maxlength="4"');
			$mform->setType('module_subject', PARAM_TEXT);
			$mform->addElement('text', 'module_number', get_string('module_number', 'local_obu_application'), 'size="4" maxlength="4"');
			$mform->setType('module_number', PARAM_TEXT);
			$mform->addElement('text', 'campus', get_string('campus', 'local_obu_application'), 'size="3" maxlength="3"');
			$mform->setType('campus', PARAM_TEXT);
			$mform->addElement('text', 'programme_code', get_string('programme_code', 'local_obu_application'), 'size="12" maxlength="12"');
			$mform->setType('programme_code', PARAM_TEXT);
			$mform->addElement('text', 'major_code', get_string('major_code', 'local_obu_application'), 'size="4" maxlength="4"');
			$mform->setType('major_code', PARAM_TEXT);
			$mform->addElement('text', 'level', get_string('level', 'local_obu_application'), 'size="2" maxlength="2"');
			$mform->setType('level', PARAM_TEXT);
			$mform->addElement('text', 'cohort_code', get_string('cohort_code', 'local_obu_application'), 'size="25" maxlength="25"');
			$mform->setType('cohort_code', PARAM_TEXT);
		}

		// Options
		$buttonarray = array();
		if ($data->delete) {
			$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('confirm_delete', 'local_obu_application'));
		} else {
			$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'local_obu_application'));
			if (($data->id != '0') && is_siteadmin()) {
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
			if ($data['code'] == '') {
				$errors['code'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['name'] == '') {
				$errors['name'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['administrator'] != '') {
				$user = read_user_by_username($data['administrator']);
				if ($user == null) {
					$errors['administrator'] = get_string('user_not_found', 'local_obu_application');
				}
			}
		}
		
		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}