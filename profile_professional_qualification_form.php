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
 * @author     Emir Kamel
 * @copyright  2023, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class profile_professional_qualification_form extends moodleform {

    const NOQUAL_CODE = 'X0004';
    function definition() {
        global $CFG, $DB, $USER;
        require_once($CFG->libdir . '/filelib.php'); // Ensure file API is included

        $mform =& $this->_form;
        $data = new stdClass();
        $data->record = $this->_customdata['record'];

        $context = context_user::instance($USER->id);

        $draftitemid = file_get_submitted_draft_itemid('qualification_pdf'); // Fetch draft area ID

        if (!empty($data->record->qualification_pdf)) { // Check if a file exists
            file_prepare_draft_area(
                $draftitemid, // Assign draft area
                $context->id,
                'local_obu_application',
                'qualification_pdf',
                $data->record->id,
                ['subdirs' => false, 'maxbytes' => 5242880, 'maxfiles' => 1] // 5MB limit
            );
        }

        $fields = [
            'highest_prof_qualification' => $data->record->highest_prof_qualification ?? '',
            'qualification_certificate' => $draftitemid,
            'prof_level' => $data->record->prof_level,
            'prof_award' => $data->record->prof_award,
            'prof_date' => $data->record->prof_date,
            'credit' => $data->record->credit,
            'credit_name' => $data->record->credit_name,
            'credit_organisation' => $data->record->credit_organisation
        ];
        $this->set_data($fields);

        $qualification_records = $DB->get_records_sql("SELECT code, crm_dropdown_text FROM {local_obu_qualifications} ORDER BY priority ASC");
        $qualification_options = ['' => get_string('select', 'local_obu_application')];
        foreach ($qualification_records as $record) {
            $qualification_options[$record->code] = $record->crm_dropdown_text;
        }

        // This 'dummy' element has two purposes:
        // - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
        // - To let us inform the user that there are validation errors without them having to scroll down further
        $mform->addElement('static', 'form_errors');

        // Professional qualification
        $mform->addElement('html', '<p><strong>' . get_string('prof_qual_preamble', 'local_obu_application') . '</strong></p>');
        $mform->addElement('select', 'highest_prof_qualification', '', $qualification_options);
        $mform->setType('highest_prof_qualification', PARAM_TEXT);
        $mform->addRule('highest_prof_qualification', null, 'required', null, 'server');
        $mform->addElement('html', '<p><strong>' . get_string('qual_cert_preamble', 'local_obu_application') . '</strong></p>');
        $mform->addElement('filepicker', 'qualification_pdf', '', null, [
            'maxbytes' => 5242880, // 5MB
            'accepted_types' => ['.pdf','.png','.jpg','.jpeg']
        ]);
        $mform->hideIf('qualification_pdf', 'highest_prof_qualification', 'eq', self::NOQUAL_CODE);
        $mform->disabledIf('qualification_pdf', 'highest_prof_qualification', 'eq', self::NOQUAL_CODE);
        $mform->setDefault('qualification_pdf', $draftitemid);
        $mform->addElement('hidden', 'prof_level');
        $mform->setType('prof_level', PARAM_TEXT);
        $mform->addElement('hidden', 'prof_award');
        $mform->setType('prof_award', PARAM_TEXT);
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
        $mform->setType('trainingperiod', PARAM_TEXT);
        $mform->addRule('trainingperiod', null, 'required', null, 'server');

        $this->add_action_buttons(true, get_string('save', 'local_obu_application'));
        $mform->addElement('static', 'submitinfo', '', get_string('submit_info', 'local_obu_application'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if ($data['credit'] == '1') {
            if ($data['credit_name'] == '') {
                $errors['credit_name'] = get_string('value_required', 'local_obu_application');
            }
            if ($data['credit_organisation'] == '') {
                $errors['credit_organisation'] = get_string('value_required', 'local_obu_application');
            }
        }

        $selectedQualification = $data['highest_prof_qualification'];
        if ($selectedQualification !== self::NOQUAL_CODE) {
            $draftid = (int)($data['qualification_pdf'] ?? 0);

            $info = file_get_draft_area_info($draftid);
            if (empty($info['filecount'])) {
                $errors['qualification_pdf'] = get_string('value_required', 'local_obu_application');
            }
        }

        if (!empty($errors)) {
            $errors['form_errors'] = get_string('form_errors', 'local_obu_application');
        }

        return $errors;
    }
}
