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
 * OBU Application - Input form for user applications listing
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class mdl_list_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
		
		$mform->addElement('text', 'lastname', get_string('lastname'), 'size="30" maxlength="100"');

        $this->add_action_buttons(true, get_string('continue', 'local_obu_forms'));
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		if ($data['lastname'] == '') {
			$errors['lastname'] = get_string('value_required', 'local_obu_application');
		} else {
			$applicants = get_applicants_by_name($data['lastname']);
			if (count($applicants) == 0) {
				$errors['lastname'] = get_string('user_not_found', 'local_obu_application');
			}
		}
		
		return $errors;
	}
}