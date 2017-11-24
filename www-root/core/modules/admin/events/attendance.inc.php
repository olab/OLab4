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
 * This file is used to modify content (i.e. goals, objectives, file resources
 * etc.) within a learning event from the entrada.events table.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('eventcontent', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/CardReader.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/windows/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/windows/default.css\" rel=\"stylesheet\" type=\"text/css\" />";
	$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/windows/medtech.css\" rel=\"stylesheet\" type=\"text/css\" />";	
	?>
	<script type="text/javascript">
		var EVENT_LIST_STATIC_TOTAL_DURATION = true;
	</script>
	<?php
	if ($EVENT_ID) {
		$query		= "	SELECT a.*, b.`organisation_id`
						FROM `events` AS a
						LEFT JOIN `courses` AS b
						ON b.`course_id` = a.`course_id`
						WHERE a.`event_id` = ".$db->qstr($EVENT_ID);
		$event_info	= $db->GetRow($query);
		if ($event_info) {
			if (!$ENTRADA_ACL->amIAllowed(new EventContentResource($event_info["event_id"], $event_info["course_id"], $event_info["organisation_id"]), "update")) {
				application_log("error", "Someone attempted to modify content for an event [".$EVENT_ID."] that they were not the coordinator for.");
				header("Location: ".ENTRADA_URL."/admin/".$MODULE);
				exit;
			} else {
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events?".replace_query(array("section" => "content", "id" => $EVENT_ID)), "title" => "Event Content");

				switch ($STEP) {
					case 2:			
						ob_clear_open_buffers();
						
                        $proxy_id = 0;

						if (isset($_POST["proxy_id"]) && $tmp_input = (int) $_POST["proxy_id"]) {
							$query = "SELECT `id` FROM `" . AUTH_DATABASE . "`.`user_data` WHERE `id` = ?";
							$proxy_id = (int) $db->GetOne($query, array($tmp_input));
						} elseif (isset($_POST["number"]) && $tmp_input = (int) $_POST["number"]) {
							$query = "SELECT `id` FROM `" . AUTH_DATABASE . "`.`user_data` WHERE `number` = ?";
							$proxy_id = (int) $db->GetOne($query, array($tmp_input));
						}

						if ($proxy_id) {
                            $audience = new Models_Event_Audience();
                            if (!$audience->isAudienceMember($proxy_id, $event_info["event_id"], $event_info["event_start"])) {
                                echo json_encode(array("error" => "This user is not an audience member for this event."));
                                exit;
                            }

							$attendance = new Models_Event_Attendance();
                            $toggle = $attendance->toggleAttendance($proxy_id, $event_info["event_id"]);
                            if ($toggle) {
                                echo json_encode(array("success" => "Successfully marked proxy_id [".$proxy_id."] as ".$toggle."."));
                                exit;
                            } else {
                                echo json_encode(array("error" => "Error occurred updating record for proxy_id [".$proxy_id."]."));
                                exit;
                            }
						} else {
							echo json_encode(array("error" => "Unable to locate proxy_id."));
                            exit;
						}
					break;
					default:
                        continue;
					break;
				}
				
                $audience = Models_Event_Attendance::fetchAllByEventID($EVENT_ID, $event_info["event_start"]);

				if (isset($_GET["download"]) && trim($_GET["download"]) == "csv") {
					if ($audience) {
						ob_clear_open_buffers();

						$output = "";
						foreach ($audience as $learner) {
							$output .= $learner["number"].','.$learner["lastname"].','.$learner["firstname"] . ','.($learner["has_attendance"] ? "Present" : "Absent")."\n";
						}

                        $file_title = "attendance-for-event-".$event_info["event_id"]."-".time().".csv";

                        header("Pragma: public");
						header("Expires: 0");
						header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
						header("Content-Type: text/csv");
						header("Content-Disposition: inline; filename=\"".$file_title."\"");
						header("Content-Length: ".@strlen($output));
						header("Content-Transfer-Encoding: binary\n");

						echo $output;
						exit;
					}
				}

				events_subnavigation($event_info, "attendance");

                echo "<div class=\"content-small\">" . fetch_course_path($event_info["course_id"]) . "</div>\n";
				echo "<h1 class=\"event-title\">" . html_encode($event_info["event_title"]) . "</h1>\n";

				if ($SUCCESS) {
					fade_element("out", "display-success-box");
					echo display_success();
				}

				if ($NOTICE) {
					echo display_notice();
				}

				if ($ERROR) {
					echo display_error();
				}
				?>
                <div class="pull-right">
                    <a href="#" onclick="javascript:openDialog('http://google.com');" class="btn btn-primary">Kiosk Mode</a>
                </div>
				<a name="event-attendance-section"></a>
				<h2 title="Event Resources Section">Event Attendance</h2>
				<div id="event-attendance-section">					
                    <div class="row-fluid">
                        <label for="number">Student Number:</label> <input type="text" name="number" id="number"/>
                    </div>
                    <table class="tableList" cellspacing="0" summary="List of Attached Files">
                        <colgroup>
                            <col class="modified"/>
                            <col class="title"/>
                            <col class="title"/>
                        </colgroup>
                        <thead>
                            <tr>
                                <td class="modified">&nbsp;</td>
                                <td class="title">Last Name</td>
                                <td class="title">First Name</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
							if ($audience && is_array($audience)) {
                                $audience = Models_Event_Attendance::sortAudience($audience);
                                if ($audience) {
                                    foreach ($audience as $proxy_id => $learner) {
                                        ?>
                                        <tr>
                                            <td><input type="checkbox" class="attendance-check" value="<?php echo $proxy_id; ?>" id="learner-<?php echo $learner["id"]; ?>"<?php echo ($learner["has_attendance"] ? " checked=\"checked\"" : "");?> /></td>
                                            <td><?php echo $learner["lastname"]; ?></td>
                                            <td><?php echo $learner["firstname"]; ?></td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="3"><?php echo display_notice(array("There is no audience associated with this event."));?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="3"><?php echo display_notice(array("There is no audience associated with this event."));?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
					<div style="margin-top:10px">
						<input type="button" class="btn" value="Download CSV" onclick="window.location = '<?php echo ENTRADA_URL."/admin/events?".replace_query(array("section" => "attendance", "id" => $EVENT_ID,"download"=>"csv"));?>'"/>
					</div>
				</div>

				<div class="kiosk-modal" style="display:none;">
						<div id="modal_message">You can now swipe student card.</div>
						<div id="modal_icon"><img src="<?php echo ENTRADA_URL."/images/large_check.png";?>" id="modal_icon_img" title="Success" alt ="Success" style="height:400px;"/></div>
						<div id="modal_response"></div>
				</div>
				<style>
					#modal_message,#modal_icon,#modal_response{
						display:block;
						clear:both;
						text-align: center;
					}
					#modal_message{
						margin-top:10px;
						font-size:2em;						
					}
					#requestDialog_top,#modal_response{
						font-size:2em;
					}
				</style>
				<script type="text/javascript">
				var kiosk_mode = false;
				var loading = false;
				var check_url = '<?php echo ENTRADA_URL."/images/large_check.png";?>';
				var x_url = '<?php echo ENTRADA_URL."/images/large_x.png";?>';
				var loading_url = '<?php echo ENTRADA_URL."/images/loading_med.gif";?>';
				var swipe_msg = 'You can now swipe student card.';
				var number = 119;
				var scan_message = '<div id="modal_message">You can now swipe student card.</div><div id="modal_icon"><img src="'+check_url+'" id="modal_icon_img" title="Success" alt ="Success" style="height:400px;"/></div><div id="modal_response"></div>';
				var valid = true;
				
				jQuery(function () {
					var reader = new CardReader();
					reader.observe(window);
					reader.validate(function(value){	
						return kiosk_mode;
					});
					reader.cardError(function(){
						if(kiosk_mode){
							jQuery('#modal_icon_img').attr('src',x_url);
							jQuery('#modal_icon_img').attr('title','Error');
							jQuery('#modal_icon_img').attr('alt','Error');							
							jQuery('#modal_response').html('Card Read Error');
						}else{
							alert('Error reading swipe. Please click Kiosk Mode to capture card swipes.');
						}
					});
					<?php
					$parser = defined("KIOSK_MODE_CARD_PARSER") ? KIOSK_MODE_CARD_PARSER : "data.substring(0,data.length-2)";
					?>

					reader.cardRead(function(data){
						number = <?php echo $parser; ?>;
						processSwipe(number);
					});
				});
				
				function openDialog(url){
					kiosk_mode = true;
					jQuery('#modal_message').html(swipe_msg);
					jQuery('#modal_response').html('');
					jQuery('#modal_icon_img').attr('src',check_url);
					jQuery('#modal_icon_img').attr('title','Error');
					jQuery('#modal_icon_img').attr('alt','Error');	
					jQuery(".kiosk-modal").dialog({
						 width: 800 , 
						 height: 600,
						 position: 'center',
						 draggable: false,
						 resizable: false,
						 modal : true,
						 show: 'fade',
						 hide: 'fade',
						 title: 'Attendance Kiosk Mode',
						 close: function(){
								kiosk_mode = false;
								}
					});					

				}
				
				function processSwipe(number){
					if(kiosk_mode){		
						var attending;
						jQuery.ajax({
							type: "POST",
							url: "<?php echo ENTRADA_URL;?>/admin/events?section=attendance&id=<?php echo $EVENT_ID;?>&step=2",
							data: "number="+number+"&attending=2",
							beforeSend: function(){
										jQuery('#modal_message').html('Loading...');
										jQuery('#modal_icon_img').attr('src',loading_url);
										jQuery('#modal_icon_img').attr('title','Loading');
										jQuery('#modal_icon_img').attr('alt','Loading');							
										jQuery('#modal_response').hide();
									},
							success: function(data){
								jQuery('#modal_message').html(swipe_msg);
								try{
									var result = jQuery.parseJSON(data);
									if(result.error){
										jQuery('#modal_icon_img').attr('src',x_url);
										jQuery('#modal_icon_img').attr('title','Error');
										jQuery('#modal_icon_img').attr('alt','Error');							
										jQuery('#modal_response').html(result.error);

									}else{
										jQuery('#modal_icon_img').attr('src',check_url);
										jQuery('#modal_icon_img').attr('title','Success');
										jQuery('#modal_icon_img').attr('alt','Success');
										jQuery('#modal_response').html(result.success);										
										jQuery('#learner-'+result.proxy_id).toggleCheck();
									}
								}catch(e){
									jQuery('#modal_icon_img').attr('src',x_url);
									jQuery('#modal_icon_img').attr('title','Error');
									jQuery('#modal_icon_img').attr('alt','Error');							
									jQuery('#modal_response').html('Unknown error while adding user.');
								}
								jQuery('#modal_response').show();
							}
						});
					}else{
					
					}
					return false;
				}
				
				$$('select.ed_select_off').each(function(el) {
					$(el).disabled = true;
					$(el).fade({ duration: 0.3, to: 0.25 });
				});
				
				jQuery('.attendance-check').click(function(){
					var proxy_id = jQuery(this).val();
					var attending;
					if(jQuery(this).is(':checked')){
						attending = 1;
					}else{
						attending = 0;
					}
					jQuery.ajax({
						type: "POST",
						url: "<?php echo ENTRADA_URL; ?>/admin/events?section=attendance&id=<?php echo $EVENT_ID;?>&step=2",
						data: "proxy_id="+proxy_id+"&attending="+attending,
						success: function(data){
							try{
								var result = jQuery.parseJSON(data);
								if(result.error){
									alert(result.error);
								}
							}catch(e){
								
							}
						}
					});
				});
				jQuery('#number').keydown(function(e){
						if(e.keyCode == 13){
							var number = jQuery(this).val();
							var attending;
							jQuery.ajax({
								type: "POST",
								url: "<?php echo ENTRADA_URL;?>/admin/events?section=attendance&id=<?php echo $EVENT_ID;?>&step=2",
								data: "number="+number+"&attending=2",
								success: function(data){
									try{
										var result = jQuery.parseJSON(data);
										if(result.error){
											alert(result.error);
										}else{
											jQuery('#number').val('');
											jQuery('#learner-'+result.proxy_id).toggleCheck();
										}
										jQuery('#number').focus();
									}catch(e){

									}
								}
							});
							return false;
						}
					});		
					
					  jQuery.fn.toggleCheck = function() {
							return this.each(function() {
								this.checked = !this.checked;
							});
						};
				</script>
				<?php			
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to edit a event you must provide a valid event identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid event identifer when attempting to edit a event.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a event you must provide the events identifier.";

		echo display_error();

		application_log("notice", "Failed to provide event identifer when attempting to edit a event.");
	}
}
