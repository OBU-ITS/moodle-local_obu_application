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
 * OBU Application - Admin settings.
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2018, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage(get_string('pluginname', 'local_obu_application'), get_string('plugintitle', 'local_obu_application'));
    $ADMIN->add('localplugins', $settings);
    $settings->add(new admin_setting_configtext('local_obu_application/google_analytics', get_string('google_analytics', 'local_obu_application'), get_string('google_analytics_text', 'local_obu_application'), ''));
	$settings->add(new admin_setting_confightmleditor('local_obu_application/introduction', get_string('introduction', 'local_obu_application'), get_string('introduction_text', 'local_obu_application'), '<h2>Welcome</h2>'));
    $settings->add(new admin_setting_confightmleditor('local_obu_application/support', get_string('support', 'local_obu_application'), get_string('support_text', 'local_obu_application'), 'For support, email moodle@brookes.ac.uk'));
    $settings->add(new admin_setting_confightmleditor('local_obu_application/privacy', get_string('privacy', 'local_obu_application'), get_string('privacy_text', 'local_obu_application'), 'Please see our privacy statement'));
}
