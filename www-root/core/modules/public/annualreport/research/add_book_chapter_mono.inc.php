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
	// Meta information for this page.
	$PAGE_META["title"]			= "Research Add";
	$PAGE_META["description"]	= "Research portion of your annual report should be entered / located here.";
	$PAGE_META["keywords"]		= "";
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/research?section=add", "title" => "Add Book / Chapter / Monograph");
	
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["research_expand_grid"] = "book_chapter_mono_grid";
	
	echo "<h1>Add Book / Chapter / Monograph / Editorial</h1>";
	// Error Checking
	switch($STEP) {
		case 2 :
			/**
			 * Non-Required field "pubmed_id" / PubMedID Title.
			 */
			if((isset($_POST["pubmed_id"])) && ($pubmed_id = clean_input($_POST["pubmed_id"], array("notags", "trim")))) {
				$PROCESSED["pubmed_id"] = $pubmed_id;
			} else if((isset($_POST["pubmed_id_hidden"])) && ($pubmed_id = clean_input($_POST["pubmed_id_hidden"], array("notags", "trim")))) {
				$PROCESSED["pubmed_id"] = $pubmed_id;
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
			 * Non-Required field "author_list" / Author List			 
			 */
			if((isset($_POST["author_list"])) && ($author_list = clean_input($_POST["author_list"], array("notags", "trim", "utf8")))) {
				$PROCESSED["author_list"] = $author_list;
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
				if($PROCESSED["category"] == "Paper") {
					/**
					 * Required field "volume" / Volume
					 */
					if((isset($_POST["volume"])) && ($volume = clean_input($_POST["volume"], array("notags", "trim", "utf8")))) {
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
				} else if(!$STARTERROR){
					$PROCESSED["year"] = "";
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
				
				if($db->AutoExecute("ar_book_chapter_mono", $PROCESSED, "INSERT")) {
					$EVENT_ID = $db->Insert_Id();
						switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
							case "new" :
								$url = ENTRADA_URL."/annualreport/research?section=add_book_chapter_mono";
								$msg	= "You will now be redirected to add another new record; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "index" :
							default :
								$url = ENTRADA_URL."/annualreport/research";
								$msg	= "You will now be redirected to the research page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
						}

						$SUCCESS++;
						$SUCCESSSTR[]  = "You have successfully added <strong>".html_encode($PROCESSED["title"])."</strong> to the system.<br /><br />".$msg;
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";

						application_log("success", "Book / Chapter / Mono [".$EVENT_ID."] added to the system.");					

				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this Book / Chapter / Mono into the system. The MEdIT Unit was informed of this error; please try again later.";

					application_log("error", "There was an error inserting a Book / Chapter / Mono. Database said: ".$db->ErrorMsg());
				}
			} else {
				$STEP = 1;
			}
		break;
		case 1 :
			if(isset($_POST['pubmed_id'])) {
				$argv[1] = $_POST['pubmed_id'];
				$PROCESSED['pubmed_id'] = clean_input($_POST["pubmed_id"], array("notags", "trim"));
				
				if ($xml = simplexml_load_file('http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&retmax=0&usehistory=y&term=' . urlencode($argv[1]))) {
					if ($xml = simplexml_load_file("http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&retmode=xml&query_key={$xml->QueryKey}&WebEnv={$xml->WebEnv}&retstart=0&retmax=10")) {
						$docs = $xml->DocSum;
					}
				}
				
				if(!isset($xml->DocSum->Item[3]) || $xml->DocSum->Item[3] == "") {
		
					$error_messages[] = 'Invalid ID / no results found';
					echo '<br>';
					echo display_error($error_messages);
					
					$authorList 	= "";
					$source	= "";
				} else {
					foreach($xml->DocSum->Item[3]->Item as $author) {
						if(isset($authorList)) {
							$authorList .= ", " . utf8_decode($author);
						} else {
							$authorList = utf8_decode($author);
						}
					}
					
					if(isset($xml->DocSum->Item[15]) && $xml->DocSum->Item[15] == "ppublish+epublish") {
						$source = utf8_decode($xml->DocSum->Item[22]);
					} else {
						if($xml->DocSum->Item[21] != "0") {
							$source = utf8_decode($xml->DocSum->Item[21]);
						} else {
							$source = utf8_decode($xml->DocSum->Item[22]);
						}
					}
				}
			}
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
			if($ERROR) {
				echo display_error();
			}
			
			$HEAD[]	= "<script type=\"text/javascript\">
				
				function setPubID(pubmed_id_value) {
					$('pubmed_id_hidden').value = pubmed_id_value;
				}
				
				</script>\n";
			?>
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="book_chapter_mono">
				<colgroup>
					<col style="width: 100%" />
				</colgroup>
				<tr>
					<td>
						<div id="pubmed_id_div" style="display: block">
							<form action="<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_book_chapter_mono&step=1" method="post">	
							<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Book / Chapter / Monograph / Editorial">
								<colgroup>
									<col style="width: 3%" />
									<col style="width: 20%" />
									<col style="width: 77%" />
								</colgroup>
								<tr>
									<td colspan="3"><h2>PubMed ID</h2></td>
								</tr>
								<tr>
									<td></td>
									<td style="vertical-align: top"><label for="pubmed_id" class="form-nrequired">Enter PubMed ID</label></td>
									<td><input type="text" id="pubmed_id" name="pubmed_id" value="<?php echo html_encode($PROCESSED["pubmed_id"]); ?>" maxlength="255" style="width: 25%" />
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" class="btn" value="Auto Fill" />
									<div class="content-small"><strong>Note:</strong> Click Auto Fill to have the fields populated from PubMed.</div>
									</td>
								</tr>
							</table>
							</form>
						</div>
						<div id="pubmed_id_results" style="display: block">
							<form action="<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_book_chapter_mono&step=2" method="post">
							<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Event">
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
								<td style="vertical-align: top"><label for="title" class="form-required">Title</label></td>
								<td><input type="text" id="title" name="title" value="<?php echo (isset($PROCESSED["title"]) ? utf8_decode($PROCESSED["title"]) : utf8_decode($xml->DocSum->Item[5])); ?>" maxlength="255" style="width: 95%" /></td>
							</tr>
							<tr>
								<td></td>
								<td style="vertical-align: top"><label for="source" class="form-required">Source</label></td>
								<td><input type="text" id="source" name="source" value="<?php echo (isset($PROCESSED["source"]) ? utf8_decode($PROCESSED["source"]) : $source); ?>" maxlength="255" style="width: 95%" /></td>
							</tr>
							<tr>
								<td></td>
								<td style="vertical-align: top"><label for="author_list" class="form-required">Author List</label></td>
								<td><input type="text" id="author_list" name="author_list" value="<?php echo (isset($PROCESSED["author_list"]) ? utf8_decode($PROCESSED["author_list"]) : $authorList); ?>" maxlength="255" style="width: 95%" /></td>
							</tr>
							<tr>
								<td></td>
								<td style="vertical-align: top"><label for="editor_list" class="form-nrequired">Editor List</label></td>
								<td><input type="text" id="editor_list" name="editor_list" value="<?php echo (isset($PROCESSED["editor_list"]) ? utf8_decode($PROCESSED["editor_list"]) : ""); ?>" maxlength="255" style="width: 95%" /></td>
							</tr>
							<tr>
								<input type="hidden" id="pubmed_id_hidden" name="pubmed_id_hidden" value="" />
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
									if($xml->DocSum->Item[0] != "") {	
										$pubDate 	= explode(" ", $xml->DocSum->Item[0]);
										$month	 	= strtotime($pubDate[1]);
										$month 		= (int)date("m", $month);
										$year		= $pubDate[0];
									} elseif (isset($PROCESSED["month"])) {
										$month 	= $PROCESSED["month"];
										$year	= $PROCESSED["year"];
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
							</tr>
							<tr id="puburl" style="display: <?php echo $urldisplay; ?>">
								<td></td>
								<td style="vertical-align: top"><label for="epub_url" class="form-nrequired">URL</label></td>
								<td><input type="text" id="epub_url" name="epub_url" value="<?php echo (isset($PROCESSED["epub_url"]) ? html_encode($PROCESSED["epub_url"]) : ""); ?>" maxlength="255" style="width: 95%" /></td>
							</tr>
							<tr id="volume" style="display: <?php echo $display; ?>">
								<td></td>
								<td style="vertical-align: top"><label for="volume" class="form-required">Volume</label></td>
								<td><input type="text" id="volume" name="volume" value="<?php echo (isset($PROCESSED["volume"]) ? html_encode($PROCESSED["volume"]) : utf8_decode($xml->DocSum->Item[6])); ?>" maxlength="10" style="width: 15%" /></td>
							</tr>
							<tr id="edition" style="display: <?php echo $display; ?>">
								<td></td>
								<td style="vertical-align: top"><label for="edition" class="form-nrequired">Edition</label></td>
								<td><input type="text" id="edition" name="edition" value="<?php echo (isset($PROCESSED["edition"]) ? html_encode($PROCESSED["edition"]) : utf8_decode($xml->DocSum->Item[7])); ?>" maxlength="10" style="width: 15%" /></td>
							</tr>
							<tr id="pages" style="display: <?php echo $display; ?>">
								<td></td>
								<td style="vertical-align: top"><label for="pages" class="form-required">Pages</label></td>
								<td><input type="text" id="pages" name="pages" value="<?php echo (isset($PROCESSED["pages"]) ? html_encode($PROCESSED["pages"]) : utf8_decode($xml->DocSum->Item[8])); ?>" maxlength="10" style="width: 15%" /></td>
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
											<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/research/'" />
										</td>
										<td style="width: 75%; text-align: right; vertical-align: middle">
											<span class="content-small">After saving:</span>
											<select id="post_action" name="post_action">							
											<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "add") ? " selected=\"selected\"" : ""); ?>>Add More Research</option>
											<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to Research list</option>
											</select>
											<input type="submit" class="btn btn-primary" onclick="setPubID('<?php echo $PROCESSED["pubmed_id"];?>');" value="Save" />
										</td>
									</tr>
									</table>
								</td>
							</tr>
							</table>
							</form>
						</div>
					</td>
				</tr>
			</table>
			</form>
			<br /><br />
			<?php
		break;
	}
}
?>