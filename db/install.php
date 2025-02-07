<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Code to execute on plugin installation
 */
function xmldb_local_obu_application_install() {
    global $CFG;

    require_once($CFG->dirroot . '/local/obu_application/db/data/prefill_qualifications.php');

    $qualifications = get_prefill_qualifications_data();
    install_prefill_qualifications_data($qualifications);

    return true;
}