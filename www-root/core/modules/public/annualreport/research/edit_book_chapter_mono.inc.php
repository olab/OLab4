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
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if((!defined("PARENT_INCLUDED")) || (!defined("IN_ANNUAL_REPORT"))) {
	exit;
} else if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : ""));
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('annualreport', 'update')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$BOOK_CHAPTER_MONO_ID = $_GET["rid"];
	
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["research_expand_grid"] = "book_chapter_mono_grid";
	
	if($BOOK_CHAPTER_MONO_ID) {
		$query	= "SELECT * FROM `ar_book_chapter_mono` WHERE `book_chapter_mono_id`=".$db->qstr($BOOK_CHAPTER_MONO_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/research?section=edit_book_chapter_mono", "title" => "Edit Book / Chapter / Monograph / Editorial");
			
			echo "<h1>Edit Book / Chapter / Monograph / Editorial</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :					
					/**
					 * Non-Required field "pubmed_id" / PubMedID Title.
					 */
					if((isset($_POST["pubmed_id"])) && ($pubmed_id = clean_input($_POST["pubmed_id"], array("notags", "trim")))) {
						$PROCESSED["pubmed_id"] = $pubmed_id;
					} else {
						$PROCESSED["pubmed_id"] = "";	
					}
					/**
					 * Required field "title" / Title
					 */
					if((isset($_POST["title"])) && ($title = clean_input($_POST["title"], array("notags", "trim", "utf8")))) {
						$PROCESSED["title"] = $title;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Title</strong> field is required.";
					}
					/**
					 * Required field "source" / Source			 
					 */
					if((isset($_POST["source"])) && ($source = clean_input($_POST["source"], array("notags", "trim", "utf8")))) {
						$PROCESSED["source"] = $source;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Source</strong> field is required.";
					}
					/**
					 * Required field "author_list" / Author List			 
					 */
					if((isset($_POST["author_list"])) && ($author_list = clean_input($_POST["author_list"], array("notags", "trim", "utf8")))) {
						$PROCESSED["author_list"] = $author_list;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Author List</strong> field is required.";
					}
					/**
					 * Required field "editor_list" / Editor List			 
					 */
					if((isset($_POST["editor_list"])) && ($editor_list = clean_input($_POST["editor_list"], array("notags", "trim", "utf8")))) {
						$PROCESSED["editor_list"] = $editor_list;
					}
					/**
					 * Required field "category" / Category
					 */
					if((isset($_POST["category"])) && ($category = clean_input($_POST["category"], array("notags", "trim")))) {
						$PROCESSED["category"] = $category;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Category</strong> field is required.";
					}
					/**
					 * Non-Required field "epub_url" / URL			 
					 */
					if($PROCESSED["category"] != "Paper") {
						if((isset($_POST["epub_url"])) && ($epub_url = clean_input($_POST["epub_url"], array("url", "trim")))) {
							$PROCESSED["epub_url"] = $epub_url;
						} else {
							$PROCESSED["epub_url"] = "";	
						}
					} else {
						$PROCESSED["epub_url"] = "";
					}
					/**
					 * Required field "status" / Status
					 */
					if((isset($_POST["status"])) && ($status = clean_input($_POST["status"], array("notags", "trim")))) {
						$PROCESSED["status"] = $status;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Status</strong> field is required.";
					}
					if(isset($PROCESSED["status"]) && $PROCESSED["status"] == "Published") {
						/**
						 * Required field "month" / Date			 
						 */
						if((isset($_POST["month"])) && ($month= clean_input($_POST["month"], array("int")))) {				
							$PROCESSED["month"] = $month;
						} else {
							$ERROR++;
							$ERRORSTR[] = "Both <strong>Date</strong> fields are required.";
							$STARTERROR = true;
						}
						/**
						 * Required field "year" / Date			 
						 */
						if((isset($_POST["year"])) && ($year= clean_input($_POST["year"], array("int")))) {
							$PROCESSED["year"] = $year;
						} else if(!$STARTERROR){
							$ERROR++;
							$ERRORSTR[] = "Both <strong>Date</strong> fields are required.";
							$STARTERROR = true;
						}
						if(!($STARTERROR)) {
							$PROCESSED["status_date"] = $month.$year;
						}
						if($PROCESSED["category"] == "Paper") {
							/**
							 * Required field "volume" / Volume
							 */
							if((isset($_POST["volume"])) && ($volume = clean_input($_POST["volume"], array("notags", "trim", "utf8"))) && $_POST["volume"] != "") {
								$PROCESSED["volume"] = $volume;
							} else {
								$ERROR++;
								$ERRORSTR[] = "The <strong>Volume</strong> field is required.";
							}
							/**
							 * Required field "pages" / Pages
							 */
							if((isset($_POST["pages"])) && ($pages = clean_input($_POST["pages"], array("notags", "trim", "utf8")))) {
								$PROCESSED["pages"] = $pages;
							} else {
								$ERROR++;
								$ERRORSTR[] = "The <strong>Pages</strong> field is required.";
							}
						} else {
							$PROCESSED["volume"] = "";
							$PROCESSED["pages"] = "";
						}
					} else {
						/**
						 * Required field "month" / Date			 
						 */
						if((isset($_POST["month"])) && ($month= clean_input($_POST["month"], array("int")))) {				
							$PROCESSED["month"] = $month;
						} else {
							$PROCESSED["month"] = "";
						}
						/**
						 * Required field "year" / Date			 
						 */
						if((isset($_POST["year"])) && ($year= clean_input($_POST["year"], array("int")))) {
							$PROCESSED["year"] = $year;
						} else {
							$PROCESSED["year"] = "";
						}
						if(isset($month) && isset($year)) {
							$PROCESSED["status_date"] = $month.$year;
						}
						if($PROCESSED["category"] == "Paper") {
							/**
							 * Required field "volume" / Volume
							 */
							if((isset($_POST["volume"])) && ($volume = clean_input($_POST["volume"], array("notags", "trim", "utf8")))) {
								$PROCESSED["volume"] = $volume;
							} else {
								$PROCESSED["volume"] = "";
							}
							/**
							 * Required field "pages" / Pages
							 */
							if((isset($_POST["pages"])) && ($pages = clean_input($_POST["pages"], array("notags", "trim", "utf8")))) {
								$PROCESSED["pages"] = $pages;
							} else {
								$PROCESSED["pages"] = "";
							}
						} else {
							$PROCESSED["volume"] = "";
							$PROCESSED["pages"] = "";
						}
					}
					/**
					 * Non-Required field "edition" / Edition
					 */
					if((isset($_POST["edition"])) && ($edition = clean_input($_POST["edition"], array("notags", "trim", "utf8"))) && $PROCESSED["category"] == "Paper") {
						$PROCESSED["edition"] = $edition;
					} else {
						$PROCESSED["edition"] = "";
					}
					/**
					 * Required field "role" / Role
					 */
					if((isset($_POST["role_id"])) && ($role_id = clean_input($_POST["role_id"], array("notags", "trim")))) {
						$PROCESSED["role_id"] = $role_id;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Role</strong> field is required.";
					}
					/**
					 * Required field "type" / Type
					 */
					if((isset($_POST["type_id"])) && ($type_id = clean_input($_POST["type_id"], array("notags", "trim")))) {
						$PROCESSED["type_id"] = $type_id;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Type</strong> field is required.";
					}
					/**
					 * Non-Required field "group" / Research Area
					 */
					if((isset($_POST["group_id"])) && ($group_id = clean_input($_POST["group_id"], array("notags", "trim")))) {
						$PROCESSED["group_id"] = $group_id;
					} else {
						$PROCESSED["group_id"] = "";	
					}
					/**
					 * Non-Required field "hospital_id" / Hospital
					 */
					if((isset($_POST["hospital_id"])) && ($hospital_id = clean_input($_POST["hospital_id"], array("notags", "trim")))) {
						$PROCESSED["hospital_id"] = $hospital_id;
					} else {
						$PROCESSED["hospital_id"] = "";	
					}
					/**
					 * Required field "year_reported" / Year Reported
					 */
					if((isset($_POST["year_reported"])) && ($year_reported = clean_input($_POST["year_reported"], array("notags", "trim")))) {
						$PROCESSED["year_reported"] = $year_reported;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Year Reported</strong> field is required.";
					}
					
					if(isset($_POST["post_action"])) {
						switch($_POST["post_action"]) {							
							case "new" :
								$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new";
							break;
							case "index" :
							default :
								$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
							break;
						}
					} else {
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
					}
					
					if(!$ERROR) {
						$PROCESSED["updated_date"]	= time();
						$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();
						$PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
						$PROCESSED["status_date"]	= $month.$year;

						if($db->AutoExecute("ar_book_chapter_mono", $PROCESSED, "UPDATE", "`book_chapter_mono_id`=".$db->qstr($BOOK_CHAPTER_MONO_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url = ENTRADA_URL."/annualreport/research?section=add_book_chapter_mono";
										$msg	= "You will now be redirected to add more Book / Chapter / Monograph / Editorial; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
									case "index" :
									default :
										$url = ENTRADA_URL."/annualreport/research";
										$msg	= "You will now be redirected to the research page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
								}
		
								$SUCCESS++;
								$SUCCESSSTR[]  = "You have successfully edited <strong>".html_encode($PROCESSED["title"])."</strong> in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
		
								application_log("success", "Edited Book / Chapter / Monograph / Editorial [".$BOOK_CHAPTER_MONO_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Book / Chapter / Monograph / Editorial record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Book / Chapter / Monograph / Editorial. Database said: ".$db->ErrorMsg());
						}
					} else {
						$STEP = 1;
					}
				break;
				case 1 :
				default :
					continue;
				break;
			}
			
			// Display Content
			switch($STEP) {
				case 2 :
					if($SUCCESS) {
						echo display_success();
					}
					if($NOTICE) {
						echo display_notice();
					}
					if($ERROR) {
						echo display_error();
					}
				break;
				case 1 :
				default :
					if(!isset($PROCESSED) || count($PROCESSED) <= 0)
					{
						$bookChapterMonoQuery = "SELECT * FROM `ar_book_chapter_mono` WHERE `book_chapter_mono_id` =".$db->qstr($BOOK_CHAPTER_MONO_ID);
						$PROCESSED = $db->GetRow($bookChapterMonoQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/research?section=edit_book_chapter_mono&amp;step=2&amp;rid=<?php echo $BOOK_CHAPTER_MONO_ID;?>"" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Event">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<tr>
						<td colspan="3"><h2>Details</h2></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="pubmed_id" class="form-nrequired">PubMed ID</label></td>
						<td><input type="text" id="pubmed_id" name="pubmed_id" value="<?php echo (isset($PROCESSED["pubmed_id"]) ? html_encode($PROCESSED["pubmed_id"]) : ""); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="title" class="form-required">Title</label></td>
						<td><input type="text" id="title" name="title" value="<?php echo (isset($PROCESSED["title"]) ? utf8_decode($PROCESSED["title"]) : ""); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="source" class="form-required">Source</label></td>
						<td><input type="text" id="source" name="source" value="<?php echo (isset($PROCESSED["source"]) ? utf8_decode($PROCESSED["source"]) : ""); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="author_list" class="form-required">Author List</label></td>
						<td><input type="text" id="author_list" name="author_list" value="<?php echo (isset($PROCESSED["author_list"]) ? utf8_decode($PROCESSED["author_list"]) : ""); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="editor_list" class="form-nrequired">Editor List</label></td>
						<td><input type="text" id="editor_list" name="editor_list" value="<?php echo (isset($PROCESSED["editor_list"]) ? utf8_decode($PROCESSED["editor_list"]) : ""); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="category" class="form-required">Publication Category</label></td>
						<td>
						<?php
						if($PROCESSED["category"] == "E-Pub")
						{
							$display = "none;";
							$urldisplay = "table-row;";
							echo "<input type=\"radio\" id=\"category_paper\" name=\"category\" value=\"Paper\"/><label for=\"category_paper\" class=\"form-nrequired\"> Paper</label><br>
							<input type=\"radio\" id=\"category_epub\" name=\"category\" value=\"E-Pub\" CHECKED/><label for=\"category_epub\" class=\"form-nrequired\"> E-Pub</label>";
						}
						else
						{
							$display = "table-row;";
							$urldisplay = "none;";
							echo "<input type=\"radio\" id=\"category_paper\" name=\"category\" value=\"Paper\"/ CHECKED><label for=\"category_paper\" class=\"form-nrequired\"> Paper</label><br>
							<input type=\"radio\" id=\"category_epub\" name=\"category\" value=\"E-Pub\"/><label for=\"category_epub\" class=\"form-nrequired\"> E-Pub</label>";
						}
						?>
						<script>
					    	jQuery(function($) {
									jQuery("input[name=category]:radio").change(function () {
										if(jQuery('#category_paper').attr("checked")) {
											jQuery('#volume').show();
											jQuery('#edition').show();
											jQuery('#pages').show();
											jQuery('#puburl').hide();
										} else {
											jQuery('#volume').hide();
											jQuery('#edition').hide();
											jQuery('#pages').hide();
											jQuery('#puburl').show();
										}
									}).trigger('change');
					    	});
						</script>
						</td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="month" class="form-required">Date</label></td>
						<td><select name="month" id="month" style="vertical-align: middle">
						<option value = ""></option>
						<?php					
							if (isset($PROCESSED["status_date"]) && strlen($PROCESSED["status_date"]) == 5) {
								$month 	= substr($PROCESSED["status_date"], 0, 1);
								$year 	= substr($PROCESSED["status_date"], 1, 4);
							} else if(isset($PROCESSED["status_date"]) && strlen($PROCESSED["status_date"]) == 6) {
								$month 	= substr($PROCESSED["status_date"], 0, 2);
								$year 	= substr($PROCESSED["status_date"], 2, 4);
							} else {
								$month 	= "";
								$year 	= "";
							}
							
							for($i=1; $i<13; $i++)
							{
								echo "<option value=\"".$i."\"".(($month == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
							}
							echo "</select> /&nbsp
							<select name=\"year\" id=\"year\" style=\"vertical-align: middle\">
							<option value = \"\"></option>";
							
							for($i=$AR_PAST_YEARS; $i<$AR_FUTURE_YEARS; $i++)
							{
								echo "<option value=\"".$i."\"".(($year == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
							}
							echo "</select>";
						?>
						</td>
					</tr><tr id="puburl" style="display: <?php echo $urldisplay; ?>">
						<td></td>
						<td style="vertical-align: top"><label for="epub_url" class="form-nrequired">URL</label></td>
						<td><input type="text" id="epub_url" name="epub_url" value="<?php echo (isset($PROCESSED["epub_url"]) ? html_encode($PROCESSED["epub_url"]) : ""); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr id="volume" style="display: <?php echo $display; ?>">
						<td></td>
						<td style="vertical-align: top"><label for="volume" class="form-required">Volume</label></td>
						<td><input type="text" id="volume" name="volume" value="<?php echo (isset($PROCESSED["volume"]) ? utf8_decode($PROCESSED["volume"]) : ""); ?>" maxlength="10" style="width: 15%" /></td>
					</tr>
					<tr id="edition" style="display: <?php echo $display; ?>">
						<td></td>
						<td style="vertical-align: top"><label for="edition" class="form-nrequired">Edition</label></td>
						<td><input type="text" id="edition" name="edition" value="<?php echo (isset($PROCESSED["edition"]) ? utf8_decode($PROCESSED["edition"]) : ""); ?>" maxlength="10" style="width: 15%" /></td>
					</tr>
					<tr id="pages" style="display: <?php echo $display; ?>">
						<td></td>
						<td style="vertical-align: top"><label for="pages" class="form-required">Pages</label></td>
						<td><input type="text" id="pages" name="pages" value="<?php echo (isset($PROCESSED["pages"]) ? utf8_decode($PROCESSED["pages"]) : ""); ?>" maxlength="10" style="width: 15%" /></td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="role_id" class="form-required">Role</label></td>
						<td><select name="role_id" id="role_id" style="vertical-align: middle; width: 35%">
						<option value = ""></option>
						<?php
							$roles = getPublicationRoles();
							
							foreach($roles as $role) {
								echo "<option value=\"".$role["role_id"]."\"".((isset($PROCESSED["role_id"]) && $PROCESSED["role_id"] == $role["role_id"]) ? " selected=\"selected\"" : "").">".$role["role_description"]."</option>\n";
							}
						?>
						</select>
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="type_id" class="form-required">Type</label></td>
						<td><select name="type_id" id="type_id" style="vertical-align: middle; width: 35%">
						<option value = ""></option>
						<?php
							$types = getPublicationTypesSpecific(array("Chapter", "Complete Book", "Monograph", "Editorial"));
							
							foreach($types as $type) {
								echo "<option value=\"".$type["type_id"]."\"".((isset($PROCESSED["type_id"]) && $PROCESSED["type_id"] == $type["type_id"]) ? " selected=\"selected\"" : "").">".$type["type_description"]."</option>\n";
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
						<td style="vertical-align: top"><label for="status" class="form-required">Status</label></td>
						<td>
						<?php
							if($PROCESSED["status"] == "Submitted")
							{
								echo "<input type=\"radio\" id=\"status_sub\" name=\"status\" value=\"Submitted\"/ CHECKED><label for=\"status_sub\" class=\"form-nrequired\"> Submitted</label><br>
								<input type=\"radio\" id=\"status_pre\" name=\"status\" value=\"In Press\"/><label for=\"status_pre\" class=\"form-nrequired\"> In Press</label><br>
								<input type=\"radio\" id=\"status_pub\" name=\"status\" value=\"Published\"/><label for=\"status_pub\" class=\"form-nrequired\"> Published</label><br>";
							}
							elseif($PROCESSED["status"] == "In Press")
							{
								echo "<input type=\"radio\" id=\"status_sub\" name=\"status\" value=\"Submitted\"/><label for=\"status_sub\" class=\"form-nrequired\"> Submitted</label><br>
								<input type=\"radio\" id=\"status_pre\" name=\"status\" value=\"In Press\"/ CHECKED><label for=\"status_pre\" class=\"form-nrequired\"> In Press</label><br>
								<input type=\"radio\" id=\"status_pub\" name=\"status\" value=\"Published\"/><label for=\"status_pub\" class=\"form-nrequired\"> Published</label><br>";
							}
							else
							{
								echo "<input type=\"radio\" id=\"status_sub\" name=\"status\" value=\"Submitted\"/><label for=\"status_sub\" class=\"form-nrequired\"> Submitted</label><br>
								<input type=\"radio\" id=\"status_pre\" name=\"status\" value=\"In Press\"/><label for=\"status_pre\" class=\"form-nrequired\"> In Press</label><br>
								<input type=\"radio\" id=\"status_pub\" name=\"status\" value=\"Published\"/ CHECKED><label for=\"status_pub\" class=\"form-nrequired\"> Published</label><br>";
							}
						?>
						</td>
					</tr>
					<?php if($ENTRADA_USER->getClinical()) { ?>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="group_id" class="form-nrequired">Research Area</label></td>
						<td><select name="group_id" id="group_id" style="vertical-align: middle; width: 55%">
						<option value = ""></option>
						<?php
							$groups = getPublicationGroups();
							
							foreach($groups as $group) {
								echo "<option value=\"".$group["group_id"]."\"".((isset($PROCESSED["group_id"]) && $PROCESSED["group_id"] == $group["group_id"]) ? " selected=\"selected\"" : "").">".$group["focus_group"]."</option>\n";
							}
						?>
						</select>
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="hospital_id" class="form-nrequired">Hospital</label></td>
						<td><select name="hospital_id" id="hospital_id" style="vertical-align: middle; width: 55%">
						<option value = ""></option>
						<?php
							$hospitals = getPublicationHospitals();
							
							foreach($hospitals as $hospital_id) {
								echo "<option value=\"".$hospital_id["hosp_id"]."\"".((isset($PROCESSED["hospital_id"]) && $PROCESSED["hospital_id"] == $hospital_id["hosp_id"]) ? " selected=\"selected\"" : "").">".$hospital_id["hosp_desc"]."</option>\n";
							}
						?>
						</select>
						</td>
					</tr>
					<?php } ?>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="year_reported" class="form-required">Report Year</label></td>
						<td><select name="year_reported" id="year_reported" style="vertical-align: middle">
						<?php
							for($i=$AR_PAST_YEARS; $i<=$AR_FUTURE_YEARS; $i++)
							{
								if(isset($PROCESSED["year_reported"]) && $PROCESSED["year_reported"] != '')
								{
									$defaultYear = $PROCESSED["year_reported"];
								}
								else
								{
									$defaultYear = $AR_CUR_YEAR;
								}
								echo "<option value=\"".$i."\"".(($defaultYear == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
							}
							echo "</select>";
						?>
						</td>
					</tr>
					<tr>
						<td colspan="3" style="padding-top: 25px">
							<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
							<tr>
								<td style="width: 25%; text-align: left">
									<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/research'" />
								</td>
								<td style="width: 75%; text-align: right; vertical-align: middle">
									<span class="content-small">After saving:</span>
									<select id="post_action" name="post_action">							
									<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "add") ? " selected=\"selected\"" : ""); ?>>Add More Research</option>
									<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to Research list</option>
									</select>
									<input type="submit" class="btn btn-primary" value="Save" />
								</td>
							</tr>
							</table>
						</td>
					</tr>
					</table>
					</form>
					<br /><br />
					<?php
				break;
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to edit a Book / Chapter / Monograph / Editorial record you must provide a valid Book / Chapter / Monograph / Editorial record identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid Book / Chapter / Monograph / Editorial record identifer when attempting to edit a Book / Chapter / Monograph / Editorial record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a Book / Chapter / Monograph / Editorial record you must provide the Book / Chapter / Monograph / Editorial record identifier.";

		echo display_error();

		application_log("notice", "Failed to provide Book / Chapter / Monograph / Editorial record identifer when attempting to edit a Book / Chapter / Monograph / Editorial record.");
	}
}
?>