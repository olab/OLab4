<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This file gives Entrada users the ability to update their user profile.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if (!defined("IN_PROFILE")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->isLoggedInAllowed('profile', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

	$PAGE_META["title"]			= "My Administrative Assistants";
	$PAGE_META["description"]	= "";
	$PAGE_META["keywords"]		= "";

	$PROXY_ID					= $ENTRADA_USER->getID();
	$VALID_MIME_TYPES			= array("image/pjpeg" => "jpg", "image/jpeg" => "jpg", "image/jpg" => "jpg", "image/gif" => "gif", "image/png" => "png");
	$VALID_MAX_FILESIZE			= 2097512; // 2MB
	$VALID_MAX_DIMENSIONS		= array("photo-width" => 216, "photo-height" => 300, "thumb-width" => 75, "thumb-height" => 104);
	$RENDER						= false;

	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/profile?section=assistants", "title" => "My Administrative Assistants");

	$PROCESSED		= array();

	if (isset($_SESSION["permissions"]) && is_array($_SESSION["permissions"]) && (count($_SESSION["permissions"]) > 1)) {
		$sidebar_html  = "<form id=\"masquerade-form\" action=\"".ENTRADA_URL."\" method=\"get\">\n";
		$sidebar_html .= "<label for=\"permission-mask\">Available permission masks:</label><br />";
		$sidebar_html .= "<select id=\"permission-mask\" name=\"mask\" style=\"width: 100%\" onchange=\"window.location='".ENTRADA_URL."/".$MODULE."/?".str_replace("&#039;", "'", replace_query(array("mask" => "'+this.options[this.selectedIndex].value")))."\">\n";
		$display_masks = true;
		$added_users = array();
		foreach ($_SESSION["permissions"] as $access_id => $result) {
			if ($result["organisation_id"] == $ENTRADA_USER->getActiveOrganisation() && is_int($access_id) && ((isset($result["mask"]) && $result["mask"]) || $access_id == $ENTRADA_USER->getDefaultAccessId() || ($result["id"] == $ENTRADA_USER->getID() && $ENTRADA_USER->getDefaultAccessId() != $access_id)) && array_search($result["id"], $added_users) === false) {
				if (isset($result["mask"]) && $result["mask"]) {
					$display_masks = true;
				}
				$added_users[] = $result["id"];
				$sidebar_html .= "<option value=\"".(($access_id == $ENTRADA_USER->getDefaultAccessId()) || !isset($result["permission_id"]) ? "close" : $result["permission_id"])."\"".(($result["id"] == $ENTRADA_USER->getActiveId()) ? " selected=\"selected\"" : "").">".html_encode($result["fullname"]) . "</option>\n";
			}
		}
		$sidebar_html .= "</select>\n";
		$sidebar_html .= "</form>\n";
		if ($display_masks) {
			new_sidebar_item("Permission Masks", $sidebar_html, "permission-masks", "open");
		}
	}

	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/". $MODULE .".js\"></script>";
	$HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/". $MODULE ."-assistants.js\"></script>";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/". $MODULE ."/". $MODULE .".css\" />";
	$HEAD[] = "<script>var PROV_STATE = \"". $prov_state ."\";</script>";
	$HEAD[] = "<script>var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";


	if ($ERROR) {
		fade_element("out", "display-error-box");
		echo display_error();
	}

	if ($SUCCESS) {
		fade_element("out", "display-success-box");
		echo display_success();
	}

	if ($NOTICE) {
		fade_element("out", "display-notice-box");
		echo display_notice();
	}

	$user_object = Models_User::fetchRowByID($ENTRADA_USER->getID());

    if ($user_object) {
        $result = $user_object->toArray();
    
        $HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js\"></script>";
        if ($ENTRADA_ACL->isLoggedInAllowed('assistant_support', 'create')) {
        ?>
            <h1 style="margin-top: 0px">My Admin Assistants</h1>
            This section allows you to assign other <?php echo APPLICATION_NAME; ?> users access privileges to <strong>your</strong> <?php echo APPLICATION_NAME; ?> account permissions. This powerful feature should be used very carefully because when you assign someone privileges to your account, they will be able to do <strong>everything in this system</strong> that you are able to do using their own account.
            <br /><br />
            <form class="form-horizontal" action="<?php echo ENTRADA_URL; ?>/profile?section=assistants" method="post" id="assistant_add_form">
                <input type="hidden" name="action" value="assistant-add" />
                <input type="hidden" id="assistant_ref" name="assistant_ref" value="" />
                <input type="hidden" id="assistant_id" name="assistant_id" value="" />
                
                <div class="control-group">
                    <label for="assistant_name" class="control-label form-required">Assistants Fullname:</label>
                    <div class="controls">
                        <input type="text" id="assistant_name" name="fullname" size="30" value="" placeholder="<?php echo $translate->_("Type to search for assistants...");?>" style="width: 203px; vertical-align: middle" />
                        <input type="button" class="btn btn-primary pull-right" value="Add Assistant" onclick="addAssistant()" />
                    </div>

                    <div id="autocomplete">
                        <div id="autocomplete-list-container"></div>
                    </div>
                </div>
                <?php echo Entrada_Utilities::generate_calendars("valid", "Access", true, true, $start_time = ((isset($PROCESSED["valid_from"])) ? $PROCESSED["valid_from"] : mktime(0, 0, 0, date("n", time()), date("j", time()), date("Y", time()))), true, true, ((isset($PROCESSED["valid_until"])) ? $PROCESSED["valid_until"] : strtotime("+1 week 23 hours 59 minutes 59 seconds", $start_time))); ?>
            </form>
            <hr />
            <?php
            $results = Models_User::getAssistants($ENTRADA_USER->getID());
            if ($results) {
            ?>
            <form action="<?php echo ENTRADA_URL; ?>/profile?section=assistants" method="post" id="assistant_remove_form">
                <input type="hidden" name="action" value="assistant-remove" />
                <table class="tableList" cellspacing="0" summary="List of Assistants">
                    <colgroup>
                        <col class="modified" />
                        <col class="title" />
                        <col class="date" />
                        <col class="date" />
                    </colgroup>
                    <thead>
                    <tr>
                        <td class="modified">&nbsp;</td>
                        <td class="title">Assistants Fullname</td>
                        <td class="date">Access Starts</td>
                        <td class="date sortedASC"><div class="noLink">Access Finishes</div></td>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <td colspan="4" style="border-top: 2px #CCCCCC solid; padding-top: 5px; text-align: right">
                            <input type="button" class="btn btn-danger" value="Remove Assistant" onclick="confirmRemoval()" />
                        </td>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php
                    foreach ($results as $result) {
                        echo "<tr>\n";
                        echo "    <td class=\"modified\"><input type=\"checkbox\" name=\"remove[".$result["assigned_to"]."]\" value=\"".$result["permission_id"]."\" /></td>\n";
                        echo "    <td class=\"title\">".html_encode($result["fullname"])."</td>\n";
                        echo "    <td class=\"date\">".date(DEFAULT_DATE_FORMAT, $result["valid_from"])."</td>\n";
                        echo "    <td class=\"date\">".date(DEFAULT_DATE_FORMAT, $result["valid_until"])."</td>\n";
                        echo "</tr>\n";
                    }
                    ?>
                    </tbody>
                </table>
            </form>
            <?php
            } else {
                add_notice("You currently have no assistants / administrative support staff setup for access to your permissions.");

                echo display_notice();
            }
        }
    } else {
        add_notice("Unfortunately your ".APPLICATION_NAME." profile is not accessible at this time, please try again later.");
        
        echo display_notice();
        
        application_log("error", "A user profile was not available in the database? Database said: ".$db->ErrorMsg());
    }
}
?>