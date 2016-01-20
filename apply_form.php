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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class apply_form extends moodleform {

    function definition() {
		
        $mform =& $this->_form;

		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

        $mform->addElement('header', 'manager_to_approve', get_string('manager_to_approve', 'local_obu_application'), '');
		include('./email_fields.php');
		
        $mform->addElement('header', 'declaration_head', get_string('declaration', 'local_obu_application'), '');
		$mform->addElement('advcheckbox', 'self_funding', get_string('self_funding', 'local_obu_application'),
			get_string('self_funding_text', 'local_obu_application'), null, array(0, 1));
		$conditions = '<a href="http://www.brookes.ac.uk/studying-at-brookes/how-to-apply/conditions-of-acceptance/" target="_blank">' . get_string('conditions', 'local_obu_application') . '</a>';
		$mform->addElement('checkbox', 'declaration', get_string('declaration', 'local_obu_application'),
			get_string('declaration_text', 'local_obu_application', $conditions));
		$mform->addRule('declaration', null, 'required', null, 'server');
        $this->add_action_buttons(true, get_string('apply', 'local_obu_application'));
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
