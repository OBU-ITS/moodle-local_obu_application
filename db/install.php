<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Code to execute on plugin installation
 */
function xmldb_local_obu_application_install() {

    $qualifications = get_prefill_qualifications_data();
    install_prefill_qualifications_data($qualifications);

    return true;
}