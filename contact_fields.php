<?php

$mform->addElement('text', 'idnumber', get_string('title', 'local_obu_application'), 'size="30" maxlength="100"');
$mform->setType('idnumber', PARAM_TEXT);
$mform->addRule('idnumber', null, 'required', null, 'server');
		
$mform->addElement('text', 'firstname', get_string('firstname'), 'size="30" maxlength="100"');
$mform->setType('firstname', PARAM_TEXT);
$mform->addRule('firstname', null, 'required', null, 'server');
		
$mform->addElement('text', 'lastname', get_string('lastname'), 'size="30" maxlength="100"');
$mform->setType('lastname', PARAM_TEXT);
$mform->addRule('lastname', null, 'required', null, 'server');
		
$mform->addElement('textarea', 'address', get_string('address'), 'cols="40" rows="5"');
$mform->setType('address', PARAM_TEXT);
$mform->addRule('address', null, 'required', null, 'server');

$mform->addElement('text', 'city', get_string('postcode', 'local_obu_application'), 'size="15" maxlength="100"');
$mform->setType('city', PARAM_TEXT);
$mform->addRule('city', null, 'required', null, 'server');

$mform->addElement('text', 'phone1', get_string('phone', 'local_obu_application'), 'size="30" maxlength="100"');
$mform->setType('phone1', PARAM_TEXT);
$mform->addRule('phone1', null, 'required', null, 'server');
