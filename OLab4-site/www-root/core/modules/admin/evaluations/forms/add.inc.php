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
 * This file is used to author and share quizzes with other folks who have
 * administrative permissions in the system.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationform", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("section" => "add")), "title" => "Add Evaluation Form");
	
	echo "<h1>Add Evaluation Form</h1>\n";

	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "target_id" / Form Type.
			 */
			if (isset($_POST["target_id"]) && ($tmp_input = clean_input($_POST["target_id"], "int")) && array_key_exists($tmp_input, $EVALUATION_TARGETS)) {
				$PROCESSED["target_id"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Form Type</strong> field is required.";
			}

			/**
			 * Required field "form_title" / Form Title.
			 */
			if ((isset($_POST["form_title"])) && ($tmp_input = clean_input($_POST["form_title"], array("notags", "trim")))) {
				$PROCESSED["form_title"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Form Title</strong> field is required.";
			}

			/**
			 * Non-Required field "form_description" / Form Description.
			 */
			if ((isset($_POST["form_description"])) && ($tmp_input = clean_input($_POST["form_description"], array("trim", "allowedtags")))) {
				$PROCESSED["form_description"] = $tmp_input;
			} else {
				$PROCESSED["form_description"] = "";
			}
			
			/**
			 * Required field "associated_author" / Associated Authors (array of proxy ids).
			 * This is actually accomplished after the evaluation_form is inserted below.
			 */	
			if ((isset($_POST["associated_author"]))) {
				$associated_authors = explode(",", $_POST["associated_author"]);
				foreach($associated_authors as $contact_order => $proxy_id) {
					if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$PROCESSED["associated_authors"][(int) $contact_order] = $proxy_id;
					}
				}
			}
			
			/**
			 * The current evaluation author must be in the author list.
			 */
			if (!in_array($ENTRADA_USER->getActiveId(), $PROCESSED["associated_authors"])) {
				array_unshift($PROCESSED["associated_authors"], $ENTRADA_USER->getActiveId());
			}

            $authors_with_duplicates = array();
            foreach ($PROCESSED["associated_authors"] as $author) {
                $evaluation_forms = Models_Evaluation_Form::fetchAllByAuthorAndTitle($author, $PROCESSED["form_title"]);
                if ($evaluation_forms) {
                    $authors_with_duplicates[] = $author;
                }
            }

            if (count($authors_with_duplicates) >= 1) {
                if (count($authors_with_duplicates) == 1) {
                    if ($authors_with_duplicates[0] == $ENTRADA_USER->getActiveId()) {
                        add_error("The <strong>Form Title</strong> must be unique for each author. Please ensure that you use a form name which you are not an author for already.<br /><br />Please consider adding a simple identifier to the end of the form name (such as \"".date("M-Y")."\") to identify this form compared to any other existing form with the same name.");
                    } else {
                        $author_name = get_account_data("wholename", $authors_with_duplicates[0]);
                        add_error("The <strong>Form Title</strong> must be unique for each author. Please ensure that you use a form name which <strong>".html_encode($author_name)."</strong> is not an author for already.<br /><br />Please consider adding a simple identifier to the end of the form name (such as \"".date("M-Y")."\") to identify this form compared to any other existing form with the same name.");
                    }
                } else {
                    $error_string = "The <strong>Form Title</strong> must be unique for each author.<br /><br /> The following list of users are already an author on another form with the same name: <br />\n<ul class=\"menu\">\n";
                    foreach ($authors_with_duplicates as $author) {
                        $author_name = get_account_data("wholename", $author);
                        $error_string .= "<li class=\"user\">".html_encode($author_name)."</li>";
                    }
                    $error_string .= "</ul>\n";
                    $error_string .= "<br />Please consider adding a simple identifier to the end of the form name (such as \"".date("M-Y")."\") to identify this form compared to any other existing form with the same name.";
                    add_error($error_string);
                }
            }

			if (!$ERROR) {
				$PROCESSED["form_parent"] = 0;
				$PROCESSED["form_active"] = 1;
				$PROCESSED["updated_date"] = time();
				$PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

				if ($db->AutoExecute("evaluation_forms", $PROCESSED, "INSERT") && ($eform_id = $db->Insert_Id())) {
					if ((is_array($PROCESSED["associated_authors"])) && (count($PROCESSED["associated_authors"]))) {
						foreach($PROCESSED["associated_authors"] as $contact_order => $proxy_id) {
							$contact_details =  array(	"eform_id" => $eform_id, 
														"proxy_id" => $proxy_id, 
														"contact_role" => "author",
														"contact_order" => (int) $contact_order, 
														"updated_date" => time(), 
														"updated_by" => $ENTRADA_USER->getID());
							if (!$db->AutoExecute("evaluation_form_contacts", $contact_details, "INSERT")) {
								add_error("There was an error while trying to attach an <strong>Associated Author</strong> to this evaluation form.<br /><br />The system administrator was informed of this error; please try again later.");

								application_log("error", "Unable to insert a new evaluation_form_contact record while adding a new evaluation form. Database said: ".$db->ErrorMsg());
							}
						}
					}
					application_log("success", "New evaluation form [".$eform_id."] was added to the system.");

					header("Location: ".ENTRADA_URL."/admin/evaluations/forms?section=edit&id=".$eform_id);
					exit;
				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this quiz into the system. The system administrator was informed of this error; please try again later.";

					application_log("error", "There was an error inserting a quiz. Database said: ".$db->ErrorMsg());
				}
			}
			
			if ($ERROR) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :
			continue;
		break;
	}

	// Display Content
	switch ($STEP) {
		case 2 :
			if ($SUCCESS) {
				echo display_success();
			}
			if ($NOTICE) {
				echo display_notice();
			}
			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :

			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
			
			/**
			 * Compiles the full list of faculty members.
			 */
			$AUTHOR_LIST = array();
			$query = "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
						FROM `".AUTH_DATABASE."`.`user_data` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON b.`user_id` = a.`id`
						WHERE b.`app_id` = '".AUTH_APP_ID."'
						AND (b.`group` = 'faculty' OR (b.`group` = 'resident' AND b.`role` = 'lecturer') OR b.`group` = 'staff' OR b.`group` = 'medtech')
						ORDER BY a.`lastname` ASC, a.`firstname` ASC";
			$results = $db->GetAll($query);
			if ($results) {
				foreach($results as $result) {
					$AUTHOR_LIST[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"], 'organisation_id'=>$result['organisation_id']);
				}
			}
			/**
			 * Load the rich text editor.
			 */
			load_rte();

			if ($ERROR) {
				echo display_error();
			}
			?>
			<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations/forms?section=add&amp;step=2" method="post" id="addEvaluationForm">
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Evaluation Form">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<tfoot>
						<tr>
							<td colspan="3" style="padding-top: 50px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td style="width: 25%; text-align: left">
										<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/evaluations/forms'" />
									</td>
									<td style="width: 75%; text-align: right; vertical-align: middle">
										<input type="submit" class="btn btn-primary" value="Proceed" />
									</td>
								</tr>
								</table>
							</td>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td colspan="3"><h2>Evaluation Form Information</h2></td>
						</tr>
						<tr>
							<td></td>
							<td><label for="target_id" class="form-required">Form Type</label></td>
							<td>
								<select id="target_id" name="target_id" style="width: 250px;">
									<option value="0">-- Select Form Type --</option>
									<?php
									if ($EVALUATION_TARGETS && is_array($EVALUATION_TARGETS) && !empty($EVALUATION_TARGETS)) {
										foreach ($EVALUATION_TARGETS as $target) {
											echo "<option value=\"".$target["target_id"]."\"".(isset($PROCESSED["target_id"]) && $PROCESSED["target_id"] == $target["target_id"] ? " selected=\"selected\"" : "").">".html_encode($target["target_title"])."</option>";
										}
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td><label for="form_title" class="form-required">Form Title</label></td>
							<td><input type="text" id="form_title" name="form_title" value="<?php echo html_encode((isset($PROCESSED["form_title"]) && $PROCESSED["form_title"] ? $PROCESSED["form_title"] : "")); ?>" maxlength="64" style="width: 95%" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top">
								<label for="form_description" class="form-nrequired">Form Description</label>
							</td>
							<td>
								<textarea id="form_description" name="form_description" style="width: 550px; height: 125px" cols="70" rows="10"><?php echo (isset($PROCESSED["form_description"]) && $PROCESSED["form_description"] ? clean_input($PROCESSED["form_description"], array("trim", "allowedtags", "encode")) : ""); ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top;">
								<label for="evaluation_authors" class="form-required">Evaluation Authors</label>
							</td>
							<td>
								<input type="text" id="author_name" name="fullname" size="30" autocomplete="off" style="width: 203px; vertical-align: middle" />
								<?php
								$ONLOAD[] = "author_list = new AutoCompleteList({ type: 'author', url: '". ENTRADA_RELATIVE ."/api/personnel.api.php?type=facultyorstaff', remove_image: '". ENTRADA_RELATIVE ."/images/action-delete.gif'})";
								?>
								<div class="autocomplete" id="author_name_auto_complete"></div>
								<input type="hidden" id="associated_author" name="associated_author" />
								<input type="button" class="btn btn-small" id="add_associated_author" value="Add" style="vertical-align: middle" />
								<span class="content-small">(<strong>Example:</strong> <?php echo html_encode($_SESSION["details"]["lastname"].", ".$_SESSION["details"]["firstname"]); ?>)</span>
								<ul id="author_list" class="menu" style="margin-top: 15px">
									<?php
									if (isset($PROCESSED["associated_authors"]) && @count($PROCESSED["associated_authors"])) {
										foreach ($PROCESSED["associated_authors"] as $author) {
											if ((array_key_exists($author, $AUTHOR_LIST)) && is_array($AUTHOR_LIST[$author])) {
												?>
												<li class="user" id="author_<?php echo $AUTHOR_LIST[$author]["proxy_id"]; ?>" style="cursor: move;margin-bottom:10px;width:350px;"><?php echo $AUTHOR_LIST[$author]["fullname"]; if ($author != $ENTRADA_USER->getID()) {?> <img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="author_list.removeItem('<?php echo $AUTHOR_LIST[$author]["proxy_id"]; ?>');" class="list-cancel-image" /><?php } ?></li>
												<?php
											}
										}
									} else {
										?>
										<li class="user" id="author_<?php echo $AUTHOR_LIST[$ENTRADA_USER->getProxyId()]["proxy_id"]; ?>" style="cursor: move;margin-bottom:10px;width:350px;"><?php echo $AUTHOR_LIST[$ENTRADA_USER->getProxyId()]["fullname"]; ?></li>
										<?php
									}
									?>
								</ul>
								<input type="hidden" id="author_ref" name="author_ref" value="" />
								<input type="hidden" id="author_id" name="author_id" value="" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<?php
		break;
	}
}