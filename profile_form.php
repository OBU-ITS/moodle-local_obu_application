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
 * @copyright  2016, Oxford Brookes University
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
		$data->nationalities = $this->_customdata['nationalities'];
		
		$fields = [
			'birthdate' => $data->record->birthdate,
			'nationality_code' => $data->record->nationality_code,
			'p16school' => $data->record->p16school,
			'p16schoolperiod' => $data->record->p16schoolperiod,
			'p16fe' => $data->record->p16fe,
			'p16feperiod' => $data->record->p16feperiod,
			'training' => $data->record->training,
			'trainingperiod' => $data->record->trainingperiod,
			'prof_level' => $data->record->prof_level,
			'prof_award' => $data->record->prof_award,
			'prof_date' => $data->record->prof_date,
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

        // Birth date/nationality
        $mform->addElement('header', 'birth_head', get_string('birth_head', 'local_obu_application'), '');
		$mform->setExpanded('birth_head');
		$mform->addElement('date_selector', 'birthdate', get_string('birthdate', 'local_obu_application'), $date_options);
		$mform->addRule('birthdate', null, 'required', null, 'server');
		$options = [];
		if ($data->record->nationality_code == 0) {
			$options['0'] = get_string('select', 'local_obu_application');
		}
		foreach ($data->nationalities as $nationality_code => $nationality_name) {
			$options[$nationality_code] = $nationality_name;
		}
		$mform->addElement('select', 'nationality_code', get_string('nationality', 'local_obu_application'), $options, null);
		$mform->addRule('nationality_code', null, 'required', null, 'server');

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
		$mform->addElement('text', 'prof_level', get_string('prof_level', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('prof_level', PARAM_TEXT);
		$mform->addRule('prof_level', null, 'required', null, 'server');
		$mform->addElement('text', 'prof_award', get_string('prof_award', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('prof_award', PARAM_TEXT);
		$mform->addRule('prof_award', null, 'required', null, 'server');
		$mform->addElement('date_selector', 'prof_date', get_string('prof_date', 'local_obu_application'));
		$mform->addRule('prof_date', null, 'required', null, 'server');

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
		
		if ($data['nationality_code'] == '0') {
			$errors['nationality_code'] = get_string('value_required', 'local_obu_application');
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
