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

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_QUIZZES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('quiz', 'create', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("section" => "add")), "title" => "Adding Quiz");
	
	$PROCESSED["associated_proxy_ids"] = array($ENTRADA_USER->getActiveId());
	
	echo "<h1>Adding Quiz</h1>\n";

	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "quiz_title" / Quiz Title.
			 */
			if ((isset($_POST["quiz_title"])) && ($tmp_input = clean_input($_POST["quiz_title"], array("notags", "trim")))) {
				$PROCESSED["quiz_title"] = $tmp_input;
			} else {
				add_error("The <strong>Quiz Title</strong> field is required.");
			}

			/**
			 * Non-Required field "quiz_description" / Quiz Description.
			 */
			if ((isset($_POST["quiz_description"])) && ($tmp_input = clean_input($_POST["quiz_description"], array("trim", "allowedtags")))) {
				$PROCESSED["quiz_description"] = $tmp_input;
			} else {
				$PROCESSED["quiz_description"] = "";
			}

			/**
			 * Required field "associated_proxy_ids" / Quiz Authors (array of proxy ids).
			 * This is actually accomplished after the quiz is inserted below.
			 */
			if ((isset($_POST["associated_proxy_ids"]))) {
				$associated_proxy_ids = explode(",", $_POST["associated_proxy_ids"]);
				foreach($associated_proxy_ids as $contact_order => $proxy_id) {
					if($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$PROCESSED["associated_proxy_ids"][(int) $contact_order] = $proxy_id;
					}
				}
			}
			
			/**
			 * The current quiz author must be in the quiz author list.
			 */
			if (!in_array($ENTRADA_USER->getActiveId(), $PROCESSED["associated_proxy_ids"])) {
				array_unshift($PROCESSED["associated_proxy_ids"], $ENTRADA_USER->getActiveId());
			}

			if (isset($_POST["post_action"])) {
				switch (clean_input($_POST["post_action"], "alpha")) {
					case "new" :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new".((int)clean_input($_POST["post_action"], "numeric") ? (int)clean_input($_POST["post_action"], "numeric") : 1);
					break;
					case "index" :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
					break;
					case "content" :
					default :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "content";
					break;
				}
			} else {
				$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new";
			}

			if (!$ERROR) {
				$PROCESSED["updated_date"]	= time();
				$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();
				$PROCESSED["created_by"]	= $ENTRADA_USER->getID();

                $quiz = new Models_Quiz($PROCESSED);
                
				if ($quiz->insert()) {
                    $quiz_id = $quiz->getQuizID();
					if ($quiz_id) {
						
						/**
						 * Add the quiz authors to the quiz_contacts table.
						 */
						if ((is_array($PROCESSED["associated_proxy_ids"])) && (count($PROCESSED["associated_proxy_ids"]))) {						
							foreach ($PROCESSED["associated_proxy_ids"] as $proxy_id) {
                                $contact = new Models_Quiz_Contact(
                                    array(
                                        "quiz_id"       => $quiz_id, 
                                        "proxy_id"      => $proxy_id, 
                                        "updated_date"  => time(), 
                                        "updated_by"    => $ENTRADA_USER->getID()
                                    )
                                );
								if (!$contact->insert()) {
									add_error("There was an error while trying to attach a <strong>Quiz Author</strong> to this quiz.<br /><br />The system administrator was informed of this error; please try again later.");

									application_log("error", "Unable to insert a new quiz_contact record while adding a new quiz. Database said: ".$db->ErrorMsg());
								}
							}
						}
						
						switch (clean_input($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"], "alpha")) {
							case "new" :
								$url	= ENTRADA_URL."/admin/".$MODULE."?section=add-question&id=".$quiz_id."&type=".((int)clean_input($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"], "numeric") ? (int)clean_input($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"], "numeric") : 1);
								$msg	= "You will now be redirected to add a new quiz question to this quiz; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "index" :
								$url	= ENTRADA_URL."/admin/".$MODULE;
								$msg	= "You will now be redirected back to the quiz index page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "content" :
							default :
								$url = ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$quiz_id;
								$msg	= "You will now be redirected back to the quiz; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
						}

						$SUCCESS++;
						$SUCCESSSTR[]	= "You have successfully added <strong>".html_encode($PROCESSED["quiz_title"])."</strong> to this system.<br /><br />".$msg;
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
						application_log("success", "New quiz [".$quiz_id."] added to the system.");
						
					} else {
						add_error("There was a problem inserting this quiz into the system. The system administrator was informed of this error; please try again later.");

						application_log("error", "There was an error inserting a quiz, as there was no insert ID. Database said: ".$db->ErrorMsg());
					}
				} else {
					add_error("There was a problem inserting this quiz into the system. The system administrator was informed of this error; please try again later.");

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
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js\"></script>\n";
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
			
			/**
			 * Load the rich text editor.
			 */
			load_rte();

			if ($ERROR) {
				echo display_error();
			}
			?>
			<form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=add&amp;step=2" method="post" id="addQuizForm" class="form-horizontal">
			<h2>Quiz Information</h2>
			<div class="control-group">
				<label for="quiz_title" class="control-label form-required">Quiz Title:</label>
				<div class="controls">
					<input type="text" id="quiz_title" name="quiz_title" value="<?php echo html_encode($PROCESSED["quiz_title"]); ?>" maxlength="64" style="width: 95%" />
				</div>
			</div>
			
			<div class="control-group">
				<label for="quiz_description" class="control-label form-nrequired">Quiz Description:</label>
				<div class="controls">
					<textarea id="quiz_description" name="quiz_description" style="height: 125px" cols="70" rows="10"><?php echo clean_input($PROCESSED["quiz_description"], array("trim", "allowedtags", "encode")); ?></textarea>
				</div>
			</div>
			
			<div class="control-group">
				<label for="quiz_description" class="control-label form-nrequired">Quiz Authors:
				<div class="content-small" style="margin-top: 15px">
					<strong>Tip:</strong> Select any other individuals you would like to give access to assigning or modifying this quiz.
				</div>
				</label>
				
				<div class="controls">
					<input type="text" id="author_name" name="fullname" size="30" autocomplete="off" style="width: 203px" />
						<?php
						$ONLOAD[] = "author_list = new AutoCompleteList({ type: 'author', url: '". ENTRADA_RELATIVE ."/api/personnel.api.php?type=facultyorstaff', remove_image: '". ENTRADA_RELATIVE ."/images/action-delete.gif'})";
						?>
						<div class="autocomplete" id="author_name_auto_complete"></div>
						<input type="hidden" id="associated_author" name="associated_proxy_ids" value="" />
						<input type="button" class="btn" id="add_associated_author" value="Add" style="vertical-align: middle" />
						<span class="content-small">(<strong>Example:</strong> <?php echo html_encode($_SESSION["details"]["lastname"].", ".$_SESSION["details"]["firstname"]); ?>)</span>
						<ul id="author_list" class="menu" style="margin-top: 15px">
							<?php
							if (is_array($PROCESSED["associated_proxy_ids"]) && !empty($PROCESSED["associated_proxy_ids"])) {
								$selected_authors = array();
                                
                                foreach ($PROCESSED["associated_proxy_ids"] as $proxy_id) {
                                    $u = User::fetchRowByID($proxy_id);
                                    if ($u->getID()) {
                                        $selected_authors[$u->getID()]["fullname"] = $u->getFullname(true);
                                    }
                                }

								foreach ($PROCESSED["associated_proxy_ids"] as $proxy_id) {
									if ($proxy_id = (int) $proxy_id) {
										if (array_key_exists($proxy_id, $selected_authors)) {
											?>
											<li class="community" id="author_<?php echo $proxy_id; ?>" style="cursor: move;"><?php echo $selected_authors[$proxy_id]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="author_list.removeItem('<?php echo $proxy_id; ?>');" class="list-cancel-image" /></li>
											<?php
										}
									}
								}
							}
							?>
						</ul>
						<input type="hidden" id="author_ref" name="author_ref" value="" />
						<input type="hidden" id="author_id" name="author_id" value="" />
				</div><!--/controls-->
			</div><!--control-group-->
			<div class="row-fluid">
				<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>'" />
				<div class="pull-right">
					<span class="content-small">After saving:</span>
					<select id="post_action" name="post_action">
						<option value="content"<?php echo (((!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"])) || ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "content")) ? " selected=\"selected\"" : ""); ?>>Return to the quiz</option>
						<?php
							$question_types = Models_Quiz_QuestionType::fetchAllRecords();
							if (!$question_types || @count($question_types) <= 1) {
								?>
								<option value="new1"<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) && ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new1") ? " selected=\"selected\"" : ""); ?>>Add a new question</option>
								<?php
							} else {
								foreach ($question_types as $question_type) {
									echo "<option value=\"new".$question_type->getQuestionTypeID()."\"".(isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) && ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new".$question_type->getQuestionTypeID()) ? " selected=\"selected\"" : "").">Add ".($quiz_record["questiontype_id"] == $question_type->getQuestionTypeID() ? "another " : "a new ").strtolower($question_type->getQuestionTypeTitle())."</option>";
								}
							}
							?>
						<option value="index"<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) && ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to quiz index</option>
					</select>
					<input type="submit" class="btn btn-primary" value="Proceed" />
				</div>
			</div>
			
			</form>
			<?php
		break;
	}
}