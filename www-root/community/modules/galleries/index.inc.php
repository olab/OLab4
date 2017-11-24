<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * This file lists all of the available photo galleries within a particular
 * page in a community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_GALLERIES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

/**
 * Add the javascript for deleting forums.
 */
if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-gallery")) {
	?>
	<script type="text/javascript">
		function galleryDelete(id) {
			Dialog.confirm('Do you really wish to remove the '+ $('gallery-' + id + '-title').innerHTML +' gallery from this community?<br /><br />If you confirm this action, you will be deactivating this gallery and all photos within it.',
				{
					id:				'requestDialog',
					width:			350,
					height:			200,
					title:			'Delete Confirmation',
					className:		'medtech',
					okLabel:		'Yes',
					cancelLabel:	'No',
					closable:		'true',
					buttonClass:	'btn',
					ok:				function(win) {
										window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-gallery&id='+id;
										return true;
									}
				}
			);
		}
	</script>
	<?php
}
?>
<div id="module-header">
	<div class="pull-right">
		<?php
		if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "add-gallery")) {
			?>
			<ul class="page-action">
				<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-gallery" class="btn btn-success">Add Photo Gallery</a></li>
			</ul>
			<?php
		}
		?>
	</div>
</div>

<div style="padding-top: 10px; clear: both">
	<?php
	$query		= "	SELECT a.*
					FROM `community_galleries` AS a
					WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`gallery_active` = '1'
					".((!$LOGGED_IN) ? " AND a.`allow_public_read` = '1'" : (($COMMUNITY_MEMBER) ? ((!$COMMUNITY_ADMIN) ? " AND a.`allow_member_read` = '1'" : "") : " AND a.`allow_troll_read` = '1'"))."
					".((!$COMMUNITY_ADMIN) ? " AND (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time()).")" : "")."
					AND a.`cpage_id` = ".$db->qstr($PAGE_ID)."
					ORDER BY a.`gallery_order` ASC, a.`gallery_title` ASC";
	$results	= $db->GetAll($query);
	if ($results) {
		$total_galleries	= @count($results);
		$column				= 0;
		?>
		<table summary="Listing of Photo Galleries">
		<colgroup>
			<col style="width: 33%" />
			<col style="width: 34%" />
			<col style="width: 33%" />
		</colgroup>
		<tbody>
			<tr>
			<?php
			foreach($results as $progress => $result) {
				$column++;
				$accessible	= true;
				if ((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
					$accessible = false;
				}

				echo "<td".((!$accessible) ? " class=\"na\"" : "")." style=\"text-align: center\">";
				echo "	<h2 id=\"gallery-".$result["cgallery_id"]."-title\" class=\"gallery-heading\"><a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-gallery&amp;id=".$result["cgallery_id"]."\">".html_encode(limit_chars($result["gallery_title"], 26))."</a></h2>\n";
				echo "	<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-gallery&amp;id=".$result["cgallery_id"]."\">".communities_galleries_fetch_thumbnail($result["gallery_cgphoto_id"])."</a>";
				echo "	<div style=\"margin-top: 5px\">\n";
				echo		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "edit-gallery")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-gallery&amp;id=".$result["cgallery_id"]."\">edit</a>)" : "");
				echo 		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-gallery")) ? " (<a class=\"action\" href=\"javascript:galleryDelete('".$result["cgallery_id"]."')\">delete</a>)" : "");
				echo "	</div>";
				echo "</td>";

				if ((($progress + 1) == $total_galleries) && ($column != 3)) {
					echo "<td colspan=\"".(3 - $column)."\">&nbsp;</td>\n";
				} elseif ($column == 3) {
					$column = 0;
					echo "<tr>\n";
					echo "	<td colspan=\"2\">&nbsp;</td>\n";
					echo "</tr>\n";
				}
			}
			?>
			</tr>
		</tbody>
		</table>
		<?php
	} else {
		$NOTICE++;
		$NOTICESTR[] = "There are currently no galleries available in this community.<br /><br />".((communities_module_access($COMMUNITY_ID, $MODULE_ID, "add-gallery")) ? "As a community adminstrator you can add galleries by clicking <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-gallery\">Add Photo Gallery</a>." : "Please check back later.");

		echo display_notice();
	}
	?>
</div>