<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Gives community administrators the ability to list all of the pages within a
 * particular community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/


if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_PAGES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if (($LOGGED_IN) && (!$COMMUNITY_MEMBER)) {
	$NOTICE++;
	$NOTICESTR[] = "You are not currently a member of this community, <a href=\"".ENTRADA_URL."/communities?section=join&community=".$COMMUNITY_ID."&step=2\" style=\"font-weight: bold\">want to join?</a>";

	echo display_notice();
}

Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/scriptaculous/effects.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/scriptaculous/dragdrop.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/scriptaculous/sortable_tree.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
/**
 * Ensure that the selected community is editable by you.
 */
if ($COMMUNITY_ID) {
	$query				= "SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `community_active` = '1'";
	$community_details	= $db->GetRow($query);
	if ($community_details) {
		$BREADCRUMB[]		= array("url" => ENTRADA_URL."/community".$community_details["community_url"], "title" => limit_chars($community_details["community_title"], 50));
		$BREADCRUMB[]		= array("url" => ENTRADA_URL."/communities?".replace_query(array("section" => "pages", "step" => "", "action" => "", "page" => "")), "title" => "Manage Pages");
		$query	= "	SELECT * FROM `community_members`
					WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
					AND `member_active` = '1'
					AND `member_acl` = '1'";
		$result	= $db->GetRow($query);
		if ($result) {
			?>
			<h1>Manage Pages</h1>
			<div style="float: right">
				<ul class="page-action" style="margin-bottom: 10px">
					<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL; ?>:pages?section=add">Add New Page</a></li>
				</ul>
			</div>
			<div style="clear: both"></div>
			<?php
			$query	= "SELECT COUNT(*) AS `total_pages` FROM `community_pages` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_url` != '' AND `page_active` = '1'";
			$result	= $db->GetRow($query);
			if (($result) && ($result["total_pages"] > 0)) {
				?>
				<form class="manage-pages-wrap" action="<?php echo COMMUNITY_URL.$community_details["community_url"].":pages?".replace_query(array("action" => "delete", "step" => 1)); ?>" method="post">
					<table class="table manage-pages " summary="List of Pages">
						<colgroup>
							<col style="width: 22px" />
							<col style="width: 100%" />
						</colgroup>
						<tfoot>
							<tr>
								<td colspan="2" style="border-top: 0;" class="manage-page-controls">
									<input type="submit" id="delete_pages_button" class="btn btn-danger" value="Delete Selected" />
									<input type="button" id="reorder_pages_button" class="btn" onclick="toggleSorting();" value="Reorder Pages">
								</td>
							</tr>
						</tfoot>
						<!--<thead>
							<tr>
								<td colspan="2">Community Pages</td>
							</tr>
						</thead>-->
						<tbody>
							<tr>
								<td class="community-page-list home">
										<a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":pages?".replace_query(array("action" => "edit", "step" => 1, "page" => "home"))?>" style="font-weight: bold">
										<?php
											$home_title = $db->GetOne("SELECT `menu_title` FROM `community_pages` WHERE `community_id` =".$db->qstr($COMMUNITY_ID)." AND `page_url` = ''");
											echo (isset($home_title) && ($home_title != "") ? $home_title : "Home");
										?>
										</a>
								</td>
							</tr>
							<tr>
								<td colspan="2" class="page-lists-style">
							<?php echo communities_pages_inlists(0, 0, array('id'=>'pagelists'), (isset($COMMUNITY_LOCKED_PAGE_IDS) && $COMMUNITY_LOCKED_PAGE_IDS) ? $COMMUNITY_LOCKED_PAGE_IDS : array()); ?>
								</td>
							</tr>
						</tbody>
					</table>
				</form>
				<form class="managae-pages-wrap" action="<?php echo COMMUNITY_URL.$community_details["community_url"].":pages?".replace_query(array("action" => "reorder", "step" => 1)); ?>" method="post">
					<div id="reorder-info" style="display: none;">
						<textarea id="pageorder" name="pageorder" style="display: none;"></textarea>
						<p class="content-small">Rearrange the pages in the table above by dragging them, and then press the <strong>Save Ordering</strong> button.</p>
						<input type="submit" id="save_pages_order_button" class="btn btn-primary" value="Save Ordering" style="display:none;"/>
					</div>
				</form>







				<script type="text/javascript">
					var tree;
					function updatePageOrderBox(container) {
						$('pageorder').value = Object.toJSON(tree.serialize());
					}
					tree = new SortableTree('pagelists', {
						onDrop: function(drag, drop, event){
							updatePageOrderBox('pagelists', this);
							return true;
						}
					});
					function toggleSorting() {
						$('pagelists').toggleClassName('sortable');
						$('reorder-info').toggle();
						$('save_pages_order_button').toggle();
						if(tree.isSortable) {
							tree.setUnsortable();
							$$('div.community-page-container a').each(function(e) {
								e.stopObserving('click');
							});
							$('reorder_pages_button').value = "Reorder Pages";
							$('delete_pages_button').removeClassName('disabled').removeAttribute("disabled","");
							window.location = "<?php echo ENTRADA_URL . "/community" . $community_details["community_url"] . ":pages"; ?>";
						} else {
							$$('div.community-page-container a').each(function(e) {
								e.observe('click', function(event) {event.stop();});
							});
							tree.setSortable();
							updatePageOrderBox('pagelists');
							$('reorder_pages_button').value = "Cancel Reordering";
							$('delete_pages_button').addClassName('disabled').writeAttribute("disabled");
						}
						return false;
					}
				</script>
				<?php
			} else {
				$NOTICE++;
				$NOTICESTR[] = "There are currently no content pages available in this community.<br /><br />To create a new page in this community, click the <strong>Add New Page</strong> button above.";
		
				echo display_notice();
			}
		} else {
			application_log("error", "User tried to modify a community, but they aren't an administrator of this community.");

			$ERROR++;
			$ERRORSTR[] = "You do not appear to be an administrator of the community that you are trying to modify.<br /><br />If you feel you are getting this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.";

			echo display_error();
		}
	} else {
		application_log("error", "User tried to modify a community id [".$COMMUNITY_ID."] that does not exist or is not active in the system.");

		$ERROR++;
		$ERRORSTR[] = "The community you are trying to modify either does not exist in the system or has been deactived by an administrator.<br /><br />If you feel you are receiving this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.";

		echo display_error();
	}
} else {
	application_log("error", "User tried to modify a community without providing a community_id.");

	header("Location: ".ENTRADA_URL."/communities");
	exit;
}
?>