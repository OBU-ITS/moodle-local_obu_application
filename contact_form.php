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
 * OBU Application - Contact details form
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

class contact_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->user = $this->_customdata['user'];
		
		if ($data->user !== false) {
			$fields = [
				'username' => $data->user->username,
				'profile_field_title' => $data->user->profile_field_title,
				'firstname' => $data->user->firstname,
				'lastname' => $data->user->lastname,
				'address' => $data->user->address,
				'city' => $data->user->city,
				'phone1' => $data->user->phone1,
				'email' => $data->user->email
			];
			
			$this->set_data($fields);
		}

		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

        $mform->addElement('header', 'contactdetails', get_string('contactdetails', 'local_obu_application'), '');
		
		$mform->addElement('text', 'profile_field_title', get_string('title', 'local_obu_application'), 'size="30" maxlength="100"');
		if ($data->user->email != $data->user->username) {
			$mform->disabledIf('profile_field_title', 'email', 'neq', $data->user->username);
		} else {
			$mform->setType('profile_field_title', PARAM_TEXT);
			$mform->addRule('profile_field_title', null, 'required', null, 'server');
		}
		
		$mform->addElement('text', 'firstname', get_string('firstname'), 'size="30" maxlength="100"');
		if ($data->user->email != $data->user->username) {
			$mform->disabledIf('firstname', 'email', 'neq', $data->user->username);
		} else {
			$mform->setType('firstname', PARAM_TEXT);
			$mform->addRule('firstname', null, 'required', null, 'server');
		}
		
		$mform->addElement('text', 'lastname', get_string('lastname'), 'size="30" maxlength="100"');
		if ($data->user->email != $data->user->username) {
			$mform->disabledIf('lastname', 'email', 'neq', $data->user->username);
		} else {
			$mform->setType('lastname', PARAM_TEXT);
			$mform->addRule('lastname', null, 'required', null, 'server');
		}
		
		$mform->addElement('textarea', 'address', get_string('address'), 'cols="40" rows="5"');
		$mform->setType('address', PARAM_TEXT);
		$mform->addRule('address', null, 'required', null, 'server');

		$mform->addElement('text', 'city', get_string('postcode', 'local_obu_application'), 'size="15" maxlength="100"');
		$mform->setType('city', PARAM_TEXT);
		$mform->addRule('city', null, 'required', null, 'server');

		$mform->addElement('text', 'phone1', get_string('phone', 'local_obu_application'), 'size="30" maxlength="100"');
		$mform->setType('phone1', PARAM_TEXT);
		$mform->addRule('phone1', null, 'required', null, 'server');

		if ($data->user->email != $data->user->username) {
			$mform->addElement('text', 'email', get_string('email'), 'size="25" maxlength="100"');
			$mform->disabledIf('email', 'firstname', 'neq', '?****?');
		} else {
			$mform->addElement('header', 'newemail', get_string('newemail', 'local_obu_application'), '');
			$mform->addElement('text', 'email', get_string('email'), 'size="25" maxlength="100"');
			$mform->setType('email', PARAM_RAW_TRIMMED);
			$mform->addRule('email', get_string('missingemail'), 'required', null, 'server');

			$mform->addElement('text', 'username', get_string('emailagain'), 'size="25" maxlength="100"');
			$mform->setType('username', PARAM_RAW_TRIMMED);
			$mform->addRule('username', get_string('missingemail'), 'required', null, 'server');
		}

        // buttons
        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);
		
		if ($data['email'] == $data['username']) {
			if (!validate_email($data['email']) || ($data['email'] != strtolower($data['email']))) {
				$errors['email'] = get_string('invalidemail');
			}
			if (empty($data['username'])) {
				$errors['username'] = get_string('missingemail');
			} else if ($data['username'] != $data['email']) {
				$errors['username'] = get_string('invalidemail');
			}
		}

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}
		
        return $errors;
    }
}
