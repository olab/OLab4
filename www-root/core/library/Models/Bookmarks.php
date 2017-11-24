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
 * A model for handling community courses
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <DNoji@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Models_Bookmarks extends Models_Base {
    protected $id,
              $uri,
              $bookmark_title,
              $updated_date,
              $order,
              $proxy_id,
              $db;
    
    protected static $table_name            = "bookmarks";
    protected static $primary_key           = "id";
    protected static $default_sort_column   = "order";

    public function __construct($arr = NULL) {
        global $db;
        $this->db = $db;
        
        parent::__construct($arr);
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getUri() {
        return $this->uri;
    }
    
    public function getBookmarkTitle() {
        return $this->bookmark_title;
    }
    
    public function getUpdatedDate() {
        return $this->updated_date;
    }
    
    public function getProxyId() {
        return $this->proxy_id;
    }
    public function getOrder() {
        return $this->order;
    }
    
    public function setId($id) {
        $this->id = $id;

        return $this;
    }
    
    public function setUri($uri) {
        $this->uri = $uri;

        return $this;
    }
    
    public function setBookmarkTitle($bookmark_title) {
        $this->bookmark_title = $bookmark_title;

        return $this;
    }
    
    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;

        return $this;
    }
    
    public function setProxyId($proxy_id) {
        $this->proxy_id = $proxy_id;

        return $this;
    }
    
    public function setOrder($order) {
        $this->order = $order;

        return $this;
    }

    /* @return ArrayObject|Models_Bookmarks[] */
    public function fetchAllByProxyId($id, $search_value = "") {
        $self = new Models_Bookmarks();
        $query = "SELECT * 
                    FROM `bookmarks` 
                    WHERE `proxy_id` = ? " . ($search_value != "" ? " AND (`bookmark_title` LIKE '%" . $search_value . "%' OR `uri` LIKE '%" . $search_value . "%')" : "") . "
                    ORDER BY `order` ASC, `updated_date` DESC";
        $results = $this->db->GetAll($query, array($id));

        $output = array();
        if ($results) {
            foreach ($results as $result) {
                $class = get_called_class();
                $output[] = new $class($result);
            }
        }
        
        return $output;
    }

    /* @return bool|Models_Bookmarks */
    public function fetchBookmarkByUri ($proxy_id, $uri) {
        $self = new Models_Bookmarks();
        
        return $self->fetchRow(array(
                array("key" => "proxy_id", "value" => $proxy_id, "method" => "=", "mode" => "AND"),
                array("key" => "uri", "value" => $uri, "method" => "=", "mode" => "AND")
            )
        );
    }

    /* @return bool|Models_Bookmarks */
    public function fetchBookmarkById ($proxy_id, $id) {
        $self = new Models_Bookmarks();
        
        return $self->fetchRow(array(
                array("key" => "id", "value" => $id, "method" => "=", "mode" => "AND"),
                array("key" => "proxy_id", "value" => $proxy_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    /*
     * Bookmarks sidebar
     *
     */

    public static function showSidebar($returnHtml = NULL) {
        global $Bookmarks, $translate, $ENTRADA_USER;

        $Bookmarks = new Models_Bookmarks();
        $size = 0;

        $currentBookmark = $Bookmarks->fetchBookmarkByUri($ENTRADA_USER->getID(), Entrada_Utilities::getCurrentUrl());
        $Bookmarks = $Bookmarks->fetchAllByProxyId($ENTRADA_USER->getID());
        $visible_bookmarks = 5; //Number of bookmarks to initially show

        $sidebar_html = "<div id=\"bookmarks-widget\">";

        $sidebar_html .= "<div class=\"row-fluid\" id=\"bookmark-controls\">";
        if ($currentBookmark) {
            $sidebar_html .= "<button class=\"btn btn-small btn-primary bookmark-btn active pull-right\" id=\"bookmarked-page\" data-bookmark=\"". $currentBookmark->getId() . "\" data-bookmark-url=\"". Entrada_Utilities::getCurrentUrl() ."\" role=\"button\"><i class=\"icon-bookmark icon-white\"></i> <span id=\"bookmark-text\">Bookmarked</span></button> ";
        } else {
            if (!$Bookmarks) {
                $sidebar_html .= "<div class=\"muted space-below\">
 <small>You can bookmark this page</small> <i class=\"fa fa-arrow-down\" aria-hidden=\"true\"></i></div>";
            }
            $sidebar_html .= "<button class=\"btn btn-small btn-primary bookmark-btn pull-right\" id=\"bookmark-page\" data-bookmark-url=\"". htmlspecialchars(Entrada_Utilities::getCurrentUrl()) ."\" role=\"button\"><i class=\"fa fa-bookmark bookmark-icon\"></i> <span id=\"bookmark-text\"> " . $translate->_("Add Bookmark") . "</span></button>";
        }

        if ($Bookmarks) {
            $sidebar_html .= "<button class=\"btn btn-small\" id=\"edit-bookmarks\"><i class=\"fa fa-cog\"></i></button>";
            $sidebar_html .= "<input type=\"text\" class=\"space-above span12 search-icon\" id=\"bookmark_search_value\" name=\"bookmark_search_value\" placeholder=\"Search bookmarks\">";
        }
        $sidebar_html .= "</div>";//end bookmark-controls
        $sidebar_html .= "<div class=\"row-fluid\" id=\"bookmark-list\">";
        $sidebar_html .= "<div class=\"span12\" id=\"bookmark-list-container\">";

        if ($Bookmarks) {

            $size = count($Bookmarks);
            for ($i = 0; $i < $size; ++$i) {
                $Bookmark = $Bookmarks[$i];

                if ($i == $visible_bookmarks) {
                    $sidebar_html .="<div id=\"all-bookmarks\" style=\"display: none;\">";
                }
                if ($i+1 > $visible_bookmarks) {
                    $sidebar_html .="<div class=\"row-fluid bookmark-item hidden-bookmark\" id=\"bookmark_". $Bookmark->getId() ."\" data-bookmark-link-id=\"". $Bookmark->getId() . "\">";
                } else {
                    $sidebar_html .="<div class=\"row-fluid bookmark-item\" id=\"bookmark_". $Bookmark->getId() ."\" data-bookmark-link-id=\"". $Bookmark->getId() . "\">";
                }
                $sidebar_html .="<div class=\"span1 delete-column\" style=\"display: none;\"><i class=\"fa fa-trash\" id=\"delete-bookmark\"></i></div>";
                $sidebar_html .="<div class=\"span12 bookmark-column\">";
                $sidebar_html .="<a href=\"". $Bookmark->getUri() ."\" class=\"bookmark-link\"><span class=\"bookmark-text\" data-toggle=\"popover\">";
                $sidebar_html .= ($currentBookmark && $currentBookmark->getUri() == $Bookmark->getUri()) ? "<strong>" . $Bookmark->getBookmarkTitle() . "</strong>" : $Bookmark->getBookmarkTitle();
                $sidebar_html .="</span></a>";
                $sidebar_html .="</div>";
                $sidebar_html .="<div class=\"span1 move-column muted\" style=\"display: none;\"><i class=\"fa fa-arrows\" id=\"move-bookmark\"></i></div>";
                $sidebar_html .="</div>";

                if ($i >= $visible_bookmarks && $i+1 == $size) {
                    $sidebar_html .= "</div>";
                }
            }

        }

        $sidebar_html .= "</div>"; // END bookmark-list-container
        $sidebar_html .= "</div>"; // END bookmark-list
        if ($size > $visible_bookmarks) {
            $sidebar_html .= "<a class=\"btn btn-link btn-block show-more\" id=\"btn-show-more-collapsible\">Show more</a>";
        }

        $sidebar_html .= "</div>";

        if ($returnHtml == true) {
            return $sidebar_html;
        } else {
            new_sidebar_item($translate->_("My Bookmarks"), $sidebar_html, "bookmarks", "open", 1);
        }
    }

    function add_bookmarks_js () {
        return file_get_contents(dirname(__FILE__)."/../../javascript/bookmark.js.php");
    }
    
    /**
     * Updates the sort order in the database.
     * 
     * @return bool - true on success, false on failure.
     */
    public function updateSort() {
         
       if (isset($this->id)) {
            $update_sort = array("id"=>$this->id, "order"=>$this->order);
            
            if ($this->db->AutoExecute(static::$table_name, $update_sort, "UPDATE", "`id` = ".$this->db->qstr($this->id))) {
                return true;
            } else {
                application_log("error", "Failed to update Bookmark ID[" . $this->id . "].  DB Said: " . $this->db->ErrorMsg());
                return false;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Deletes model in the database.
     * 
     * @return bool - true on success, false on failure.
     * 
     */
    public function delete() {
         
       if (isset($this->id)) {
           $query = "DELETE FROM `" . static::$table_name . "` WHERE `id` = ?";

            if ($this->db->Execute($query, array($this->id))) {
                return true;
            } else {
                application_log("error", "Failed to update Bookmark id[" . $this->id . "].  DB Said: " . $this->db->ErrorMsg());
                return false;
            }
        } else {
            return false;
        }
    }
}