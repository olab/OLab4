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
 * This file is used to display events from the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_RELATIVE);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("event", "read", false)) {
	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$USE_QUERY = false;
	$EVENT_ID = 0;
	$RESULT_ID = 0;
	$RESULT_TOTAL_ROWS = 0;
	$PREFERENCES = preferences_load($MODULE);

	/**
	 * Process any sorting or pagination requests.
	 */
	events_process_sorting();

	/**
	 * Check to see if they are trying to view an event using an event_id.
	 */
	if ((isset($_GET["rid"])) && ($tmp_input = clean_input($_GET["rid"], array("nows", "int")))) {
		$EVENT_ID = $tmp_input;
		$transverse = true;
		if (isset($_GET["community"]) && ((int)$_GET["community"])) {
			$community_id = ((int)$_GET["community"]);
		}
	} elseif ((isset($_GET["drid"])) && ($tmp_input = clean_input($_GET["drid"], array("nows", "int")))) {
		$EVENT_ID = $tmp_input;
		$transverse = true;
	} elseif ((isset($_GET["id"])) && ($tmp_input = clean_input($_GET["id"], array("nows", "int")))) {
		$EVENT_ID = $tmp_input;
		$transverse = false;
	}
    
    $event_info	= Models_Event::fetchEventById($EVENT_ID);
    if ($event_info) {
        $COURSE_ID = $event_info["course_id"];
    }

    $event = Models_Event::get($EVENT_ID);
	$assessments = Models_Assessment_Event::fetchAllAssessmentByEventID($EVENT_ID);

	/**
	 * Check for groups which have access to the administrative side of this module
	 * and add the appropriate toggle sidebar item.
	 */
	if ($ENTRADA_ACL->amIAllowed("eventcontent", "update", false)) {
		switch ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]) {
			case "admin" :
				$admin_wording = "Administrator View";
				$admin_url = ENTRADA_RELATIVE."/admin/events".(($EVENT_ID) ? "?".replace_query(array("section" => "edit", "id" => $EVENT_ID)) : "");
			break;
			case "pcoordinator" :
				$admin_wording = "Coordinator View";
				$admin_url = ENTRADA_RELATIVE."/admin/events".(($EVENT_ID) ? "?".replace_query(array("section" => "edit", "id" => $EVENT_ID)) : "");
			break;
			case "director" :
				$admin_wording = "Director View";
				$admin_url = ENTRADA_RELATIVE."/admin/events".(($EVENT_ID) ? "?".replace_query(array("section" => "content", "id" => $EVENT_ID)) : "");
			break;
			case "teacher" :
			case "faculty" :
			case "lecturer" :
				$admin_wording = "Teacher View";
				$admin_url = ENTRADA_RELATIVE."/admin/events".(($EVENT_ID) ? "?".replace_query(array("section" => "content", "id" => $EVENT_ID)) : "");
			break;
			default :
				$admin_wording = "";
				$admin_url = "";
			break;
		}

		$sidebar_html  = "<ul class=\"menu none\">\n";
		$sidebar_html .= "	<li><a href=\"".ENTRADA_RELATIVE."/events".(($EVENT_ID) ? "?".replace_query(array("id" => $EVENT_ID, "action" => false)) : "")."\"><img src=\"".ENTRADA_RELATIVE."/images/checkbox-on.gif\" alt=\"\" /> <span>Learner View</span></a></li>\n";
		if (($admin_wording) && ($admin_url)) {
			$sidebar_html .= "<li><a href=\"".$admin_url."\"><img src=\"".ENTRADA_RELATIVE."/images/checkbox-off.gif\" alt=\"\" /> <span>".html_encode($admin_wording)."</span></a></li>\n";
		}
		$sidebar_html .= "</ul>\n";

		new_sidebar_item("Display Style", $sidebar_html, "display-style", "open");
	}

	if (isset($_GET["organisation_id"]) && ($organisation = ((int) $_GET["organisation_id"]))) {
		$ORGANISATION_ID = $organisation;
		$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["organisation_id"] = $ORGANISATION_ID;
	} else {
		if (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["organisation_id"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["organisation_id"]) {
			$ORGANISATION_ID = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["organisation_id"];
		} else {
			$ORGANISATION_ID = $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["organisation_id"];
			$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["organisation_id"] = $ORGANISATION_ID;
		}
	}

    $settings = new Entrada_Settings();
    if ($settings->read("podcast_display_sidebar")) {
        $sidebar_html = "<div style=\"text-align: center\">\n";
        $sidebar_html .= "	<a href=\"" . ENTRADA_RELATIVE . "/podcasts\"><img src=\"" . ENTRADA_RELATIVE . "/images/itunes_podcast_icon.png\" width=\"70\" height=\"70\" alt=\"MEdTech Podcasts\" title=\"Subscribe to our Podcast feed.\" border=\"0\"></a><br />\n";
        $sidebar_html .= "	<a href=\"" . ENTRADA_RELATIVE . "/podcasts\" style=\"display: block; margin-top: 10px; font-size: 14px\">Podcasts Available</a>";
        $sidebar_html .= "</div>\n";
        new_sidebar_item("Podcasts in iTunes", $sidebar_html, "podcast-bar", "open", "2.1");
    }

	$BREADCRUMB[] = array("url" => ENTRADA_RELATIVE."/events", "title" => "Learning Events");

	/**
	 * If we were going into the $EVENT_ID
	 */
	if ($EVENT_ID) {
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/windows/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
		$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/windows/default.css\" rel=\"stylesheet\" type=\"text/css\" />";
		$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/windows/medtech.css\" rel=\"stylesheet\" type=\"text/css\" />";
		$HEAD[] = "<script>var SITE_URL = '".ENTRADA_URL."';</script>";

		?>
		<script type="text/javascript">
            EVENT_ID = <?php echo $EVENT_ID . ";"; ?>
			function beginQuiz(id) {
				Dialog.confirm('Do you really wish to begin your attempt of this quiz? The timer will begin immediately if this quiz has a time-limit, and you will only have until that timer expires to answer the questions before the quiz is closed to you.',
					{
						id:				'requestDialog',
						width:			350,
						height:			125,
						title:			'Quiz Start Confirmation',
						className:		'medtech',
						okLabel:		'Yes',
						cancelLabel:	'No',
						closable:		'true',
						buttonClass:	'btn',
						ok:				function(win) {
											window.location = '<?php echo ENTRADA_RELATIVE; ?>/quizzes?section=attempt&id='+id;
											return true;
										}
					}
				);
			}
		</script>
		<?php
		$event_info	= Models_Event::fetchEventById($EVENT_ID);
		if (!$event_info) {
			add_error("The requested learning event does not exist in the system.");

			echo display_error();
		} else {
			$LASTUPDATED = $event_info["updated_date"];

			if (($event_info["release_date"]) && ($event_info["release_date"] > time())) {
				add_error("The event you are trying to view is not yet available. Please try again after ".date("r", $event_info["release_date"]));

				echo display_error();
			} elseif (($event_info["release_until"]) && ($event_info["release_until"] < time())) {
				add_error("The event you are trying to view is no longer available; it expired ".date("r", $event_info["release_until"]));

				echo display_error($errorstr);
			} else {
				if ($ENTRADA_ACL->amIAllowed(new EventResource($EVENT_ID, $event_info['course_id'], $event_info['organisation_id']), 'read')) {
					add_statistic($MODULE, "view", "event_id", $EVENT_ID);

					$event_contacts = events_fetch_event_contacts($EVENT_ID);

                    $event_audience = $event->getEventAudience();

					$associated_cohorts = array("all");
					$associated_cohorts_string = "";
					$query = "SELECT * FROM `event_audience` WHERE `event_id` = ".$db->qstr($EVENT_ID)." AND `audience_type` = 'cohort'";
					$cohorts = $db->GetAll($query);
					if ($cohorts) {
						foreach ($cohorts as $cohort) {
							$associated_cohorts[] = $cohort["audience_value"];
							$associated_cohorts_string .= ($associated_cohorts_string ? ", ".$db->qstr($cohort["audience_value"]) : $db->qstr($cohort["audience_value"]) );
						}
						$event_audience_type = "cohort";
					}

					$event_resources = events_fetch_event_resources($EVENT_ID, "all");
					$event_discussions = $event_resources["discussions"];
					$event_types = $event_resources["types"];

                                        $EXAM_TEXT = $translate->_("exams");

					// Meta information for this page.
					$PAGE_META["title"]			= $event_info["event_title"]." - ".APPLICATION_NAME;
					$PAGE_META["description"]	= trim(str_replace(array("\t", "\n", "\r"), " ", html_encode(strip_tags($event_info["event_goals"]))));
					$PAGE_META["keywords"]		= "";

					$BREADCRUMB[] = array("url" => ENTRADA_RELATIVE."/events?".replace_query(array("id" => $event_info["event_id"])), "title" => $event_info["event_title"]);

					$include_details			= true;
					$include_audience			= true;
					$include_objectives			= true;
					$include_resources			= true;
					$include_comments			= true;

					$icon_discussion			= (((is_array($event_discussions)) && (count($event_discussions))) ? true : false);
					$icon_course_website		= true;

					$discussion_title			= (($icon_discussion) ? "Read the posted discussion comments." : "Start up a conversion, leave your comment!");
					$syllabus_title				= "Visit Course Website";

// @todo simpson This needs to be fixed.
					if (($_SESSION["details"]["allow_podcasting"]) && ($event_audience_type == "cohort") && (in_array($_SESSION["details"]["allow_podcasting"], $associated_cohorts))) {
						$sidebar_html = "To upload a podcast: <a href=\"#\" onclick=\"openDialog('".ENTRADA_URL."/api/file-wizard-podcast.api.php?id=".$EVENT_ID."')\">click here</a>";
						new_sidebar_item("Upload A Podcast", $sidebar_html, "podcast_uploading", "open", "2.0");

						$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/elementresizer.js\"></script>";
						$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/wizard.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
						$HEAD[] = "<link href=\"".ENTRADA_URL."/css/wizard.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
						$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
						$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
						$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
						?>

						<iframe id="upload-frame" name="upload-frame" onload="frameLoad()" style="display: none;"></iframe>
						<a id="false-link" href="#placeholder"></a>
						<div id="placeholder" style="display: none"></div>
						<script type="text/javascript">
						var ajax_url = '';
						var modalDialog;
						document.observe('dom:loaded', function() {
							modalDialog = new Control.Modal($('false-link'), {
								position:		'center',
								overlayOpacity:	0.75,
								closeOnClick:	'overlay',
								className:		'modal',
								fade:			true,
								fadeDuration:	0.30,
								beforeOpen: function(request) {
									eval($('scripts-on-open').innerHTML);
								}
							});
						});

						function openDialog (url) {
							if (url && url != ajax_url) {
								ajax_url = url;
								new Ajax.Request(ajax_url, {
									method: 'get',
									onComplete: function(transport) {
										modalDialog.container.update(transport.responseText);
										modalDialog.open();
									}
								});
							} else {
								$('scripts-on-open').update();
								modalDialog.open();
							}
						}
						</script>
						<?php
					}

					if ($transverse) {
						$transversal_ids = events_fetch_transversal_ids($EVENT_ID, (isset($community_id) && $community_id ? $community_id : false));
					}
                    ?>
					<div class="no-printing">
                        <?php
                        if ($transverse && is_array($transversal_ids) && !empty($transversal_ids)) {
                            $back_click = "";
                            $next_click = "";
                            ?>
                            <div class="btn-toolbar clearfix">
                                <div class="btn-group span12">
                                    <?php
                                    if (isset($transversal_ids["prev"])) {
                                        $back_click = ENTRADA_RELATIVE . "/events?" . replace_query(array((isset($_GET["drid"]) ? "drid" : "rid") => $transversal_ids["prev"]));

                                        echo "<a class=\"btn\" id=\"back_event\" href=\"".$back_click."\" title=\"Previous Event\"><i class=\"icon-chevron-left\"></i></a>";
                                    } else {
                                        echo "<a class=\"btn disabled\" id=\"back_event\" href=\"#\" title=\"Previous Event\"><i class=\"icon-chevron-left\"></i></a>";
                                    }
                                    ?>
                                    <div id="swipe-location" class="event-navbar-middle"><?php echo html_encode($event_info["event_title"]); ?></div>
                                    <?php
                                    if (isset($transversal_ids["next"])) {
                                        $next_click = ENTRADA_RELATIVE . "/events?" . replace_query(array((isset($_GET["drid"]) ? "drid" : "rid") => $transversal_ids["next"]));

                                        echo "<a class=\"btn\" id=\"next_event\" href=\"".$next_click."\" title=\"Next Event\"><i class=\"icon-chevron-right\"></i></a>";
                                    } else {
                                        echo "<a class=\"btn disabled\" id=\"next_event\" href=\"#\" title=\"Next Event\"><i class=\"icon-chevron-right\"></i></a>";
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>

                        <div class="pull-right">
                            <i class="fa fa-link"></i> <a href="<?php echo ENTRADA_RELATIVE; ?>/events?id=<?php echo $event_info["event_id"]; ?>" class="space-right"><small>Link to this page</small></a>
                            <i class="fa fa-print"></i> <a href="javascript: window.print()"><small>Print this page</small></a>
                        </div>
					</div>

                    <div class="clearfix"></div>
					<div class="content-small"><?php echo fetch_course_path($event_info["course_id"], $event_info["cunit_id"]); ?></div>
					<a name="event-details-section-anchor"></a><!--nus310517-->
					<h1 id="page-top" class="event-title"><?php echo html_encode($event_info["event_title"]); ?></h1>

                    <script type="text/javascript">
                        var ajax_url = '';
                        var modalDialog;

                        function submitLTIForm() {
                            jQuery('#ltiSubmitForm').submit();
                        }

                        function openLTIDialog(url) {
                            var width  = jQuery(window).width() * 0.9,
                                height = jQuery(window).height() * 0.9;

                            if(width < 400) { width = 400; }
                            if(height < 400) { height = 400; }

                            modalDialog = new Control.Modal($('#false-link'), {
                                position:		'center',
                                overlayOpacity:	0.75,
                                closeOnClick:	'overlay',
                                className:		'modal',
                                fade:			true,
                                fadeDuration:	0.30,
                                width: width,
                                height: height,
                                afterOpen: function(request) {
                                    eval($('scripts-on-open').innerHTML);
                                },
                                beforeClose: function(request) {
                                    jQuery('#ltiContainer').remove();
                                }
                            });

                            new Ajax.Request(url, {
                                method: 'get',
                                parameters: 'width=' + width + '&height=' + height,
                                onComplete: function(transport) {
                                    modalDialog.container.update(transport.responseText);
                                    modalDialog.open();
                                }
                            });
                        }

                        function closeLTIDialog() {
                            modalDialog.close();
                        }
                    </script>
                    <?php
                    /*
                     * This feature provides the ability for the Learning Event page to be designed differently
                     * for each organization or template. Issue #1654 -SG
                    */

                    // Look for a template file with the same name as this file.
                    $template_file = "/templates/" . $ENTRADA_TEMPLATE->activeTemplate() . "/views/events/event.tpl.php";
                    if (!file_exists(ENTRADA_ABSOLUTE . $template_file)) {
                        // Then check the default template (useful for multi-organization setup).
                        $template_file = "/templates/" . DEFAULT_TEMPLATE . "/views/events/event.tpl.php";
                        if (!file_exists(ENTRADA_ABSOLUTE . $template_file)) {
                            // Fall back to the public/events template.
                            $template_file = "/core/modules/public/events/event.tpl.php";
                        }
                    }

                    // Pass these vars in the current scope to the template view.
                    $template_variables = [
                            "db",
                            "event",
                            "course",
                            "event_info",
                            "event_types",
                            "event_contacts",
                            "associated_cohorts_string",
                            "event_audience",
                            "event_audience_type",
                            "assessments",
                            "ENTRADA_USER",
                            "ENTRADA_ACL",
                            "EVENT_ID",
                    ];

                    $template = new Views_HTMLTemplate();
                    $template->setTemplatePath($template_file);
                    $template->render(compact($template_variables));
                    ?>
                    <div>
                        <?php
                        echo "<a name=\"event-comments-section-anchor\"></a>\n";
                        echo "<h2 title=\"Event Comments Section\">Discussions &amp; Comments</h2>\n";
                        echo "<div id=\"event-comments-section\" class=\"section-holder\">\n";
                        if (defined("NOTIFICATIONS_ACTIVE") && NOTIFICATIONS_ACTIVE) {
                            ?>
                            <div id="notifications-toggle" style="display: inline; padding-top: 4px; width: 100%; text-align: right;"></div>
                            <br /><br />
                            <script type="text/javascript">
                            function promptNotifications(enabled) {
                                Dialog.confirm('Do you really wish to '+ (enabled == 1 ? "stop" : "begin") +' receiving notifications when new comments are made on this event?',
                                    {
                                        id:				'requestDialog',
                                        width:			350,
                                        height:			75,
                                        title:			'Notification Confirmation',
                                        className:		'medtech',
                                        okLabel:		'Yes',
                                        cancelLabel:	'No',
                                        closable:		'true',
                                        buttonClass:	'btn',
                                        destroyOnClose:	true,
                                        ok:				function(win) {
                                                            new Window(	{
                                                                            id:				'resultDialog',
                                                                            width:			350,
                                                                            height:			75,
                                                                            title:			'Notification Result',
                                                                            className:		'medtech',
                                                                            okLabel:		'close',
                                                                            buttonClass:	'btn',
                                                                            resizable:		false,
                                                                            draggable:		false,
                                                                            minimizable:	false,
                                                                            maximizable:	false,
                                                                            recenterAuto:	true,
                                                                            destroyOnClose:	true,
                                                                            url:			'<?php echo ENTRADA_URL."/api/notifications.api.php?record_id=".$EVENT_ID; ?>&content_type=event_discussion&action=edit&active='+(enabled == 1 ? '0' : '1'),
                                                                            onClose:			function () {
                                                                                                new Ajax.Updater('notifications-toggle', '<?php echo ENTRADA_URL."/api/notifications.api.php?record_id=".$EVENT_ID; ?>&content_type=event_discussion&action=view');
                                                                                            }
                                                                        }
                                                            ).showCenter();
                                                            return true;
                                                        }
                                    }
                                );
                            }
                            </script>
                            <?php
                            $ONLOAD[] = "new Ajax.Updater('notifications-toggle', '".ENTRADA_URL."/api/notifications.api.php?record_id=".$EVENT_ID."&content_type=event_discussion&action=view')";
                        }

                        $editable	= false;
                        $edit_ajax	= array();
                        if ($event_discussions) {
                            $i = 0;
                            foreach ($event_discussions as $result) {
                                if ($result["proxy_id"] == $ENTRADA_USER->getID()) {
                                    $editable		= true;
                                    $edit_ajax[]	= $result["ediscussion_id"];
                                } else {
                                    $editable		= false;
                                }

                                $poster_name = get_account_data("firstlast", $result["proxy_id"]);

                                echo "<blockquote id=\"event_comment_" . (int) $result["ediscussion_id"]."\">\n";
                                echo " " . html_encode($result["discussion_title"]) . "<br />";
                                echo "	<div class=\"discussion-comment\" id=\"discussion_comment_".$result["ediscussion_id"]."\">".nl2br(html_encode($result["discussion_comment"]))."</div>\n";
                                echo "	<small><strong>".get_account_data("firstlast", $result["proxy_id"])."</strong>, ".date(DEFAULT_DATETIME_FORMAT, $result["updated_date"])." ".($editable ? " ( <span id=\"edit_mode_" . (int) $result["ediscussion_id"] . "\">edit</span> )" : "") . "</small>\n";
                                echo "</blockquote>\n";

                                $i++;
                            }

                            if ((@is_array($edit_ajax)) && (@count($edit_ajax))) {
                                echo "<script type=\"text/javascript\">\n";
                                foreach ($edit_ajax as $discussion_id) {
                                    echo "var editor_".$discussion_id." = new Ajax.InPlaceEditor('discussion_comment_".$discussion_id."', '".ENTRADA_RELATIVE."/api/discussions.api.php', { rows: 7, cols: 150, okText: 'Save Changes', cancelText: 'Cancel Changes', externalControl: 'edit_mode_".$discussion_id."', callback: function(form, value) { return 'action=edit&sid=".session_id()."&id=".$discussion_id."&discussion_comment='+escape(value) } });\n";
                                }
                                echo "</script>\n";
                            }
                        } else {
                            echo "<div class=\"content-small\">There are no comments or discussions on this event. <strong>Start a conversation</strong>, leave your comment below.</div>\n";
                        }
                        echo "	<br /><br />";
                        echo "	<form action=\"".ENTRADA_RELATIVE."/discussions?action=add".(($USE_QUERY) ? "&amp;".((isset($_GET["drid"])) ? "drid" : "rid")."=".$EVENT_ID : "")."\" method=\"post\">\n";
                        echo "		<input type=\"hidden\" name=\"event_id\" value=\"".$EVENT_ID."\" />\n";
                        echo "		<label for=\"discussion_comment\" class=\"content-subheading\">Leave a Comment</label>\n";
                        echo "		<div class=\"content-small\">Posting comment as <strong>".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]."</strong></div>\n";
                        echo "		<textarea id=\"discussion_comment\" name=\"discussion_comment\" class=\"expandable span12\"></textarea>\n";
                        echo "		<div style=\"text-align: right; padding-top: 8px\"><input type=\"submit\" class=\"btn btn-primary\" value=\"Post Comment\" /></div>\n";
                        echo "	</form>\n";
                        echo "</div>\n";
                        ?>
                    </div>
                    <?php
					/**
					 * Sidebar item that will provide the links to the different sections within this page.
					 */
					$sidebar_html  = "<ul class=\"menu\">\n";
					if ($include_details) {
						$sidebar_html .= "	<li class=\"link\"><a href=\"#event-details-section-anchor\" onclick=\"$('event-details-section-anchor').scrollTo(); return false;\" title=\"Event Details\">Event Details</a></li>\n";
					}

                    if (isset($include_keywords) && $include_keywords) {
                        $sidebar_html .= "  <li class=\"link\"><a href=\"#event-keywords-section-anchor\" onclick=\"$('event-keywords-section-anchor').scrollTo(); return false;\" title=\"Event Keywords\">Event Keywords</a></li>\n";
                    }

					if ($include_objectives) {
						$sidebar_html .= "	<li class=\"link\"><a href=\"#event-objectives-section-anchor\" onclick=\"$('event-objectives-section-anchor').scrollTo(); return false;\" title=\"" . $translate->_("Event Objectives") . "\">" . $translate->_("Event Objectives") . "</a></li>\n";
					}

					if ($include_resources) {
						$sidebar_html .= "	<li class=\"link\"><a href=\"#event-resources-section-anchor\" onclick=\"$('event-resources-section-anchor').scrollTo(); return false;\" title=\"Event Resources\">Event Resources</a></li>\n";
					}

					if ($include_comments) {
						$sidebar_html .= "	<li class=\"link\"><a href=\"#event-comments-section-anchor\" onclick=\"$('event-comments-section-anchor').scrollTo(); return false;\" title=\"Event Discussions &amp; Comments\">Event Comments</a></li>\n";
					}
					$sidebar_html .= "</ul>\n";

					new_sidebar_item("Page Anchors", $sidebar_html, "page-anchors", "open", "1.9");
				} else {
					add_error("You are not permitted to access this event. This error has been logged.");

					echo display_error($errorstr);
                    application_log("error", "User [".$_SESSION["details"]["username"]."] tried to access the event [".$EVENT_ID."] and was denied access.");
				}
			}
		}
	} else {
        $HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/script/xc2_timestamp.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/elementresizer.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

		/**
		 * Process any filter requests.
		 */
		events_process_filters($ACTION);

		/**
		 * Check if preferences need to be updated.
		 */
		preferences_update($MODULE, $PREFERENCES);

		/**
		 * Fetch all of the events that apply to the current filter set.
		 */

		$learning_events = events_fetch_filtered_events(
				$ENTRADA_USER->getActiveId(),
				$ENTRADA_USER->getActiveGroup(),
				$ENTRADA_USER->getActiveRole(),
				$ENTRADA_USER->getActiveOrganisation(),
				$_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"],
				$_SESSION[APPLICATION_IDENTIFIER]["events"]["so"],
				$_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"],
				$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"],
				0,
				$_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"],
				true,
				(isset($_GET["pv"]) ? (int) trim($_GET["pv"]) : 1),
				$_SESSION[APPLICATION_IDENTIFIER]["events"]["pp"]);
		/**
		 * Output the filter HTML.
		 */
		events_output_filter_controls();

		/**
		 * Output the calendar controls and pagination.
		 */
		events_output_calendar_controls();

		if (!empty($learning_events["events"])) {
			?>
			<p class="muted text-center">
				<small>
                    <?php
                    switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"]) {
                        case "day" :
                            echo "Found ".count($learning_events["result_ids_map"])." event".((count($learning_events["result_ids_map"]) != 1) ? "s" : "")." that take place on <strong>".date("D, M jS, Y", $learning_events["duration_start"])."</strong>.\n";
                            break;
                        case "month" :
                            echo "Found ".count($learning_events["result_ids_map"])." event".((count($learning_events["result_ids_map"]) != 1) ? "s" : "")." that take place during <strong>".date("F", $learning_events["duration_start"])."</strong> of <strong>".date("Y", $learning_events["duration_start"])."</strong>.\n";
                            break;
                        case "year" :
                            echo "Found ".count($learning_events["result_ids_map"])." event".((count($learning_events["result_ids_map"]) != 1) ? "s" : "")." that take place during <strong>".date("Y", $learning_events["duration_start"])."</strong>.\n";
                            break;
                        default :
                        case "week" :
                            echo "Found ".count($learning_events["result_ids_map"])." event".((count($learning_events["result_ids_map"]) != 1) ? "s" : "")." from <strong>".date("D, M jS, Y", $learning_events["duration_start"])."</strong> to <strong>".date("D, M jS, Y", $learning_events["duration_end"])."</strong>.\n";
                            break;
                    }
                    ?>
                </small>
			</p>
			<table class="table table-bordered table-striped" cellspacing="0" cellpadding="1" summary="List of Learning Events">
				<thead>
					<tr>
						<th class="date<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"] == "date") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["events"]["so"]) : ""); ?>"><?php echo public_order_link("date", "Date &amp; Time"); ?></th>
						<th class="course-code<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"] == "course") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["events"]["so"]) : ""); ?>"><?php echo public_order_link("course", "Course"); ?></th>
						<th class="title<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"] == "title") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["events"]["so"]) : ""); ?>"><?php echo public_order_link("title", "Event Title"); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$count_modified = 0;

					foreach ($learning_events["events"] as $result) {
                        $attachments = attachment_check($result["event_id"]);
                        $url = ENTRADA_RELATIVE."/events?rid=".$result["event_id"];
                        $is_modified = false;

                        /**
                         * Determine if this event has been modified since their last visit.
                         */
                        if (isset($result["last_visited"]) && ((int) $result["last_visited"]) && ((int) $result["last_visited"] < (int) $result["updated_date"])) {
                            $is_modified = true;
                            $count_modified++;
                        }

                        if ($is_modified) {
                        }

                        echo "<tr id=\"event-".$result["event_id"]."\" class=\"event".(($is_modified) ? " modified" : "")."\">\n";
                        echo "	<td><a href=\"".$url."\">".date(DEFAULT_DATETIME_FORMAT, $result["event_start"])."</a></td>\n";
                        echo "	<td><a href=\"".$url."\">".html_encode($result["course_code"])."</a></td>\n";
                        echo "	<td><a href=\"".$url."\">".html_encode($result["event_title"])."</a></td>\n";
                        echo "</tr>\n";
					}
					?>
				</tbody>
			</table>
			<?php
			if ($count_modified) {
				if ($count_modified != 1) {
					$sidebar_html = "There are ".$count_modified." teaching events on this page which were updated since you last looked at them.";
				} else {
					$sidebar_html = "There is ".$count_modified." teaching event on this page which has been updated since you last looked at it.";
				}
				$sidebar_html .= " Eg. <img src=\"".ENTRADA_RELATIVE."/images/highlighted-example.gif\" width=\"67\" height=\"14\" alt=\"Updated events are denoted like.\" title=\"Updated events are denoted like.\" style=\"vertical-align: middle\" />";

				new_sidebar_item("Recently Modified", $sidebar_html, "modified-event", "open");
			}
		} else {
			$filters_applied = (((isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["filters"])) && ($filters_total = @count($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["filters"]))) ? true : false);
			?>
			<div class="display-notice">
				<h3>No Matching Events</h3>
				There are no learning events scheduled
				<?php
				switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"]) {
					case "day" :
						echo "that take place on <strong>".date(DEFAULT_DATETIME_FORMAT, $learning_events["duration_start"])."</strong>";
					break;
					case "month" :
						echo "that take place during <strong>".date("F", $learning_events["duration_start"])."</strong> of <strong>".date("Y", $learning_events["duration_start"])."</strong>";
					break;
					case "year" :
						echo "that take place during <strong>".date("Y", $learning_events["duration_start"])."</strong>";
					break;
					default :
					case "week" :
						echo "from <strong>".date(DEFAULT_DATETIME_FORMAT, $learning_events["duration_start"])."</strong> to <strong>".date(DEFAULT_DATETIME_FORMAT, $learning_events["duration_end"])."</strong>";
					break;
				}
				echo (($filters_applied) ? " that also match the supplied &quot;Show Only&quot; restrictions" : "") ?>.
				<br /><br />
				If this is unexpected there are a few things that you can check:
				<ol>
					<li style="padding: 3px">Make sure that you are browsing the intended time period. For example, if you trying to browse <?php echo date("F", time()); ?> of <?php echo date("Y", time()); ?>, make sure that the results bar above says &quot;... takes place in <strong><?php echo date("F", time()); ?></strong> of <strong><?php echo date("Y", time()); ?></strong>&quot;.</li>
					<?php
					if ($filters_applied) {
						echo "<li style=\"padding: 3px\">You also have ".$filters_total." filter".(($filters_total != 1) ? "s" : "")." applied to the event list. you may wish to remove ".(($filters_total != 1) ? "one or more of these" : "it")." by clicking the link in the &quot;Showing Events That Include&quot; box above.</li>";
					}
					?>
				</ol>
			</div>
			<?php
		}

		echo "<form action=\"\" method=\"get\">\n";
		echo "	<input type=\"hidden\" id=\"dstamp\" name=\"dstamp\" value=\"".html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])."\" />\n";
		echo "</form>\n";

		/**
		 * Output the sidebar for sorting and legend.
		 */
		events_output_sidebar();

		$ONLOAD[] = "initList()";
	}
}
