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
 * OBU Application - Amend course form [Moodle]
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

class mdl_amend_course_form extends moodleform {

    function definition() {
		
        $mform =& $this->_form;

        $data = new stdClass();
		$data->courses = $this->_customdata['courses'];
		$data->application = $this->_customdata['application'];
		
		$fields = [
			'current_course_name' => $data->application->course_code . ' ' . $data->application->course_name,
			'current_course_date' => $data->application->course_date,
			'course_date' => $data->application->course_date
		];
		$this->set_data($fields);
		
		$mform->addElement('hidden', 'id', $data->application->id);
		$mform->setType('id', PARAM_RAW);
		
		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');

        // Current course
		$mform->addElement('header', 'current_course_head', get_string('course', 'local_obu_application'), '');
		$mform->setExpanded('current_course_head');
		$mform->addElement('static', 'current_course_name', get_string('name', 'local_obu_application'));
		$mform->addElement('static', 'current_course_date', get_string('course_date', 'local_obu_application'));

        // Amended course
		$mform->addElement('header', 'course_head', get_string('course_head', 'local_obu_application'), '');
		$mform->setExpanded('course_head');
		$mform->addElement('select', 'course_code', get_string('course', 'local_obu_application'), $data->courses, null);
		$mform->addElement('text', 'course_date', get_string('course_date', 'local_obu_application'), 'size="40" maxlength="100"');
		$mform->setType('course_date', PARAM_TEXT);
		$mform->addRule('course_date', null, 'required', null, 'server');
		
		$this->add_action_buttons(true, get_string('save', 'local_obu_application'));
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
