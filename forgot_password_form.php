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
 * OBU Application - Forgot password form
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham (derived from '/login/forgot_password_form.php')
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class login_forgot_password_form extends moodleform {

    /**
     * Define the forgot password form
     */
    function definition() {
        $mform = $this->_form;

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        $mform->setDisableShortforms(true);

        //$mform->addElement('header', 'searchbyemail', get_string('searchbyemail'), '');

        $mform->addElement('text', 'email', get_string('email'), 'size="30" maxlength="100"');
        $mform->setType('email', PARAM_RAW);

        $this->add_action_buttons(true, get_string('search'));
    }

    /**
     * Validate user input from the forgot password form.
     * @param array $data array of submitted form fields.
     * @param array $files submitted with the form.
     * @return array errors occuring during validation.
     */
    function validation($data, $files) {
        global $CFG, $DB;

        $errors = parent::validation($data, $files);

		if (empty($data['email']) || !validate_email($data['email'])) {
			$errors['email'] = get_string('invalidemail');
		} else if ($DB->count_records('user', array('email' => strtolower($data['email']))) > 1) {
			$errors['email'] = get_string('forgottenduplicate');
		} else {
			if ($user = get_complete_user_data('email', strtolower($data['email']))) {
				if (empty($user->confirmed)) {
					$errors['email'] = get_string('confirmednot');
				}
			}
			if (!$user and empty($CFG->protectusernames)) {
				$errors['email'] = get_string('emailnotfound');
            }
        }

        return $errors;
    }
}
