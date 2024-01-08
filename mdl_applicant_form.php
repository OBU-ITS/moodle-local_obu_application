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
 * OBU Application - Input form for applicant selection
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2021, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class mdl_applicant_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->role = $this->_customdata['role'];
		$data->action = $this->_customdata['action'];
		
		$mform->addElement('hidden', 'role', $data->role);
		$mform->setType('role', PARAM_RAW);
		$mform->addElement('hidden', 'action', $data->action);
		$mform->setType('action', PARAM_RAW);

		$mform->addElement('text', 'name', get_string('name'), 'size="30" maxlength="100"');

        $this->add_action_buttons(true, get_string('continue', 'local_obu_application'));
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		if ($data['name'] == '') {
			$errors['name'] = get_string('value_required', 'local_obu_application');
		} else {
			$applicants = get_applicants_by_first_name($data['name']);
			if (count($applicants) == 0) {
                $applicants = get_applicants_by_last_name($data['name']);
                if (count($applicants) == 0) {
                    $errors['name'] = get_string('user_not_found', 'local_obu_application');
                }
			}
		}
		
		return $errors;
	}
}