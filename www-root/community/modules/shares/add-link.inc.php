<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Used to upload links to a specific folder of a community.
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

$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/shares.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Add Link</h1>\n";

$isCommunityCourse = Models_Community_Course::is_community_course($COMMUNITY_ID);

if ($RECORD_ID) {
    $query			= "SELECT * FROM `community_shares` WHERE `cshare_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
    $folder_record	= $db->GetRow($query);
    if ($folder_record) {
        if (shares_module_access($RECORD_ID, "add-link")) {
            $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$folder_record["cshare_id"], "title" => limit_chars($folder_record["folder_title"], 32));
            $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-link&id=".$RECORD_ID, "title" => "Upload Link");

            if ($isCommunityCourse) {
                $course_groups_query = "SELECT a.*, b.`course_code`, b.`course_name`
                          FROM `course_groups` AS a
                          JOIN `courses` AS b
                          ON b.`course_id` = a.`course_id`
                          JOIN `community_courses` AS c
                          ON c.`course_id` = b.`course_id`
                          WHERE a.`active` = 1
                          AND c.`community_id` = ".$db->qstr($COMMUNITY_ID);
                $community_course_groups = $db->GetAll($course_groups_query);
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function() {
                       function hideCourseGroups() {
                           if (jQuery("#course-group-checkbox").is(':checked')) {
                                jQuery(".course-group-permissions").show();
                           } else {
                                jQuery(".course-group-permissions").hide();
                           }
                       }
                       //Set the initial UI state
                       hideCourseGroups();

                       jQuery(".permission-type-checkbox").click(function() {
                           hideCourseGroups();
                       });
                    });
                </script>
                <?php
            }

            $link_uploads = array();
            // Error Checking
            switch($STEP) {
                case 2 :
                    foreach($_POST["link_title"] as $tmp_link_id => $link_title){
                        /**
                        * Required field "title" / Link Title.
                        */
                        if ((isset($_POST["link_title"][$tmp_link_id])) && ($title = clean_input($_POST["link_title"][$tmp_link_id], array("notags", "trim")))) {
                            $PROCESSED["link_title"] = $title;
                            $link_uploads[$tmp_link_id]["link_title"] = $title;
                        } else {
                            $ERROR++;
                            $ERRORSTR[] = "The <strong>Link Title</strong> field is required.";
                        }

                        /**
                        * Required field "url" / Link URL.
                        */
                        if ((isset($_POST["link_url"][$tmp_link_id])) && ($link_url = clean_input($_POST["link_url"][$tmp_link_id], array("trim")))) {
                            $PROCESSED["link_url"] = $link_url;
                            $link_uploads[$tmp_link_id]["link_url"] = $link_url;
                        } else {
                            $ERROR++;
                            $ERRORSTR[] = "The <strong>Link URL</strong> field is required.";
                        }

                        /**
                        * Non-Required field "description" / Link Description.
                        *
                        */
                        if ((isset($_POST["link_description"][$tmp_link_id])) && $description = clean_input($_POST["link_description"][$tmp_link_id], array("notags", "trim"))) {
                            $PROCESSED["link_description"] = $description;
                            $link_uploads[$tmp_link_id]["link_description"] = $description;
                        } else {
                            $PROCESSED["link_description"] = "";
                            $link_uploads[$tmp_link_id]["link_description"] = "";
                        }

                        /**
                         * Non-Required field "access_method" / View Method.
                         */
                        if ((isset($_POST["access_method"][$tmp_link_id])) && clean_input($_POST["access_method"][$tmp_link_id], array("int")) == 1) {
                            $PROCESSED["access_method"] = 1;
                            $link_uploads[$tmp_link_id]["access_method"] = 1;
                        } else {
                            $PROCESSED["access_method"] = 0;
                            $link_uploads[$tmp_link_id]["access_method"] = 0;
                        }

                        /** Required "iframe_resize" / View Method.
                         */
                        if ((isset($_POST["iframe_resize"][$tmp_link_id])) && (clean_input($_POST["iframe_resize"][$tmp_link_id], array("int")) == 1)) {
                            $PROCESSED["iframe_resize"]	= 1;
                            $link_uploads[$tmp_link_id]["iframe_resize"] = 1;
                        } else {
                            $PROCESSED["iframe_resize"]	= 0;
                            $link_uploads[$tmp_link_id]["iframe_resize"] = 0;
                        }

                        /**
                         * Non-Required field "session_variables" / View Method.
                         */
                        if ((isset($_POST["session_variables"][$tmp_link_id])) && clean_input($_POST["session_variables"][$tmp_link_id], array("int")) == 1) {
                            $PROCESSED["session_variables"] = 1;
                            $link_uploads[$tmp_link_id]["session_variables"] = 1;
                        } else {
                            $PROCESSED["session_variables"] = 0;
                            $link_uploads[$tmp_link_id]["session_variables"] = 0;
                        }


                        /**
                         * Non-Required field "student_hidden" / View Method.
                         */
                        if ((isset($_POST["student_hidden"][$tmp_link_id])) && clean_input($_POST["student_hidden"][$tmp_link_id], array("int")) == 1) {
                            $PROCESSED["student_hidden"] = 1;
                            $link_uploads[$tmp_link_id]["student_hidden"] = 1;
                        } else {
                            $PROCESSED["student_hidden"] = 0;
                            $link_uploads[$tmp_link_id]["student_hidden"] = 0;
                        }

                        /**
                         * Required field "permission_acl_style" for community courses
                         */
                        if ($isCommunityCourse) {
                            if (!isset($_POST["permission_acl_style"])) {
                                $ERROR++;
                                $ERRORSTR[] = "The <strong>Permission Level</strong> field is required.";
                            }
                        }

                        /**
                         * Permission checking for member access.
                         */
                        if ((isset($_POST["allow_member_read"])) && (clean_input($_POST["allow_member_read"], array("int")) == 1)) {
                            $PROCESSED["allow_member_read"] = 1;
                        } else {
                            $PROCESSED["allow_member_read"] = 0;
                        }
                        if ((isset($_POST["allow_member_revision"])) && (clean_input($_POST["allow_member_revision"], array("int")) == 1)) {
                            $PROCESSED["allow_member_revision"] = 1;
                        } else {
                            $PROCESSED["allow_member_revision"] = 0;
                        }

                        /**
                         * Permission checking for troll access.
                         * This can only be done if the community_registration is set to "Open Community"
                         */
                        $PROCESSED["allow_troll_read"] = 0;
                        $PROCESSED["allow_troll_revision"] = 0;
                        if (!(int) $community_details["community_registration"]) {
                            if ((isset($_POST["allow_troll_read"])) && (clean_input($_POST["allow_troll_read"], array("int")) == 1)) {
                                $PROCESSED["allow_troll_read"] = 1;
                            }
                            if ((isset($_POST["allow_troll_revision"])) && (clean_input($_POST["allow_troll_revision"], array("int")) == 1)) {
                                $PROCESSED["allow_troll_revision"] = 1;
                            }
                        }

                        /**
                        * Required field "release_from" / Release Start (validated through validate_calendars function).
                        * Non-required field "release_until" / Release Finish (validated through validate_calendars function).
                        */
                        $release_dates = validate_calendars("release", true, false);
                        if ((isset($release_dates["start"])) && ((int) $release_dates["start"])) {
                            $PROCESSED["release_date"]	= (int) $release_dates["start"];
                        } else {
                            $ERROR++;
                            $ERRORSTR[] = "The <strong>Release Start</strong> field is required.";
                        }
                        if ((isset($release_dates["finish"])) && ((int) $release_dates["finish"])) {
                            $PROCESSED["release_until"]	= (int) $release_dates["finish"];
                        } else {
                            $PROCESSED["release_until"]	= 0;
                        }

                        if (!$ERROR) {
                            $PROCESSED["cshare_id"]		= $RECORD_ID;
                            $PROCESSED["community_id"]	= $COMMUNITY_ID;
                            $PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
                            $PROCESSED["link_active"]	= 1;
                            $PROCESSED["updated_date"]	= time();
                            $PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

                            unset($PROCESSED["cslink_id"]);
                            if ($db->AutoExecute("community_share_links", $PROCESSED, "INSERT")) {
                                if ($LINK_ID = $db->Insert_Id()) {
                                    //Add course group permissions to community_acl_groups
                                    if ($_POST['permission_acl_style'] === 'CourseGroupMember' && $community_course_groups && !empty($community_course_groups)) {
                                        foreach ($community_course_groups as $community_course_group) {
                                            //Set the default value to '0'
                                            $PROCESSED[$community_course_group['cgroup_id']] = array("create" => 0, "read" => 0, "update" => 0, "delete" => 0);

                                            if ($_POST[$community_course_group['cgroup_id']]) {
                                                foreach ($_POST[$community_course_group['cgroup_id']] as $perms) {
                                                    //Update the value to '1' if it was submitted
                                                    $PROCESSED[$community_course_group['cgroup_id']][clean_input($perms)] = 1;
                                                }
                                            }

                                            $db->AutoExecute("community_acl_groups", array("cgroup_id" => $community_course_group['cgroup_id'], "resource_type" => "communitylink", "resource_value" => $LINK_ID, "create" => $PROCESSED[$community_course_group['cgroup_id']]['create'], "read" => $PROCESSED[$community_course_group['cgroup_id']]['read'], "update" => $PROCESSED[$community_course_group['cgroup_id']]['update'], "delete" => $PROCESSED[$community_course_group['cgroup_id']]['delete']), "INSERT");
                                        }
                                    }

                                    //If the user's role is 'admin', use the submitted form values
                                    if ($COMMUNITY_ADMIN) {
                                        $update_perm = array(
                                            'read' => (($_POST['read']) ? 1 : 0),
                                            'create' => (($_POST['create']) ? 1 : 0),
                                            'update' => (($_POST['update']) ? 1 : 0),
                                            'delete' => (($_POST['delete']) ? 1 : 0),
                                            'assertion' => $_POST['permission_acl_style']
                                        );
                                    } else {
                                        //If the user is not an admin, set these default permissions
                                        $update_perm = array(
                                            'read' => 1,
                                            'create' => 0,
                                            'update' => 1,
                                            'delete' => 0,
                                            'assertion' => $_POST['permission_acl_style']
                                        );
                                    }
                                    $results = $db->AutoExecute("`community_acl`", array(
                                            "resource_type" => "communitylink",
                                            "resource_value" => $LINK_ID,
                                            "create" => $update_perm['create'],
                                            "read" => $update_perm['read'],
                                            "update" => $update_perm['update'],
                                            "delete" => $update_perm['delete'],
                                            "assertion" => $update_perm['assertion']
                                        ),
                                        "INSERT"
                                    );
                                    if ($results === false) {
                                        $ERROR++;
                                        $ERRORSTR[] = "Error updating the community ACL.";
                                    }
                                    $PROCESSED["cslink_id"] = $LINK_ID;

                                    if (!$ERROR) {
                                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added <strong>%s</strong>."), $PROCESSED["link_title"]), "success", $MODULE);
                                        add_statistic("community:" . $COMMUNITY_ID . ":shares", "link_add", "cslink_id", $VERSION_ID);
                                        communities_log_history($COMMUNITY_ID, $PAGE_ID, $LINK_ID, "community_history_add_link", 1, $RECORD_ID);
                                        if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
                                            community_notify($COMMUNITY_ID, $LINK_ID, "link", COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-link&id=" . $link_ID, $RECORD_ID, $PROCESSES["release_date"]);
                                        }

                                        $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-folder&id=" . $RECORD_ID;
                                        header("Location: " . $url);
                                        exit;
                                    }
                                }
                            }

                            if (!$SUCCESS) {
                                /**
                                * Because there was no success, check if the link_id was set... if it
                                * was we need to delete the database record :( In the future this will
                                * be handled with transactions like it's supposed to be.
                                */
                                if ($LINK_ID) {
                                    $query	= "DELETE FROM `community_share_links` WHERE `cslink_id` = ".$db->qstr($LINK_ID)." AND `cshare_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." LIMIT 1";
                                    @$db->Execute($query);
                                }


                                $ERROR++;
                                $ERRORSTR[]	= "Unable to store the new link on the server; the MEdTech Unit has been informed of this error, please try again later.";

                                //application_log("error", "Failed to move the uploaded Community link to the storage directory [".COMMUNITY_STORAGE_DOCUMENTS."/".$VERSION_ID."].");
                            }
                        }

                        if ($ERROR) {
                            $STEP = 1;
                        }
                    }//end key value loop
                break;
                case 1 :
                default :
                    continue;
                    break;
            }

            // Page Display
            switch($STEP) {
                case 1 :
                default :
                    if(count($link_uploads)<1){
                        $link_uploads[] = array();
                    }
                    if ($ERROR) {
                        echo display_error();
                        add_notice("There was an error while trying to upload your link(s). You will need to reselect the link(s) you wish to upload.");
                    }
                    if ($NOTICE) {
                        echo display_notice();
                    }
                    ?>

                <?php

                function output_link_template($link_id = "#{link_id}", $link_num = "#{link_number}") {
                    return
                        '<div id="link_'.$link_id.'" class="link-upload">'.
                        '<h2>Link '.$link_num.' Details</h2>'.
                        ($link_id == "#{link_id}" ? '<div style="text-align: right">(<a class="action" href="#" onclick="$(\'link_#{link_id}\').remove();">remove</a>)</div>' : "").
                        '<div class="control-group">'.
                        '    <label class="control-label form-required" for="link_url_link">Link URL</label>'.
                        '    <div class="controls">'.
                        '        <input type="text" style="width: 80%" id="link_url_'.$link_id.'" value="http://" name="link_url['.$link_id.']" onchange="fetchLinkname('.$link_id.')" />'.
                        '    </div>'.
                        '</div>'.
                        '<div class="control-group">'.
                        '    <label class="control-label form-required" for="link_title">Link Title</label>'.
                        '    <div class="controls">'.
                        '        <input type="text" style="width: 80%" id="link_'.$link_id.'_title" name="link_title['.$link_id.']" value="" maxlength="84"/>'.
                        '    </div>'.
                        '</div>'.
                        '<div class="control-group">'.
                        '    <label class="control-label form-nrequired" for="link_description">Link Description</label>'.
                        '    <div class="controls">'.
                        '        <textarea id="link_'.$link_id.'_description" style="width: 80%;" class="link_description" name="link_description['.$link_id.']"></textarea>'.
                        '    </div>'.
                        '</div>'.
                        '   <h2 title="Advanced Settings Section '.$link_num.'" class="collapsed" style="font-size: 13px; cursor: pointer">Advanced Settings</h2>'.
                        '   <div id="advanced-settings-section-'.$link_num.'">'.
                                output_advanced_settings($link_id) .
                        '   </div>'.
                        '</div>'.
                        '</div>';
                }
                    
                function output_advanced_settings($link_id) {
                    return
                        '   <div class="control-group">'.
                        '    <label class="control-label form-nrequired" for="link_'.$link_id.'_description">Access Method</label>'.
                        '       <div class="controls">'.
                        '           <label for="access_method_0_'.$link_id.'" class="radio">' .
                        '           <input type="radio" id="access_method_0_'.$link_id.'" name="access_method['.$link_id.']" value="0" />' .
                        '           Open this URL in a <?php echo APPLICATION_NAME;?> iframe page. (May not work with all sites)</label>' .
                        '           <label for="access_method_1_'.$link_id.'" class="radio"><input type="radio" id="access_method_1_'.$link_id.'" name="access_method['.$link_id.']" value="1" checked=checked>Open this URL in a new window ' .
                        '           </label>' .
                        '       </div>'.
                        '       <hr>'.
                        '   </div>'.
                        '   <div class="control-group">'.
                        '    <label for="iframe_resize_" class="control-label form-nrequired">Iframe Javascript Resizing</label>' .
                        '       <div class="controls" id="iframe_resize_'.$link_id.'">'.
                        '           <label for="iframe_resize_1_'.$link_id.'" class="radio"><input type="radio" id="iframe_resize_1_'.$link_id.'" name="iframe_resize['.$link_id.']" value="0" style="vertical-align: middle" />Use iframe javascript resizer method</label>'.
                        '           <label for="iframe_resize_0_'.$link_id.'" class="radio"><input type="radio" id="iframe_resize_0_'.$link_id.'" name="iframe_resize['.$link_id.']" value="1" style="vertical-align: middle" checked="checked"/>No iframe resizing.</label>' .
                        '       </div>'.
                        '       <hr>'.
                        '   </div>'.
                        '   <div class="control-group">'.
                        '       <label for="session_variables" class="control-label form-nrequired">Session Variables</label>' .
                        '       <div class="controls">'.
                        '           <label for="session_var_0_'.$link_id.'" class="radio"><input type="radio" id="session_var_0_'.$link_id.'" name="session_variables['.$link_id.']" value="0" style="vertical-align: middle" checked="checked" />No variables passed.</label>' .
                        '           <label for="session_var_1_'.$link_id.'" class="radio"><input type="radio" id="session_var_1_'.$link_id.'" name="session_variables['.$link_id.']" value="1" style="vertical-align: middle" />Pass variables.</label>' .
                        '       </div>'.
                        '       <hr>'.
                        '   </div>'.
                        '   <div class="control-group">'.
                        '       <label for="student_hidden" class="control-label form-nrequired">Would you like to hide this link from students?</label>' .
                        '       <div class="controls">'.
                        '           <label for="student_hidden_0_'.$link_id.'" class="radio"><input type="radio" id="student_hidden_0_'.$link_id.'" name="student_hidden['.$link_id.']" value="0" style="vertical-align: middle" checked="checked"/>Allow students to view this link.</label>' .
                        '           <label for="student_hidden_1_'.$link_id.'" class="radio"><input type="radio" id="student_hidden_1_'.$link_id.'" name="student_hidden['.$link_id.']" value="1" style="vertical-align: middle" />Hide this link from students.</label>' .
                        '       </div>'.
                        '       <hr>'.
                        '   </div>';
                }
                ?>

                <script>
                    var addLinkHTML = "<?php echo addslashes(output_link_template()); ?>";

                </script>

                <style>
                .page-action li a {
                    color:#FFF;
                    font-weight:700;
                }
                .page-action li {
                    display:inline;
                    background: none;
                    padding:0;
                }
                .iframe-resize-control {
                    display: none;
                }
                .link_description {
                    width: 500px;
                    resize: vertical;
                }
                </style>
                <script type="text/javascript">
                    jQuery(document).ready(function() {                                
                        jQuery(document).on({ 
                             click: function () {
                                var target_id = jQuery(this).data('id');
                                var target_value = jQuery(this).val();

                                if (target_value == 1) {
                                    // hide iframe
                                    jQuery('#iframe_resize_' + target_id).hide();
                                } else {
                                    //show iframe
                                    jQuery('#iframe_resize_' + target_id).show();
                                }
                             }
                         }, ".access_method"); //pass the element as an argument to .on

                        jQuery("#addLink").on("click", function() {
                            addLink();
                        })
                    });


                </script>
                <div style="float: right">
                    <ul class="page-action">
                        <li><a id="addLink" style="cursor: pointer" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> Add Another Link</a></li>
                    </ul>
                </div>

                <form id="upload-link-form"  class="form-horizontal" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-link&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post">
                    <table class="community-add-table" summary="Upload Link">
                        <colgroup>
                            <col style="width: 3%" />
                            <col style="width: 20%" />
                            <col style="width: 77%" />
                        </colgroup>
                        <tbody>
                            <tr>
                                <td colspan="3">
                                    <div id="link_list">
                                        <?php
                                        foreach ($link_uploads as $tmp_link_id => $link_upload) {
                                            if (!$link_upload["success"]) {
                                                echo output_link_template("0", "1");
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3"><h2>Batch Link Permissions</h2></td>
                            </tr>

                            <?php if ($isCommunityCourse) { ?>
                            <tr>
                                <td colspan="2" style="vertical-align: top !important">
                                    <label for="permission_level" class="form-required">Permission Level: </label>
                                </td>
                                <td style="vertical-align: top">
                                    <table class="table table-bordered no-thead">
                                        <colgroup>
                                            <col style="width: 5%" />
                                            <col style="width: auto" />
                                        </colgroup>
                                        <tr>
                                            <td><input id="community-all-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseCommunityEnrollment" checked="checked" /></td>
                                            <td><label for="community-all-checkbox" class="content-small">All Community Members</label></td>
                                        </tr>
                                        <tr>
                                            <td><input id="course-group-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseGroupMember" /></td>
                                            <td><label for="course-group-checkbox" class="content-small">Course Groups</label></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <?php if ($COMMUNITY_ADMIN) { ?>
                                <tr class="file-permissions">
                                    <td colspan="3"><h3>File Permissions</h3></td>
                                </tr>
                                <tr class="file-permissions">
                                    <td colspan="3">
                                        <table class="table table-bordered table-striped table-community-centered">
                                            <colgroup>
                                                <col style="width: 50%" />
                                                <col style="width: 50%" />
                                            </colgroup>
                                            <thead>
                                            <tr>
                                                <td>View File</td>
                                                <td style="border-left: none">Upload New Version</td>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td class="on"><input type="checkbox" id="read" name="read" value="read" checked="checked" /></td>
                                                <td><input type="checkbox" id="update" name="update" value="update" /></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            <?php } ?>
                            <tr class="course-group-permissions">
                                <td colspan="3"><h3>Course Group Permissions</h3></td>
                            </tr>
                            <tr class="course-group-permissions">
                                <td colspan="3">
                                    <?php
                                    $course_ids = array_unique(array_map(function($item) { return (int)$item['course_id']; }, $community_course_groups));
                                    foreach ($course_ids as $course_id) {
                                        $course_groups = array_filter($community_course_groups, function($item) use ($course_id) {
                                            return (int)$item['course_id'] === $course_id;
                                        });
                                        usort($course_groups, function($a, $b) {
                                            if ($a['group_name'] < $b['group_name']) {
                                                return -1;
                                            } else if ($a['group_name'] > $b['group_name']) {
                                                return 1;
                                            } else {
                                                return 0;
                                            }
                                        });
                                        $course_code = $course_groups[0]['course_code'];
                                        $course_name = $course_groups[0]['course_name'];

                                        echo "<h4>$course_code: $course_name</h4>";
                                        ?>
                                        <table class="table table-striped table-bordered table-community-centered-list">
                                            <colgroup>
                                                <col style="width: 40%" />
                                                <col style="width: 30%" />
                                                <col style="width: 30%" />
                                            </colgroup>
                                            <thead>
                                            <tr>
                                                <td>Group</td>
                                                <td style="border-left: none">View Link</td>
                                                <td style="border-left: none">Upload New Version</td>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php

                                            foreach ($course_groups as $course_group) {
                                                ?>
                                                <tr>
                                                    <td class="left"><strong><?php echo $course_group['group_name']; ?></strong></td>
                                                    <td class="on"><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_read" name="<?php echo $course_group['cgroup_id']; ?>[]" value="read" /></td>
                                                    <td><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_update" name="<?php echo $course_group['cgroup_id']; ?>[]" value="update" /></td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    <?php
                                    }
                                    ?>

                                    <?php if (!(int) $community_details["community_registration"]) { ?>
                                        <h4>Non-members</h4>
                                        <table class="table table-striped table-bordered  table-community-centered-list">
                                            <colgroup>
                                                <col style="width: 40%" />
                                                <col style="width: 30%" />
                                                <col style="width: 30%" />
                                            </colgroup>
                                            <thead>
                                            <tr>
                                                <td>Group</td>
                                                <td style="border-left: none">View Link</td>
                                                <td style="border-left: none">Upload New Version</td>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td class="left"><strong>Browsing Non-Members</strong></td>
                                                <td class="on"><input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                                <td><input type="checkbox" id="allow_troll_revision" name="allow_troll_revision" value="1"<?php echo (((isset($PROCESSED["allow_troll_revision"])) && ($PROCESSED["allow_troll_revision"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    <?php } ?>
                                    </td>
                                </tr>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="3">
                                            <table class="table table-striped table-bordered table-community-centered-list">
                                                <colgroup>
                                                    <col style="width: 40%" />
                                                    <col style="width: 30%" />
                                                    <col style="width: 30%" />
                                                </colgroup>
                                                <thead>
                                                <tr>
                                                    <td>Group</td>
                                                    <td style="border-left: none">View Link</td>
                                                    <td style="border-left: none">Upload New Version</td>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td class="left"><strong>Community Administrators</strong></td>
                                                    <td class="on"><input type="checkbox" id="allow_admin_read" name="allow_admin_read" value="1" checked="checked" onclick="this.checked = true" /></td>
                                                    <td><input type="checkbox" id="allow_admin_revision" name="allow_admin_revision" value="1" checked="checked" onclick="this.checked = true" /></td>
                                                </tr>
                                                <tr>
                                                    <td class="left"><strong>Community Members</strong></td>
                                                    <td class="on"><input type="checkbox" id="allow_member_read" name="allow_member_read" value="1"<?php echo (((!isset($PROCESSED["allow_member_read"])) || ((isset($PROCESSED["allow_member_read"])) && ($PROCESSED["allow_member_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                                    <td><input type="checkbox" id="allow_member_revision" name="allow_member_revision" value="1"<?php echo ((((isset($PROCESSED["allow_member_revision"])) && ($PROCESSED["allow_member_revision"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                                </tr>
                                                <?php if (!(int)$community_details["community_registration"]) { ?>
                                                    <tr>
                                                        <td class="left"><strong>Browsing Non-Members</strong></td>
                                                        <td class="on"><input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo ((((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                                        <td><input type="checkbox" id="allow_troll_revision" name="allow_troll_revision" value="1"<?php echo ((((isset($PROCESSED["allow_troll_revision"])) && ($PROCESSED["allow_troll_revision"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                                    </tr>
                                                <?php } ?>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td colspan="3"><h2>Batch Time Release Options</h2></td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <table class="date-time">
                                            <?php
                                            echo generate_calendars("release", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0));
                                            ?>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="padding-top: 15px; text-align: right">
                                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                <?php
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
        application_log("error", "The provided folder id was invalid [".$RECORD_ID."] (Upload link).");

        header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
        exit;
    }
} else {
    application_log("error", "No folder id was provided to upload into. (Upload link)");

    header("Location: " . COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL);
    exit;
}
