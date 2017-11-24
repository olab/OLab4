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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_NOTICES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("notice", "create", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/notices?".replace_query(array("section" => "add")), "title" => "Adding Notice");

	/**
	 * Get the active organisation and add this notice to that organisation.
	 */
	$PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();

	echo "<h1>Adding Notice</h1>\n";

	// Error Checking
	switch($STEP) {
		case 2 :
			if ((isset($_POST["target"])) && ($target_audience = clean_input($_POST["target"], array("trim", "dir")))) {
				$PROCESSED["target"] = $target_audience;
			} else {
				$PROCESSED["target"] = 'updated';
			}

			if ((isset($_POST["notice_summary"])) && ($notice_summary = strip_tags(clean_input($_POST["notice_summary"], "trim"), "<a><br><p>"))) {
				$PROCESSED["notice_summary"] = $notice_summary;
			} else {
				add_error("You must provide a notice summary.");
			}

			$display_date = validate_calendars("display", true, true);
			if ((isset($display_date["start"])) && ((int) $display_date["start"])) {
				$PROCESSED["display_from"] = (int) $display_date["start"];
			} else {
				add_error("You must select a valid display start date.");
			}

			if ((isset($display_date["finish"])) && ((int) $display_date["finish"])) {
				$PROCESSED["display_until"] = (int) $display_date["finish"];
			} else {
				add_error("You must select a valid display finish date.");
			}

			if (isset($_POST["target_audience"]) && $target_audience = clean_input($_POST["target_audience"], "trim")) {
				if (strpos($target_audience, "all:") !== false || $target_audience == "public") {
					$PROCESSED["associated_audience"][] = array("audience_type" => $target_audience, "audience_value" => 0);
				}
			}

			if (!isset($PROCESSED["associated_audience"]) || !count($PROCESSED["associated_audience"])) {
				/**
				 * Non-required field "associated_faculty" / Associated Faculty (array of proxy ids).
				 * This is actually accomplished after the event is inserted below.
				 */
				if ((isset($_POST["associated_faculty"]))) {
					$associated_faculty = explode(",", $_POST["associated_faculty"]);
					foreach ($associated_faculty as $contact_order => $proxy_id) {
						$id = explode("_",$proxy_id);
						$id = $id[1];
						if ($proxy_id = clean_input($id, array("trim", "int"))) {
							$PROCESSED["associated_audience"][] = array("audience_type"=>"faculty","audience_value"=>$proxy_id);
						}
					}
				}

				/**
				 * Non-required field "associated_student" / Associated Students (array of proxy ids).
				 * This is actually accomplished after the event is inserted below.
				 */
				if ((isset($_POST["associated_student"]))) {
					$associated_student = explode(",", $_POST["associated_student"]);
					foreach ($associated_student as $contact_order => $proxy_id) {
						$id = explode("_",$proxy_id);
						$id = $id[1];
						if ($proxy_id = clean_input($id, array("trim", "int"))) {
							$PROCESSED["associated_audience"][] =  array("audience_type"=>"student","audience_value"=>$proxy_id);
						}
					}
				}

				/**
				 * Non-required field "associated_staff" / Associated Staff (array of proxy ids).
				 * This is actually accomplished after the event is inserted below.
				 */
				if ((isset($_POST["associated_staff"]))) {
					$associated_staff = explode(",", $_POST["associated_staff"]);
					foreach ($associated_staff as $contact_order => $proxy_id) {
						$id = explode("_", $proxy_id);
						$id = $id[1];
						if ($proxy_id = clean_input($id, array("trim", "int"))) {
							$PROCESSED["associated_audience"][] =  array("audience_type"=>"staff","audience_value"=>$proxy_id);
						}
					}
				}

				/**
				 * Non-required field "associated_cohort" / Associated Cohorts (array of proxy ids).
				 * This is actually accomplished after the event is inserted below.
				 */
				if ((isset($_POST["associated_cohort"]))) {
					$associated_cohort = explode(",", $_POST["associated_cohort"]);
					foreach ($associated_cohort as $contact_order => $group_id) {
						$id = explode("_", $group_id);
						$id = $id[1];
						if ($group_id = clean_input($id, array("trim", "int"))) {
							$PROCESSED["associated_audience"][] =  array("audience_type"=>"cohort","audience_value"=>$group_id);
						}
					}
				}

				/**
				 * Non-required field "associated_course_list" / Associated Course List (array of proxy ids).
				 * This is actually accomplished after the event is inserted below.
				 */
				if ((isset($_POST["associated_course_list"]))) {
					$associated_course_list = explode(",", $_POST["associated_course_list"]);
					foreach ($associated_course_list as $contact_order => $group_id) {
						$id = explode("_", $group_id);
						$id = $id[1];
						if ($group_id = clean_input($id, array("trim", "int"))) {
							$PROCESSED["associated_audience"][] =  array("audience_type"=>"course_list","audience_value"=>$group_id);
						}
					}
				}
			}

			if (!$ENTRADA_ACL->amIAllowed(new NoticeResource($PROCESSED["organisation_id"]), "create")) {
				add_error("You do not have permission to add a notice for this organisation. This error has been logged and will be investigated.");

				application_log("error", "User tried to create a notice within an organisation [" . $PROCESSED["organisation_id"] . "] they didn't have permission to create a notice in.");
			}

			if (!isset($PROCESSED["associated_audience"]) || !count($PROCESSED["associated_audience"])) {
				add_error("You must select at least one audience to display the notice to.");
			}

			if (!$ERROR) {
				$PROCESSED["updated_date"] = time();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
				$PROCESSED["created_by"] = $ENTRADA_USER->getID();

				if ($db->AutoExecute("notices", $PROCESSED, "INSERT")) {
					if ($NOTICE_ID = $db->Insert_Id()) {
						application_log("success", "Successfully added notice ID [".$NOTICE_ID."]");

						if (isset($PROCESSED["associated_audience"]) && is_array($PROCESSED["associated_audience"]) && !empty($PROCESSED["associated_audience"])) {
							foreach ($PROCESSED["associated_audience"] as $audience_member) {
								$audience_member["updated_by"] = $ENTRADA_USER->getID();
								$audience_member["updated_date"] = time();
								$audience_member["notice_id"] = $NOTICE_ID;
								if ($db->AutoExecute("notice_audience", $audience_member, "INSERT")) {
									application_log("success", "Successfully added audience for notice ID [".$NOTICE_ID."]");
								} else {

								}
							}
						}
					} else {
						application_log("error", "Unable to fetch the newly inserted notice identifier for this notice.");
					}

					$url = ENTRADA_URL."/admin/notices";
					add_success("You have successfully added a new notice to the system.<br /><br />You will now be redirected to the notice index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

					$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
				} else {
					add_error("There was a problem inserting this notice into the system. The MEdTech Unit was informed of this error; please try again later.");

					application_log("error", "There was an error inserting a notice. Database said: ".$db->ErrorMsg());
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

	// Page Display
	switch($STEP) {
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
			$ONLOAD[] = "updateAudienceOptions()";

			/**
			 * Load the rich text editor.
			 */
			load_rte("minimal");

			if ($ERROR) {
				echo display_error();
			}
			?>
			<form action="<?php echo ENTRADA_URL; ?>/admin/notices?section=add&amp;step=2" method="post" class="form-horizontal">
				<input type="hidden" id="org_id" name="org_id" value="<?php echo (int) $ENTRADA_USER->getActiveOrganisation(); ?>" />

				<?php
				if ($PROCESSED["organisation_id"]) {
					require_once(ENTRADA_ABSOLUTE."/core/modules/admin/notices/api-audience-options.inc.php");
				}
				?>
				<div class="control-group">
					<label for="notice_summary" class="form-required">Notice Summary:</label>
					<textarea id="notice_summary" name="notice_summary" cols="60" rows="10" style="width:100%"><?php echo ((isset($PROCESSED["notice_summary"])) ? html_encode(trim($PROCESSED["notice_summary"])) : ""); ?></textarea>
				</div>
				
				<h2>Time Release Options</h2>
				
				<div class="row-fluid">
					<table>
						<tr>
							<?php echo generate_calendars("display", "", true, true, ((isset($PROCESSED["display_from"])) ? $PROCESSED["display_from"] : time()), true, true, ((isset($PROCESSED["display_until"])) ? $PROCESSED["display_until"] : strtotime("+5 days 23:59:59"))); ?>
						</tr>
					</table>
				</div>
				
				<div class="row-fluid" style="margin-top:10px">
					<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>'" />
					<div class="pull-right">
						<input type="submit" class="btn btn-primary" value="Save" />
					</div>
				</div>
			</form>

			<script type="text/javascript">
				var multiselect = [];
				var audience_type;

				function showMultiSelect() {
					$$('select_multiple_container').invoke('hide');
					audience_type = $F('audience_type');
					org_id = $F('org_id');
					if (audience_type.match(/all.*/) || audience_type == 'public') {
						$('audience_list').hide();
					}else if (multiselect[audience_type]) {
						$('audience_list').show();
						multiselect[audience_type].container.show();
					} else {
						$('audience_list').show();
						if (audience_type) {
							new Ajax.Request('<?php echo ENTRADA_RELATIVE; ?>/admin/notices?section=api-audience-selector', {
								evalScripts : true,
								parameters: {
									'options_for' : audience_type,
									'org_id' : org_id,
									'associated_cohort' : $('associated_cohort').value,
									'associated_student' : $('associated_student').value
								},
								method: 'post',
								onLoading: function() {
									$('options_loading').show();
								},
								onSuccess: function(response) {
									if (response.responseText) {
										$('options_container').insert(response.responseText);
										if ($(audience_type + '_options')) {
											$(audience_type + '_options').addClassName('multiselect-processed');

											multiselect[audience_type] = new Control.SelectMultiple('associated_'+audience_type, audience_type + '_options', {
												checkboxSelector: 'table.select_multiple_table tr td input[type=checkbox]',
												nameSelector: 'table.select_multiple_table tr td.select_multiple_name label',
												filter: audience_type + '_select_filter',
												resize: audience_type + '_scroll',
												afterCheck: function(element) {
													var tr = $(element.parentNode.parentNode);
													tr.removeClassName('selected');

													if (element.checked) {
														tr.addClassName('selected');

														addAudience(element.id, audience_type);
													} else {
														removeAudience(element.id, audience_type);
													}
												}
											});


											if ($(audience_type + '_cancel')) {
												$(audience_type + '_cancel').observe('click', function(event) {
													this.container.hide();

													$('audience_type').options.selectedIndex = 0;
													$('audience_type').show();

													return false;
												}.bindAsEventListener(multiselect[audience_type]));
											}

											if ($(audience_type + '_close')) {
												$(audience_type + '_close').observe('click', function(event) {
													this.container.hide();

													$('audience_type').clear();

													return false;
												}.bindAsEventListener(multiselect[audience_type]));
											}

											multiselect[audience_type].container.show();
										}
									} else {
										new Effect.Highlight('audience_type', {startcolor: '#FFD9D0', restorecolor: 'true'});
										new Effect.Shake('audience_type');
									}
								},
								onError: function() {
									alert("There was an error retrieving the requested audience. Please try again.");
								},
								onComplete: function() {
									$('options_loading').hide();
								}
							});
						}
					}
					return false;
				}

				function addAudience(element, audience_id) {
					if (!$('audience_'+element)) {
						$('audience_list').innerHTML += '<li class="' + ((audience_id == 'student' || audience_id == 'faculty' || audience_id == 'staff' )? 'user' : 'group') + '" id="audience_'+element+'" style="cursor: move;">'+$($(element).value+'_label').innerHTML+'<img src="<?php echo ENTRADA_RELATIVE; ?>/images/action-delete.gif" onclick="removeAudience(\''+element+'\', \''+audience_id+'\');" class="list-cancel-image" /></li>';
						$$('#audience_list div').each(function (e) { e.hide(); });

						Sortable.destroy('audience_list');
						Sortable.create('audience_list');
					}
				}

				function removeAudience(element, audience_id) {
					$('audience_'+element).remove();
					Sortable.destroy('audience_list');
					Sortable.create('audience_list');
					if ($(element)) {
						$(element).checked = false;
					}
					var audience = $('associated_'+audience_id).value.split(',');
					for (var i = 0; i < audience.length; i++) {
						if (audience[i] == element) {
							audience.splice(i, 1);
							break;
						}
					}
					$('associated_'+audience_id).value = audience.join(',');
				}

				function selectEventAudienceOption(type) {
					if (type == 'custom' && !jQuery('#event_audience_type_custom_options').is(":visible")) {
						jQuery('#event_audience_type_custom_options').slideDown();
					} else if (type != 'custom' && jQuery('#event_audience_type_custom_options').is(":visible")) {
						jQuery('#event_audience_type_custom_options').slideUp();
					}
				}

				function updateAudienceOptions() {
					if ($F('org_id') > 0)  {
						$('audience-options').show();
						$('audience-options').update('<tr><td colspan="2">&nbsp;</td><td><div class="content-small" style="vertical-align: middle"><img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif" width="16" height="16" alt="Please Wait" title="" style="vertical-align: middle" /> Please wait while <strong>audience options</strong> are being loaded ... </div></td></tr>');
						new Ajax.Updater('audience-options', '<?php echo ENTRADA_RELATIVE; ?>/admin/notices?section=api-audience-options', {
							evalScripts : true,
							parameters : {
								ajax : 1,
								org_id : $F('org_id'),
								event_audience_students: ($('associated_student') ? $('associated_student').getValue() : ''),
								event_audience_cohort: ($('associated_cohort') ? $('associated_cohort').getValue() : '')
							},
							onSuccess : function (response) {
								if (response.responseText == "") {
									$('audience-options').update('');
									$('audience-options').hide();
								}
							},
							onFailure : function () {
								$('audience-options').update('');
								$('audience-options').hide();
							}
						});

					} else {
						$('audience-options').update('');
						$('audience-options').hide();
					}
				}
			</script>

			<?php
		break;
	}
}