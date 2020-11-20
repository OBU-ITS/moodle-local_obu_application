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
 * OBU Application - User profile form
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

class profile_form extends moodleform {

    function definition() {
		global $CFG;
		
        $mform =& $this->_form;

        $data = new stdClass();
		$data->record = $this->_customdata['record'];
		$data->nations = $this->_customdata['nations'];
		$data->areas = $this->_customdata['areas'];

		if ($data->record->birth_code != '') {
			$data->birth_code = $data->record->birth_code;
		} else {
			$data->birth_code = $this->_customdata['default_birth_code'];
		}

		if ($data->record->nationality_code != '') {
			$data->nationality_code = $data->record->nationality_code;
		} else {
			$data->nationality_code = $this->_customdata['default_nationality_code'];
		}

		if ($data->record->residence_code != '') {
			$data->residence_code = $data->record->residence_code;
		} else {
			$data->residence_code = $this->_customdata['default_residence_code'];
		}
		
		$fields = [
			'birth_code' => $data->birth_code,
			'birthdate' => $data->record->birthdate,
			'nationality_code' => $data->nationality_code,
			'gender' => $data->record->gender,
			'residence_code' => $data->residence_code,
			'p16school' => $data->record->p16school,
			'p16schoolperiod' => $data->record->p16schoolperiod,
			'p16fe' => $data->record->p16fe,
			'p16feperiod' => $data->record->p16feperiod,
			'training' => $data->record->training,
			'trainingperiod' => $data->record->trainingperiod,
			'prof_level' => $data->record->prof_level,
			'prof_award' => $data->record->prof_award,
			'prof_date' => $data->record->prof_date,
			'credit' => $data->record->credit,
			'credit_name' => $data->record->credit_name,
			'credit_organisation' => $data->record->credit_organisation,
			'emp_place' => $data->record->emp_place,
			'emp_area' => $data->record->emp_area,
			'emp_title' => $data->record->emp_title,
			'emp_prof' => $data->record->emp_prof,
			'prof_reg_no' => $data->record->prof_reg_no,
			'criminal_record' => $data->record->criminal_record
		];
		$this->set_data($fields);
		
		$date_options = array('startyear' => 1950, 'stopyear'  => 2030, 'timezone'  => 99, 'optional' => false);
		
		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

        // General - birth country, date, nationality, gender and residence
        $mform->addElement('header', 'general_head', get_string('general_head', 'local_obu_application'), '');
		$mform->setExpanded('general_head');
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
		$mform->addElement('html', '<p><strong>' . get_string('residence_preamble', 'local_obu_application') . '</strong></p>');
		$residence_code = $mform->addElement('select', 'residence_code', get_string('residence_area', 'local_obu_application'), $data->areas);
		$residence_code->setSelected($data->residence_code);
		$mform->addRule('residence_code', null, 'required', null, 'server');

        // Education
		$mform->addElement('header', 'education_head', get_string('education_head', 'local_obu_application'), '');
		$mform->setExpanded('education_head');
		$mform->addElement('text', 'p16school', get_string('p16school', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('p16school', PARAM_TEXT);
		$mform->addElement('text', 'p16schoolperiod', get_string('period', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('p16schoolperiod', PARAM_TEXT);
		$mform->addElement('text', 'p16fe', get_string('p16fe', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('p16fe', PARAM_TEXT);
		$mform->addElement('text', 'p16feperiod', get_string('period', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('p16feperiod', PARAM_TEXT);
		$mform->addElement('text', 'training', get_string('training', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('training', PARAM_TEXT);
		$mform->addRule('training', null, 'required', null, 'server');
		$mform->addElement('text', 'trainingperiod', get_string('period', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('trainingperiod', PARAM_TEXT);
		$mform->addRule('trainingperiod', null, 'required', null, 'server');

        // Professional qualification
		$mform->addElement('header', 'prof_qual_head', get_string('prof_qual_head', 'local_obu_application'), '');
		$mform->setExpanded('prof_qual_head');
		$mform->addElement('html', '<p><strong>' . get_string('prof_level_preamble', 'local_obu_application') . '</strong></p>');
		$mform->addElement('text', 'prof_level', get_string('prof_level', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('prof_level', PARAM_TEXT);
		$mform->addRule('prof_level', null, 'required', null, 'server');
		$mform->addElement('html', '<p><strong>' . get_string('prof_award_preamble', 'local_obu_application') . '</strong></p>');
		$mform->addElement('text', 'prof_award', get_string('prof_award', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('prof_award', PARAM_TEXT);
		$mform->addRule('prof_award', null, 'required', null, 'server');
		$mform->addElement('date_selector', 'prof_date', get_string('prof_date', 'local_obu_application'));
		$mform->addRule('prof_date', null, 'required', null, 'server');
		$mform->addElement('html', '<p \><strong>' . get_string('credit_preamble', 'local_obu_application') . '</strong>');
		$mform->addElement('advcheckbox', 'credit', get_string('credit', 'local_obu_application'), get_string('credit_text', 'local_obu_application'), null, array(0, 1));
		$mform->addElement('html', '<p><strong>' . get_string('credit_name_preamble', 'local_obu_application') . '</strong></p>');
		$mform->addElement('text', 'credit_name', get_string('credit_name', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('credit_name', PARAM_TEXT);
		$mform->disabledIf('credit_name', 'credit', 'eq', '0');
		$mform->addElement('html', '<p><strong>' . get_string('credit_organisation_preamble', 'local_obu_application') . '</strong></p>');
		$mform->addElement('text', 'credit_organisation', get_string('credit_organisation', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('credit_organisation', PARAM_TEXT);
		$mform->disabledIf('credit_organisation', 'credit', 'eq', '0');

        // Employment
		$mform->addElement('header', 'employment_head', get_string('employment_head', 'local_obu_application'), '');
		$mform->setExpanded('employment_head');
		$mform->addElement('text', 'emp_place', get_string('emp_place', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('emp_place', PARAM_TEXT);
		$mform->addRule('emp_place', null, 'required', null, 'server');
		$mform->addElement('text', 'emp_area', get_string('emp_area', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('emp_area', PARAM_TEXT);
		$mform->addElement('text', 'emp_title', get_string('emp_title', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('emp_title', PARAM_TEXT);
		$mform->addElement('text', 'emp_prof', get_string('emp_prof', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('emp_prof', PARAM_TEXT);

        // Professional registration
		$mform->addElement('header', 'prof_reg_head', get_string('prof_reg_head', 'local_obu_application'), '');
		$mform->setExpanded('prof_reg_head');
		$mform->addElement('text', 'prof_reg_no', get_string('prof_reg_no', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('prof_reg_no', PARAM_TEXT);
		
        // Criminal record
		$mform->addElement('header', 'criminal_record_head', get_string('criminal_record_head', 'local_obu_application'), '');
		$mform->setExpanded('criminal_record_head');
		$options = [];
		if ($data->record->criminal_record == 0) { // A mandatory field so must be the first time thru
			$options['0'] = ''; // No choice made yet
		}
		$options['1'] = get_string('yes', 'local_obu_application');
		$options['2'] = get_string('no', 'local_obu_application');
		$mform->addElement('select', 'criminal_record', get_string('criminal_record', 'local_obu_application'), $options);
		$mform->addRule('criminal_record', null, 'required', null, 'server');

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

		if ($data['credit'] == '1') {
			if ($data['credit_name'] == '') {
				$errors['credit_name'] = get_string('value_required', 'local_obu_application');
			}
			if ($data['credit_organisation'] == '') {
				$errors['credit_organisation'] = get_string('value_required', 'local_obu_application');
			}
		}

		if ($data['criminal_record'] == '0') {
			$errors['criminal_record'] = get_string('value_required', 'local_obu_application');
		}

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}
