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
 * OBU Application - Amend details form [Moodle]
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

class mdl_amend_details_form extends moodleform {

    function definition() {
		
        $mform =& $this->_form;

        $data = new stdClass();
		$data->record = $this->_customdata['record'];
		$data->nations = $this->_customdata['nations'];
		$data->areas = $this->_customdata['areas'];

		if (($data->record->birth_code != '') && ($data->record->birth_code != 'ZZ')) {
			$data->birth_code = $data->record->birth_code;
		} else {
			$data->birth_code = $this->_customdata['default_birth_code'];
		}

		if (($data->record->nationality_code != '') && ($data->record->nationality_code != 'ZZ')) {
			$data->nationality_code = $data->record->nationality_code;
		} else {
			$data->nationality_code = $this->_customdata['default_nationality_code'];
		}

		if (($data->record->residence_code != '') && ($data->record->residence_code != 'ZZ')) {
			$data->residence_code = $data->record->residence_code;
		} else {
			$data->residence_code = $this->_customdata['default_residence_code'];
		}

		$fields = [
			'birth_code' => $data->birth_code,
			'birthdate' => $data->record->birthdate,
			'nationality_code' => $data->nationality_code,
			'gender' => $data->record->gender,
            'personal_email' => $data->record->personal_email,
			'residence_code' => $data->residence_code
		];
		$this->set_data($fields);
		
		$date_options = array('startyear' => 1950, 'stopyear'  => 2030, 'timezone'  => 99, 'optional' => false);
		
		$mform->addElement('hidden', 'id', $data->record->id);
		$mform->setType('id', PARAM_RAW);
		
		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

        // General - birth country, date, nationality, gender and residence
		$birth_code = $mform->addElement('select', 'birth_code', get_string('birth_country', 'local_obu_application'), $data->nations, null);
		$birth_code->setSelected($data->birth_code);
		$mform->addRule('birth_code', null, 'required', null, 'server');
		$mform->addElement('date_selector', 'birthdate', get_string('birthdate', 'local_obu_application'), $date_options);
		$mform->addRule('birthdate', null, 'required', null, 'server');
		$nationality_code = $mform->addElement('select', 'nationality_code', get_string('nationality', 'local_obu_application'), $data->nations, null);
		$nationality_code->setSelected($data->nationality_code);
		$mform->addRule('nationality_code', null, 'required', null, 'server');
		$genders = [];
		$genders['N'] = get_string('gender_not_available', 'local_obu_application');
		$genders['F'] = get_string('gender_female', 'local_obu_application');
		$genders['M'] = get_string('gender_male', 'local_obu_application');
		$mform->addElement('select', 'gender', get_string('gender', 'local_obu_application'), $genders);
		$mform->addRule('gender', null, 'required', null, 'server');
        $mform->addElement('text', 'personal_email', get_string('personalemail', 'local_obu_application'), 'size="40" maxlength="100"');
		$residence_code = $mform->addElement('select', 'residence_code', get_string('residence_area', 'local_obu_application'), $data->areas);
		$residence_code->setSelected($data->residence_code);
		$mform->addRule('residence_code', null, 'required', null, 'server');
		
		$this->add_action_buttons(true, get_string('save', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);
		
		if ($data['birth_code'] == '') {
			$errors['birth_code'] = get_string('value_required', 'local_obu_application');
		}
		
		if ((mktime() - $data['birthdate']) < 504921600) { // Must be at least 16 years old!
			$errors['birthdate'] = get_string('invalid_date', 'local_obu_application');
		}
		
		if ($data['nationality_code'] == '') {
			$errors['nationality_code'] = get_string('value_required', 'local_obu_application');
		}
		
		if ($data['residence_code'] == '') {
			$errors['residence_code'] = get_string('value_required', 'local_obu_application');
		}

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}
