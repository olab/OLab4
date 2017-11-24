<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to view the details of / download the specified link within a folder.
 * 
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2013 Regents of The University of California. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_SHARES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}
    //checks the role of the user and sets hidden to true if they're not a facluty, staff, or medtech memeber
    //used to control access to files if they're marked hidden from students
    $group = $ENTRADA_USER->getActiveGroup();
    if ($group == 'faculty' || $group == 'staff'  || $group == 'medtech') {
        $hidden = false;
    } else {
        $hidden = true;
    }    
if ($RECORD_ID) {
	$query			= "
					SELECT a.*, b.`folder_title`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `uploader_fullname`, c.`username` AS `uploader_username`
					FROM `community_share_links` AS a
					LEFT JOIN `community_shares` AS b
					ON a.`cshare_id` = b.`cshare_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
					ON a.`proxy_id` = c.`id`
					WHERE a.`proxy_id` = c.`id`
					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`cslink_id` = ".$db->qstr($RECORD_ID)."
					AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
					AND a.`link_active` = '1'
					AND b.`folder_active` = '1'".
                    ($hidden ? "AND a.`student_hidden` = '0' AND b.`student_hidden` = '0'" : "");
	$file_record	= $db->GetRow($query);
        

	if ($file_record) {
        
        //checks if a folders parent is hidden
        $parent_folder_hidden = Models_Community_Share::parentFolderHidden($file_record['cshare_id']);
        if ($parent_folder_hidden && $hidden) {
            application_log("error", "An attempt to view a link with a parent folder hidden was made. cshare_id: [".$RECORD_ID."]");

            header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
            exit;
        }        
        
		if (isset($_GET["access"])) {
			/**
			 * Check for valid permissions before checking if the link really exists.
			 */
			if (shares_link_module_access($RECORD_ID, "view-link")) {
                
				$file_version = false;
                //get course id for the community
                $query = "  SELECT `course_id`
                            FROM `community_courses`
                            WHERE `community_id` = ".$db->qstr($COMMUNITY_ID);
                $course_id = $db->GetRow($query);
                //if the access method is external to entrada, open in the header, otherwise open it in a window on the page.
                if ($_GET["access"] == 'header') {
                    //session_variables
                    if ($file_record['session_variables']) {
                       ?>
                        <form id="header_rederict" action="<?php echo $file_record["link_url"]?>" method="post">
                            <input type="hidden" name="course_id" value="<?php echo $course_id["course_id"]?>" />
                            <input type="hidden" name="community_id" value="<?php echo $COMMUNITY_ID?>" />
                            <input type="hidden" name="username" value="<?php echo $ENTRADA_USER->getUsername()?>" />
                            <input type="hidden" name="email" value="<?php echo $ENTRADA_USER->getEmail()?>" />
                            <input type="hidden" name="firstname" value="<?php echo $ENTRADA_USER->getFirstname()?>" />
                            <input type="hidden" name="lastname" value="<?php echo $ENTRADA_USER->getLastname()?>" />
                            <input type="hidden" name="role" value="<?php echo $ENTRADA_USER->getActiveRole()?>" />
                            <input type="hidden" name="group" value="<?php echo $ENTRADA_USER->getActiveGroup()?>" />
                        </form>
                        <script type="text/javascript">
                            jQuery(document).ready(function() {
                                jQuery("#header_rederict").submit();
                            });
                        </script>
                       <?php
                    } else {
                       header("Location: ".$file_record["link_url"]);
                    }
                } else {
                    if ($file_record["session_variables"]) {
                        if ($file_record["iframe_resize"]) {
                            $iframe = "<iframe class='iframeSize'  width='100%' scrolling='no' src='" . $file_record["link_url"] . "?course_id=" . $course_id['course_id'] . "&community_id=" . $COMMUNITY_ID . "&username=" . $ENTRADA_USER->getUsername() .  "&firstname=" . $ENTRADA_USER->getFirstname() . "&lastname=" . $ENTRADA_USER->getLastname() . "&role=" . $ENTRADA_USER->getActiveRole() . "&group=" . $ENTRADA_USER->getActiveGroup() . "&email=" . $ENTRADA_USER->getEmail() . "'></iframe>";
                    } else {
                            $iframe = "<iframe class='iframeSize'  width='100%' src='" . $file_record["link_url"] . "?course_id=" . $course_id['course_id'] . "&community_id=" . $COMMUNITY_ID . "&username=" . $ENTRADA_USER->getUsername() .  "&firstname=" . $ENTRADA_USER->getFirstname() . "&lastname=" . $ENTRADA_USER->getLastname()  . "&role=" . $ENTRADA_USER->getActiveRole() . "&group=" . $ENTRADA_USER->getActiveGroup() .  "&email=" . $ENTRADA_USER->getEmail() ."'></iframe>";
                        }
                    } else {
                        $iframe = "<iframe class='iframeSize' src='" . $file_record["link_url"] . "'></iframe>";
                    }
                }
			}

			if (($ERROR) || ($NOTICE)) {
				$ERROR++;
				$ERRORSTR[] = "<strong>Unable to download the selected file.</strong><br /><br />The file you have selected cannot be downloaded at this time, ".(($LOGGED_IN) ? "please try again later." : "Please log in to continue.");
			}

			if ($NOTICE) {
				echo display_notice();
			}
			if ($ERROR) {
				echo display_error();
			}

		}
        
        if (shares_link_module_access($RECORD_ID, "view-link")) {

            Models_Community_Share::getParentsBreadCrumbs($file_record["cshare_id"]);
            $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-link&id=".$RECORD_ID, "title" => limit_chars($file_record["link_title"], 32));

            $community_shares_select = community_shares_in_select_hierarchy($file_record["cshare_id"], $file_record["parent_folder_id"], $PAGE_ID);
            ?>
                <?php
            /**
             * If there is time release properties, display them to the browsing users.
             */
            if (($release_date = (int) $file_record["release_date"]) && ($release_date > time())) {
                $NOTICE++;
                $NOTICESTR[] = "This link will not be accessible to others until <strong>".date(DEFAULT_DATE_FORMAT, $release_date)."</strong>.";
            } elseif ($release_until = (int) $file_record["release_until"]) {
                if ($release_until > time()) {
                    $NOTICE++;
                    $NOTICESTR[] = "This link will be accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong>.";
                } else {
                    /**
                     * Only administrators or people who wrote the post will get this.
                     */
                    $NOTICE++;
                    $NOTICESTR[] = "This link was only accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong> by others.";
                }
            }

            if ($NOTICE) {
                echo display_notice();
            }
            if ($NAVIGATION) {
                echo "	<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
                echo "	<tbody>\n";
                echo "		<tr>\n";
                echo "			<td style=\"text-align: left\">\n".(((int) $NAVIGATION["back"]) ? "<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-link&amp;id=".(int) $NAVIGATION["back"]."\">&laquo; Previous Link</a>" : "&nbsp;")."</td>";
                echo "			<td style=\"text-align: right\">\n".(((int) $NAVIGATION["next"]) ? "<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-link&amp;id=".(int) $NAVIGATION["next"]."\">Next Link &raquo;" : "&nbsp;")."</td>";
                echo "		</tr>\n";
                echo "	</tbody>\n";
                echo "	</table>\n";
            }
            ?>
            <a name="top"></a>
            <h4 id="file-<?php echo $RECORD_ID; ?>-title"><?php echo html_encode($file_record["link_title"]); ?></h4>
            <div>
                <?php echo html_encode($file_record["link_description"]); ?>
            </div>
            <?php 
            if ($file_record['iframe_resize']) {
            ?>
            <!--    This section is extra code to get the iframe to resize on cross domain pages, only works when you have control over the remote site.
                    Include this link on the remote site.
                    <script type="text/javascript" src="../js/iframeResizer.contentWindow.min.js"></script> 
                    for documentation go here: https://github.com/davidjbradshaw/iframe-resizer
            -->
            <script type="text/javascript">
              //MDN PolyFil for IE8 (This is not needed if you use the jQuery version)
              if (!Array.prototype.forEach){
                Array.prototype.forEach = function(fun /*, thisArg */){
                  "use strict";
                  if (this === void 0 || this === null || typeof fun !== "function") throw new TypeError();

                  var
                    t = Object(this),
                    len = t.length >>> 0,
                    thisArg = arguments.length >= 2 ? arguments[1] : void 0;

                  for (var i = 0; i < len; i++)
                    if (i in t)
                      fun.call(thisArg, t[i], i, t);
                };
              }
            </script>
            <?php $HEAD[] = "<meta content='IE=edge' http-equiv='X-UA-Compatible'>";
            } //ends iframe reisze            
            ?>
            <div id="loadLink"><?php echo $iframe;?></div>
            <?php 
            if ($file_record['iframe_resize']) {
            ?>
            <script type="text/javascript" src="<?php echo ENTRADA_URL . '/javascript/iframeResizer.min.js';?>"></script>
            <script type="text/javascript">
                iFrameResize({
                    log                     : false,                  // Enable console logging
                    autoResize              : true,
                    enablePublicMethods     : true                  // Enable methods within iframe hosted page 
                });
            </script>
            <?php
            }//ends iframe resize
           
            if ($LOGGED_IN) {
                add_statistic("community:".$COMMUNITY_ID.":shares", "link_view", "cslink_id", $RECORD_ID);
            }
        } else {
            if ($ERROR) {
                echo display_error();
            }
            if ($NOTICE) {
                echo display_notice();
            }
        }
	} else {
		application_log("error", "The provided link id was invalid [".$RECORD_ID."] (View Link).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No link id was provided to view. (View Link)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
?>