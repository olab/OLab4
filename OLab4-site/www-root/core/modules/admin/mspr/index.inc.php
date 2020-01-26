<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Allows administrators to edit users from the entrada_auth.user_data table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_MSPR_ADMIN"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("mspr", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	require_once("Entrada/mspr/functions.inc.php");
	
	if (isset($_GET['mode'])) {
		$mode = $_GET['mode'];
	}
	
	if (isset($_GET['year'])) {
		$year = $_GET['year'];
		if (!is_numeric($year)) {
			unset($year);
		}
	}
	
	switch($mode) {
		case "all" :
			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/mspr?all", "title" => "Attention Required" );
			
			//XXX this could end up quite slow... relies on numerous queries. Will get slower over time. Should try to replace with a single getAllAttentionRequired()
			$msprs = MSPRs::getAll();

			$att_reqs = array();
			foreach ($msprs as $mspr) {
				if ($mspr->isAttentionRequired()) {
					$att_reqs[] = $mspr;
				}
			}
			//no need to create mspr records. Only listing ones requiring attention... records obviously already created.
			if (count($att_reqs) > 0) {
			?>
			<table id="mspr-class-list" class="tableList">
				<col width="36%" />
				<col width="10%" />
				<col width="25%" />
				<col width="25%" />
				<col width="4%" />
				<thead>
					<tr>
						<td class="general">
							Student Name
						</td>
						<td class="general">
							Status
						</td>
						<td class="general">
							Submission Deadline
						</td>
						<td class="general">
							Documents
						</td>
						<td class="general">
							Edit
						</td>
						
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach($att_reqs as $mspr) {
						$status = "attention-required";
						$user = $mspr->getUser();
						$user_id = $user->getID();
					?>
					<tr class="<?php echo $status; ?>">
						<td>
							<?php
								$user = $mspr->getUser();
								echo "<a href=\"".ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$user->getID()."\">".$user->getFullname() ."</a>";
							?>
						</td>
						<td>
							<?php echo $mspr->isClosed() ? "closed" : "open"; ?>
						</td>
						<td>
							<?php 
							$cts = $mspr->getClosedTimestamp();
							if ($cts) {
								echo date("Y-m-d @ H:i",$cts); 
							}
							?>
						</td>
						<?php
							$revision = $mspr->getGeneratedTimestamp();
							if (!$revision) {
								$revisions = $mspr->getMSPRRevisions();
								if ($revisions) {
									$revision = array_shift($revisions);
								}
							}
	
							if ($revision) {
							?>
							<td>
								<a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-revisions&id=<?php echo $user_id; ?>&get=html&revision=<?php echo $revision; ?>"><img src="<?php echo ENTRADA_URL; ?>/serve-icon.php?ext=html" /> HTML</a> <a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-revisions&id=<?php echo $user_id; ?>&get=pdf&revision=<?php echo $revision; ?>"><img src="<?php echo ENTRADA_URL; ?>/serve-icon.php?ext=pdf" /> PDF</a> (<a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-revisions&id=<?php echo $user_id; ?>">revisions</a>)		
							</td>
							<td><?php if ($mspr->isClosed()) {?>
								<a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-edit&id=<?php echo $user_id; ?>&from=attention"><img src="<?php echo ENTRADA_URL; ?>/images/btn-edit.gif" alt="Edit icon" title="Manual Edit" /></a>
								<?php } ?>
							</td>
						<?php
							} else {
								echo "<td>None</td><td>&nbsp;</td>";
							} 						
						?>				
					</tr>
					<?php 
					} 
					?>
				</tbody>
			</table>
			<?php
			} else {
			?>
			<div class="display-notice"><h3>None Found</h3>No MSPRs require attention at this time.</div>
			<?php
			}
			
			break;
		case "year":
			if ($year) {
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/mspr?year=".$year, "title" => "Class of ".$year );
				
				$mspr_meta = MSPRClassData::get($year);
				
				add_mspr_admin_sidebar($year);
				
				if (!$mspr_meta) {
					//no class data set up.. create it now
					MSPRClassData::create($year,null);
					$mspr_meta = MSPRClassData::get($year);
				}
				
				$class_close = $mspr_meta->getClosedTimestamp();
				
				if (!$class_close) {
					$opt_notice = "<div class=\"display-notice\">MSPR submission deadline has not been set. It is strongly recommended that you <a href=\"".ENTRADA_URL."/admin/mspr?section=mspr-options&year=".$year."\" >set the deadline</a> in the options now.</div>";	
				}
				
				//cannot assume that the class list hasn't changed.
				
				$query = "INSERT IGNORE into `student_mspr` (`user_id`) select a.id from `".AUTH_DATABASE."`.`user_data` a 
							where a.`grad_year`=".$db->qstr($year)." and 
							a.`id` NOT IN (SELECT b.`user_id` from `student_mspr` b)";
	
				if(!$db->Execute($query)) {
					add_error("Failed to update MSPR Clas List");
					application_log("error", "Unable to update student_mspr records. Database said: ".$db->ErrorMsg());
				}
				
				$msprs = MSPRs::getYear($year);
				
				if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["showAll"])) {
					$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["showAll"] = true;
				}
				
				if ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["showAll"] == true) {
					$ONLOAD[] = "showAll();";
				} else {
					$ONLOAD[] = "hideAttentionNotRequired();";
				}
				?>
				<script>

				function showAll() {
					$("mspr-class-list").addClassName("show-all");
				}
				
				function showAllUpdate() {
					showAll();
					new Ajax.Request('<?php echo ENTRADA_URL; ?>/admin/mspr?section=api-mspr-preferences',
							{
								method:"post",
								parameters: {showAll:"showAll"}
							}
							); //unconcerned with response
				}

				function hideAttentionNotRequiredUpdate() {
					hideAttentionNotRequired();
					new Ajax.Request('<?php echo ENTRADA_URL; ?>/admin/mspr?section=api-mspr-preferences',
							{
								method:"post",
								parameters: {showAll:"hide"},
								onSuccess: function (response) {
									console.log(response);
								}
							}
							); //unconcerned with response
				}
				function hideAttentionNotRequired() {
					$("mspr-class-list").removeClassName("show-all");
				}
	
				function checkAll(event) {
					var state = Event.findElement(event).checked;
					//var state = $$("#mspr-class-list thead input[type=checkbox]").pluck("checked").any();
					$$("#mspr-class-list tbody input[type=checkbox]").reject(isDisabled).each(function (el) { el.checked=state; });
				}
	
				function areAllChecked() {
					return $$("#mspr-class-list tbody input[type=checkbox]").reject(isDisabled).pluck("checked").all();
				}
	
				function isDisabled(el) {
					return el.disabled;
				}
	
				function setCheckAll() {
					var state = areAllChecked();
					$$("#mspr-class-list thead input[type=checkbox]").each(function (el) { el.checked=state; });
				}
	
				document.observe("dom:loaded",function() { 
						$$("#mspr-class-list tbody input[type=checkbox]").invoke("observe","click",setCheckAll);
						$$("#mspr-class-list thead input[type=checkbox]").invoke("observe","click",checkAll);
					});
				
				
				</script>
				
				<h1>Manage MSPRs: Class of <?php echo $year;?></h1>
				<?php echo display_status_messages(); echo $opt_notice;?>
				<div class="instructions">
					
				</div>
				<p><strong>Submission deadline:</strong> <?php echo ($class_close ? date("F j, Y \a\\t g:i a",$class_close) : "Unset"); ?> &nbsp;&nbsp;(<a href="<?php echo ENTRADA_URL; ?>/admin/mspr?section=mspr-options&year=<?php echo $year; ?>">change</a>)</p>
				
				
				<a href="#" onclick='showAllUpdate();'>Show All</a> / <a href="#" onclick='hideAttentionNotRequiredUpdate();'>Show only those requiring attention</a>
				<form method="post" action="?section=generate">
					<input type="hidden" name="year" value="<?php echo $year; ?>" />  
					<table id="mspr-class-list" class="table table-bordered table-striped">
						<col width="3%"  />
						<col width="33%" />
						<col width="10%" />
						<col width="25%" />
						<col width="25%" />
						<col width="4%" />
						<thead>
							<tr>
								<th class="general">
									<input type="checkbox" name="all" value="all" />
								</th>
								<th class="general">
									Student Name
								</th>
								<th class="general">
									Status
								</th>
								<th class="general">
									Submission Deadline
								</th>
								<th class="general">
									Documents
								</th>
								<th class="general">
									Edit
								</th>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach($msprs as $mspr) {
							$status = ($mspr->isAttentionRequired() ? "attention-required" : "attention-not-required");
							$user = $mspr->getUser();

							if ($user) {
								$user_id = $user->getID();
								?>
								<tr class="<?php echo $status; ?>">
									<td class="general">
										<input type="checkbox" name="user_id[]" value="<?php echo $user->getID(); ?>" <?php echo ($mspr->isClosed())? "": "disabled=\"disabled\" class=\"disabled\"";?> />
									</td>
									<td>
										<?php echo "<a href=\"".ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$user_id."\">".$user->getFullname() ."</a>"; ?>
									</td>
									<td>
										<?php echo $mspr->isClosed() ? "closed" : "open"; ?>
									</td>
									<td>
										<?php
										$cts = $mspr->getClosedTimestamp();
										if ($cts) {
											echo date("Y-m-d @ H:i",$cts);
										}
										?>
									</td>
									<?php
									$revision = $mspr->getGeneratedTimestamp();
									if (!$revision) {
										$revisions = $mspr->getMSPRRevisions();
										if ($revisions) {
											$revision = array_shift($revisions);
										}
									}

									if ($revision) {
										?>
										<td>
											<a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-revisions&id=<?php echo $user_id; ?>&get=html&revision=<?php echo $revision; ?>"><img src="<?php echo ENTRADA_URL; ?>/serve-icon.php?ext=html" /> HTML</a> <a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-revisions&id=<?php echo $user_id; ?>&get=pdf&revision=<?php echo $revision; ?>"><img src="<?php echo ENTRADA_URL; ?>/serve-icon.php?ext=pdf" /> PDF</a> (<a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-revisions&id=<?php echo $user_id; ?>">revisions</a>)
										</td>
										<td>
											<?php
											if ($mspr->isClosed()) {
												?>
												<a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-edit&id=<?php echo $user_id; ?>&from=class"><img src="<?php echo ENTRADA_URL; ?>/images/btn-edit.gif" alt="Edit icon" title="Manual Edit" /></a>
												<?php
											}
											?>
										</td>
										<?php
									} else {
										echo "<td>None</td><td>&nbsp;</td>";
									}
									?>
								</tr>
								<?php
							}
						}
						?>
						</tbody>
					</table>
					<br />
					<input type="submit" class="btn btn-primary" name="generate" value="Generate Report" />
				</form><br /><br />
				<div class="instructions">Note: Only MSPRs closed to submissions will be generated.</div>
				
			<?php
			} else {
				add_error("Invalid graduating year provided.");
				display_status_messages();	
			}
			break;
		default:
			?>
			<h1>Manage MSPRs</h1>
			<?php
			$query = "SELECT DISTINCT(`grad_year`) FROM `". AUTH_DATABASE . "`.`user_data` WHERE `grad_year` IS NOT NULL AND `grad_year` <> 0 ORDER BY `grad_year` ASC";
			$results = $db->GetAll($query);
			if ($results) {
				?>
				<div class="instructions alert alert-info">
					From the options below either select a cohort to manage or click &quot;Manage All MSPRs requiring attention&quot; to view those awaiting staff approval.
				</div>
				<div class="row-fluid">
					<div class="span5">
						<div class="well mspr-box">
							<div class="inner-box">
								<form method="get" class="form-horizontal">
									<input type="hidden" name="mode" value="year"/>
									<div class="control-group">
										<label>Choose cohort to manage:</label>
										<select name="year">
											<?php
											//because we ned the current school year, we have to rig it a bit.
											$cur_year = (int)date("Y");
											if (date("n") > 8) $cur_year += 1;
											foreach ($results as $result) {
												$year = $result['grad_year'];
												echo build_option($year, $year, $year == $cur_year);
											}
											?>
										</select>
										<input type="submit" class="btn btn-primary" value="Proceed"></input>
									</div>


								</form>
							</div>
						</div>
					</div>
					<div class="span2" style="text-align:center">
						<span class="or" style="margin-top:35px">OR</span>
					</div>
					<div class="span5">
						<div class="well mspr-box" style="text-align:center;font-size:1.2em;font-weight:500">
							<div class="inner-box">
								<a href="?mode=all" style="margin-top:30px;display:inline-block">Manage All MSPRs
									requiring attention</a>
							</div>
						</div>
					</div>
				</div>
				<?php
			} else {
				echo display_notice(array($translate->_("There are not presently any learners in the system to manage MSPRs for. Please check back later.")));
			}
	}
}