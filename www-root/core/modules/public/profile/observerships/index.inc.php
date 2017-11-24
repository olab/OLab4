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
 * This is the default section that is loaded when the quizzes module is
 * accessed without a defined section.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_OBSERVERSHIPS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} else {

	require_once("Classes/mspr/Observership.class.php");
	require_once("Classes/mspr/Observerships.class.php");
	?>
	<h1>My Observerships</h1>
	<?php
	$total_observerships = 0;
	$observerships = Observerships::get(array("student_id" => $ENTRADA_USER->getID(), "status" => "confirmed"));

	if ($observerships) { 
		$total_observerships += count($observerships);
		echo "<h2>Confirmed Observerships</h2>";
		echo "<div class=\"display-generic\"><strong>Please note:</strong> Only the top 8 confirmed observerships will be listed on your MSPR, they are highlighted in green in the list below. You can re-order your observerships using the button below.</div>"; ?>
		<style type="text/css">
			.sortable-placeholder td {background-color:#EBEBEB;}
			.ui-sortable tr td {cursor:pointer;}
			.ui-sortable-disabled tr td {cursor:inherit;}
			.ui-sortable tr td.sort {background-image:url(<?php echo ENTRADA_URL; ?>/images/arrow_up_down.png);background-repeat:no-repeat;background-position:center center;}
			#observership-list tr.active {
				background-color: #E6F5E6;
			}
		</style>
		<script type="text/javascript">
		var SITE_URL = "<?php echo ENTRADA_URL; ?>";
		jQuery(function(){
			var observership_list = jQuery("#observership-list tbody");
			var dragged = false;
			observership_list.sortable({
				helper: "clone",
				placeholder : "sortable-placeholder",
				update : function(event, ui) {
					console.log("updated");
				},
				start : function (event, ui) {
					ui.placeholder.html("<td colspan='6'></td>")
					dragged = true;
				}
			});
			observership_list.sortable( "disable" );
			
			var clicked = false;
			jQuery("#reorder_observership").live("click", function(){
				if (clicked == false) {
					jQuery("#observership-list tbody .delete").hide();
					jQuery("#observership-list tbody .modified").addClass("sort");
					jQuery("#cancel_order").show();
					jQuery(this).attr("value", "Save Order");
					observership_list.sortable( "enable" );
					clicked = true;
					jQuery("#delete_button").hide();
				} else {
					observership_list.sortable( "disable" );
					jQuery("#observership-list tbody tr.observership").removeClass("active");
					var active_counter = 0;
					jQuery("#observership-list tbody tr.observership").each(function(i){
						if (jQuery("#observership-list tbody tr:eq(" + i + ") .status").html() == "Confirmed" && active_counter <= 7) {
							jQuery("#observership-list tbody tr").eq(i).addClass("active");
							active_counter++;
						}
						var observership_id = jQuery(this).attr("data-id");
						jQuery.ajax({
							type : "POST",
							url: SITE_URL + "/api/mspr.api.php",
							data: "action=update-order&observership_id="+observership_id+"&order="+i,
							success : function(data) {
								console.log(data);
							}
						})
					});
					jQuery(this).attr("value", "Reorder");
					jQuery("#cancel_order").hide();
					jQuery("#observership-list tbody .delete").show();
					jQuery("#observership-list tbody .modified").removeClass("sort");
					clicked = false;
					jQuery("#delete_button").show();
				}
				return false;
			});
			jQuery("#cancel_order").live("click", function() {
				if (dragged == true) {
					observership_list.sortable( "cancel" );
				}
				observership_list.sortable( "disable" );

				jQuery(this).hide();
				jQuery("#reorder_observership").attr("value", "Reorder");
				jQuery("#observership-list tbody .delete").show();
				jQuery("#observership-list tbody .modified").removeClass("sort");
				clicked = false;
				jQuery("#delete_button").show();
				return false;
			});
		});
		</script>

			<div style="overflow:hidden;">
				<ul class="page-action" style="float:right;">
					<li><a href="<?php echo ENTRADA_URL; ?>/profile/observerships?section=add" class="strong-green">Add New Observership</a></li>
				</ul>
			</div>
			<table class="table" id="observership-list" cellspacing="0" cellpadding="1" summary="List of Observerships">
				<thead>
					<tr>
						<th width="20">&nbsp;</th>
						<th width="350">Title</th>
						<th>Status</th>
						<th>Start</th>
						<th>Finish</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
		<?php
		$i = 1;
		foreach ($observerships as $observership) {
			$editable = ($observership->getEnd() >= time() && ($observership->getStatus() == "pending" || $observership->getStatus() == "rejected") ? true : false);
			
			echo "<tr class=\"observership ".($i <= 8 && $observership->getStatus() == "confirmed" ? "active" : "")."\" data-id=\"".$observership->getID()."\">\n";
			echo "\t<td class=\"modified\">" . ($editable ? "<input type=\"checkbox\" class=\"delete\" name=\"delete[]\" value=\"".$observership->getID()."\" />" : "") . "</td>\n";
			echo "\t<td><a href=\"".ENTRADA_URL."/profile/observerships?section=review&id=".$observership->getID()."\">".$observership->getTitle()."</a></td>\n";
			echo "\t<td class=\"status\">".ucwords(strtolower($observership->getStatus()))."</td>\n";
			echo "\t<td>".date("Y-m-d", $observership->getStart())."</td>\n";
			echo "\t<td>".date("Y-m-d", $observership->getEnd())."</td>\n";
			echo "<td>";
			if ($editable) {
				echo "<a href=\"".ENTRADA_URL."/profile/observerships?section=edit&id=".$observership->getID()."\">Edit Observership</a>";
			} else {
				if ($observership->getEnd() < time()) {
					if ($observership->getStatus() == "approved" || $observership->getStatus() == "confirmed") {
						$action = "";
						if ($observership->getStatus() == "confirmed" && ($observership->getReflection() != "" && $observership->getReflection() != 0)) {
							$action = "Review";
						} else if ($observership->getStatus() == "approved") {
							$action = ($observership->getReflection() != "" ? "Edit" : "Add");
						}
						if ($action) {
							echo "<a href=\"".ENTRADA_URL."/profile/observerships?section=reflection&id=".$observership->getID()."\">" . $action . " Reflection</a>";
						}
					}
				}
			}
			echo "</td>\n";
			echo "</tr>\n";
			if ($observership->getStatus() == "confirmed") {
				$i++;
			}
		} ?>
				</tbody>
			</table>
		<div class="row-fluid">
			<input class="btn btn-primary pull-right" type="button" value="Reorder" id="reorder_observership" />
			<input class="btn" type="button" value="Cancel Reorder" id="cancel_order" style="display:none;" />
		</div>
		<?php
	} 
	
	$other_observerships["pending"] = Observerships::get(array("student_id" => $ENTRADA_USER->getID(), "status" => "pending"));
	$other_observerships["approved"] = Observerships::get(array("student_id" => $ENTRADA_USER->getID(), "status" => "approved"));
	$other_observerships["rejected"] = Observerships::get(array("student_id" => $ENTRADA_USER->getID(), "status" => "rejected"));
	$other_observerships["denied"] = Observerships::get(array("student_id" => $ENTRADA_USER->getID(), "status" => "denied"));
	
	if ($other_observerships) {
		$total_observerships += count($other_observerships);
		?>
		<h2>Unconfirmed Observerships</h2>
		<div class="display-generic">
			<strong>Please note:</strong> Unconfirmed observerships will not appear in your MSPR. Approved observerships require a reflection to be entered after they have been completed, which will notify the preceptor for confirmation.
		</div>
		<form action="<?php echo ENTRADA_URL; ?>/profile/observerships?section=delete" method="post">
			<table class="table table-striped" id="unconfirmed-observership-list" cellspacing="0" cellpadding="1" summary="List of Observerships">
				<thead>
					<tr>
						<th width="20">&nbsp;</th>
						<th width="280">Title</th>
						<th>Status</th>
						<th>Start</th>
						<th>Finish</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
		<?php
		foreach ($other_observerships as $type) {
			foreach ($type as $observership) {
				$editable = ($observership->getEnd() >= time() && ($observership->getStatus() == "pending" || $observership->getStatus() == "rejected") ? true : false);

				echo "<tr class=\"observership ".($i <= 8 && $observership->getStatus() == "confirmed" ? "active" : "")."\" data-id=\"".$observership->getID()."\">\n";
				echo "\t<td class=\"modified\">" . ($observership->getStatus() == "pending" || $observership->getStatus() == "approved" || $observership->getStatus() == "rejected" ? "<input type=\"checkbox\" class=\"delete\" name=\"delete[]\" value=\"".$observership->getID()."\" />" : "") . "</td>\n";
				echo "\t<td><a href=\"".ENTRADA_URL."/profile/observerships?section=review&id=".$observership->getID()."\">".$observership->getTitle()."</a></td>\n";
				echo "\t<td class=\"status\">".ucwords(strtolower($observership->getStatus()))."</td>\n";
				echo "\t<td>".date("Y-m-d", $observership->getStart())."</td>\n";
				echo "\t<td>".date("Y-m-d", $observership->getEnd())."</td>\n";
				echo "<td>";
				if ($editable) {
					echo "<a href=\"".ENTRADA_URL."/profile/observerships?section=edit&id=".$observership->getID()."\">Edit Observership</a>";
				} else {
					if ($observership->getEnd() < time()) {
						if ($observership->getStatus() == "approved" || $observership->getStatus() == "confirmed") {
							$action = "";
							if ($observership->getStatus() == "confirmed" && ($observership->getReflection() != "" && $observership->getReflection() != 0)) {
								$action = "Review";
							} else if ($observership->getStatus() == "approved") {
								$action = ($observership->getReflection() != "" ? "Edit" : "Add");
							}
							if ($action) {
								echo "<a href=\"".ENTRADA_URL."/profile/observerships?section=reflection&id=".$observership->getID()."\">" . $action . " Reflection</a>";
							}
						}
					}
				}
				echo "</td>\n";
				echo "</tr>\n";
			} 
		}
		?>
				</tbody>
			</table>
			<?php if (clerkship_fetch_schedule($ENTRADA_USER->getID()) == false) { ?>
			<div class="row-fluid">	
				<input class="btn pull-right" type="submit" value="Delete" id="delete_button" />
			</div>	
			<?php } ?>
		</form>
		<?php
	}
	
	if ($total_observerships == 0) {
		add_generic("You currently have no observerships in the system. You may add an observership by using the link below.");
		echo display_generic();
		echo "<a href=\"".ENTRADA_URL."/profile/observerships?section=delete\">Add</a>";
	}
}