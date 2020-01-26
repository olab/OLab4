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
	$PRIZES_ID = $_GET["rid"];
	if($PRIZES_ID) {
		$query	= "SELECT * FROM `ar_prizes` WHERE `prizes_id`=".$db->qstr($PRIZES_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/prizes?section=edit_prize", "title" => "Edit Prizes, Honours and Awards");
			
			// This grid should be expanded upon redirecting back to the prizes index.
			$_SESSION["self_expand_grid"] = "prizes_grid";
			
			echo "<h1>Edit Research</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :
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
					 * Required field "prize_type" / Type of Award / Prize
					 */
					if((isset($_POST["prize_type"])) && ($prize_type = clean_input($_POST["prize_type"], array("notags", "trim")))) {
						$PROCESSED["prize_type"] = $prize_type;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Type of Award / Prize</strong> field is required.";
					}
					/**
					 * Required field "description" / Description			 
					 */
					if((isset($_POST["description"])) && ($description = clean_input($_POST["description"], array("notags", "trim")))) {
						$PROCESSED["description"] = $description;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Description</strong> field is required.";
					}
					/**
					 * Required field "year_reported" / Year Reported.
					 */
					if((isset($_POST["year_reported"])) && ($year_reported = clean_input($_POST["year_reported"], array("int")))) {
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
						
						if($db->AutoExecute("ar_prizes", $PROCESSED, "UPDATE", "`prizes_id`=".$db->qstr($PRIZES_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url 	= ENTRADA_URL."/annualreport/prizes?section=add_prize";
										$msg	= "You will now be redirected to add another record; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
									case "index" :
									default :
										$url 	= ENTRADA_URL."/annualreport/prizes";
										$msg	= "You will now be redirected to the Prizes, Honours and Awards page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
								}
		
								$SUCCESS++;
								$SUCCESSSTR[]  = "You have successfully edited <strong>".html_encode($PROCESSED["category"])."</strong> in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
		
								application_log("success", "Edited Grant [".$PRIZES_ID."] added to the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem inserting this record into the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Prizes, Honours and Awards. Database said: ".$db->ErrorMsg());
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
						$prizesQuery = "SELECT * FROM `ar_prizes` WHERE `prizes_id` = ".$db->qstr($PRIZES_ID);
						$prizesResult = $db->GetRow($prizesQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/prizes?section=edit_prize&amp;step=2&amp;rid=<?php echo $PRIZES_ID;?>" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Prizes, Honours and Awards">
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
						<td><label for="category" class="form-required">Category</label></td>
						<td><select name="category" id="category" style="vertical-align: middle">
						<option value=""></option>
						<?php
							$prizeCategoryArray = getPrizeCategories();
							foreach($prizeCategoryArray as $prizeCategoryListValue) {
								echo "<option value=\"".$prizeCategoryListValue["prize_category"]."\"".((($prizesResult["category"] == $prizeCategoryListValue["prize_category"]) || ($PROCESSED["category"] == $prizeCategoryListValue["prize_category"])) ? " selected=\"selected\"" : "").">".html_encode($prizeCategoryListValue["prize_category"])."</option>\n";
							}
						?>
						</select>
						</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="prize_type" class="form-required">Type of Award / Prize</label></td>
						<td><select name="prize_type" id="prize_type" style="vertical-align: middle">
						<option value=""></option>
						<?php
							$prizeTypeArray = getPrizeTypes();
							foreach($prizeTypeArray as $prizeTypeListValue) {
								echo "<option value=\"".$prizeTypeListValue["prize_type"]."\"".((($prizesResult["prize_type"] == $prizeTypeListValue["prize_type"]) || ($PROCESSED["prize_type"] == $prizeTypeListValue["prize_type"])) ? " selected=\"selected\"" : "").">".html_encode($prizeTypeListValue["prize_type"])."</option>\n";
							}
						?>
						</select>
						</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="description" class="form-required">Description</label></td>				
						<td><textarea id="description" name="description" style="width: 95%" rows="4"><?php echo ((isset($prizesResult["description"])) ? html_encode($prizesResult["description"]) : html_encode($PROCESSED["description"])); ?></textarea></td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="year_reported" class="form-required">Report Year</label></td>
						<?php
						if((isset($PROCESSED["year_reported"]) && $PROCESSED["year_reported"] != "")) {
							displayARYearReported($PROCESSED["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, true);
						} else {
							displayARYearReported($prizesResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
						}
						?>
					</tr>
					<tr>
						<td colspan="3" style="padding-top: 25px">
							<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
							<tr>
								<td style="width: 25%; text-align: left">
									<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/prizes'" />
								</td>
								<td style="width: 75%; text-align: right; vertical-align: middle">
									<span class="content-small">After saving:</span>
									<select id="post_action" name="post_action">							
									<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to Prizes / Awards</option>
									<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "add") ? " selected=\"selected\"" : ""); ?>>Add More Prizes / Awards</option>									
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
			$ERRORSTR[] = "In order to edit a Prizes / Awards you must provide a valid Prizes / Awards identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid Prizes / Awards identifer when attempting to edit a Prizes / Awards record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a Prizes / Awards you must provide the Prizes / Awards identifier.";

		echo display_error();

		application_log("notice", "Failed to provide Prizes / Awards identifer when attempting to edit a Prizes / Awards record.");
	}
}
?>