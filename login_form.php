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
 * OBU Application - Login form
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham (derived from '/login/index_form.html')
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

if (!empty($CFG->loginpasswordautocomplete)) {
    $autocomplete = 'autocomplete="off"';
} else {
    $autocomplete = '';
}
?>
<div class="loginform">
	<div class="loginpanel">
        <?php echo get_config('local_obu_application', 'introduction'); ?>
		<h1 class="login-heading mb-4"><?php print_string('welcometitle', 'local_obu_application'); ?></h1>
        <?php
            if (!empty($errormsg)) {
                echo html_writer::link('#', $errormsg, array('id' => 'loginerrormessage', 'class' => 'sr-only'));
                echo html_writer::start_tag('div', array('class' => 'alert alert-danger', 'role' => 'alert'));
                echo $OUTPUT->error_text($errormsg);
                echo html_writer::end_tag('div');
            }
        ?>
        <form class="login-form" action="<?php echo $CFG->httpswwwroot; ?>/local/obu_application/login.php" method="post" id="login" <?php echo $autocomplete; ?>>
            <div class="loginform">
                <div class="login-form-username form-group">
                    <label for="username" class="sr-only">
                        <?php print_string("email") ?>
                    </label>
                    <input type="text" name="username" id="username" class="form-control form-control-lg" value="<?php p($frm->username) ?>" placeholder="Username" autocomplete="username">
                </div>
                <div class="login-form-password form-group">
                    <label for="password" class="sr-only"><?php print_string("password") ?></label>
                    <input type="password" name="password" id="password" value="" class="form-control form-control-lg" placeholder="Password" <?php echo $autocomplete; ?>>
                </div>
                <div class="login-form-submit form-group">
                    <button class="btn btn-primary btn-lg" type="submit" id="loginbtn"><?php print_string("login") ?></button>
                </div>
            </div>
            <div class="login-form-forgotpassword form-group">
                <a href="forgot_password.php"><?php print_string('forgotten', 'local_obu_application') ?></a>
            </div>
        </form>
        <div class="login-divider"></div>
        <h3>Don't have an account?</h3>
        <div class="d-flex" style="gap:10px">
            <a href="register_applicant.php" class="btn btn-primary" style="width:100%">Register as an applicant</a>
            <br />
            <a href="register_funder.php" class="btn btn-primary" style="width:100%">Register as a funder</a>
        </div>
        <div class="login-divider"></div>
        <div class="d-flex">
<!--            <button type="button" class="btn btn-secondary" data-modal="alert" data-modal-title-str='["cookiesenabled", "core"]'  data-modal-content-str='["cookiesenabled_help_html", "core"]'>--><?php //print_string('cookiesnotice', 'core') ?><!--</button>-->
            <a href="#" data-modal="alert" data-modal-title-str='["cookiesenabled", "core"]'  data-modal-content-str='["cookiesenabled_help_html", "core"]'><?php print_string('cookiesnotice', 'core') ?></a>
        </div>

        <?php //echo get_config('local_obu_application', 'support'); ?>
	</div>

</div>
