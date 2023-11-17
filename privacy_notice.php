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
 * OBU Application - User registration (signup) page
 *
 * @package    obu_application
 * @category   local
 * @author     Joe Souch (derived from '/login/signup.php')
 * @copyright  2023, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require('../../config.php');
require_once('./hide_moodle.php');
require_once('./locallib.php');

$PAGE->set_url(new moodle_url('/local/obu_application/privacy_notice.php'));
$PAGE->set_pagelayout('login');
$PAGE->add_body_class("privacy-notice");
$PAGE->set_title(get_string('browsertitle', 'local_obu_application'), false);

echo $OUTPUT->header();

echo get_config('local_obu_application', 'privacy');

echo $OUTPUT->footer();