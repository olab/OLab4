<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("firstlogin", "read")) {
	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $value = "";

    /*
     * Determine what needs to be updated with regards to the profile.
     * @todo This is technical debt, please find a more efficient way of doing this.
     */
    if (!(int) $_SESSION["details"]["privacy_level"]) {
        if ($COPYRIGHT) {
            if ($_SESSION["details"]["google_id"] == "opt-in") {
                $value = "privacy-copyright-google-update";
            } else {
                $value = "privacy-copyright-update";
            }
        } else {
            if ($_SESSION["details"]["google_id"] == "opt-in") {
                $value = "privacy-google-update";
            } else {
                $value = "privacy-update";
            }
        }
    } else {
        if ($COPYRIGHT) {
            if ($_SESSION["details"]["google_id"] == "opt-in") {
                $value = "copyright-google-update";
            } else {
                $value = "copyright-update";
            }
        } else {
            if ($_SESSION["details"]["google_id"] == "opt-in") {
                $value = "google-update";
            }
        }
    }
	?>

	<script type="text/javascript">
		function acceptButton(cb) {
			if(cb.checked)
			{
				$('proceed-button').disabled = false;
			}
			else
			{
				$('proceed-button').disabled = true;
			}
			return;
		}
	</script>

	<form action="<?php echo ENTRADA_RELATIVE; ?>/profile" method="post">
        <?php
        echo "<input type=\"hidden\" name=\"action\" value=\"".$value."\" />\n";
        echo (($PROCEED_TO) ? "<input type=\"hidden\" name=\"redirect\" value=\"".rawurlencode($PROCEED_TO)."\" />\n" : "");
        ?>

        <div class="page-header">
            <h1>Welcome to <?php echo APPLICATION_NAME; ?>,</h1>
            <p class="lead">Our integrated online teaching and learning platform. Since this is likely your first time logging in, we just need to collect a bit of information to finish provisioning your account.</p>
        </div>

        <?php
        /*
         * Google Hosted Apps Account
         */
        if ($_SESSION["details"]["google_id"] == "opt-in") {
            ?>
            <h2>Create Your <strong><?php echo $GOOGLE_APPS["domain"]; ?></strong> Google Account</h2>
            <div class="alert alert-info">
                Would you like to create a <strong><?php echo $GOOGLE_APPS["domain"]; ?></strong> account, powered by Google? This exciting new ability gives you your own personal <?php echo $GOOGLE_APPS["quota"]; ?> e-mail address @<?php echo $GOOGLE_APPS["domain"]; ?> that you can keep <em>indefinitely</em>! In addition to e-mail, you also have access to your own personal calendar space, and a powerful suite of online document tools.
            </div>

            <label class="radio">
                <input type="radio" id="google_account_1" name="google_account" value="1" checked="checked" />
                <strong>Yes Please!</strong>: create my <?php echo $GOOGLE_APPS["domain"]; ?> account</strong>.
                <div class="content-small">
                    Your account will be automatically created, and activation information will be sent to <strong><?php echo $_SESSION["details"]["email"]; ?></strong>.
                </div>
            </label>

            <label class="radio">
                <input type="radio" id="google_account_0" name="google_account" value="0" />
                <strong>No Thank-you</strong>: please do not create me an account at this time.
                <div class="content-small">
                    If you decide you would like one in the future, simply contact the system administrator.
                </div>
            </label>

            <?php
        }

        /*
         * Privacy Level Settings
         */
        if (!(int) $_SESSION["details"]["privacy_level"]) {
            ?>
            <h2>Privacy Level Setting</h2>
            <div class="alert alert-info">
                <?php echo APPLICATION_NAME; ?> contains a <strong>People Search</strong> tab, which acts a directory of people associated with your institution. You can lookup people using a simple name search or by browsing through groups. Please tell us how much information you wish to reveal about yourself when other students use People Search.
            </div>

            <label class="radio">
                <input type="radio" id="privacy_level_3" name="privacy_level" value="3" />
                <strong>Complete Profile</strong>: show the information I choose to provide.
                <div class="content-small">
                    This means that normal logged in users will be able to view any information you provide in the <strong>My Profile</strong> section. You can provide as much or as little information as you would like; however, whatever you provide will be displayed.
                </div>
            </label>

            <label class="radio">
                <input type="radio" id="privacy_level_2" name="privacy_level" value="2" checked="checked" />
                <strong>Typical Profile</strong>: show basic information about me.
                <div class="content-small">
                    This means that normal logged in users will only be able to view your name, email address, role, official photo and uploaded photo if you have added one, regardless of how much information you provide in the <strong>My Profile</strong> section.
                </div>
            </label>


            <label class="radio">
                <input type="radio" id="privacy_level_1" name="privacy_level" value="1" />
                <strong>Minimal Profile</strong>: show minimal information about me.
                <div class="content-small">
                    This means that normal logged in users will only be able to view your name and role. In other words, people will not be able to get your e-mail address or other contact information.
                </div>
            </label>

            <?php
        }

        /*
         * Copyright
         */
        if ($COPYRIGHT) {
            ?>
            <h2><?php echo $translate->_("copyright_title"); ?></h2>

            <div class="alert alert-info">
                <?php echo $copyright_settings["copyright-firstlogin"]; ?>

                <label class="checkbox space-above">
                    <input type="checkbox" value="1" onchange="acceptButton(this)"> <?php echo $translate->_("copyright_accept_label"); ?>
                </label>
            </div>
            <?php
            echo "<input type=\"hidden\" name=\"copyright\" value=\"1\" />\n";
        }
        ?>

        <input type="submit" class="btn btn-primary pull-right" id="proceed-button" value="Proceed"<?php echo ($COPYRIGHT ? " disabled=\"disabled\"" : ""); ?> />
	</form>
	<?php
}