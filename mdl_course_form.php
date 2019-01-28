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
 * @copyright  2016, Oxford Brookes University
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
		
		if ($data->record != null) {
			$fields = [
				'code' => $data->record->code,
				'name' => $data->record->name,
				'supplement' => $data->record->supplement
			];
			$this->set_data($fields);
		}
		
		$mform->addElement('html', '<h2>' . get_string('update_course', 'local_obu_application') . '</h2>');

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
		} else {
			$mform->addElement('text', 'code', get_string('code', 'local_obu_application'), 'size="10" maxlength="10"');
			$mform->setType('code', PARAM_TEXT);
			$mform->addElement('text', 'name', get_string('name', 'local_obu_application'), 'size="50" maxlength="100"');
			$mform->setType('name', PARAM_TEXT);
			$mform->addElement('text', 'supplement', get_string('supplement', 'local_obu_application'), 'size="2" maxlength="2"');
			$mform->setType('supplement', PARAM_TEXT);
		}

		// Options
		$buttonarray = array();
		if ($data->delete) {
			$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('confirm_delete', 'local_obu_application'));
		} else {
			$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'local_obu_application'));
			if ($data->id != '0') {
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
		}
		
		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}