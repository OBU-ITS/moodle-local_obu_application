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
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

if (!empty($CFG->loginpasswordautocomplete)) {
    $autocomplete = 'autocomplete="off"';
} else {
    $autocomplete = '';
}
?>

<div class="loginbox clearfix twocolumns">
	
    <div class="signuppanel">
		<?php print_string('introduction', 'local_obu_application') ?>
		<h2><?php print_string('registration', 'local_obu_application') ?></h2>
		<div class="subcontent">
			<?php print_string('registrationsteps', 'local_obu_application', 'signup.php'); ?>
			<div class="signupform">
				<form action="signup.php" method="get" id="register">
					<div><input type="submit" value="<?php print_string('register', 'local_obu_application') ?>" /></div>
				</form>
			</div>
		</div>
    </div>

	<div class="loginpanel">
		<h2><?php print_string("login") ?></h2>
		<div class="subcontent loginsub">
			<?php
				if (!empty($errormsg)) {
					echo html_writer::start_tag('div', array('class' => 'loginerrors'));
					echo html_writer::link('#', $errormsg, array('id' => 'loginerrormessage', 'class' => 'accesshide'));
					echo $OUTPUT->error_text($errormsg);
					echo html_writer::end_tag('div');
				}
			?>
			<form action="<?php echo $CFG->httpswwwroot; ?>/local/obu_application/login.php" method="post" id="login" <?php echo $autocomplete; ?> >
				<div class="loginform">
					<div class="form-label"><label for="username"><?php print_string("email") ?></label></div>
					<div class="form-input"><input type="text" name="username" id="username" size="50" maxlength="100" value="<?php p($frm->username) ?>" /></div>
					<div class="clearer"><!-- --></div>
					<div class="form-label"><label for="password"><?php print_string("password") ?></label></div>
					<div class="form-input">
						<input type="password" name="password" id="password" size="12" maxlength="32" value="" <?php echo $autocomplete; ?> />
						<input type="submit" id="loginbtn" value="<?php print_string("login") ?>" />
					</div>
				</div>
				<div class="clearer"><!-- --></div>
				<?php if (isset($CFG->rememberusername) and $CFG->rememberusername == 2) { ?>
					<div class="rememberpass">
						<input type="checkbox" name="rememberusername" id="rememberusername" value="1" <?php if ($frm->username) {echo 'checked="checked"';} ?> />
						<label for="rememberusername"><?php print_string('rememberemail', 'local_obu_application') ?></label>
					</div>
				<?php } ?>
				<div class="clearer"><!-- --></div>
				<div class="forgetpass"><a href="forgot_password.php"><?php print_string('forgotten', 'local_obu_application') ?></a></div>
			</form>
			<div class="desc">
				<?php
					echo get_string('cookiesenabled');
					echo $OUTPUT->help_icon('cookiesenabled');
				?>
			</div>
		</div>
	</div>
	
</div>
