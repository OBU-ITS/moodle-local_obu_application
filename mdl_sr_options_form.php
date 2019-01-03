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
 * OBU Application - Status Report options form
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2018, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class mdl_sr_options_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->selected_courses = $this->_customdata['selected_courses'];
		$data->application_date = $this->_customdata['application_date'];
		$data->sort_order = $this->_customdata['sort_order'];
		$data->courses = $this->_customdata['courses'];
		$data->sort_orders = $this->_customdata['sort_orders'];

		$this->set_data(array('application_date' => $data->application_date));

		$mform->addElement('html', '<h2>' . get_string('sr_options', 'local_obu_application') . '</h2>');

		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_errors');
		
		// Courses
		$select = $mform->addElement('select', 'selected_courses', get_string('courses', 'local_obu_application'), $data->courses, null);
		$select->setMultiple(true);
		$select->setSelected($data->selected_courses);

		// Date
		$mform->addElement('date_selector', 'application_date', get_string('application_date', 'local_obu_application'));

		// Sort order
		$select = $mform->addElement('select', 'sort_order', get_string('sort_order', 'local_obu_application'), $data->sort_orders, null);
		$select->setSelected($data->sort_order);

		// Options
		$buttonarray = array();
		$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'local_obu_application'));
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