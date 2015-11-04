<?php

$mform->addElement('text', 'username', get_string('email'), 'size="25" maxlength="100"');
$mform->setType('username', PARAM_RAW_TRIMMED);
$mform->addRule('username', get_string('missingemail'), 'required', null, 'server');

$mform->addElement('text', 'email', get_string('emailagain'), 'size="25" maxlength="100"');
$mform->setType('email', PARAM_RAW_TRIMMED);
$mform->addRule('email', get_string('missingemail'), 'required', null, 'server');
