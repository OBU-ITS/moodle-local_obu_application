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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class profile_form extends moodleform {

    function definition() {
        global $USER;
		
        $mform =& $this->_form;

        $data = new stdClass();
		$data->record = $this->_customdata['record'];
		
		if ($data->record !== false) {
			$fields = [
				'birthdate' => $data->record->birthdate,
				'birthcountry' => $data->record->birthcountry,
				'firstentrydate' => $data->record->firstentrydate,
				'lastentrydate' => $data->record->lastentrydate,
				'residencedate' => $data->record->residencedate,
				'support' => $data->record->support,
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
		}
		
		$mform->addElement('html', '<h2>' . fullname($USER, true) . ' - ' .get_string('profile', 'local_obu_application') . '</h2>');

		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

        $mform->addElement('header', 'birth_head', get_string('birth_head', 'local_obu_application'), '');
		$mform->setExpanded('birth_head');
		$mform->addElement('date_selector', 'birthdate', get_string('birthdate', 'local_obu_application'));
		$mform->addRule('birthdate', null, 'required', null, 'server');
        $country = get_string_manager()->get_list_of_countries();
        $default_country[''] = get_string('selectacountry');
        $country = array_merge($default_country, $country);
        $mform->addElement('select', 'birthcountry', get_string('birthcountry', 'local_obu_application'), $country);
        if( !empty($CFG->country) ){
            $mform->setDefault('country', $CFG->country);
        }else{
            $mform->setDefault('country', '');
        }
		$mform->addRule('birthcountry', null, 'required', null, 'server');
        $mform->addElement('header', 'non_eu_head', get_string('non_eu_head', 'local_obu_application'), '');
		$mform->setExpanded('non_eu_head');
		$mform->addElement('text', 'firstentrydate', get_string('firstentrydate', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addElement('text', 'lastentrydate', get_string('lastentrydate', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addElement('text', 'residencedate', get_string('residencedate', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->addElement('header', 'needs_head', get_string('needs_head', 'local_obu_application'), '');
		$mform->setExpanded('needs_head');
		$mform->addElement('text', 'support', get_string('support', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->addElement('header', 'education_head', get_string('education_head', 'local_obu_application'), '');
		$mform->setExpanded('education_head');
		$mform->addElement('text', 'p16school', get_string('p16school', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addElement('text', 'p16schoolperiod', get_string('period', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addElement('text', 'p16fe', get_string('p16fe', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addElement('text', 'p16feperiod', get_string('period', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addElement('text', 'training', get_string('training', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addRule('training', null, 'required', null, 'server');
		$mform->addElement('text', 'trainingperiod', get_string('period', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addRule('trainingperiod', null, 'required', null, 'server');
        $mform->addElement('header', 'prof_qual_head', get_string('prof_qual_head', 'local_obu_application'), '');
		$mform->setExpanded('prof_qual_head');
		$mform->addElement('text', 'prof_level', get_string('prof_level', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addRule('prof_level', null, 'required', null, 'server');
		$mform->addElement('text', 'prof_award', get_string('prof_award', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addRule('prof_award', null, 'required', null, 'server');
		$mform->addElement('date_selector', 'prof_date', get_string('prof_date', 'local_obu_application'));
		$mform->addRule('prof_date', null, 'required', null, 'server');
        $mform->addElement('header', 'employment_head', get_string('employment_head', 'local_obu_application'), '');
		$mform->setExpanded('employment_head');
		$mform->addElement('text', 'emp_place', get_string('emp_place', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addElement('text', 'emp_area', get_string('emp_area', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addElement('text', 'emp_title', get_string('emp_title', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addElement('text', 'emp_prof', get_string('emp_prof', 'local_obu_application'), 'size="40" maxlength="100"');
        $mform->addElement('header', 'prof_reg_head', get_string('prof_reg_head', 'local_obu_application'), '');
		$mform->setExpanded('prof_reg_head');
		$mform->addElement('text', 'prof_reg_no', get_string('prof_reg_no', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->addRule('prof_reg_no', null, 'required', null, 'server');
        $mform->addElement('header', 'criminal_record_head', get_string('criminal_record_head', 'local_obu_application'), '');
		$mform->setExpanded('criminal_record_head');
		$mform->addElement('selectyesno', 'criminal_record', get_string('criminal_record', 'local_obu_application'));
		$mform->addElement('html', '</td></tr></tbody></table>');
        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);
/*
        if (empty($data['email'])) {
            $errors['email'] = get_string('missingemail');
        } else if ($data['email'] != $data['username']) {
            $errors['email'] = get_string('invalidemail');
        }
*/
		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}
