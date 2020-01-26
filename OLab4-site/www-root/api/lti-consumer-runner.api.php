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
 * Loads the Course link wizard when a course director wants to add / edit
 * a linked resource on the Manage Courses > Content page.
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

require_once "Entrada/lti/oauth/oauth-utils.class.php";
require_once "Entrada/lti/oauth/oauth-exception.class.php";
require_once "Entrada/lti/oauth/oauth-request.class.php";
require_once "Entrada/lti/oauth/oauth-token.class.php";
require_once "Entrada/lti/oauth/oauth-consumer.class.php";
require_once "Entrada/lti/oauth/oauth-signature-method.interface.php";
require_once "Entrada/lti/oauth/method/oauth-signature-method-hmac-sha1.class.php";
require_once "Entrada/lti/LTIConsumer.class.php";

ob_start("on_checkout");

if (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    echo "<div id=\"scripts-on-open\" style=\"display: none;\">\n";
    echo "alert('It appears as though your session has expired; you will now be taken back to the login page.');\n";
    echo "if(window.opener) {\n";
    echo "	window.opener.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
    echo "	top.window.close();\n";
    echo "} else {\n";
    echo "	window.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
    echo "}\n";
    echo "</div>\n";
    exit;
} else {
    $LTI_ID = 0;
    $WIDTH = 400;
    $HEIGHT = 400;
    $IS_EVENT = false;

    if ((isset($_GET["ltiid"])) && ((int) trim($_GET["ltiid"]))) {
        $LTI_ID = (int) trim($_GET["ltiid"]);
    }

    if ((isset($_GET["width"])) && ((int) trim($_GET["width"]))) {
        $WIDTH = (int) trim($_GET["width"]);
    }

    if ((isset($_GET["height"])) && ((int) trim($_GET["height"]))) {
        $HEIGHT = (int) trim($_GET["height"]);
    }

    if ((isset($_GET["event"])) && ((int) trim($_GET["event"]))) {
        $IS_EVENT = true;
    }

    if ($WIDTH <= 0)  { $WIDTH = 400; }
    if ($HEIGHT <= 0) { $HEIGHT = 400; }

    $WIDTH  = $WIDTH - 1;
    $HEIGHT = $HEIGHT - 70;

    if ($LTI_ID) {
        if ($IS_EVENT) {
            $query = "SELECT a.*, c.`course_id`, c.`course_name`, c.`course_code`, c.`organisation_id`
                        FROM `event_lti_consumers` AS a
                        JOIN `events` AS b
                        ON b.`event_id` = a.`event_id`
                        JOIN `courses` AS c
                        ON c.`course_id` = b.`course_id`
                        WHERE a.`id` = " . $db->qstr($LTI_ID);
        } else {
            $query = "SELECT a.*, b.`course_id`, b.`course_name`, b.`course_code`, b.`organisation_id`
                        FROM `course_lti_consumers` AS a
                        JOIN `courses` AS b
                        ON b.`course_id` = a.`course_id`
                        WHERE a.`id` = " . $db->qstr($LTI_ID);
        }
        $result	= $db->GetRow($query);
        if ($result) {
			add_statistic((($IS_EVENT) ? "events" : "courses"), "launch_lti", "lti_id", $LTI_ID);

            $lti_role = "Learner";

            if ($ENTRADA_USER->getActiveGroup() != "student") {
                if ($IS_EVENT) {
                    if ($ENTRADA_ACL->amIAllowed(new EventContentResource($result["event_id"], $result["course_id"], $result["organisation_id"]), "update")) {
                        $lti_role = "Instructor";
                    }
                } else {
                    if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($result["course_id"], $result["organisation_id"]), "update")) {
                        $lti_role = "Instructor";
                    }
                }
            }

            $parameters = array(
                "resource_link_id" => (($IS_EVENT) ? "event" : "course") . "-" . $LTI_ID,
                "resource_link_title" => $result["lti_title"],
                "resource_link_description" => "",
                "user_id" => $ENTRADA_USER->getUsername(), // @todo This could be either getId(), getUsername(), or getNumber().
                "roles" => $lti_role,
                "lis_person_name_full" => $ENTRADA_USER->getFirstname() . " " . $ENTRADA_USER->getLastname(),
                "lis_person_name_family" => $ENTRADA_USER->getLastname(),
                "lis_person_name_given" => $ENTRADA_USER->getFirstname(),
                "lis_person_contact_email_primary" => $ENTRADA_USER->getEmail(),
                "context_id" => $result["course_code"],
                "context_title" => $result["course_name"],
                "context_label" => $result["course_code"],
                "tool_consumer_info_product_family_code" => APPLICATION_NAME,
                "tool_consumer_info_version" => APPLICATION_VERSION,
                "tool_consumer_instance_guid" => ENTRADA_URL,
                "tool_consumer_instance_description" => "",
                "launch_presentation_locale" => "en-US",
                "launch_presentation_document_target" => "iframe",
                "launch_presentation_width" => "",
                "launch_presentation_height" => "",
                "launch_presentation_css_url" => ""
            );

            if ($result["lti_params"]) {
                $paramsList = explode(";", $result["lti_params"]);
                if ($paramsList && count($paramsList) > 0) {
                    foreach ($paramsList as $param) {
                        $parts = explode("=", $param);
                        if ($parts && (count($parts) == 2)) {
                            $key = clean_input($parts[0], array("trim", "notags"));
                            $value = clean_input($parts[1], array("trim", "notags"));

                            if ($key) {
                                $parameters["custom_".$key] = $value;
                            }
                        }
                    }
                }
            }

            $ltiConsumer = new LTIConsumer();
            $signedParams = $ltiConsumer->sign($parameters, $result["launch_url"], "POST", $result["lti_key"], $result["lti_secret"]);
            ?>
            <div id="ltiContainer">
                <form id="ltiSubmitForm" name="ltiSubmitForm" method="POST" action="<?php echo html_encode($result["launch_url"]); ?>" target="ltiTestFrame" enctype="application/x-www-form-urlencoded">
                    <?php
                    if ($signedParams && count($signedParams) > 0) {
                        foreach ($signedParams as $key => $value) {
                            $key = htmlspecialchars($key);
                            $value = htmlspecialchars($value);

                            echo "<input type=\"hidden\" name=\"" . $key . "\" value=\"" . $value . "\"/>";
                        }
                    }
                    ?>
                    <input id="ltiSubmitBtn" type="submit" style="display: none;"/>
                </form>
                <h3 class="border-below" style="margin-top: -30px;">LTI Provider - <?php echo html_encode($result["lti_title"]); ?></h3>
                <iframe name="ltiTestFrame" id="ltiTestFrame" src="" width="<?php echo $WIDTH; ?>" height="<?php echo $HEIGHT; ?>" scrolling="auto" style="border: 1px solid rgba(0, 0, 0, 0.075);" transparency=""></iframe>
                <div>
                    <input type="button" class="btn" value="Close" onclick="closeLTIDialog()" />
                </div>
                <div id="scripts-on-open" style="display: none;">
                    submitLTIForm();
                </div>
            </div>
            <?php
        } else {
            add_error("Unable to locate the requested LTI Provider at this time.");

            echo display_error();
        }

    } else {
        add_error("You must specify an LTI Provider identifier to run");

        echo display_error();
    }
}