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
 * OBU Application - Delete applicant form
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

class mdl_delete_applicant_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->user = $this->_customdata['user'];
		$data->applicant = $this->_customdata['applicant'];
		
		if ($data->user !== false) {
			$fields = [
				'firstname' => $data->user->firstname,
				'lastname' => $data->user->lastname,
				'phone1' => $data->user->phone1,
				'phone2' => $data->user->phone2,
				'email' => $data->user->email
			];

			if ($data->applicant !== false) {
				$applicant_fields = [
					'title' => $data->applicant->title,
					'address_1' => $data->applicant->address_1,
					'address_2' => $data->applicant->address_2,
					'address_3' => $data->applicant->address_3,
					'city' => $data->applicant->city,
					'domicile_country' => $data->applicant->domicile_country,
					'postcode' => $data->applicant->postcode
				];
				$fields = array_merge($fields, $applicant_fields);
			}
			
			$this->set_data($fields);
		}
		
		$mform->addElement('html', '<h2>' . get_string('delete_applicant', 'local_obu_application') . '</h2>');

		$mform->addElement('hidden', 'userid', $data->user->id);
		$mform->setType('userid', PARAM_RAW);
		
		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');
		
		$mform->addElement('static', 'title', get_string('title', 'local_obu_application'));
		$mform->addElement('static', 'firstname', get_string('firstnames', 'local_obu_application'));
		$mform->addElement('static', 'lastname', get_string('lastname'));
		$mform->addElement('static', 'address_1', get_string('address_1', 'local_obu_application'));
		$mform->addElement('static', 'address_2', get_string('address_2', 'local_obu_application'));
		$mform->addElement('static', 'address_3', get_string('address_3', 'local_obu_application'));
		$mform->addElement('static', 'city', get_string('city', 'local_obu_application'));
		$mform->addElement('static', 'postcode', get_string('postcode', 'local_obu_application'));
		$mform->addElement('static', 'domicile_country', get_string('domicile_country', 'local_obu_application'));
		$mform->addElement('static', 'phone1', get_string('phone1'));
		$mform->addElement('static', 'phone2', get_string('phone2'));
		$mform->addElement('static', 'email', get_string('email'));

		// Options
		$buttonarray = array();
		$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('confirm_delete', 'local_obu_application'));
		$buttonarray[] = &$mform->createElement('cancel');
		$mform->addGroup($buttonarray, 'buttonarray', '', array(' '), false);
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