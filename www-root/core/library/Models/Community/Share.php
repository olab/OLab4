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
 * A model for handling document shares
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 */
class Models_Community_Share extends Models_Base {
    protected   $cshare_id,
                $community_id,
                $cpage_id,
                $parent_folder_id,
                $folder_title,
                $folder_description,
                $folder_icon,
                $folder_order,
                $folder_active,
                $student_hidden,
                $admin_notifications,
                $allow_public_read,
                $allow_public_upload,
                $allow_public_comment,
                $allow_troll_read,
                $allow_troll_upload,
                $allow_troll_comment,
                $allow_member_read,
                $allow_member_upload,
                $allow_member_comment,
                $release_date,
                $release_until,
                $updated_date,
                $updated_by;

    protected static $table_name = "community_shares";
    protected static $default_sort_column = "folder_order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getCShareID() {
        return $this->cshare_id;
    }

    public function setCShareID($cshare_id) {
        $this->cshare_id = $cshare_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }
    public function getCPageID() {
        return $this->cpage_id;
    }

    public function getParentFolderID() {
        return $this->parent_folder_id;
    }

    public function setParentFolderID($parent_folder_id) {
        $this->parent_folder_id = $parent_folder_id;
    }

    public function getFolderTitle() {
        return $this->folder_title;
    }

    public function getFolderDescription() {
        return $this->folder_description;
    }

    public function getFolderIcon() {
        return $this->folder_icon;
    }

    public function getFolderOrder() {
        return $this->folder_order;
    }

    public function getFolderActive() {
        return $this->folder_active;
    }

    public function getStudentHidden() {
        return $this->student_hidden;
    }

    public function getAdminNotifications() {
        return $this->admin_notifications;
    }

    public function getAllowPublicRead() {
        return $this->allow_public_read;
    }

    public function getAllowPublicUpload() {
        return $this->allow_public_upload;
    }

    public function getAllowPublicComment() {
        return $this->allow_public_comment;
    }

    public function getAllowTrollRead() {
        return $this->allow_troll_read;
    }

    public function getAllowTrollUpload() {
        return $this->allow_troll_upload;
    }

    public function geAllowTrollComment() {
        return $this->allow_troll_comment;
    }

    public function getAllowMemberRead() {
        return $this->allow_member_read;
    }

    public function getAllowMemberUpload() {
        return $this->allow_member_upload;
    }

    public function getAllowMemberComment() {
        return $this->allow_member_comment;
    }

    public function getReleaseDate() {
        return $this->release_date;
    }

    public function getReleaseUntil() {
        return $this->release_until;
    }

    public function getUpdateDate() {
        return $this->updated_date;
    }

    public function getUpdateBy() {
        return $this->updated_by;
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute($this->table_name, $this->toArray(), "INSERT")) {
            return $this;
        } else {
            return false;
        }
    }

    public function update() {
        global $db;
        if ($db->AutoExecute($this->table_name, $this->toArray(), "UPDATE", "`cshare_id` = " . $db->qstr($this->getCShareID()))) {
            return true;
        } else {
            return false;
        }
    }

    public static function fetchRowByCShareID($cshare_id = 0) {
        $self = new self();
        return $self->fetchRow(array("cshare_id" => $cshare_id));
    }

    public static function fetchAllByCPageID($cpage_id) {
        $self = new self();

        $constraints = array(
            array(
                "key"       => "cpage_id",
                "value"     => $cpage_id,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    /**
     * This will get a tree of all folders and documents that the current user
     * has access to in the given community. It will have enough details to
     * render the entire html for the document shares index. If $docs is false,
     * only gets folders.
     * 
     * @param int $community_id
     * @param int $page_id
     * @param bool $is_student
     * @param bool $docs
     * @return mixed
     */
    public static function getSharesIndex($community_id, $page_id, $is_student, $docs = true) {
        $is_community_course = Models_Community_Course::is_community_course($community_id);
        return static::getShareChildren($community_id, $page_id, 0, $is_student, $is_community_course, true, $docs);
    }
    
    /**
     * Gets the documents and folders that are children of the given folder
     * in the given community. Will return a tree with all subfolders if
     * $recursive is true. Will only return folders if $docs is false.
     * 
     * @global ADODB $db
     * @param int $community_id
     * @param int $page_id
     * @param int $cshare_id
     * @param bool $is_student
     * @param bool $recursive
     * @param bool $docs
     * @return mixed
     */
    public static function getShareChildren($community_id, $page_id, $cshare_id, $is_student, $is_community_course, $recursive = false, $docs = true) {
        global $db;
        $children = array();
        $folders_query = "
            SELECT `cshare_id` AS `id`, 'folder' AS `type`, `folder_title` AS `title`,
                `folder_description` AS `description`, `parent_folder_id` AS `parent_id`,
                `folder_icon`, `student_hidden`, `release_date`, `release_until`,
                `allow_public_read`, `allow_public_upload`,
                `allow_troll_read`, `allow_troll_upload`,
                `allow_member_read`, `allow_member_upload`
            FROM `community_shares`
            WHERE `parent_folder_id` = ".$db->qstr($cshare_id)."
            AND `community_id` = ".$db->qstr($community_id)."
            AND `cpage_id` = ".$db->qstr($page_id)."
            AND `folder_active` = 1
            ".($is_student ? "AND `student_hidden` = 0
                AND (`release_date` = 0 OR `release_date` < ".time().")
                AND (`release_until` = 0 OR `release_until` > ".time().")" : "")."
            ORDER BY `folder_order`, `folder_title` ASC";
        $folders_result = $db->GetAll($folders_query);
        if (is_array($folders_result) && !empty($folders_result)) {
            foreach ($folders_result as $folder) {
                if (static::isAllowed($community_id, $folder, "read", $is_community_course)) {
                    if ($recursive) {
                        $folder["children"] = static::getShareChildren($community_id, $page_id, $folder["id"], $is_student, $is_community_course, $recursive, $docs);
                        $folder["num_docs"] = 0;
                        foreach ($folder["children"] as $child) {
                            if ($child["type"] === "folder") {
                                $folder["num_docs"] += $child["num_docs"];
                            } else {
                                $folder["num_docs"]++;
                            }
                        }
                    }
                    $folder["actions"] = static::getActions($community_id, $folder, $is_community_course);
                    $children[] = $folder;
                }
            }
        }
        if ($docs) {
            $nodes_query = "
                SELECT a.* FROM
                    (SELECT `csfile_id` AS `id`, `file_title` AS `title`, 'file' AS `type`,
                        `student_hidden`, `release_date`, `release_until`, 0 AS `access_method`, `proxy_id`,
                        `allow_member_read`, `allow_member_revision`, `allow_troll_read`, `allow_troll_revision`
                    FROM `community_share_files` WHERE `cshare_id` = ".$db->qstr($cshare_id)."
                    AND `file_active` = 1
                    UNION ALL
                    SELECT `cslink_id` AS `id`, `link_title` AS `title`, 'link' AS `type`,
                        `student_hidden`, `release_date`, `release_until`, `access_method`, `proxy_id`,
                        `allow_member_read`, `allow_member_revision`, `allow_troll_read`, `allow_troll_revision`
                    FROM `community_share_links` WHERE `cshare_id` = ".$db->qstr($cshare_id)."
                    AND `link_active` = 1
                    UNION ALL
                    SELECT `cshtml_id` AS `id`, `html_title` AS `title`, 'html' AS `type`,
                        `student_hidden`, `release_date`, `release_until`, `access_method`, `proxy_id`,
                        `allow_member_read`, 0 AS `allow_member_revision`, `allow_troll_read`, 0 AS `allow_troll_revision`
                    FROM `community_share_html` WHERE `cshare_id` = ".$db->qstr($cshare_id)."
                    AND `html_active` = 1) AS a
                ".($is_student ? "WHERE a.`student_hidden` = 0
                    AND (a.`release_date` = 0 OR a.`release_date` < ".time().")
                    AND (a.`release_until` = 0 OR a.`release_until` > ".time().")" : "")."
                ORDER BY a.`title` ASC";
            $nodes_result = $db->GetAll($nodes_query);
            if (is_array($nodes_result) && !empty($nodes_result)) {
                foreach ($nodes_result as $node) {
                    if (static::isAllowed($community_id, $node, "read", $is_community_course)) {
                        $node["actions"] = static::getActions($community_id, $node, $is_community_course);
                        $children[] = $node;
                    }
                }
            }
        }
        return $children;
    }
    
    /**
     * Returns an array of actions for the pencil icon in community shares.
     * Each action has a "title" and "href", which can be used to output the
     * menu html.
     * 
     * @global bool $COMMUNITY_ADMIN
     * @param int $community_id
     * @param int $id
     * @return array
     */
    public static function getActions($community_id, $resource, $is_community_course = null) {
        $actions = array();
        switch ($resource["type"]) {
            case "folder" :
                if (static::isAllowed($community_id, $resource, "create", $is_community_course)) {
                    $actions[] = array("title" => "Add File", "href" => "?section=add-file&id={$resource["id"]}");
                    $actions[] = array("title" => "Add Link", "href" => "?section=add-link&id={$resource["id"]}");
                    $actions[] = array("title" => "Add HTML", "href" => "?section=add-html&id={$resource["id"]}");
                }
                if (static::isAllowed($community_id, $resource, "update", $is_community_course)) {
                    $actions[] = array("title" => "Edit Folder", "href" => "?section=edit-folder&id={$resource["id"]}");
                    $actions[] = array("title" => "Move Folder", "href" => "javascript:folderMove('{$resource["id"]}');");
                }
                if (static::isAllowed($community_id, $resource, "delete", $is_community_course)) {
                    $actions[] = array("title" => "Delete Folder", "href" => "javascript:folderDelete('{$resource["id"]}');");
                }
                break;
            case "file" :
            case "link" :
            case "html" :
                if (static::isAllowed($community_id, $resource, "update", $is_community_course)) {
                    $actions[] = array("title" => "Edit ".ucwords($resource["type"]), "href" => "?section=edit-{$resource["type"]}&id={$resource["id"]}");
                }
                if (static::isAllowed($community_id, $resource, "delete", $is_community_course)) {
                    $actions[] = array("title" => "Move ".ucwords($resource["type"]), "href" => "javascript:{$resource["type"]}Move('{$resource["id"]}');");
                    $actions[] = array("title" => "Delete ".ucwords($resource["type"]), "href" => "javascript:{$resource["type"]}Delete('{$resource["id"]}');");
                }
                break;
        }
        return $actions;
    }
    
    public static function isAllowed($community_id, $resource, $permission, $is_community_course = null) {
        global $ENTRADA_USER, $COMMUNITY_ADMIN, $COMMUNITY_MEMBER, $LOGGED_IN, $COMMUNITY_ACL;
        
        if ($is_community_course === null) {
            $is_community_course = Models_Community_Course::is_community_course($community_id);
        }
        
        if ($COMMUNITY_ADMIN) {
            return true;
        } else if ($resource["type"] === "folder" && ($permission === "update" || $permission === "delete")) {
            return false;
        } else if ($permission === "delete" || ($permission === "update" && ($resource["type"] === "file" || $resource["type"] === "html"))) {
            return $resource["proxy_id"] === $ENTRADA_USER->getID();
        } else if (($ENTRADA_USER->getActiveGroup() === "student" && $resource["student_hidden"]) ||
                (($resource["release_date"] && $resource["release_date"] > time()) ||
                ($resource["release_until"] && $resource["release_until"] < time()))) {
            return false;
        } else if ($is_community_course) {
            return $COMMUNITY_ACL->amIAllowed("community{$resource["type"]}", $resource["id"], $permission);
        } else if ($permission === "read" &&
                (($resource["allow_member_read"] && $COMMUNITY_MEMBER) ||
                ($resource["allow_troll_read"] && $LOGGED_IN) ||
                ($resource["type"] === "folder" && $resource["allow_public_read"]))) {
            return true;
        } else if ($permission === "create" && $resource["type"] === "folder" &&
                (($resource["allow_member_upload"] && $COMMUNITY_MEMBER) ||
                ($resource["allow_troll_upload"] && $LOGGED_IN) ||
                ($resource["allow_public_upload"]))) {
            return true;
        } else if ($permission === "update" &&
                ($resource["type"] === "link" || $resource["type"] === "file") &&
                ($resource["allow_member_revision"] && $COMMUNITY_MEMBER) ||
                ($resource["allow_troll_revision"] && $LOGGED_IN)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Given the output of getShareChildren, this will output an array of strings
     * with the folder hierarchy, e.g.:
     * 
     * Folder1
     * -Subfolder1
     * -Subfolder2
     * --Subsubfolder1
     * Folder2
     * -Subfolder3
     * 
     * Each array element will have a "title" (the text) and an "id" (the folder id).
     * 
     * @param mixed $folders
     * @param string $prefix
     * @return mixed
     */
    public static function getSelectHierarchy($folders, $prefix = "") {
        $hierarchy = array();
        if (is_array($folders) && !empty($folders)) {
            foreach ($folders as $folder) {
                if ($folder["type"] !== "folder") {
                    continue;
                }
                $hierarchy[] = array("title" => $prefix.$folder["title"], "id" => $folder["id"]);
                $sub_hierarchy = static::getSelectHierarchy($folder["children"], "-{$prefix}");
                $hierarchy = array_merge($hierarchy, $sub_hierarchy);
            }
        }
        return $hierarchy;
    }
    
    /**
     * This will output the html for a given folder to be viewed on the index
     * page of community file shares.
     * 
     * @param mixed $share
     * @return string
     */
    public static function getIndexFolderHtml($share) {
        $output = "<li class=\"folder_container\" id=\"share_id_{$share['id']}\" data-cshare-id=\"{$share['id']}\" data-parent=\"{$share['parent_id']}\">\n";
        $output .= "<span class=\"iconPlaceholder".(empty($share["children"]) ? "" : " i-hidden")."\"></span>\n";
        $output .= "<i class=\"float-left point-right share_id_{$share['id']}".(empty($share["children"]) ? " i-hidden" : "")."\" id=\"share_id_{$share['id']}\"></i>\n";
        $output .= "<div class=\"folder_sub_loop\">\n";
        $output .= "<span id=\"folder_id_{$share['id']}\" class=\"folderIcon folder-{$share['folder_icon']}".($share['student_hidden'] ? "-hidden" : "")."\"></span>\n";
        $output .= "<ul class=\"folderUL\">\n";
        $output .= "<li class=\"folderShare\">\n";
        $output .= "<div>\n";
        $output .= "<a".($share['student_hidden'] ? " class=\"hidden_shares\"" : "")." id=\"folder-{$share['id']}-title\" href=\"?section=view-folder&id={$share['id']}\" style=\"font-weight: bold\">".html_encode($share['title'])."</a>\n";
        $output .= "<span class=\"content-small\">({$share['num_docs']} documents)</span>\n";
        if (($share["release_date"] && $share["release_date"] > time()) || ($share["release_until"] && $share["release_until"] < time())) {
            $output .= "<span><i class=\"icon-time\"></i> </span>\n";
        }
        if (!empty($share["actions"])) {
            $output .= "<div class=\"btn-group share-edit-btn\">\n";
            $output .= "<button class=\"btn btn-mini dropdown-toggle\" data-toggle=\"dropdown\"><i class=\"fa fa-cog\" aria-hidden=\"true\"></i></button>\n";
            $output .= "<ul class=\"dropdown-menu toggle-left\">\n";
            foreach ($share["actions"] as $action) {
                $output .= "<li><a class=\"action\" href=\"{$action['href']}\">{$action['title']}</a></li>\n";
            }
            $output .= "</ul>\n";
            $output .= "</div>\n";
        }
        $output .= "<div class=\"content-small\">".html_encode(limit_chars($share["description"], 125))."</div>\n";
        $output .= "</div>\n";
        $output .= "<div class=\"clear\"></div>\n";
        $direct_folder_children = 0;
        $direct_doc_children = 0;
        foreach ($share["children"] as $child) {
            if ($child["type"] === "folder") {
                $direct_folder_children++;
            } else {
                $direct_doc_children++;
            }
        }
        if ($direct_folder_children > 0) {
            $output .= "<ul class=\"folderUL subshare\">\n";
            foreach ($share["children"] as $child) {
                if ($child["type"] === "folder") {
                    $output .= self::getIndexFolderHtml($child);
                }
            }
            $output .= "</ul>\n";
            $output .= "<div class=\"clear\"></div>\n";
        }
        if ($direct_doc_children > 0) {
            $output .= "<div class=\"clear\"></div>\n";
            $output .= "<ul class=\"fileUL subshare\">\n";
            foreach ($share["children"] as $child) {
                if ($child["type"] === "folder") {
                    continue;
                }
                $ext = self::getExtension($child["id"], $child["type"], $child["student_hidden"]);
                $output .= "<li class=\"fileshare\">\n";
                $output .= "<img src=\"".ENTRADA_URL."/serve-icon.php?ext={$ext["ext"]}&hidden={$child["student_hidden"]}\" width=\"16\" height=\"16\" alt=\"{$ext["english"]}\" title=\"{$ext["english"]}\" style=\"vertical-align: middle; margin-right: 4px\" />\n";
                switch ($child["type"]) {
                    case "file" :
                        $output .= "<a".($child['student_hidden'] ? " class=\"hidden_shares\"" : "")." id=\"file-{$child['id']}-title\" href=\"?section=view-file&id={$child['id']}\" style=\"font-weight: bold; vertical-align: middle\">".limit_chars(html_encode($child["title"]), 50, true)."</a>\n";
                        $download_button = Models_Community_Share_File::getDownloadButton($child['id']);
                        $output .= $download_button;
                        break;
                    case "link" :
                        $output .= "<a".($child['student_hidden'] ? " class=\"hidden_shares\"" : "").($child["access_method"] ? " target=\"_blank\"" : "")." id=\"link-{$child['id']}-title\" href=\"?section=view-link&id={$child['id']}&access=".($child['access_method'] ? "header" : "entrada")."\" style=\"font-weight: bold; vertical-align: middle\">".limit_chars(html_encode($child["title"]), 50, true)."</a>\n";
                        break;
                    case "html" :
                        $output .= "<a".($child['student_hidden'] ? " class=\"hidden_shares\"" : "").($child["access_method"] ? " target=\"_blank\"" : "")." id=\"html-{$child['id']}-title\" href=\"?section=view-html&id={$child['id']}&access=".($child['access_method'] ? "header" : "entrada")."\" style=\"font-weight: bold; vertical-align: middle\">".limit_chars(html_encode($child["title"]), 50, true)."</a>\n";
                        break;
                }
                if (($child["release_date"] && $child["release_date"] > time()) || ($child["release_until"] && $child["release_until"] < time())) {
                    $output .= "<span><i class=\"icon-time\"></i> </span>\n";
                }
                if (!empty($child["actions"])) {
                    $output .= "<div class=\"btn-group share-edit-btn\">\n";
                    $output .= "<button class=\"btn btn-mini dropdown-toggle\" data-toggle=\"dropdown\"><i class=\"icon-pencil\"></i></button>\n";
                    $output .= "<ul class=\"dropdown-menu toggle-left\">\n";
                    foreach ($child["actions"] as $action) {
                        $output .= "<li><a class=\"action\" href=\"{$action['href']}\">{$action['title']}</a></li>\n";
                    }
                    $output .= "</ul>\n";
                    $output .= "</div>\n";
                }
                $output .= "</li>\n";
            }
            $output .= "</ul>\n";
        }
        $output .= "</li>\n";
        $output .= "</ul>\n";
        $output .= "</div>\n";
        $output .= "</li>\n";
        $output .= "<div class=\"clear\"></div>\n";
        return $output;
    }

    /**
     * This function looks up the file mimetype for a document share file
     *
     * @param int $id learning object id
     * @param string $type type of object
     * @param $hidden
     * @return bool $ext         file extension type - false if fails to find mimetype
     *
     * @global object $db
     */
    public static function getExtension($id, $type, $hidden) {
        global $db;
        $ext = false;
        switch ($type) {
            case "file":
                $sql = "    SELECT `file_mimetype`
                        FROM `community_share_file_versions`
                        WHERE `csfile_id` = " . $db->qstr($id) .  "
                        ORDER BY `file_version` DESC LIMIT 1 ";
                $mimetype = $db->GetOne($sql);

                break;
            case "link":
                $mimetype = "text/html";
                break;
            case "html":
                $mimetype = "text/html5";
                break;
        }

        if ($mimetype) {
            $sql = "    SELECT `ext`, `english`
                    FROM `filetypes`
                    WHERE `mime` = " . $db->qstr($mimetype) . "
                    AND `hidden` = " . $db->qstr($hidden) . "
                    ORDER BY `id` ASC LIMIT 1 ";
            $ext = $db->GetRow($sql);
        }

        return $ext;
    }
    
    /**
     * Gets the HTML for the edit menu for a folder/file/link/html document.
     * 
     * @param int $community_id
     * @param mixed $resource
     * @return string
     */
    public static function getEditMenu($community_id, $resource) {
        $actions = static::getActions($community_id, $resource);
        $output = "";
        if (!empty($actions)) {
            $output .= "<div class=\"btn-group share-edit-btn\">\n";
            $output .= "<button class=\"btn btn-mini dropdown-toggle space-right\" data-toggle=\"dropdown\"><i class=\"fa fa-cog\" aria-hidden=\"true\"></i></button>\n";
            $output .= "<ul class=\"dropdown-menu toggle-left\">\n";
            foreach ($actions as $action) {
                $output .= "<li><a class=\"action\" href=\"{$action['href']}\">{$action['title']}</a></li>\n";
            }
            $output .= "</ul>\n";
            $output .= "</div>\n";
        }
        return $output;
    }

    //checks if a folders parent is hidden
    //@param $id int - the record id for the file or link
    //return true if a folder is hidden.
    public static function parentFolderHidden($folder_id) {
        global $db;
        $query = "  SELECT `parent_folder_id`, `student_hidden`
                    FROM `community_shares`
                    WHERE `cshare_id` = " . $folder_id;
        $parent_record = $db->GetRow($query);

        //runs recursively till it finds a folder hidden or reaches the top level
        if ($parent_record['student_hidden'] == '1') {
            return true;
        } else {
            if ($parent_record['parent_folder_id']) {
                return self::parentFolderHidden($parent_record['parent_folder_id']);
            }
        }
    }

    //loops through the parent folders to create the breadcrumbs
    public static function getParentsBreadCrumbs($PARENT_FOLDER) {
        global $db, $COMMUNITY_ID, $BREADCRUMB, $COMMUNITY_URL, $PAGE_URL;
        $queryParents = "   SELECT *
                        FROM `community_shares`
                        WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
                        AND `cshare_id` = ".$PARENT_FOLDER."
                        AND `folder_active` = '1'";
        $parent_folder = $db->GetRow($queryParents);

        if ($parent_folder) {
            self::getParentsBreadCrumbs($parent_folder["parent_folder_id"]);
            $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$parent_folder["cshare_id"], "title" => limit_chars($parent_folder["folder_title"], 32));
        }
    }

    /**
     *
     *
     *
     * Selects and formats the select options for folders used for moving a folder from one folder to another
     *
     * @return string
     */
    public static function selectParentFolderOptions($cshare_id, $parent_folder_id, $page_id, $action = "edit") {
        global $COMMUNITY_ID, $db;

        $folderHTML = 'Root Level||0|0,';

        $activeRootFoldersSelect = "    SELECT `folder_title`, `cshare_id`, `parent_folder_id`
                                        FROM `community_shares`
                                        WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
                                        AND `folder_active` = '1'
                                        AND `parent_folder_id` = '0'
                                        AND `cpage_id` = ".$db->qstr($page_id)."
                                        ORDER BY `folder_order` ASC, `folder_title` ASC";
        $activeRootFolders = $db->GetAll($activeRootFoldersSelect);
        if (is_array($activeRootFolders) && !empty($activeRootFolders)) {
            foreach($activeRootFolders as $activeRootFolder) {
                $loops = 0;
                $loops = get_number_parent_folders($activeRootFolder['cshare_id']);
                 //use string replace on commas to avoid array_combine errors on folders with commas in the name.
                $search_array = Array(",", "|");
                $replace_array = Array("&#44;", "&#124;");
                $active_title_escaped = str_replace($search_array, $replace_array, $activeRootFolder['folder_title']);
                $folderHTML .= $active_title_escaped."|";
                $folderHTML .= $activeRootFolder['parent_folder_id']."|";
                $folderHTML .= $activeRootFolder['cshare_id']."|";
                $folderHTML .= $loops.",";
                $folderHTML .= selectSubFolders($activeRootFolder['cshare_id']);
            }
        }
        //removes the extra comma
        $folderHTML = substr($folderHTML, 0, -1);
        //creates an array from the string
        $activeFolders = explode(",", $folderHTML);

        $folderExport = '';
        if (is_array($activeFolders) && !empty($activeFolders)) {
            foreach ($activeFolders as $activeFolder) {
                $fields = array ( 'folder_title', 'parent_folder_id', 'cshare_id', 'loops' );
                $activeFolder_array = array_combine ( $fields, explode ( "|", $activeFolder ) );
    
                $loops = 0;
                $dashes = '';
    
                switch ($action) {
                    case "edit":
                        $disabled = ($cshare_id == $activeFolder_array['cshare_id'] ? "disabled='disabled'" : "");
                        $selected = ($parent_folder_id == $activeFolder_array['cshare_id'] ? "selected='selected'" : "");
                        break;
                    case "add":
                        $selected = ($parent_folder_id == $activeFolder_array['cshare_id'] ? "selected='selected'" : "");
                        break;
                }
    
                for ($i = 0; $i < $activeFolder_array['loops']; $i++ ) {
                    $dashes .= "-";
                }
                $folderExport .= "<option value='" . $activeFolder_array['cshare_id']."' " . $disabled . $selected . ">" . $dashes . $activeFolder_array['folder_title']."</option>";
            }
        }
        return $folderExport;
    }

    public static function getFilesLinksCumulativeCount($cshare_id = 0) {
        global $ENTRADA_USER;

        //checks the role of the user and sets hidden to true if they're not a facluty, staff, or medtech memeber
        //used to control access to files if they're marked hidden from students
        $group = $ENTRADA_USER->getActiveGroup();
        if ($group == 'faculty' || $group == 'staff'  || $group == 'medtech') {
            $hidden = false;
        } else {
            $hidden = true;
        }

        $output					= array();
        $output["total_links"]	= 0;
        $output["total_files"]	= 0;
        $output["total_html"]	= 0;
        $links = 0;
        $files = 0;
        $html = 0;

        $folder_cshare_ids = communities_subfolders_ids($cshare_id);
        //generate total number of links and files
        if (is_array($folder_cshare_ids) && !empty($folder_cshare_ids)) {
            foreach ($folder_cshare_ids as $folder_cshare_id) {
                $links  = communities_shares_link_latest($folder_cshare_id);
                $html   = communities_shares_html_latest($folder_cshare_id);
                $files  = communities_shares_latest($folder_cshare_id);
                $output["total_links"] = $output["total_links"] + $links['total_links'];
                $output["total_files"] = $output["total_files"] + $files['total_files'];
                $output["total_html"] = $output["total_html"] + $html['total_html'];
            }
        }

        $output['total_docs'] = $output["total_links"] + $output["total_files"] + $output["total_html"];
        return $output;
    }


    /**
     * @param int $community_id
     * @param $table_name
     * @param $id_field
     * @param $type_field
     * @param $active_filed
     * @return array
     */
    public static function fetchAllByCommunityIDCourseGroupMember($community_id = 0, $table_name, $id_field, $type_field, $active_filed) {
        global $db;

        $sql = "SELECT item.*
                FROM `" .$table_name . "` AS `item`
                JOIN `community_acl` AS `ca`
                ON `item`.`$id_field` = `ca`.`resource_value`
                WHERE `item`.`community_id` = " . $community_id . "
                AND `ca`.`assertion` = 'CourseGroupMember'
                AND `ca`.`resource_type` = '" . $type_field . "'
                AND `item`.`$active_filed`";
        $results = $db->GetAll($sql);
        $output = array();

        if (isset($results)) {
            if (is_array($results) && !empty($results)) {
                foreach ($results as $result) {
                    $self = new self();
                    $output[] = $self->fromArray($result);
                }
            }
        }

        return $output;
    }
}
