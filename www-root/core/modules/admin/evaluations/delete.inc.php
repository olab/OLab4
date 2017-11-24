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
 * Please add a description here about what this file does.
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Howard Lu <yhlu@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/
if((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed("evaluation", "delete", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => "", "title" => "Delete Evaluations");

	echo "<h1>Delete Evaluations</h1>";

	$EVALUATION_IDS = array();

	// Error Checking
	switch($STEP) {
		case 2 :
		case 1 :
		default :
			if((!isset($_POST["checked"])) || (!is_array($_POST["checked"])) || (!@count($_POST["checked"]))) {
				header("Location: ".ENTRADA_URL."/admin/evaluations");
				exit;
			} else {
				foreach($_POST["checked"] as $evaluation_id) {
					$evaluation_id = (int) trim($evaluation_id);
					if($evaluation_id) {
						$EVALUATION_IDS[] = $evaluation_id;
					}
				}

				if(!@count($EVALUATION_IDS)) {
					$ERROR++;
					$ERRORSTR[] = "There were no valid evaluation identifiers provided to delete. Please ensure that you access this section through the evaluation index.";
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
			$removed = array();

			foreach($EVALUATION_IDS as $evaluation_id) {
				$allow_removal = false;

				if($evaluation_id = (int) $evaluation_id) {
                                    /**
                                     * Check to see if this evaluation exist.
                                     */
                                    $query		= "	SELECT * FROM `evaluations`
                                                                    WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
                                    $rlt_detail	= $db->GetAll($query);
                                    if (($rlt_detail) && (count($rlt_detail) <= 0)) {
                                            $ERROR++;
                                            $ERRORSTR[] = "You cannot delete <a href=\"".ENTRADA_URL."/admin/evaluations?section=edit&amp;id=".$evaluation_id."\" style=\"font-weight: bold\">".html_encode($result["evaluation_title"])."</a> at this time because there are no evaluation.";
                                    } else {


                                            /**
                                             * Remove all records from evaluation_targets table.
                                             */

                                            $query		= "SELECT * FROM `evaluation_targets` WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
                                            $results	= $db->GetAll($query);
                                            if($results) {
                                                    foreach($results as $result) {
                                                            $removed[$evaluation_id]["etarget_id"][] = $result["etarget_id"];
                                                    }

                                                    $query = "DELETE FROM `evaluation_targets` WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
                                                    $db->Execute($query);
                                            }



                                            /**
                                             * Remove all records from evaluation_evaluators table.
                                             */
                                            $query		= "SELECT * FROM `evaluation_evaluators` WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
                                            $results	= $db->GetAll($query);
                                            if($results) {
                                                    foreach($results as $result) {
                                                            $removed[$evaluation_id]["evaluation_id"][] = $result["evaluation_id"];
                                                    }

                                                    $query = "DELETE FROM `evaluation_evaluators` WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
                                                    $db->Execute($query);
                                            }

                                            /**
                                             * Remove evaluation_id record from evaluations table.
                                             */
                                            $query		= "SELECT * FROM `evaluations` WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
                                            $results	= $db->GetAll($query);
                                            if($results) {
                                                    foreach($results as $result) {
                                                            $removed[$evaluation_id]["evaluation_title"] = $result["evaluation_title"];
                                                    }
                                                    $query = "DELETE FROM `evaluations` WHERE `evaluation_id` = ".$db->qstr($evaluation_id);
                                                    $db->Execute($query);
                                            }
                                    }
				}
			}

			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/evaluations\\'', 5000)";

			if($total_removed = @count($removed)) {
				$SUCCESS++;
				$SUCCESSSTR[$SUCCESS]  = "You have successfully removed ".$total_removed." evaluation".(($total_removed != 1) ? "s" : "")." from the system:";
				$SUCCESSSTR[$SUCCESS] .= "<div style=\"padding-left: 15px; padding-bottom: 15px; font-family: monospace\">\n";
				foreach($removed as $result) {
					$SUCCESSSTR[$SUCCESS] .= html_encode($result["evaluation_title"])."<br />";
				}
				$SUCCESSSTR[$SUCCESS] .= "</div>\n";
				$SUCCESSSTR[$SUCCESS] .= "You will be automatically redirected to the evaluation index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/evaluations\">click here</a> if you do not wish to wait.";

				echo display_success();

				application_log("success", "Successfully removed evaluation ids: ".implode(", ", $EVALUATION_IDS));
			} else {
				$ERROR++;
				$ERRORSTR[] = "Unable to remove the requested evaluations from the system.<br /><br />The system administrator has been informed of this issue and will address it shortly; please try again later.";

				application_log("error", "Failed to remove all evaluations from the remove request. Database said: ".$db->ErrorMsg());
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
				$total_evaluations	= count($EVALUATION_IDS);

				$query_evaluations = "	SELECT `evaluation_id`, `eform_id`, `organisation_id`, `evaluation_title`, `evaluation_description`, `evaluation_active`,
                                                            `evaluation_start`, `evaluation_finish`, `min_submittable`, `max_submittable`, `release_date`,
                                                            `release_until`, `updated_date`, `updated_by` from `evaluations`
                                                            WHERE `evaluation_id` IN (".implode(", ", $EVALUATION_IDS).")
                                                            ORDER BY `evaluation_start`";
				$results	= $db->GetAll($query_evaluations);
				if($results) {
					echo display_notice(array("Please review the following evaluation".(($total_evaluations != 1) ? "s" : "")." to ensure that you wish to <strong>permanently delete</strong> ".(($total_evaluations != 1) ? "them" : "it").".<br /><br />This will also remove any related evaluators, targets, etc. and this action cannot be undone."));
					?>
					<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations?section=delete&amp;step=2" method="post">
					<table class="tableList" cellspacing="0" summary="List of Events">
					<colgroup>
                        <col class="modified" />
                        <col class="title" />
                        <col class="start" />
                        <col class="finish" />
					</colgroup>
					<thead>
						<tr>
                            <td class="modified">&nbsp;</td>
                            <td>Title</td>
                            <td>Start</td>
                            <td>Finish</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td></td>
							<td colspan="3" style="padding-top: 10px">
								<input type="submit" class="btn btn-danger" value="Confirm Removal" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach($results as $result) {
							$url			= "";
							$accessible		= true;
							$administrator	= false;

							if($ENTRADA_ACL->amIAllowed(new EvaluationResource($result["evaluation_id"], $result["organisation_id"], true), 'delete')) {
								$administrator = true;
							} else {
								if((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
									$accessible = false;
								}
							}

                                                        $administrator = true; //Added before EvaluationResource is setup.

							if($administrator) {
								$url 	= ENTRADA_URL."/admin/evaluations?section=edit&amp;id=".$result["evaluation_id"];

                                echo "<tr id=\"evaluation-".$result["evaluation_id"]."\" class=\"evaluation".((!$url) ? " np" : ((!$accessible) ? " na" : ""))."\">\n";
                                echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["evaluation_id"]."\" checked=\"checked\" /></td>\n";
                                echo "	<td class=\"title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Evaluation Title: ".html_encode($result["evaluation_title"])."\">" : "").html_encode($result["evaluation_title"]).(($url) ? "</a>" : "")."</td>\n";
                                echo "	<td class=\"start".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Evaluation Date\">" : "").date(DEFAULT_DATE_FORMAT, $result["evaluation_start"]).(($url) ? "</a>" : "")."</td>\n";
                                echo "	<td class=\"finish".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Intended For Phase ".html_encode($result["evaluation_finish"])."\">" : "").date(DEFAULT_DATE_FORMAT, html_encode($result["evaluation_finish"])).(($url) ? "</a>" : "")."</td>\n";
								echo "</tr>\n";
							}
						}
						?>
					</tbody>
					</table>
					</form>
					<?php
				} else {
					application_log("error", "The confirmation of removal query returned no results... curious Database said: ".$db->ErrorMsg());

					header("Location: ".ENTRADA_URL."/admin/evaluations");
					exit;
				}
			}
		break;
	}
}