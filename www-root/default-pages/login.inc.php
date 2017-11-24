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
 * The main login page for Entrada.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) exit;

/**
 * Focus on the username textbox when this module is loaded.
 */
$ONLOAD[] = "jQuery('#username').focus()";

if (($ACTION == "login") && $ERROR) {
	echo display_error();
}

/**
 * If the user is trying to access a link and is not logged in, display a
 * notice to inform the user that they need to log in first.
 */
if ($PROCEED_TO && (stristr($PROCEED_TO, "link-course.php") || stristr($PROCEED_TO, "link-event.php"))) {
	echo display_notice("You must log in to access this link; once you have logged in you will be automatically redirected to the requested location.");
}

/**
 * If the user is trying to access a file and is not logged in, display a
 * notice to inform the user that they need to log in first.
 */
if ($PROCEED_TO && (stristr($PROCEED_TO, "file-course.php") || stristr($PROCEED_TO, "file-event.php"))) {
	echo display_notice("You must log in to download the requested file; once you have logged in the download will start automatically.");
}
?>
<div class="row-fluid">
    <div id="login-form" class="span4">
        <?php
        if (defined("AUTH_SSO_ENABLED") && (AUTH_SSO_ENABLED == true) && !$SSO_ERROR) {
        ?>
        <a href="<?php echo ENTRADA_URL; ?>/<?php echo "?action=ssologin".(($PROCEED_TO) ? "&url=".rawurlencode($PROCEED_TO) : ""); ?>">
            <span>
                <h2><?php echo $translate->_("SSO Login");?></h2>
            </span>
        </a>
        <?php
        }
        if (defined("AUTH_METHOD") && !(AUTH_METHOD == "sso")) {
        ?>
        <h2><?php echo APPLICATION_NAME; ?> Login</h2>
        <p>Please enter your <?php echo APPLICATION_NAME; ?> username and password to log in.</p>

        <form class="login-form" action="<?php echo ENTRADA_URL; ?>/<?php echo(($PROCEED_TO) ? "?url=" . rawurlencode($PROCEED_TO) : ""); ?>" method="post">
            <input type="hidden" name="action" value="login"/>
            <input type="hidden" name="ssobypass" value="1"/>
            <div class="control-group">
                <label class="control-label" for="username">Username</label>
                <div class="controls">
                    <input type="text" id="username" name="username" value="<?php echo((isset($_REQUEST["username"])) ? html_encode(trim($_REQUEST["username"])) : ""); ?>"/>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="password">Password</label>
                <div class="controls">
                    <input type="password" id="password" name="password" value=""/>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="login-actions">
                <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("login"); ?>">
                <a href="<?php echo ENTRADA_RELATIVE; ?>/password_reset">Forgot your password?</a>
            </div>
        </form>
        <div class="clearfix"></div>
        <?php
        }
        ?>
    </div>
    <?php
    $public_notices = Models_Notice::fetchPublicNotices();
    if ($public_notices) {
    ?>
    <div class="span8">
        <h2>Public Notices</h2>
        <ul class="public-notices">
            <?php
            foreach ($public_notices as $notice) {
                echo "<li>";
                echo "	<span class=\"content-small\">".date(DEFAULT_DATE_FORMAT, $notice["updated_date"])."</span>\n";
                echo "	<p>".trim(strip_selected_tags(clean_input($notice["notice_summary"], "html"), "p"))."</p>";
                echo "</li>";
            }
            ?>
        </ul>
    </div>
    <?php
    }
    ?>
</div>