<?php

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
 * OBU Application - Supplementary form input form
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class mdl_supplement_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->ref = $this->_customdata['ref'];
		$data->version = $this->_customdata['version'];
		$data->versions = $this->_customdata['versions'];
		$data->record = $this->_customdata['record'];
		
		$already_published = 0;
		if ($data->record != null) {
			$template['text'] = $data->record->template;
			$already_published = $data->record->published;
			$fields = [
				'template' => $template,
				'published' => $already_published
			];
			$this->set_data($fields);
		}
		
		$mform->addElement('html', '<h2>' . get_string('amend_supplement', 'local_obu_application') . '</h2>');

		if ($data->ref == '') {
			$mform->addElement('text', 'ref', get_string('suppref', 'local_obu_application'), 'size="10" maxlength="10"');
			$mform->setType('ref', PARAM_TEXT);
			$this->add_action_buttons(true, get_string('continue', 'local_obu_application'));
			return;
		}
		$mform->addElement('hidden', 'ref', $data->ref);
		$mform->setType('ref', PARAM_RAW);
		$mform->addElement('static', null, get_string('suppref', 'local_obu_application'), $data->ref);
		
		if ($data->version == '') {
			if (!$data->versions) {
				$mform->addElement('text', 'version', get_string('version', 'local_obu_application'), 'size="10" maxlength="10"');
				$mform->setType('version', PARAM_TEXT);
			} else {
				$select = $mform->addElement('select', 'versions', get_string('version', 'local_obu_application'), $data->versions, null);
				$select->setSelected(0);
			}
			$this->add_action_buttons(true, get_string('continue', 'local_obu_application'));
			return;
		}
		$mform->addElement('hidden', 'version', $data->version);
		$mform->setType('version', PARAM_RAW);
		$mform->addElement('static', null, get_string('version', 'local_obu_application'), $data->version);
		
		$mform->addElement('editor', 'template', get_string('supplement', 'local_obu_application'));
		$mform->setType('template', PARAM_RAW);
		$mform->disabledIf('template', 'published', 'checked');

		if ($already_published) {
			$mform->addElement('hidden', 'already_published', 1);
			$mform->setType('already_published', PARAM_RAW);
			$mform->addElement('hidden', 'published', 1);
			$mform->setType('published', PARAM_RAW);
			$mform->addElement('html', '<strong>' . get_string('published', 'local_obu_application') . '</strong>' . get_string('publish_note', 'local_obu_application'));
		} else {
			$mform->addElement('hidden', 'already_published', 0);
			$mform->setType('already_published', PARAM_RAW);
			$mform->addElement('advcheckbox', 'published', get_string('publish', 'local_obu_application'), get_string('publish_note', 'local_obu_application'), null, array(0, 1));
			$mform->disabledIf('published', 'published', 'checked');
		}

        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
    }
}