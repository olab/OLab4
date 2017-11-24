<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This is the index file of each community when there has been no module requested.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_COURSE"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

$query	= "	SELECT *
			FROM `community_pages`
			WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
			AND `page_url` = ".$db->qstr(((isset($PAGE_URL) && ($PAGE_URL)) ? $PAGE_URL : ""))."
			AND `page_active` = '1'";
$result	= $db->GetRow($query);
if ($result) {

	if ($ERROR) {
		echo display_error();
	}
	if ($result["page_url"] != '') {
		if (isset($result["page_title"]) && trim($result["page_title"]) != "") {
			echo "<h1>".html_encode($result["page_title"])."</h1>\n";
		}

		echo "<div class=\"community-page-content\" style=\"margin-top: 10px;\">";
		echo 	$result["page_content"];
		echo "</div>";
	}
}

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
    $query	= "	SELECT *
                FROM `community_courses`
                WHERE `community_id` = ".$db->qstr($COMMUNITY_ID) . "
                ORDER BY `course_id` ASC";
    $community_courses	= $db->GetAll($query);
    if ($community_courses) {
        $course_ids = array();
        foreach ($community_courses as $community_course) {
            $course_ids[] = $db->qstr($community_course["course_id"]);
        }

        $query	= "	SELECT *
                    FROM `events`
                    WHERE `course_id` IN (".implode(", ", $course_ids).")";
        $course_events = $db->GetAll($query);

        $event_ids = array();
        foreach ($course_events as $course_event) {
            $event_ids[] = $db->qstr($course_event["event_id"]);
        }

        switch ($PAGE_URL) {
            case "" :
                $query = "	SELECT *
                            FROM `courses`
                            WHERE `course_id` = " . $course_ids[0] . "
                            AND `course_active` = 1";
                $result = $db->GetRow($query);
                if ($result) {
                    if ($result["course_description"] && $result["course_description"] != "") {
                        echo "<div class=\"community-page-content\">\n";
                        echo $result["course_description"];
                        echo "</div>\n";
                    }
                    if ($result["course_message"] && $result["course_message"] != "") {
                        echo "<h1>Directors Message</h1>\n";
                        echo "<div class=\"community-page-content\" style=\"margin-top: 10px;\">\n";
                        echo $result["course_message"];
                        echo "</div>\n";
                    }
                }

                $query = "	SELECT b.*, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, c.`account_active`, c.`access_starts`, c.`access_expires`, c.`last_login`, c.`role`, c.`group`
                            FROM `course_contacts` AS a
                            JOIN `".AUTH_DATABASE."`.`user_data` AS b
                            ON b.`id` = a.`proxy_id`
                            JOIN `".AUTH_DATABASE."`.`user_access` AS c
                            ON c.`user_id` = b.`id`
                            AND c.`app_id` IN (".AUTH_APP_IDS_STRING.")
                            JOIN `courses` AS d
                            ON a.`course_id` = d.`course_id`
                            AND d.`course_active` = 1
                            WHERE a.`course_id` IN (".implode(", ", $course_ids).")
                            AND a.`contact_type` = 'director'
                            GROUP BY b.`id`
                            ORDER BY `contact_order` ASC";
                $results = $db->GetAll($query);
                if ($results) {
                    echo "<h2>" . (count($results) > 1 ? $translate->_("Course Directors") : $translate->_("Course Director")) . "</h2>\n";
                    foreach ($results as $key => $result) {
                        echo "<div id=\"result-".$result["id"]."\" style=\"width: 100%; padding: 5px 0px 5px 5px; line-height: 16px; text-align: left;\">\n";
                        echo "	<table style=\"width: 100%;\" class=\"profile-card\">\n";
                        echo "	<colgroup>\n";
                        echo "		<col style=\"width: 15%\" />\n";
                        echo "		<col style=\"width: 25%\" />\n";
                        echo "		<col style=\"width: 38%\" />\n";
                        echo "		<col style=\"width: 22%\" />\n";
                        echo "	<colgroup>";
                        echo "	<tr>";
                        echo "		<td style=\"vertical-align: top;\">";
                        echo "			<div id=\"img-holder-".$result["id"]."\" class=\"img-holder\">\n";

                        $offical_file_active	= false;
                        $uploaded_file_active	= false;

                        /**
                         * If the photo file actually exists, and either
                         * 	If the user is in an administration group, or
                         *  If the user is trying to view their own photo, or
                         *  If the proxy_id has their privacy set to "Any Information"
                         */
                        if ((@file_exists(STORAGE_USER_PHOTOS."/".$result["id"]."-official")) && $ENTRADA_ACL && ($ENTRADA_ACL->amIAllowed(new PhotoResource($result["id"], (int) $result["privacy_level"], "official"), "read"))) {
                            $offical_file_active	= true;
                        }

                        /**
                         * If the photo file actually exists, and
                         * If the uploaded file is active in the user_photos table, and
                         * If the proxy_id has their privacy set to "Basic Information" or higher.
                         */
                        $query			= "SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = '1' AND `photo_active` = '1' AND `proxy_id` = ".$db->qstr($result["id"]);
                        $photo_active	= $db->GetOne($query);
                        if ((@file_exists(STORAGE_USER_PHOTOS."/".$result["id"]."-upload")) && $photo_active && $ENTRADA_ACL && ($ENTRADA_ACL->amIAllowed(new PhotoResource($result["id"], (int) $result["privacy_level"], "upload"), "read"))) {
                            $uploaded_file_active = true;
                        }

                        if ($uploaded_file_active) {
                            echo "		<img id=\"uploaded_photo_".$result["id"]."\" class=\"uploaded\" src=\"".webservice_url("photo", array($result["id"], "upload"))."\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" title=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" />\n";
                        } elseif ($offical_file_active) {
                            echo "		<img id=\"official_photo_".$result["id"]."\" class=\"official\" src=\"".webservice_url("photo", array($result["id"], "official"))."\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" title=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" />\n";
                        }

                        if ((!$offical_file_active) && (!$uploaded_file_active)) {
                            echo "		<img src=\"".ENTRADA_URL."/images/headshot-male.gif\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"No Photo Available\" title=\"No Photo Available\" />\n";
                        }

                        echo "			</div>\n";
                        echo "		</td>\n";
                        echo "		<td style=\"font-size: 14px; color: #003366; vertical-align: top\">";
                        echo "			<div style=\"font-weight: bold;\">".html_encode((($result["prefix"]) ? $result["prefix"]." " : "").$result["firstname"]." ".$result["lastname"])."</div>";
                        echo "			<div class=\"content-small\" style=\"margin-bottom: 15px\">".ucwords($result["group"])." > ".($result["group"] == "student" ? "Class of " : "").ucwords($result["role"])."</div>\n";
                        if ($result["privacy_level"] > 1 || $COMMUNITY_ADMIN) {
                            echo "			<a href=\"mailto:".html_encode($result["email"])."\">".html_encode($result["email"])."</a><br />\n";

                            if ($result["email_alt"]) {
                                echo "		<a href=\"mailto:".html_encode($result["email_alt"])."\">".html_encode($result["email_alt"])."</a>\n";
                            }
                        }
                        echo "		</td>\n";
                        echo "		<td style=\"padding-top: 1.3em;\">\n";
                        echo "			<div>\n";
                        echo "				<table class=\"address-info\" style=\"width: 100%;\">\n";
                        if ($result["telephone"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr>\n";
                            echo "				<td style=\"width: 30%;\">Telephone: </td>\n";
                            echo "				<td>".html_encode($result["telephone"])."</td>\n";
                            echo "			</tr>\n";
                        }
                        if ($result["fax"] && ($result["privacy_level"] > 2 || $COMMUNITY_ADMIN)) {
                            echo "			<tr>\n";
                            echo "				<td>Fax: </td>\n";
                            echo "				<td>".html_encode($result["fax"])."</td>\n";
                            echo "			</tr>\n\n";
                        }
                        if ($result["address"] && $result["city"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr>\n";
                            echo "				<td><br />Address: </td>\n";
                            echo "				<td><br />".html_encode($result["address"])."</td>\n";
                            echo "			</tr>\n";
                            echo "			<tr>\n";
                            echo "				<td>&nbsp;</td>\n";
                            echo "				<td>".html_encode($result["city"].($result["city"] && $result["province"] ? ", ".$result["province"] : ""))."</td>\n";
                            echo "			</tr>\n";
                            echo "			<tr>\n";
                            echo "				<td>&nbsp;</td>\n";
                            echo "				<td>".html_encode($result["country"].($result["country"] && $result["postcode"] ? ", ".$result["postcode"] : ""))."</td>\n";
                            echo "			</tr>\n";
                        }
                        if ($result["office_hours"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr><td colspan=\"2\">&nbsp;</td></tr>";
                            echo "			<tr>\n";
                            echo "				<td>Office Hours: </td>\n";
                            echo "				<td>".nl2br(html_encode($result["office_hours"]))."</td>\n";
                            echo "			</tr>\n\n";
                        }
                        echo "				</table>\n";
                        echo "			</div>\n";
                        echo "		</td>\n";
                        echo "		<td style=\"padding-top: 1.3em; vertical-align: top\">\n";

                        $query		= "	SELECT CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
                                        FROM `permissions` AS a
                                        LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                        ON b.`id` = a.`assigned_to`
                                        WHERE a.`assigned_by`=".$db->qstr($result["id"])."
                                        AND (a.`valid_from` = '0' OR a.`valid_from` <= ".$db->qstr(time()).") AND (a.`valid_until` = '0' OR a.`valid_until` > ".$db->qstr(time()).")
                                        ORDER BY `valid_until` ASC";
                        $assistants	= $db->GetAll($query);
                        if ($assistants) {
                            echo "		<span class=\"content-small\">Administrative Assistants:</span>\n";
                            echo "		<ul class=\"assistant-list\">";
                            foreach ($assistants as $assistant) {
                                echo "		<li><a href=\"mailto:".html_encode($assistant["email"])."\">".html_encode($assistant["fullname"])."</a></li>";
                            }
                            echo "		</ul>";
                        }
                        echo "		</td>\n";
                        echo "	</tr>\n";
                        echo "	</table>\n";
                        echo "</div>\n";
                    }
                }

                $query = "	SELECT b.*, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, c.`account_active`, c.`access_starts`, c.`access_expires`, c.`last_login`, c.`role`, c.`group`
                            FROM `courses` AS a
                            LEFT JOIN `course_contacts` AS a1
                            ON a.`course_id` = a1.`course_id`
                            AND a1.`contact_type` = 'pcoordinator'
                            JOIN `".AUTH_DATABASE."`.`user_data` AS b
                            ON (b.`id` = a.`pcoord_id` OR b.`id` = a1.`proxy_id`)
                            JOIN `".AUTH_DATABASE."`.`user_access` AS c
                            ON c.`user_id` = b.`id`
                            AND c.`app_id` IN (".AUTH_APP_IDS_STRING.")
                            WHERE a.`course_id` IN (".implode(", ", $course_ids).")
                            AND a.`course_active` = '1'
                            GROUP BY b.`id`
                            ORDER BY `contact_order` ASC";
                $results = $db->GetAll($query);
                if ($results) {
                    echo "<h2>" . (count($results) > 1 ? $translate->_("Program Coordinators") : $translate->_("Program Coordinator")) . "</h2>\n";
                    foreach ($results as $key => $result) {
                        echo "<div id=\"result-".$result["id"]."\" style=\"width: 100%; padding: 5px 0px 5px 5px; line-height: 16px; text-align: left;\">\n";
                        echo "	<table style=\"width: 100%;\" class=\"profile-card\">\n";
                        echo "	<colgroup>\n";
                        echo "		<col style=\"width: 15%\" />\n";
                        echo "		<col style=\"width: 25%\" />\n";
                        echo "		<col style=\"width: 38%\" />\n";
                        echo "		<col style=\"width: 22%\" />\n";
                        echo "	<colgroup>";
                        echo "	<tr>";
                        echo "		<td style=\"vertical-align: top;\">";
                        echo "			<div id=\"img-holder-".$result["id"]."\" class=\"img-holder\">\n";

                        $offical_file_active	= false;
                        $uploaded_file_active	= false;

                        /**
                         * If the photo file actually exists, and either
                         * 	If the user is in an administration group, or
                         *  If the user is trying to view their own photo, or
                         *  If the proxy_id has their privacy set to "Any Information"
                         */
                        if ((@file_exists(STORAGE_USER_PHOTOS."/".$result["id"]."-official")) && $ENTRADA_ACL && ($ENTRADA_ACL->amIAllowed(new PhotoResource($result["id"], (int) $result["privacy_level"], "official"), "read"))) {
                            $offical_file_active	= true;
                        }

                        /**
                         * If the photo file actually exists, and
                         * If the uploaded file is active in the user_photos table, and
                         * If the proxy_id has their privacy set to "Basic Information" or higher.
                         */
                        $query			= "SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = '1' AND `photo_active` = '1' AND `proxy_id` = ".$db->qstr($result["id"]);
                        $photo_active	= $db->GetOne($query);
                        if ((@file_exists(STORAGE_USER_PHOTOS."/".$result["id"]."-upload")) && $photo_active && $ENTRADA_ACL && ($ENTRADA_ACL->amIAllowed(new PhotoResource($result["id"], (int) $result["privacy_level"], "upload"), "read"))) {
                            $uploaded_file_active = true;
                        }


                        if ($uploaded_file_active) {
                            echo "		<img id=\"uploaded_photo_".$result["id"]."\" class=\"uploaded\" src=\"".webservice_url("photo", array($result["id"], "upload"))."\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" title=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" />\n";
                        } elseif ($offical_file_active) {
                            echo "		<img id=\"official_photo_".$result["id"]."\" class=\"official\" src=\"".webservice_url("photo", array($result["id"], "official"))."\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" title=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" />\n";
                        }

                        if ((!$offical_file_active) && (!$uploaded_file_active)) {
                            echo "		<img src=\"".ENTRADA_URL."/images/headshot-male.gif\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"No Photo Available\" title=\"No Photo Available\" />\n";
                        }

                        echo "			</div>\n";
                        echo "		</td>\n";
                        echo "		<td style=\"font-size: 14px; color: #003366; vertical-align: top\">";
                        echo "			<div style=\"font-weight: bold;\">".html_encode((($result["prefix"]) ? $result["prefix"]." " : "").$result["firstname"]." ".$result["lastname"])."</div>";
                        echo "			<div class=\"content-small\" style=\"margin-bottom: 15px\">".ucwords($result["group"])." > ".($result["group"] == "student" ? "Class of " : "").ucwords($result["role"])."</div>\n";
                        if ($result["privacy_level"] > 1 || $COMMUNITY_ADMIN) {
                            echo "			<a href=\"mailto:".html_encode($result["email"])."\">".html_encode($result["email"])."</a><br />\n";

                            if ($result["email_alt"]) {
                                echo "		<a href=\"mailto:".html_encode($result["email_alt"])."\">".html_encode($result["email_alt"])."</a>\n";
                            }
                        }
                        echo "		</td>\n";
                        echo "		<td style=\"padding-top: 1.3em;\">\n";
                        echo "			<div>\n";
                        echo "				<table class=\"address-info\" style=\"width: 100%;\">\n";
                        if ($result["telephone"] && ($result["privacy_level"] > 2 || $COMMUNITY_ADMIN)) {
                            echo "			<tr>\n";
                            echo "				<td style=\"width: 30%;\">Telephone: </td>\n";
                            echo "				<td>".html_encode($result["telephone"])."</td>\n";
                            echo "			</tr>\n";
                        }
                        if ($result["fax"] && ($result["privacy_level"] > 2 || $COMMUNITY_ADMIN)) {
                            echo "			<tr>\n";
                            echo "				<td>Fax: </td>\n";
                            echo "				<td>".html_encode($result["fax"])."</td>\n";
                            echo "			</tr>\n\n";
                        }
                        if ($result["address"] && $result["city"] && ($result["privacy_level"] > 2 || $COMMUNITY_ADMIN)) {
                            echo "			<tr>\n";
                            echo "				<td><br />Address: </td>\n";
                            echo "				<td><br />".html_encode($result["address"])."</td>\n";
                            echo "			</tr>\n";
                            echo "			<tr>\n";
                            echo "				<td>&nbsp;</td>\n";
                            echo "				<td>".html_encode($result["city"].($result["city"] && $result["province"] ? ", ".$result["province"] : ""))."</td>\n";
                            echo "			</tr>\n";
                            echo "			<tr>\n";
                            echo "				<td>&nbsp;</td>\n";
                            echo "				<td>".html_encode($result["country"].($result["country"] && $result["postcode"] ? ", ".$result["postcode"] : ""))."</td>\n";
                            echo "			</tr>\n";
                        }
                        if ($result["office_hours"] && ($result["privacy_level"] > 2 || $COMMUNITY_ADMIN)) {
                            echo "			<tr><td colspan=\"2\">&nbsp;</td></tr>";
                            echo "			<tr>\n";
                            echo "				<td>Office Hours: </td>\n";
                            echo "				<td>".nl2br(html_encode($result["office_hours"]))."</td>\n";
                            echo "			</tr>\n\n";
                        }
                        echo "				</table>\n";
                        echo "			</div>\n";
                        echo "		</td>\n";
                        echo "		<td style=\"padding-top: 1.3em; vertical-align: top\">\n";

                        $query		= "	SELECT CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
                                        FROM `permissions` AS a
                                        LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                        ON b.`id` = a.`assigned_to`
                                        WHERE a.`assigned_by`=".$db->qstr($result["id"])."
                                        AND (a.`valid_from` = '0' OR a.`valid_from` <= ".$db->qstr(time()).") AND (a.`valid_until` = '0' OR a.`valid_until` > ".$db->qstr(time()).")
                                        ORDER BY `valid_until` ASC";
                        $assistants	= $db->GetAll($query);
                        if ($assistants) {
                            echo "		<span class=\"content-small\">Administrative Assistants:</span>\n";
                            echo "		<ul class=\"assistant-list\">";
                            foreach ($assistants as $assistant) {
                                echo "		<li><a href=\"mailto:".html_encode($assistant["email"])."\">".html_encode($assistant["fullname"])."</a></li>";
                            }
                            echo "		</ul>";
                        }
                        echo "		</td>\n";
                        echo "	</tr>\n";
                        echo "	</table>\n";
                        echo "</div>\n";
                    }
                }

                $query = "	SELECT b.*, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, c.`account_active`, c.`access_starts`, c.`access_expires`, c.`last_login`, c.`role`, c.`group`
                            FROM `course_contacts` AS a
                            JOIN `".AUTH_DATABASE."`.`user_data` AS b
                            ON b.`id` = a.`proxy_id`
                            JOIN `".AUTH_DATABASE."`.`user_access` AS c
                            ON c.`user_id` = b.`id`
                            AND c.`app_id` IN (".AUTH_APP_IDS_STRING.")
                            JOIN `courses` AS d
                            ON a.`course_id` = d.`course_id`
                            AND d.`course_active` = 1
                            WHERE a.`course_id` IN (".implode(", ", $course_ids).")
                            AND a.`contact_type` = 'ccoordinator'
                            GROUP BY b.`id`
                            ORDER BY `contact_order` ASC";
                $results = $db->GetAll($query);
                if ($results) {
                    echo "<h2>" . (count($results) > 1 ? $translate->_("Curriculum Coordinators") : $translate->_("Curriculum Coordinator")) . "</h2>\n";
                    foreach ($results as $key => $result) {
                        echo "<div id=\"result-".$result["id"]."\" style=\"width: 100%; padding: 5px 0px 5px 5px; line-height: 16px; text-align: left;\">\n";
                        echo "	<table style=\"width: 100%;\" class=\"profile-card\">\n";
                        echo "	<colgroup>\n";
                        echo "		<col style=\"width: 15%\" />\n";
                        echo "		<col style=\"width: 25%\" />\n";
                        echo "		<col style=\"width: 38%\" />\n";
                        echo "		<col style=\"width: 22%\" />\n";
                        echo "	<colgroup>";
                        echo "	<tr>";
                        echo "		<td style=\"vertical-align: top;\">";
                        echo "			<div id=\"img-holder-".$result["id"]."\" class=\"img-holder\">\n";

                        $offical_file_active	= false;
                        $uploaded_file_active	= false;

                        /**
                         * If the photo file actually exists, and either
                         * 	If the user is in an administration group, or
                         *  If the user is trying to view their own photo, or
                         *  If the proxy_id has their privacy set to "Any Information"
                         */
                        if ((@file_exists(STORAGE_USER_PHOTOS."/".$result["id"]."-official")) && $ENTRADA_ACL && ($ENTRADA_ACL->amIAllowed(new PhotoResource($result["id"], (int) $result["privacy_level"], "official"), "read"))) {
                            $offical_file_active	= true;
                        }

                        /**
                         * If the photo file actually exists, and
                         * If the uploaded file is active in the user_photos table, and
                         * If the proxy_id has their privacy set to "Basic Information" or higher.
                         */
                        $query			= "SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = '1' AND `photo_active` = '1' AND `proxy_id` = ".$db->qstr($result["id"]);
                        $photo_active	= $db->GetOne($query);
                        if ((@file_exists(STORAGE_USER_PHOTOS."/".$result["id"]."-upload")) && $photo_active && $ENTRADA_ACL && ($ENTRADA_ACL->amIAllowed(new PhotoResource($result["id"], (int) $result["privacy_level"], "upload"), "read"))) {
                            $uploaded_file_active = true;
                        }

                        if ($uploaded_file_active) {
                            echo "		<img id=\"uploaded_photo_".$result["id"]."\" class=\"uploaded\" src=\"".webservice_url("photo", array($result["id"], "upload"))."\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" title=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" />\n";
                        } elseif ($offical_file_active) {
                            echo "		<img id=\"official_photo_".$result["id"]."\" class=\"official\" src=\"".webservice_url("photo", array($result["id"], "official"))."\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" title=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" />\n";
                        }

                        if ((!$offical_file_active) && (!$uploaded_file_active)) {
                            echo "		<img src=\"".ENTRADA_URL."/images/headshot-male.gif\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"No Photo Available\" title=\"No Photo Available\" />\n";
                        }

                        echo "			</div>\n";
                        echo "		</td>\n";
                        echo "		<td style=\"font-size: 14px; color: #003366; vertical-align: top\">";
                        echo "			<div style=\"font-weight: bold;\">".html_encode((($result["prefix"]) ? $result["prefix"]." " : "").$result["firstname"]." ".$result["lastname"])."</div>";
                        echo "			<div class=\"content-small\" style=\"margin-bottom: 15px\">".ucwords($result["group"])." > ".($result["group"] == "student" ? "Class of " : "").ucwords($result["role"])."</div>\n";
                        if ($result["privacy_level"] > 1 || $COMMUNITY_ADMIN) {
                            echo "			<a href=\"mailto:".html_encode($result["email"])."\">".html_encode($result["email"])."</a><br />\n";

                            if ($result["email_alt"]) {
                                echo "		<a href=\"mailto:".html_encode($result["email_alt"])."\">".html_encode($result["email_alt"])."</a>\n";
                            }
                        }
                        echo "		</td>\n";
                        echo "		<td style=\"padding-top: 1.3em;\">\n";
                        echo "			<div>\n";
                        echo "				<table class=\"address-info\" style=\"width: 100%;\">\n";
                        if ($result["telephone"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr>\n";
                            echo "				<td style=\"width: 30%;\">Telephone: </td>\n";
                            echo "				<td>".html_encode($result["telephone"])."</td>\n";
                            echo "			</tr>\n";
                        }
                        if ($result["fax"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr>\n";
                            echo "				<td>Fax: </td>\n";
                            echo "				<td>".html_encode($result["fax"])."</td>\n";
                            echo "			</tr>\n\n";
                        }
                        if ($result["address"] && $result["city"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr>\n";
                            echo "				<td><br />Address: </td>\n";
                            echo "				<td><br />".html_encode($result["address"])."</td>\n";
                            echo "			</tr>\n";
                            echo "			<tr>\n";
                            echo "				<td>&nbsp;</td>\n";
                            echo "				<td>".html_encode($result["city"].($result["city"] && $result["province"] ? ", ".$result["province"] : ""))."</td>\n";
                            echo "			</tr>\n";
                            echo "			<tr>\n";
                            echo "				<td>&nbsp;</td>\n";
                            echo "				<td>".html_encode($result["country"].($result["country"] && $result["postcode"] ? ", ".$result["postcode"] : ""))."</td>\n";
                            echo "			</tr>\n";
                        }
                        if ($result["office_hours"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr><td colspan=\"2\">&nbsp;</td></tr>";
                            echo "			<tr>\n";
                            echo "				<td>Office Hours: </td>\n";
                            echo "				<td>".nl2br(html_encode($result["office_hours"]))."</td>\n";
                            echo "			</tr>\n\n";
                        }
                        echo "				</table>\n";
                        echo "			</div>\n";
                        echo "		</td>\n";
                        echo "		<td style=\"padding-top: 1.3em; vertical-align: top\">\n";

                        $query		= "	SELECT CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
                                        FROM `permissions` AS a
                                        LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                        ON b.`id` = a.`assigned_to`
                                        WHERE a.`assigned_by`=".$db->qstr($result["id"])."
                                        AND (a.`valid_from` = '0' OR a.`valid_from` <= ".$db->qstr(time()).") AND (a.`valid_until` = '0' OR a.`valid_until` > ".$db->qstr(time()).")
                                        ORDER BY `valid_until` ASC";
                        $assistants	= $db->GetAll($query);
                        if ($assistants) {
                            echo "		<span class=\"content-small\">Administrative Assistants:</span>\n";
                            echo "		<ul class=\"assistant-list\">";
                            foreach ($assistants as $assistant) {
                                echo "		<li><a href=\"mailto:".html_encode($assistant["email"])."\">".html_encode($assistant["fullname"])."</a></li>";
                            }
                            echo "		</ul>";
                        }
                        echo "		</td>\n";
                        echo "	</tr>\n";
                        echo "	</table>\n";
                        echo "</div>\n";
                    }
                }

                $query = "	SELECT b.*, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, c.`account_active`, c.`access_starts`, c.`access_expires`, c.`last_login`, c.`role`, c.`group`
                            FROM `course_contacts` AS a
                            JOIN `".AUTH_DATABASE."`.`user_data` AS b
                            ON b.`id` = a.`proxy_id`
                            JOIN `".AUTH_DATABASE."`.`user_access` AS c
                            ON c.`user_id` = b.`id`
                            AND c.`app_id` IN (".AUTH_APP_IDS_STRING.")
                            JOIN `courses` AS d
                            ON a.`course_id` = d.`course_id`
                            AND d.`course_active` = 1
                            WHERE a.`course_id` IN (".implode(", ", $course_ids).")
                            AND a.`contact_type` = 'associated_faculty'
                            GROUP BY b.`id`
                            ORDER BY `contact_order` ASC";
                $results = $db->GetAll($query);
                if ($results) {
                    echo "<h2>" . $translate->_("Faculty") . "</h2>\n";
                    foreach ($results as $key => $result) {
                        echo "<div id=\"result-".$result["id"]."\" style=\"width: 100%; padding: 5px 0px 5px 5px; line-height: 16px; text-align: left;\">\n";
                        echo "	<table style=\"width: 100%;\" class=\"profile-card\">\n";
                        echo "	<colgroup>\n";
                        echo "		<col style=\"width: 15%\" />\n";
                        echo "		<col style=\"width: 25%\" />\n";
                        echo "		<col style=\"width: 38%\" />\n";
                        echo "		<col style=\"width: 22%\" />\n";
                        echo "	<colgroup>";
                        echo "	<tr>";
                        echo "		<td style=\"vertical-align: top;\">";
                        echo "			<div id=\"img-holder-".$result["id"]."\" class=\"img-holder\">\n";

                        $offical_file_active	= false;
                        $uploaded_file_active	= false;

                        /**
                         * If the photo file actually exists, and either
                         * 	If the user is in an administration group, or
                         *  If the user is trying to view their own photo, or
                         *  If the proxy_id has their privacy set to "Any Information"
                         */
                        if ((@file_exists(STORAGE_USER_PHOTOS."/".$result["id"]."-official")) && $ENTRADA_ACL && ($ENTRADA_ACL->amIAllowed(new PhotoResource($result["id"], (int) $result["privacy_level"], "official"), "read"))) {
                            $offical_file_active	= true;
                        }

                        /**
                         * If the photo file actually exists, and
                         * If the uploaded file is active in the user_photos table, and
                         * If the proxy_id has their privacy set to "Basic Information" or higher.
                         */
                        $query			= "SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = '1' AND `photo_active` = '1' AND `proxy_id` = ".$db->qstr($result["id"]);
                        $photo_active	= $db->GetOne($query);
                        if ((@file_exists(STORAGE_USER_PHOTOS."/".$result["id"]."-upload")) && $photo_active && $ENTRADA_ACL && ($ENTRADA_ACL->amIAllowed(new PhotoResource($result["id"], (int) $result["privacy_level"], "upload"), "read"))) {
                            $uploaded_file_active = true;
                        }

                        if ($uploaded_file_active) {
                            echo "		<img id=\"uploaded_photo_".$result["id"]."\" class=\"uploaded\" src=\"".webservice_url("photo", array($result["id"], "upload"))."\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" title=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" />\n";
                        } elseif ($offical_file_active) {
                            echo "		<img id=\"official_photo_".$result["id"]."\" class=\"official\" src=\"".webservice_url("photo", array($result["id"], "official"))."\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" title=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" />\n";
                        }

                        if ((!$offical_file_active) && (!$uploaded_file_active)) {
                            echo "		<img src=\"".ENTRADA_URL."/images/headshot-male.gif\" width=\"72\" height=\"72\" style=\"width: 72px; height: 72px;\" alt=\"No Photo Available\" title=\"No Photo Available\" />\n";
                        }

                        echo "			</div>\n";
                        echo "		</td>\n";
                        echo "		<td style=\"font-size: 14px; color: #003366; vertical-align: top\">";
                        echo "			<div style=\"font-weight: bold;\">".html_encode((($result["prefix"]) ? $result["prefix"]." " : "").$result["firstname"]." ".$result["lastname"])."</div>";
                        echo "			<div class=\"content-small\" style=\"margin-bottom: 15px\">".ucwords($result["group"])." > ".($result["group"] == "student" ? "Class of " : "").ucwords($result["role"])."</div>\n";
                        if ($result["privacy_level"] > 1 || $COMMUNITY_ADMIN) {
                            echo "			<a href=\"mailto:".html_encode($result["email"])."\">".html_encode($result["email"])."</a><br />\n";

                            if ($result["email_alt"]) {
                                echo "		<a href=\"mailto:".html_encode($result["email_alt"])."\">".html_encode($result["email_alt"])."</a>\n";
                            }
                        }
                        echo "		</td>\n";
                        echo "		<td style=\"padding-top: 1.3em;\">\n";
                        echo "			<div>\n";
                        echo "				<table class=\"address-info\" style=\"width: 100%;\">\n";
                        if ($result["telephone"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr>\n";
                            echo "				<td style=\"width: 30%;\">Telephone: </td>\n";
                            echo "				<td>".html_encode($result["telephone"])."</td>\n";
                            echo "			</tr>\n";
                        }
                        if ($result["fax"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr>\n";
                            echo "				<td>Fax: </td>\n";
                            echo "				<td>".html_encode($result["fax"])."</td>\n";
                            echo "			</tr>\n\n";
                        }
                        if ($result["address"] && $result["city"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr>\n";
                            echo "				<td><br />Address: </td>\n";
                            echo "				<td><br />".html_encode($result["address"])."</td>\n";
                            echo "			</tr>\n";
                            echo "			<tr>\n";
                            echo "				<td>&nbsp;</td>\n";
                            echo "				<td>".html_encode($result["city"].($result["city"] && $result["province"] ? ", ".$result["province"] : ""))."</td>\n";
                            echo "			</tr>\n";
                            echo "			<tr>\n";
                            echo "				<td>&nbsp;</td>\n";
                            echo "				<td>".html_encode($result["country"].($result["country"] && $result["postcode"] ? ", ".$result["postcode"] : ""))."</td>\n";
                            echo "			</tr>\n";
                        }
                        if ($result["office_hours"] && ($result["privacy_level"] > 2 || (isset($COMMUNITY_ADMIN) && $COMMUNITY_ADMIN))) {
                            echo "			<tr><td colspan=\"2\">&nbsp;</td></tr>";
                            echo "			<tr>\n";
                            echo "				<td>Office Hours: </td>\n";
                            echo "				<td>".nl2br(html_encode($result["office_hours"]))."</td>\n";
                            echo "			</tr>\n\n";
                        }
                        echo "				</table>\n";
                        echo "			</div>\n";
                        echo "		</td>\n";
                        echo "		<td style=\"padding-top: 1.3em; vertical-align: top\">\n";

                        $query		= "	SELECT CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
                                        FROM `permissions` AS a
                                        LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                        ON b.`id` = a.`assigned_to`
                                        WHERE a.`assigned_by`=".$db->qstr($result["id"])."
                                        AND (a.`valid_from` = '0' OR a.`valid_from` <= ".$db->qstr(time()).") AND (a.`valid_until` = '0' OR a.`valid_until` > ".$db->qstr(time()).")
                                        ORDER BY `valid_until` ASC";
                        $assistants	= $db->GetAll($query);
                        if ($assistants) {
                            echo "		<span class=\"content-small\">Administrative Assistants:</span>\n";
                            echo "		<ul class=\"assistant-list\">";
                            foreach ($assistants as $assistant) {
                                echo "		<li><a href=\"mailto:".html_encode($assistant["email"])."\">".html_encode($assistant["fullname"])."</a></li>";
                            }
                            echo "		</ul>";
                        }
                        echo "		</td>\n";
                        echo "	</tr>\n";
                        echo "	</table>\n";
                        echo "</div>\n";
                    }
                }

               /**
                * If the history is enabled, display the course history on the home page.
                */
                $query			= "	SELECT * FROM `community_page_options`
                                    WHERE `option_title` = 'show_history'
                                    AND `community_id` = ".$db->qstr($COMMUNITY_ID)."
                                    AND `option_value` = '1'";
                $history_enabled	= $db->GetRow($query);
                if ($history_enabled) {
                    /**
                     * Fetch all community events and put the HTML output in a variable.
                     */
                    $query		= "	SELECT *
                                    FROM `community_history`
                                    WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
                                    AND `history_display` = '1'
                                    ORDER BY `history_timestamp` DESC
                                    LIMIT 0, 15";
                    $results	= $db->CacheGetAll(CACHE_TIMEOUT, $query);
                    if($results) {
                        $history_messages = "";
                        echo "<ul class=\"history\">";
                        foreach($results as $key => $result) {
                            if ((int)$result["cpage_id"] && ($result["history_key"] != "community_history_activate_module")) {
                                $query = "SELECT `page_url` FROM `community_pages` WHERE `cpage_id` = ".$db->qstr($result["cpage_id"])." AND `community_id` = ".$db->qstr($result["community_id"]);
                                $page_url = $db->GetOne($query);
                            } elseif ($result["history_key"] == "community_history_activate_module") {
                                $query = "SELECT a.`page_url` FROM `community_pages` as a JOIN `communities_modules` as b ON b.`module_shortname` = a.`page_type` WHERE b.`module_id` = ".$db->qstr($result["record_id"])." AND a.`community_id` = ".$db->qstr($result["community_id"])." AND a.`page_active` = '1'";
                                $page_url = $db->GetOne($query);
                            }

                            if ($result["history_key"]) {
                                $history_message = $translate->_($result["history_key"]);
                                $record_title = "";
                                $parent_id = (int)$result["record_parent"];
                                community_history_record_title($result["history_key"], $result["record_id"], $result["cpage_id"], $result["community_id"], $result["proxy_id"]);

                            } else {
                                $history_message = $result["history_message"];
                            }

                            $content_search						= array("%SITE_COMMUNITY_URL%", "%SYS_PROFILE_URL%", "%PAGE_URL%", "%RECORD_ID%", "%RECORD_TITLE%", "%PARENT_ID%", "%PROXY_ID%");
                            $content_replace					= array(COMMUNITY_URL.$COMMUNITY_URL, ENTRADA_URL."/people", $page_url, $result["record_id"], $record_title, $parent_id, $result["proxy_id"]);
                            $history_message			= str_replace($content_search, $content_replace, $history_message);
                            $history_messages .= "<li".(!($key % 2) ? " style=\"background-color: #F4F4F4\"" : "").">".strip_tags($history_message, "<a>")."</li>";
                        }
                        $history_messages .= "</ul>";
                    }
                    if ($history_messages) {
                    ?>
                        <div style="position: relative; clear: both">
                            <div style="width: 100%">
                                <h2>Recent Updates</h2>
                                <?php
                                echo $history_messages;
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                }
            break;
            case strpos($PAGE_URL, "course_calendar") !== false :
                $HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
                $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js\"></script>";
                $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js\"></script>";
                $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_timestamp.js\"></script>";

                /**
                 * Update requested length of time to display.
                 * Valid: day, week, month, year
                 */
                if(isset($_GET["dtype"])) {
                    if(in_array(trim($_GET["dtype"]), array("day", "week", "month", "year"))) {
                        $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["dtype"] = trim($_GET["dtype"]);
                    }

                    $_SERVER["QUERY_STRING"] = replace_query(array("dtype" => false));
                } else {
                    if(!isset($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["dtype"])) {
                        $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["dtype"] = "week";
                    }
                }

                /**
                 * Update requested timestamp to display.
                 * Valid: Unix timestamp
                 */
                if(isset($_GET["dstamp"])) {
                    $integer = (int) trim($_GET["dstamp"]);
                    if($integer) {
                        $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"] = $integer;
                    }

                    $_SERVER["QUERY_STRING"] = replace_query(array("dstamp" => false));
                } else {
                    if(!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])) {
                        $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"] = time();
                    }
                }

                /**
                 * Update requested column to sort by.
                 * Valid: date, teacher, title, phase
                 */
                if(isset($_GET["sb"])) {
                    if(in_array(trim($_GET["sb"]), array("date", "teacher", "title"))) {
                        $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["sb"]	= trim($_GET["sb"]);
                    }

                    $_SERVER["QUERY_STRING"] = replace_query(array("sb" => false));
                } else {
                    if(!isset($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["sb"])) {
                        $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["sb"] = "date";
                    }
                }

                /**
                 * Update requested order to sort by.
                 * Valid: asc, desc
                 */
                if(isset($_GET["so"])) {
                    $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

                    $_SERVER["QUERY_STRING"] = replace_query(array("so" => false));
                } else {
                    if(!isset($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"])) {
                        $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"] = "asc";
                    }
                }

                /**
                 * Update requsted number of rows per page.
                 * Valid: any integer really.
                 */
                if((isset($_GET["pp"])) && ((int) trim($_GET["pp"]))) {
                    $integer = (int) trim($_GET["pp"]);

                    if(($integer > 0) && ($integer <= 250)) {
                        $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["pp"] = $integer;
                    }

                    $_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
                } else {
                    if(!isset($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["pp"])) {
                        $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["pp"] = DEFAULT_ROWS_PER_PAGE;
                    }
                }

                /**
                 * This fetches the unix timestamps from the first and last second of the day, week, month, year, etc.
                 */
                $DISPLAY_DURATION = fetch_timestamps($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["dtype"], $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]);

                /**
                 * Get the total number of results using the generated queries above and calculate the total number
                 * of pages that are available based on the results per page preferences.
                 */
                $query 		= "	SELECT COUNT(`events`.`event_id`) AS `total_rows`
                                FROM `events`
                                WHERE ".(($DISPLAY_DURATION) ? "`events`.`event_start` BETWEEN ".$db->qstr($DISPLAY_DURATION["start"])." AND ".$db->qstr($DISPLAY_DURATION["end"])."
                                AND " : "")." `events`.`course_id` IN (".implode(", ", $course_ids).")";
                $result = $db->GetRow($query);
                if($result) {
                    $TOTAL_ROWS	= $result["total_rows"];

                    if($TOTAL_ROWS <= $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["pp"]) {
                        $TOTAL_PAGES = 1;
                    } elseif (($TOTAL_ROWS % $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["pp"]) == 0) {
                        $TOTAL_PAGES = (int) ($TOTAL_ROWS / $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["pp"]);
                    } else {
                        $TOTAL_PAGES = (int) ($TOTAL_ROWS / $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["pp"]) + 1;
                    }
                } else {
                    $TOTAL_ROWS		= 0;
                    $TOTAL_PAGES	= 1;
                }

                /**
                 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
                 */
                if(isset($_GET["pv"])) {
                    $PAGE_CURRENT = (int) trim($_GET["pv"]);

                    if(($PAGE_CURRENT < 1) || ($PAGE_CURRENT > $TOTAL_PAGES)) {
                        $PAGE_CURRENT = 1;
                    }
                } else {
                    $PAGE_CURRENT = 1;
                }

                $PAGE_PREVIOUS	= (($PAGE_CURRENT > 1) ? ($PAGE_CURRENT - 1) : false);
                $PAGE_NEXT		= (($PAGE_CURRENT < $TOTAL_PAGES) ? ($PAGE_CURRENT + 1) : false);

                /**
                 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
                 */
                $limit_parameter = (int) (($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["pp"] * $PAGE_CURRENT) - $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["pp"]);
                foreach ($course_ids as $course_id) {
                    $filters["course"][] = (int) trim($course_id, '\'');
                }

                $_SESSION[APPLICATION_IDENTIFIER]["community_page"][$COMMUNITY_ID]["filters"] = $filters;

                $learning_events = events_fetch_filtered_events(
                    $ENTRADA_USER->getActiveId(),
                    $ENTRADA_USER->getActiveGroup(),
                    $ENTRADA_USER->getActiveRole(),
                    $ENTRADA_USER->getActiveOrganisation(),
                    $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["sb"],
                    $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"],
                    $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["dtype"],
                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"],
                    0,
                    $filters,
                    true,
                    (isset($_GET["pv"]) ? (int) trim($_GET["pv"]) : 1),
                    $_SESSION[APPLICATION_IDENTIFIER]["community_page"]["pp"],
                    $COMMUNITY_ID);
                ?>
                <script type="text/javascript">
                    function setDateValue(field, date) {
                        timestamp = getMSFromDate(date);
                        if(field.value != timestamp) {
                            window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?".(($_SERVER["QUERY_STRING"] != "") ? replace_query(array("dstamp" => false))."&" : ""); ?>dstamp='+timestamp;
                        }
                        return;
                    }
                </script>

                <?php

                /**
                 * Output the calendar controls.
                 */
                events_output_calendar_controls("community", (COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL));

                /**
                 * Provide the queries with the columns to order by.
                 */
                switch($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["sb"]) {
                    case "teacher" :
                        $SORT_BY	= "`fullname` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"]).", `events`.`event_start` ASC";
                    break;
                    case "title" :
                        $SORT_BY	= "`events`.`event_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"]).", `events`.`event_start` ASC";
                    break;
                    case "phase" :
                        $SORT_BY	= "`events`.`event_phase` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"]).", `events`.`event_start` ASC";
                    break;
                    case "date" :
                    default :
                        $SORT_BY	= "`events`.`event_start` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"]);
                    break;
                }

                if ($learning_events["events"]) {
                    ?>
                    <div class="tableListTop">
                        <img src="<?php echo ENTRADA_URL; ?>/images/lecture-info.gif" width="15" height="15" alt="" title="" style="vertical-align: middle" />
                        <?php
                        switch($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["dtype"]) {
                            case "day" :
                                echo "Found ".$TOTAL_ROWS." event".(($TOTAL_ROWS != 1) ? "s" : "")." that take place on <strong>".date("D, M jS, Y", $DISPLAY_DURATION["start"])."</strong>.\n";
                            break;
                            case "month" :
                                echo "Found ".$TOTAL_ROWS." event".(($TOTAL_ROWS != 1) ? "s" : "")." that take place during <strong>".date("F", $DISPLAY_DURATION["start"])."</strong> of <strong>".date("Y", $DISPLAY_DURATION["start"])."</strong>.\n";
                            break;
                            case "year" :
                                echo "Found ".$TOTAL_ROWS." event".(($TOTAL_ROWS != 1) ? "s" : "")." that take place during <strong>".date("Y", $DISPLAY_DURATION["start"])."</strong>.\n";
                            break;
                            default :
                            case "week" :
                                echo "Found ".$TOTAL_ROWS." event".(($TOTAL_ROWS != 1) ? "s" : "")." from <strong>".date("D, M jS, Y", $DISPLAY_DURATION["start"])."</strong> to <strong>".date("D, M jS, Y", $DISPLAY_DURATION["end"])."</strong>.\n";
                            break;
                        }
                        ?>
                    </div>
                    <table class="tableList" cellspacing="0" summary="List of Events">
                    <colgroup>
                        <col class="modified" />
                        <col class="date" />
                        <col class="teacher" />
                        <col class="title" />
                        <col class="attachment" />
                    </colgroup>
                    <thead>
                        <tr>
                            <td class="modified" id="colModified">&nbsp;</td>
                            <td class="date<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["sb"] == "date") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"]) : ""); ?>" id="colDate"><?php echo community_public_order_link("date", "Date &amp; Time", ENTRADA_URL."/community".$COMMUNITY_URL.":".$PAGE_URL); ?></td>
                            <td class="teacher<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["sb"] == "teacher") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"]) : ""); ?>" id="colTeacher"><?php echo community_public_order_link("teacher", "Teacher", ENTRADA_URL."/community".$COMMUNITY_URL.":".$PAGE_URL); ?></td>
                            <td class="title<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["sb"] == "title") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"]) : ""); ?>" id="colTitle"><?php echo community_public_order_link("title", "Event Title", ENTRADA_URL."/community".$COMMUNITY_URL.":".$PAGE_URL); ?></td>
                            <td class="attachment" id="colAttachment">&nbsp;</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rid		= $limit_parameter;

                        $count_modified		= 0;
                        $count_cohort		= 0;
                        $count_group		= 0;
                        $count_individual	= 0;

                        foreach ($learning_events["events"] as $result) {
                            if (((!$result["release_date"]) || ($result["release_date"] <= time())) && ((!$result["release_until"]) || ($result["release_until"] >= time()))) {
                                $attachments	= attachment_check($result["event_id"]);
                                $url			= ENTRADA_URL."/events?rid=".$result["event_id"]."&community=".$COMMUNITY_ID;
                                $is_modified	= false;

                                /**
                                 * Determine if this event has been modified since their last visit.
                                 */
                                if(((int) $result["last_visited"]) && ((int) $result["last_visited"] < (int) $result["updated_date"])) {
                                    $is_modified = true;
                                    $count_modified++;
                                }

                                /**
                                 * Increment the appropriate audience_type counter.
                                 */
                                switch($result["audience_type"]) {
                                    case "cohort" :
                                        $count_cohort++;
                                    break;
                                    case "group_id" :
                                        $count_group++;
                                    break;
                                    case "proxy_id" :
                                        $count_individual++;
                                    break;
                                    default :
                                        continue;
                                    break;
                                }

                                echo "<tr id=\"event-".$result["event_id"]."\" class=\"event".(($is_modified) ? " modified" : (($result["audience_type"] == "proxy_id") ? " individual" : ""))."\">\n";
                                echo "	<td class=\"modified\">";
                                        if($is_modified) {
                                            echo "<img src=\"".ENTRADA_URL."/images/event-modified.gif\" width=\"16\" height=\"16\" alt=\"This event has been modified since your last visit on ".date(DEFAULT_DATE_FORMAT, $result["last_visited"]).".\" title=\"This event has been modified since your last visit on ".date(DEFAULT_DATE_FORMAT, $result["last_visited"]).".\" style=\"vertical-align: middle\" />";
                                        } elseif($result["audience_type"] == "proxy_id") {
                                            echo "<img src=\"".ENTRADA_URL."/images/event-individual.gif\" width=\"16\" height=\"16\" alt=\"Individual Event\" title=\"Individual Event\" style=\"vertical-align: middle\" />";
                                        } else {
                                            echo "<img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"16\" height=\"16\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />";
                                        }
                                echo "	</td>\n";
                                echo "	<td class=\"date\"><a href=\"".$url."\" title=\"Event Date\">".date(DEFAULT_DATE_FORMAT, $result["event_start"])."</a></td>\n";
                                echo "	<td class=\"teacher\"><a href=\"".$url."\" title=\"Primary Teacher: ".html_encode($result["fullname"])."\">".html_encode($result["fullname"])."</a></td>\n";
                                echo "	<td class=\"title\"><a href=\"".$url."\" title=\"Event Title: ".html_encode($result["event_title"])."\">".html_encode($result["event_title"])."</a></td>\n";
                                echo "	<td class=\"attachment\">".(($attachments) ? "<img src=\"".ENTRADA_URL."/images/attachment.gif\" width=\"16\" height=\"16\" alt=\"Contains ".$attachments." attachment".(($attachments != 1) ? "s" : "")."\" title=\"Contains ".$attachments." attachment".(($attachments != 1) ? "s" : "")."\" />" : "<img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"16\" height=\"16\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />")."</td>\n";
                                echo "</tr>\n";
                            }

                            $rid++;
                        }
                        ?>
                    </tbody>
                    </table>
                    <?php
                    if($count_modified) {
                        if($count_modified != 1) {
                            $sidebar_html = "There are ".$count_modified." teaching events on this page which were updated since you last looked at them.";
                        } else {
                            $sidebar_html = "There is ".$count_modified." teaching event on this page which has been updated since you last looked at it.";
                        }
                        $sidebar_html .= " Eg. <img src=\"".ENTRADA_URL."/images/highlighted-example.gif\" width=\"67\" height=\"14\" alt=\"Updated events are denoted like.\" title=\"Updated events are denoted like.\" style=\"vertical-align: middle\" />";

                        new_sidebar_item("Recently Modified", $sidebar_html, "modified-event", "open");
                    }
                } else {
                    $filters_applied = (((isset($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["filters"])) && ($filters_total = @count($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["filters"]))) ? true : false);
                    ?>
                    <div class="display-notice">
                        <h3>No Matching Events</h3>
                        There are no learning events scheduled
                        <?php
                        switch($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["dtype"]) {
                            case "day" :
                                echo "that take place on <strong>".date(DEFAULT_DATE_FORMAT, $DISPLAY_DURATION["start"])."</strong>";
                            break;
                            case "month" :
                                echo "that take place during <strong>".date("F", $DISPLAY_DURATION["start"])."</strong> of <strong>".date("Y", $DISPLAY_DURATION["start"])."</strong>";
                            break;
                            case "year" :
                                echo "that take place during <strong>".date("Y", $DISPLAY_DURATION["start"])."</strong>";
                            break;
                            default :
                            case "week" :
                                echo "from <strong>".date(DEFAULT_DATE_FORMAT, $DISPLAY_DURATION["start"])."</strong> to <strong>".date(DEFAULT_DATE_FORMAT, $DISPLAY_DURATION["end"])."</strong>";
                            break;
                        }
                        echo (($filters_applied) ? " that also match the supplied &quot;Show Only&quot; restrictions" : "") ?>.
                        <br /><br />
                        If this is unexpected there are a few things that you can check:
                        <ol>
                            <li style="padding: 3px">Make sure that you are browsing the intended time period. For example, if you trying to browse <?php echo date("F", time()); ?> of <?php echo date("Y", time()); ?>, make sure that the results bar above says &quot;... takes place in <strong><?php echo date("F", time()); ?></strong> of <strong><?php echo date("Y", time()); ?></strong>&quot;.</li>
                            <?php
                            if($filters_applied) {
                                echo "<li style=\"padding: 3px\">You also have ".$filters_total." filter".(($filters_total != 1) ? "s" : "")." applied to the event list. you may wish to remove ".(($filters_total != 1) ? "one or more of these" : "it")." by clicking the link in the &quot;Showing Events That Include&quot; box above.</li>";
                            }
                            ?>
                        </ol>
                    </div>
                    <?php
                }

                echo "<form action=\"\" method=\"get\">\n";
                echo "<input type=\"hidden\" id=\"dstamp\" name=\"dstamp\" value=\"".html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])."\" />\n";
                echo "</form>\n";
            break;
            case (preg_match("/objectives$/", $PAGE_URL) != 0) :
                $course_ids_str = "";
                $clean_ids_str = "";
                $course_ids = array();

                $query = "	SELECT a.`course_id`
                            FROM `community_courses` AS a
                            JOIN `courses` AS b
                            ON a.`course_id` = b.`course_id`
                            WHERE b.`course_active` = '1'
                            AND a.`community_id` = ".$db->qstr($COMMUNITY_ID);
                $results = $db->GetAll($query);
                if ($results) {
                    foreach ($results as $course_id) {
                        $course_ids[] = $course_id["course_id"];
                        if ($course_ids_str) {
                            $course_ids_str .= ", ".$db->qstr($course_id["course_id"]);
                            $clean_ids_str .= ",".$course_id["course_id"];
                        } else {
                            $course_ids_str = $db->qstr($course_id["course_id"]);
                            $clean_ids_str = ",".$course_id["course_id"];
                        }
                    }
                }

                $show_objectives = false;
                list($objectives,$top_level_id) = courses_fetch_objectives($ENTRADA_USER->getActiveOrganisation(),$course_ids,-1, 1, false);
                ?>
                <script type="text/javascript">
                function renewList (hierarchy) {
                    if (hierarchy != null && hierarchy) {
                        hierarchy = 1;
                    } else {
                        hierarchy = 0;
                    }
                    new Ajax.Updater('objectives_list', '<?php echo ENTRADA_URL; ?>/api/objectives.api.php',
                        {
                            method:	'post',
                            parameters: 'course_ids=<?php echo $clean_ids_str ?>&hierarchy='+hierarchy
                        }
                    );
                }
                </script>
                <?php
                echo "<strong>The learner will be able to:</strong>";
                echo "<div id=\"objectives_list\">\n".course_objectives_in_list($objectives, $top_level_id,$top_level_id, false, false, 1, false)."\n</div>\n";
            break;
            case (strpos($PAGE_URL, "mcc_presentations") !== false) :
                $query = "	SELECT b.*
                            FROM `course_objectives` AS a
                            JOIN `global_lu_objectives` AS b
                            ON a.`objective_id` = b.`objective_id`
                            JOIN `objective_organisation` AS c
                            ON b.`objective_id` = c.`objective_id`
                            WHERE a.`objective_type` = 'event'
                            AND a.`course_id` IN (".implode(", ", $course_ids).")
                            AND b.`objective_active` = 1
                            AND a.`active` = 1
                            AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                            GROUP BY b.`objective_id`
                            ORDER BY b.`objective_order`";
                $results = $db->GetAll($query);
                if ($results) {
                    echo "<ul class=\"objectives\">\n";
                    foreach ($results as $result) {
                        if ($result["objective_name"]) {
                            echo "<li>".$result["objective_name"]."</li>\n";
                        }
                    }
                    echo "</ul>\n";
                }
            break;
            case (strpos($PAGE_URL,"course_assignments") !== false):
                ?>
                    <table class="tableList" cellspacing="0" summary="List of Assignments" id="assignment_list">
                        <?php
                        $query =  "	SELECT a.*, b.`course_code`
                                    FROM `assignments` AS a
                                    JOIN `courses` AS b
                                    ON a.`course_id` = b.`course_id`
                                    WHERE a.`course_id` IN (".implode(', ',$course_ids).")
                                    AND a.`release_date` < ".$db->qstr(time())."
                                    AND (
                                        a.`release_until` > ".$db->qstr(time())."
                                        OR a.`release_until` = 0
                                    )
                                    AND a.`assignment_active`  = 1";
                        $results = $db->GetAll($query);
                        if ($results) { ?>
                        <thead>
                            <tr>
                                <td width="20">&nbsp;</td>
                                <td colspan="3">Assignment Title</td>
                                <td colspan="2"><?php echo $translate->_("course"); ?> Code</td>
                                <td colspan="2">Due Date</td>
                            </tr>
                        </thead>
                        <?php } ?>
                        <tbody>
                            <?php
                            if($results){
                                foreach ($results as $result) {
                                    $url = ENTRADA_URL."/profile/gradebook/assignments?section=view&amp;id=".$result["assignment_id"];
                                    echo "<tr id=\"assignment-".$result["assignment_id"]."\">";
                                    echo "<td class=\"modified\" width=\"20\"><img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"19\" height=\"19\" alt=\"\" title=\"\" /></td>";
                                    echo "<td colspan=\"3\"><a href=\"$url\">".$result["assignment_title"]."</a></td>";
                                    echo "<td colspan=\"2\"><a href=\"$url\">".$result["course_code"]. "</a></td>";
                                    echo "<td colspan=\"2\"><a href=\"$url\">".($result["due_date"] == 0?"No Due Date":date(DEFAULT_DATE_FORMAT,$result["due_date"])). "</a></td>";
                                    echo "</tr>";
                                }
                            } else {
                                ?> <tr><td><?php add_notice('No Assignments have been created for this course.'); echo display_notice(); ?></td></tr><?php
                            }
                        ?>
                        </tbody>
                    </table>
                    <?php
            break;
            case (strpos($PAGE_URL,"assessment_strategies") !== false):

                if ($ENTRADA_USER->getGroup() == 'student') {
                    $student_sql = "AND a.`cohort` = ".$db->qstr($ENTRADA_USER->getCohort());
                } else {
                    $student_sql = "";
                }

                $query =  "	SELECT a.`cohort`, c.`group_name`, a.`assessment_id`, a.`name`, a.`type`, a.`grade_weighting`, b.`title` AS `characteristic`
                            FROM `assessments` AS a
                            JOIN `assessments_lu_meta` AS b
                            ON a.`characteristic_id` = b.`id`
                            JOIN `groups` AS c
                            ON a.`cohort` = c.`group_id`
                            WHERE `course_id` IN (".implode("', '", $course_ids).")
                            AND a.`active` = '1'".
                            $student_sql."
                            ORDER BY c.`group_id` DESC, a.`order` ASC, a.`type`";

                $assessments = $db->GetAll($query);
                $cohorts_threshold = Entrada_Settings::fetchValueByShortname("course_webpage_assessment_cohorts_count", $ENTRADA_USER->getActiveOrganisation());
                if ($assessments) {
                    echo "<h1>Assessments</h1>";
                    $group_count = 0;
                    foreach ($assessments as $assessment) {
                        if ($cohorts_threshold && !isset($output[$assessment["cohort"]])) {
                            if ($group_count >= $cohorts_threshold) {
                                break;
                            } else {
                                $group_count++;
                            }
                        }
                        $output[$assessment["cohort"]]["assessments"][$assessment["assessment_id"]]["name"] = $assessment["name"];
                        $output[$assessment["cohort"]]["assessments"][$assessment["assessment_id"]]["type"] = $assessment["type"];
                        $output[$assessment["cohort"]]["assessments"][$assessment["assessment_id"]]["characteristic"] = $assessment["characteristic"];
                        $output[$assessment["cohort"]]["assessments"][$assessment["assessment_id"]]["grade_weighting"] = $assessment["grade_weighting"];
                        $output[$assessment["cohort"]]["group_name"] = $assessment["group_name"];
                    }
                    foreach ($output as $course) {
                        echo "<h2>".$course["group_name"]."</h2>";
                        echo "<table width=\"100%\">\n";
                        echo "\t<thead>\n";
                        echo "\t\t<tr>\n";
                        echo "\t\t\t<th width=\"40%\" style=\"text-align:left;\">Assessment Title</th>\n";
                        echo "\t\t\t<th width=\"20%\" style=\"text-align:left;\">Type</th>\n";
                        echo "\t\t\t<th width=\"25%\" style=\"text-align:left;\">Characteristic</th>\n";
                        echo "\t\t\t<th width=\"15%\" style=\"text-align:left;\">Grade Weight</th>\n";
                        echo "\t\t</tr>\n";
                        echo "\t</thead>\n";
                        echo "\t<tbody>\n";
                        foreach ($course["assessments"] as $assessment) {
                            echo "\t<tr>\n";
                            echo "\t\t<td>".$assessment["name"]."</td>\n";
                            echo "\t\t<td>".$assessment["type"]."</td>\n";
                            echo "\t\t<td>".$assessment["characteristic"]."</td>\n";
                            echo "\t\t<td>".$assessment["grade_weighting"]."</td>\n";
                            echo "\t</tr>\n";
                        }
                        echo "\t<tbody>\n";
                        echo "</table>\n";
                    }

                }

            break;
            default :
                continue;
            break;
        }
    }
} else {
    echo display_notice(array("Please <a href=\"".ENTRADA_URL."/?url=".rawurlencode($PROCEED_TO)."\">log into ".APPLICATION_NAME."</a> to access more content on this page."));
}