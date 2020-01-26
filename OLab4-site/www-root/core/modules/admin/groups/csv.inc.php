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
 * Allows students to delete an elective in the system if it has not yet been approved.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('group', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/${MODULE}\\'', 15000)";

    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\"> %s </a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	
	ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));

    $request = ${"_" . $request_method};

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "import" :
                    if (!empty($_FILES)) {
                        if ($_FILES["file"]["error"] || !$_FILES["file"]["size"]) {
                            add_error($translate->_("There was a problem <strong>uploading</strong> file."));
                        } elseif(!in_array(mime_content_type($_FILES["file"]["tmp_name"]), array("text/csv", "text/plain", "application/vnd.ms-excel","text/comma-separated-values","application/csv", "application/excel", "application/vnd.ms-excel", "application/vnd.msexcel","application/octet-stream"))) {
                            add_error("Invalid <strong>file type</strong> uploaded. Must be a CSV file in the proper format.");
                        } else {
                            $PROCESSED["group_name"] = "";
                            $PROCESSED["group_type"] = "";
                            $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
                            $PROCESSED["entrada_only"] = 1;
                            $PROCESSED["created_date"] = time();
                            $PROCESSED["created_by"] = $ENTRADA_USER->getID();
                            $PROCESSED["updated_date"] = $PROCESSED["created_date"];
                            $PROCESSED["updated_by"] = $PROCESSED["created_by"];
                            $PROCESSED["group_id"] = 0;
                            $PROCESSED["group_active"] = 1;

                            $GROUP_IDS = array();

                            $fh = fopen($_FILES["file"]["tmp_name"], 'r');
                            $line = 0;
                            while (($data = fgetcsv($fh, 1000, ",")) !== FALSE) {
                                if ($line++ && !$ERROR) {
                                    if ($num != count($data)) {
                                        add_error($translate->_("The file appears as an <strong>inconsistent</strong> csv file: varying field number."));
                                    }
                                }
                                $num = count($data);
                            }
                            if ($line < 2) {
                                add_error($translate->_("The file has <strong>no data</strong> or only a header line."));
                            }
                            fclose($fh);
                            if (!$ERROR) {
                                $fh = fopen($_FILES["file"]["tmp_name"], 'r');
                                $line = $count = $group_count = 0;
                                while (($data = fgetcsv($fh, 1000, ",")) !== FALSE) {
                                    if (!$line++) {  // Skip header
                                        continue;
                                    }
                                    if (!strlen($data[0]) || ((!strlen($data[1]) || !strlen($data[2])) && !strlen($data[4]))) {
                                        continue;
                                    }

                                    if (strcmp($data[0], $PROCESSED["group_name"])) { // A new or different group
                                        //insert into groups
                                        $PROCESSED["group_name"] = $data[0];
                                        $result = Models_Group::fetchRowByName($PROCESSED["group_name"], $PROCESSED["organisation_id"]);
                                        if ($result) {
                                            $PROCESSED["group_id"] = $result;
                                        } else {
                                            unset($PROCESSED["group_id"]);
                                            $PROCESSED["group_type"] = "cohort";
                                            $group = new Models_Group($PROCESSED);
                                            if (!$group->insert()) {
                                                add_error(sprintf($translate->_("There was an error while trying to add the <strong>Group</strong> %s. <br /><br />The system administrator was informed of this error; please try again later."), $PROCESSED["group_name"]));
                                                break;
                                            } else {
                                                $entry = array("group_id" => $group->getID(), "organisation_id" => $PROCESSED["organisation_id"], "created_date" => $PROCESSED["created_date"], "created_by" => $PROCESSED["created_by"], "updated_date" => $PROCESSED["updated_date"], "updated_by" => $PROCESSED["updated_by"]);
                                                if ($db->AutoExecute("group_organisations", $entry, "INSERT")) {
                                                    add_success(sprintf($translate->_("You have successfully added new group[cohort] %s to the system."), $PROCESSED["group_name"]));
                                                    $PROCESSED["group_id"] = $group->getID();
                                                } else {
                                                    add_error(sprintf($translate->_("There was an error while trying to add the <strong>Group</strong> %s. <br /><br />The system administrator was informed of this error; please try again later."), $PROCESSED["group_name"]));
                                                    application_log("error", "Unable to insert a new group organisation for group_id [" . $this->group_id . "[. Database said: " . $db->ErrorMsg());
                                                }
                                            }
                                        }

                                        if (!in_array($PROCESSED["group_id"], $GROUP_IDS)) {
                                            $GROUP_IDS[] = $PROCESSED["group_id"];
                                            $group_count++;
                                        }
                                    }

                                    $PROCESSED["member_active"] = $data[3];

                                    $number = clean_input($data[4], ["nows", "striptags"]);

                                    if ($number) {
                                        $user = Models_User::fetchRowByNumber($number);
                                        if ($user) {
                                            $PROCESSED["proxy_id"] = $user->getID(); // Use given Entrada id
                                        } else {
                                            add_notice(sprintf($translate->_("Unable to find the student number provided on line [%d]"), $line));
                                            continue;
                                        }
                                    } else {
                                        add_notice(sprintf($translate->_("Learner Name [%s] on line [%d] did not have a University ID specified."), ($data[1] . " " . $data[2]), $line));
                                        continue;
                                    }

                                    if (($return = Models_Group_Member::addMember($PROCESSED)) === false) {
                                        add_error(sprintf($translate->_("There was an error while trying to add the <strong>Group Member</strong> %s into [%s].<br />The system administrator was informed of this error; please try again later."), $PROCESSED["proxy_id"], $PROCESSED["group_name"]));
                                        break 1;
                                    } elseif ($return) {
                                        add_success(sprintf($translate->_("You have successfully added '%s' to group [%s]."), Models_User::fetchRowByID($PROCESSED["proxy_id"])->getFullname(false), $PROCESSED["group_name"]));
                                        $count++;
                                    } else {
                                        add_notice(sprintf($translate->_("'%s' is already a member of the '%s' group. <br />"), Models_User::fetchRowByID($PROCESSED["proxy_id"])->getFullname(false), $PROCESSED["group_name"]));
                                    }
                                }
                                fclose($fh);
                            }
                        }
                        echo json_encode(array("status" => "success", "dataSuccess" => $SUCCESSSTR, "dataNotice" => $NOTICESTR, "dataError" => $ERRORSTR));
                    }
                    break;
                case "export" :
                    if (isset($request["checked"])) {
                        $group_ids = $request["checked"];
                    } else {
                        header("Location: " . ENTRADA_URL . "/admin/$MODULE");
                        exit;
                    }

                    $result = Models_Group::getName($group_ids[0]);
                    list($filename, $result) = preg_split('/ /', $result, 2);
                    $filename = "Group-$filename-" . date("dmhi", time()) . ".csv";

                    header("Content-Type:  application/vnd.ms-excel");
                    header("Content-Disposition: \"" . $filename . "\"; filename=\"" . $filename . "\"");

                    echo $translate->_("\"Group Name\",\"Firstname\",\"Lastname\",\"Active\",\"University ID\"") . "\n";

                    $groups = Models_Group::fetchGroupsInList($group_ids);

                    foreach ($groups as $group) {
                        $members = $group->members();
                        if ($members) {
                            foreach ($members as $member) {
                                echo html_encode($group->getGroupName()) .
                                    "," . html_encode($member["lastname"]) . "," . html_encode($member["firstname"]) .
                                    ",$member[member_active], $member[number]\n";
                            }
                        }
                    }
                    break;
            }
            break;

        case "GET" :
            switch ($request["method"]) {
                case "demo" :
                    header("Pragma: public");
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Content-Type: application/force-download");
                    header("Content-Type: application/octet-stream");
                    header("Content-Type: text/csv");
                    header("Content-Disposition: attachment; filename=group-template.csv");
                    header("Content-Transfer-Encoding: binary");

                    $fp = fopen("php://output", "w");
                    $row = array("Group Name", "Firstname", "Lastname", "Active", "University ID");
                    fputcsv($fp, $row);
                    fputcsv($fp, array("Class of 2019", "Kathy", "Morin", 1, "123456789"));
                    fputcsv($fp, array("Class of 2019", "Charlie", "Gray", 1, "123456790"));
                    fputcsv($fp, array("Class of 2019", "Molly", "Martinez", 1, "123456791"));
                    fclose($fp);

                    break;
            }
            break;
    }
}
exit();