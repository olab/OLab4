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
 * Serves as a dashboard type file for a particular apartment the Regional Education module.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 */

if (!defined("IN_SCHEDULE")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	switch ($ASCHEDULE_INFO["occupant_type"]) {
		case "undergrad" :
		case "postgrad" :
			echo "<h2>".html_encode($ASCHEDULE_INFO["firstname"]." ".$ASCHEDULE_INFO["lastname"])."</h2>";
			?>
			<div class="userProfile">
				<div class="head">
					<div>Learner Profile</div>
				</div>
				<div class="body">
					<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
						<tr>
							<td style="width: 110px; vertical-align: top; padding-left: 10px">
								<div style="position: relative">
									<?php
									$query = "SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = 1 AND `proxy_id` = ".$db->qstr($ASCHEDULE_INFO["proxy_id"]);
									$uploaded_file_active = $db->GetOne($query);

									echo "<div id=\"img-holder-".$ASCHEDULE_INFO["proxy_id"]."\" class=\"img-holder\">\n";

									$offical_file_active = false;
									$uploaded_file_active = false;

									/**
									 * If the photo file actually exists
									 */
									if (@file_exists(STORAGE_USER_PHOTOS."/".$ASCHEDULE_INFO["proxy_id"]."-official")) {
										$offical_file_active = true;
									}

									/**
									 * If the photo file actually exists, and
									 * If the uploaded file is active in the user_photos table, and
									 * If the proxy_id has their privacy set to "Basic Information" or higher.
									 */
									if ((@file_exists(STORAGE_USER_PHOTOS."/".$ASCHEDULE_INFO["proxy_id"]."-upload")) && ($db->GetOne("SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = '1' AND `photo_active` = '1' AND `proxy_id` = ".$db->qstr($ASCHEDULE_INFO["proxy_id"]))) && ((int) $ASCHEDULE_INFO["privacy_level"] >= 2)) {
										$uploaded_file_active = true;
									}

									if ($offical_file_active) {
										echo "	<img id=\"official_photo_".$ASCHEDULE_INFO["proxy_id"]."\" class=\"official\" src=\"".webservice_url("photo", array($ASCHEDULE_INFO["proxy_id"], "official"))."\" width=\"72\" height=\"100\" alt=\"".html_encode($ASCHEDULE_INFO["prefix"]." ".$ASCHEDULE_INFO["firstname"]." ".$ASCHEDULE_INFO["lastname"])."\" title=\"".html_encode($ASCHEDULE_INFO["prefix"]." ".$ASCHEDULE_INFO["firstname"]." ".$ASCHEDULE_INFO["lastname"])."\" />\n";
									}

									if ($uploaded_file_active) {
										echo "	<img id=\"uploaded_photo_".$ASCHEDULE_INFO["proxy_id"]."\" class=\"uploaded\" src=\"".webservice_url("photo", array($ASCHEDULE_INFO["proxy_id"], "upload"))."\" width=\"72\" height=\"100\" alt=\"".html_encode($ASCHEDULE_INFO["prefix"]." ".$ASCHEDULE_INFO["firstname"]." ".$ASCHEDULE_INFO["lastname"])."\" title=\"".html_encode($ASCHEDULE_INFO["prefix"]." ".$ASCHEDULE_INFO["firstname"]." ".$ASCHEDULE_INFO["lastname"])."\" />\n";
									}

									if (($offical_file_active) || ($uploaded_file_active)) {
										echo "	<a id=\"zoomin_photo_".$ASCHEDULE_INFO["proxy_id"]."\" class=\"zoomin\" onclick=\"growPic($('official_photo_".$ASCHEDULE_INFO["proxy_id"]."'), $('uploaded_photo_".$ASCHEDULE_INFO["proxy_id"]."'), $('official_link_".$ASCHEDULE_INFO["proxy_id"]."'), $('uploaded_link_".$ASCHEDULE_INFO["proxy_id"]."'), $('zoomout_photo_".$ASCHEDULE_INFO["proxy_id"]."'));\">+</a>";
										echo "	<a id=\"zoomout_photo_".$ASCHEDULE_INFO["proxy_id"]."\" class=\"zoomout\" onclick=\"shrinkPic($('official_photo_".$ASCHEDULE_INFO["proxy_id"]."'), $('uploaded_photo_".$ASCHEDULE_INFO["proxy_id"]."'), $('official_link_".$ASCHEDULE_INFO["proxy_id"]."'), $('uploaded_link_".$ASCHEDULE_INFO["proxy_id"]."'), $('zoomout_photo_".$ASCHEDULE_INFO["proxy_id"]."'));\"></a>";
									} else {
										echo "	<img src=\"".ENTRADA_URL."/images/headshot-male.gif\" width=\"72\" height=\"100\" alt=\"No Photo Available\" title=\"No Photo Available\" />\n";
									}

									if (($offical_file_active) && ($uploaded_file_active)) {
										echo "	<a id=\"official_link_".$ASCHEDULE_INFO["proxy_id"]."\" class=\"img-selector one\" onclick=\"showOfficial($('official_photo_".$ASCHEDULE_INFO["proxy_id"]."'), $('official_link_".$ASCHEDULE_INFO["proxy_id"]."'), $('uploaded_link_".$ASCHEDULE_INFO["proxy_id"]."'));\" href=\"javascript: void(0);\">1</a>";
										echo "	<a id=\"uploaded_link_".$ASCHEDULE_INFO["proxy_id"]."\" class=\"img-selector two\" onclick=\"hideOfficial($('official_photo_".$ASCHEDULE_INFO["proxy_id"]."'), $('official_link_".$ASCHEDULE_INFO["proxy_id"]."'), $('uploaded_link_".$ASCHEDULE_INFO["proxy_id"]."'));\" href=\"javascript: void(0);\">2</a>";
									}
									echo "</div>\n";
									?>
								</div>
							</td>
							<td style="width: 100%; vertical-align: top; padding-left: 5px">
								<table width="100%" cellspacing="0" cellpadding="1" border="0">
									<tr>
										<td style="width: 20%">Full Name:</td>
										<td style="width: 80%"><?php echo html_encode($ASCHEDULE_INFO["prefix"]." ".$ASCHEDULE_INFO["firstname"]." ".$ASCHEDULE_INFO["lastname"]); ?></td>
									</tr>
									<tr>
										<td>Gender:</td>
										<td><?php echo display_gender($ASCHEDULE_INFO["gender"]); ?></td>
									</tr>
									<tr>
										<td>Student Type:</td>
										<td><?php echo html_encode($ASCHEDULE_INFO['learner_type']); ?></td>
									</tr>
									<tr>
										<td>Student Number:</td>
										<td><?php echo html_encode($ASCHEDULE_INFO["number"]); ?></td>
									</tr>
									<tr>
										<td>E-Mail Address:</td>
										<td><a href="mailto:<?php echo html_encode($ASCHEDULE_INFO["email"]); ?>"><?php echo html_encode($ASCHEDULE_INFO["email"]); ?></a></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="display-notice">
				<?php
				echo html_encode($ASCHEDULE_INFO["firstname"])." is scheduled to reside in this apartment from ";
				echo date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_start"])." until ".date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_finish"]);
				if ((int) $ASCHEDULE_INFO["event_id"]) {
					echo " during their <strong>".html_encode($EVENT_INFO["rotation_title"])."</strong> rotation";
				}
				echo ".";
				?>
			</div>

			<div id="delete-confirmation-box" class="modal-confirmation">
				<h1>Delete <strong>Accommodation</strong> Confirmation</h1>
				<div class="display-notice">Do you really wish to remove <strong><?php echo html_encode($ASCHEDULE_INFO["firstname"]." ".$ASCHEDULE_INFO["lastname"]); ?></strong> from this apartment between <?php echo date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_start"]); ?> until <?php echo date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_finish"]); ?>?</div>

				<p><input type="checkbox" id="delete-confirmation-el-notify" name="notify_learner" value="1" /> <label for="delete-confirmation-el-notify" class="form-nrequired">Notify <strong><?php echo html_encode($ASCHEDULE_INFO["firstname"]); ?></strong> via e-mail they have been removed.</label></p>
				<div class="footer">
					<button class="btn space-left" onclick="Control.Modal.close()">Close</button>
					<button class="btn btn-danger pull-right space-right" id="delete-confirmation-confirm">Confirm</button>
				</div>
			</div>
			<?php
		break;
		case "other" :
		default :
			?>
			<h2>Other Occupant</h2>
			<div class="display-generic">
				This accommodation space is being reserved for &quot;<strong><?php echo html_encode($ASCHEDULE_INFO["occupant_title"]); ?></strong>&quot; from <?php echo date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_start"]); ?> until <?php echo date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_finish"]); ?>.
			</div>
			<?php
			if ((int) $ASCHEDULE_INFO["cost_recovery"]) {
				echo "<div class=\"display-notice\">\n";
				echo "	<strong>Please Note:</strong> This is a cost recovery space, please collect funds from this individual.";
				echo "</div>\n";
			}
			?>
			<div id="delete-confirmation-box" class="modal-confirmation">
				<h1>Delete <strong>Accommodation</strong> Confirmation</h1>
				<div class="display-notice">Do you really wish to remove <strong><?php echo html_encode($ASCHEDULE_INFO["occupant_title"]); ?></strong> from this apartment between <?php echo date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_start"]); ?> until <?php echo date(DEFAULT_DATE_FORMAT, $ASCHEDULE_INFO["inhabiting_finish"]); ?>?</div>

				<div class="footer">
					<button class="btn" onclick="Control.Modal.close()">Close</button>
					<button class="btn btn-danger pull-right" id="delete-confirmation-confirm">Confirm</button>
				</div>
			</div>

			<?php
		break;
	}
	?>

    <div class="row-fluid">
        <a class="btn btn-danger" href="#delete-confirmation-box" id="delete-accommodation-button"><div>Delete</div></a>
        <button class="btn pull-right" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments/manage?id=<?php echo $APARTMENT_ID; ?>&dstamp=<?php echo $ASCHEDULE_INFO["inhabiting_start"]; ?>'">Ok</button>
    </div>


	<form id="delete-accommodation-form" action="<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments/manage/schedule?id=<?php echo $APARTMENT_ID; ?>&sid=<?php echo $ASCHEDULE_ID; ?>&section=delete" method="post">
	<input type="hidden" id="delete-accommodation-el-confirmed" name="confirmed" value="0" />
	<input type="hidden" id="delete-accommodation-el-notify" name="notify" value="0" />
	</form>

	<script type="text/javascript">
	Event.observe(window, 'load', function() {
		new Control.Modal('delete-accommodation-button', {
			overlayOpacity:	0.75,
			closeOnClick:	'overlay',
			className:		'modal-confirmation',
			fade:			true,
			fadeDuration:	0.30
		});

		Event.observe('delete-confirmation-confirm', 'click', function() {
			$('delete-accommodation-el-confirmed').setValue('1');

			if ($('delete-confirmation-el-notify')) {
				$('delete-accommodation-el-notify').setValue($('delete-confirmation-el-notify').getValue());
			}
			$('delete-accommodation-form').submit();
		});
	});
	</script>
	<?php
}