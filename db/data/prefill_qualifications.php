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
 * OBU Application - Prefill highest qualification data functionalities
 *
 * @package    obu_application
 * @category   local
 * @author     Emir Kamel
 * @copyright  2025, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

function get_prefill_qualifications_data(): array {
    $qualifications = [
        ['D0000', 'UK doctorate degree', 1, 'PG', 1, 'UK PHD', ''],
        ['D0001', 'Non-UK doctorate degree', 1, 'PG', 1, 'Non-UK PHD', ''],
        ['D0002', 'Other qualification at level D', 1, 'PG', 1, 'Other research qualification', ''],
        ['M0000', 'UK masters degree', 2, 'PG', 1, 'UK Masters', ''],
        ['M0001', 'Non-UK masters degree', 2, 'PG', 1, 'Non-UK Masters', ''],
        ['M0016', 'Postgraduate Certificate in Education or Professional Graduate Diploma in Education', 2, 'PG', 1, 'PGCE/PGDipEd', ''],
        ['M0021', 'Other taught qualification at level M', 2, 'PG', 1, 'Other qualification at Masters level', ''],
        ['H0000', 'UK first degree with honours', 3, 'PG', 1, 'UK honours degree', ''],
        ['H0001', 'Non-UK first degree', 3, 'PG', 1, 'Non-UK degree', ''],
        ['H0002', 'First degree with honours leading to Qualified Teacher Status (QTS)/registration with a General Teaching Council (GTC)', 3, 'PG', 1, 'UK undergraduate teacher training degree', ''],
        ['H0016', 'Other qualification at level H', 3, 'PG', 0, 'Other degree level qualification (e.g. Affiliate)', ''],
        ['M0002', 'Integrated undergraduate/postgraduate taught masters degree on the enhanced/extended pattern', 3, 'PG', 1, 'Integrated Masters (e.g. MEng, MChem)', ''],
        ['J0002', 'Diploma of Higher Education (DipHE)', 4, 'UG', 0, 'DipHE', ''],
        ['J0003', 'Higher National Diploma (HND)', 4, 'UG', 0, 'HND', ''],
        ['C0000', 'Certificate of Higher Education (CertHE)', 5, 'UG', 0, 'CertHE', ''],
        ['C0001', 'Higher National Certificate (HNC)', 5, 'UG', 0, 'HNC', ''],
        ['C0008', 'Credits at level C', 5, 'UG', 1, 'University level credits', ''],
        ['J0000', 'Foundation degree', 5, 'UG', 0, 'Foundation Degree', ''],
        ['P0000', 'Diploma at level 3', 6, 'UG', 0, 'Level 3 Diploma (e.g. BTEC Diploma)', ''],
        ['P0001', 'Certificate at level 3', 6, 'UG', 0, 'Level 3 Certificate (e.g. BTEC Cert)', ''],
        ['P0004', 'A/AS level', 6, 'UG', 0, 'A/AS level', ''],
        ['P0008', 'International Baccalaureate (IB) Diploma', 6, 'UG', 0, 'International Baccalaureate', ''],
        ['P0013', 'Other qualification at level 3', 6, 'UG', 0, 'Other A-level equivalent qualification (e.g. foundation course or non-UK qualification)', ''],
        ['P0014', 'Level 3 qualifications of which none are subject to UCAS Tariff', 6, 'UG', 0, 'UK Advanced level equivalent quals (not in UCAS tariff)', 'e.g. vocational quals'],
        ['P0015', 'Level 3 qualifications of which all are subject to UCAS Tariff', 6, 'UG', 0, 'Mixed A-level equiv. quals all tariffable', ''],
        ['P0016', 'Level 3 qualifications of which some are subject to UCAS Tariff', 6, 'UG', 1, 'Mixed A-level equiv. quals, some are in UCAS Tariff', ''],
        ['X0000', 'Higher education (HE) access course, Quality Assurance Agency (QAA) recognised', 6, 'UG', 0, 'Access Diploma', ''],
        ['Q0002', 'Other qualification at level 2', 7, 'UG', 0, 'Below Advanced-level qualification (e.g. GCSE)', ''],
        ['X0002', 'Mature student admitted on basis of previous experience and/or admissions test', 8, 'UG and PG', 0, 'Mature student w experience', ''],
        ['X0004', 'Student has no formal qualification', 8, 'UG', 0, 'No formal qualifications', 'Is this likely - maybe for foundation entry? Or would they be admitted on mature age and experience?'],
    ];
    return $qualifications;
}

function install_prefill_qualifications_data($qualifications){
    global $DB;

    if (!$DB->get_manager()->table_exists('local_obu_qualifications')) {
        return;
    }

    foreach ($qualifications as $qualification) {
        $record = new stdClass();
        $record->code = $qualification[0];
        $record->label = $qualification[1];
        $record->priority = $qualification[2];
        $record->admissions_type = $qualification[3];
        $record->cpd_subset = $qualification[4];
        $record->crm_dropdown_text = $qualification[5];
        $record->notes = $qualification[6];

        if (!$DB->record_exists('local_obu_qualifications', ['code' => $qualification[0]])) {
            $DB->insert_record('local_obu_qualifications', $record, false);
        }
    }
}