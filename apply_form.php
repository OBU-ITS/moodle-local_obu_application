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
 * OBU Application - User 'apply' form
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

class apply_form extends moodleform {

    function definition() {
		
        $mform =& $this->_form;

        $data = new stdClass();
		$data->organisations = $this->_customdata['organisations'];
		$data->record = $this->_customdata['record'];

		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

        $mform->addElement('select', 'self_funding', get_string('self_funding', 'local_obu_application'), array("0"=>"No", "1"=>"Yes"));

		$mform->addElement('static', 'funding', '');
		$mform->closeHeaderBefore('funding');
		$mform->addElement('html', '<h1>' . get_string('funding_organisation', 'local_obu_application') . '</h1>');
		$options = [];
		$options['-1'] = get_string('select', 'local_obu_application');
		foreach ($data->organisations as $organisation_id => $organisation_name) {
			$options[$organisation_id] = $organisation_name;
		}
		$options['0'] = get_string('other', 'local_obu_application');
		$mform->addElement('select', 'funding_organisation', get_string('organisation', 'local_obu_application'), $options, null);
		$mform->disabledIf('funding_organisation', 'self_funding', 'neq', '0');
		$mform->addElement('static', 'funding_text', get_string('funding_text', 'local_obu_application'));
		$mform->addElement('text', 'funder_email', get_string('email'), 'size="40" maxlength="100"');
		$mform->setType('funder_email', PARAM_RAW_TRIMMED);
		$mform->disabledIf('funder_email', 'self_funding', 'neq', '0');
		$mform->disabledIf('funder_email', 'funding_organisation', 'neq', '0');
		$mform->addElement('text', 'funder_email2', get_string('confirm_email', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('funder_email2', PARAM_RAW_TRIMMED);
		$mform->disabledIf('funder_email2', 'self_funding', 'neq', '0');
		$mform->disabledIf('funder_email2', 'funding_organisation', 'neq', '0');
		
        $mform->addElement('header', 'declaration_head', get_string('declaration', 'local_obu_application'), '');
		
		$conditions = '<a href="https://www.brookes.ac.uk/about-brookes/structure-and-governance/policies-and-financial-statements/terms-and-conditions-of-enrolment#other" target="_blank">' . get_string('conditions', 'local_obu_application') . '</a>';
		$mform->addElement('checkbox', 'declaration', get_string('declaration', 'local_obu_application'), get_string('declaration_text', 'local_obu_application', $conditions));
		$mform->addRule('declaration', null, 'required', null, 'server');
		
        $this->add_action_buttons(true, get_string('apply', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

		// if not self-funding, the applicant must enter either the organisation or email of funder to approve
		if ($data['self_funding'] == '0') {
			if ($data['funding_organisation'] == -1) { // Not entered
				$errors['funding_organisation'] = get_string('value_required', 'local_obu_application');
			} else if ($data['funding_organisation'] == 0) { // 'Other Organisation'
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
			} else { // Known organisation (likely an NHS trust) so no email please
				if (!empty($data['funder_email'])) {
					$errors['funder_email'] = get_string('value_verboten', 'local_obu_application');
				}
				if (!empty($data['funder_email2'])) {
					$errors['funder_email2'] = get_string('value_verboten', 'local_obu_application');
				}
			}
		}

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}
