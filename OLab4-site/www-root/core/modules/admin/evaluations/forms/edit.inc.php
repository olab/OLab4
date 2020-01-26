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
 * This file is used to author evaluation forms.
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
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationform", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($FORM_ID) {
		$query = "	SELECT a.*
					FROM `evaluation_forms` AS a
					WHERE `eform_id` = ".$db->qstr($FORM_ID)."
					AND `form_active` = '1'";
		$form_record = $db->GetRow($query);
		if ($form_record && $ENTRADA_ACL->amIAllowed(new EvaluationFormResource($form_record["eform_id"], $form_record["organisation_id"], true), "update")) {
			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/forms?section=edit&id=".$FORM_ID, "title" => limit_chars($form_record["form_title"], 32));

			/**
			 * Load the rich text editor.
			 */
			load_rte();

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
                            foreach ($evaluation_forms as $evaluation_form) {
                                if ($evaluation_form->getID() != $FORM_ID) {
                                    $authors_with_duplicates[] = $author;
                                }
                            }
                        }
                    }

                    if (count($authors_with_duplicates) >= 1) {
                        if (count($authors_with_duplicates) == 1) {
                            if ($authors_with_duplicates[0] == $ENTRADA_USER->getActiveId()) {
                                add_error("The <strong>Form Title</strong> must be unique for each author. Please ensure that you use a form name which you are not an author for already. <br /><br />Please consider adding a simple identifier to the end of the form name (such as \"".date("M-Y")."\") to identify this form compared to any other existing form with the same name.");
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
                            $error_string .= "<br />Please consider adding a simple identifier to the end of the form name (such as \"".date("M-Y")."\") to identify this form compared to any other existing form with the same name.\n";
                            add_error($error_string);
                        }
                    }

					if (!$ERROR) {
						$PROCESSED["updated_date"] = time();
						$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

						if ($db->AutoExecute("evaluation_forms", $PROCESSED, "UPDATE", "`eform_id` = ".$db->qstr($FORM_ID))) {
							if ((is_array($PROCESSED["associated_authors"])) && (count($PROCESSED["associated_authors"]))) {
								$query = "DELETE FROM `evaluation_form_contacts` WHERE `eform_id` = ".$db->qstr($FORM_ID);
								if ($db->Execute($query)) {
									foreach($PROCESSED["associated_authors"] as $contact_order => $proxy_id) {
										$contact_details =  array(	"eform_id" => $FORM_ID, 
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
							}
							$SUCCESS++;
							$SUCCESSSTR[] = "The <strong>Form Information</strong> section has been successfully updated.";

							application_log("success", "Form information for form_id [".$FORM_ID."] was updated.");
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem updating this evaluation form. The system administrator was informed of this error; please try again later.";

							application_log("error", "There was an error updating evaluation form information for form_id [".$FORM_ID."]. Database said: ".$db->ErrorMsg());
						}
					}
				break;
				case 1 :
				default :
					$PROCESSED = $form_record;

					/**
					 * Add any existing associated reviewers from the evaluation_contacts table
					 * into the $PROCESSED["associated_reviewers"] array.
					 */
					$query = "SELECT * FROM `evaluation_form_contacts` WHERE `eform_id` = ".$db->qstr($FORM_ID)." ORDER BY `contact_order` ASC";
					$results = $db->GetAll($query);
					if ($results) {
						foreach($results as $contact_order => $result) {
							$PROCESSED["associated_authors"][(int) $contact_order] = $result["proxy_id"];
						}
					}
				break;
			}

			// Display Content
			switch ($STEP) {
				case 2 :
				case 1 :
				default :
					
					if (isset($_GET["success"]) && $_GET["success"]) {
						if ($_GET["success"] == "true") {
							echo display_success(array("The <strong>MCC Presentations</strong> for the selected question have been updated successfully."));
						} else {
							echo display_error(array("There was an error while attempting to update the <strong>MCC Presentations</strong> for the selected question. Please contact a system administrator if this problem persists."));
						}
					}
					
					if (!$ALLOW_QUESTION_MODIFICATIONS) {
						echo display_notice(array("Please note this evaluation form has already been used in an evaluation, therefore the questions cannot be modified.<br /><br />If you would like to make modifications to the form you must copy it first <em>(using the Copy Form button below)</em> and then make your modifications."));
					}

					$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>\n";
					$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js\"></script>\n";
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
					
					?>
					<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations/forms?section=edit&amp;id=<?php echo $FORM_ID; ?>" method="post" id="editEvaluationFormForm">
					<input type="hidden" name="step" value="2" />
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Evaluation Form">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<thead>
						<tr>
							<td colspan="3">
								<a name="form_information_section"></a><h2 id="form_information_section" title="Evaluation Form Information">Evaluation Form Information</h2>
								<?php
								if ($SUCCESS) {
									fade_element("out", "display-success-box");
									echo display_success();
								}

								if ($NOTICE) {
									fade_element("out", "display-notice-box", 100, 15000);
									echo display_notice();
								}

								if ($ERROR) {
									echo display_error();
								}
								?>
							</td>
						</tr>
					</thead>
					<tbody id="form-information">
						<tr>
							<td></td>
							<td><label for="target_id" class="form-required">Form Type</label></td>
							<td>
								<select id="target_id" name="target_id" style="width: 250px;">
									<option value="0">-- Select Form Type --</option>
									<?php
									if ($EVALUATION_TARGETS && is_array($EVALUATION_TARGETS) && !empty($EVALUATION_TARGETS)) {
										foreach ($EVALUATION_TARGETS as $target) {
											echo "<option value=\"".$target["target_id"]."\"".(($PROCESSED["target_id"] == $target["target_id"]) ? " selected=\"selected\"" : "").">".html_encode($target["target_title"])."</option>";
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
							<td><input type="text" id="form_title" name="form_title" value="<?php echo html_encode($PROCESSED["form_title"]); ?>" maxlength="64" style="width: 95%" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top">
								<label for="form_description" class="form-nrequired">Form Description</label>
							</td>
							<td>
								<textarea id="form_description" name="form_description" style="width: 550px; height: 125px" cols="70" rows="10"><?php echo clean_input($PROCESSED["form_description"], array("trim", "encode")); ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="3" style="padding: 25px 0px 25px 0px">
								<div style="float: left">
									<button href="#disable-form-confirmation-box" class="btn btn-danger" id="form-control-disable">Disable Form</button>
								</div>
								<div style="float: right; text-align: right">
									<button href="#copy-form-confirmation-box" class="btn" id="form-control-copy"><i class="icon-share"></i> Copy Form</button>
									<input type="submit" class="btn btn-primary" value="Save Changes" />
								</div>
								<div class="clear"></div>
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
									if (is_array($PROCESSED["associated_authors"]) && count($PROCESSED["associated_authors"])) {
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

					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Evaluation Form Questions">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 17%" />
						<col style="width: 80%" />
					</colgroup>
					<thead>
						<tr>
							<td colspan="3">
								<a name="form_questions_section"></a><h2 id="form_questions_section" title="Evaluation Form Questions">Evaluation Form Questions</h2>
							</td>
						</tr>
					</thead>
					<tbody id="evaluation-form-questions">
						<tr>
							<td colspan="3">
								<?php
								if ($ALLOW_QUESTION_MODIFICATIONS) {
									?>
									<div style="padding-bottom: 2px">
										<ul class="page-action">
											<li><a href="<?php echo ENTRADA_URL; ?>/admin/evaluations/questions?form_id=<?php echo $FORM_ID; ?>">Attach Evaluation Questions</a></li>
										</ul>
									</div>
									<?php
								}

								$query = "SELECT a.*, b.*
											FROM `evaluation_form_questions` AS a
											JOIN `evaluations_lu_questions` AS b
											ON a.`equestion_id` = b.`equestion_id`
											WHERE a.`eform_id` = ".$db->qstr($FORM_ID)."
											ORDER BY a.`question_order` ASC";
								$questions = $db->GetAll($query);
								if ($questions) {
									Classes_Evaluation::getQuestionAnswerControls($questions, $FORM_ID, $ALLOW_QUESTION_MODIFICATIONS);
								} else {
									$ONLOAD[] = "$('display-no-question-message').show()";
								}
								?>
								<div id="display-no-question-message" class="display-generic" style="display: none">
									There are currently <strong>no questions</strong> associated with this evaluation form.<br /><br />To create questions in this form click the <strong>Attach Evaluation Questions</strong> link above.
								</div>
							</td>
						</tr>
					</tbody>
					</table>
					<div id="disable-form-confirmation-box" class="modal-confirmation">
						<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations/forms?section=delete" method="post" id="disableEvaluationFormForm">
							<input type="hidden" name="delete[]" value="<?php echo $FORM_ID; ?>" />
							<input type="hidden" name="confirmed" value="1" />
							<h1>Disable <strong>Form</strong> Confirmation</h1>
							Do you really wish to disable this evaluation form?
							<div class="body">
								<div id="disable-form-confirmation-content" class="content">
									<strong><?php echo html_encode($PROCESSED["form_title"]); ?></strong>
								</div>
							</div>
							If you confirm this action, this form will not be available for evaluations.
							<div class="footer">
								<input type="button" class="btn" value="Close" onclick="Control.Modal.close()" style="float: left; margin: 8px 0px 4px 10px" />
								<input type="submit" class="btn btn-primary" value="Confirm" style="float: right; margin: 8px 10px 4px 0px" />
							</div>
						</form>
					</div>
					<div id="copy-form-confirmation-box" class="modal-confirmation">
						<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations/forms?section=copy&amp;id=<?php echo $FORM_ID; ?>" method="post" id="copyEvaluationForm">
							<h1>Copy <strong>Form</strong> Confirmation</h1>
							<div id="copy-form-message-holder" class="display-generic">If you would like to create a new form based on the existing questions in this form, please provide a new title and press <strong>Copy Form</strong>.</div>
							<div class="body">
								<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Copying Form">
									<colgroup>
										<col style="width: 30%" />
										<col style="width: 70%" />
									</colgroup>
									<tbody>
										<tr>
											<td><span class="form-nrequired">Current Form Title</span></td>
											<td><?php echo html_encode($PROCESSED["form_title"]); ?></td>
										</tr>
										<tr>
											<td><label for="form_title" class="form-required">New Form Title</label></td>
											<td><input type="text" id="form_title" name="form_title" value="<?php echo html_encode($PROCESSED["form_title"]); ?>" maxlength="64" style="width: 96%" /></td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="footer">
								<input type="button" class="btn" value="Cancel" onclick="Control.Modal.close()" style="float: left; margin: 8px 0px 4px 10px" />
								<input type="submit" class="btn btn-primary" value="Copy Form" style="float: right; margin: 8px 10px 4px 0px" />
							</div>
						</form>
					</div>
					<a id="false-link" href="#placeholder"></a>
					<div id="placeholder" style="display: none; padding: 10px 20px 10px 20px; min-width: 400px;"></div>
					<script type="text/javascript" defer="defer">
						var ajax_url = '';
						var modalDialog;
						document.observe('dom:loaded', function() {
							modalDialog = new Control.Modal($('false-link'), {
								position:		'center',
								overlayOpacity:	0.75,
								closeOnClick:	'overlay',
								className:		'modal',
								fade:			true,
								fadeDuration:	0.30,
								beforeOpen: function(request) {
									eval($('scripts-on-open').innerHTML);
								}
							});
						});
				
						function openDialog (url) {
							if (url && url != ajax_url) {
								ajax_url = url;
								new Ajax.Request(ajax_url, {
									method: 'get',
									onComplete: function(transport) {
										modalDialog.container.update(transport.responseText);
										modalDialog.open();
									}
								});
							} else {
								$('scripts-on-open').update();
								modalDialog.open();
							}
						}

						
						// Modal control for deleting form.
						new Control.Modal('form-control-disable', {
							overlayOpacity:	0.75,
							closeOnClick:	'overlay',
							className:		'modal-confirmation',
							fade:			true,
							fadeDuration:	0.30
						});

						// Modal control for copying form.
						new Control.Modal('form-control-copy', {
							overlayOpacity:	0.75,
							closeOnClick:	'overlay',
							className:		'modal-confirmation',
							fade:			true,
							fadeDuration:	0.30
						});
					</script>

					
					<?php
					/**
					 * Sidebar item that will provide the links to the different sections within this page.
					 */
					$sidebar_html  = "<ul class=\"menu\">\n";
					$sidebar_html .= "	<li class=\"link\"><a href=\"#form_information_section\" onclick=\"$('form_information_section').scrollTo(); return false;\" title=\"Form Information\">Form Information</a></li>\n";
					$sidebar_html .= "	<li class=\"link\"><a href=\"#form_questions_section\" onclick=\"$('form_questions_section').scrollTo(); return false;\" title=\"Form Questions\">Form Questions</a></li>\n";
					$sidebar_html .= "</ul>\n";

					new_sidebar_item("Page Anchors", $sidebar_html, "page-anchors", "open", "1.9");
				break;
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to edit an evaluation form, you must provide a valid identifier.";

			echo display_error();

			application_log("notice", "Failed to provide a valid identifer [".$FORM_ID."] when attempting to edit an evaluation form.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit an evaluation form you must provide an identifier.";

		echo display_error();

		application_log("notice", "Failed to provide a form identifier when editing an evaluation form.");
	}
}