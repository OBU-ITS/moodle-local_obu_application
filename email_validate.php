<?php

if (!validate_email($data['username']) || ($data['username'] != strtolower($data['username']))) {
    $errors['username'] = get_string('invalidemail');
}
		
if (empty($data['email'])) {
    $errors['email'] = get_string('missingemail');
} else if ($data['email'] != $data['username']) {
    $errors['email'] = get_string('invalidemail');
}
