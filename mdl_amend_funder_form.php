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
 * OBU Application - Amend funder form [Moodle]
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

class mdl_amend_funder_form extends moodleform {

    function definition() {
		
        $mform =& $this->_form;

        $data = new stdClass();
		$data->record = $this->_customdata['record'];
		$data->organisations = $this->_customdata['organisations'];
		
		if ($data->record->funding_id == 0) { // 'Other Organisation'
			$funder_email = $data->record->funder_email;
		} else { // A known organisation with a fixed email address
			$funder_email = '';
		}

		$fields = [
			'funding_organisation' => $data->record->funding_id,
			'funder_email' => $funder_email,
			'funder_email2' => $funder_email
		];
		$this->set_data($fields);
		
		$options = [];
		foreach ($data->organisations as $organisation_id => $organisation_name) {
			$options[$organisation_id] = $organisation_name;
		}
		$options['0'] = get_string('other', 'local_obu_application');
		
		$mform->addElement('hidden', 'id', $data->record->id);
		$mform->setType('id', PARAM_RAW);
		
		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

		$mform->addElement('static', 'funder', '');
		$mform->addElement('html', '<h1>' . get_string('funding_organisation', 'local_obu_application') . '</h1>');
		$mform->addElement('select', 'funding_organisation', get_string('organisation', 'local_obu_application'), $options, null);
		$mform->addElement('static', 'funding_text', get_string('funding_text', 'local_obu_application'));
		$mform->addElement('text', 'funder_email', get_string('email'), 'size="40" maxlength="100"');
		$mform->setType('funder_email', PARAM_RAW_TRIMMED);
		$mform->disabledIf('funder_email', 'funding_organisation', 'neq', '0');
		$mform->addElement('text', 'funder_email2', get_string('confirm_email', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('funder_email2', PARAM_RAW_TRIMMED);
		$mform->disabledIf('funder_email2', 'funding_organisation', 'neq', '0');
		
		$this->add_action_buttons(true, get_string('save', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

		if ($data['funding_organisation'] == 0) { // 'Other Organisation'
			if (empty($data['funder_email'])) {
				$errors['funder_email'] = get_string('missingemail');
			} else if (!validate_email($data['funder_email'])) {
				$errors['funder_email'] = get_string('invalidemail');
			}
			if (empty($data['funder_email2'])) {
				$errors['funder_email2'] = get_string('missingemail');
			} else if ($data['funder_email2'] != $data['funder_email']) {
				$errors['funder_email2'] = get_string('invalidemail');
			}
		}
		
		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}
