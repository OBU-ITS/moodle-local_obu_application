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
 * OBU Application - User visa requirement form
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

class visa_form extends moodleform {

    function definition() {
		
        $mform =& $this->_form;

		$fields = [
			'visa_requirement' => $this->_customdata['visa_requirement']
		];
		$this->set_data($fields);

		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

		$visa_requirement = array();
		$visa_requirement[] = $mform->createElement('radio', 'visa_requirement', '', get_string('visa_not_required', 'local_obu_application'), 0);
		$visa_requirement[] = $mform->createElement('radio', 'visa_requirement', '', get_string('visa_tier4', 'local_obu_application'), 1);
		$visa_requirement[] = $mform->createElement('radio', 'visa_requirement', '', get_string('visa_tier2', 'local_obu_application'), 2);
		$mform->addGroup($visa_requirement, 'visa_requirement', '', '<br />', false);

		$this->add_action_buttons(true, get_string('save_continue', 'local_obu_application'));
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
