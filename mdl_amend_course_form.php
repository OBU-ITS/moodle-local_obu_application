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
 * @copyright  2020, Oxford Brookes University
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
		$data->dates = $this->_customdata['dates'];
		$data->application = $this->_customdata['application'];
		
		$fields = [
			'current_course_name' => $data->application->course_code . ' ' . $data->application->course_name,
			'current_course_date' => $data->application->course_date,
			'course_code' => $data->application->course_code,
			'course_date' => $data->application->course_date,
			'studying' => $data->application->studying,
			'current_student_number' => $data->application->student_number,
            'previous_student_number' => $data->application->student_number
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
		$mform->addElement('select', 'course_date', get_string('course_date', 'local_obu_application'), $data->dates, null);

		// Already studying
        $options = [];
        $options['2'] = get_string('no', 'local_obu_application');
        $options['3'] = get_string('pgcert', 'local_obu_application');
        $options['4'] = get_string('pgdip', 'local_obu_application');
        $options['5'] = get_string('bsc', 'local_obu_application');
        $options['6'] = get_string('msc', 'local_obu_application');
        $options['7'] = get_string('standalone_module', 'local_obu_application');
        $mform->addElement('select', 'studying', get_string('currently_enrolled', 'local_obu_application'), $options);
        $mform->addRule('studying', null, 'required', null, 'server');
        $mform->addElement('text', 'current_student_number', get_string('current_student_number', 'local_obu_application'), 'size="10" maxlength="10"');
        $mform->setType('current_student_number', PARAM_TEXT);
        $mform->hideif('current_student_number', 'studying', 'eq', '2');
        $mform->addElement('text', 'previous_student_number', get_string('previous_student_number', 'local_obu_application'), 'size="10" maxlength="10"');
        $mform->setType('previous_student_number', PARAM_TEXT);
        $mform->hideif('previous_student_number', 'studying', 'neq', '2');
		
		$this->add_action_buttons(true, get_string('save', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;

        $errors = parent::validation($data, $files);

        if ($data['studying'] != '2') {
            if ($data['current_student_number'] == '') {
                $errors['current_student_number'] = get_string('value_required', 'local_obu_application');
            } else if (read_user_by_username($data['current_student_number']) == null) {
                $errors['current_student_number'] = get_string('user_not_found', 'local_obu_application');
            }
        } else if ($data['studying'] == '2') {
            if ($data['previous_student_number'] != '' && read_user_by_username($data['previous_student_number']) == null) {
                $errors['previous_student_number'] = get_string('user_not_found', 'local_obu_application');
            }
        }

		if (!empty($errors)) {
			$errors['form_errors'] = get_string('form_errors', 'local_obu_application');
		}

        return $errors;
    }
}
