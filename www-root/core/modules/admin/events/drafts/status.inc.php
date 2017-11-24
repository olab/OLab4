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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('event', 'delete', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	
	$draft_ids = array();

	$action = clean_input($_GET["action"], array("trim", "nohtml"));
	
	switch ($action) {
		case "reopen" :
			$status = "open";
		break;
		case "approve" :
		default :
			$status = "approved";
		break;
	}
	
	$BREADCRUMB[]	= array("url" => "", "title" => ucwords($action)." Drafts");

	echo "<h1>".ucwords($action)." Drafts</h1>";
	
	// Error Checking
	switch($STEP) {
		case 2 :
		case 1 :
		default :
			if(((!isset($_POST["checked"])) || (!is_array($_POST["checked"])) || (!@count($_POST["checked"]))) && (!isset($_GET["draft_id"]) || !$_GET["draft_id"])) {
				header("Location: ".ENTRADA_URL."/admin/events/drafts");
				exit;
			} else {
				if ((isset($_POST["checked"])) && (is_array($_POST["checked"])) && (@count($_POST["checked"]))) {
					foreach($_POST["checked"] as $draft_id) {
						$draft_id = (int) trim($draft_id);
						if($draft_id) {
							$draft_ids[] = $draft_id;
						}
					}
				} elseif (isset($_GET["draft_id"]) && ($draft_id = clean_input($_GET["draft_id"], array("trim", "int")))) {
					$draft_ids[] = $draft_id;
				}

				if(!@count($draft_ids)) {
					add_error("There were no valid draft identifiers provided to create. Please ensure that you access this section through the event index.");
				}
			}

			if($ERROR) {
				$STEP = 1;
			}
		break;
	}

	// Display Page
	switch($STEP) {
		case 2 :
			$approved = array();
			foreach($draft_ids as $draft_id) {
                $d =  Models_Event_Draft::fetchRowByID($draft_id);
                if ($d->fromArray(array("status" => $status))->update()) {
                    $approved[] = $d;
                } else {
                    application_log("error", "An unknown error was encountered while attempting to change the status [".$status."] of an event draft [".$draft_id."].");
                }
			}

			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/events/drafts\\'', 5000)";

			if(!empty($approved)) {
				$total_approved = count($approved);
				$successmsg[] = "You have successfully ".$action."ed ".$total_approved." draft".(($total_approved > 1) ? "s" : "").".";
				$successmsg[] .= "<div style=\"padding-left: 15px; padding-bottom: 15px; font-family: monospace\">\n";
				foreach($approved as $draft) {
					$successmsg[] .= html_encode($draft->getName())."<br />";
				}
				$successmsg[] .= "</div>\n";
				$successmsg[] .= "You will be automatically redirected to the event index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/events/drafts\">click here</a> if you do not wish to wait.";

				echo display_success(implode("\n", $successmsg));

				application_log("success", "User [".$ENTRADA_USER->getActiveId()."] approved draft ids: ".implode(", ", $draft_ids));
			} else {
				add_error("Unable to remove the requested drafts from the system.<br /><br />The system administrator has been informed of this issue and will address it shortly; please try again later.");
				application_log("error", "Failed to remove draft from the remove request. Database said: ".$db->ErrorMsg());
			}

			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
			if($ERROR) {
				echo display_error();
			} else {
				
				$total_events	= count($draft_ids);
				
                $drafts = array();
                foreach ($draft_ids as $draft_id) {
                    $drafts[] = Models_Event_Draft::fetchRowByID($draft_id);
                }
                
				if(!empty($drafts)) {
					echo display_notice(array("Please review the following draft".(($total_events > 1) ? "s" : "")." to ensure that you wish to set ".(($total_events > 1) ? "them" : "it")." to <strong>".$status."</strong>.<br /><br />Approving a draft will schedule it to be imported into the system.<br />Re-opening a draft will remove it from the importation schedule and allow it to be modified."));
					$JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
					?>
					<style type="text/css">
						#draft-list_length {padding:5px 4px 0 0;}
						#draft-list_filter {-moz-border-radius:10px 10px 0px 0px; border: 1px solid #9D9D9D;border-bottom:none;background-color:#FAFAFA;font-size: 0.9em;padding:3px;}
						#draft-list_paginate a {margin:2px 5px;}
					</style>
					<form action="<?php echo ENTRADA_URL; ?>/admin/events/drafts?section=status&step=2&action=<?php echo $action; ?>" method="post">
                        <table class="table table-bordered table-striped" id="draft-list" widht="100%" cellspacing="0" summary="List of Events">
                        <colgroup>
                            <col class="modified" />
                            <col class="date" />
                            <col class="title" />
                            <col class="description" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="modified">&nbsp;</th>
                                <th class="date">Creation Date &amp; Time</th>
                                <th class="title">Draft Title</th>
                                <th class="description">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach($drafts as $draft) {
                                $url 	= ENTRADA_URL."/admin/events/drafts?section=edit&amp;draft_id=".$draft->getDraftID();

                                echo "<tr id=\"draft-".$draft->getDraftID()."\" class=\"event".((!$url) ? " np" : ((!$accessible) ? " na" : ""))."\">\n";
                                echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$draft->getDraftID()."\" checked=\"checked\" /></td>\n";
                                echo "	<td class=\"date".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Draft Creation Date\">" : "").date(DEFAULT_DATE_FORMAT, $draft->getCreated()).(($url) ? "</a>" : "")."</td>\n";
                                echo "	<td class=\"title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Draft Title: ".html_encode($draft->getName())."\">" : "").html_encode($draft->getName()).(($url) ? "</a>" : "")."</td>\n";
                                echo "	<td class=\"description".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Draft Description: ".html_encode($draft->getDescription())."\">" : "").html_encode($draft->getDescription()).(($url) ? "</a>" : "")."</td>\n";
                                echo "</tr>\n";
                            }
                            ?>
                        </tbody>
                        </table>
                        <table width="100%">
                            <tfoot>
                                <tr>
                                    <td><input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/events/drafts/';" /></td>
                                    <td align="right" style="padding-top: 10px"><input type="submit" class="btn btn-primary" value="Confirm" /></td>
                                </tr>
                            </tfoot>
                        </table>
					</form>
					<?php
				} else {
					application_log("error", "The confirmation of removal query returned no results... curious Database said: ".$db->ErrorMsg());
					header("Location: ".ENTRADA_URL."/admin/events/drafts");
					exit;	
				}
			}
		break;
	}
}