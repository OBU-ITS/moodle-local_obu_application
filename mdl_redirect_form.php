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
 * OBU Application - Input form for user form redirection
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class mdl_redirect_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
        $data->application_id = $this->_customdata['application_id'];
		$data->application_status = $this->_customdata['application_status'];
        $data->approver_email = $this->_customdata['approver_email'];
        $data->approver_name = $this->_customdata['approver_name'];
		
		// Start with the required hidden field
		$mform->addElement('hidden', 'id', $data->application_id);

		$mform->addElement('html', $data->application_status);
		
		if (!$data->approver_email) {
			$mform->addElement('text', 'approver', get_string('email'), 'size="40" maxlength="100"');
			$this->add_action_buttons(true, get_string('continue', 'local_obu_application'));
		} else {
			$mform->addElement('hidden', 'approver', $data->approver_email);
			$mform->addElement('static', 'approver_email', get_string('email'), $data->approver_email);
			$mform->addElement('static', 'approver_name', null, $data->approver_name);
			$this->add_action_buttons(true, get_string('save', 'local_obu_application'));
		}
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		// Do our own validation and add errors to array
		foreach ($data as $key => $value) {
			if ($key == 'approver') {
				$approver = get_complete_user_data('email', $value);
				if ($approver === false) {
					$errors[$key] = get_string('user_not_found', 'local_obu_application');
				}
			}
		}
		
		return $errors;
	}
}