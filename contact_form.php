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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class contact_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->record = $this->_customdata['record'];
		
		if ($data->record !== false) {
			$fields = [
				'username' => $data->record->username,
				'idnumber' => $data->record->idnumber,
				'firstname' => $data->record->firstname,
				'lastname' => $data->record->lastname,
				'address' => $data->record->address,
				'city' => $data->record->city,
				'phone1' => $data->record->phone1,
				'email' => $data->record->email
			];
			
			$this->set_data($fields);
		}

		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

        $mform->addElement('header', 'contactdetails', get_string('contactdetails', 'local_obu_application'), '');
        include('./contact_fields.php');

        $mform->addElement('header', 'newemail', get_string('newemail', 'local_obu_application'), '');
        include('./email_fields.php');

        // buttons
        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

		include('./email_validate.php');

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}
		
        return $errors;
    }
}
