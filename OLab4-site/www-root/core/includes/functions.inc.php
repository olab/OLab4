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
 * This file contains all of the functions used within Entrada.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

function get_prev_community_page($COMMUNITY_ID, $PAGE_ID, $PARENT_ID, $PAGE_ORDER) {
	global $db;

	$prev_page_order = $PAGE_ORDER - 1;
	$query = "	SELECT *
				FROM `community_pages` cp
				WHERE cp.`community_id` = " . $db->qstr($COMMUNITY_ID) . "
				AND cp.`page_order` <= " . $db->qstr($prev_page_order) . "
				AND cp.`parent_id` = " . $db->qstr($PARENT_ID) . "
				AND cp.`page_active` = '1'
				ORDER BY cp.`page_order` DESC";
	$result1 = $db->GetRow($query);
	if ($result1) {
		$result2 = get_prev_sibling_ancestors($COMMUNITY_ID, $result1["cpage_id"]);
		if(!$result2) {
			return $result1;
		}
		return $result2;
	} else {
		$query = "	SELECT *
					FROM `community_pages` cp
					WHERE cp.`community_id` = " . $db->qstr($COMMUNITY_ID) . "
					AND cp.`cpage_id` = " . $db->qstr($PARENT_ID) . "
					AND cp.`page_active` = '1'";
		$result1 = $db->GetRow($query);
		return $result1;
	}
}

function get_prev_sibling_ancestors($COMMUNITY_ID, $PARENT_ID, $PAGE_ORDER = 0) {
	global $db;

	//get prev siblings ancestors
	$query = "	SELECT *
				FROM `community_pages` cp
				WHERE cp.`community_id` = " . $db->qstr($COMMUNITY_ID) . "
				AND cp.`parent_id` = " . $db->qstr($PARENT_ID) . "
				AND cp.`page_active` = '1'
				ORDER BY `page_order` DESC";
	$result2 = $db->GetRow($query);

	if (!$result2) {
		$query = "	SELECT *
				FROM `community_pages` cp
				WHERE cp.`community_id` = " . $db->qstr($COMMUNITY_ID) . "
				AND cp.`cpage_id` = " . $db->qstr($PARENT_ID) . "
				AND cp.`page_active` = '1'
				ORDER BY `page_order` DESC";
		$result2 = $db->GetRow($query);
		return $result2;
	} else {
		return get_prev_sibling_ancestors($COMMUNITY_ID, $result2["cpage_id"]);
	}
}

function get_next_community_page($COMMUNITY_ID, $PAGE_ID, $PARENT_ID, $PAGE_ORDER) {
	global $db;
	if ($PAGE_ID) {
		$query = "	SELECT *
					FROM `community_pages` cp
					WHERE cp.`community_id` = " . $db->qstr($COMMUNITY_ID) . "
					AND cp.`parent_id` = " . $db->qstr($PAGE_ID) . "
					AND cp.`page_active` = '1'
					ORDER BY `page_order` ASC";
		$result = $db->GetRow($query);
	}
	//if no children
	if (!$PAGE_ID || !$result) {
		$next_page_order = $PAGE_ORDER + 1;
		$query = "	SELECT *
					FROM `community_pages` cp
					WHERE cp.`community_id` = " . $db->qstr($COMMUNITY_ID) . "
					AND cp.`page_order` = " . $db->qstr($next_page_order) . "
					AND cp.`parent_id` = " . $db->qstr($PARENT_ID) . "
					AND cp.`page_active` = '1'";
		$result = $db->GetRow($query);
		//if no sibling then find my next ancestor sibling if it exists.
		if (!$result) {
			$result = get_next_ancestor_sibling($COMMUNITY_ID, $PARENT_ID, $PAGE_ORDER);
			return $result;
		}

		return $result;

	} else {
		return $result;
	}
}

function get_next_ancestor_sibling($COMMUNITY_ID, $PARENT_ID, $PAGE_ORDER) {
	global $db;

	if ($PARENT_ID != 0) {
		$query = "	SELECT *
					FROM `community_pages` cp
					WHERE cp.`community_id` = " . $db->qstr($COMMUNITY_ID) . "
					AND cp.`cpage_id` = " . $db->qstr($PARENT_ID) . "
					AND cp.`page_active` = '1'";
		$result = $db->GetRow($query);
		$NEXT_PARENT_ID = $result["parent_id"];
		$NEXT_PAGE_ORDER = $result["page_order"] + 1;
	} else {
		$NEXT_PARENT_ID = 0;
		$NEXT_PAGE_ORDER = $PAGE_ORDER + 1;
		$query = "	SELECT max(`page_order`)
					FROM `community_pages` cp
					WHERE cp.`community_id` = " . $db->qstr($COMMUNITY_ID) . "
					AND cp.`parent_id` = " . $db->qstr($NEXT_PARENT_ID) . "
					AND cp.`page_active` = '1'";
		$max_page_order = $db->GetOne($query);
		if ($NEXT_PAGE_ORDER > $max_page_order) {
			return 0;
		}
	}

	$query = "	SELECT *
				FROM `community_pages` cp
				WHERE cp.`community_id` = " . $db->qstr($COMMUNITY_ID) . "
				AND cp.`parent_id` = " . $db->qstr($NEXT_PARENT_ID) . "
				AND cp.`page_order` >= " . $db->qstr($NEXT_PAGE_ORDER) . "
				AND cp.`page_active` = '1'
				ORDER BY cp.`page_order` ASC";
	$result2 = $db->GetRow($query);
	if (!$result2) {
		return get_next_ancestor_sibling($COMMUNITY_ID, $NEXT_PARENT_ID, $PAGE_ORDER);
	} else {
		return $result2;
	}
}

/**
 * This is really just a controller function that calls a bunch of other functions. This function is called by ob_start().
 * @uses check_head()
 * @uses check_meta()
 * @uses check_body()
 * @uses check_sidebar()
 * @uses check_breadcrumb()
 * @param string $buffer
 * @return string buffer
 */
function on_checkout($buffer) {
	$buffer = check_head($buffer);
	$buffer = check_jquery($buffer);
    $buffer = check_javascript_translations($buffer);
	$buffer = check_meta($buffer);
	$buffer = check_body($buffer);
	$buffer = check_sidebar($buffer);
	$buffer = check_breadcrumb($buffer);
	$buffer = check_script($buffer);
	return $buffer;
}

/**
 * surrounds the supplied string with a javascript try/catch
 * @param string $element
 */
function add_try_catch_js($element) {
	return "try {".$element.";}catch(e){ clog(e); }";
}

/**
 * processes the supplied string to add script elements including onload and onunload blocks. called by on_checkout
 * @param string $buffer
 */
function check_script($buffer) {
	global $SCRIPT, $ONLOAD, $ONUNLOAD;

	$elements = array();
	if ((isset($ONLOAD)) && (count($ONLOAD))) {
		$ONLOAD = array_map('add_try_catch_js',$ONLOAD);
        $elements["load"] = "jQuery(document).ready(function() {\n".implode(";\n\t", $ONLOAD)."\n});\n";
	}

	if ((isset($ONUNLOAD)) && (count($ONUNLOAD))) {
        $elements["unload"] = "jQuery(window).unload(function() {\n".implode(";\n\t", $ONUNLOAD).";\n});\n";
	}

	if ($elements) {
		$SCRIPT[] = "\n<script defer=\"defer\" type=\"text/javascript\">\n".implode("\n",$elements)."</script>";
	}

	$output = "";
	if (isset($SCRIPT) && (count($SCRIPT))) {
		$output .= implode("\n", $SCRIPT);
	}
	return str_replace("</body>", $output."\n</body>", $buffer);
}

/**
 * Function is called by on_checkout. Adds any head elements that are required, specified in the $HEAD array.
 *
 * @param string $buffer
 * @return string buffer
 */
function check_head($buffer) {
	global $HEAD;

	$output = "";

	if ((isset($HEAD)) && (count($HEAD))) {
		$output = implode("\n", $HEAD);
	}

	return str_replace("%HEAD%", $output, $buffer);
}

/**
 * Function is called by on_checkout. Adds any jquery elements that are required, specified in the $JQUERY array,
 * which loads above Prototype to prevent conflicts. This is a quick fix until we can rewrite all of the Javascript
 * to use jQuery.
 *
 * @param string $buffer
 * @return string buffer
 */
function check_jquery($buffer) {
	global $JQUERY;

	$output = "";

	if ((isset($JQUERY)) && (count($JQUERY))) {
		$output = implode("\n", $JQUERY);
	}

	return str_replace("%JQUERY%", $output, $buffer);
}

/**
 * Function is called by on_checkout. Adds any jquery elements that are required, specified in the $JQUERY array,
 * which loads above Prototype to prevent conflicts. This is a quick fix until we can rewrite all of the Javascript
 * to use jQuery.
 *
 * @param string $buffer
 * @return string buffer
 */
function check_javascript_translations($buffer) {
    global $JAVASCRIPT_TRANSLATIONS;

    $output = "";

    if ((isset($JAVASCRIPT_TRANSLATIONS)) && (count($JAVASCRIPT_TRANSLATIONS))) {
        $output = implode("\n", $JAVASCRIPT_TRANSLATIONS);
    }

    return str_replace("%JAVASCRIPT_TRANSLATIONS%", $output, $buffer);
}

/**
 * Function is called by on_checkout function. It modifies the page meta information in the $PAGE_META array.
 *
 * @param string $buffer
 * @return string buffer
 */
function check_meta($buffer) {
	global $PAGE_META, $DEFAULT_META, $LASTUPDATED;

	$title			= ((isset($PAGE_META["title"])) ? $PAGE_META["title"] : $DEFAULT_META["title"]);
	$description	= ((isset($PAGE_META["description"])) ? $PAGE_META["description"] : $DEFAULT_META["description"]);
	$keywords		= ((isset($PAGE_META["keywords"])) ? $PAGE_META["keywords"] : $DEFAULT_META["keywords"]);

	if ((isset($LASTUPDATED)) && ((int) $LASTUPDATED)) {
		$LASTUPDATED = "Last updated: ".date("r", $LASTUPDATED).".<br />";
	} else {
		$LASTUPDATED = "";
	}

	return str_replace(array("%TITLE%", "%DESCRIPTION%", "%KEYWORDS%", "%LASTUPDATED%"), array($title, $description, $keywords, $LASTUPDATED), $buffer);
}

/**
 * Function is called by on_checkout function. Adds any events specified in the $ONLOAD or $ONUNLOAD array.
 *
 * @param string $buffer
 * @return string buffer
 */
function check_body($buffer) {
	return $buffer;
}

/**
 * Function is called by on_checkout. Adds any sidebar events specified in $SIDEBAR array.
 *
 * @param string $buffer
 * @return string buffer
 */
function check_sidebar($buffer) {
	global $SIDEBAR;

	$output = "";

	if(@count($SIDEBAR)) {
		@ksort($SIDEBAR);

		$output .= "<div class=\"inner-sidebar no-printing\">\n";
		$output .= implode("\n\n", $SIDEBAR);
		$output .= "</div>\n";
	}
	return str_replace("%SIDEBAR%", $output, $buffer);
}

/**
 * @deprecated The system navigator has been removed from Entrada.
 *
 * @return bool
 */
function load_system_navigator() {
	return false;
}

/**
 *  Library function to help navigator_tabs() function on creating dropdown itens
 *
 * @param array $module_item main menu tab that has children items
 * @param array $css_classes defined css classes to the menu items
 * @return string
 */
function navigator_tabs_dropdown_item($module_item, $css_classes){
    global $ENTRADA_USER, $ENTRADA_ACL, $MODULE;
    $tab_children = "<ul class=\"dropdown-menu\">";

    foreach ($module_item["children"] as $child_shortname => $child_item) {
        $child_active = false;

        if (isset($child_item["resource"]) && isset($child_item["permission"])) {
            if (!$ENTRADA_ACL->amIAllowed($child_item["resource"], $child_item["permission"])) {
                continue;
            } else if (isset($child_item["limit-to-role"]) && $child_item["limit-to-role"]) {
                // Check if the logged user role can access this meny item
                // First we check if the limit to role in the child item is an array,
                // if not, we convert it
                if (!is_array($child_item["limit-to-role"])) {
                    $child_item["limit-to-role"] = array($child_item["limit-to-role"]);
                }
                // Now we verify if the user role is allowed to access the item
                if (!in_array($ENTRADA_USER->getRole(), $child_item["limit-to-role"])) {
                    // We skip the item in the loop in case the user cann't access it
                    continue;
                }
            } else if (isset($child_item["limit-to-groups"]) && $child_item["limit-to-groups"]) {
                // Check if the logged user group can access this meny item
                // First we check if the limit to groups in the child item is an array,
                // if not, we convert it
                if (!is_array($child_item["limit-to-groups"])) {
                    $child_item["limit-to-groups"] = array($child_item["limit-to-groups"]);
                }
                // Now we verify if the user group is allowed to access the item
                if (!in_array($ENTRADA_USER->getActiveGroup(), $child_item["limit-to-groups"])) {
                    // We skip the item in the loop in case the user cann't access it
                    continue;
                }
            }
        }

        if ($MODULE == $child_shortname) {
            $child_active = true;

            /**
             * This will make the childs' parent active as well.
             */
            if (!in_array("current", $css_classes)) {
                $css_classes[] = "current";
            }
        }

        $tab_children .= "<li" . ($child_active ? " class=\"current\"" : "") . ">";
        $tab_children .= "	<a href=\"" . ((isset($child_item["url"])) ? $child_item["url"] : ENTRADA_RELATIVE . "/" . $child_shortname) . "\"" . ((isset($child_item["target"])) ? " target=\"" . $child_item["target"] . "\"" : "") . "><span>" . $child_item["title"] . "</span></a>";
        $tab_children .= "</li>";
    }
    $tab_children .= "</ul>";
    return $tab_children;
}

function navigator_tabs() {

    global $ENTRADA_USER, $ENTRADA_ACL, $MODULE, $MODULES, $translate;

    if (!defined("MAX_NAV_TABS")) {
        $max_public = 9;
    } else {
        $max_public = MAX_NAV_TABS;
    }

    $tabs_admin = "";
    $tabs_public = "";

    $admin_tabs = array();
    $public_tabs = array();
    $more_tabs = array();

    $counter = 0;
    $more_bold = "";

    $output_html = "";

    $navigation = $translate->_("navigation_tabs");

    if (is_array($navigation) && !empty($navigation)) {
        /**
         * Temporary fix until we figure out something better
         * for dynamic modules.
         */
        $navigation["admin"] = $MODULES;

        $navigator_admin = $navigation["admin"];
        $navigator_public = $navigation["public"];

        /**
         * Administrative tab entries.
         */
        $assertion_check = false;
        if ($ENTRADA_USER->getActiveGroup() == "student") {
            $assertion_check = true;
        }

        foreach ($navigation["admin"] as $shortname => $module_info) {
            $enabled = true;

            if (isset($module_info["enabled"])) {
				$settings = new Entrada_Settings();
                $enabled = $settings->read($module_info["enabled"]);
            }

            if ($enabled && $ENTRADA_ACL->amIAllowed($module_info["resource"], $module_info["permission"], $assertion_check)) {
                $admin_tabs[] = "<li class=\"%" . $shortname . "%\"><a href=\"" . ENTRADA_URL . "/admin/" . $shortname . "\"><span>" . html_encode(((isset($module_info["title"])) ? $module_info["title"] : ucwords(strtolower($shortname)))) . "</span></a></li>\n";
            }
        }

        if (!empty($admin_tabs)) {
            $max_public--;

            if (defined("IN_ADMIN") && (IN_ADMIN == true)) {
                $tab_bold = " current";
                $admin_text = str_replace("%" . $MODULE . "%", "current", implode("\n", $admin_tabs));
            } else {
                $tab_bold = "";
                $admin_text = implode("\n", $admin_tabs);
            }

            $tabs_admin .= "<li class=\"dropdown" . $tab_bold . "\" id=\"admin_tab\">";
            $tabs_admin .= "	<a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" id=\"admin_tab_link\"><span>Admin</span> <b class=\"caret\"></b></a>";
            $tabs_admin .= "	<ul class=\"dropdown-menu\" id=\"admin_drop_options\">";
            $tabs_admin .= $admin_text;
            $tabs_admin .= "	</ul>";
            $tabs_admin .= "</li>\n";
        }

        /**
         * Public tab entries.
         */
        foreach ($navigation["public"] as $shortname => $module_item) {
            $active = false;
            $css_classes = array();

            $tab_children = "";
            $has_children = false;

            $include_item = false;

            $enabled = true;
			      if (isset($module_item["enabled"])) {
				        $settings = new Entrada_Settings();
                $enabled = $settings->read($module_item["enabled"]);
            }
            /**
             * If this menu item has a resource attached check to see if the user
             * has permission before adding it to the stack. If there is no resource
             * associated with it, it is safe to add.
             */
            if (isset($module_item["resource"]) && isset($module_item["permission"])) {
                if ($enabled && $ENTRADA_ACL->amIAllowed($module_item["resource"], $module_item["permission"])) {

                    if (isset($module_item["limit-to-role"]) && $module_item["limit-to-role"]) {
                        // Check if the logged user role can access this menu item
                        // First we check if the limit to role in the child item is an array,
                        // if not, we convert it
                        if (!is_array($module_item["limit-to-role"])) {
                            $module_item["limit-to-role"] = array($module_item["limit-to-role"]);
                        }
                        // Now we verify if the user role is allowed to access the item
                        if (in_array($ENTRADA_USER->getRole(), $module_item["limit-to-role"])) {
                            $counter++;
                            $include_item = true;
                        }
                    } else if (isset($module_item["limit-to-groups"]) && $module_item["limit-to-groups"]) {
                        if (!is_array($module_item["limit-to-groups"])) {
                            $module_item["limit-to-groups"] = array($module_item["limit-to-groups"]);
                        }

                        if (in_array($ENTRADA_USER->getActiveGroup(), $module_item["limit-to-groups"])) {
                            $counter++;
                            $include_item = true;
                        }
                    } elseif (isset($module_item["limit-to-roles"]) && $module_item["limit-to-roles"]) {
                        if (!is_array($module_item["limit-to-roles"])) {
                            $module_item["limit-to-roles"] = array($module_item["limit-to-roles"]);
                        }

                        if (in_array($ENTRADA_USER->getActiveRole(), $module_item["limit-to-roles"])) {
                            $counter++;

                            $include_item = true;
                        }
                    } else {
                        $counter++;

                        $include_item = true;
                    }
                }
            } else if ($enabled) {
                $counter++;
                $include_item = true;
            }

            if ($include_item) {
                if (isset($module_item["children"]) && is_array($module_item["children"]) && !empty($module_item["children"])) {
                    $has_children = true;
                    $css_classes[] = "dropdown";
                }

                if ($counter == 1) {
                    $css_classes[] = "first";
                }

                if ($has_children) {
                    $tab_children .= navigator_tabs_dropdown_item($module_item, $css_classes);
                }

                if ($MODULE == $shortname) {
                    $css_classes[] = "current";
                    $active = true;
                }

                $tab = "<li" . (!empty($css_classes) ? " class=\"" . implode(" ", $css_classes) . "\"" : "") . ">";
                if ($tab_children) {
                    $tab .= "<a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">" . $module_item["title"] . " <b class=\"caret\"></b></a>";
                    $tab .= $tab_children;

                } else {
                    $tab .= "<a href=\"" . ENTRADA_RELATIVE . "/" . $shortname . "\">" . $module_item["title"] . "</a>";
                }
                $tab .= "</li>\n";

                // Push excess public tabs into more
                // In order to grab another tab to make space for the more tab within the max limit
                // Lest remove one less from the max public
                if ($counter > $max_public - 1) {
                    $more_tabs[$shortname] = $module_item;
                    if ($active == true) {
                        $more_bold = " current";
                    }
                } else {
                    $public_tabs[] = $tab;
                }
            }
        }

        /**
         * Add "More" tabs.
         */
        if (!empty($more_tabs)) {
            $sub_class = "dropdown-menu";
            //If the more menu has only one item I will not create a submenu with the item and just add it to the main navbar
            $tabs_more = "";
            if (count($more_tabs) > 1) {
                $sub_class = "dropdown-submenu";
                $tabs_more .= "<li class=\"dropdown" . $more_bold . "\" id=\"more_tab\">";
                $tabs_more .= "	<a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">More <b class=\"caret\"></b></a>";
                $tabs_more .= "	<ul class=\"dropdown-menu\" id=\"more_drop_options\">";
            }
            foreach ($more_tabs as $shortname => $extra_item) {
                //For each item of the more tabs item I will verify if it has children item
                $css_classes = array();
                if ($MODULE == $shortname) {
                    $css_classes[] = "current";
                }
                if (isset($extra_item["children"]) && is_array($extra_item["children"]) && !empty($extra_item["children"])) {
                    //If the item has children itens lets create a submenu
                    $css_classes[] = "dropdown";
                    $tabs_more .= "<li class='$sub_class'>";
                    $tabs_more .= "<a href=''#'>" . $extra_item["title"] . "</a>";
                    $tabs_more .= navigator_tabs_dropdown_item($extra_item, $css_classes);
                    $tabs_more .= "</li>";
                } else {
                    $tabs_more .= "<li " . (!empty($css_classes) ? " class=\"" . implode(" ", $css_classes) . "\"" : "") . ">";
                    $tabs_more .= "<a href=\"" . ENTRADA_RELATIVE . "/" . $shortname . "\">" . $extra_item["title"] . "</a>";
                    $tabs_more .= "</li>\n";
                }
            }
            if (count($more_tabs) > 1) {
                $tabs_more .= "	</ul>";
                $tabs_more .= "</li>\n";
            }
        }

        $tabs_public = implode("\n", $public_tabs);

        $output_html .= $tabs_public;

        if (!empty($tabs_more)) {
            $output_html .= $tabs_more;
        }

        if (!empty($navigator_admin)) {
            $output_html .= $tabs_admin;
        }
    }

	/**
	 * Add Logout tab.
	 */
	/*$output_html .= "<li class=\"last staysput\"><a href=\"".ENTRADA_RELATIVE."/?action=logout\"><span>Logout</span></a></li>\n";*/


	/**
	 * Replace the active module with the current css keyword.
	 */
	$output_html = str_replace("%".$MODULE."%", "current", $output_html);

	/**
	 * Remove temporary placeholders.
	 */
	/*$output_html = "<div id=\"screenTabs\"><div id=\"tabs\"><ul>".preg_replace("/\%(.*)\%/", "", $output_html)."</ul></div></div>";*/
	$output_html = "<ul class=\"nav\">".preg_replace("/\%(.*)\%/", "", $output_html)."</ul>";
	return $output_html;
}

/**
 * This function adds sw
 *
 * @global array $JQUERY
 * @param string $next
 * @param string $back
 * @param string $type
 * @return true
 */
function navigator_swipe($direction = array(), $type = "click") {
	global $JQUERY;

	if ($type != "js") {
		$type = "click";
	}

	/**
	 * Load jSwipe to handle next and back on mobile devices.
	 */
	$JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.touchSwipe.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

	$swipe = array();

	/**
	 * Shortcuts.
	 */
	if (isset($direction["next"]) && ($tmp_input = clean_input($direction["next"]))) {
		$direction["left"] = $tmp_input;
	}
	if (isset($direction["back"]) && ($tmp_input = clean_input($direction["back"]))) {
		$direction["right"] = $tmp_input;
	}

	if (isset($direction["up"]) && ($tmp_input = clean_input($direction["up"]))) {
		$swipe[] = "swipeUp: function() { ".(($type == "click") ? "window.location = '".$direction["up"]."'" : $direction["up"])." }";
	}
	if (isset($direction["right"]) && ($tmp_input = clean_input($direction["right"]))) {
		$swipe[] = "swipeRight: function() { ".(($type == "click") ? "window.location = '".$direction["right"]."'" : $direction["right"])." }";
	}
	if (isset($direction["down"]) && ($tmp_input = clean_input($direction["down"]))) {
		$swipe[] = "swipeDown: function() { ".(($type == "click") ? "window.location = '".$direction["down"]."'" : $direction["down"])." }";
	}
	if (isset($direction["left"]) && ($tmp_input = clean_input($direction["left"]))) {
		$swipe[] = "swipeLeft: function() { ".(($type == "click") ? "window.location = '".$direction["left"]."'" : $direction["left"])." }";
	}

	if (!empty($swipe)) {
		echo "<script type=\"text/javascript\">jQuery(document).ready(function() { jQuery('body').swipe({".implode(", ", $swipe)."}); });</script>";
	}

	return true;
}

/**
 * Function is called by on_checkout. Adds any breadcrumb menu items specified in $BREADCRUMB array.
 *
 * @param string $buffer
 * @return string buffer
 */
function check_breadcrumb($buffer) {
	global $BREADCRUMB;

	$i = 1;
	$output	= "";

	if (isset($BREADCRUMB) && is_array($BREADCRUMB) && $BREADCRUMB) {
        $total = count($BREADCRUMB);

		ksort($BREADCRUMB);

		$output .= "<ul class=\"breadcrumb\">\n";

        foreach ($BREADCRUMB as $entry) {
			$output .= "<li".(($i == $total) ? " class=\"active\"" : "")."><span class=\"divider\">/</span> ".((($i < $total) && ($entry["url"] != "")) ? "<a href=\"".$entry["url"]."\">" : "") . html_encode($entry["title"]) . ((($i < $total) && ($entry["url"] != "")) ? "</a>" : "") . "</li>\n";
			$i++;
		}

        $output .= "</ul>\n";
	}
	return str_replace("%BREADCRUMB%", $output, $buffer);
}


/**
 * Constants for new_sidebar_item
 * SIDEBAR_APPEND - places the new item at the end of the *current* list of sidebar items
 * SIDEBAR_PREPEND - places the new item at the beginning of the *current* list of items
 */
if(!defined("SIDEBAR_APPEND")) {
	define("SIDEBAR_APPEND", 0);
}

if(!defined("SIDEBAR_PREPEND")) {
	define("SIDEBAR_PREPEND", 1);
}

/**
 * Function that generates standard sidebar items. It adds them to the $SIDEBAR array which
 * will is processed by the check_sidebar() function through on_checkout() as a callback function.
 *
 * @example new_sidebar_item("Kingston Weather", "This is the content", "weather-widget", "open", SIDEBAR_APPEND);
 * @param string $title
 * @param string $html
 * @param string $id
 * @param string $state
 * @param int $position
 * @return true
 */
function new_sidebar_item($title = "", $html = "", $id = "", $state = "open", $position = SIDEBAR_APPEND) {
	global $SIDEBAR, $NOTICE, $NOTICESTR;

	$state = (($state == "open") ? $state : "close");
	$id = (($id == "") ? "sidebar-".$weight : $id);

	$output  = "<div class=\"panel\" id=\"".html_encode($id)."\">\n";
	$output .= "    <div class=\"panel-head\">\n";
	$output .= "        <h3>".html_encode($title)."</h3>\n";
	$output .= "    </div>\n";
	$output .= "    <div class=\"clearfix panel-body\">".$html."</div>\n";
	$output .= "</div>\n";

	switch ($position) {
		case SIDEBAR_PREPEND:
			array_unshift($SIDEBAR, $output);
		break;
		case SIDEBAR_APPEND:
		default:
			array_push($SIDEBAR, $output);
        break;
	}

	return true;
}

/**
 * Adds a non-standard sidebar item with no header. Used for exam and assessment
 * modules.
 * 
 * @param type $html
 * @param type $id
 * @param type $state
 * @param type $position
 * @return boolean
 */
function new_sidebar_item_no_header($html = "", $id = "", $priority = 0) {
    global $SIDEBAR;

    $output  = "<div".($id ? " id=\"".html_encode($id)."\"" : "").">\n";
    $output .= "    <div class=\"clearfix\">".$html."</div>\n";
    $output .= "</div>\n";

    // $SIDEBAR[$priority][] = $output;
    array_push($SIDEBAR, $output);
    return true;
}

function assessment_sidebar_item($html = "", $id = "", $state = "open", $position = SIDEBAR_APPEND) {
    global $SIDEBAR, $NOTICE, $NOTICESTR;

    $state = (($state == "open") ? $state : "close");
    $id = (($id == "") ? "sidebar-".$weight : $id);

    $output  = "<div class=\"assessment-sidebar-panel\" id=\"".html_encode($id)."\">\n";
    $output .= "    <div class=\"clearfix\">".$html."</div>\n";
    $output .= "</div>\n";

    switch ($position) {
        case SIDEBAR_PREPEND:
            array_unshift($SIDEBAR, $output);
            break;
        case SIDEBAR_APPEND:
        default:
            array_push($SIDEBAR, $output);
            break;
    }

    return true;
}

/*
 * Function that builds system feedback sidebar widget from language file.
 *
 * @param $group string or array
 * @return bool
 */
function add_feedback_sidebar($group = "") {
	global $translate, $SCRIPT;

    $feedback_text = $translate->_("global_feedback_widget");

	if ($feedback_text) {
		$SCRIPT[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/feedback.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

		$sidebar_html  = "<div id=\"feedback-widget\">";
		$sidebar_html .= "  <ul class=\"menu feedback\" data-enc=\"".feedback_enc()."\">";

		/*
		 * Global Feedback options
		 */
		foreach ($feedback_text["global"] as $css_class => $feedback) {
			$sidebar_html .= "<li class=\"".$css_class."\">";
			$sidebar_html .= "  <a href=\"".ENTRADA_RELATIVE."/agent-feedback.php\"><strong>".$feedback["link-text"]."</strong></a><br />";
			$sidebar_html .= "  <span class=\"content-small\">".$feedback["link-desc"]."</span>";
			$sidebar_html .= "</li>";
		}

		/*
		 * Group Specific feedback options
		 */
		if (is_array($group)) {
			foreach ($group as $groupname) {
				if (!empty($feedback_text[$groupname])) {
					foreach ($feedback_text[$groupname] as $css_class => $feedback) {
                        $sidebar_html .= "<li class=\"".$css_class."\">";
                        $sidebar_html .= "  <a href=\"".ENTRADA_RELATIVE."/agent-feedback.php\"><strong>".$feedback["link-text"]."</strong></a><br />";
                        $sidebar_html .= "  <span class=\"content-small\">".$feedback["link-desc"]."</span>";
                        $sidebar_html .= "</li>";
					}
				}
			}
		} else if (is_string($group)) {
			if (!empty($feedback_text[$group])) {
				foreach ($feedback_text[$group] as $css_class => $feedback) {
                    $sidebar_html .= "<li class=\"".$css_class."\">";
                    $sidebar_html .= "  <a href=\"".ENTRADA_RELATIVE."/agent-feedback.php\"><strong>".$feedback["link-text"]."</strong></a><br />";
                    $sidebar_html .= "  <span class=\"content-small\">".$feedback["link-desc"]."</span>";
                    $sidebar_html .= "</li>";
				}
			}
		}

		$sidebar_html .= "  </ul>";
		$sidebar_html .= "</div>";

		new_sidebar_item($translate->_("Give Feedback!"), $sidebar_html, "page-feedback", "open");

		return true;
	} else {
		return false;
	}
}

/**
 * Clears all open buffers so you can start with a clean page. This function is
 * primarily used as a method to handle AJAX requests cleanly.
 *
 * @return true
 */
function ob_clear_open_buffers() {
	$level = @ob_get_level();

	for ($i = 0; $i < $level; $i++) {
		@ob_end_clean();
	}

	return true;
}

/**
 * Multi-dimensional array search which performs a similar task to that of
 * array_search; however, it allows for multi-dimensional arrays.
 *
 * @param string $needle
 * @param array $haystack
 * @return bool or array
 */
function dimensional_array_search($needle, $haystack) {
	$value	= false;
	$x		= 0;
	foreach ($haystack as $temp) {
		if(is_array($temp)) {
			$search = array_search($needle, $temp);
			if(strlen($search) > 0 && $search >= 0) {
				$value[0] = $x;
				$value[1] = $search;
			}
		}
		$x++;
	}
	return $value;
}

/**
 * Provides URL's to different internal web-services.
 *
 * @param string $service
 * @param array $options
 * @return string
 */
function webservice_url($service = "", $options = array()) {
	switch($service) {
		case "gender" :
			return ENTRADA_URL."/api/gender.api.php/".$_SESSION["details"]["group"]."/".$options["number"];
		break;
		case "photo" :
			return ENTRADA_URL."/api/photo.api.php/".implode("/", $options);
		break;
		case "clerkship_department" :
			return ENTRADA_URL."/api/clerkship_department.api.php";
		break;
		case "clerkship_prov" :
			return ENTRADA_URL."/api/clerkship_prov.api.php";
		break;
		case "province" :
			return ENTRADA_URL."/api/province.api.php";
		break;
		case "mspr-admin" :
			return ENTRADA_URL."/admin/users/manage/students?section=api-mspr";
		break;
		case "mspr-profile" :
			return ENTRADA_URL."/profile?section=api-mspr";
		break;
		case "awards" :
			return ENTRADA_URL."/admin/awards?section=api-awards";
		break;
		case "personnel" :
			return ENTRADA_URL."/api/personnel.api.php";
		break;
		default :
			return "";
		break;
	}
}

// Function that checks to see if magic_quotes_gpc is enabled or not.
function checkslashes($value="", $type = "insert") {
	switch ($type) {
		case "insert" :
			if (!ini_get("magic_quotes_gpc")) {
				return addslashes($value);
			} else {
				return $value;
			}
		break;
		case "display" :
			if (!ini_get("magic_quotes_gpc")) {
				return htmlspecialchars($value);
			} else {
				return htmlspecialchars(stripslashes($value));
			}
		break;
		default :
			return false;
		break;
	}
}

/**
 * Function that returns data from the authentication database.
 *
 * @param string $type
 * @param int $id
 * @return string
 */
function get_account_data($type = "", $id = 0) {
	global $db;

	if($id = (int) trim($id)) {
		switch(strtolower($type)) {
			case "number" :
				$query = "SELECT `number` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=".$db->qstr($id);
            break;
			case "firstname" :
				$query = "SELECT `firstname` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=".$db->qstr($id);
			break;
			case "lastname" :
				$query = "SELECT `lastname` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=".$db->qstr($id);
			break;
			case "fullname" :
			case "lastfirst" :
				$query = "SELECT CONCAT_WS(', ', `lastname`, `firstname`) AS `fullname` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=".$db->qstr($id);
				$type = "fullname";
			break;
			case "wholename" :
			case "firstlast" :
				$query = "SELECT CONCAT_WS(' ', `firstname`, `lastname`) AS `firstlast` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=".$db->qstr($id);
				$type = "firstlast";
			break;
			case "email" :
				$query = "SELECT `email` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=".$db->qstr($id);
			break;
			case "grad_year" :
				$query = "SELECT `grad_year` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=".$db->qstr($id);
			break;
			case "organisation_id" :
				$query = "SELECT `organisation_id` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=".$db->qstr($id);
			break;
			case "username" :
				$query = "SELECT `username` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=".$db->qstr($id);
			break;
			case "role" :
				$query = "SELECT `role` FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id`=".$db->qstr($id)." AND `app_id`=".$db->qstr(AUTH_APP_ID);
			break;
			case "group" :
				$query = "SELECT `group` FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id`=".$db->qstr($id)." AND `app_id`=".$db->qstr(AUTH_APP_ID);
			break;
			default :
				return "";
			break;
		}

		$result = ((USE_CACHE) ? $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query) : $db->GetRow($query));
		if (!$result && strtolower($type) == "role") {
			$query = "SELECT `role` FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id` = ".$db->qstr($id)." AND `app_id` IN (".AUTH_APP_IDS_STRING.")";
			$result = ((USE_CACHE) ? $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query) : $db->GetRow($query));
		} elseif (!$result && strtolower($type) == "group") {
			$query = "SELECT `group` FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id` = ".$db->qstr($id)." AND `app_id` IN (".AUTH_APP_IDS_STRING.")";
			$result = ((USE_CACHE) ? $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query) : $db->GetRow($query));
		}
		return $result[$type];
	} else {
		return "";
	}
}



/**
 * Fetches the page name for the given page data. Query depends on what type
 * of data the action field is representing.
 * @global object $db
 * @param type $page_data
 * @return string
 */
function get_page_name($page_data){
	global $db;
	$raw_page_data = explode("-",$page_data);
	$action_field = $raw_page_data[0]."_".$raw_page_data[1];
	$action_value = $raw_page_data[2];

	unset($query);
	switch ($action_field){
		case 'cshare_id':
				$query = "	SELECT `folder_title` AS `page`
							FROM `community_shares`
							WHERE `cshare_id` = ".$db->qstr($action_value);
			break;
		case 'cscomment_id':
				$query = "	SELECT b.`file_title` AS `page`
							FROM `community_share_comments` AS a
							LEFT JOIN `community_share_files` AS b
							ON a.`csfile_id` = b.`csfile_id`
							WHERE a.`cscomment_id` = ".$db->qstr($action_value);
			break;
		case 'csfile_id':
				$query = "	SELECT b.`file_title` AS `page`
							FROM `community_share_files` A
							WHERE a.`csfile_id` = ".$db->qstr($action_value);
			break;
		case 'csfversion_id':
				$query = "	SELECT b.`file_title` AS `page`
							FROM `community_share_file_versions` AS a
							LEFT JOIN `community_share_files` AS b
							ON a.`csfile_id` = b.`csfile_id`
							WHERE a.`csfversion_id` = ".$db->qstr($action_value);
			break;
		case 'cannouncement_id':
				$query = "	SELECT `announcement_title` AS `page`
							FROM `community_announcements`
							WHERE `cannouncement_id` = ".$db->qstr($action_value);
			break;
		case 'cdiscussion_id':
				$query = "	SELECT `forum_title` AS `page`
							FROM `community_discussions`
							WHERE `cdiscussion_id` = ".$db->qstr($action_value);
			break;
		case 'cdtopic_id':
				$query = "	SELECT `topic_title` AS `page`
							FROM `community_discussion_topics`
							WHERE `cdtopic_id` = ".$db->qstr($action_value);
			break;
		case 'cevent_id':
				$query = "	SELECT `event_title` AS `page`
							FROM `community_events`
							WHERE `cevent_id` = ".$db->qstr($action_value);
			break;
		case 'cgallery_id':
				$query = "	SELECT `gallery_title` AS `page`
							FROM `community_galleries`
							WHERE `cgallery_id` = ".$db->qstr($action_value);
			break;
		case 'cgphoto_id':
				$query = "	SELECT `photo_title` AS `page`
							FROM `community_gallery_photos`
							WHERE `cgphoto_id` = ".$db->qstr($action_value);
			break;
		case 'cgcomment_id':
				$query = "	SELECT a.`gallery_title` AS `page`
							FROM `community_galleries` AS a
							LEFT JOIN `community_gallery_comments` AS b
							ON a.`cgaller_id` = b.`cgallery_id`
							WHERE `cgcomment_id` = ".$db->qstr($action_value);
			break;
		default:
			break;
	}




	if ($query) {
		$result = $db->GetOne($query);
	} else {
		$result = $action_field." ".$action_value;
	}

	return $result;
}

/**
 * Function will return the online status of a particular proxy_id. You have the choice of the output,
 * either text (default), image or an integer.
 * 0 = offline
 * 1 = away
 * 2 = online
 *
 * @param int $proxy_id
 * @param str $type
 * @return depends on the $output_type variable.
 */
function get_online_status($proxy_id = 0, $output_type = "text") {
	global $db;

	$output = 0;

	if($proxy_id = (int) trim($proxy_id)) {
		$query = "	SELECT MAX(`timestamp`) AS `timestamp`
					FROM `users_online`
					WHERE `proxy_id` = ".$db->qstr($proxy_id)."
					GROUP BY `proxy_id`";
		$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
		if(($result) && ((int) $result["timestamp"])) {
			if((int) $result["timestamp"] < (time() - 600)) {
				$output = 1;
			} else {
				$output = 2;
			}
		}
	}

	switch($output_type) {
		case "text" :
			switch($output) {
				case 1 :
					$output = "status-away";
					break;
				case 2 :
					$output = "status-online";
					break;
				default :
					$output = "status-offline";
					break;
			}
			break;
		case "image" :
			switch($output) {
				case 1 :
					$output = "<img src=\"".ENTRADA_RELATIVE."/images/list-status-away.gif\" width=\"12\" height=\"12\" alt=\"Online Status: Away\" title=\"Online Status: Away\" style=\"vertical-align: middle\" />";
					break;
				case 2 :
					$output = "<img src=\"".ENTRADA_RELATIVE."/images/list-status-online.gif\" width=\"12\" height=\"12\" alt=\"Online Status: Online\" title=\"Online Status: Online\" style=\"vertical-align: middle\" />";
					break;
				default :
					$output = "<img src=\"".ENTRADA_RELATIVE."/images/list-status-offline.gif\" width=\"12\" height=\"12\" alt=\"Online Status: Offline\" title=\"Online Status: Offline\" style=\"vertical-align: middle\" />";
					break;
			}
			break;
		case "int" :
		default :
			continue;
			break;
	}

	return $output;
}

/**
 * Handy function that takes the QUERY_STRING and adds / modifies / removes elements from it
 * based on the $modify array that is provided.
 *
 * @param array $modify
 * @return string
 * @example echo "index.php?".replace_query(array("action" => "add", "step" => 2));
 */
function replace_query($modify = array(), $html_encode_output = false) {
	$process	= array();
	$tmp_string	= array();
	$new_query	= "";

	// Checks to make sure there is something to modify, else just returns the string.
	if(count($modify) > 0) {
		$original	= explode("&", $_SERVER["QUERY_STRING"]);
		if(count($original) > 0) {
			foreach ($original as $value) {
				$pieces = explode("=", $value);
				// Gets rid of any unset variables for the URL.
				if(isset($pieces[0]) && isset($pieces[1])) {
					if (!isset($process[$pieces[0]])) {
						$process[$pieces[0]] = $pieces[1];
					} else {
						// inputs with multiple values like checkboxes or any other array input
						if (!is_array($process[$pieces[0]])) {
							$process[$pieces[0]] = array($process[$pieces[0]]);
						}

						$process[$pieces[0]][] = $pieces[1];
					}
				}
			}
		}

		foreach ($modify as $key => $value) {
		// If the variable already exists, replace it, else add it.
			if(array_key_exists($key, $process)) {
				if(($value === 0) || (($value) && ($value !=""))) {
					$process[$key] = $value;
				} else {
					unset($process[$key]);
				}
			} else {
				if(($value === 0) || (($value) && ($value !=""))) {
					$process[$key] = $value;
				}
			}
		}
		if(count($process) > 0) {
			foreach ($process as $var => $value) {
				if (!is_array($value)) {
					$tmp_string[] = $var."=".$value;
				} else {
					// inputs with multiple values like a radio or any other array input
					foreach ($value as $multi_value) {
						$tmp_string[] = $var."=".$multi_value;
					}
				}
			}
			$new_query = implode("&", $tmp_string);
		} else {
			$new_query = "";
		}
	} else {
		$new_query = $_SERVER["QUERY_STRING"];
	}

	return (((bool) $html_encode_output) ? html_encode($new_query) : $new_query);
}

/**
 * Here for historical reasons.
 *
 */
function order_link($field, $name, $order, $sort, $location = "public") {
	switch($location) {
		case "admin" :
			return admin_order_link($field, $name);
			break;
		case "public" :
		default :
			return public_order_link($field, $name);
			break;
	}
}

/**
 * This function handles sorting and ordering for the public modules.
 *
 * @param string $field_id
 * @param string $field_name
 * @return string
 */
function public_order_link($field_id, $field_name, $url = "") {
	global $MODULE;

    if (!$url) {
        $url = ENTRADA_URL."/".$MODULE.(isset($SUBMODULE) && $SUBMODULE ? "/".$SUBMODULE : "");
    }

	if(isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) == strtolower($field_id)) {
		if(strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) == "desc") {
			return "<a href=\"".$url."?".replace_query(array("so" => "asc"))."\" title=\"Order by ".$field_name.", Sort Ascending\">".$field_name."</a>";
		} else {
			return "<a href=\"".$url."?".replace_query(array("so" => "desc"))."\" title=\"Order by ".$field_name.", Sort Decending\">".$field_name."</a>";
		}
	} else {
		return "<a href=\"".$url."?".replace_query(array("sb" => $field_id))."\" title=\"Order by ".$field_name."\">".$field_name."</a>";
	}
}

/**
 * This function handles sorting and ordering for the public modules.
 *
 * @param string $field_id
 * @param string $field_name
 * @return string
 */
function community_public_order_link($field_id, $field_name, $url) {

	if(strtolower($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["sb"]) == strtolower($field_id)) {
		if(strtolower($_SESSION[APPLICATION_IDENTIFIER]["community_page"]["so"]) == "desc") {
			return "<a href=\"".$url."?".replace_query(array("so" => "asc"))."\" title=\"Order by ".$field_name.", Sort Ascending\">".$field_name."</a>";
		} else {
			return "<a href=\"".$url."?".replace_query(array("so" => "desc"))."\" title=\"Order by ".$field_name.", Sort Decending\">".$field_name."</a>";
		}
	} else {
		return "<a href=\"".$url."?".replace_query(array("sb" => $field_id))."\" title=\"Order by ".$field_name."\">".$field_name."</a>";
	}
}

/**
 * This function handles sorting and ordering for the administration modules.
 *
 * @param string $field_id
 * @param string $field_name
 * @return string
 */
function admin_order_link($field_id, $field_name, $submodule = null, $custom_selector = null) {
	global $MODULE;

	if (isset($submodule)) {
		$module_url = $MODULE . "/" . $submodule;
	} else {
		$module_url = $MODULE;
	}

    if (isset($custom_selector) && $custom_selector) {
        $module_selector = $custom_selector;
    } else {
        $module_selector = $MODULE;
    }

	if (isset($_SESSION[APPLICATION_IDENTIFIER][$module_selector]["sb"]) && strtolower($_SESSION[APPLICATION_IDENTIFIER][$module_selector]["sb"]) == strtolower($field_id)) {
		if (strtolower($_SESSION[APPLICATION_IDENTIFIER][$module_selector]["so"]) == "desc") {
			return "<a href=\"".ENTRADA_URL."/admin/".$module_url."?".replace_query(array("so" => "asc"))."\" title=\"Order by ".$field_name.", Sort Ascending\">".$field_name."</a>";
		} else {
			return "<a href=\"".ENTRADA_URL."/admin/".$module_url."?".replace_query(array("so" => "desc"))."\" title=\"Order by ".$field_name.", Sort Decending\">".$field_name."</a>";
		}
	} else {
		return "<a href=\"".ENTRADA_URL."/admin/".$module_url."?".replace_query(array("sb" => $field_id))."\" title=\"Order by ".$field_name."\">".$field_name."</a>";
	}
}

/**
 * This function handles sorting and ordering for the public modules.
 *
 * @param string $field_id
 * @param string $field_name
 * @return string
 */
function community_order_link($field_id, $field_name) {
	global $PAGE_URL, $COMMUNITY_URL, $COMMUNITY_ID;

	if(strtolower($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"]) == strtolower($field_id)) {
		if(strtolower($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]) == "desc") {
			return "<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":members?".replace_query(array("sb" => $field_id, "so" => "asc"))."\" title=\"Order by ".$field_name.", Sort Ascending\">".$field_name."</a>";
		} else {
			return "<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":members?".replace_query(array("sb" => $field_id, "so" => "desc"))."\" title=\"Order by ".$field_name.", Sort Decending\">".$field_name."</a>";
		}
	} else {
		return "<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":members?".replace_query(array("sb" => $field_id))."\" title=\"Order by ".$field_name."\">".$field_name."</a>";
	}
}

/**
 * Function will return the actual full title text of the filter key.
 *
 * @param string $filter_key
 * @return string
 */
function filter_name($filter_key) {
	global $translate;
	switch ($filter_key) {
		case "teacher" :
			return "Teachers Involved";
		break;
		case "student" :
			return "Students Involved";
		break;
		case "course" :
			return "Courses Involved";
		break;
		case "group" :
			return "Classes / Groups Involved";
		break;
		case "eventtype" :
			return "Event Types";
		break;
		case "term" :
			return "Terms Involved";
		break;
		case "cp" :
			return $translate->_("Clinical Presentations") . " Involved";
		break;
		case "co" :
			return $translate->_("Curriculum Objectives") . " Involved";
		break;
		case "topic" :
			return "Hot Topics Involved";
		break;
		case "department" :
			return "Departments Involved";
		break;
		default :
			return false;
		break;
	}
}

/**
 * Returns the starting second's timestamp of the $type[day || week || month || year].
 *
 * @param string $type
 * @param integer $timestamp
 * @return integer
 */
function startof($type, $timestamp = 0) {
	if (!(int) $timestamp) {
		$timestamp = time();
	}

	switch($type) {
		case "all" :
			return false;
		break;
		case "day" :
		case "academic" :
			return mktime(0, 0, 0, date("n", $timestamp), date("j", $timestamp), date("Y", $timestamp));
		break;
		case "month" :
			return mktime(0, 0, 0, date("n", $timestamp), 1, date("Y", $timestamp));
		break;
		case "year" :
			return mktime(0, 0, 0, 1, 1, date("Y", $timestamp));
		break;
		case "week" :
		default :
			return mktime(0, 0, 0, date("n", $timestamp), (date("j", $timestamp) - date("w", $timestamp)), date("Y", $timestamp));
		break;
	}
}

/**
 * This function returns the provided template file usith the method passed.
 *
 * @param string $template_file
 * @param string $fetch_style
 * @example $template_html = fetch_template("globa/external");
 * @return string
 */
function fetch_template($template_file = "", $fetch_style = "filesystem") {
    global $ENTRADA_TEMPLATE;

	if ($template_file && ($template_file = clean_input($template_file, "dir"))) {
		$template_file = $ENTRADA_TEMPLATE->absolute()."/layouts/".$template_file.".tpl.php";
        
		if (@file_exists($template_file)) {
			switch ($fetch_style) {
				case "url" :
					return @file_get_contents($ENTRADA_TEMPLATE->url()."/".$template_file.".tpl.php");
				break;
				case "filesystem" :
				default :
					return @file_get_contents($template_file);
				break;
			}
		}
	}

	return false;
}

function fetch_organisation_installation($organisation_id = 0) {
    global $db;

    if ($organisation_id = (int) $organisation_id) {
        $query = "SELECT `organisation_installation` FROM `".AUTH_DATABASE."`.`organisations` WHERE `organisation_id` = ".$db->qstr($organisation_id);
        $result	= $db->GetRow($query);
        if ($result) {
            return $result["organisation_installation"];
        }
    }

    return false;
}

function fetch_organisation_title($organisation_id = 0) {
	global $db;

	if ($organisation_id = (int) $organisation_id) {
		$query = "SELECT `organisation_title` FROM `".AUTH_DATABASE."`.`organisations` WHERE `organisation_id` = ".$db->qstr($organisation_id);
		$result	= $db->GetRow($query);
		if ($result) {
			return $result["organisation_title"];
		}
	}

	return false;
}

/**
 * This function returns the full path of the course, including the name (i.e. Term 1 > Unit 3 > FooBar 101).
 *
 * @param int $course_id
 * @return string
 */
function fetch_course_path($course_id = 0, $cunit_id = null) {
	$output = "";

	$course_id = (int) $course_id;

	if ($course_id) {
		$curriculum_path = curriculum_hierarchy($course_id, true, true);

		if (is_array($curriculum_path) && !empty($curriculum_path)) {
			$output = implode(" &gt; ", $curriculum_path);
		}

		if ($cunit_id) {
			$course_unit = Models_Course_Unit::fetchRowByID($cunit_id);
			$output .= " &gt; <a href=\"".ENTRADA_URL."/courses/units?id=".$course_id."&cunit_id=".$cunit_id."\">".$course_unit->getUnitText()."</a>";
		}
	}

	if (!$output) {
		$output = "No Associated Course";
	}

	return $output;
}

/**
 * This function returns the name of the course if it is found, otherwise false.
 *
 * @param int $id
 * @return string
 */
function fetch_course_title($course_id = 0, $return_course_name = true, $return_course_code = false) {
	global $db;

	if (($course_id = (int) $course_id) && ($return_course_name || $return_course_code)) {
		$output = array();
		$query	= "	SELECT `course_name`, `course_code`
					FROM `courses`
					WHERE `course_id` = ".$db->qstr($course_id);
		$result	= $db->GetRow($query);
		if ($result) {
			if (((bool) $return_course_name) && ($result["course_name"])) {
				$output[] = $result["course_name"];
			}

			if (((bool) $return_course_code) && ($result["course_code"])) {
				$output[] = $result["course_code"];
			}

			return implode(": ", $output);
		}
	}

	return false;
}

function fetch_group_title($group_id = 0) {
	global $db;

	if ($group_id = (int) $group_id) {
		$query = "SELECT `group_name` FROM `groups` WHERE `group_id` = ".$db->qstr($group_id);
		$result	= $db->GetRow($query);
		if ($result) {
			return $result["group_name"];
		}
	}

	return false;
}

function fetch_term_title($curriculum_type_id = 0) {
	global $db;

	if ($curriculum_type_id = (int) $curriculum_type_id) {
		$query = "SELECT `curriculum_type_name` FROM `curriculum_lu_types` WHERE `curriculum_type_id` = ".$db->qstr($curriculum_type_id);
		$result	= $db->GetRow($query);
		if ($result) {
			return $result["curriculum_type_name"];
		}
	}

	return false;
}

function fetch_objective_title($objective_id = 0) {
	global $db, $ENTRADA_USER;

	if ($objective_id = (int) $objective_id) {
		$query = "SELECT a.`objective_name` FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_id` = ".$db->qstr($objective_id)."
					AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
		$result	= $db->GetRow($query);
		if ($result) {
			return $result["objective_name"];
		}
	}

	return false;
}

function fetch_eventtype_title($eventtype_id = 0) {
	global $db;

	if ($eventtype_id = (int) $eventtype_id) {
		$query	= "SELECT `eventtype_title` FROM `events_lu_eventtypes` WHERE `eventtype_id` = ".$db->qstr($eventtype_id);
		$result	= $db->GetRow($query);
		if ($result) {
			return $result["eventtype_title"];
		}
	}

	return false;
}

function fetch_clinical_presentations($parent_id = 0, $presentations = array(), $course_id = 0, $presentation_ids = false, $org_id = 0) {
	global $db, $ENTRADA_USER, $translate;

	$parent_id = (int) $parent_id;
	$course_id = (int) $course_id;
	$org_id = ($org_id == 0 ? $ENTRADA_USER->getActiveOrganisation() : (int) $org_id );

	if ($course_id) {
		$presentation_ids = array();

		$query = "	SELECT `objective_id`
					FROM `course_objectives`
					WHERE `course_id` = ".$db->qstr($course_id)."
					AND `objective_type` = 'event'
                    AND `active` = '1'";
		$allowed_objectives = $db->GetAll($query);
		if ($allowed_objectives) {
			foreach ($allowed_objectives as $presentation) {
				$presentation_ids[] = $presentation["objective_id"];
			}
		}
	}

	if ($parent_id) {
		$query = "	SELECT a.*
					FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE `objective_active` = '1'
					AND `objective_parent` = ".$db->qstr($parent_id)."
					AND b.`organisation_id` = ".$db->qstr($org_id);
	} else {
		$objective_name = $translate->_("events_filter_controls");
		$objective_name = $objective_name["cp"]["global_lu_objectives_name"];

		$query = "	SELECT a.*
					FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_active` = '1'
					AND b.`organisation_id` = ".$db->qstr($org_id)."
					AND a.`objective_name` = ".$db->qstr($objective_name);
	}

	$results = $db->GetAll($query);
	if ($results) {
		foreach ($results as $result) {
			if ($parent_id) {
				$presentations[] = $result;
			}
			$presentations = fetch_clinical_presentations($result["objective_id"], $presentations, 0, (isset($presentation_ids) && $presentation_ids ? $presentation_ids : array()), $org_id);
		}
	}

	if (!$parent_id && is_array($presentation_ids)) {
		foreach ($presentations as $key => $presentation) {
			if (array_search($presentation["objective_id"], $presentation_ids) === false) {
				unset($presentations[$key]);
			}
		}
	}

	return $presentations;
}

function fetch_curriculum_objectives_children($parent_id = 0, &$objectives) {
	global $db, $ENTRADA_USER, $translate;

	$parent_id = (int) $parent_id;

	if ($parent_id) {
		$query = "	SELECT a.*
					FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_active` = '1'
					AND a.`objective_parent` = ".$db->qstr($parent_id)."
					AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
	} else {
		$objective_name = $translate->_("events_filter_controls");
		$objective_name = $objective_name["co"]["global_lu_objectives_name"];

		$query = "	SELECT a.*
					FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_active` = '1'
					AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
					AND a.`objective_name` = ".$db->qstr($objective_name);
	}

	$results = $db->GetAll($query);
	if ($results) {
		foreach ($results as $result) {
			$children = fetch_curriculum_objectives_children($result["objective_id"], $objectives);

			if (!$children) {
				$objectives[] = $result;
			}
		}

		return true;
	}

	return false;
}

function fetch_objective_set_for_objective_id($id = 0){
	global $db;

	$parent_id = (int)$id;

	if (!$parent_id) {
		return false;
	}

	$level = 0;

	do{
		$level++;
		$query = "	SELECT * FROM `global_lu_objectives`
					WHERE `objective_id` = ".$db->qstr($parent_id);
		$parent = $db->GetRow($query);
		$parent_id = (int)$parent["objective_parent"];
	}while($parent_id && $level < 10);

	if ($level == 10) {
		return false;
	}

	return $parent;
}

function fetch_objective_child_mapped_course($objective_id = 0,$course_id = 0){
	global $db;

	$parent_id = (int)$objective_id;

	if (!$parent_id || !$course_id) {
		return false;
	}
	$query = "	SELECT a.*, COALESCE(b.`cobjective_id`,0) AS `mapped` FROM `global_lu_objectives` a
				LEFT JOIN `course_objectives` b
				ON a.`objective_id` = b.`objective_id`
                AND b.`active` = '1'
				AND b.`course_id` = ".$db->qstr($course_id)."
				WHERE `objective_parent` = ".$db->qstr($objective_id);
	$children = $db->GetRow($query);
	return children_check_mapped($children,$objective_id,$course_id);
}

function children_check_mapped($children,$objective_id,$course_id){
	if (!$children || !$objective_id || !$course_id) {
		return false;
	}
	foreach($children as $child){
		if($child["mapped"]){
			return true;
		}
		$query = "	SELECT a.*, COALESCE(b.`cobjective_id`,0) AS `mapped` FROM `global_lu_objectives` a
					LEFT JOIN `course_objectives` b
					ON a.`objective_id` = b.`objective_id`
                    AND b.`active` = '1'
					AND b.`course_id` = ".$db->qstr($course_id)."
					WHERE `objective_parent` = ".$db->qstr($child["objective_id"]);
		$children = $db->GetRow($query);
		$r = children_check_mapped($children,$objective_id,$course_id);
		if ($r) {
			return true;
		}
	}

	return false;
}

function fetch_event_topics() {
	global $db, $ENTRADA_USER;

	$query = "	SELECT a.`topic_id`, a.`topic_name`
				FROM `events_lu_topics` AS a
				JOIN `topic_organisation` AS b
				ON b.`topic_id` = a.`topic_id`
				WHERE b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
				ORDER BY a.`topic_name` ASC";
	$results = $db->GetAll($query);

	return $results;
}

function fetch_event_topic_title($topic_id = 0) {
	global $db;

	$topic_id = (int) $topic_id;

	if ($topic_id) {
		$query = "SELECT `topic_name` FROM `events_lu_topics` WHERE `topic_id` = ".$db->qstr($topic_id);

		return $db->GetOne($query);
	}

	return false;
}

/**
 * Function returns the group_id of the first year class. This year is
 * frequently used used as a default or fallback throughout Entrada.
 */
function fetch_first_cohort() {
	global $db, $ENTRADA_USER;

	$query = "	SELECT a.`group_id` FROM `groups` AS a
				JOIN `group_organisations` AS b
				ON a.`group_id` = b.`group_id`
				WHERE a.`group_type` = 'cohort'
				AND a.`group_active` = '1'
				AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
				ORDER BY a.`group_id` DESC LIMIT 0, 1";
	return $db->GetOne($query);
}

/**
 * Function returns the graduating year of the first year class. This year is
 * frequently used used as a default or fallback throughout Entrada.
 */
function fetch_first_year() {
	/**
	 * This is based on a 4 year program with a year cut-off of July 1.
	 * @todo This should be in the settings.inc.php file.
	 */
	return date("Y") + (date("m") < 7 ? 3 : 4);
}

/**
 * This function provides the unix timestamps of the start and end of the requested date type.
 *
 * @uses startof()
 * @param string $type
 * @param int $timestamp
 * @return array or false if $type = all
 */
function fetch_timestamps($type, $timestamp_start, $timestamp_finish = 0) {
	$start = startof($type, $timestamp_start);

	switch ($type) {
		case "all" :
			return false;
		break;
		case "day" :
		case "week" :
		case "month" :
		case "year" :
			return array (
					"start" => $start,
					"end" => (strtotime("+1 ".$type, $start) - 1)
			);
		break;
		case "academic" :
			if (defined("ACADEMIC_YEAR_START_DATE")) {
				$academic_start_date = ACADEMIC_YEAR_START_DATE;
			} else {
				$academic_start_date = "September 1";
			}

			$academic_start_date_timestamp = strtotime("00:00:00 " . $academic_start_date . ", " . date("y", $start));

			if (date("n", $start) >= date("n", $academic_start_date_timestamp)) {
				$academic_start = strtotime("00:00:00 " . $academic_start_date . ", " . date("y", $start));
			} else {
				$academic_start = strtotime("00:00:00 " . $academic_start_date . ", " . date("y", strtotime("-1 year", $start)));
			}
			return array (
					"start" => $academic_start,
					"end" => (strtotime("+1 year", $academic_start) - 1)
			);
		break;
		case "custom" :
			return array (
					"start" => $timestamp_start,
					"end" => $timestamp_finish
			);
		break;
		default :
			return array (
					"start" => $start,
					"end" => (strtotime("+1 week", $start) - 1)
			);
		break;
	}
}

/**
 * Function will return the department title based on the provided department_id.
 * @param int $department_id
 * @return string or bool
 */
function fetch_department_title($department_id = 0) {
	global $db;

	if($department_id = (int) $department_id) {
		$query	= "SELECT `department_title` FROM `".AUTH_DATABASE."`.`departments` WHERE `department_id` = ".$db->qstr($department_id);
		$result	= $db->GetRow($query);

		if(($result) && ($department_title = trim($result["department_title"]))) {
			return $department_title;
		}
	}
	return false;
}

/**
 * Get week title
 * @param int $week_id
 */
function fetch_week_title($week_id = 0) {
    global $db;
    if (($week_id = (int) $week_id)) {
        $week = Models_Week::fetchRowByID($week_id);
        if ($week) {
            return $week->getWeekTitle();
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * Function will return the parent_id based on the provided department_id.
 * @param int $department_id
 * @return parent_id or bool
 */
function fetch_department_parent($department_id = 0) {
	global $db;

	if($department_id = (int) $department_id) {
		$query	= "SELECT `parent_id` FROM `".AUTH_DATABASE."`.`departments` WHERE `department_id` = ".$db->qstr($department_id);
		$result	= $db->GetRow($query);

		if(($result)) {
			return $result["parent_id"];
		}
	}
	return false;
}

/**
 * Function will return the children of a department (i.e. divisions) based on the provided department_id.
 * @param int $department_id
 * @return array(department IDs) or bool (false)
 */
function fetch_department_children($department_id = 0) {
	global $db;

	if($department_id = (int) $department_id) {
		$query	= "SELECT `department_id` FROM `".AUTH_DATABASE."`.`departments` WHERE `parent_id` = ".$db->qstr($department_id);
		$results = $db->GetAll($query);

		if(($results)) {
			return $results;
		} else {
			return false;
		}
	}
	return false;
}

/**
 * Function will return a list of Countries.
 * @param none
 * @return resultset(countries_id, country) or bool
 */
function fetch_countries() {
	global $db;

	$query = "	SELECT * FROM `global_lu_countries`
				ORDER BY `country` ASC";

	if ($results = $db->GetAll($query)) {
		return $results;
	} else {
		return false;
	}
}

/**
 * Function will return a list of available templates.
 * @param none
 * @return results
 */
function fetch_templates() {
	//search the templates directory for available templates.
	$dir    = ENTRADA_ABSOLUTE . '/templates';
	$dir_contents = scandir($dir);

	if (is_array($dir_contents) && count($dir_contents)) {
		$results = array_filter($dir_contents, "filter_dir");
		if ($results) {
			return $results;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * This function is the array_filter callback used in fetch_templates to
 * filter the array of template directory contents for the removal of files,
 * "." and "..".
 *
 * @param <string> $dir
 * @return <boolean> true if $item is a directory and not "." or ".."; false otherwise.
 */
function filter_dir($item) {
	$item_abs = ENTRADA_ABSOLUTE . '/templates/' . $item;
	if (is_dir($item_abs) && $item != "." && $item != "..") {
		return true;
	} else {
		return false;
	}
}


/**
 * Function will return a specific Country from the list of Countries.
 * @param $countries_id
 * @return resultset(country) or bool
 */
function fetch_specific_country($countries_id) {
	global $db;

	$query	= "	SELECT `country` FROM `global_lu_countries`
				WHERE `countries_id` =".$db->qstr($countries_id);

	if ($result = $db->GetRow($query)) {
		return $result["country"];
	} else {
		return false;
	}
}

/**
 * This function generates a select box containing all child categories below the
 * category passed in.
 *
 * @param unknown_type $results
 * @param unknown_type $parent_id
 * @param unknown_type $current_selected
 * @param unknown_type $indent
 * @param unknown_type $exclude
 * @param unknown_type $hide_empty
 * @return unknown
 */
function clerkship_categories_inselect($results, $parent_id = 0, $current_selected = array(), $indent = 0, $exclude = array(), $hide_empty = false) {
	if($indent > 99) {
		die("Preventing infinite loop");
	}

	$output	= "";
	$ctotal	= @count($results);
	for($i = 0; $i < $ctotal; $i++) {
		if($results[$i]["category_parent"] == $parent_id) {
			if((!@in_array($results[$i]["category_id"], $exclude)) && (!@in_array($parent_id, $exclude))) {
				$result  = clerkship_categories_inselect($results, $results[$i]["category_id"], $current_selected, $indent + 1, $exclude, $hide_empty);
				$output .= (((!$hide_empty) || ($result != "")) ? "<option value=\"".$results[$i]["category_id"]."\"".((@in_array($results[$i]["category_id"], $current_selected)) ? " selected=\"selected\"" : "").">".str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $indent).(($indent > 0) ? "&rarr;&nbsp;" : "").$results[$i]["category_name"]."</option>\n" : " ");
				$output .= $result;
			} else {
				$exclude[] = $results[$i]["category_id"];
			}
		}
	}
	return $output;
}

/**
 * Returns true if child categories exist underneath the parent_id passed in
 *
 * @param unknown_type $parent_id
 * @param unknown_type $indent
 * @return unknown
 */
function clerkship_generate_included_categories($parent_id = 0, $indent = 0) {
	global $db, $report_results;

	if($indent > 99) die("Preventing infinite loop");

	$query		= "
				SELECT a.`category_id`, a.`category_name`
				FROM `".CLERKSHIP_DATABASE."`.`categories` AS a
				WHERE a.`category_parent` = ".$db->qstr($parent_id)."
				AND a.`category_status` <> 'trash'
				GROUP BY a.`category_id`
				ORDER BY a.`category_order` ASC";
	$results	= $db->GetAll($query);
	foreach($results as $result) {
		$report_results[$result["category_name"]]["indent"]			= (int) $indent;
		$report_results[$result["category_name"]]["category_ids"][]	= (int) $result["category_id"];
		clerkship_generate_included_categories($result["category_id"], $indent + 1);
	}

	return ((@count($report_results) > 0) ? true : false);
}

/**
 * Returns all categories under the specified parent id as an array.
 *
 * @param unknown_type $parent_id
 * @param unknown_type $indent
 * @return unknown
 */
function clerkship_categories_inarray($parent_id, $indent = 0) {
	global $db, $sub_category_ids;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	$query		= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `category_parent`=".$db->qstr($parent_id)." AND `category_status`<>'trash' ORDER BY `category_order` ASC";
	$results	= $db->GetAll($query);
	foreach($results as $result) {
		$sub_category_ids[] = $result["category_id"];
		clerkship_categories_inarray($result["category_id"], $indent + 1);
	}

	return ((@count($sub_category_ids) > 0) ? true : false);
}

/**
 * Returns the name of the category with the supplied category id.
 *
 * @param unknown_type $category_id
 * @return unknown
 */
function clerkship_categories_name($category_id = 0) {
	global $db;

	$query	= "SELECT `category_name` FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `category_id`=".$db->qstr($category_id);
	$result	= $db->GetRow($query);
	if($result) {
		return $result["category_name"];
	} else {
		return "Not Available";
	}
}

/**
 * Output any available Clerkship evaluations the learner may have to complete.
 *
 * @global object $db
 */
function clerkship_display_available_evaluations() {
	global $db, $ENTRADA_USER;

	/**
	 * Display Clerkship Evaluation Information to Student.
	 */
	$query = "	SELECT a.*, b.`event_title`, b.`event_finish`, d.`form_title`, d.`form_type`
				FROM `".CLERKSHIP_DATABASE."`.`notifications` AS a
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS b
				ON b.`event_id` = a.`event_id`
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`evaluations` AS c
				ON c.`item_id` = a.`item_id`
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`eval_forms` AS d
				ON d.`form_id` = c.`form_id`
				WHERE a.`user_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
				AND a.`item_maxinstances` > '0'
				AND (
					a.`notification_status` <> 'complete'
					OR a.`notification_status` <> 'cancelled'
				)
					AND b.`event_finish` < ".$db->qstr(strtotime("00:00:01"))."
				AND b.`event_status` = 'published'
				AND c.`item_status` = 'published'
				AND d.`form_status` = 'published'
				ORDER BY a.`notified_last` DESC";
	$results = $db->GetAll($query);
	if($results) {
		?>
		<div class="display-notice" style="color: #333333">
			<h2>Clerkship Evaluation<?php echo ((@count($results) != 1) ? "s" : ""); ?> To Complete</h2>
			<form action="<?php echo ENTRADA_URL; ?>/clerkship?action=remove" method="post">
			<table style="width: 97%" cellspacing="2" cellpadding="2" border="0" summary="Available Clerkship Evaluations">
			<colgroup>
				<col style="width: 25%" />
				<col style="width: 75%" />
			</colgroup>
			<tfoot>
				<tr>
					<td colspan="2" style="text-align: right; padding-right: 15px">
						<input type="submit" class="btn btn-danger" value="Remove From List" />
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php
			foreach($results as $result) {
				echo "<tr>\n";
				echo "	<td style=\"vertical-align: top; white-space: nowrap; font-size: 12px\">\n";
				echo "		<input type=\"checkbox\" name=\"mark_done[]\" id=\"notification_".(int) $result["notification_id"]."\" value=\"".(int) $result["notification_id"]."\" style=\"vertical-align: middle\" /> ";
				echo "		<label for=\"notification_".(int) $result["notification_id"]."\">".date(DEFAULT_DATETIME_FORMAT, $result["event_finish"])."</label>\n";
				echo "	</td>\n";
				echo "	<td style=\"padding-top: 3px; vertical-align: top; white-space: normal; font-size: 12px\">\n";
				echo "		<a href=\"".ENTRADA_URL."/clerkship?section=evaluate&amp;nid=".(int) $result["notification_id"]."\">".ucwords($result["form_type"])." Evaluation".(($result["item_maxinstances"] != 1) ? "s" : "")." Available</a> <span class=\"content-small\">(".$result["item_maxinstances"]." available)</span>\n";
				echo "		<div class=\"content-small\">\n";
				echo "			<strong>For</strong> ".$result["event_title"]."\n";
				echo "		</div>\n";
				echo "</tr>\n";
			}
			?>
			</tbody>
			</table>
			</form>
		</div>
		<?php
	}
}

/**
 * Function will return the number of sub-categories under the ID you specify..
 * @param $category_parent
 * @return total number of children under category parent
 */
function clerkship_categories_children_count($category_parent = 0) {
	global $db;

	$query	= "SELECT COUNT(*) AS `total` FROM `categories` WHERE `category_parent`=".$db->qstr($category_parent);
	$result	= $db->GetOne($query);

	return $result;
}

/**
 * Function will return a list of Clerkship Disciplines.
 * @param none
 * @return resultset(discipline_id, discipline) or bool
 */
function clerkship_fetch_disciplines() {
	global $db;

	$query	= "SELECT * FROM `global_lu_disciplines`
                ORDER BY `discipline` ASC";

	if($results	= $db->GetAll($query)) {
		return $results;
	} else {
		return false;
	}
}

/**
 * Function will return a specific Clerkship Discipline.
 * @param $discipline_id
 * @return resultset(discipline) or bool
 */
function clerkship_fetch_specific_discipline($discipline_id) {
	global $db;

	$query	= "SELECT `discipline` FROM `global_lu_disciplines`
		WHERE `discipline_id` =".$db->qstr($discipline_id);

	if($result = $db->GetRow($query)) {
		return $result['discipline'];
	} else {
		return false;
	}
}

/**
 * Function will return a list of schools.
 * @param $discipline_id
 * @return resultset(disciplines) or bool
 */
function clerkship_fetch_schools() {
	global $db;

	$query		= "	SELECT *
					FROM `global_lu_schools`
					ORDER BY `school_title`";
	$results	= $db->GetAll($query);
	if ($results) {
		return $results;
	}

	return false;
}

/**
 * Function will return a specfic school school_title from global_lu_schools.
 * @param $schools_id
 * @return resultset(school_title) or bool
 */
function clerkship_fetch_specific_school($schools_id) {
	global $db;

	$query		= "	SELECT `school_title`
					FROM `global_lu_schools`
					WHERE `schools_id` =".$db->qstr($schools_id);
	$result	= $db->GetRow($query);
	if ($result) {
		return $result["school_title"];
	}

	return false;
}

/**
 * Function will return an array containing past and current clerkship schedule or false if neither are found.
 * @param $user_id
 * @return array() or bool
 */
function clerkship_fetch_schedule($user_id) {
	global $db;

	$query				= "	SELECT *
							FROM `".CLERKSHIP_DATABASE."`.`events` AS a
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
							ON b.`event_id` = a.`event_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
							ON c.`region_id` = a.`region_id`
							WHERE a.`event_finish` >= ".$db->qstr(strtotime("00:00:00", time()))."
							AND (a.`event_status` = 'published' OR a.`event_status` = 'approval')
							AND b.`econtact_type` = 'student'
							AND b.`etype_id` = ".$db->qstr($user_id)."
							ORDER BY a.`event_start` ASC";
	$clerkship_schedule	= $db->GetAll($query);

	$query						= "	SELECT *
									FROM `".CLERKSHIP_DATABASE."`.`events` AS a
									LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
									ON b.`event_id` = a.`event_id`
									LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
									ON c.`region_id` = a.`region_id`
									WHERE a.`event_finish` <= ".$db->qstr(strtotime("00:00:00", time()))."
									AND (a.`event_status` = 'published' OR a.`event_status` = 'approval')
									AND b.`econtact_type` = 'student'
									AND b.`etype_id` = ".$db->qstr($user_id)."
									ORDER BY a.`event_start` ASC";
	$clerkship_past_schedule	= $db->GetAll($query);

	if ($clerkship_schedule || $clerkship_past_schedule) {
		$schedules["present"]	= $clerkship_schedule;
		$schedules["past"]		= $clerkship_past_schedule;
		return $schedules;
	} else {
		return false;
	}
}

/**
 * This function sets a session variable for the module it's run from
 *
 * @global type $MODULE
 * @param string $name              This is the session variable name
 * @param string $setting           This is the value of the variable, should be a $_POST
 * @param string $default_setting   This is the default value if one is not set
 * $param string $user_defined_module       This allows you to specify a module
 * @return string $setting_return   THis is the setting either from the selection or the session
 *
 *
 */
function preference_module_set($name, $setting, $default_setting, $user_defined_module = "", $clear_setting = false) {
	global $MODULE, $ENTRADA_USER;
	if ($user_defined_module) {
		$module_preference = $user_defined_module;
	} else {
		$module_preference = $MODULE;
	}

	$setting_return = "";
	if ($clear_setting === true) {
		$setting_return = "";
		$_SESSION[APPLICATION_IDENTIFIER][$module_preference][$name] = $setting_return;
	} else {
		if (isset($setting) && $setting != "") {
			$setting_return = $setting;
			$_SESSION[APPLICATION_IDENTIFIER][$module_preference][$name] = $setting_return;
		} else {
			if (isset($_SESSION[APPLICATION_IDENTIFIER][$module_preference][$name])) {
				$setting_return = $_SESSION[APPLICATION_IDENTIFIER][$module_preference][$name];
			} else {
				$setting_return = $default_setting;
			}
		}
	}
	return $setting_return;
}

/**
 * This function will load users module preferences into a session from the database table.
 * It also returns the preferences as an array, so they can be later compared to see if a
 * preferences_update() is required.
 *
 * @param string $module
 * @return array
 */
function preferences_load($module) {
	global $db, $ENTRADA_USER;

	if(!isset($_SESSION[APPLICATION_IDENTIFIER][$module])) {
		$query	= "SELECT `preferences` FROM `".AUTH_DATABASE."`.`user_preferences` WHERE `app_id`=".$db->qstr(AUTH_APP_ID)." AND `proxy_id`=".$db->qstr($ENTRADA_USER->getID())." AND `module`=".$db->qstr($module);
		$result	= $db->GetRow($query);
		if($result) {
			if($result["preferences"]) {
				$preferences = @unserialize($result["preferences"]);
				if(@is_array($preferences)) {
					$_SESSION[APPLICATION_IDENTIFIER][$module] = $preferences;
				}
			}
		}
	}

	return ((isset($_SESSION[APPLICATION_IDENTIFIER][$module])) ? $_SESSION[APPLICATION_IDENTIFIER][$module] : array());
}

/**
 * This function loads the preferances for specified user into an array
 * 
 * @global object $db
 * @param string $module
 * @param int $proxy_id
 * @return array $preferences
 * 
 */
function preferences_load_user($module, $proxy_id) {
	global $db;
    
    if (isset($module) && isset($proxy_id)) {
		$query	= "SELECT `preferences` FROM `".AUTH_DATABASE."`.`user_preferences` WHERE `app_id`=".$db->qstr(AUTH_APP_ID)." AND `proxy_id`=".$db->qstr($proxy_id)." AND `module`=".$db->qstr($module);
		$result	= $db->GetRow($query);
		if($result) {
			if($result["preferences"]) {
				$preferences = @unserialize($result["preferences"]);
				if(@is_array($preferences)) {
					return $preferences;
				}
			}
		}
    }
}

/**
 * This function will gather any associated permissions assigned by other individuals to this
 * user's account.
 *
 * @return array
 */
function permissions_load() {
	global $db, $ENTRADA_USER;

	$permissions = array();

	$query = "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`firstname`, a.`lastname`, b.`organisation_id`, b.`role`, b.`group`, b.`id` AS `access_id`, b.`private_hash`
				FROM `".AUTH_DATABASE."`.`user_data` AS a
				JOIN `".AUTH_DATABASE."`.`user_access` AS b
				ON b.`user_id` = a.`id`
				AND b.`app_id`=".$db->qstr(AUTH_APP_ID)."
				AND b.`account_active`='true'
				AND (b.`access_starts`='0' OR b.`access_starts`<=".$db->qstr(time()).")
				AND (b.`access_expires`='0' OR b.`access_expires`>=".$db->qstr(time()).")
				WHERE a.`id` = ".$db->qstr($ENTRADA_USER->getID())."
				ORDER BY b.`id` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		foreach ($results as $result) {
			$permissions[$result["access_id"]] = array (
				"id" => $result["proxy_id"],
				"access_id" => $result["access_id"],
				"group" => $result["group"],
				"role" => $result["role"],
				"organisation_id" => $result["organisation_id"],
                "private_hash" => $result["private_hash"],
				"fullname" => $result["fullname"],
				"firstname" => $result["firstname"],
				"lastname" => $result["lastname"]
			);
		}
	}

	$query = "	SELECT a.*, b.`id` AS `proxy_id`, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, b.`firstname`, b.`lastname`, c.`organisation_id`, c.`role`, c.`group`, c.`id` AS `access_id`
				FROM `permissions` AS a
				LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
				ON b.`id` = a.`assigned_by`
				LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS c
				ON c.`user_id` = b.`id` AND c.`app_id`=".$db->qstr(AUTH_APP_ID)."
				AND c.`account_active`='true'
				AND (c.`access_starts`='0' OR c.`access_starts`<=".$db->qstr(time()).")
				AND (c.`access_expires`='0' OR c.`access_expires`>=".$db->qstr(time()).")
				WHERE a.`assigned_to` = ".$db->qstr($ENTRADA_USER->getID())."
				AND a.`valid_from` <= ".$db->qstr(time())."
				AND a.`valid_until` >= ".$db->qstr(time())."
				ORDER BY `fullname` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		foreach ($results as $result) {
			$permissions[$result["access_id"]] = array (
				"id" => $result["proxy_id"],
				"access_id" => $result["access_id"],
				"permission_id" => $result["permission_id"],
				"group" => $result["group"],
				"role" => $result["role"],
				"organisation_id"=>$result["organisation_id"],
				"starts" => $result["valid_from"],
				"expires" => $result["valid_until"],
				"fullname" => $result["fullname"],
				"firstname" => $result["firstname"],
				"lastname" => $result["lastname"],
				"mask" => true
			);
		}
	}

	return $permissions;
}

/**
 * This function look at the user_access table for a particular entry (i.e. id) and user
 * in order to load the associated permissions into an array that can be set in the Session.
 * Modelled after the load_permissions function.
 *
 * @return array
 */
function load_org_group_role($proxy_id, $ua_id) {
	global $db;
	$permissions	= array();
	$query = "	SELECT b.`id` AS `proxy_id`, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, b.`firstname`, b.`lastname`, c.`organisation_id`, c.`role`, c.`group`, c.`access_starts`, c.`access_expires`
				FROM `".AUTH_DATABASE."`.`user_data` AS b
				JOIN `".AUTH_DATABASE."`.`user_access` AS c
				ON c.`user_id` = b.`id` AND c.`app_id`=".$db->qstr(AUTH_APP_ID)."
				AND c.`account_active`='true'
				AND (c.`access_starts`='0' OR c.`access_starts`<=".$db->qstr(time()).")
				AND (c.`access_expires`='0' OR c.`access_expires`>=".$db->qstr(time()).")
				WHERE c.`id` = " . $db->qstr($ua_id) . "
				AND b.`id` = ".$db->qstr($proxy_id);

	$result = $db->GetRow($query);
	if($result) {
		$permissions[$ua_id] = array("group" => $result["group"], "role" => $result["role"], "organisation_id"=>$result['organisation_id'],  "starts" => $result["access_starts"], "expires" => $result["access_expires"], "fullname" => $result["fullname"], "firstname" => $result["firstname"], "lastname" => $result["lastname"]);
	}
	return $permissions;
}


/**
 * medtech / staff wants in.
 * Page requires medtech / admin.
 *
 * @param array $requirements
 * @return bool
 * @example permissions_check(array("medtech" => "*", "faculty => array("faculty", "admin"), "staff" => "admin"));
 */
function permissions_check($requirements = array()) {
	global $ENTRADA_USER;
	if((is_array($requirements)) && (count($requirements)) && (is_array($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]))) {
		foreach ($requirements as $group => $roles) {
			if($group == "*") {
				if($roles == "*") {
					return true;
				} else {
					if(!@is_array($roles)) {
						$roles = array($roles);
					}

					if(@in_array($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"], $roles)) {
						return true;
					}
				}
			} else {
				if($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] == $group) {
					if($roles == "*") {
						return true;
					} else {
						if(!@is_array($roles)) {
							$roles = array($roles);
						}

						if(@in_array($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"], $roles)) {
							return true;
						}
					}
				}
			}
		}
	}

	return false;
}

function permissions_fetch($identifier, $type = "event", $existing_allowed_ids = array()) {
	global $db;

	if((is_array($existing_allowed_ids)) && (count($existing_allowed_ids))) {
		$allowed_ids	= $existing_allowed_ids;
	} else {
		$allowed_ids	= array();
	}

	switch($type) {
		case "event" :
			$query		= "	SELECT a.`event_id`, b.`proxy_id` AS `teacher`, c.`pcoord_id` AS `coordinator`, d.`proxy_id` AS `director`, e.`proxy_id` AS `ccoordinator`, f.`proxy_id` AS `pcoordinator`
							FROM `events` AS a
							LEFT JOIN `event_contacts` AS b
							ON b.`event_id` = a.`event_id`
							LEFT JOIN `courses` AS c
							ON c.`course_id` = a.`course_id`
							LEFT JOIN `course_contacts` AS d
							ON d.`course_id` = c.`course_id`
							AND d.`contact_type` = 'director'
							LEFT JOIN `course_contacts` AS e
							ON e.`course_id` = c.`course_id`
							AND e.`contact_type` = 'ccoordinator'
							LEFT JOIN `course_contacts` AS f
							ON f.`course_id` = c.`course_id`
							AND f.`contact_type` = 'pcoordinator'
							WHERE a.`event_id` = ".$db->qstr($identifier);
			$results	= $db->GetAll($query);
			if($results) {
				foreach ($results as $result) {
					$allowed_ids[] = $result["teacher"];
					$allowed_ids[] = $result["pcoordinator"];
					$allowed_ids[] = $result["coordinator"];
					$allowed_ids[] = $result["ccoordinator"];
					$allowed_ids[] = $result["director"];
					$allowed_ids[] = $result["other_director"];
				}
			}
		break;
		case "course" :
			$query		= "	SELECT a.`pcoord_id` AS `coordinator`, b.`proxy_id` AS `director`, c.`proxy_id` AS `pcoordinator`
							FROM `courses` AS a
							LEFT JOIN `course_contacts` AS b
							ON b.`course_id` = a.`course_id`
							AND b.`contact_type` = 'director'
							LEFT JOIN `course_contacts` AS c
							ON c.`course_id` = a.`course_id`
							AND c.`contact_type` = 'pcoordinator'
							WHERE a.`course_id` = ".$db->qstr($identifier)."
							AND a.`course_active` = '1'";
			$results	= $db->GetAll($query);
			if($results) {
				foreach ($results as $result) {
					$allowed_ids[] = $result["director"];
					$allowed_ids[] = $result["coordinator"];
					$allowed_ids[] = $result["pcoordinator"];
				}
			}
		break;
		case "quiz " :
			$query		= "	SELECT a.`proxy_id`
							FROM `quiz_contacts` AS a
							WHERE a.`quiz_id` = ".$db->qstr($identifier);
			$results	= $db->GetAll($query);
			if($results) {
				foreach ($results as $result) {
					$allowed_ids[] = $result["proxy_id"];
				}
			}
		break;
		default :
			continue;
		break;
	}

	return array_diff(array_unique($allowed_ids), array(""));
}

/**
 * This function controls the permission mask feature by ensuring validity of the mask id
 * and setting the tmp variable properly.
 *
 * @return true
 */
function permissions_mask() {
	global $db, $ENTRADA_USER;

	if(isset($_GET["mask"])) {
		if(trim($_GET["mask"]) == "close") {
			$ENTRADA_USER->setAccessId($ENTRADA_USER->getDefaultAccessId());
		} elseif((int) trim($_GET["mask"])) {
			$query	= "SELECT * FROM `permissions` WHERE `permission_id` = ".$db->qstr((int) trim($_GET["mask"]));
			$result	= $db->GetRow($query);
			if($result) {
				if($result["assigned_to"] == $ENTRADA_USER->getID()) {
					if($result["valid_from"] <= time()) {
						if($result["valid_until"] >= time()) {
							$query = "SELECT `id` FROM `".AUTH_DATABASE."`.`user_access`
										WHERE `user_id` = ".$db->qstr($result["assigned_by"])."
										AND `app_id` = ".$db->qstr(AUTH_APP_ID)."
										AND `account_active` = 'true'
										AND (`access_starts` = '0' OR `access_starts` <= ".$db->qstr(time()).")
										AND (`access_expires` = '0' OR `access_expires` >= ".$db->qstr(time()).")
										AND `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
							$access_id = $db->getOne($query);
							if ($access_id) {
								$ENTRADA_USER->setAccessId($access_id);
								$ENTRADA_USER->setClinical(getClinicalFromProxy($ENTRADA_USER->getActiveId()));
							} else {
								$query = "SELECT `id` FROM `".AUTH_DATABASE."`.`user_access`
											WHERE `user_id` = ".$db->qstr($result["assigned_by"])."
											AND `app_id` = ".$db->qstr(AUTH_APP_ID)."
											AND `account_active` = 'true'
											AND (`access_starts` = '0' OR `access_starts` <= ".$db->qstr(time()).")
											AND (`access_expires` = '0' OR `access_expires` >= ".$db->qstr(time()).")";
								$access_id = $db->getOne($query);
								if ($access_id) {
									$ENTRADA_USER->setAccessId($access_id);
									$ENTRADA_USER->setClinical(getClinicalFromProxy($ENTRADA_USER->getActiveId()));
								}
							}
						} else {
							application_log("notice", $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] tried to masquerade as proxy id [".$result["assigned_by"]."], but their permission to this account has expired.");
						}
					} else {
						application_log("notice", $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] tried to masquerade as proxy id [".$result["assigned_by"]."], but their permission to this account has not yet begun.");
					}
				} else {
					application_log("error", $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] tried to masquerade as proxy id [".$result["assigned_by"]."], but they do not have permission_id [".$result["permission_id"]."] does not belong to them. Oooo. Bad news.");
				}
			} else {
				application_log("error", $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] tried to masquerade as proxy id [".$result["assigned_by"]."], but the provided permission_id [".$result["permission_id"]."] does not exist in the database.");
			}
		}

		$_SERVER["QUERY_STRING"] = replace_query(array("mask" => false));
	}

	return true;
}

/**
 * This function will return an array of all groups and roles who have the
 * $module_name registered to them.
 *
 * @param string $module_name
 * @return array
 */
function permissions_by_module($module_name = "") {
	global $ADMINISTRATION;

	$output = array();

	if ((is_array($ADMINISTRATION)) && ($module_name = clean_input($module_name, "alpha"))) {
		foreach ($ADMINISTRATION as $group => $result) {
			foreach ($result as $role => $options) {
				if ((is_array($options["registered"])) && (in_array($module_name, $options["registered"]))) {
					$output[$group][] = $role;
				}
			}
		}
	}

	return $output;
}

/**
 * This function will check to see if we need to update the users module preferences in the
 * database table.
 *
 * @param string $module
 * @param array $preferences
 * @return bool
 */
function preferences_update($module, $original_preferences = array()) {
	global $db, $ENTRADA_USER;
	if(isset($_SESSION[APPLICATION_IDENTIFIER][$module])) {
		if (serialize($_SESSION[APPLICATION_IDENTIFIER][$module]) != serialize($original_preferences)) {
			$query	= "SELECT `preference_id` FROM `".AUTH_DATABASE."`.`user_preferences` WHERE `app_id`=".$db->qstr(AUTH_APP_ID)." AND `proxy_id`=".$db->qstr($ENTRADA_USER->getID())." AND `module`=".$db->qstr($module);
			$result	= $db->GetRow($query);
			if($result) {
				if(!$db->AutoExecute("`".AUTH_DATABASE."`.`user_preferences`", array("preferences" => @serialize($_SESSION[APPLICATION_IDENTIFIER][$module]), "updated" => time()), "UPDATE", "preference_id = ".$db->qstr($result["preference_id"]))) {
					application_log("error", "Unable to update the users database preferences for this module. Database said: ".$db->ErrorMsg());

					return false;
				}
			} else {
				if(!$db->AutoExecute(AUTH_DATABASE.".user_preferences", array("app_id" => AUTH_APP_ID, "proxy_id" => $ENTRADA_USER->getID(), "module" => $module, "preferences" => @serialize($_SESSION[APPLICATION_IDENTIFIER][$module]), "updated" => time()), "INSERT")) {
					application_log("error", "Unable to insert the users database preferences for this module. Database said: ".$db->ErrorMsg());

					return false;
				}
			}
		}
	}

	return true;
}

/**
 * This function updates the preferances for a specified user if needed.
 * @global object $db
 * @param type $module
 * @param type $proxy_id
 * @param type $original_preferences
 * @param type $new_preferences
 * @return boolean
 */
function preferences_update_user($module, $proxy_id, $original_preferences = array(), $new_preferences = array()) {
	global $db;
	if(isset($new_preferences)) {
		if (serialize($new_preferences) != serialize($original_preferences)) {
			$query	= "SELECT `preference_id` FROM `".AUTH_DATABASE."`.`user_preferences` WHERE `app_id`=".$db->qstr(AUTH_APP_ID)." AND `proxy_id`=".$db->qstr($proxy_id)." AND `module`=".$db->qstr($module);
			$result	= $db->GetRow($query);
			if($result) {
				if(!$db->AutoExecute("`".AUTH_DATABASE."`.`user_preferences`", array("preferences" => @serialize($new_preferences), "updated" => time()), "UPDATE", "preference_id = ".$db->qstr($result["preference_id"]))) {
					application_log("error", "Unable to update the users database preferences for this module. Database said: ".$db->ErrorMsg());

					return false;
				}
			} else {
				if(!$db->AutoExecute(AUTH_DATABASE.".user_preferences", array("app_id" => AUTH_APP_ID, "proxy_id" => $proxy_id, "module" => $module, "preferences" => @serialize($new_preferences), "updated" => time()), "INSERT")) {
					application_log("error", "Unable to insert the users database preferences for this module. Database said: ".$db->ErrorMsg());

					return false;
				}
			}
		}
	}

	return true;
}


/**
 * This function handles basic logging for the application. You provide it with the entry type and message
 * it will log it to the appropriate log file. You also have the option of notifying the application
 * administrator of error log entries.
 *
 * @param string $type
 * @param string $message
 * @return bool
 */
function application_log($type, $message) {
	global $AGENT_CONTACTS, $ENTRADA_USER;
    $page_url = false;
    if (isset($_SERVER["SERVER_NAME"]) && isset($_SERVER["REQUEST_URI"])) {
        $page_url = 'http';
        if ((isset($_SERVER["HTTPS"])) && $_SERVER["HTTPS"] == "on") {
            $page_url .= "s";
        }
        $page_url .= "://";
        $page_url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }

	$search		= array("\t", "\r", "\n");
	$log_entry	= date("r", time())."\t".str_replace($search, " ", $message)."\t".((isset($ENTRADA_USER)) && $ENTRADA_USER ? $ENTRADA_USER->getID() : 0)."\t".((isset($page_url)) ? clean_input($page_url, array("nows")) : "")."\t".((isset($_SERVER["REMOTE_ADDR"])) ? str_replace($search, " ", $_SERVER["REMOTE_ADDR"]) : 0)."\t".((isset($_SERVER["HTTP_USER_AGENT"])) ? str_replace($search, " ", $_SERVER["HTTP_USER_AGENT"]) : false)."\n";

	switch($type) {
		case "access" :
			$log_file = "access_log.txt";
			break;
		case "cron" :
			$log_file = "cron_log.txt";
			break;
		case "reminder" :
			$log_file = "reminder_log.txt";
			break;
		case "success" :
			$log_file = "success_log.txt";
			break;
		case "notice" :
			$log_file = "notice_log.txt";
			break;
		case "error" :
			$log_file = "error_log.txt";
			$log_entry .= get_caller_string() . "\n";
			if((defined("NOTIFY_ADMIN_ON_ERROR")) && (NOTIFY_ADMIN_ON_ERROR)) {
				@error_log($log_entry, 1, $AGENT_CONTACTS["administrator"]["email"], "Subject: ".APPLICATION_NAME.": Errorlog Entry\nFrom: \"".$AGENT_CONTACTS["administrator"]["name"]."\" <".$AGENT_CONTACTS["administrator"]["email"].">\n");
			}
			break;
		case "mobile" :
			$log_file = "mobile_log.txt";
			break;
		default :
			$log_file = "default_log.txt";
			break;
	}

	if(@error_log($log_entry, 3, LOG_DIRECTORY.DIRECTORY_SEPARATOR.$log_file)) {
		return true;
	} else {
		return false;
	}
}

/**
 * uses debug_backtrace to get the file and line number of the caller of the function calling this.
 */
function get_caller_string() {
	$bt = debug_backtrace();
	if (isset($bt[1]) && is_array($bt[1]) && isset($bt[1]['file']) && isset($bt[1]['line'])) {
		return " file: " . $bt[1]['file'] . " line: " . $bt[1]['line'];
	}
	return "";
}

/**
 * This function is only around for historical reasons.
 *
 * @param string $type
 * @param string $message
 * @return bool
 */
function system_log_data($type, $message) {
	return application_log($type, $message);
}

/**
 * This function simply counts the number of confirmed reads the specified
 * notice_id has recieved.
 *
 * @param int $notice_id
 * @return int
 */
function count_notice_reads($notice_id = 0) {
	global $db;

	if ($notice_id = (int) $notice_id) {
		$query = "	SELECT COUNT(*) AS `total_reads`
					FROM `statistics`
					WHERE `module` = 'notices'
					AND `action` = 'read'
					AND `action_field` = 'notice_id'
					AND `action_value` = ".$db->qstr($notice_id);
		$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
		if ($result) {
			return (int) $result["total_reads"];
		}
	}

	return 0;
}

/**
 * This function cleans a string with any valid rules that have been provided in the $rules array.
 * Note that $rules can also be a string if you only want to apply a single rule.
 * If no rules are provided, then the string will simply be trimmed using the trim() function.
 * @param string $string
 * @param array $rules
 * @return string
 * @example $variable = clean_input(" 1235\t\t", array("nows", "int")); // $variable will equal an integer value of 1235.
 */
function clean_input($string, $rules = array()) {
	if (is_scalar($rules)) {
		if (trim($rules) != "") {
			$rules = array($rules);
		} else {
			$rules = array();
		}
	}

	if (count($rules) > 0) {
		foreach ($rules as $rule) {
			switch ($rule) {
                case "end_of_day" :
                    $string = strtotime(date("Y-m-d", $string)." 23:59:59");
                    break;
				case "page_url" :		// Acceptable characters for community page urls.
				case "module" :
					$string = preg_replace("/[^a-z0-9_\-]/i", "", $string);
				    break;
				case "url" :			// Allows only a minimal number of characters
					$string = preg_replace(array("/[^a-z0-9_\-\.\/\~\?\&\:\#\=\+]/i", "/(\.)\.+/", "/(\/)\/+/"), "$1", $string);
				    break;
				case "file" :
				case "dir" :			// Allows only a minimal number of characters
					$string = preg_replace(array("/[^a-z0-9_\:\-\.\/]/i", "/(\.)\.+/", "/(\/)\/+/"), "$1", $string);
				    break;
				case "int" :			// Change string to an integer.
					$string =  (int) $string;
				    break;
				case "float" :			// Change string to a float.
					$string = (float) $string;
				    break;
				case "bool" :			// Change string to a boolean.
					$string = (bool) $string;
				    break;
				case "nows" :			// Trim all whitespace anywhere in the string.
					$string = str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B", "&nbsp;"), "", $string);
				    break;
				case "trim" :			// Trim whitespace from ends of string.
					$string = trim($string);
				    break;
				case "trimds" :			// Removes double spaces.
					$string = str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B", "&nbsp;", "\x7f", "\xff", "\x0", "\x1f"), " ", $string);
					$string = html_decode(str_replace("&nbsp;", "", html_encode($string)));
				    break;
				case "nl2br" :
					$string = nl2br($string);
				    break;
				case "underscores" :	// Trim all whitespace anywhere in the string.
					$string = str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B", "&nbsp;"), "_", $string);
				    break;
				case "lower" :			// Change string to all lower case.
				case "lowercase" :
					$string = strtolower($string);
				    break;
				case "upper" :			// Change string to all upper case.
				case "uppercase" :
					$string = strtoupper($string);
				    break;
				case "ucwords" :		// Change string to correct word case.
					$string = ucwords(strtolower($string));
				    break;
				case "boolops" :		// Removed recognized boolean operators.
					$string = str_replace(array("\"", "+", "-", "AND", "OR", "NOT", "(", ")", ",", "-"), "", $string);
				    break;
				case "quotemeta" :		// Quote's meta characters
					$string = quotemeta($string);
				    break;
				case "credentials" :	// Acceptable characters for login credentials.
					$string = preg_replace("/[^a-z0-9_\-\.@]/i", "", $string);
				    break;
				case "alphanumeric" :	// Remove anything that is not alphanumeric.
					$string = preg_replace("/[^a-z0-9]+/i", "", $string);
				    break;
				case "alpha" :			// Remove anything that is not an alpha.
					$string = preg_replace("/[^a-z]+/i", "", $string);
				    break;
				case "numeric" :		// Remove everything but numbers 0 - 9 for when int won't do.
					$string = preg_replace("/[^0-9]+/i", "", $string);
				    break;
				case "name" :			// @todo jellis ?
					$string = preg_replace("/^([a-z]+(\'|-|\.\s|\s)?[a-z]*){1,2}$/i", "", $string);
				    break;
				case "emailcontent" :	// Check for evil tags that could be used to spam.
					$string = str_ireplace(array("content-type:", "bcc:","to:", "cc:"), "", $string);
				    break;
				case "postclean" :		// @todo jellis ?
					$string = preg_replace('/\<br\s*\/?\>/i', "\n", $string);
					$string = str_replace("&nbsp;", " ", $string);
				    break;
				case "utf8_convert" :
					$string = preg_replace_callback("/(&#[ox]?[a-f,A-F,0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $string);
				    break;
				case "html_decode" :
				case "decode" :			// Returns the output of the html_decode() function.
					$string = html_decode($string);
				    break;
				case "html_encode" :
				case "encode" :			// Returns the output of the html_encode() function.
					$string = html_encode($string);
				    break;
				case "htmlspecialchars" : // Returns the output of the htmlspecialchars() function.
				case "specialchars" :
					$string = htmlspecialchars($string, ENT_QUOTES, DEFAULT_CHARSET, false);
				    break;
				case "htmlbrackets" :	// Converts only brackets into entities.
					$string = str_replace(array("<", ">"), array("&lt;", "&gt;"), $string);
				    break;
				case "notags" :			// Strips tags from the string.
				case "nohtml" :
				case "striptags" :
					$string = strip_tags($string);
				    break;
				case "mbconvert":
					$string = mb_convert_encoding($string, DEFAULT_CHARSET, 'auto');
					break;
				case "allowedtags" :	// Cleans and validates HTML, requires HTMLPurifier: http://htmlpurifier.org
				case "nicehtml" :
				case "html" :
					if (!gc_enabled()) {
						gc_enable();
					}
					gc_collect_cycles();

					$html = new HTMLPurifier();

				    $embed_regex = defined("VIDEO_EMBED_REGEX") ? VIDEO_EMBED_REGEX : "www.youtube.com/embed/|player.vimeo.com/video/";

					$config = HTMLPurifier_Config::createDefault();
					$config->set("Cache.SerializerPath", CACHE_DIRECTORY);
					$config->set("Core.Encoding", DEFAULT_CHARSET);
					$config->set("Core.EscapeNonASCIICharacters", true);
                    $config->set("HTML.TidyLevel", "medium");
					$config->set("HTML.SafeIframe", true);
					$config->set("HTML.FlashAllowFullScreen", true);
                    $config->set("URI.SafeIframeRegexp", "%^(http://|https://|//)(" . $embed_regex . ")%");
					$config->set("HTML.SafeObject", true);
					$config->set("Output.FlashCompat", true);
					$config->set("Test.ForceNoIconv", true);
					$config->set("Attr.AllowedFrameTargets", array("_blank", "_self", "_parent", "_top"));
					$config->set("Attr.EnableID", true);
					$config->set("Attr.IDPrefix", "user_");

                    $def = $config->getHTMLDefinition(true);
                    $def->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', array(
                        'src' => 'URI',
                        'type' => 'Text',
                        'width' => 'Length',
                        'height' => 'Length',
                        'poster' => 'URI',
                        'preload' => 'Enum#auto,metadata,none',
                        'controls' => 'Bool',
                    ));
                    $def->addElement('source', 'Block', 'Flow', 'Common', array(
                        'src' => 'URI',
                        'type' => 'Text'
                    ));
                    $def->addAttribute("iframe", "allowfullscreen", "Text");
                    $def->addAttribute("iframe", "webkitallowfullscreen", "Text");
                    $def->addAttribute("iframe", "mozallowfullscreen", "Text");
                    $def->addAttribute("a", "data-toggle", "Text");

					$string = $html->purify($string, $config);

                    if ($string) {
                        // Since all ids on the page are getting the _user prefix, the next block will
                        // add that prefix to all existing anchors in the page.
                        // If the anchor already has the prefix it will not match with the regular expression.
                        $regex = "/href=\"#(?!user_)/";
                        $replace = "href=\"#user_";
                        $string = preg_replace($regex, $replace, $string);
                    }
				    break;
                case "msword" :
                    $string = iconv("UTF-8", "ASCII//TRANSLIT", $string);
                    break;
                case "strtotime" :
                    $string = strtotime($string);
                    break;
                case "strtotimeonly" :
                    $time = strtotime($string);
                    if ($time) {
                        $midnight = strtotime("00:00:00", $time);
                        $string = $time - $midnight;
                    }
                    break;
				case preg_match("/^min:[0-9]+$/", $rule) === 1:
					$rule = explode(":", $rule);
					$min = (int) $rule[1];
					$value = is_int($string) ? $string : strlen($string);

					if ($value < $min) {
						return false;
					}
				    break;
				case preg_match("/^max:[0-9]+$/", $rule) === 1:
					$rule = explode(":", $rule);
					$max = (int) $rule[1];
					$value = is_int($string) ? $string : strlen($string);

					if ($value > $max) {
						return false;
					}
				    break;
				default :				// Unknown rule, log notice.
					application_log("notice", "Unknown clean_input function rule [".$rule."]");
				    break;
			}
		}

		return $string;
	} else {
		return trim($string);
	}
}

/**
 * Function to properly format the success messages for consistency.
 *
 * @param array $success_messages
 * @return string containing the HTML of the message or false if there is no HTML.
 */
function display_success($success_messages = array(), $mobile = false) {
	global $SUCCESS, $SUCCESSSTR;

	$output_html = "";

	if (is_scalar($success_messages)) {
		if (trim($success_messages) != "") {
			$success_messages = array($success_messages);
		} else {
			$success_messages = array();
		}
	}

	if (!$num_success = (int) @count($success_messages)) {
		if ($num_success = (int) @count($SUCCESSSTR)) {
			$success_messages = $SUCCESSSTR;
		}
	}

	if ($num_success) {
		$output_html .= "<div id=\"display-success-box\" class=\"alert alert-block alert-success\">\n";
        $output_html .= (!$mobile ? "   <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>\n" : "");
		$output_html .= "	<ul>\n";
		foreach ($success_messages as $success_message) {
			$output_html .= "	<li>".$success_message."</li>\n";
		}
		$output_html .= "	</ul>\n";
		$output_html .= "</div>\n";
	}

	return (($output_html) ? $output_html : false);
}

/**
 * Function to properly format the generic messages for consistency.
 *
 * @param array $generic_messages
 * @return string containing the HTML of the message or false if there is no HTML.
 */
function display_generic($generic_messages = array(), $mobile = false) {
	global $GENERIC, $GENERICSTR;

	$output_html = "";

	if (is_scalar($generic_messages)) {
		if (trim($generic_messages) != "") {
			$generic_messages = array($generic_messages);
		} else {
			$generic_messages = array();
		}
	}

	if (!$num_generic = (int) @count($generic_messages)) {
		if ($num_generic = (int) @count($GENERICSTR)) {
			$generic_messages = $GENERICSTR;
		}
	}

	if ($num_generic) {
		$output_html .= "<div id=\"display-generic-box\" class=\"alert alert-block alert-generic\">\n";
        $output_html .= (!$mobile ? "   <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>\n" : "");
		$output_html .= "	<ul>\n";
		foreach ($generic_messages as $generic_message) {
			$output_html .= "	<li>".$generic_message."</li>\n";
		}
		$output_html .= "	</ul>\n";
		$output_html .= "</div>\n";
	}

	return (($output_html) ? $output_html : false);
}

/**
 * Function to properly format the error messages for consistency.
 *
 * @param array $notice_messages
 * @return string containing the HTML of the message or false if there is no HTML.
 */
function display_notice($notice_messages = array(), $mobile = false) {
	global $NOTICE, $NOTICESTR;

	$output_html = "";

	if (is_scalar($notice_messages)) {
		if (trim($notice_messages) != "") {
			$notice_messages = array($notice_messages);
		} else {
			$notice_messages = array();
		}
	}

	if (!$num_notices = (int) @count($notice_messages)) {
		if ($num_notices = (int) @count($NOTICESTR)) {
			$notice_messages = $NOTICESTR;
		}
	}

	if ($num_notices) {
		$output_html .= "<div id=\"display-notice-box\" class=\"alert alert-block\">\n";
        $output_html .= (!$mobile ? "   <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>\n" : "");
		$output_html .= "	<ul>\n";
		foreach ($notice_messages as $notice_message) {
			$output_html .= "	<li>".$notice_message."</li>\n";
		}
		$output_html .= "	</ul>\n";
		$output_html .= "</div>\n";
	}

	return (($output_html) ? $output_html : false);
}

/**
 * Function to properly format the error messages for consistency.
 *
 * @param array $error_messages
 * @return string containing the HTML of the message or false if there is no HTML.
 */
function display_error($error_messages = array(), $mobile = false) {
	global $ERROR, $ERRORSTR;

	$output_html = "";

	if (is_scalar($error_messages)) {
		if (trim($error_messages) != "") {
			$error_messages = array($error_messages);
		} else {
			$error_messages = array();
		}
	}

	if (!$num_errors = (int) @count($error_messages)) {
		if ($num_errors = (int) @count($ERRORSTR)) {
			$error_messages = $ERRORSTR;
		}
	}

	if($num_errors) {
		$output_html .= "<div id=\"display-error-box\" class=\"alert alert-block alert-error\">\n";
        $output_html .= (!$mobile ? "   <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>\n" : "");
		$output_html .= "	<ul>\n";
		foreach ($error_messages as $error_message) {
			$output_html .= "	<li>".$error_message."</li>\n";
		}
		$output_html .= "	</ul>\n";
		$output_html .= "</div>\n";
	}

	return (($output_html) ? $output_html : false);
}

/**
 * Simple function to return the gender.
 *
 * @param int $gender
 * @param string $format
 *
 * @return string
 *
 */
function display_gender($gender, $format = "default") {
	switch ($gender) {
		case 2 :
			if ($format == "short") {
				return "M";
			} else {
				return "Male";
			}
		break;
		case 1 :
			if ($format == "short") {
				return "F";
			} else {
				return "Female";
			}
		break;
		default :
		case 0 :
			if ($format == "short") {
				return "U";
			} else {
				return "Unknown";
			}
		break;
	}
}

/**
 * Returns a more human readable friendly filesize.
 *
 * @param int $bytes
 * @return string
 */
function readable_size($bytes) {
	$kb = 1024;				// Kilobyte
	$mb = 1048576;			// Megabyte
	$gb = 1073741824;		// Gigabyte
	$tb = 1099511627776;	// Terabyte

	if($bytes < $kb) {
		return $bytes." b";
	} else if($bytes < $mb) {
			return round($bytes / $kb, 2)." KB";
		} else if($bytes < $gb) {
				return round($bytes / $mb, 2)." MB";
			} else if($bytes < $tb) {
					return round($bytes / $gb, 2)." GB";
				} else {
					return round($bytes / $tb, 2)." TB";
				}
}

/**
 * Function will return a properly formatted filename.
 *
 * @param string $filename
 * @return string
 */
function useable_filename($filename) {
	return strtolower(preg_replace(array("/(\.)\.+/", "/(\_)\_+/"), "$1", preg_replace(array("/[^a-z0-9_\-\.]/i"), "_", $filename)));
}

/**
 * Returns a string with a maximum character length of the requested value.
 *
 * @param string $string
 * @param int $character_limit
 * @param bool $show_acronym
 * @param bool $encode_string
 * @return string
 */
function limit_chars($string = "", $character_limit = 0, $show_acronym = false, $encode_string = true) {
	if(($string = trim($string)) && ($character_limit = (int) $character_limit)) {
		if(strlen($string) > $character_limit) {
			return substr($string, 0, ($character_limit - 4))." ".(($show_acronym) ? "<acronym title=\"".(($encode_string) ? html_encode($string) : $string)."\" style=\"cursor: pointer\">...</acronym>" : "...");
		}
	}

	return $string;
}

/**
 * This function will check the provided event_id for event resources, such as files and links
 * then return the total number of attachments.
 *
 * @param integer $event_id
 * @param string $side
 * @return integer
 */
function attachment_check($event_id = 0, $side = "public") {
	global $db;

	$total_files	= 0;
	$total_links	= 0;
	$total_quizzes	= 0;
	$grand_total	= 0;

	if ($event_id = (int) $event_id) {
		$query	= "SELECT COUNT(*) AS `total_files` FROM `event_files` WHERE `event_id` = ".$db->qstr($event_id).(($side == "public") ? " AND (`release_date` = '0' OR `release_date` <= '".time()."') AND (`release_until` = '0' OR `release_until` >= '".time()."')" : "");
		$result	= ((USE_CACHE) ? $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query) : $db->GetRow($query));
		if ($result) {
			$total_files = $result["total_files"];
			$grand_total += $total_files;
		}

		$query	= "SELECT COUNT(*) AS `total_links` FROM `event_links` WHERE `event_id` = ".$db->qstr($event_id).(($side == "public") ? " AND (`release_date` = '0' OR `release_date` <= '".time()."') AND (`release_until` = '0' OR `release_until` >= '".time()."')" : "");
		$result	= ((USE_CACHE) ? $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query) : $db->GetRow($query));
		if ($result) {
			$total_links = $result["total_links"];
			$grand_total += $total_links;
		}

		$query	= "SELECT COUNT(*) AS `total_quizzes` FROM `attached_quizzes` WHERE `content_type` = 'event' AND `content_id` = ".$db->qstr($event_id).(($side == "public") ? " AND (`release_date` = '0' OR `release_date` <= '".time()."') AND (`release_until` = '0' OR `release_until` >= '".time()."')" : "");
		$result	= ((USE_CACHE) ? $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query) : $db->GetRow($query));
		if ($result) {
			$total_quizzes = $result["total_quizzes"];
			$grand_total += $total_quizzes;
		}
	}

	return $grand_total;
}

/**
 * This function is used as a micro-timer for the length of time for pages to execute.
 *
 * @return float
 */
function getmicrotime() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float) $usec + (float) $sec);
}

/**
 * This function returns an array of the hierarchal path to the provided
 * course_id.
 *
 * @example Semester 1 > Course Name
 *
 * @param int $course_id
 * @param bool $return_course_code
 * @param bool $return_course_link
 * @return array
 */
function curriculum_hierarchy($course_id = 0, $return_course_code = false, $return_course_link = false) {
	global $db;

	if ($course_id = (int) $course_id) {
		$output	= array();
		$count	= 0;

		$query	= "	SELECT * FROM `courses`
					WHERE `course_id` = ".$db->qstr($course_id)."
					AND `course_active` = '1'";
		$result	= $db->GetRow($query);

		if ($result) {
			$output[] = ($return_course_link ? "<a href=\"".ENTRADA_RELATIVE."/courses?id=".$course_id."\">" : "").(($return_course_code) ? $result["course_code"].": " : "").$result["course_name"].($return_course_link ? "</a>" : "");

			$query	= "SELECT * FROM `curriculum_lu_types` WHERE `curriculum_type_id` = ".$db->qstr($result["curriculum_type_id"]);
			$result	= $db->GetRow($query);

			if ($result) {
				$output[] = $result["curriculum_type_name"];
			}

			return array_reverse($output);
		}
	}

	return false;
}

function generate_password($len = 5) {
	$pass	= "";
	$lchar	= 0;
	$char	= 0;
	for($i = 0; $i < $len; $i++) {
		while($char == $lchar) {
			$char = rand(48, 109);
			if($char > 57) $char += 7;
			if($char > 90) $char += 6;
		}
		$pass .= chr($char);
		$lchar = $char;
	}
	return strtolower($pass);
}

/**
 * Determine wether or not the proxy server is required based on the start_block and end_block provided.
 *
 * @param string $start_block
 * @param unknown_type $end_block
 * @return unknown
 */
function require_proxy($start_block = "", $end_block = "") {
	if((isset($_SERVER["REMOTE_ADDR"])) && ($_SERVER["REMOTE_ADDR"] != "")) {
		if((ip2long($_SERVER["REMOTE_ADDR"]) >= ip2long($start_block)) && (ip2long($_SERVER["REMOTE_ADDR"]) <= ip2long($end_block))) {
			return false;
		} else {
			return true;
		}
	}

	return false;
}

/**
 * This function determines whether or not the remote address is in the
 * exceptions list (as defined in settings.inc.php).
 *
 * Note: Combine require_proxy and check_proxy functions.
 *
 * @param string $location
 * @return bool
 */
function check_proxy($location = "default") {
	global $PROXY_URLS, $PROXY_SUBNETS;

	if(!is_array($PROXY_SUBNETS[$location])) {
		$location = "default";
	}

	if((isset($PROXY_SUBNETS[$location]["exceptions"])) && (in_array($_SERVER["REMOTE_ADDR"], $PROXY_SUBNETS[$location]["exceptions"]))) {
		return true;
	} elseif(require_proxy($PROXY_SUBNETS[$location]["start"], $PROXY_SUBNETS[$location]["end"])) {
		return true;
	} else {
		return false;
	}
}

/**
 * Function is responsible for updating the last updated information.
 *
 * @param string $type
 * @param int $event_id
 * @return bool
 */
function last_updated($type = "event", $event_id = 0) {
	global $db, $ENTRADA_USER;

	if($event_id = (int) $event_id) {
		switch($type) {
			case "lecture" :
			case "event" :
				if($db->AutoExecute("events", array("updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "UPDATE", "event_id = ".$db->qstr($event_id))) {
					return true;
				}
				break;
			default :
				continue;
				break;
		}
	}

	return false;
}

/**
 * This function will fetch the feeds out of the language customization file,
 * and return the feeds that are applicable to the users group.
 *
 * @global <type> $translate
 * @return <type> array
 */
function dashboard_fetch_feeds($default = false) {
	global $translate, $ENTRADA_USER;

	if (!$default && isset($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["feeds"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["feeds"])) {
		return $_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["feeds"];
	} else {
		$feeds = $translate->_("public_dashboard_feeds");
		$role = $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"];
		$group = $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"];

		if (is_array($feeds[$group]) && is_array($feeds[$role]) && $group != $role) {
			$feeds = array_merge($feeds[$role], $feeds[$group], $feeds["global"]);
		} elseif (is_array($feeds[$role])) {
			$feeds = array_merge($feeds[$role], $feeds["global"]);
		} elseif (is_array($feeds[$group])) {
			$feeds = array_merge($feeds["global"], $feeds[$group]);
		} else {
			$feeds = $feeds["global"];
		}
		return $feeds;
	}
}

/**
 * This function will fetch the links out of the language customization file,
 * and return the links that are applicable to the users group.
 *
 * @global <type> $translate
 * @return <type> array
 */
function dashboard_fetch_links() {
	global $translate, $ENTRADA_USER;

	$links = $translate->_("public_dashboard_links");
	$group = $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"];

	if (is_array($links[$group])) {
		return array_merge($links["global"], $links[$group]);
	} else {
		return $links["global"];
	}
}

function unixstamp_to_iso8601($t) {
	$tz	= date("Z", $t)/60;
	$tm	= $tz % 60; $tz=$tz/60;
	if ($tz<0) {
		$ts="-";
		$tz=-$tz;
	} else {
		$ts="+";
	}

	$tz	= substr("0" . $tz, -2);
	$tm	= substr("0" . $tm, -2);
	return date("Y-m-d\TH:i:s", $t)."${ts}${tz}:${tm}";
}

/**
 * Function responsible for returning the number of times a poll has
 * been responded to.
 *
 * @param int $poll_id
 * @return int
 */
function poll_responses($poll_id = 0) {
	global $db;

	if($poll_id = (int) $poll_id) {
		$query	= "SELECT COUNT(*) AS `responses` FROM `poll_results` WHERE `poll_id`=".$db->qstr($poll_id);
		$result	= $db->GetRow($query);
		if($result) {
			return $result["responses"];
		}
	}
	return 0;
}

/**
 * Function used to output the poll current poll responses in hidden input elements
 *
 * @param array $responses
 * @return string
 */
function poll_responses_in_form($responses) {
	$output = "";
	if (isset($responses) && is_array($responses)) {
		foreach ($responses as $key => $response) {
			$output .= "<input id=\"response_".((int) $key)."\" name=\"response[".((int) $key)."]\" value=\"".$response."\" type=\"hidden\" />";
		}
	}
	return $output;
}

/**
 * Function responsible for displaying the number of times a response
 * was selected.
 *
 * @param int $poll_id
 * @param int $answer_id
 * @return int
 */
function poll_answer_responses($poll_id = 0, $answer_id = 0) {
	global $db;

	if(($poll_id = (int) $poll_id) && ($answer_id = (int) $answer_id)) {
		$query = "	SELECT COUNT(*) AS `responses`
					FROM `poll_results`
					WHERE `poll_id` = ".$db->qstr($poll_id)."
					AND `answer_id` = ".$db->qstr($answer_id);
		$result	= $db->GetRow($query);
		if($result) {
			return $result["responses"];
		}
	}
	return 0;
}

/**
 * Function responsible for checking to see whether or not the proxy_id
 * is eligible to take the poll.
 *
 * @param int $poll_id
 * @return bool
 */
function poll_prevote_check($poll_id = 0) {
	global $db, $ENTRADA_USER;

	if($poll_id = (int) $poll_id) {
		$query = "	SELECT *
					FROM `poll_results`
					WHERE `poll_id` = ".$db->qstr($poll_id)."
					AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
		$result	= $db->GetRow($query);
		if($result) {
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}
}

/**
 * Function responsible for actually displaying an uncompleted poll.
 *
 * @param int $poll_id
 * @return string
 */
function poll_display($poll_id = 0) {
	global $db;

	$output = "";

	if($poll_id = (int) $poll_id ) {
		$query		= "SELECT `poll_question` FROM `poll_questions` WHERE `poll_id`=".$db->qstr($poll_id);
		$poll_question	= $db->GetRow($query);

		if($poll_question) {
			if(!poll_prevote_check($poll_id)) {
				$output = poll_results($poll_id);
			} else {
				$query		= "SELECT `answer_id`, `answer_text` FROM `poll_answers` WHERE `poll_id`=".$db->qstr($poll_id)." ORDER BY `answer_order` ASC";
				$poll_answers	= $db->GetAll($query);
				$total_votes	= poll_responses($poll_id);

				$output .= "<div id=\"poll\">\n";
				$output .= "<form action=\"".ENTRADA_URL."/serve-polls.php?pollSend&nojs\" method=\"post\" id=\"pollForm\" onsubmit=\"return ReadVote();\">\n";
				$output .= html_encode($poll_question["poll_question"]);
				$output .= "	<div style=\"padding-top: 5px; padding-left: 3px; padding-bottom: 5px\">\n";
				foreach ($poll_answers as $poll_answer) {
					if(trim($poll_answer["answer_text"]) != "") {
						$output .=  "<label for=\"choice_".$poll_answer["answer_id"]."\" style=\"font-size: 11px\">\n";
						$output .=  "	<input type=\"radio\" id=\"choice_".$poll_answer["answer_id"]."\" value=\"".$poll_answer["answer_id"]."\" name=\"poll_answer_id\" />\n";
						$output .=  	html_encode($poll_answer["answer_text"]);
						$output .=  "</label><br />\n";
					}
				}
				$output .= "	</div>\n";
				$output .= "	<input type=\"hidden\" id=\"poll_id\" name=\"poll_id\" value=\"".$poll_id."\" />\n";
				$output .= "	<div style=\"text-align: right\"><input type=\"submit\" class=\"btn btn-primary\" name=\"vote\" value=\"Vote\" /></div>\n";
				$output .= "</form>\n";
				$output .= "</div>\n";
			}
		}
	}
	return $output;
}

/**
 * Function responsible for displaying the results of a poll.
 *
 * @param int $poll_id
 * @return string
 */
function poll_results($poll_id = 0) {
	global $db;

	$output = "";
    $poll_id = (int) $poll_id;

    if ($poll_id) {
		$query = "SELECT `poll_question` FROM `poll_questions` WHERE `poll_id` = ".$db->qstr($poll_id);
		$poll_question = $db->GetRow($query);
		if ($poll_question) {
			$answers = array();
			$winner = 0;
			$highest = 0;

			$total_votes = poll_responses($poll_id);

            $query = "SELECT `answer_id`, `answer_text`, `answer_order` FROM `poll_answers` WHERE `poll_id`=".$db->qstr($poll_id)." ORDER BY `answer_order` ASC";
			$poll_answers = $db->GetAll($query);
            if ($poll_answers) {
                foreach ($poll_answers as $poll_answer) {
                    if (trim($poll_answer["answer_text"]) != "") {
                        $answers[$poll_answer["answer_order"]]["answer_id"]	= $poll_answer["answer_id"];
                        $answers[$poll_answer["answer_order"]]["answer_text"] = $poll_answer["answer_text"];
                        $answers[$poll_answer["answer_order"]]["votes"] = poll_answer_responses($poll_id, $poll_answer["answer_id"]);

                        if ($answers[$poll_answer["answer_order"]]["votes"] > $highest) {
                            $winner	= $answers[$poll_answer["answer_order"]]["answer_id"];
                            $highest = $answers[$poll_answer["answer_order"]]["votes"];
                        }
                    }
                }
            }

			$output .= "<div class=\"poll\">\n";
			$output .= "    <div class=\"poll-question\">".html_encode($poll_question["poll_question"])."</div>\n";

            foreach ($answers as $answer) {
				$percent = round($answer["votes"] / ($total_votes + 0.0001) * 100);

				$output .= html_encode($answer["answer_text"]);
                $output .= "<div class=\"poll-response row-fluid\">\n";
                $output .= "    <div class=\"span10\">\n";
				$output .= "        <div class=\"progress\">\n";
				$output .= "            <div class=\"bar\" style=\"width: ".((!$percent) ? "1" : $percent)."%\"></div>";
				$output .= "        </div>\n";
				$output .= "    </div>\n";
                $output .= "    <div class=\"span2\">\n";
				$output .=          $percent."%\n";
				$output .= "    </div>\n";
				$output .= "</div>\n";
			}

			$output .= "    <div class=\"poll-votes\">\n";
            $output .= "        <strong>Total Votes:</strong> ".$total_votes."\n";
            $output .= "    </div>\n";
			$output .= "</div>\n";
		}
	}

	return $output;
}

/**
 * Wrapper function for html_entities.
 *
 * @param string $string
 * @param bool $double_encode
 * @return string
 */
function html_encode($string = "", $double_encode = true) {
    if (!defined("DEFAULT_CHARSET")) {
        define("DEFAULT_CHARSET", "UTF-8");
    }

    return htmlentities($string, ENT_QUOTES, DEFAULT_CHARSET, $double_encode);
}

/**
 * Wrapper for PHP's html_entities_decode function.
 *
 * @param string $string
 * @return string
 */
function html_decode($string) {
	return html_entity_decode($string, ENT_QUOTES, DEFAULT_CHARSET);
}

/**
 * Wrapper function for htmlspecialchars. This is called xml_encode because unlike
 * HTML, XML only supports five named character entities.
 *
 * @param string $string
 * @return string
 */
function xml_encode($string) {
	return htmlspecialchars($string, ENT_QUOTES, DEFAULT_CHARSET);
}

/**
 * Wrapper for PHP's html_entities_decode function.
 *
 * @param string $string
 * @return string
 */
function xml_decode($string) {
	return html_entity_decode($string, ENT_QUOTES, DEFAULT_CHARSET);
}

/**
 * This function is used to generate a calendar with an optional time selector in a form.
 *
 * @param string $fieldname
 * @param string $display_name
 * @param bool $required
 * @param int $current_time
 * @param bool $use_times
 * @param bool $add_line_break
 * @param bool $auto_end_date
 * @param bool $disabled
 * @param bool $optional Indicates whether this date/time field is optional. Checkbox if true, date/time fields only if false. default: true
 * @return string
 */
function generate_calendar($fieldname, $display_name = "", $required = false, $current_time = 0, $use_times = true, $add_line_break = false, $auto_end_date = false, $disabled = false, $optional=true) {
    global $HEAD, $ONLOAD;

    if (!$display_name) {
        $display_name = ucwords(strtolower($fieldname));
    }

    $output = "";

    if ($use_times) {
        $ONLOAD[] = "updateTime('".$fieldname."')";
    }

    if ($optional) {
        $ONLOAD[] = "dateLock('".$fieldname."')";
    }

    if ($current_time) {
        $time = 1;
        $time_date = date("Y-m-d", $current_time);
        $time_hour = (int) date("G", $current_time);
        $time_min = (int) date("i", $current_time);
    } else {
        $time = (($required) ? 1 : 0);
        $time_date = "";
        $time_hour = 0;
        $time_min = 0;
    }

    if ($auto_end_date) {
        $readonly = "disabled=\"disabled\"";
    } else {
        $readonly = "";
    }

    $output .= "<tr>\n";
    $output .= "	<td>";
    if ($optional) {
        $output .= "    <input type=\"checkbox\" name=\"".$fieldname."\" id=\"".$fieldname."\" value=\"1\"".(($time) ? " checked=\"checked\"" : "").(($required) ? " readonly=\"readonly\"" : "")." onclick=\"".(($required) ? "this.checked = true" : "dateLock('".$fieldname."')")."\" />";
    } else {
        $output .= "    &nbsp;";
    }
    $output .= "	</td>";
    $output .= "	<td>";
    $output .= "		<label id=\"".$fieldname."_text\" for=\"".$fieldname."\" class=\"".($required ? "form-required" : "form-nrequired")."\">".html_encode($display_name)."</label>";
    $output .= "	</td>";
    $output .= "	<td id=\"".$fieldname."_row\">\n";
    $output .= "        <div class=\"input-append\">";
    $output .= "		    <input type=\"text\" class=\"input-small\" name=\"".$fieldname."_date\" id=\"".$fieldname."_date\" value=\"".$time_date."\" $readonly autocomplete=\"off\" ".(!$disabled ? "onfocus=\"showCalendar('', this, this, '', '".$fieldname."_date', 0, 20, 1)\"" : "")." style=\"padding-left: 10px\" />&nbsp;";

    if (!$disabled) {
        $output .= "	    <a class=\"btn\" href=\"javascript: showCalendar('', document.getElementById('".$fieldname."_date'), document.getElementById('".$fieldname."_date'), '', '".$fieldname."_date', 0, 20, 1)\" title=\"Show Calendar\" onclick=\"if (!document.getElementById('".$fieldname."').checked) { return false; }\"><i class=\"icon-calendar\"></i></a>";
    }
    $output .= "        </div>";

    if ($use_times) {
        $output .= "	&nbsp;".(((bool) $add_line_break) ? "<br />" : "");
        $output .= "	<select class=\"input-mini\" name=\"".$fieldname."_hour\" id=\"".$fieldname."_hour\" onchange=\"updateTime('".$fieldname."')\">\n";
        foreach (range(0, 23) as $hour) {
            $output .= "	<option value=\"".(($hour < 10) ? "0" : "").$hour."\"".(($hour == $time_hour) ? " selected=\"selected\"" : "").">".(($hour < 10) ? "0" : "").$hour."</option>\n";
        }

        $output .= "	</select>\n";
        $output .= "	:";
        $output .= "	<select class=\"input-mini\" name=\"".$fieldname."_min\" id=\"".$fieldname."_min\" onchange=\"updateTime('".$fieldname."')\">\n";
        foreach (range(0, 59) as $minute) {
            $output .= "	<option value=\"".(($minute < 10) ? "0" : "").$minute."\"".(($minute == $time_min) ? " selected=\"selected\"" : "").">".(($minute < 10) ? "0" : "").$minute."</option>\n";
        }
        $output .= "	</select>\n";
        $output .= "	<span class=\"time-wrapper\">&nbsp;( <span class=\"content-small\" id=\"".$fieldname."_display\"></span> )</span>\n";
    }

    if($auto_end_date) {
        $output .= "<div id=\"auto_end_date\" class=\"content-small\" style=\"display: none\"></div>";
    }

    $output .= "	</td>\n";
    $output .= "</tr>\n";

    return $output;
}

/**
 * This function is used to generate the standard start / finish calendars
 * within forms.
 *
 * @param string $fieldname
 * @param string $display_name
 * @param bool $show_start
 * @param int $current_start
 * @param bool $show_finish
 * @param int $current_finish
 * @return string
 */
function generate_calendars($fieldname, $display_name = "", $show_start = false, $start_required = false, $current_start = 0, $show_finish = false, $finish_required = false, $current_finish = 0, $use_times = true, $add_line_break = false, $display_name_start_suffix = " Start", $display_name_finish_suffix = " Finish") {
	global $HEAD, $ONLOAD;

	if(!$display_name) {
		$display_name = ucwords(strtolower($fieldname));
	}

	$output = "";
	if($show_start) {
		$output .= generate_calendar($fieldname."_start", $display_name.$display_name_start_suffix, $start_required, $current_start, $use_times, $add_line_break);
	}

	if($show_finish) {
		$output .= generate_calendar($fieldname."_finish", $display_name.$display_name_finish_suffix, $finish_required, $current_finish, $use_times, $add_line_break);
	}

	return $output;
}

/**
 * Function will validate the calendar that is generated by generate_calendars().
 *
 * @param string $fieldname
 * @param int $require_start
 * @param int $require_finish
 * @return array
 */
function validate_calendars($fieldname, $require_start = true, $require_finish = true, $use_times = true) {
	global $ERROR, $ERRORSTR;

	$timestamp_start	= 0;
	$timestamp_finish	= 0;

	if(($require_start) && ((!isset($_POST[$fieldname."_start"])) || (!$_POST[$fieldname."_start_date"]))) {
		$ERROR++;
		$ERRORSTR[] = "You must select a start date for the ".$fieldname." calendar entry.";
	} elseif(isset($_POST[$fieldname."_start"]) && $_POST[$fieldname."_start"] == "1") {
		if((!isset($_POST[$fieldname."_start_date"])) || (!trim($_POST[$fieldname."_start_date"]))) {
			$ERROR++;
			$ERRORSTR[] = "You have checked <strong>".ucwords(strtolower($fieldname))." Start</strong> but not selected a calendar date.";
		} else {
			if(($use_times) && ((!isset($_POST[$fieldname."_start_hour"])))) {
				$ERROR++;
				$ERRORSTR[] = "You have checked <strong>".ucwords(strtolower($fieldname))." Start</strong> but not selected an hour of the day.";
			} else {
				if(($use_times) && ((!isset($_POST[$fieldname."_start_min"])))) {
					$ERROR++;
					$ERRORSTR[] = "You have checked <strong>".ucwords(strtolower($fieldname))." Start</strong> but not selected a minute of the hour.";
				} else {
					$pieces	= explode("-", $_POST[$fieldname."_start_date"]);
					$hour	= (($use_times) ? (int) trim($_POST[$fieldname."_start_hour"]) : 0);
					$minute	= (($use_times) ? (int) trim($_POST[$fieldname."_start_min"]) : 0);
					$second	= 0;
					$month	= (int) trim($pieces[1]);
					$day	= (int) trim($pieces[2]);
					$year	= (int) trim($pieces[0]);

                    if (checkdate($month, $day, $year)) {
                        $timestamp_start = mktime($hour, $minute, $second, $month, $day, $year);
                    } else {
                        $ERROR++;
                        $ERRORSTR[] = "Invalid format for calendar date.";
                    }
				}
			}
		}
	}

	if(($require_finish) && ((!isset($_POST[$fieldname."_finish"])) || (!$_POST[$fieldname."_finish_date"]))) {
		$ERROR++;
		$ERRORSTR[] = "You must select a finish date for the ".$fieldname." calendar entry.";
	} elseif(isset($_POST[$fieldname."_finish"]) && $_POST[$fieldname."_finish"] == "1") {
		if((!isset($_POST[$fieldname."_finish_date"])) || (!trim($_POST[$fieldname."_finish_date"]))) {
			$ERROR++;
			$ERRORSTR[] = "You have checked <strong>".ucwords(strtolower($fieldname))." Finish</strong> but not selected a calendar date.";
		} else {
			if(($use_times) && ((!isset($_POST[$fieldname."_finish_hour"])))) {
				$ERROR++;
				$ERRORSTR[] = "You have checked <strong>".ucwords(strtolower($fieldname))." Finish</strong> but not selected an hour of the day.";
			} else {
				if(($use_times) && ((!isset($_POST[$fieldname."_finish_min"])))) {
					$ERROR++;
					$ERRORSTR[] = "You have checked <strong>".ucwords(strtolower($fieldname))." Finish</strong> but not selected a minute of the hour.";
				} else {
					$pieces	= explode("-", trim($_POST[$fieldname."_finish_date"]));
					$hour	= (($use_times) ? (int) trim($_POST[$fieldname."_finish_hour"]) : 23);
					$minute	= (($use_times) ? (int) trim($_POST[$fieldname."_finish_min"]) : 59);
					$second	= ((($use_times) && ((int) trim($_POST[$fieldname."_finish_min"]))) ? 59 : 0);
					$month	= (int) trim($pieces[1]);
					$day	= (int) trim($pieces[2]);
					$year	= (int) trim($pieces[0]);

                    if (checkdate($month, $day, $year)) {
                        $timestamp_finish = mktime($hour, $minute, $second, $month, $day, $year);
                    } else {
                        $ERROR++;
                        $ERRORSTR[] = "Invalid format for calendar date.";
                    }
				}
			}
		}
	}

	if(($timestamp_start) && ($timestamp_finish) && ($timestamp_finish < $timestamp_start)) {
		$ERROR++;
		$ERRORSTR[] = "The <strong>".ucwords(strtolower($fieldname))." Finish</strong> date &amp; time you have selected is before the <strong>".ucwords(strtolower($fieldname))." Start</strong> date &amp; time you have selected.";
	}

	return array("start" => $timestamp_start, "finish" => $timestamp_finish);
}

/**
 * Function will validate the calendar that is generated by generate_calendar().
 *
 * @param string $fieldname
 * @param bool $use_times
 * @return int $timestamp
 */
function validate_calendar($label, $fieldname, $use_times = true, $required = true) {
	global $ERROR, $ERRORSTR;

	$timestamp_start	= 0;
	$timestamp_finish	= 0;

	if((!isset($_POST[$fieldname."_date"])) || (!trim($_POST[$fieldname."_date"]))) {
		if ($required) {
			add_error("<strong>".$label."</strong> date not entered.");
		} else {
			return;
		}
	} elseif (!checkDateFormat($_POST[$fieldname."_date"])) {
		add_error("Invalid format for <strong>".$label."</strong> date.");
	} else {
		if(($use_times) && ((!isset($_POST[$fieldname."_hour"])))) {
			add_error("<strong>".$label."</strong> hour not entered.");
		} else {
			if(($use_times) && ((!isset($_POST[$fieldname."_min"])))) {
				add_error("<strong>".$label."</strong> minute not entered.");
			} else {
				$pieces	= explode("-", $_POST[$fieldname."_date"]);
				$hour	= (($use_times) ? (int) trim($_POST[$fieldname."_hour"]) : 0);
				$minute	= (($use_times) ? (int) trim($_POST[$fieldname."_min"]) : 0);
				$second	= 0;
				$month	= (int) trim($pieces[1]);
				$day	= (int) trim($pieces[2]);
				$year	= (int) trim($pieces[0]);

				$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
			}
		}
	}

	return $timestamp;
}

function generate_organisation_select() {
	global $db, $MODULE, $organisations_list, $ENTRADA_ACL;
	$return = '';
	$return .= '<tr>
				<td style="vertical-align: top;"><input id="organisation_checkbox" type="checkbox" disabled="disabled" checked="checked"></td>
				<td style="vertical-align: top; padding-top: 4px;"><label for="organisation_id" class="form-required">Organisation</label></td>
				<td style="vertical-align: top;">
					<select id="organisation_id" name="organisation_id" style="width: 177px">';
	if(!isset($ORGANISATION_LIST)) {
		$query		= "SELECT `organisation_id`, `organisation_title` FROM `".AUTH_DATABASE."`.`organisations`";
		$results	= $db->GetAll($query);
	} else {
		$results = $ORGANISATION_LIST;
	}

	$all_organisations = false;
	if($results) {
		$all_organisations = true;
		foreach($results as $result) {
			if($ENTRADA_ACL->amIAllowed('resourceorganisation'.$result["organisation_id"], 'read')) {
				$return .= "<option value=\"".(int) $result["organisation_id"]."\"".(((isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"])) && ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] == $result["organisation_id"])) ? " selected=\"selected\"" : "").">".html_encode($result["organisation_title"])."</option>\n";
			} else {
				$all_organisations = false;
			}
		}
	}

	if($all_organisations) {
		$return .= '<option value="-1"'.(isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"]) && ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] == -1) ? " selected=\"selected\"" : "").'>All organisations</option>';
	}

	$return .= '
					</select>
				</td>
			</tr>';

	return $return;
}
/**
 * Works like PHP function strip_tags, but it only removes selected tags.
 * @example strip_selected_tags('<b>Person:</b> <strong>Salavert</strong>', 'strong') => <b>Person:</b> Salavert
 */
function strip_selected_tags($string, $tags = array()) {
	$args	= func_get_args();
	$string	= array_shift($args);
	$tags	= ((func_num_args() > 2) ? array_diff($args, array($string)) : (array) $tags);

	foreach ($tags as $tag) {
		while(preg_match_all("/<".$tag."[^>]*>(.*)<\/".$tag.">/iU", $string, $found)) {
			$string = str_replace($found[0], $found[1], $string);
		}
	}

	return $string;
}

/**
 * Builds the description of the learning event from the Curriculum Search tool.
 *
 * @param string $page_content
 * @param string $search_query
 * @return unknown
 */
function search_description($needle = "", $haystack = "") {
	$search_term = clean_input($needle, array("notags", "boolops", "quotemeta", "trim"));
	$haystack = clean_input($haystack, array("notags", "trimds", "trim"));
	$haystack = preg_replace("/(".$search_term.")/i", "<span class=\"highlight\">\\1</span>", $haystack);

	$wordpos = strpos(strtolower($haystack), strtolower($search_term));
	$halfside = intval($wordpos - 500 / 2 - strlen($search_term));

	if ($wordpos && $halfside > 0) {
		return "..." . substr($haystack, $halfside, 275);
	} else {
		return substr($haystack, 0, 275);
	}
}

/**
 * Function is responsible for adding statistics to the database.
 *
 * @param string $module_name
 * @param string $action
 * @param string $action_field
 * @param string $action_value
 * @return bool
 */
function add_statistic($module_name = "", $action = "", $action_field = "", $action_value = "", $proxy_id = 0) {
	global $MODULE, $db, $ENTRADA_USER;

	if(!$module_name) {
		if(!$MODULE) {
			$module_name	= "unknown";
		} else {
			$module_name	= $MODULE;
		}
	}

	if (((int) $proxy_id == 0) && isset($ENTRADA_USER) && $ENTRADA_USER && is_object($ENTRADA_USER) && method_exists($ENTRADA_USER, "getID")) {
		$proxy_id = (int) $ENTRADA_USER->getID();
	}

	$stat					= array();
	$stat["proxy_id"]		= $proxy_id;
	$stat["timestamp"]		= time();
	$stat["module"]			= $module_name;
	$stat["action"]			= $action;
	$stat["action_field"]	= $action_field;
	$stat["action_value"]	= $action_value;
	$stat["prune_after"]	= mktime(0, 0, 0, 8, 15, (date("Y", time()) + 1));

	if(!$db->AutoExecute("statistics", $stat, "INSERT")) {
		application_log("error", "Unable to add entry to statistics table. Database said: ".$db->ErrorMsg());

		return false;
	} else {
		return true;
	}
}

/**
 * Function checks to ensure the e-mail address is valid.
 *
 * @param string $address
 * @return bool
 */
function valid_address($address = "", $mode = 0) {
	switch((int) $mode) {
		case 2 :	// Strict
			$regex = "/^([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i";
		break;
		case 1 :	// Promiscuous
			$regex = "/^([*+!.&#$|\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i";
		break;
		default :	// Recommended
			$regex = "/^([*+!.&#$|0-9a-z^_=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i";
		break;
	}

	if(preg_match($regex, trim($address))) {
		return true;
	} else {
		return false;
	}
}

/**
 * Function adds the appropriate fade actions to the online event.
 *
 * @param string $direction
 * @param string $element_id
 * @param int $start_opacity
 * @param int $fade_timeout
 */
function fade_element($direction = "out", $element_id, $start_opacity = 100, $fade_timeout = 0) {
	global $ONLOAD;

	if(!$fade_timeout) {
		$fade_timeout = 6000;
	}

	$ONLOAD[] = "window.setTimeout('Effect.".(($direction != "out") ? "Appear" : "Fade")."(\'".addslashes($element_id)."\')', ".(int) $fade_timeout.")";

	return;
}

/**
 * The nl2bar function doesn't remove the \n, it merely adds <br /> to the front of it.
 * Well, I need it removed somtimes.
 *
 * @param string $text
 * @return string
 */
function nl2br_replace($text) {
	return str_replace(array("\r", "\n"), array("", "<br />"), $text);
}

/**
 * I also need to convert <br>'s back to \n's (since I removed them already).
 *
 * @param string $text
 * @return string
 */
function br2nl_replace($text) {
	return  preg_replace("/<br\\\\s*?\\/??>/i", "\\n", $text);
}

/**
 * This is ADOdb's function that is called after a logout has occurred.
 * I can't think of any current uses for it; however, it might be useful
 * in the future.
 *
 * @param string $expireref
 * @param string $sesskey
 */
function NotifyFn($expireref, $sesskey) {
	global $ADODB_SESS_CONN;
}

/**
 * This silly little function returns the feedback URL's $_GET["enc"] variable.
 *
 * @return unknown
 */
function feedback_enc() {
	return base64_encode(serialize(array("url" => $_SERVER["REQUEST_URI"])));
}

/**
 * Any time this function is called, the user will be required to authenticate using
 * HTTP Authentication.
 *
 */
function http_authenticate() {
	header("WWW-Authenticate: Basic realm=\"".APPLICATION_NAME."\"");
	header("HTTP/1.0 401 Unauthorized");
	echo "You must enter a valid username and password to access this resource.\n";
	exit;
}

/**
 * Basic function to create a help icon.
 *
 * @todo Actually make this work, not DomTT but something similar.
 * @param string $help_title
 * @param string $help_content
 * @return string
 */
function help_create_button($help_title = "", $help_content = "") {
	$output = "<img src=\"".ENTRADA_URL."/images/btn_help.gif\" width=\"16\" height=\"16\" alt=\"Help: ".html_encode($help_title)."\" title=\"Help: ".html_encode($help_title)."\" style=\"vertical-align: middle\" />";

	return $output;
}

/**
 * Function will load CKEditor (WYSIWYG / Rich Text Editor) into the page <head></head>
 * causing all textareas on the page to be replaced with a rich text equivilant.
 *
 * @param array $buttons
 * @return true
 */
function load_rte($toolbar_groups = array(), $plugins = array(), $other_options = array()) {
	global $HEAD;

	$extra_plugins = array("a11ychecker", "collapsibleItem");

    if (is_array($plugins) && !empty($plugins) && $plugins["autogrow"] === true) {
        $autogrow = true;

        $extra_plugins[] = "autogrow";
    } else {
        $autogrow = false;
    }
    
    if (!$toolbar_groups || (is_scalar($toolbar_groups) && ($toolbar_groups = clean_input($toolbar_groups, "alpha")))) {
        //Check whether we should allow access to file uploads
        switch ($toolbar_groups) {
            case "notices" :
                $allow_uploads = false;
            break;
            default:
                $allow_uploads = true;
            break;
        }
        
        //Assign the right set of toolbar groups
        switch ($toolbar_groups) {
            case "full" :
            case "communityadvanced" :
            case "communitybasic" :
            case "advanced" :
                $toolbar_groups = array(
                    array("name" => "clipboard", "groups" => array("document", "mode", "spellchecker")),
                    array("name" => "links"),
                    array("name" => "insert", "groups" => array("mediaembed", "insert")),
                    array("name" => "list", "groups" => array("list", "indent", "blocks")),
                    array("name" => "styles"),
                    array("name" => "basicstyles", "groups" => array("basicstyles", "cleanup")),
                    array("name" => "paragraph", "groups" => array("colors", "align")),
                    array("name" => "tools", "groups" => array("maximize")),
                );
            break;
            default :
                $toolbar_groups = array(
                    array("name" => "clipboard", "groups" => array("document", "mode", "spellchecker")),
                    array("name" => "links"),
                    array("name" => "insert", "groups" => array("mediaembed", "insert")),
                    array("name" => "paragraph", "groups" => array("list", "indent", "blocks", "align")),
                    array("name" => "tools", "groups" => array("maximize")),
                    array("name" => "styles"),
                    array("name" => "basicstyles", "groups" => array("basicstyles", "cleanup")),
                );
            break;
        }
    }
    
	$output  = "<script src=\"" . ENTRADA_RELATIVE . "/javascript/ckeditor/ckeditor.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>\n";

	$output .= "<script defer=\"defer\">\n";
    $output .= "CKEDITOR.editorConfig = function(config) {\n";
    $output .= "    config.customConfig = '';\n";
    $output .= "    config.allowedContent = true;\n";
    $output .= "    config.baseHref = '" . ENTRADA_URL . "';\n";
    $output .= "    config.forcePasteAsPlainText = true;\n";
    $output .= "    config.autoParagraph = false;\n";
    $output .= "    config.toolbarGroups = " . json_encode($toolbar_groups) . ";\n";
    $output .= "    config.removeDialogTabs = 'link:upload;image:Upload';\n";
    $output .= "    config.extraPlugins = '" . implode(",", $extra_plugins). "';\n";
    $output .= "    config.removeButtons = 'Cut,Copy,Paste,JustifyBlock';\n";
    if ($allow_uploads) {
        $output .= "config.filebrowserBrowseUrl = '" . ENTRADA_URL . "/api/filebrowser.api.php?type=Files';\n";
        $output .= "config.filebrowserImageBrowseUrl = '" . ENTRADA_URL . "/api/filebrowser.api.php?type=Images';\n";
        $output .= "config.filebrowserUploadUrl = '" . ENTRADA_URL . "/api/filebrowser.api.php?type=Files&step=2&method=upload';\n";
        $output .= "config.filebrowserImageUploadUrl = '" . ENTRADA_URL . "/api/filebrowser.api.php?type=Files&step=2&method=upload';\n";
        $output .= "config.filebrowserWindowWidth = '800';\n";
        $output .= "config.filebrowserWindowHeight = '600';\n";
    }
    if ($autogrow) {
        $output .= "config.autoGrow_onStartup = true;\n";
        $output .= "config.autoGrow_minHeight = 75;\n";
    }

    $output .= "}\n";

	$output .= "window.onload = function() {\n";
    $output .= "    CKEDITOR.replaceAll(function(textarea, config) {\n";
    $output .= "        if (jQuery(textarea).hasClass('expandable')) {\n";
    $output .= "            return false;\n";
    $output .= "        }\n";
    $output .= "    });\n";
    $output .= "}\n";
    $output .= "function toggleEditor(id) {\n";
	$output .= "    if(!CKEDITOR.instances[id]) {\n";
	$output .= "        CKEDITOR.replace(id);\n";
	$output .= "    } else {\n";
	$output .= "        CKEDITOR.instances[id].destroy(false);\n";
	$output .= "    }\n";
	$output .= "}\n";
    $output .= "</script>\n";

    $HEAD[] = $output;

	return true;
}

/**
 * COMMUNITY SYSTEM FUNCTIONS
 * These functions are specific to the commmunity system. All community modules
 * are prefixed with communities_.
 *
 */

/**
 * @todo Write this function and have it go to someone other than Matt Simpson.
 *
 * @return bool
 */
function communities_approval_notice() {
	return @mail($AGENT_CONTACTS["administrator"]["email"], "New Community To Approve", "Please review the latest community which has requested to be put in a sanctioned category.", "From: ".$AGENT_CONTACTS["administrator"]["name"]." <".$AGENT_CONTACTS["administrator"]["email"].">");
}

/**
 * Function will load Rich Text Editor into the page <head></head>
 * causing all textareas on the page to be replaced with rte's.
 *
 * @param array $buttons
 * @return true
 */
function communities_load_rte($buttons = array(), $plugins = array(), $other_options = array()) {
	if (!is_array($buttons) || !count($buttons)) {
		$buttons = "community";
	}

	return load_rte($buttons, $plugins, $other_options);
}

/**
 * This function handles data in the users_online table by inserting, updating or deleting
 * the details accordingly. This function is called every page load in the admin.php file.
 *
 * @param string $action
 */
function users_online($action = "default") {
	global $db, $ENTRADA_USER;

	switch($action) {
		case "logout" :
			if (isset($ENTRADA_USER) && $ENTRADA_USER && (int) $ENTRADA_USER->getID()) {
			/**
			 * This query will delete only the exact session information, but it's probably better to delete
			 * everthing about this user is it not? I don't know.
			 * $query = "DELETE FROM `users_online` WHERE `session_id` = ".$db->qstr(session_id())." AND `proxy_id` = ".$db->qstr((int) $ENTRADA_USER->getID())." LIMIT 1";
			 */
				$query = "DELETE FROM `users_online` WHERE `proxy_id` = ".$db->qstr((int) $ENTRADA_USER->getID());
				if(!$db->Execute($query)) {
					application_log("error", "Loggout: Failed to delete users_online entry for proxy id ".$ENTRADA_USER->getID().". Database said: ".$db->ErrorMsg());
				}
			}
		break;
		case "default" :
		default :
			if((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
				$query	= "SELECT * FROM `users_online` WHERE `session_id` = ".$db->qstr(session_id());
				$result	= $db->GetRow($query);
				if($result) {
					$query = "UPDATE `users_online` SET `timestamp` = ".$db->qstr(time())." WHERE `session_id` = ".$db->qstr(session_id());
					if(!$db->Execute($query)) {
						application_log("error", "Unable to update the users_online timestamp. Database said: ".$db->ErrorMsg());
					}
				} else {
					$query = "INSERT INTO `users_online` (`session_id`, `ip_address`, `proxy_id`, `username`, `firstname`, `lastname`, `timestamp`) VALUES (".$db->qstr(session_id()).", ".$db->qstr($_SERVER["REMOTE_ADDR"]).", ".$db->qstr($ENTRADA_USER->getID()).", ".$db->qstr($_SESSION["details"]["username"]).", ".$db->qstr($_SESSION["details"]["firstname"]).", ".$db->qstr($_SESSION["details"]["lastname"]).", ".$db->qstr(time()).")";
					if(!$db->Execute($query)) {
						application_log("error", "Unable to insert a users_online record. Database said: ".$db->ErrorMsg());
					}
				}
			}
			break;
	}

	return true;
}

/**
 * This function handles removing old entries from the users_online table after the sessions have expired.
 *
 * @param string $expireref
 * @param string $sesskey
 * @return true
 */
function expired_session($expireref, $sesskey) {
	global $db;
	$query = "DELETE FROM `users_online` WHERE `session_id` = ".$db->qstr($sesskey)." LIMIT 1";
	if(!$db->Execute($query)) {
		application_log("error", "Expired: Failed to delete users_online entry for proxy id ".$expireref.". Database said: ".$db->ErrorMsg());
	}
	return true;
}

/**
 * This function generate the weather widget for the city specified by city_code.
 * 
 * @param string $city_code
 * @param array $options
 * @param string $weather_source
 * @return string
 */
function display_weather($city_code = "", $options = array(), $weather_source = "weather.com") {
	global $WEATHER_LOCATION_CODES, $WEATHER_TEMP_UNIT;

	$output_html	= "";
	$weather		= array();
	$weather_codes	= array();

	/**
	 * Validate the city_code
	 */
	if (isset($city_code) && $city_code) {
		if (is_array($city_code)) {
			foreach ($city_code as $value) {
				if ($value = clean_input($value, array("alphanumeric"))) {
					$weather_codes[$value] = ((isset($WEATHER_LOCATION_CODES[$value])) ? $WEATHER_LOCATION_CODES[$value] : "");
				}
			}
		} else {
			if($city_code = clean_input($city_code, array("alphanumeric"))) {
				$weather_codes[$city_code] = ((isset($WEATHER_LOCATION_CODES[$value])) ? $WEATHER_LOCATION_CODES[$value] : "");
			}
		}
	}

	if (!is_array($weather_codes) || (count($weather_codes) < 1)) {
		$weather_codes = $WEATHER_LOCATION_CODES;
	}

	if (is_array($weather_codes)) {
		foreach ($weather_codes as $weather_code => $city_name) {
			if(@file_exists(CACHE_DIRECTORY."/weather-".$weather_code.".xml")) {
				$xml		= @simplexml_load_file(CACHE_DIRECTORY."/weather-".$weather_code.".xml");
				$weather	= array();
				if ($xml) {
					/**
					 * XML content found, parse the data
					 */
					$yweather = $xml->results->channel->children("http://xml.weather.yahoo.com/ns/rss/1.0");
					foreach ($yweather as $key => $category) {
						foreach ($category->attributes() as $skey => $attribute) {
							$xml_data[$skey] = $attribute[0];
						}
					}
					$yweather = $xml->results->channel->item->children("http://xml.weather.yahoo.com/ns/rss/1.0");
					foreach ($yweather as $key => $category) {
						if ($key != "forecast") {
							foreach ($category->attributes() as $skey => $attribute) {
								$xml_data[$skey] = $attribute[0];
							}
						}
					}
					$weather["icon"]		= $xml_data["code"];
					$weather["tmp"]			= $xml_data["temp"];
					$weather["conditions"]	= $xml_data["text"];
					$wind_directions 		= array(
													0 => "N",
													1 => "NNE",
													2 => "NE",
													3 => "ENE",
													4 => "E",
													5 => "ESE",
													6 => "SE",
													7 => "SSE",
													8 => "S",
													9 => "SSW",
													10 => "SW",
													11 => "WSW",
													12 => "W",
													13 => "WNW",
													14 => "NW",
													15 => "NNW"
												);
					$angle_difference 		= 22.5;
					$direction_index 		= round((((float)$xml_data["direction"])/((float)$angle_difference)));
					$direction_string		= $wind_directions[($direction_index < 16 && $direction_index >= 0 ? $direction_index : 0)];
					$weather["windir"]		= $direction_string;
					$weather["s"]			= $xml_data["speed"]." ".$xml_data["distance"]."/h";
					$weather["sunr"]		= $xml_data["sunrise"];
					$weather["suns"]		= $xml_data["sunset"];
					/**
					 * If the temperature unit is Celsius, need to do the conversion here as yahoo send
					 * the chill factor in fahrenheits no matter the selection
					 */
					$weather["flik"]		= (($WEATHER_TEMP_UNIT == "c") ? ($xml_data["chill"] - 32) / 1.8 : $xml_data["chill"]);
				} else {
					/**
					 * No XML data found, generate default data.
					 */
					$weather["icon"]		= "0";
					$weather["tmp"]			= "?";
					$weather["conditions"]	= "Unknown";
					$weather["flik"]		= "?";
					$weather["s"]			= "calm";
					$weather["windir"]		= "";
					$weather["sunr"]		= "Unknown";
					$weather["suns"]		= "Unknown";
				}

				$output_html .= "<table style=\"width: 100%; margin-bottom: 15px\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
				$output_html .= "<thead>\n";
				$output_html .= "	<tr>\n";
				$output_html .= "		<td colspan=\"2\"><h2>".html_encode($city_name)."</h2></td>\n";
				$output_html .= "	</tr>\n";
				$output_html .= "</thead>\n";
				$output_html .= "<tbody>\n";
				$output_html .= "	<tr>\n";
				$output_html .= "		<td style=\"text-align: center; vertical-align: middle\">\n";
				$output_html .= "			<img src=\"".ENTRADA_URL."/images/weather/".((!(int) $weather["icon"]) ? "na" : (int) $weather["icon"]).".png\" width=\"64\" height=\"64\" border=\"0\" alt=\"".html_encode($weather["conditions"])."\" title=\"".html_encode($weather["conditions"])."\" />";
				$output_html .= "		</td>\n";
				$output_html .= "		<td style=\"text-align: center; vertical-align: middle\">\n";
				$output_html .= "			<h1 style=\"font-size: 28px; margin: 0px\">".((int) $weather["tmp"])."&#176;</h1>";
				$output_html .= "			<div class=\"content-small\" style=\"font-weight: bold\">".$weather["conditions"]."</div>";
				$output_html .= "		</td>\n";
				$output_html .= "	</tr>\n";

				if($weather["tmp"] != $weather["flik"]) {
					$output_html .= "<tr>\n";
					$output_html .= "	<td class=\"content-small\" style=\"text-align: right; padding-right: 10px\">Feels like:</td>\n";
					$output_html .= "	<td class=\"content-small\">".((int) $weather["flik"])."&#176;</td>";
					$output_html .= "</tr>\n";
				}

				$output_html .= "	<tr>\n";
				$output_html .= "		<td class=\"content-small\" style=\"text-align: right; padding-right: 10px\">Wind:</td>\n";
				$output_html .= "		<td class=\"content-small\">".(($weather["s"] == "calm") ? "Calm" : html_encode($weather["windir"])." @ ".html_encode($weather["s"]))."</td>";
				$output_html .= "	</tr>\n";
				$output_html .= "</tbody>\n";
				$output_html .= "</table>\n";
			}
		}
	}

	return $output_html;
}

/**
 * This function will generate a fairly random hash code which
 * can be used in a number of situations.
 *
 * @param int $num_chars
 * @return string
 */
function generate_hash($num_chars = 32) {
	if(!$num_chars = (int) $num_chars) {
		$num_chars = 32;
	}

	return substr(hash("sha256", uniqid(rand(), 1)), 0, $num_chars);
}

/**
 * Community function responsible for logging every historical event that takes place in a community.
 * This is used to display commmunity history in the community (if display_message is 1) and it is
 * also used to get stats on the most active communities.
 *
 * @param int $community_id
 * @param int $page_id
 * @param int $record_id
 * @param string $message
 * @param int $display_message
 * @param int $parent_id
 * @return bool
 */
function communities_log_history($community_id = 0, $page_id = 0, $record_id = 0, $history_message = "", $display_message = 0, $parent_id = 0) {
	global $db, $ENTRADA_USER;

	if(($community_id = (int) $community_id) && (strlen(trim($history_message)))) {
		$page_id			= (int) $page_id;
		$record_id			= (int) $record_id;
		$display_message	= (((int) $display_message) ? 1 : 0);

		$query = "INSERT INTO `community_history` (`community_id`, `cpage_id`, `record_id`, `record_parent`, `proxy_id`, `history_key`, `history_display`, `history_timestamp`) VALUES (".$db->qstr($community_id).", ".$db->qstr($page_id).", ".$db->qstr($record_id).", ".$db->qstr($parent_id).", ".$db->qstr((int) $ENTRADA_USER->getID()).", ".$db->qstr($history_message).", ".$db->qstr($display_message).", ".$db->qstr(time()).")";
		if($db->Execute($query)) {
			return true;
		} else {
			application_log("error", "Unable to insert historical community event. Database said: ".$db->ErrorMsg());
		}
	}

	return false;
}

/**
 * This function sets two variables used to generate community history log output lines.
 *
 * @param string $history_key
 * @param int $record_id
 * @param int $page_id
 * @param int $community_id
 */
function community_history_record_title($history_key = "", $record_id = 0, $page_id = 0, $community_id = 0, $proxy_id = 0) {
	global $db, $record_title, $parent_id;
	switch ($history_key) {
		case "community_history_add_announcement" :
		case "community_history_edit_announcement" :
			$query = "SELECT (`announcement_title`) as `record_title` FROM `community_announcements` WHERE `cannouncement_id` = ".$db->qstr($record_id)." AND `cpage_id` = ".$db->qstr($page_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_add_forum" :
		case "community_history_edit_forum" :
			$query = "SELECT (`forum_title`) as `record_title` FROM `community_discussions` WHERE `cdiscussion_id` = ".$db->qstr($record_id)." AND `cpage_id` = ".$db->qstr($page_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_add_post" :
		case "community_history_edit_post" :
			$query = "SELECT (`topic_title`) as `record_title` FROM `community_discussion_topics` WHERE `cdtopic_id` = ".$db->qstr($record_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_edit_reply" :
		case "community_history_add_reply" :
			$query = "SELECT (b.`topic_title`) as `record_title`, (a.`cdtopic_parent`) as `parent_id` FROM `community_discussion_topics` as a LEFT JOIN `community_discussion_topics` as b ON a.`cdtopic_parent` = b.`cdtopic_id` WHERE a.`cdtopic_id` = ".$db->qstr($record_id)." AND a.`community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_add_event" :
		case "community_history_edit_event" :
			$query = "SELECT (`event_title`) as `record_title` FROM `community_events` WHERE `cevent_id` = ".$db->qstr($record_id)." AND `cpage_id` = ".$db->qstr($page_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_add_learning_event" :
		case "community_history_edit_learning_event" :
			$query = "SELECT (`event_title`) as `record_title` FROM `events` WHERE `event_id` = ".$db->qstr($record_id);
			break;
		case "community_history_add_photo_comment" :
		case "community_history_edit_photo_comment" :
			$query = "SELECT (`photo_title`) as `record_title` FROM `community_gallery_photos` WHERE `cgphoto_id` = ".$db->qstr($parent_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_add_photo" :
		case "community_history_edit_photo" :
		case "community_history_move_photo" :
			$query = "SELECT (`photo_title`) as `record_title` FROM `community_gallery_photos` WHERE `cgphoto_id` = ".$db->qstr($record_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_add_gallery" :
		case "community_history_edit_gallery" :
			$query = "SELECT (`gallery_title`) as `record_title` FROM `community_galleries` WHERE `cgallery_id` = ".$db->qstr($record_id)." AND `cpage_id` = ".$db->qstr($page_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_add_poll" :
		case "community_history_edit_poll" :
			$query = "SELECT (`poll_title`) as `record_title` FROM `community_polls` WHERE `cpolls_id` = ".$db->qstr($record_id)." AND `cpage_id` = ".$db->qstr($page_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_add_file_comment" :
		case "community_history_edit_file_comment" :
			$query = "SELECT (`file_title`) as `record_title` FROM `community_share_files` WHERE `csfile_id` = ".$db->qstr($parent_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_add_file" :
		case "community_history_edit_file" :
		case "community_history_move_file" :
			$query = "SELECT (`file_title`) as `record_title` FROM `community_share_files` WHERE `csfile_id` = ".$db->qstr($record_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_add_share" :
		case "community_history_edit_share" :
			$query = "SELECT (`folder_title`) as `record_title` FROM `community_shares` WHERE `cshare_id` = ".$db->qstr($record_id)." AND `cpage_id` = ".$db->qstr($page_id)." AND `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_create_moderated_community" :
		case "community_history_create_active_community" :
		case "community_history_rename_community" :
			$query = "SELECT (`community_title`) as `record_title` FROM `communities` WHERE `community_id` = ".$db->qstr($community_id);
			break;
		case "community_history_activate_module" :
			$query = "SELECT (`module_title`) as `record_title` FROM `communities_modules` WHERE `module_id` = ".$db->qstr($record_id);
			break;
		case "community_history_add_page" :
		case "community_history_edit_page" :
			$query = "SELECT (`menu_title`) as `record_title` FROM `community_pages` WHERE `cpage_id` = ".$db->qstr($page_id);
			break;
		case "community_history_edit_community" :
			$query = "SELECT CONCAT_WS(' ', `firstname`, `lastname`) as `record_title` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($record_id);
			break;
		case "community_history_add_member" :
			$query = "SELECT CONCAT_WS(' ', `firstname`, `lastname`) as `record_title` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($proxy_id);
			break;
	}
	$result = $db->GetRow($query);

	if ($result) {
		$record_title = $result["record_title"];
		if (isset($result["parent_id"]) && $result["parent_id"]) {
			$parent_id = $result["parent_id"];
		}
	}
}

/**
 * Community function responsible for deactivating community history logs
 * for items which have been deleted or deactivated.
 *
 * @param int $community_id
 * @param int $page_id
 * @param int $record_id
 * @return bool
 */
function communities_deactivate_history($community_id = 0, $page_id = 0, $record_id = 0) {
	global $db;

	if(($community_id = (int) $community_id)) {
		$page_id = (int) $page_id;
		$record_id = (int) $record_id;

		$query = "UPDATE `community_history` SET `history_display` = '0' WHERE `community_id` = ".$db->qstr($community_id)." AND `cpage_id` = ".$db->qstr($page_id).(((int)$record_id) > 0 ? " AND `record_id` = ".$db->qstr($record_id) : "");
		if($db->Execute($query)) {
			if ($record_id) {
				communities_deactivate_children($community_id, $page_id, $record_id);
			}
			return true;
		} else {
			application_log("error", "Unable to deactivate historical community event. Database said: ".$db->ErrorMsg());
		}
	}

	return false;
}

function communities_deactivate_children($community_id, $page_id, $parent_id) {
	global $db;
	$query = "SELECT `record_id` FROM `community_history` WHERE `community_id` = ".$db->qstr($community_id)." AND `cpage_id` = ".$db->qstr($page_id)." AND `record_parent` = ".$db->qstr($parent_id);

	$results = $db->GetAll($query);
	foreach ($results as $result) {
		communities_deactivate_children($community_id, $page_id, $result["record_id"]);
	}
	$db->Execute("UPDATE `community_history` SET `history_display` = '0' WHERE `community_id` = ".$db->qstr($community_id)." AND `cpage_id` = ".$db->qstr($page_id)." AND `record_parent` = ".$db->qstr($parent_id));

}

/**
 * Function is responsible for counting the total number of communities under
 * the specified category_id.
 *
 * @param int $category_id
 * @return int
 */
function communities_count($category_id = 0) {
	global $db;

	$query	= "SELECT COUNT(*) AS `total` FROM `communities` WHERE".(($category_id = (int) trim($category_id)) ? " `category_id` = ".$db->qstr($category_id)." AND" : "")." `community_active` = '1'";
	$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
	if($result) {
		return (int) $result["total"];
	}

	return 0;
}

/**
 * Function will return all information on the provided category_id from the database.
 *
 * @param int $category_id
 * @return array
 */
function communities_fetch_category($category_id = 0) {
	global $db;

	if($category_id = (int) $category_id) {
		$query	= "SELECT * FROM `communities_categories` WHERE `category_id` = ".$db->qstr($category_id);
		$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
		if($result) {
			return $result;
		}
	}

	return false;
}

/**
 * @todo Is this used? Does it work? It was previously undocumented.
 *
 * @param int $community_id
 * @return string
 */
function communities_generate_url($community_id = 0) {
	global $db;

	if(!$community_id = (int) $community_id) {
		return false;
	}

	$fetched = array();
	communities_fetch_parents($community_id, $fetched, 0, false);

	return $fetched;

	return "/".((@count($path)) ? implode("/", array_reverse($path))."/" : "");
}

/**
 * Function will return the communities above the specified community_id, as an array.
 *
 * @param int $community_id
 * @return array
 */
function communities_regenerate_url($community_parent = 0) {

	if(!$community_parent = (int) $community_parent) {
		return false;
	}

	$community_url	= "";
	$fetched		= array();

	communities_fetch_parents($community_parent, $fetched);

	if((is_array($fetched)) && (count($fetched))) {
		$communities = array_reverse($fetched);
		unset($fetched);

		foreach ($communities as $community) {
			$community_url .= "/".$community["community_shortname"];
		}
	}

	return $fetched;
}

/**
 * Recursive function will return all communities above the specified community_id, as an array.
 *
 * @uses This function can be used to generate breadcrumb trails, create maps / paths, etc.
 * @param int $community_id
 * @param array $fetched
 * @param int $level
 * @param bool $fetch_top
 * @return array
 */
function communities_fetch_parents($community_id = 0, &$fetched, $level = 0, $fetch_top = true) {
	global $db;

	if($level > 99) {
		return false;
	}

	if(!$community_id = (int) $community_id) {
		return false;
	}

	$query	= "SELECT `community_id`, `community_parent`, `community_url`, `community_shortname`, `community_title`, `community_active` FROM `communities` WHERE `community_id` = ".$db->qstr($community_id);
	$result	= $db->GetRow($query);
	if($result) {
		$fetched[$result["community_id"]] = $result;

		/**
		 * If you want to fetch to the top, this becomes a recursive function.
		 */
		if((bool) $fetch_top) {
			communities_fetch_parents($result["community_parent"], $fetched, $level + 1, true);
		}
	}
	return true;
}

/**
 * Function will return the communities above the specified community_id, as an array.
 *
 * @param int $community_id
 * @return array
 */
function communities_fetch_parent($community_id = 0) {

	if(!$community_id = (int) $community_id) {
		return false;
	}

	$fetched = array();
	communities_fetch_parents($community_id, $fetched, 0, false);

	return $fetched;
}

/**
 * Recursive function will return all communities below the specified community_id, as an array.
 *
 * @param int $community_id
 * @param array $requested_fields
 * @param int $max_generations
 * @param bool $show_inactive
 * @param int $level
 * @return array
 */
function communities_fetch_children($community_id = 0, $requested_fields = false, $max_generations = 0, $show_inactive = false, $output_type = false, $level = 0) {
	global $db, $COMMUNITIES_FETCH_CHILDREN;

	if($level > 99) {
		return false;
	}

	if(($output_type) && (!in_array($output_type, array("array", "select")))) {
		return false;
	}

	if((!is_array($requested_fields)) || (!count($requested_fields))) {
		$requested_fields = array("community_id", "community_parent", "community_url", "community_title", "community_active");
	}

	$fetched	= array();
	$query = "	SELECT `".implode("`, `", $requested_fields)."`
				FROM `communities`
				WHERE `community_parent` = ".$db->qstr((int) $community_id)."
				".((!(bool) $show_inactive) ? " AND `community_active` = '1'" : "")."
				ORDER BY `community_title` ASC";
	$results = $db->GetAll($query);
	if($results) {
		foreach ($results as $result) {
			$fetched[$result["community_id"]] = $result;

			if($output_type) {
				$fetched[$result["community_id"]]["indent_level"]	= $indent;
			}

			if((!$max_generations) || ($level < $max_generations)) {
				$children = communities_fetch_children($result["community_id"], $requested_fields, $max_generations, $show_inactive, $output_type, $level + 1);

				if((is_array($children)) && (@count($children))) {
					$fetched[$result["community_id"]]["community_children"]	= $children;
				} else {
					$fetched[$result["community_id"]]["community_children"] = array();
				}
			} else {
				$fetched[$result["community_id"]]["community_children"] = array();
			}
		}
	}

	switch($output_type) {
		case "select" :
			$html = "";
			if((is_array($fetched)) && (count($fetched))) {
				foreach ($fetched as $result) {
					$html .= "<option value=\"".$result["community_id"]."\"".(((is_array($COMMUNITIES_FETCH_CHILDREN)) && (in_array($result["community_id"], $COMMUNITIES_FETCH_CHILDREN))) ? " selected=\"selected\"" : "").">".str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $result["indent_level"]).html_encode($result["community_title"])."</option>\n";
				}
			}

			return $html;
			break;
		case "array" :
		default :
			return $fetched;
			break;
	}
}

/**
 * Function will return the title of the provided module_id.
 *
 * @param unknown_type $module_id
 * @return unknown
 */
function communities_title($community_id = 0) {
	global $db;

	if($community_id = (int) $community_id) {
		if($result = communities_details($community_id, array("community_title"))) {
			return $result["community_title"];
		}
	}

	return "Unknown Community";
}

/**
 * Function will return the requested information about the provided module_id.
 *
 * @param int $module_id
 * @param array $requested_info
 * @return array
 */
function communities_details($community_id = 0, $requested_info = array()) {
	global $db;

	if($community_id = (int) $community_id) {
		$field_names = array();

		if(!is_array($requested_info)) {
			$requested_info = array($requested_info);
		}

		if((@count($requested_info)) && ($module_columns = $db->MetaColumnNames("communities"))) {
			foreach ($requested_info as $field) {
				if(in_array($field, $module_columns)) {
					$field_names[] = $field;
				}
			}

			if(@count($field_names)) {
				$query	= "SELECT `".implode("`, `", $field_names)."` FROM `communities` WHERE `community_id` = ".$db->qstr($community_id);
				$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
				if($result) {
					return $result;
				}
			}
		}
	}

	return false;
}

/**
 * Function will return the title of the provided module_id.
 *
 * @param unknown_type $module_id
 * @return unknown
 */
function communities_module_title($module_id = 0) {
	global $db;

	if($module_id = (int) $module_id) {
		if($result = communities_module_details($module_id, array("module_title"))) {
			return $result["module_title"];
		}
	}

	return "Unknown Module";
}

/**
 * Function will return the requested information about the provided module_id.
 *
 * @param int $module_id
 * @param array $requested_info
 * @return array
 */
function communities_module_details($module_id = 0, $requested_info = array()) {
	global $db;

	if($module_id = (int) $module_id) {
		$field_names = array();

		if(!is_array($requested_info)) {
			$requested_info = array($requested_info);
		}

		if((@count($requested_info)) && ($module_columns = $db->MetaColumnNames("communities_modules"))) {
			foreach ($requested_info as $field) {
				if(in_array($field, $module_columns)) {
					$field_names[] = $field;
				}
			}

			if(@count($field_names)) {
				$query	= "SELECT `".implode("`, `", $field_names)."` FROM `communities_modules` WHERE `module_id` = ".$db->qstr($module_id);
				$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
				if($result) {
					return $result;
				}
			}
		}
	}

	return false;
}

/**
 * Wrapper function.
 *
 * @param unknown_type $community_id
 * @param unknown_type $module_id
 * @param unknown_type $action
 */
function communities_module_access($community_id = 0, $module_id = 0, $action = "index") {
	global $db;

	if(($community_id = (int) $community_id) && ($module_id = (int) $module_id) && ($action = trim($action))) {
		$query	= "SELECT * FROM `community_permissions` WHERE `community_id` = ".$db->qstr($community_id)." AND `module_id` = ".$db->qstr($module_id);
		$result	= $db->GetRow($query);
		if($result) {
			return communities_module_access_unique($community_id, $module_id, $action);
		} else {
			return communities_module_access_generic($module_id, $action);
		}
	}

	return false;
}
/**
 * Tells the module whether or not to load the specified action. This is the generic version which uses
 * the communities_modules table for overall results.
 *
 * @param int $module_id
 * @param string $action
 * @return bool
 */
function communities_module_access_generic($module_id = 0, $action = "index") {
	global $db, $LOGGED_IN, $COMMUNITY_MEMBER, $COMMUNITY_ADMIN, $PROXY_ID, $RECORD_AUTHOR, $PAGE_OPTIONS;

	$allow_to_load = false;

	if(((bool) $LOGGED_IN) && ((bool) $COMMUNITY_MEMBER) && ((bool) $COMMUNITY_ADMIN)) {
		$allow_to_load = true;
	} else {
		if($module_id = (int) $module_id) {
			$query	= "SELECT `module_permissions` FROM `communities_modules` WHERE `module_id` = ".$db->qstr($module_id);
			$result	= $db->GetRow($query);
			if($result) {
				if(($module_permissions = trim($result["module_permissions"])) && ($module_permissions = @unserialize($module_permissions)) && (@is_array($module_permissions))) {
					if(isset($module_permissions[$action])) {
						$query = "SELECT `module_shortname` FROM `communities_modules` WHERE `module_id` = ".$db->qstr($module_id);
						if((int) $module_permissions[$action] != 1) {
							$allow_to_load = true;
						} elseif ((($module_name = $db->GetOne($query)) && ($module_name == "events" || $module_name == "announcements")) && ($action == "edit" || $action == "add" || $action == "delete") && (($PAGE_OPTIONS["allow_member_posts"] && $COMMUNITY_MEMBER) || ($PAGE_OPTIONS["allow_troll_posts"] && $LOGGED_IN)) && ((!$RECORD_AUTHOR && ($action == "add" || $action == "delete")) || $RECORD_AUTHOR == $PROXY_ID)) {
							$allow_to_load = true;
						}
					}
				}
			}
		}
	}

	return $allow_to_load;
}

/**
 * Tells the module whether or not to load the specified action for the specified community.
 *
 * @param int $community_id
 * @param int $module_id
 * @param string $action
 * @return bool
 */
function communities_module_access_unique($community_id = 0, $module_id = 0, $action = "index") {
	global $db, $COMMUNITY_ADMIN;

	$allow_to_load = false;

	if (($community_id = (int) $community_id) && ($module_id = (int) $module_id)) {
		$query = "SELECT * FROM `community_permissions` WHERE `community_id` = ".$db->qstr($community_id)." AND `module_id` = ".$db->qstr($module_id)." AND (`action` = 'all' OR `action` = ".$db->qstr($action).")";
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				if (($action == "index") || ((bool) $COMMUNITY_ADMIN) || ((int) $result["level"] === 0)) {
					$allow_to_load = true;
					break;
				}
			}
		}
	}

	return $allow_to_load;
}

/**
 * Activates speficied module for the specified community
 *
 * @param int $community_id
 * @param int $module_id
 * @return bool
 */
function communities_module_activate($community_id = 0, $module_id = 0) {
	global $db;

	if (($community_id = (int) $community_id) && ($module_id = (int) $module_id)) {
        /*
         * Check that the requested module is present and active.
         */
		$query = "SELECT * FROM `communities_modules` WHERE `module_id` = ".$db->qstr($module_id)." AND `module_active` = '1'";
		$module_info = $db->GetRow($query);
		if ($module_info) {
			$query	= "SELECT * FROM `community_modules` WHERE `community_id` = ".$db->qstr($community_id)." AND `module_id` = ".$db->qstr($module_id);
			$result	= $db->GetRow($query);
			if ($result) {
                /*
                 * If it is not already active, active it.
                 */
				if (!(int) $result["module_active"]) {
					if (!$db->AutoExecute("community_modules", array("module_active" => 1), "UPDATE", "`community_id` = ".$db->qstr($community_id)." AND `module_id` = ".$db->qstr($module_id))) {
						application_log("error", "Unable to active module ".(int) $module_id." (updating existing record) for updated community id ".(int) $community_id.". Database said: ".$db->ErrorMsg());
					}
				}
			} else {
				if (!$db->AutoExecute("community_modules", array("community_id" => $community_id, "module_id" => $module_id, "module_active" => 1), "INSERT")) {
					application_log("error", "Unable to active module ".(int) $module_id." (inserting new record) for updated community id ".(int) $community_id.". Database said: ".$db->ErrorMsg());
				}
			}
		} else {
			application_log("error", "Module_id [".$module_id."] requested activation in community_id [".$community_id."] but the module is either missing or inactive.");
		}
	} else {
		application_log("error", "There was no community_id [".$community_id."] or module_id [".$module_id."] provided to active a module.");
	}

	return true;
}

/**
 * Deactivates speficied module for the specified community
 *
 * @param int $community_id
 * @param int $module_id
 * @return bool
 */
function communities_module_deactivate($community_id = 0, $module_id = 0) {
	global $db;

	if(($community_id = (int) $community_id) && ($module_id = (int) $module_id)) {
	/**
	 * Check that the requested module is present and active.
	 */
		$query			= "SELECT * FROM `communities_modules` WHERE `module_id` = ".$db->qstr($module_id);
		$module_info	= $db->GetRow($query);
		if($module_info) {
			$query		= "SELECT * FROM `community_pages` WHERE `community_id` = ".$db->qstr($community_id)." AND `page_active` = '1' AND `page_type` = ".$db->qstr($module_info["module_shortname"]);
			$results	= $db->GetAll($query);
			if($results) {
				foreach ($results as $result) {
					communities_pages_delete($result["cpage_id"]);
				}
			}
		}

		if($db->AutoExecute("community_modules", array("module_active" => 0), "UPDATE", "`community_id` = ".$db->qstr($community_id)." AND `module_id` = ".$db->qstr($module_id))) {
			return true;
		}
	}

	application_log("error", "Can't deactive module_id [".$module_id."] for community_id [".$community_id."]");

	return false;
}

/**
 * Responsible for fetching the modules which are enabled for a specific community_id.
 *
 * @param int $community_id
 * @return multi-dimensional array
 */
function communities_fetch_modules($community_id = 0) {
	global $db;

	$available	= array();

	if($community_id = (int) $community_id) {
		$query		= "
					SELECT b.`module_id`, b.`module_shortname`, b.`module_title`
					FROM `community_modules` AS a
					LEFT JOIN `communities_modules` AS b
					ON b.`module_id` = a.`module_id`
					WHERE b.`module_active` = '1'
					AND a.`module_active` = '1'
					AND a.`community_id` = ".$db->qstr($community_id);
		$results	= $db->GetAll($query);
		if($results) {
			$i = 1;
			foreach ($results as $result) {
				$available[$result["module_shortname"]]	= $result["module_title"];
				// Extra module information included here.
				switch($result["module_shortname"]) {
					case "announcements" :
						$i++;
						$available["calendar"]			= "Calendar";
						break;
					default:
						continue;
						break;
				}
				$i++;
			}
		}
	}

	return array("enabled" => $available);
}

/**
 * Responsible for fetching the modules which are enabled for a specific community_id.
 *
 * @param int $community_id
 * @return multi-dimensional array
 */
function communities_fetch_pages($community_id = 0, $user_access = 0) {
	global $db, $PAGE_URL;

	$navigation			= array();
	$available			= array();
	$details			= array();
	$available_ids		= array();

	$access_query_condition = array(
		" `allow_public_view` = 1 ",
		" `allow_troll_view` = 1 ",
		" `allow_member_view` = 1 ",
		" 1 "
    );

    /*
     * These are default modules which are always enabled.
     */
    $module_enabled = array(
        "default" => true,
        "url" => true,
        "lticonsumer" => true,
        "course" => true,
    );

	$community_access = 1;
	if ($user_access < 2) {
		$community_access = (int) $db->GetOne("SELECT `community_registration` from `communities` WHERE `community_id` =".$db->qstr($community_id)." AND `community_protected` = '1'");
	}

	if ($user_access == 1 && ((int) $db->GetOne("SELECT `community_registration` from `communities` WHERE `community_id` =".$db->qstr($community_id)." AND `community_protected` = '0'"))) {
		$user_access = 0;
	}

    $query = "SELECT a.*, b.`module_shortname`
                FROM `community_modules` AS a
                LEFT JOIN `communities_modules` AS b
                ON b.`module_id` = a.`module_id`
                WHERE a.`community_id` = ".$db->qstr($community_id);
	$module_availability = $db->GetAll($query);
	if ($module_availability) {
		foreach ($module_availability as $module_record) {
			$module_enabled[$module_record["module_shortname"]] = (((int) $module_record["module_active"]) == 1 ? true : false);
		}
	}

	if (($community_id = (int) $community_id) && ($community_access < 4 || $user_access > 1)) {

		$query = "SELECT * FROM `community_pages`
					WHERE `page_url` = ''
					AND `community_id` = ".$db->qstr($community_id)."
					ORDER BY `page_order` ASC";
		$home = $db->GetRow($query);
		$navigation[$home["cpage_id"]]	= array(
					           "cpage_id" => $home["cpage_id"],
					           "link_order" => 0,
					           "link_parent" => $home["parent_id"],
					           "link_url" => "",
					           "link_title" => $home["menu_title"],
					           "link_selected" => ($home["page_url"] == $PAGE_URL ? true : false),
					           "child_selected" => false,
					           "link_new_window" => 0,
					           "link_type" => $home["page_type"],
					           "link_children" => array()
							);
		if (communities_page_has_children($home["cpage_id"], $access_query_condition[$user_access], $community_id)) {
			$navigation[0]["link_children"] = communities_fetch_child_pages($home["cpage_id"], $access_query_condition[$user_access], $community_id);
			if ($navigation[0]["link_children"]) {
				foreach ($navigation[0]["link_children"] as $child) {
					if ($child["link_selected"] || $child["child_selected"]) {
						$navigation[0]["child_selected"] = true;
					}
				}
			}
		}

//		$full_query = "SELECT `cpage_id`, `page_url`, `menu_title`, `page_order`, `page_type` FROM `community_pages` WHERE `community_id` = ".$db->qstr($community_id)." AND `page_url` != '' AND `page_active` = '1' ORDER BY `page_order` ASC";
//		$full_results = $db->GetAll($full_query);
//		if ($full_results) {
//			foreach ($full_results as $result) {
//				$exists[$result["page_url"]] = $result["menu_title"];
//			}
//		}
//
//		$available_query = "SELECT `cpage_id`, `page_url`, `menu_title`, `page_order`, `page_type` FROM `community_pages` WHERE `community_id` = ".$db->qstr($community_id)." AND ".$access_query_condition[$user_access]." AND `page_url` != '' AND `page_active` = '1' ORDER BY `page_order` ASC";
//		$available_results = $db->GetAll($available_query);
//		if ($full_results) {
//			foreach ($full_results as $result) {
//				$available[$result["page_url"]] = $result["menu_title"];
//				$available_ids[$result["page_url"]]	= $result["cpage_id"];
//				$details[$result["page_url"]] = $result;
//			}
//		}

        $full_query = "SELECT `cpage_id`, `page_url`, `menu_title`, `page_order`, `page_type` FROM `community_pages` WHERE `community_id` = ".$db->qstr($community_id)." AND `page_url` != '' AND `page_active` = '1' ORDER BY `page_order` ASC";
        $full_results = $db->GetAll($full_query);
        if ($full_results) {
            foreach ($full_results as $result) {
                $exists[$result["page_url"]] = $result["menu_title"];
                $available[$result["page_url"]] = $result["menu_title"];
                $available_ids[$result["page_url"]]	= $result["cpage_id"];
                $details[$result["page_url"]] = $result;
            }
        }

		$navigation_query = "SELECT `cpage_id`, `page_url`, `menu_title`, `page_order`, `page_type`, `page_content`, `page_visible` FROM `community_pages` WHERE `parent_id` = '0' AND `community_id` =".$db->qstr($community_id)." AND ".$access_query_condition[$user_access]." AND `page_url` != '' AND `page_active` = '1' ORDER BY `page_order` ASC";
		$navigation_results	= $db->GetAll($navigation_query);
		if ($navigation_results) {
			foreach ($navigation_results as $result) {
				if ($module_enabled[$result["page_type"]]) {
					if (((int)$result["page_visible"]) == 1) {
						if ($result["page_type"] == "url") {
							$query = "SELECT `option_value` FROM `community_page_options` WHERE `cpage_id` = ".$db->qstr($result["cpage_id"])." AND `option_title` = 'new_window'";
							$new_window = $db->GetOne($query);
						} else {
							$new_window = false;
						}

						$navigation[$result["cpage_id"]] = array(
                            "cpage_id" => $result["cpage_id"],
                            "link_order" => (int) $result["page_order"],
                            "link_parent" => 0,
                            "link_url" => ":".$result["page_url"],
                            "link_title" => $result["menu_title"],
                            "link_selected" => ($result["page_url"] == $PAGE_URL ? true : false),
                            "link_new_window" => ($new_window ? true : false),
                            "link_type" => $result["page_type"],
                            "link_children" => array()
                        );

						$visible = true;
					} else {
						$visible = false;
					}

					if (communities_page_has_children($result["cpage_id"], $access_query_condition[$user_access], $community_id) && $visible) {
						$navigation[$result["cpage_id"]]["link_children"] = communities_fetch_child_pages($result["cpage_id"], $access_query_condition[$user_access], $community_id);
						if ($navigation[$result["cpage_id"]]["link_children"]) {
							foreach ($navigation[$result["cpage_id"]]["link_children"] as $child) {
								if ($child["link_selected"] || $child["child_selected"]) {
									$navigation[$result["cpage_id"]]["child_selected"] = true;
								}
							}
						}
					}
				}
			}
		}
	}

	return array("enabled" => $available, "navigation" => $navigation, "details" => $details, "exists" => $exists, "available_ids" => $available_ids);
}

/**
 * Search the communities navigation heirarchy to find the selected page
 * Recursive function
 *
 * @param array $page
 * @param int $level
 * @return array $selected_page || null
 */
function communities_navigation_find_selected($page, $level = 0) {
	$level++;
	/* protect against infinite recursion, just in case */
	if ($level > 99) {
		return null;
	}
	if (isset($page["link_selected"]) && $page["link_selected"]) {
		return $page;
	} else {
		if (isset($page["link_children"]) && is_array($page["link_children"]) && (count($page["link_children"]) > 0)) {
			foreach ($page["link_children"] as $child_page) {
				$child_selected = communities_navigation_find_selected($child_page, $level);
				if ($child_selected !== null) {
					return $child_selected;
				}
			}
		}
	}
	return null;
}

function communities_page_has_children($cpage_id, $access_query_condition, $community_id) {
	global $db;
	$query = "SELECT COUNT(`cpage_id`) FROM `community_pages`
				WHERE `parent_id` = ".$db->qstr($cpage_id)."
				AND `community_id` = ".$db->qstr($community_id)."
				AND ".$access_query_condition;
	$found = $db->GetOne($query);
	return ($found ? true : false);
}


function communities_fetch_child_pages($cpage_id, $access_query_condition, $community_id, $level = 1) {
	global $db, $PAGE_URL;

	if ($level > 99) {
		return false;
	}

	$children_array = array();

	$query = "SELECT * FROM `community_pages`
				WHERE `parent_id` = ".$db->qstr($cpage_id)."
				AND ".$access_query_condition."
				AND `page_active` = '1'
				AND `community_id` = ".$db->qstr($community_id)."
				ORDER BY `page_order` ASC";
	$children = $db->GetAll($query);
	if ($children) {
		foreach ($children as $child) {
			if (((int)$child["page_visible"]) == 1) {
				if ($child["page_type"] == "url") {
					$child["new_window"] = $db->GetOne("SELECT `option_value` FROM `community_page_options` WHERE `cpage_id` = ".$db->qstr($child["cpage_id"])." AND `option_title` = 'new_window'");
				} else {
					$child["new_window"] = false;
				}
				$child_array = array(
							           "cpage_id" => $child["cpage_id"],
							           "link_order" => $child["page_order"],
							           "link_parent" => $child["parent_id"],
							           "link_url" => ":".$child["page_url"],
							           "link_title" => $child["menu_title"],
							           "link_selected" => ($child["page_url"] == $PAGE_URL ? true : false),
							           "child_selected" => false,
							           "link_new_window" => ($child["new_window"] ? $child["new_window"] : 0),
							           "link_type" => $child["page_type"],
							           "page_visible" => $child["page_visible"],
							           "link_children" => array()
									);
				$found = communities_page_has_children($child["cpage_id"], $access_query_condition, $community_id);
				if ($found) {
					$child_descendants = communities_fetch_child_pages($child["cpage_id"], $access_query_condition, $community_id, ($level + 1));
					if ($child_descendants) {
						$child_array["link_children"] = $child_descendants;
						foreach ($child_descendants as $child) {
							if ($child["link_selected"] || $child["child_selected"]) {
								$child_array["child_selected"] = true;
							}
						}
					}
				}
				$children_array[$child["cpage_id"]] = $child_array;
			}
		}
		return $children_array;
	}
}


/**
 * Will count the number of members and optionally specified ACL level.
 * e.g. communities_count_members(3, 1); will return all admins in community 3.
 *
 * @param int $community_id
 * @param int $acl_level
 * @return int
 */
function communities_count_members($community_id = 0, $acl_level = "all") {
	global $db;

	$output = 0;

	if($community_id = (int) $community_id) {
		$query	= "SELECT COUNT(*) AS `total_members` FROM `community_members` WHERE `community_id` = ".$db->qstr($community_id).(($acl_level != "all") ? " AND `member_acl` = ".$db->qstr((int) $acl_level) : "");
		$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
		if($result) {
			$output = (int) $result["total_members"];
		}
	}

	return $output;
}

/**
 * This function is used by the discussions module to pull the latest posting details
 * from the database to display on the index, etc.
 *
 * @param int $cdiscussion_id
 * @return array
 */
function communities_discussions_latest($cdiscussion_id = 0) {
	global $db, $COMMUNITY_ID;

	$output				= array();
	$output["posts"]	= 0;
	$output["replies"]	= 0;

	if($cdiscussion_id = (int) $cdiscussion_id) {
		$query = "	SELECT IF(a.`cdtopic_parent` = '0', a.`cdtopic_id`, b.`cdtopic_id`) AS `cdtopic_id`, IF(a.`cdtopic_parent` = '0', a.`topic_title`, b.`topic_title`) AS `topic_title`, IF(a.`cdtopic_parent` = '0', a.`anonymous`, b.`anonymous`) AS `anonymous`, a.`updated_date`, a.`proxy_id`, c.`username`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `poster_fullname`
					FROM `community_discussion_topics` AS a
					LEFT JOIN `community_discussion_topics` AS b
					ON a.`cdtopic_parent` = b.`cdtopic_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
					ON a.`proxy_id` = c.`id`
					WHERE a.`cdiscussion_id` = ".$db->qstr($cdiscussion_id)."
					AND a.`community_id` = ".$db->qstr((int) $COMMUNITY_ID)."
					AND a.`topic_active` = '1'
					AND (b.`topic_active` IS NULL OR b.`topic_active`='1')
					ORDER BY a.`updated_date` DESC
					LIMIT 0, 1";
		$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
		if($result) {
			$output["username"]		= $result["username"];
			$output["fullname"]		= $result["poster_fullname"];
			$output["proxy_id"]		= (int) $result["proxy_id"];
			$output["updated_date"]	= $result["updated_date"];
			$output["cdtopic_id"]	= $result["cdtopic_id"];
			$output["topic_title"]	= $result["topic_title"];
			$output["anonymous"]	= $result["anonymous"];

			/**
			 * Fetch the total number of posts.
			 * This could prolly be done with one query, but at what cost? I'm not sure.
			 */
			$query	= "SELECT COUNT(*) AS `total_posts` FROM `community_discussion_topics` WHERE `cdtopic_parent` = '0' AND `cdiscussion_id` = ".$db->qstr($cdiscussion_id)." AND `community_id` = ".$db->qstr((int) $COMMUNITY_ID)." AND `topic_active` ='1'";
			$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
			if(($result) && ((int) $result["total_posts"])) {
				$output["posts"] = (int) $result["total_posts"];

				/**
				 * Fetch the total number of replies to posts.
				 */
				$query	= "SELECT COUNT(*) AS `total_replies` FROM `community_discussion_topics` WHERE `cdtopic_parent` <> '0' AND `cdiscussion_id` = ".$db->qstr($cdiscussion_id)." AND `community_id` = ".$db->qstr((int) $COMMUNITY_ID)." AND `topic_active` ='1'";
				$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
				if(($result) && ((int) $result["total_replies"])) {
					$output["replies"] = (int) $result["total_replies"];
				}
			}
		}
	}

	return $output;
}

function communities_discussions_files_subnavigation($discusion_info,$tab='content', $path){
	$HTML = "<div class=\"no-printing\">";
	$HTML .= "	<ul class=\"nav nav-tabs\">";
        /* Admin code - modify with Daniels code
	if ($ENTRADA_ACL->amIAllowed(new EventResource($event_info["event_id"], $event_info["course_id"], $event_info["organisation_id"]), 'update')) {
		echo "		<li".($tab=='edit'?' class="active"':'')."><a href=\"".ENTRADA_URL."/admin/events?".replace_query(array("section" => "edit", "id" => $event_info['event_id'], "step" => false))."\" >Event Details</a></li>";
	}
         * 
         */
	$HTML .= "		<li".($tab=='edit-post'?' class="active"':'')."><a href=\"".$path.replace_query(array("section" => "edit-post", "id" => $discusion_info, "step" => false))."\" >Content</a></li>";

	$HTML .= "		<li".($tab=='edit-file'?' class="active"':'')."><a href=\"".$path.replace_query(array("section" => "edit-file", "id" => $discusion_info,"step"=>false))."\" >File Attachment</a></li>";
        
        //	$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-file&id=".$RECORD_ID, "title" => "Upload File");

	$HTML .= "	</ul>";
	$HTML .= "</div>\n";
        
        return $HTML;

}

/**
 * This function is used by the shares module to pull the latest file details
 * from the database to display on the index, etc.
 *
 * @param int $cshare_id
 * @return array
 */
function communities_shares_latest($cshare_id = 0) {
	global $db, $COMMUNITY_ID, $ENTRADA_USER;
    
    //checks the role of the user and sets hidden to true if they're not a facluty, staff, or medtech memeber
    //used to control access to files if they're marked hidden from students
    $group = $ENTRADA_USER->getActiveGroup();
    if ($group == 'faculty' || $group == 'staff'  || $group == 'medtech') {
        $hidden = false;
    } else {
        $hidden = true;
    }    

	$output					= array();
	$output["total_files"]	= 0;
	$output["total_bytes"]	= 0;

	if($cshare_id = (int) $cshare_id) {
		$query = "	SELECT a.`csfile_id`
					FROM `community_share_files` AS a
					WHERE a.`cshare_id` = ".$db->qstr($cshare_id)."
					AND a.`community_id` = ".$db->qstr((int) $COMMUNITY_ID).
                    ($hidden ? "AND a.`student_hidden` = '0'" : "")."
					AND a.`file_active` = '1'";
		$result	= $db->CacheGetAll(CACHE_TIMEOUT, $query);
		if($result) {
			$output["total_files"] = count(array_filter($result, function($item) {
				return shares_file_module_access($item['csfile_id'], "view-file");
			}));
		}
	}

	return $output;
}

/**
 * This function is used by the shares module to pull the latest link details
 * from the database to display on the index, etc.
 *
 * @param int $cshare_id
 * @return array
 */
function communities_shares_link_latest($cshare_id = 0) {
	global $db, $COMMUNITY_ID, $ENTRADA_USER;
    
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

	if($cshare_id = (int) $cshare_id) {
		$query = "	SELECT a.`cslink_id`
					FROM `community_share_links` AS a
					WHERE a.`cshare_id` = ".$db->qstr($cshare_id)."
					AND a.`community_id` = ".$db->qstr((int) $COMMUNITY_ID).
                    ($hidden ? "AND a.`student_hidden` = '0'" : "")."
					AND a.`link_active` = '1'";
		$result	= $db->CacheGetAll(CACHE_TIMEOUT, $query);
		if($result) {
			$output["total_links"] = count(array_filter($result, function($item) {
				return shares_link_module_access($item['cslink_id'], "view-link");
			}));
		}
	}

	return $output;
}

/**
 * This function is used by the shares module to pull the latest html details
 * from the database to display on the index, etc.
 *
 * @param int $cshare_id
 * @return array
 */
function communities_shares_html_latest($cshare_id = 0) {
	global $db, $COMMUNITY_ID, $ENTRADA_USER;
    
    //checks the role of the user and sets hidden to true if they're not a facluty, staff, or medtech memeber
    //used to control access to files if they're marked hidden from students
    $group = $ENTRADA_USER->getActiveGroup();
    if ($group == 'faculty' || $group == 'staff'  || $group == 'medtech') {
        $hidden = false;
    } else {
        $hidden = true;
    }        

	$output					= array();
	$output["total_html"]	= 0;

	if($cshare_id = (int) $cshare_id) {
		$query = "	SELECT a.`cshtml_id`
					FROM `community_share_html` AS a
					WHERE a.`cshare_id` = ".$db->qstr($cshare_id)."
					AND a.`community_id` = ".$db->qstr((int) $COMMUNITY_ID).
                    ($hidden ? "AND a.`student_hidden` = '0'" : "")."
					AND a.`html_active` = '1'";
		$result	= $db->CacheGetAll(CACHE_TIMEOUT, $query);
		if($result) {
			$output["total_html"] = count(array_filter($result, function($item) {
				return shares_html_module_access($item['cshtml_id'], "view-html");
			}));
		}
	}

	return $output;
}

function communities_subfolders_ids($cshare_id = 0, $array = array()) {
    global $db, $COMMUNITY_ID, $ENTRADA_USER;
    
    //if this is the first level then add the initial share to the array
    if (count($array) == 0) {
        $array[] = $cshare_id;
    }
    
    //select all subfolders
    $query = "SELECT `cshare_id` FROM `community_shares` WHERE `parent_folder_id` = " . $db->qstr($cshare_id) . " AND `folder_active` = '1'";
    $results = $db->GetAll($query);
    
    //add all the subfolders to the array
    if ($results && is_array($results)) {
        foreach ($results as $result) {
            $array[] = $result['cshare_id'];
            $loop = communities_subfolders_ids($result['cshare_id'], $array);
            $array = $loop;
        }
    }

    return $array;
}

function communities_galleries_fetch_thumbnail($cgphoto_id, $photo_title = "") {
	global $COMMUNITY_URL, $PAGE_URL, $COMMUNITY_TEMPLATE;

	if ($cgphoto_id = (int) $cgphoto_id) {
		$photo_url = COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?action=view-photo&amp;id=".$cgphoto_id."&amp;render=thumbnail";
	} else {
		$photo_url = COMMUNITY_RELATIVE."/templates/".$COMMUNITY_TEMPLATE."/images/galleries-no-photo.gif";
	}
	return "<img src=\"".$photo_url."\" width=\"150\" height=\"150\" alt=\"".html_encode($photo_title)."\" title=\"".html_encode($photo_title)."\" class=\"img-polaroid\" />\n";
}

function community_galleries_in_select($gallery_id = 0) {
	global $COMMUNITY_ID, $db;

	$output = "";

	$query	= "	SELECT a.`cgallery_id`, a.`gallery_title`, a.`cpage_id`, b.`menu_title`
				FROM `community_galleries` AS a
				LEFT JOIN `community_pages` AS b
				ON b.`cpage_id` = a.`cpage_id`
				WHERE a.`cgallery_id` != ".$db->qstr($gallery_id)."
				AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
				AND a.`gallery_active` = '1'
				AND b.`page_active` = '1'
				ORDER BY b.`page_order` ASC, a.`gallery_order` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		$cpage_id = 0;
		$output = "<select id=\"gallery_id\" name=\"gallery_id\" style=\"width: 300px\">";

		foreach ($results as $key => $result) {
			if ($cpage_id != $result["cpage_id"]) {
				$cpage_id = $result["cpage_id"];

				if ($key) {
					$output .= "</optgroup>";
				}

				$output .= "<optgroup label=\"".html_encode($result["menu_title"])."\">";
			}
			$output .= "<option value=\"".(int) $result["cgallery_id"]."\">".html_encode($result["gallery_title"])."</option>";

		}

		$output .= "</optgroup>";
		$output .= "</select>";

	}

	return $output;
}

function community_shares_in_select($share_id) {
	global $COMMUNITY_ID, $db;

	$output	= "";

	$query	= "	SELECT a.`cshare_id`, a.`folder_title`, a.`cpage_id`, b.`menu_title`
				FROM `community_shares` AS a
				LEFT JOIN `community_pages` AS b
				ON b.`cpage_id` = a.`cpage_id`
				WHERE a.`cshare_id` != ".$db->qstr($share_id)."
				AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
				AND a.`folder_active` = '1'
				AND b.`page_active` = '1'
				ORDER BY b.`page_order` ASC, a.`folder_order` ASC";
	$shares	= $db->GetAll($query);
	if ($shares) {
		$cpage_id = 0;
		$output = "<select id=\"share_id\" name=\"share_id\" style=\"width: 300px\">";

		foreach ($shares as $key => $result) {
			if ($cpage_id != $result["cpage_id"]) {
				$cpage_id = $result["cpage_id"];

				if ($key) {
					$output .= "</optgroup>";
				}

				$output .= "<optgroup label=\"".html_encode($result["menu_title"])."\">";
			}

			$output .= "<option value=\"".(int) $result["cshare_id"]."\">".html_encode($result["folder_title"])."</option>";

		}

		$output .= "</optgroup>";
		$output .= "</select>";

	}

	return $output;
}

//Selects the folders you have acces to by parent folder id
//Outputs it as a string to be exploded
function folder_select_view($cshare_id) {
    global $db, $loops, $COMMUNITY_ID;

    $querySelectFolders2 = "    SELECT `folder_title`, `cshare_id` FROM `community_shares` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID).
                            "   AND `folder_active` = '1' AND `parent_folder_id` = '".$cshare_id."' ORDER BY `folder_order` ASC, `folder_title` ASC";
    $folder_selects2 = $db->GetAll($querySelectFolders2);
    $folder_length = count($folder_selects2);
    $folder_loops = 0;
    $folderHTML = '';
    foreach ($folder_selects2 as $folder_select2) {

        $indentDashes = '';
        for($i = 1;$i<=$loops;$i++) {
            $indentDashes .= "-";
        }

        //counts where we are in the folder loop
        $folder_loops++;

        //compares the length of the array and if it's the last then it advances the counter one.
        //Also advances the counter if the array is one
        if (($folder_loops == $folder_length) || ($folder_length == '1')) {
            $loops++;
        }
        $folderHTML .= $indentDashes.$folder_select2["folder_title"]."|";
        $folderHTML .= $folder_select2["cshare_id"].",";
        $folderHTML .= folder_select_view($folder_select2["cshare_id"]);
    }
    return $folderHTML;
}

//gets the number of parent folders a folder has
//used to control the loop for dashes in the edit and create folder
//@param $cshare_id int - the record id for the folder
//return $loop int
function get_number_parent_folders($cshare_id, $loop = 0) {
    global $db;
    $query = "  SELECT `parent_folder_id`
                FROM `community_shares`
                WHERE `cshare_id` = " . $cshare_id;
    $parent_record = $db->GetRow($query);

    //runs recursively till it finds a folder on the top level
    if ($parent_record['parent_folder_id'] == '0') {
        return $loop;
    } else {
        $loop++;
        return get_number_parent_folders($parent_record['parent_folder_id'], $loop);
    }
    return $loop;
}




function selectSubFolders($cshare_id) {
    global $db, $COMMUNITY_ID;
    $folderHTML = '';

    $selectSubFolders =     "   SELECT `folder_title`, `cshare_id`, `parent_folder_id`
                                FROM `community_shares`
                                WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
                                AND `folder_active` = '1'
                                AND `parent_folder_id` = '".$cshare_id."'
                                ORDER BY `folder_order` ASC, `folder_title` ASC";
    $activeSubFolders = $db->GetAll($selectSubFolders);
    foreach($activeSubFolders as $activeSubFolder) {
        $loops = 0;
        $loops = get_number_parent_folders($activeSubFolder['cshare_id']);
         //use string replace on commas to avoid array_combine errors on folders with commas in the name.
        $search_array = Array(",", "|");
        $replace_array = Array("&#44;", "&#124;");
        $active_title_escaped = str_replace($search_array, $replace_array, $activeSubFolder['folder_title']);
        $folderHTML .= $active_title_escaped."|";
        $folderHTML .= $activeSubFolder['parent_folder_id']."|";
        $folderHTML .= $activeSubFolder['cshare_id']."|";
        $folderHTML .= $loops.",";
        $folderHTML .= selectSubFolders($activeSubFolder['cshare_id']);
    }
    return $folderHTML;
}




//sorts the community shares by
function community_shares_in_select_hierarchy($cshare_id, $parent_folder_id, $page_id, $type = "folder") {
    global $db, $COMMUNITY_ID;

    if ($type == "folder") {
    	$folderHTML = 'Root Level||0|0,';
    } else {
        $folderHTML = "";
    }

    $activeRootFoldersSelect = "    SELECT `folder_title`, `cshare_id`, `parent_folder_id`
                                    FROM `community_shares`
                                    WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
                                    AND `folder_active` = '1'
                                    AND `parent_folder_id` = '0'
                                    AND `cpage_id` = ".$db->qstr($page_id)."
                                    ORDER BY `folder_order` ASC, `folder_title` ASC";
    $activeRootFolders = $db->GetAll($activeRootFoldersSelect);
	if ($activeRootFolders) {
		if (is_array($activeRootFolders)) {
			foreach ($activeRootFolders as $activeRootFolder) {
				$loops = 0;
				$loops = get_number_parent_folders($activeRootFolder['cshare_id']);
				//use string replace on commas to avoid array_combine errors on folders with commas in the name.
				$search_array = Array(",", "|", "'", "’");
				$replace_array = Array("&#44;", "&#124;", "&#39;", "&#8217;");
				$active_title_escaped = str_replace($search_array, $replace_array, $activeRootFolder['folder_title']);
				$folderHTML .= $active_title_escaped . "|";
				$folderHTML .= $activeRootFolder['parent_folder_id'] . "|";
				$folderHTML .= $activeRootFolder['cshare_id'] . "|";
				$folderHTML .= $loops . ",";
				$folderHTML .= selectSubFolders($activeRootFolder['cshare_id']);
			}
		}
		//removes the extra comma
		$folderHTML = substr($folderHTML, 0, -1);
		//creates an array from the string
		$activeFolders = explode(",", $folderHTML);
	}

    $folderExport = "<select id=\"share_id\" name=\"share_id\" style=\"width: 300px\">";
    if ($folderHTML) {
		if (isset($activeFolders)) {
			if (is_array($activeFolders)) {
				foreach ($activeFolders as $activeFolder) {
					$fields = array('folder_title', 'parent_folder_id', 'cshare_id', 'loops');
					$activeFolder_array = array_combine($fields, explode("|", $activeFolder));

					$loops = 0;
					$dashes = '';
					$disabled = $cshare_id == $activeFolder_array['cshare_id'] ? "disabled=\"disabled\"" : "";
					$selected = $parent_folder_id == $activeFolder_array['cshare_id'] ? " selected=\"selected\"" : "";

					for ($i = 0; $i < $activeFolder_array['loops']; $i++) {
						$dashes .= "-";
					}
					$folderExport .= "<option value=\"" . $activeFolder_array['cshare_id'] . "\" " . $disabled . $selected . ">" . $dashes . html_encode($activeFolder_array['folder_title']) . "</option>";
				}
			}
		}
	}
    $folderExport .= "</select>";

    return $folderExport;
}

/**
 * Processes / resizes and creates properly sized image and thumbnail image
 * for images uploaded to the galleries module.
 *
 * @param string $original_file
 * @param int $photo_id
 * @return bool
 */
function communities_galleries_process_photo($original_file, $photo_id = 0) {
	global $VALID_MAX_DIMENSIONS, $COMMUNITY_ID;

	if(!@function_exists("gd_info")) {
		return false;
	}

	if((!@file_exists($original_file)) || (!@is_readable($original_file))) {
		return false;
	}

	if(!$photo_id = (int) $photo_id) {
		return false;
	}

	$new_file		= COMMUNITY_STORAGE_GALLERIES."/".$photo_id;
	$img_quality	= 85;

	if($original_file_details = @getimagesize($original_file)) {
		$original_file_width	= $original_file_details[0];
		$original_file_height	= $original_file_details[1];

		/**
		 * Check if the original_file needs to be resized or not.
		 */
		if(!DEMO_MODE && (($original_file_width > $VALID_MAX_DIMENSIONS["photo"]) || ($original_file_height > $VALID_MAX_DIMENSIONS["photo"]))) {
			switch($original_file_details["mime"]) {
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$original_img_resource = @imagecreatefromjpeg($original_file);
					break;
				case "image/png":
					$original_img_resource = @imagecreatefrompng($original_file);
					break;
				case "image/gif":
					$original_img_resource = @imagecreatefromgif($original_file);
					break;
				default :
					return false;
					break;
			}

			if($original_img_resource) {
			/**
			 * Determine whether it's a horizontal / vertical image and calculate the new smaller size.
			 */
				if($original_file_width > $original_file_height) {
					$new_file_width		= $VALID_MAX_DIMENSIONS["photo"];
					$new_file_height	= (int) (($VALID_MAX_DIMENSIONS["photo"] * $original_file_height) / $original_file_width);
				} else {
					$new_file_width		= (int) (($VALID_MAX_DIMENSIONS["photo"] * $original_file_width) / $original_file_height);
					$new_file_height	= $VALID_MAX_DIMENSIONS["photo"];
				}

				if($original_file_details["mime"] == "image/gif") {
					$new_img_resource = @imagecreate($new_file_width, $new_file_height);
				} else {
					$new_img_resource = @imagecreatetruecolor($new_file_width, $new_file_height);
				}

				if($new_img_resource) {
					if(@imagecopyresampled($new_img_resource, $original_img_resource, 0, 0, 0, 0, $new_file_width, $new_file_height, $original_file_width, $original_file_height)) {
						switch($original_file_details["mime"]) {
							case "image/pjpeg":
							case "image/jpeg":
							case "image/jpg":
								if(!@imagejpeg($new_img_resource, $new_file, $img_quality)) {
									return false;
								}
								break;
							case "image/png":
								if(!@imagepng($new_img_resource, $new_file)) {
									return false;
								}
								break;
							case "image/gif":
								if(!@imagegif($new_img_resource, $new_file)) {
									return false;
								}
								break;
							default :
								return false;
								break;
						}

						@chmod($new_file, 0644);

						/**
						 * Frees the memory this used, so it can be used again for the thumbnail.
						 */
						@imagedestroy($original_img_resource);
						@imagedestroy($new_img_resource);
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			if (!DEMO_MODE) {
				if(@move_uploaded_file($original_file, $new_file)) {
					@chmod($new_file, 0644);

					/**
					 * Create the new width / height so we can use the same variables
					 * below for thumbnail generation.
					 */
					$new_file_width		= $original_file_width;
					$new_file_height	= $original_file_height;
				} else {
					return false;
				}
			} else {
				if(@copy(DEMO_PHOTO, $new_file)) {
					@chmod($new_file, 0644);

					/**
					 * Create the new width / height so we can use the same variables
					 * below for thumbnail generation.
					 */
					$new_file_width		= $original_file_width;
					$new_file_height	= $original_file_height;
				} else {
					return false;
				}
			}
		}

		/**
		 * Check that the new_file exists, and can be used, then proceed
		 * with Thumbnail generation ($new_file-thumbnail).
		 */
		if((@file_exists($new_file)) && (@is_readable($new_file))) {
			$cropped_size = $VALID_MAX_DIMENSIONS["thumb"];

			switch($original_file_details["mime"]) {
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$original_img_resource = @imagecreatefromjpeg($new_file);
					break;
				case "image/png":
					$original_img_resource = @imagecreatefrompng($new_file);
					break;
				case "image/gif":
					$original_img_resource = @imagecreatefromgif($new_file);
					break;
				default :
					return false;
					break;
			}

			if(($new_file_width > $VALID_MAX_DIMENSIONS["thumb"]) || ($new_file_height > $VALID_MAX_DIMENSIONS["thumb"])) {
				$dest_x			= 0;
				$dest_y			= 0;
				$ratio_orig		= ($new_file_width / $new_file_height);
				$cropped_width	= $VALID_MAX_DIMENSIONS["thumb"];
				$cropped_height	= $VALID_MAX_DIMENSIONS["thumb"];

				if($ratio_orig > 1) {
					$cropped_width	= ($cropped_height * $ratio_orig);
				} else {
					$cropped_height	= ($cropped_width / $ratio_orig);
				}
			} else {
				$cropped_width	= $new_file_width;
				$cropped_height	= $new_file_height;

				$dest_x			= ($VALID_MAX_DIMENSIONS["thumb"] / 2) - ($cropped_width / 2);
				$dest_y			= ($VALID_MAX_DIMENSIONS["thumb"] / 2) - ($cropped_height / 2 );
			}

			if($original_file_details["mime"] == "image/gif") {
				$new_img_resource = @imagecreate($VALID_MAX_DIMENSIONS["thumb"], $VALID_MAX_DIMENSIONS["thumb"]);
			} else {
				$new_img_resource = @imagecreatetruecolor($VALID_MAX_DIMENSIONS["thumb"], $VALID_MAX_DIMENSIONS["thumb"]);
			}

			if($new_img_resource) {
				if(@imagecopyresampled($new_img_resource, $original_img_resource, $dest_x, $dest_y, 0, 0, $cropped_width, $cropped_height, $new_file_width, $new_file_height)) {
					switch($original_file_details["mime"]) {
						case "image/pjpeg":
						case "image/jpeg":
						case "image/jpg":
							if(!@imagejpeg($new_img_resource, $new_file."-thumbnail", $img_quality)) {
								return false;
							}
							break;
						case "image/png":
							if(!@imagepng($new_img_resource, $new_file."-thumbnail")) {
								return false;
							}
							break;
						case "image/gif":
							if(!@imagegif($new_img_resource, $new_file."-thumbnail")) {
								return false;
							}
							break;
						default :
							return false;
							break;
					}

					@chmod($new_file."-thumbnail", 0644);

					/**
					 * Frees the memory this used, so it can be used again.
					 */
					@imagedestroy($original_img_resource);
					@imagedestroy($new_img_resource);

					return true;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Function takes the original file that someone uploads in to the assignment
 * module and moves it to the correct storage location.
 *
 * Note: It _will not_ overwrite existing files, because it shouldn't
 * every file should be a unique ID a la version control.
 *
 * @param string $original_file
 * @param int $csfversion_id
 * @return bool
 */
function assignments_process_file($original_file, $afversion_id = 0) {
	if((!@file_exists($original_file)) || (!@is_readable($original_file))) {
		return false;
	}

	if(!$afversion_id = (int) $afversion_id) {
		return false;
	}

	if(!@file_exists($new_file = FILE_STORAGE_PATH."/A".$afversion_id)) {
		if (!DEMO_MODE) {
			if(@move_uploaded_file($original_file, $new_file)) {
				@chmod($new_file, 0644);
				return true;
			} else {
				return false;
			}
		} else {
			if (copy(DEMO_ASSIGNMENT, $new_file)) {
				@chmod($new_file, 0644);
				return true;
			} else {
				return false;
			}
		}
	}

	return false;
}

/**
 * Function takes the original file that someone uploads in the shares
 * module and moves it to the correct storage location.
 *
 * Note: It _will not_ overwrite existing files, because it shouldn't
 * every file should be a unique ID a la version control.
 *
 * @param string $original_file
 * @param int $csfversion_id
 * @return bool
 */
function communities_shares_process_file($original_file, $csfversion_id = 0) {
	global $COMMUNITY_ID;

	if((!@file_exists($original_file)) || (!@is_readable($original_file))) {
		return false;
	}

	if(!$csfversion_id = (int) $csfversion_id) {
		return false;
	}

	if(!@file_exists($new_file = COMMUNITY_STORAGE_DOCUMENTS."/".$csfversion_id)) {
		if (!DEMO_MODE) {
			if(@move_uploaded_file($original_file, $new_file)) {
				@chmod($new_file, 0644);
				return true;
			}
		} else {
			if(@copy(DEMO_FILE, $new_file)) {
				@chmod($new_file, 0644);
				return true;
			}
		}
	}

	return false;
}

/**
 * Function takes the original file that someone uploads in the discussion
 * module and moves it to the correct storage location.
 *
 * Note: It _will not_ overwrite existing files, because it shouldn't
 * every file should be a unique ID a la version control.
 *
 * @param string $original_file
 * @param int $csfversion_id
 * @return bool
 */
function communities_discussion_process_file($original_file, $csfversion_id = 0) {
	global $COMMUNITY_ID;

	if((!@file_exists($original_file)) || (!@is_readable($original_file))) {
		return false;
	}

	if(!$csfversion_id = (int) $csfversion_id) {
		return false;
	}

	if(!@file_exists($new_file = COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION."/".$csfversion_id)) {
		if (!DEMO_MODE) {
			if(@move_uploaded_file($original_file, $new_file)) {
				@chmod($new_file, 0644);
				return true;
			}
		} else {
			if(@copy(DEMO_FILE, $new_file)) {
				@chmod($new_file, 0644);
				return true;
			}
		}
	}

	return false;
}

/**
 * This function handles sorting and ordering for the community modules.
 *
 * @param string $field_id
 * @param string $field_name
 * @return string
 */
function communities_order_link($field_id, $field_name) {
	global $COMMUNITY_ID, $COMMUNITY_URL, $PAGE_URL;

	if(strtolower($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"]) == strtolower($field_id)) {
		if(strtolower($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]) == "desc") {
			return "<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?".replace_query(array("so" => "asc"), false)."\" title=\"Order by ".$field_name.", Sort Ascending\">".$field_name."</a>";
		} else {
			return "<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?".replace_query(array("so" => "desc"), false)."\" title=\"Order by ".$field_name.", Sort Decending\">".$field_name."</a>";
		}
	} else {
		return "<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?".replace_query(array("sb" => $field_id), false)."\" title=\"Order by ".$field_name."\">".$field_name."</a>";
	}
}
/**
 * This function recieves a page URL and ID from a community page that has been
 * moved from its' current location and sets the children of that page to have
 * the correct URL based on their new location.
 *
 * @param int $parent_id
 * @param string $parent_url
 */
function communities_set_children_urls($parent_id, $parent_url) {
	global $ERROR, $ERRORSTR, $COMMUNITY_RESERVED_PAGES, $db;

	$child_data = array();
	$query = "SELECT * FROM `community_pages` WHERE `parent_id` = ".$db->qstr($parent_id)." AND `page_active` = '1'";
	$child_records = $db->GetAll($query);
	if ($child_records) {
		foreach ($child_records as $child_record) {
			$page_url = clean_input($child_record["menu_title"], array("lower","underscores","page_url"));
			$page_url = $parent_url . "/" . $page_url;
			if(in_array($page_url, $COMMUNITY_RESERVED_PAGES)) {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Menu Title</strong> you have chosen is reserved, please try again.";
			} else {
				$child_data["page_url"] = $page_url;
				$query	= "SELECT * FROM `community_pages` WHERE `page_url` = ".$db->qstr($child_data["page_url"], get_magic_quotes_gpc())." AND `page_active` = '1' AND `community_id` = ".$db->qstr($child_record["community_id"])." AND `cpage_id` != ".$db->qstr($child_record["cpage_id"]);
				$result	= $db->GetRow($query);
				if($result) {
					$ERROR++;
					$ERRORSTR[] = "The new <strong>Page URL</strong> already exists in this community; Page URLs must be unique.";
				} else {
					if ($db->AutoExecute("community_pages", $child_data, "UPDATE", "cpage_id = ".$child_record["cpage_id"])) {
						if ($db->GetRow("SELECT * FROM `community_pages` WHERE `parent_id` = ".$db->qstr($child_record["cpage_id"]))) {
							communities_set_children_urls($child_record["cpage_id"], $child_data["page_url"]);
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "There was an error changing a child page URL. Database said: ".$db->ErrorMsg();
					}
				}
			}
		}
	}
}

/**
 * Functions used by the clerkship module are below.
 *
 */

/**
 * This function generates a formatted category title based on the hierarchical
 * child / parent / grandparent relationship of the categories table.
 *
 * @param unknown_type $category_id
 * @param unknown_type $levels
 * @return unknown
 */
function clerkship_categories_title($category_id = 0, $levels = 3) {
	global $db;

	$output	= array();
	$level	= 1;

	if((!$levels = (int) $levels) || ($levels > 15)) {
		$levels = 3;
	}

	if($category_id = (int) $category_id) {
		for($level = 1; $level <= $levels; $level++) {
			if($level == 1) {
				$query = "SELECT `category_name`, `category_parent` FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `category_id` = ".$db->qstr($category_id);
			} else {
				$query = "SELECT `category_name`, `category_parent` FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `category_id` = ".$db->qstr((int) $result["category_parent"]);
			}

			$result	= $db->GetRow($query);
			if(($result) && (trim($result["category_name"]))) {
				$output[] = $result["category_name"];
			}
		}

		if((is_array($output)) && (count($output))) {
			return html_entity_decode(implode(" &gt; ", array_reverse($output)));
		}
	}

	return false;
}

/**
 * This function will return the name of a region based on it's ID.
 *
 * @param int $region_id
 * @return string
 */
function clerkship_region_name($region_id = 0) {
	global $db;

	if($region_id = (int) $region_id) {
		$query	= "SELECT `region_name` FROM `".CLERKSHIP_DATABASE."`.`regions` WHERE `region_id` = ".$db->qstr($region_id);
		$result	= $db->GetRow($query);
		if($result) {
			return $result["region_name"];
		}
	}

	return false;
}

/**
 * This function returns all rotation ids which the current user has access to.
 *
 * @return array of integers
 */
function clerkship_rotations_access() {
	global $db, $ENTRADA_ACL;
	$query = "	SELECT a.`course_id`, a.`rotation_id`, b.`organisation_id`
				FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
				LEFT JOIN `courses` AS b
				ON a.`course_id` = b.`course_id`";
	$courses = $db->GetAll($query);
	$rotation_ids = array();
	if (is_array($courses) && count($courses)) {
		foreach ($courses as $course) {
			if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($course["course_id"], $course["organisation_id"]), 'update')) {
				$rotation_ids[] = $course["rotation_id"];
			}
		}
	}
	return $rotation_ids;
}
/**
 * Function will return all pages below the specified parent_id, the current user has access to.
 *
 * @param int $identifier
 * @param int $indent
 * @return string
 */
function communities_pages_inlists($identifier = 0, $indent = 0, $options = array(), $locked_ids = array()) {
	global $db, $COMMUNITY_ID, $COMMUNITY_URL;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	$selected				= 0;
	$selectable_children	= true;

	if(is_array($options)) {
		if((isset($options["selected"])) && ($tmp_input = clean_input($options["selected"], array("nows", "int")))) {
			$selected = $tmp_input;
		}

		if(isset($options["selectable_children"])) {
			$selectable_children = (bool) $options["selectable_children"];
		}

		if(isset($options["id"])) {
			$ul_id = $options["id"];
			$options["id"] = null;
		}
	}

	$identifier	= (int) $identifier;
	$output = "";

	if ($identifier && ($indent === 0)) {
		$query = "SELECT `cpage_id`, `page_url`, `menu_title`, `parent_id`, `page_visible`, `page_type` FROM `community_pages` WHERE `community_id` = ".$COMMUNITY_ID." AND `cpage_id` = ".$db->qstr((int) $identifier)." AND `page_url` != '0' AND `page_active` = '1' ORDER BY `page_order` ASC";
	} else {
		$query = "SELECT `cpage_id`, `page_url`, `menu_title`, `parent_id`, `page_visible`, `page_type` FROM `community_pages` WHERE `community_id` = ".$COMMUNITY_ID." AND `parent_id` = ".$db->qstr((int) $identifier)." AND `page_url` != '' AND `page_active` = '1' ORDER BY `page_order` ASC";
	}

	$results = $db->GetAll($query);
	if ($results) {
		$output .= "<ul class=\"community-page-list\" ".(isset($ul_id) ? "id = \"".$ul_id."\"" : "").">";
		foreach ($results as $result) {
			$output .= "<li id=\"content_".$result["cpage_id"]."\">";
			$output .= "    <div class=\"community-page-container\">";
			if (($indent > 0) && (!$selectable_children)) {
				$output .= "    <span class=\"delete\">&nbsp;</span>\n";
				$output .= "    <span class=\"".(((int) $result["page_visible"]) == 0 ? "hidden-page " : "")."next off\">" . html_encode($result["menu_title"])."</span>";
			} else {
				$output .= "    <span class=\"delete\">".(!in_array($result["cpage_id"], $locked_ids) ? "<input type=\"checkbox\" id=\"delete_".$result["cpage_id"]."\" name=\"delete[]\" value=\"".$result["cpage_id"]."\"".(($selected == $result["cpage_id"]) ? " checked=\"checked\"" : "")." />" : "<div class=\"locked-spacer\">&nbsp;</div>")."</span>\n";
				$output .= "    <span class=\"".(((int) $result["page_visible"]) == 0 ? "hidden-page " : "")."next\">";
				$output .= "        <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":pages?".replace_query(array("action" => "edit", "step" => 1, "page" => $result["cpage_id"]))."\">" . html_encode($result["menu_title"])."</a>";
				$output .= "    </span>";
			}
			$output .= "    </div>";
			$output .= communities_pages_inlists($result["cpage_id"], $indent + 1, $options, $locked_ids);
			$output .= "</li>\n";

		}
		$output .= "</ul>";
	} else {
		$output .= "<ul class=\"community-page-list empty\"></ul>";
	}

	return $output;
}

function communities_pages_inradio($identifier = 0, $indent = 0, $options = array(), $locked_ids = array()) {
	global $db, $COMMUNITY_ID, $COMMUNITY_URL;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	$selected				= 0;
	$selectable_children	= true;

	if(is_array($options)) {
		if((isset($options["selected"])) && ($tmp_input = clean_input($options["selected"], array("nows", "int")))) {
			$selected = $tmp_input;
		}

		if(isset($options["selectable_children"])) {
			$selectable_children = (bool) $options["selectable_children"];
		}

		if(isset($options["id"])) {
			$ul_id = $options["id"];
			$options["id"] = null;
		}
		if(isset($options["nav_type"])) {
			$nav_type = $options["nav_type"];
		}
		if(isset($options["parent_swap"])) {
			$new_parent = $options["parent_swap"]["parent_id"];
			$page_id = $options["parent_swap"]["page_id"];
		}
	}

	$identifier	= (int) $identifier;
	$output		= "";

	if(($identifier) && ($indent === 0)) {
		$query	= "SELECT `cpage_id`, `page_url`, `menu_title`, `parent_id`, `page_visible`, `page_type` FROM `community_pages` WHERE `community_id` = ".$COMMUNITY_ID." AND `cpage_id` = ".$db->qstr((int) $identifier)." AND `page_url` != '0' AND `page_active` = '1' ORDER BY `page_order` ASC";
	} else {
		if (!isset($options["parent_swap"])) {
			$query	= "SELECT `cpage_id`, `page_url`, `menu_title`, `parent_id`, `page_visible`, `page_type` FROM `community_pages` WHERE `community_id` = ".$COMMUNITY_ID." AND `parent_id` = ".$db->qstr((int) $identifier)." AND `page_active` = '1' ORDER BY `page_order` ASC";
		} else {
			$query	= "SELECT `cpage_id`, `page_url`, `menu_title`, `parent_id`, `page_visible`, `page_type`
						FROM `community_pages`
						WHERE `community_id` = ".$COMMUNITY_ID."
						AND `parent_id` = ".$db->qstr((int) $identifier)."
						AND `cpage_id` != ".$db->qstr($page_id)."
						AND `page_active` = '1'
						ORDER BY `page_order` ASC";
		}
	}

	$results	= $db->GetAll($query);
	if (isset($options["parent_swap"]) && $options["parent_swap"] && $identifier == $new_parent && $page_id) {
		$query	= "SELECT `cpage_id`, `page_url`, `menu_title`, `parent_id`, `page_visible`, `page_type`
					FROM `community_pages`
					WHERE `community_id` = ".$COMMUNITY_ID."
					AND `cpage_id` = ".$db->qstr((int) $page_id)."
					AND `page_url` != '0'
					AND `page_active` = '1'
					ORDER BY `page_order` ASC";
		$additional_page = $db->GetRow($query);
		array_push($results, $additional_page);
	}
	if($results) {
		$output .= "<ul class=\"community-page-list\" ".(isset($ul_id) ? "id = \"".$ul_id."\"" : "").">";
		foreach ($results as $result) {
			$output .= "<li id=\"content_".$ul_id."_".$result["cpage_id"]."\"".(!($identifier) || ($indent !== 0) ? " class=\"parent_".$identifier."\"" : "").">\n";
			$output .= "<div class=\"community-page-container\">";
			if(($indent > 0) && (!$selectable_children)) {
				$output .= "	<span class=\"delete\">&nbsp;</span>\n";
				$output .= "	<span class=\"".(((int) $result["page_visible"]) == 0 ? "hidden-page " : "")."next off\">".
								html_encode($result["menu_title"])."</span>\n";
			} else {
				$output .= "	<span class=\"delete\">".(array_search($result["cpage_id"], $locked_ids) === false ? "<input type=\"radio\" id=\"nav_" . $nav_type . "_page_id".$result["cpage_id"]."\" name=\"nav_" . $nav_type . "_page_id\" value=\"".$result["cpage_id"]."\"".(($selected == $result["cpage_id"]) ? " checked=\"checked\"" : "")." />" : "<div class=\"locked-spacer\">&nbsp;</div>")."</span>\n";
				$output .= "	<span class=\"".(((int) $result["page_visible"]) == 0 ? "hidden-page " : "")."next\">
								<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":pages?".replace_query(array("action" => "edit", "step" => 1, "page" => $result["cpage_id"]))."\">".
								html_encode($result["menu_title"])."</a></span>\n";
			}
			$output .= "</div>";
			$options["id"] = $ul_id;
			$output .= communities_pages_inradio($result["cpage_id"], $indent + 1, $options);
			$output .= "</li>\n";

		}
		$output .= "</ul>";
	} else {
		$output .= "<ul class=\"community-page-list empty\"></ul>";
	}

	return $output;
}

/**
 * Function will return all pages below the specified parent_id, the current user has access to.
 *
 * @param int $identifier
 * @param int $indent
 * @return string
 */
function community_type_pages_inlists($community_type_id, $identifier = 0, $indent = 0, $options = array(), $checked_ids = array()) {
    global $db;

    if ($indent > 99) {
        die("Preventing infinite loop.");
    }

    $selectable_children = true;
    $community_type_id = (int) $community_type_id;
    $identifier	= (int) $identifier;
    $output = "";

    if ($community_type_id) {
        if (is_array($options)) {

            if (isset($options["selectable_children"])) {
                $selectable_children = (bool)$options["selectable_children"];
            }

            if (isset($options["id"])) {
                $ul_id = $options["id"];
                $options["id"] = null;
            }
        }

        $locked_ids = array();
        $query = "SELECT `ctpage_id` FROM `community_type_pages`
                    WHERE `type_id` = " . $db->qstr($community_type_id) . "
                    AND `type_scope` = 'organisation'
                    AND `lock_page` = 1";
        $locked_pages = $db->CacheGetAll(CACHE_TIMEOUT, $query);
        if ($locked_pages) {
            foreach ($locked_pages as $locked_page) {
                $locked_ids[] = $locked_page["ctpage_id"];
            }
        }

        if ($identifier && ($indent === 0)) {
            $query = "SELECT `ctpage_id`, `page_url`, `menu_title`, `parent_id`, `page_visible`, `page_type` FROM `community_type_pages` WHERE `type_id` = " . $community_type_id . " AND `type_scope` = 'organisation' AND `ctpage_id` = " . $db->qstr((int)$identifier) . " AND `page_url` != '0' AND `page_active` = '1' ORDER BY `page_order` ASC";
        } else {
            $query = "SELECT `ctpage_id`, `page_url`, `menu_title`, `parent_id`, `page_visible`, `page_type` FROM `community_type_pages` WHERE `type_id` = " . $community_type_id . " AND `type_scope` = 'organisation' AND `parent_id` = " . $db->qstr((int)$identifier) . " AND `page_active` = '1' ORDER BY `page_order` ASC";
        }
        $results = $db->GetAll($query);
        if ($results) {
            $output .= "<ul class=\"community-page-list\" " . (isset($ul_id) ? "id = \"" . $ul_id . "\"" : "") . ">";
            foreach ($results as $result) {
                $output .= "<li id=\"content_" . $result["ctpage_id"] . "\">\n";
                $output .= "<div class=\"community-page-container\">";
                if (($indent > 0) && !$selectable_children) {
                    $output .= "	<span class=\"include\">&nbsp;</span>\n";
                    $output .= "	<span class=\"" . (((int)$result["page_visible"]) == 0 ? "hidden-page " : "") . "next off\">" . html_encode($result["menu_title"]) . "</span>\n";
                } else {
                    $output .= "	<span class=\"include\"><input type=\"checkbox\" id=\"page_" . $result["ctpage_id"] . "\" name=\"page_ids[]\" value=\"" . $result["ctpage_id"] . "\"" . (array_search($result["ctpage_id"], $locked_ids) !== false || array_search($result["ctpage_id"], $checked_ids) !== false ? " checked=\"checked\"" : "") . (array_search($result["ctpage_id"], $locked_ids) !== false ? " disabled=\"disabled\"" : "") . " /></span>\n";
                    $output .= "	<span class=\"" . (((int)$result["page_visible"]) == 0 ? "hidden-page " : "") . "next\">" . html_encode($result["menu_title"]) . "</span>\n";
                    if (array_search($result["ctpage_id"], $locked_ids) !== false) {
                        $output .= "	<input type=\"hidden\" name=\"page_ids[]\" value=\"" . $result["ctpage_id"] . "\" />";
                    }
                }
                $output .= "</div>";
                $output .= community_type_pages_inlists($community_type_id, $result["ctpage_id"], $indent + 1, $options);
                $output .= "</li>\n";
            }
            $output .= "</ul>";
        } else {
            $output .= "<ul class=\"community-page-list empty\"></ul>";
        }
    }

    return $output;
}

/**
 * Function will return all pages below the specified parent_id, the current user has access to.
 *
 * @param int $identifier
 * @param int $indent
 * @return string
 */
function communities_pages_intable($identifier = 0, $indent = 0, $options = array(), $locked_ids = array()) {
	global $db, $COMMUNITY_ID, $COMMUNITY_URL;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	$selected				= 0;
	$selectable_children	= true;

	if(is_array($options)) {
		if((isset($options["selected"])) && ($tmp_input = clean_input($options["selected"], array("nows", "int")))) {
			$selected = $tmp_input;
		}

		if(isset($options["selectable_children"])) {
			$selectable_children = (bool) $options["selectable_children"];
		}
	}


	$identifier	= (int) $identifier;
	$output		= "";

	if(($identifier) && ($indent === 0)) {
		$query	= "SELECT `cpage_id`, `page_url`, `menu_title`, `parent_id`, `page_visible`, `page_type` FROM `community_pages` WHERE `community_id` = ".$COMMUNITY_ID." AND `cpage_id` = ".$db->qstr((int) $identifier)." AND `page_url` != '0' AND `page_active` = '1' ORDER BY `page_order` ASC";
	} else {
		$query	= "SELECT `cpage_id`, `page_url`, `menu_title`, `parent_id`, `page_visible`, `page_type` FROM `community_pages` WHERE `community_id` = ".$COMMUNITY_ID." AND `parent_id` = ".$db->qstr((int) $identifier)." AND `page_url` != '' AND `page_active` = '1' ORDER BY `page_order` ASC";
	}

	$results	= $db->GetAll($query);
	if($results) {
		foreach ($results as $result) {
			if(($indent > 0) && (!$selectable_children)) {
				$output .= "<tr id=\"content_".$result["cpage_id"]."\">\n";
				$output .= "	<td>&nbsp;</td>\n";
				$output .= "	<td ".(((int) $result["page_visible"]) == 0 ? " class=\"hidden-page\"" : "")."style=\"padding-left: ".($indent * 25)."px; vertical-align: middle\"><img src=\"".ENTRADA_URL."/images/record-next-off.gif\" width=\"11\" height=\"11\" border=\"0\" alt=\"\" title=\"\" style=\"vertical-align: middle; margin-right: 5px\" />".html_encode($result["menu_title"])."</td>\n";
				$output .= "</tr>\n";
			} else {
				$output .= "<tr id=\"content_".$result["cpage_id"]."\">\n";
				$output .= "	<td>".(array_search($result["cpage_id"], $locked_ids) === false ? "<input type=\"checkbox\" id=\"delete_".$result["cpage_id"]."\" name=\"delete[]\" value=\"".$result["cpage_id"]."\" style=\"vertical-align: middle\"".(($selected == $result["cpage_id"]) ? " checked=\"checked\"" : "")." />" : "&nbsp;")."</td>\n";
				$output .= "	<td ".(((int) $result["page_visible"]) == 0 ? " class=\"hidden-page\"" : "")."style=\"padding-left: ".($indent * 25)."px; vertical-align: middle\"><img src=\"".ENTRADA_URL."/images/record-next-on.gif\" width=\"11\" height=\"11\" border=\"0\" alt=\"\" title=\"\" style=\"vertical-align: middle; margin-right: 5px\" /><a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":pages?".replace_query(array("action" => "edit", "step" => 1, "page" => $result["cpage_id"]))."\"".(($result["parent_id"] == 0) ? " style=\"font-weight: bold\"" : "").">".html_encode($result["menu_title"])."</a></td>\n";
				$output .= "</tr>\n";
			}

			$output .= communities_pages_intable($result["cpage_id"], $indent + 1, $options);
		}
	}

	return $output;
}

/**
 * Function will return all pages below the specified parent_id, as option elements of an input select.
 * This is a recursive function that has a fall-out of 99 runs.
 *
 * @param int $parent_id
 * @param array $current_selected
 * @param int $indent
 * @param array $exclude
 * @return string
 */
function communities_pages_inselect($parent_id = 0, &$current_selected, $indent = 0, &$exclude = array()) {
	global $db, $COMMUNITY_ID;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	if(!is_array($current_selected)) {
		$current_selected = array($current_selected);
	}

	$output	= "";
	$query	= "SELECT `cpage_id`, `menu_title`, `parent_id` FROM `community_pages` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_active` = '1' AND `parent_id` = ".$db->qstr($parent_id)." AND `page_url` != '' ORDER BY `page_order` ASC";
	$results	= $db->GetAll($query);
	if($results) {
		foreach ($results as $result) {
			if((!@in_array($result["cpage_id"], $exclude)) && (!@in_array($parent_id, $exclude))) {
				$output .= "<option value=\"".(int) $result["cpage_id"]."\"".((@in_array($result["cpage_id"], $current_selected)) ? " selected=\"selected\"" : "").">".str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $indent).(($indent > 0) ? "&rarr;&nbsp;" : "").html_encode($result["menu_title"])."</option>\n";
			} else {
				$exclude[] = (int) $result["cpage_id"];
			}
			$output .= communities_pages_inselect($result["cpage_id"], $current_selected, $indent + 1, $exclude);
		}
	}

	return $output;
}

/**
 * This function is used to return the children of the current page in an unordered
 * list used to navigate to children from parents.
 *
 * @param integer $page_id
 * @return string
 */
function communities_page_children_in_list($page_id = 0) {
	global $db, $COMMUNITY_ID, $COMMUNITY_URL, $USER_ACCESS;

	$output = "";

	if($page_id = (int) $page_id) {
		$access_query_condition = array();
		$access_query_condition[0] = "AND a.`allow_public_view` = '1'";
		$access_query_condition[1] = "AND a.`allow_troll_view` = '1'";
		$access_query_condition[2] = "AND a.`allow_member_view` = '1'";

		if ($USER_ACCESS == 1 && ((int) $db->GetOne("SELECT `community_registration` from `communities` WHERE `community_id` =".$db->qstr($community_id)." AND `community_protected` = '0'"))) {
			$USER_ACCESS = 0;
		}

		$query	= "	SELECT a.*
					FROM `community_pages` AS a
					WHERE a.`parent_id` = ".$db->qstr($page_id)."
					AND `page_active` = '1'
					AND `page_visible` = '1'
					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID). "
					".((isset($access_query_condition[$USER_ACCESS])) ? $access_query_condition[$USER_ACCESS] : "")."
					ORDER BY a.`page_order` ASC";
		$results	= $db->GetAll($query);
		if($results) {
			$output = "\n<ul class=\"child-nav\">";
			foreach ($results as $result) {
				if ($result["page_type"] == "url") {
					$query = "SELECT `option_value` FROM `community_page_options` WHERE `cpage_id` = ".$db->qstr($result["cpage_id"])." AND `option_title` = 'new_window'";
					$new_window = $db->GetOne($query);
				} else {
					$new_window = false;
				}
				$output .= "\n<li><a href=\"".ENTRADA_URL."/community".$COMMUNITY_URL.":".$result["page_url"]."\"".($new_window ? " target=\"_blank\"" : "")."> ".(strlen($result["menu_title"]) > 18 ? substr($result["menu_title"],0,15)."..." : $result["menu_title"] ) ." </a></li>";
			}
			$output .= "\n</ul>\n";
		} else {
			/*
			@todo I think that this just adds some unnecessary confusion to the page navigation.
			determine whether this is in fact the case. Basically what this code does is if
			the page has no children, it displays this $page_id's brothers and sisters.
			if($parent_id = communities_pages_fetch_parent_id($page_id)) {
				$query		= "	SELECT a.*
								FROM `community_pages` AS a
								WHERE a.`parent_id` = ".$db->qstr($parent_id)."
								AND `page_active` = '1'
								AND `page_visible` = '1'
								AND a.`community_id` = ".$db->qstr($COMMUNITY_ID). "
								".((isset($access_query_condition[$USER_ACCESS])) ? $access_query_condition[$USER_ACCESS] : "")."
								ORDER BY a.`page_order` ASC";
				$results	= $db->GetAll($query);
				if(($results) && (count($results) > 1)) {
					$output .= "\n<ul class=\"child-nav\">";
					foreach ($results as $result) {
						$output .= "\n<li".(($page_id == $result["cpage_id"]) ? " class=\"live\"" : "")."><a href=\"".ENTRADA_URL."/community".$COMMUNITY_URL.":".$result["page_url"]."\"> ".(strlen($result["menu_title"]) > 18 ? substr($result["menu_title"],0,15)."..." : $result["menu_title"] ) ." </a></li>";
					}
					$output .= "\n</ul>\n";
				}
			}
			*/
		}
	}

	return ((trim($output) != "") ? "\n<div class=\"child-menu\">\n".$output."\n</div>\n" : "");
}

/**
 * This recursive function will return all cpage_id's above the specified cpage_id as an array.
 *
 * @param int $parent_id
 * @return array
 */
function communities_pages_fetch_parents($parent_id = 0) {
	global $db, $COMMUNITY_ID;

	static $level	= 0;
	static $pages	= array();

	if($level > 99) {
		application_log("error", "Stopped an infinite loop in the communities_pages_fetch_parents() function.");

		return $pages;
	}

	if($parent_id = (int) $parent_id) {
		$query		= "SELECT `cpage_id`, `parent_id`, `menu_title`, `page_url` FROM `community_pages` WHERE `cpage_id` = ".$db->qstr($parent_id)." AND `page_active` = '1' AND `community_id` = ".$db->qstr($COMMUNITY_ID);
		$results	= $db->GetAll($query);
		if($results) {
			foreach ($results as $result) {
				$pages[$result["cpage_id"]]["url"] = $result["page_url"];
				$pages[$result["cpage_id"]]["title"] = $result["menu_title"];

				$level++;

				communities_pages_fetch_parents($result["parent_id"]);
			}
		}
	}

	return $pages;
}

/**
 * This function returns the parent_id of the provided page_id.
 *
 * @param int $cpage_id
 * @return int
 */
function communities_pages_fetch_parent_id($cpage_id = 0) {
	global $db, $COMMUNITY_ID;

	if($cpage_id = (int) $cpage_id) {
		$query	= "SELECT `parent_id` FROM `community_pages` WHERE `cpage_id` = ".$db->qstr($cpage_id)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
		$result	= $db->GetRow($query);
		if($result) {
			return $result["parent_id"];
		}
	}

	return 0;
}

/**
 * Function will return the number of sub-pages under the page_id you specify.
 *
 * @param int $parent_id
 * @param int $page_count
 * @param int $level
 * @return int
 */
function communities_pages_count($parent_id = 0, &$page_count, $level = 0) {
	global $db, $COMMUNITY_ID;

	if($level > 99) {
		die("Preventing infinite loop");
	}

	$query	= "SELECT `cpage_id` FROM `community_pages` WHERE `parent_id` = ".$db->qstr($parent_id)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_active` = '1' AND `page_url` != ''";
	$results	= $db->GetAll($query);
	if($results) {
		foreach ($results as $result) {
			$page_count = $page_count + 1;
			communities_pages_count($result["cpage_id"], $page_count, $level + 1);
		}
	}
	return $page_count;
}

/**
 * Function will move the groups with the $from_id, to the $to_id.
 *
 * @param int $from_id
 * @param int $to_id
 * @return bool
 */
function communities_pages_move($from_id = 0, $to_id = 0) {
	global $db, $COMMUNITY_ID;

	$result = false;

	if(($from_id = (int) $from_id) && ($to_id == 0 || $to_id = (int) $to_id)) {

		$query = "SELECT `cpage_id` FROM `community_pages` WHERE `parent_id` = ".$db->qstr($from_id)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_active` = '1' ORDER BY `page_order`";

		if($page_ids = $db->GetAll($query)) {

			if (!$new_order = $db->GetOne("SELECT MAX(`page_order`) as `new_order` FROM `community_pages` WHERE `parent_id` = ".$db->qstr($to_id)." AND `page_active` = '1' AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_url` != ''")) {
				$new_order = 1;
			}

			foreach ($page_ids as $page_id) {
				$query = "UPDATE `community_pages` SET `parent_id` = ".$db->qstr($to_id).", `page_order` = ".$db->qstr($new_order)." WHERE `cpage_id` = ".$db->qstr($page_id["cpage_id"])." AND `page_active` = '1'";
				if($db->Execute($query)) {
					$result = true;
				}else {
					$result = false;
					break;
				}
				$new_order++;
			}
		}
	}
	return $result;
}

/**
 * Function will delete all pages below the specified parent_id.
 *
 * @param int $parent_id
 * @return true
 */
function communities_pages_delete($cpage_id = 0, $exclude_ids = array()) {
	global $db, $COMMUNITY_ID;

	static $level = 0;

	if($level > 99) {
		application_log("error", "Stopped an infinite loop in the communities_pages_delete() function.");

		return false;
	}

	if($cpage_id = (int) $cpage_id) {
		if((!is_array($exclude_ids)) || (!in_array($cpage_id, $exclude_ids))) {
			$query		= "SELECT `cpage_id` FROM `community_pages` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_active` = '1' AND `parent_id` = ".$db->qstr($cpage_id);
			$results	= $db->GetAll($query);
			if($results) {
				foreach ($results as $result) {
					if((!is_array($exclude_ids)) || (!in_array($result["cpage_id"], $exclude_ids))) {
						$level++;

						communities_pages_delete($result["cpage_id"], $exclude_ids);
					}
				}
			}

			$query = "UPDATE `community_pages` SET `page_active` = '0', `page_url` = CONCAT(`page_url`, '.trash') WHERE `cpage_id` = ".$db->qstr($cpage_id)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_url` != ''";
			if(!$db->Execute($query)) {
				application_log("error", "Unable to deactivate cpage_id [".$cpage_id."] from community_id [".$COMMUNITY_ID."]. Database said: ".$db->ErrorMsg());
			} else {
				communities_deactivate_history($COMMUNITY_ID, $cpage_id, 0);
			}
		}
	}

	return true;
}

/**
 * This function adds the parent pages of the current page to the breadcrumb list,
 * as long as the current page has a parent id other than 0.
 *
 */
function communities_build_parent_breadcrumbs() {
	global $db, $COMMUNITY_ID, $PAGE_URL, $COMMUNITY_URL, $BREADCRUMB;

	$query	= "SELECT `cpage_id`, `parent_id` FROM `community_pages` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_url` = ".$db->qstr($PAGE_URL)." AND `page_active` = '1' AND `page_url` != ''";
	$result	= $db->GetRow($query);

	if($result) {
		$pages = communities_pages_fetch_parents($result["parent_id"]);
		if((is_array($pages)) && (count($pages))) {
			$pages = array_reverse($pages, true);
			foreach ($pages as $page) {
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$page["url"], "title" => $page["title"]);
			}
		}
	}
}

/**
 * This function is used by the polls module to pull the latest polling details
 * from the database to display on the index, etc.
 *
 * @param int $cpolls_id
 * @return array
 */
function communities_polls_latest($cpolls_id = 0) {
	global $db, $COMMUNITY_ID;

	$output					= array();
	$output["voters"]		= 0;
	$output["votes_cast"]	= 0;

	if($cpolls_id = (int) $cpolls_id) {
	// Get Count of admins since they can vote
		$query 	= "SELECT DISTINCT COUNT(`proxy_id`) AS counted_admins FROM `community_members` WHERE `community_id` = ".$db->qstr((int) $COMMUNITY_ID)." AND `member_acl` = '1'";

		if($newResult = $db->CacheGetRow(CACHE_TIMEOUT, $query)) {
			$output["voters"] = (int)$newResult["counted_admins"];
		}

		if($permissions = communities_polls_permissions($cpolls_id)) {
			if((int)$permissions['allow_member_vote'] == 1) {
			// Check to see if this poll has specific members voting only
			// If so count them and the admins only, otherwise count them all
				$query	= "SELECT DISTINCT COUNT(`proxy_id`) AS counted_members FROM `community_polls_access` WHERE `cpolls_id` = ".$db->qstr((int) $cpolls_id);

				$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
				if(is_array($result) && (int)$result["counted_members"] != 0) {
					$output["voters"] += (int)$result["counted_members"];
				} else {
				// No specific members so count them all
					$query	= "SELECT DISTINCT COUNT(`proxy_id`) AS counted_members FROM `community_members` WHERE `community_id` = ".$db->qstr((int) $COMMUNITY_ID). " AND `member_acl` = '0'";
					$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
					if($result) {
						$output["voters"] += (int)$result["counted_members"];
					}
				}
			}
		}
		/**
		 * Fetch the total number of votes cast so far.
		 * This is done by concatenating the proxy id of
		 * the voter and the time the vote was submitted and
		 * counting the number of distinct entries because
		 * all responses in one "vote" will have the same
		 * updated_date value.
		 */
		$query	= "SELECT COUNT(*) AS `total_votes`
		FROM `community_polls_results`, `community_polls_responses`
		WHERE `community_polls_responses`.`cpolls_id` = ".$db->qstr($cpolls_id)."
		AND `community_polls_responses`.`cpresponses_id` = `community_polls_results`.`cpresponses_id`";
		$query	= "SELECT DISTINCT (CONCAT_WS(' ', a.`proxy_id`, a.`updated_date`)) AS `record`
		FROM `community_polls_results` AS a
		LEFT JOIN `community_polls_responses` AS b
		ON a.`cpresponses_id` = b.`cpresponses_id`
		WHERE b.`cpolls_id` = ".$db->qstr($cpolls_id);
		$result	= $db->GetAll($query);
		if(($result)) {
			$output["votes_cast"] = (int) count($result);
		}
	}

	return $output;
}

/**
 * This function is used by the polls module to determine if specific community member access is set for a specific poll
 *
 * @param int $cpolls_id
 * @return array
 */
function communities_polls_specific_access($cpolls_id = 0) {
	global $db, $COMMUNITY_ID;

	$members				= array();

	if($cpolls_id = (int) $cpolls_id) {
		$query	= "SELECT `proxy_id` FROM `community_polls_access` WHERE `cpolls_id` = ".$db->qstr((int) $cpolls_id);
		$results	= $db->CacheGetAll(CACHE_TIMEOUT, $query);
		if($results) {
			foreach ($results as $result) {
				$members[]	= $result["proxy_id"];
			}
		}
	}

	return $members;
}

/**
 * This function is used by the polls module to gather member permissions masks
 *
 * @param int $cpolls_id
 * @return array
 */
function communities_polls_permissions($cpolls_id = 0) {
	global $db, $COMMUNITY_ID;

	$query = "SELECT `allow_member_read`, `allow_member_vote`, `allow_member_results`, `allow_member_results_after` FROM `community_polls`
	WHERE `cpolls_id` = ".$db->qstr($cpolls_id)."
	AND (`allow_member_read` = '1' OR `allow_member_vote` = '1' OR `allow_member_results` = '1' OR `allow_member_results_after` = '1')";

	$results	= $db->CacheGetRow(CACHE_TIMEOUT, $query);

	return $results;
}

/**
 * This function is used by the polls module to gather number of votes for a specific member
 *
 * @param int $cpolls_id
 * @return array
 */
function communities_polls_votes_cast_by_member($cpolls_id = 0, $proxy_id = 0) {
	global $db, $COMMUNITY_ID;

	$query = "SELECT COUNT(proxy_id) as `votes`
	FROM `community_polls_results`, `community_polls_responses`
	WHERE `community_polls_responses`.`cpolls_id` = ".$db->qstr($cpolls_id)."
	AND `community_polls_responses`.`cpresponses_id` = `community_polls_results`.`cpresponses_id`
	AND `proxy_id` = ".$db->qstr($proxy_id);

	$vote_record = $db->GetRow($query);

	return $vote_record;
}

/**
 * This function takes the HTML from the navigator_tabs() function, and transforms it so it can be used
 * as sidebar navigation in Bootstrap enabled Community Templates.
 * @param string $navigator_tabs
 * @return mixed|string
 */
function communities_entrada_navigation($navigator_tabs = "") {
    global $translate;

    /*
     * Transform a bit of the navigator_tabs() HTML.
     */
    $search = array(
        "<ul class=\"nav\">",
        "class=\"dropdown\"",
        " <b class=\"caret\"></b>",
    );
    $replace = array(
        "<ul class=\"nav nav-list\">",
        "class=\"dropdown-submenu pull-left\"",
        "",
    );

    $navigator_tabs = str_ireplace($search, $replace, $navigator_tabs);

    /*
     * Remove the last </ul>.
     */
    $navigator_tabs = substr($navigator_tabs, 0, -5);

    /*
     * Add a log out link.
     */
    $navigator_tabs .= "<li><a href=\"".ENTRADA_RELATIVE."/?action=logout\">".$translate->_("logout")."</a></li></ul>";
    
    return $navigator_tabs;
}

/**
 * These are functions related to the scorm module.
 */

/**
 * Delete a file or a directory (and its whole content)
 *
 * @param  - $filePath (String) - the path of file or directory to delete
 * @return - boolean - true if the delete succeed
 *		   boolean - false otherwise.
 */
function recursive_delete_file($filename) {
	if(is_file($filename)) {
		return unlink($filename);
	} elseif(is_dir($filename)) {
		if(!$handle = @opendir($filename)) {
			return false;
		}

		$filelist = array();

		while(false !== ($file = readdir($handle))) {
			if($file == "." || $file == "..") continue;

			$filelist[] = $filename."/".$file;
		}

		closedir($handle);

		if(count($filelist)) {
			foreach ($filelist as $remove) {
				if(!recursive_delete_file($remove)) {
					return false;
				}
			}
		}

		clearstatcache();

		if(is_writable($filename)) {
			return @rmdir($filename);
		} else {
			return false;
		}
	}
}

function fetch_mime_type($filename) {
	preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);

	switch(strtolower($fileSuffix[1])) {
		case "js" :
			return "application/x-javascript";

		case "json" :
			return "application/json";

		case "jpg" :
		case "jpeg" :
		case "jpe" :
			return "image/jpg";

		case "png" :
		case "gif" :
		case "bmp" :
		case "tiff" :
			return "image/".strtolower($fileSuffix[1]);

		case "css" :
			return "text/css";

		case "xml" :
			return "application/xml";

		case "doc" :
		case "docx" :
			return "application/msword";

		case "xls" :
		case "xlt" :
		case "xlm" :
		case "xld" :
		case "xla" :
		case "xlc" :
		case "xlw" :
		case "xll" :
			return "application/vnd.ms-excel";

		case "ppt" :
		case "pps" :
			return "application/vnd.ms-powerpoint";

		case "rtf" :
			return "application/rtf";

		case "pdf" :
			return "application/pdf";

		case "html" :
		case "htm" :
		case "php" :
			return "text/html";

		case "txt" :
			return "text/plain";

		case "mpeg" :
		case "mpg" :
		case "mpe" :
			return "video/mpeg";

		case "mp3" :
			return "audio/mpeg3";

		case "wav" :
			return "audio/wav";

		case "aiff" :
		case "aif" :
			return "audio/aiff";

		case "avi" :
			return "video/msvideo";

		case "wmv" :
			return "video/x-ms-wmv";

		case "mov" :
			return "video/quicktime";

		case "zip" :
			return "application/zip";

		case "tar" :
			return "application/x-tar";

		case "swf" :
			return "application/x-shockwave-flash";

		default :
			if(function_exists("mime_content_type")) {
				$fileSuffix = mime_content_type($filename);
			}

			return "unknown/" . trim($fileSuffix[0], ".");
	}
}

//function used to get the contents of a url
function file_get_contents_curl($url) {     
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CAINFO, "/usr/local/zend/lib/cacert.pem");
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * Processes / resizes and creates properly sized image and thumbnail image
 * for images uploaded to the galleries module.
 *
 * @param string $original_file
 * @param int $photo_id
 * @return bool
 */
function process_user_photo($original_file, $photo_id = 0, $type = "upload") {
	global $VALID_MAX_DIMENSIONS, $_SESSION, $ENTRADA_USER;

	if(!@function_exists("gd_info")) {
		return false;
	}

	if((!@file_exists($original_file)) || (!@is_readable($original_file))) {
		return false;
	}

	if(!$photo_id = (int) $photo_id) {
		return false;
	}

	if ($type !== "upload") {
	    $type = "official";
    }

	$new_file = STORAGE_USER_PHOTOS . "/" . $ENTRADA_USER->getID() . "-" . $type;
	$img_quality = 85;

	if($original_file_details = @getimagesize($original_file)) {
		$original_file_width = $original_file_details[0];
		$original_file_height = $original_file_details[1];

		/**
		 * Check if the original_file needs to be resized or not.
		 */
		if(!DEMO_MODE && (($original_file_width > $VALID_MAX_DIMENSIONS["photo-width"]) || ($original_file_height > $VALID_MAX_DIMENSIONS["photo-height"]))) {
			switch($original_file_details["mime"]) {
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$original_img_resource = @imagecreatefromjpeg($original_file);
					break;
				case "image/png":
					$original_img_resource = @imagecreatefrompng($original_file);
					break;
				case "image/gif":
					$original_img_resource = @imagecreatefromgif($original_file);
					break;
				default :
					return false;
					break;
			}
			if($original_img_resource) {
			/**
			 * Determine whether it's a horizontal / vertical image and calculate the new smaller size.
			 */
				if($original_file_width > $original_file_height) {
					$new_file_width		= $VALID_MAX_DIMENSIONS["photo-width"];
					$new_file_height	= (int) (($VALID_MAX_DIMENSIONS["photo-width"] * $original_file_height) / $original_file_width);
				} else {
					$new_file_width		= (int) (($VALID_MAX_DIMENSIONS["photo-height"] * $original_file_width) / $original_file_height);
					$new_file_height	= $VALID_MAX_DIMENSIONS["photo-height"];
				}

				if($original_file_details["mime"] == "image/gif") {
					$new_img_resource = @imagecreate($new_file_width, $new_file_height);
				} else {
					$new_img_resource = @imagecreatetruecolor($new_file_width, $new_file_height);
				}

				if($new_img_resource) {
					if(@imagecopyresampled($new_img_resource, $original_img_resource, 0, 0, 0, 0, $new_file_width, $new_file_height, $original_file_width, $original_file_height)) {
						switch($original_file_details["mime"]) {
							case "image/pjpeg":
							case "image/jpeg":
							case "image/jpg":
								if(!@imagejpeg($new_img_resource, $new_file, $img_quality)) {
									return false;
								}
							break;
							case "image/png":
								if(!@imagepng($new_img_resource, $new_file)) {
									return false;
								}
							break;
							case "image/gif":
								if(!@imagegif($new_img_resource, $new_file)) {
									return false;
								}
							break;
							default :
								return false;
							break;
						}

						@chmod($new_file, 0644);

						/**
						 * Frees the memory this used, so it can be used again for the thumbnail.
						 */
						@imagedestroy($original_img_resource);
						@imagedestroy($new_img_resource);
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			if (!DEMO_MODE) {
				if(@move_uploaded_file($original_file, $new_file)) {
					@chmod($new_file, 0644);

					/**
					 * Create the new width / height so we can use the same variables
					 * below for thumbnail generation.
					 */
					$new_file_width		= $original_file_width;
					$new_file_height	= $original_file_height;
				} else {
					return false;
				}
			} else {
				if(@copy(DEMO_PHOTO, $new_file)) {
					@chmod($new_file, 0644);

					/**
					 * Create the new width / height so we can use the same variables
					 * below for thumbnail generation.
					 */
					$new_file_width		= $original_file_width;
					$new_file_height	= $original_file_height;
				} else {
					return false;
				}
			}
		}

		/**
		 * Check that the new_file exists, and can be used, then proceed
		 * with Thumbnail generation ($new_file-thumbnail).
		 */
		if((@file_exists($new_file)) && (@is_readable($new_file))) {

			switch($original_file_details["mime"]) {
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$original_img_resource = @imagecreatefromjpeg($new_file);
					break;
				case "image/png":
					$original_img_resource = @imagecreatefrompng($new_file);
					break;
				case "image/gif":
					$original_img_resource = @imagecreatefromgif($new_file);
					break;
				default :
					return false;
					break;
			}

			if(($new_file_width > $VALID_MAX_DIMENSIONS["thumb-width"]) || ($new_file_height > $VALID_MAX_DIMENSIONS["thumb-height"])) {
				$dest_x			= 0;
				$dest_y			= 0;
				$ratio_orig		= ($new_file_width / $new_file_height);
				$cropped_width	= $VALID_MAX_DIMENSIONS["thumb-width"];
				$cropped_height	= $VALID_MAX_DIMENSIONS["thumb-height"];

				if($ratio_orig > 1) {
					$cropped_width	= ($cropped_height * $ratio_orig);
				} else {
					$cropped_height	= ($cropped_width / $ratio_orig);
				}
			} else {
				$cropped_width	= $new_file_width;
				$cropped_height	= $new_file_height;

				$dest_x			= ($VALID_MAX_DIMENSIONS["thumb-width"] / 2) - ($cropped_width / 2);
				$dest_y			= ($VALID_MAX_DIMENSIONS["thumb-height"] / 2) - ($cropped_height / 2 );
			}

			if($original_file_details["mime"] == "image/gif") {
				$new_img_resource = @imagecreate($VALID_MAX_DIMENSIONS["thumb-width"], $VALID_MAX_DIMENSIONS["thumb-height"]);
			} else {
				$new_img_resource = @imagecreatetruecolor($VALID_MAX_DIMENSIONS["thumb-width"], $VALID_MAX_DIMENSIONS["thumb-height"]);
			}

			if($new_img_resource) {
				if(@imagecopyresampled($new_img_resource, $original_img_resource, $dest_x, $dest_y, 0, 0, $cropped_width, $cropped_height, $new_file_width, $new_file_height)) {
					switch($original_file_details["mime"]) {
						case "image/pjpeg":
						case "image/jpeg":
						case "image/jpg":
							if(!@imagejpeg($new_img_resource, $new_file."-thumbnail", $img_quality)) {
								return false;
							}
						break;
						case "image/png":
							if(!@imagepng($new_img_resource, $new_file."-thumbnail")) {
								return false;
							}
						break;
						case "image/gif":
							if(!@imagegif($new_img_resource, $new_file."-thumbnail")) {
								return false;
							}
						break;
						default :
							return false;
						break;
					}

					@chmod($new_file."-thumbnail", 0644);

					/**
					 * Frees the memory this used, so it can be used again.
					 */
					@imagedestroy($original_img_resource);
					@imagedestroy($new_img_resource);

					return true;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function google_generate_id($firstname = "", $lastname = "") {
	global $db;

	$google_id	= false;

	$firstname	= clean_input($firstname, array("alpha", "lowercase"));
	$lastname	= clean_input($lastname, array("alpha", "lowercase"));

	if(($firstname) && ($lastname)) {
		$result	= true;
		$i		= 1;

		while (($result) && ($i <= strlen($firstname))) {
			$google_id = substr($firstname, 0, $i).$lastname;

			$query	= "SELECT `id` FROM `".AUTH_DATABASE."`.`user_data` WHERE `google_id` = ".$db->qstr($google_id);
			$result	= $db->GetRow($query);

			$i++;
		}

		if ($result) {
			$google_id = $firstname.".".$lastname;

			$query	= "SELECT `id` FROM `".AUTH_DATABASE."`.`user_data` WHERE `google_id` = ".$db->qstr($google_id);
			$result	= $db->GetRow($query);
		}

		$i = 1;
		while (($result) && ($i <= 100)) {
			$google_id = substr($firstname, 0, 1).$lastname.$i;

			$query	= "SELECT `id` FROM `".AUTH_DATABASE."`.`user_data` WHERE `google_id` = ".$db->qstr($google_id);
			$result	= $db->GetRow($query);

			$i++;
		}

		if ($result) {
			$google_id = false;
		}
	}

	return $google_id;
}

function google_create_id() {
	global $db, $GOOGLE_APPS, $AGENT_CONTACTS, $ERROR, $ERRORSTR, $ENTRADA_USER;

	if ((isset($GOOGLE_APPS)) && (is_array($GOOGLE_APPS)) && (isset($GOOGLE_APPS["active"])) && ((bool) $GOOGLE_APPS["active"])) {
		$query	= "	SELECT a.*, b.`group`, b.`role`
					FROM `".AUTH_DATABASE."`.`user_data` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
					ON a.`id` = b.`user_id`
					WHERE a.`id` = ".$db->qstr($ENTRADA_USER->getID());
		$result	= $db->GetRow($query);
		if ($result) {
			if ((isset($GOOGLE_APPS["groups"])) && (is_array($GOOGLE_APPS["groups"])) && (in_array($_SESSION["details"]["group"], $GOOGLE_APPS["groups"]))) {
				if (($result["google_id"] == "opt-out") || ($result["google_id"] == "opt-in") || ($result["google_id"] == "")) {
					if ($google_id = google_generate_id($result["firstname"], $result["lastname"])) {
						require_once "Zend/Loader.php";

						Zend_Loader::loadClass("Zend_Gdata_ClientLogin");
						Zend_Loader::loadClass("Zend_Gdata_Gapps");

						$firstname	= $result["firstname"];
						$lastname	= $result["lastname"];
						$password	= $result["password"];

						try {
							$client		= Zend_Gdata_ClientLogin::getHttpClient($GOOGLE_APPS["admin_username"], $GOOGLE_APPS["admin_password"], Zend_Gdata_Gapps::AUTH_SERVICE_NAME);
							$service	= new Zend_Gdata_Gapps($client, $GOOGLE_APPS["domain"]);
							$service->createUser($google_id, $firstname, $lastname, $password, "SHA-1");

							$search		= array("%FIRSTNAME%", "%LASTNAME%", "%GOOGLE_APPS_DOMAIN%", "%GOOGLE_ID%", "%GOOGLE_APPS_QUOTA%", "%APPLICATION_NAME%", "%ADMINISTRATOR_NAME%", "%ADMINISTRATOR_EMAIL%");
							$replace	= array($firstname, $lastname, $GOOGLE_APPS["domain"], $google_id, $GOOGLE_APPS["quota"], APPLICATION_NAME, $AGENT_CONTACTS["administrator"]["name"], $AGENT_CONTACTS["administrator"]["email"]);

							$subject	= str_replace($search, $replace, $GOOGLE_APPS["new_account_subject"]);
							$message	= str_replace($search, $replace, $GOOGLE_APPS["new_account_msg"]);

							$query = "UPDATE `".AUTH_DATABASE."`.`user_data` SET `google_id` = ".$db->qstr($google_id)." WHERE `id` = ".$db->qstr($ENTRADA_USER->getID());
							if ($db->Execute($query)) {
								if(@mail($_SESSION["details"]["email"], $subject, $message, "From: \"".$AGENT_CONTACTS["administrator"]["name"]."\" <".$AGENT_CONTACTS["administrator"]["email"].">\nReply-To: \"".$AGENT_CONTACTS["administrator"]["name"]."\" <".$AGENT_CONTACTS["administrator"]["email"].">")) {
									$_SESSION["details"]["google_id"] = $google_id;

									application_log("success", "Successfully sent new Google account notice to ".$_SESSION["details"]["email"]);

									return true;
								} else {
									application_log("error", "Unable to send new Google account notification to ".$_SESSION["details"]["email"]);

									throw new Exception();
								}
							} else {
								application_log("error", "Unable to update the google_id [".$google_id."] field for proxy_id [".$ENTRADA_USER->getID()."].");

								throw new Exception();
							}
						} catch (Zend_Gdata_Gapps_ServiceException $e) {
							if (is_array($e->getErrors())) {
								foreach ($e->getErrors() as $error) {
									application_log("error", "Unable to create google_id [".$google_id."] for username [".$_SESSION["details"]["username"]."]. Error details: [".$error->getErrorCode()."] ".$error->getReason().".");
								}
							}
						}
					} else {
						application_log("error", "google_generate_id() function returned false out of firstname [".$result["firstname"]." and lastname [".$result["lastname"]."].");
					}
				}
			} else {
				application_log("error", "google_create_id() failed because users group [".$_SESSION["details"]["group"]."] was not in the GOOGLE_APPS[groups].");
			}
		} else {
			application_log("error", "google_create_id() failed because we were unable to generate information on proxy_id [".$ENTRADA_USER->getID()."]. Database said: ".$db->ErrorMsg());
		}
	}

	$ERROR++;
	$ERRORSTR[] = "We apologize, but we were unable to create your <strong>".$GOOGLE_APPS["domain"]."</strong> account for you at this time.<br /><br />The system administrator has been notified of the error; please try again later.";

	return false;
}

function google_reset_password($password = "") {
	global $db, $GOOGLE_APPS, $ENTRADA_USER;

	if ((isset($GOOGLE_APPS)) && (is_array($GOOGLE_APPS)) && (isset($GOOGLE_APPS["active"])) && ((bool) $GOOGLE_APPS["active"]) && ($password)) {
		$query = "	SELECT a.*, b.`group`, b.`role`
					FROM `".AUTH_DATABASE."`.`user_data` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
					ON a.`id` = b.`user_id`
					WHERE a.`id` = ".$db->qstr($ENTRADA_USER->getID())."
					AND b.`app_id` = ".$db->qstr(AUTH_APP_ID);
		$result	= $db->GetRow($query);
		if ($result) {
			if (!in_array($result["google_id"], array("", "opt-out", "opt-in"))) {
				try {
					$client = Zend_Gdata_ClientLogin::getHttpClient($GOOGLE_APPS["admin_username"], $GOOGLE_APPS["admin_password"], Zend_Gdata_Gapps::AUTH_SERVICE_NAME);
					$service = new Zend_Gdata_Gapps($client, $GOOGLE_APPS["domain"]);

					$account = $service->retrieveUser($result["google_id"]);
					$account->login->password = $password;
					$account->save();

					application_log("success", "Successfully updated Google account password for google_id [".$result["google_id"]."] and proxy_id [".$ENTRADA_USER->getID()."].");

					return true;
				} catch (Zend_Gdata_Gapps_ServiceException $e) {
					application_log("error", "Unable to change password for google_id [".$google_id."] for proxy_id [".$ENTRADA_USER->getID()."]. Error details: [".$error->getErrorCode()."] ".$error->getReason().".");
					if (is_array($e->getErrors())) {
						foreach ($e->getErrors() as $error) {
							application_log("error", "Unable to change password for google_id [".$google_id."] for proxy_id [".$ENTRADA_USER->getID()."]. Error details: [".$error->getErrorCode()."] ".$error->getReason().".");
						}
					}
				}
			}
		} else {
			application_log("error", "google_reset_password() failed because we were unable to fetch information on proxy_id [".$ENTRADA_USER->getID()."]. Database said: ".$db->ErrorMsg());
		}
	}

	return false;
}

/**
 * Function takes minutes and converts them to hours.
 *
 * @param int $minutes
 * @return int
 */
function display_hours($minutes = 0) {
	if($minutes = (int) $minutes) {
		return round(($minutes / 60), 2);
	}

	return 0;
}

/**
 * This function generates the javascript for PlotKit's y axis labels.
 *
 * @param unknown_type $labels
 * @return unknown
 */
function plotkit_statistics_lables($labels = array()) {
	$output = array();

	if(is_array($labels)) {
		foreach ($labels as $key => $label) {
			$output[] = "{label: '".$label."', v: ".$key."}";
		}
	}

	return implode(", ", $output);
}

/**
 * This function generates the javascript for PlotKit's chart data.
 *
 * @param unknown_type $values
 * @return unknown
 */
function plotkit_statistics_values($values = array()) {
	$output = array();

	if(is_array($values)) {
		foreach ($values as $key => $value) {
			$output[] = "[".(int) $key.", ".$value."]";
		}
	}

	return implode(", ", $output);
}

/**
 * Insert notification and recipients into cron notification tables.
 *
 * @param array $user_ids
 * @param string $community
 * @param string $type
 * @param string $subject
 * @param string $message
 * @param string $url
 * @param bigint $release_time
 */
function post_notify($user_ids, $community, $type, $subject, $message, $url='', $release_time=0, $record_id=0, $author_id=0) {
	global $db;

	if(($db->AutoExecute("community_notifications", array("release_time" => ($release_time?$release_time:time()), "community" => $community,
		"type" => $type, "subject" => $subject, "body" => $message, "url" => $url, "record_id" => $record_id, "author_id" => $author_id), "INSERT")) && ($cnotification_id = $db->Insert_Id())) {
		foreach($user_ids as $user_id) {
			if(!$db->AutoExecute("cron_community_notifications", array("cnotification_id" => $cnotification_id, "proxy_id" => $user_id['proxy_id']), "INSERT")) {
				application_log("error", "Unable to insert the recipient for this post. Database said: ".$db->ErrorMsg());
				return ;
			}
		}
	} else {
		application_log("error", "Unable to insert this notification. Database said: ".$db->ErrorMsg());
	}
}

/**
 * Delete notification and recipients from cron notification tables.
 *
 * @param int    $cnotification_id
 */
function delete_notify($cnotification_id) {
	global $db;

	$query  = "DELETE FROM `cron_community_notifications` WHERE `cnotification_id` = ".$db->qstr($cnotification_id);
	if($db->Execute($query)) {
		$query  = "DELETE FROM `community_notifications` WHERE `cnotification_id` = ".$db->qstr($cnotification_id);
		if(!$db->Execute($query)) {
			application_log("error", "Failed to delete Post $cnotification_id from table `community_notifications`. Database said: ".$db->ErrorMsg());
		}
	} else
		application_log("error", "Failed to delete records with `cnotification_id` of $cnotification_id from table `cron_community_notifications`. Database said: ".$db->ErrorMsg());
}

/**
 * Delete individual notification.
 *
 * @param string $type
 */
function delete_notifications($types) {
	global $db;

	$query  = "SELECT `cnotification_id` FROM `community_notifications` WHERE `type` = ".$db->qstr($types);
	$result = $db->GetRow($query);
	if ($result) {
		delete_notify($result['cnotification_id']);
	}
}

/**
 * This function selects the group of users from the database who should be
 * receiving a notification for the chosen 'notify type', then adds a queued
 * notification to the database which will be sent out by a cron-job to each
 * user.
 *
 * @param int $community_id
 * @param int $record_id
 * @param string $notify_type
 * @return boolean
 */
function community_notify($community_id, $record_id, $content_type, $url, $permission_id = 0, $release_time = 0) {
	global $db, $ENTRADA_USER;

	/**
	 * Select the user permission level required to access the content which
	 * is the basis of the notification. Administrators of the community will
	 * always have access however - so they can sign up for notifications for
	 * any piece of content/page.
	*/
	switch ($content_type) {
		case "poll" :
			$query = "	SELECT a.`allow_member_read`, b.`allow_member_view`
						FROM `community_polls` AS a
						LEFT JOIN `community_pages` AS b
						ON a.`cpage_id` = b.`cpage_id`
						WHERE a.`cpolls_id` = ".$db->qstr($record_id);
			$result = $db->GetRow($query);
			if ($result["allow_member_read"] && $result["allow_member_view"]) {
				$permission_required = 0;
			} else {
				$permission_required = 1;
			}
			break;
		case "file" :
		case "file-revision" :
		case "file-comment" :
			$query = "	SELECT a.`allow_member_read`, b.`allow_member_view`
						FROM `community_shares` AS a
						LEFT JOIN `community_pages` AS b
						ON a.`cpage_id` = b.`cpage_id`
						WHERE a.`cshare_id` = ".$db->qstr($record_id);
			$result = $db->GetRow($query);
			if ($result["allow_member_read"] && $result["allow_member_view"]) {
				$permission_required = 0;
			} else {
				$permission_required = 1;
			}
			break;
		case "photo" :
		case "photo-comment" :
			$query = "	SELECT a.`allow_member_read`, b.`allow_member_view`
						FROM `community_galleries` AS a
						LEFT JOIN `community_pages` AS b
						ON a.`cpage_id` = b.`cpage_id`
						WHERE a.`cgallery_id` = ".$db->qstr($record_id);
			$result = $db->GetRow($query);
			if ($result["allow_member_read"] && $result["allow_member_view"]) {
				$permission_required = 0;
			} else {
				$permission_required = 1;
			}
			break;
		case "announcement" :
			$query = "	SELECT b.`allow_member_view`
						FROM `community_announcements` AS a
						LEFT JOIN `community_pages` AS b
						ON a.`cpage_id` = b.`cpage_id`
						WHERE a.`cannouncement_id` = ".$db->qstr($record_id);
			$result = $db->GetRow($query);
			if ($result["allow_member_view"]) {
				$permission_required = 0;
			} else {
				$permission_required = 1;
			}
			break;
		case "announcement_moderate" :
			$permission_required = 1;
		case "announcement_release" :
		case "announcement_delete" :
			$permission_required = 0;
		case "event" :
			$query = "	SELECT b.`allow_member_view`
						FROM `community_events` AS a
						LEFT JOIN `community_pages` AS b
						ON a.`cpage_id` = b.`cpage_id`
						WHERE a.`cevent_id` = ".$db->qstr($record_id);
			$result = $db->GetRow($query);
			if ($result["allow_member_view"]) {
				$permission_required = 0;
			} else {
				$permission_required = 1;
			}
			break;
        case "file-db":
		case "post" :
			$query = "	SELECT a.`allow_member_read`, b.`allow_member_view`
						FROM `community_discussions` AS a
						LEFT JOIN `community_pages` AS b
						ON a.`cpage_id` = b.`cpage_id`
                        JOIN `community_discussion_topics` AS c
                        ON c.`cdiscussion_id` = a.`cdiscussion_id`
						WHERE c.`cdtopic_id` = ".$db->qstr($record_id);
			$result = $db->GetRow($query);
			if ($result["allow_member_read"] && $result["allow_member_view"]) {
				$permission_required = 0;
			} else {
				$permission_required = 1;
			}
			break;
		case "reply" :
			$query = "	SELECT a.`allow_member_read`, b.`allow_member_view`
						FROM `community_discussions` AS a
						LEFT JOIN `community_pages` AS b
						ON a.`cpage_id` = b.`cpage_id`
						JOIN `community_discussion_topics` AS c
						ON a.`cdiscussion_id` = c.`cdiscussion_id`
						WHERE c.`cdtopic_id` = ".$db->qstr($record_id);
			$result = $db->GetRow($query);
			if ($result["allow_member_read"] && $result["allow_member_view"]) {
				$permission_required = 0;
			} else {
				$permission_required = 1;
			}
			break;
		default :
			$permission_required = 1;
			break;
	}

	/**
	 * Select which users will be sent a notification based on the
	 * type of notification and the user's notification setting for
	 * the selected piece of content in the selected community.
	 */
	switch ($content_type) {
		case "announcement" :
		case "announcement_moderate" :
		case "event" :
			$query = "	SELECT a.`proxy_id` FROM `community_members` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						WHERE a.`proxy_id` NOT IN (
													SELECT `proxy_id` FROM `community_notify_members`
													WHERE `community_id` = ".$db->qstr($community_id)."
													AND `record_id` = ".$db->qstr($permission_id)."
													AND `notify_type` = ".$db->qstr($content_type)."
													AND `notify_active` = '0'
												)
                        AND a.`member_active` = 1
						AND a.`community_id` = ".$db->qstr($community_id)."
						AND b.`notifications` = '1'
						AND a.`member_acl` >= ".$db->qstr($permission_required);
			break;
		case "announcement_release" :
		case "announcement_delete" :
			$query = "	SELECT a.`proxy_id`
						FROM `community_announcements` AS a
						WHERE a.`cannouncement_id` = ".$db->qstr($record_id);

			break;
		case "poll" :
			$query = "	SELECT COUNT(`proxy_id`) AS `members_count`
						FROM `community_polls_access`
						WHERE `cpolls_id` = ".$db->qstr($record_id);
			$members_count	= $db->GetOne($query);
			if (isset($members_count) && $members_count) {
				$query = "	SELECT a.`proxy_id` FROM `community_members` AS a
							LEFT JOIN `community_notify_members` AS b
							ON b.`proxy_id` = a.`proxy_id`
							AND b.`notify_type` = ".$db->qstr($content_type)."
							AND b.`community_id` = a.`community_id`
							AND b.`record_id` = ".$db->qstr($permission_id)."
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
							ON a.`proxy_id` = c.`id`
							WHERE a.`proxy_id` IN (
													SELECT `proxy_id`
													FROM `community_polls_access`
													WHERE `cpolls_id` = ".$db->qstr($record_id)."
												)
                            AND a.`member_active` = 1
							AND b.`notify_active` != '0'
							AND a.`community_id` = ".$db->qstr($community_id)."
							AND c.`notifications` = '1'
							AND a.`member_acl` >= ".$db->qstr($permission_required);
			} else {
				$query = "	SELECT `proxy_id` FROM `community_members` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON a.`proxy_id` = b.`id`
							WHERE a.`proxy_id` NOT IN (
														SELECT `proxy_id` FROM `community_notify_members`
														WHERE `community_id` = ".$db->qstr($community_id)."
														AND `record_id` = ".$db->qstr($permission_id)."
														AND `notify_type` = ".$db->qstr($content_type)."
														AND `notify_active` = '0'
													)
                            AND a.`member_active` = 1
							AND a.`community_id` = ".$db->qstr($community_id)."
							AND b.`notifications` = '1'
							AND a.`member_acl` >= ".$db->qstr($permission_required);
			}
			break;
		case "join" :
		case "leave" :
			$query = "	SELECT `proxy_id` FROM `community_members` AS a
						JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						WHERE a.`proxy_id` IN (
												SELECT `proxy_id` FROM `community_notify_members`
												WHERE `community_id` = ".$db->qstr($community_id)."
												AND `record_id` = ".$db->qstr($community_id)."
												AND `notify_type` = 'members'
												AND `notify_active` = '1'
											)
                        AND a.`member_active` = 1
						AND a.`member_acl` = '1'
						AND b.`notifications` = '1'
						AND a.`community_id` = ".$db->qstr($community_id);
			break;
		case "reply" :
			/**
			 * Selects the ids of the community members who are subscribed to this particular post,
			 * and does a union with the ids of the community members who are subscribed to the 
			 * discussion board in general.
			 */
			$query = "	SELECT DISTINCT(a.`proxy_id`) FROM `community_members` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						LEFT JOIN `community_notify_members` AS c
						ON c.`community_id` = ".$db->qstr($community_id)."
						AND c.`record_id` = ".$db->qstr($permission_id)."
						AND c.`notify_type` = ".$db->qstr($content_type)."
						AND c.`proxy_id` = a.`proxy_id`
						LEFT JOIN `community_discussion_topics` AS d
						ON d.`cdtopic_id` = c.`record_id`
						WHERE c.`notify_active` = '1'
                        AND a.`proxy_id` != ".$db->qstr($ENTRADA_USER->getActiveId())."
						AND a.`community_id` = ".$db->qstr($community_id)."
						AND b.`notifications` = '1'
						AND a.`member_acl` >= ".$db->qstr($permission_required)."
						UNION
						SELECT a.`proxy_id`
						FROM `community_members` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						WHERE a.`proxy_id` IN (
							SELECT p.`proxy_id` FROM `community_notify_members` AS p
							LEFT JOIN `community_discussion_topics` AS q
							ON p.`record_id` = q.`cdiscussion_id`
							WHERE p.`community_id` = ".$db->qstr($community_id)."
							AND q.`cdtopic_id` = ".$db->qstr($permission_id)."
							AND p.`notify_type` = 'post'
							AND p.`notify_active` = '1'
						)
                        AND a.`proxy_id` != ".$db->qstr($ENTRADA_USER->getActiveId())."
                        AND a.`member_active` = 1
						AND a.`community_id` = ".$db->qstr($community_id)."
						AND b.`notifications` = '1'
						AND a.`member_acl` >= ".$db->qstr($permission_required);
			break;
		case "file-revision" :
		case "file-comment" :
			$query = "	SELECT `proxy_id` FROM `community_members` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						LEFT JOIN `community_notify_members` AS c
						ON c.`community_id` = ".$db->qstr($community_id)."
						AND c.`record_id` = ".$db->qstr($permission_id)."
						AND c.`notify_type` = 'file-notify'
						AND c.`proxy_id` = b.`id`
						WHERE c.`notify_active` = '1'
                        AND a.`member_active` = 1
						AND a.`community_id` = ".$db->qstr($community_id)."
						AND b.`notifications` = '1'
						AND a.`member_acl` >= ".$db->qstr($permission_required);
			break;
		default :
			$query = "	SELECT `proxy_id` FROM `community_members` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						WHERE a.`proxy_id` IN (
												SELECT `proxy_id` FROM `community_notify_members`
												WHERE `community_id` = ".$db->qstr($community_id)."
												AND `record_id` = ".$db->qstr($permission_id)."
												AND `notify_type` = ".$db->qstr($content_type)."
												AND `notify_active` = '1'
											)
                        AND a.`proxy_id` != ".$db->qstr($ENTRADA_USER->getActiveId())."
                        AND a.`member_active` = 1
						AND a.`community_id` = ".$db->qstr($community_id)."
						AND b.`notifications` = '1'
						AND a.`member_acl` >= ".$db->qstr($permission_required);
			break;
	}
	$proxy_ids = $db->GetAll($query);

	if($proxy_ids && count($proxy_ids)) {
		/**
		 * Select which type of message should be sent - then generate the message
		 * and subject in accordance with that.
		 */
		switch ($content_type) {
			case "poll" :
				$query	 = "SELECT a.`poll_title`, b.`community_title`
							FROM `community_polls` AS a
							LEFT JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE b.`community_id` = ".$db->qstr($community_id)."
							AND a.`cpolls_id` = ".$db->qstr($record_id);
				$result = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["poll_title"];
				$subject = "New poll started";
				break;
			case "file" :
				$query	 = "SELECT a.`file_title`, b.`community_title`
							FROM `community_share_files` AS a
							LEFT JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE b.`community_id` = ".$db->qstr($community_id)."
							AND a.`csfile_id` = ".$db->qstr($record_id);
				$result = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["file_title"];
				$subject = "New file added";
				break;
			case "file-revision" :
				$query	 = "SELECT a.`file_title`, b.`community_title`
							FROM `community_share_files` AS a
							LEFT JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE b.`community_id` = ".$db->qstr($community_id)."
							AND a.`csfile_id` = ".$db->qstr($record_id);
				$result = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["file_title"];
				$subject = "New version of file added";
				break;
			case "file-comment" :
				$query	 = "SELECT a.`file_title`, b.`community_title`
							FROM `community_share_files` AS a
							LEFT JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE b.`community_id` = ".$db->qstr($community_id)."
							AND a.`csfile_id` = ".$db->qstr($record_id);
				$result = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["file_title"];
				$subject = "New file comment added";
				break;
			case "photo" :
				$query	 = "SELECT a.`photo_title`, b.`community_title`
							FROM `community_gallery_photos` AS a
							LEFT JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE b.`community_id` = ".$db->qstr($community_id)."
							AND a.`cgphoto_id` = ".$db->qstr($record_id);
				$result = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["photo_title"];
				$subject = "New photo added";
				break;
			case "photo-comment" :
				$query	 = "SELECT a.`photo_title`, b.`community_title`
							FROM `community_gallery_photos` AS a
							LEFT JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE b.`community_id` = ".$db->qstr($community_id)."
							AND a.`cgphoto_id` = ".$db->qstr($record_id);
				$result = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["photo_title"];
				$subject = "New photo comment added";
				break;
			case "announcement" :
			case "announcement_moderate" :
			case "announcement_release" :
			case "announcement_delete" :
				$query	 = "SELECT a.`announcement_title`, b.`community_title`
							FROM `community_announcements` AS a
							LEFT JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE b.`community_id` = ".$db->qstr($community_id)."
							AND a.`cannouncement_id` = ".$db->qstr($record_id);
				$result = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["announcement_title"];
				if ($content_type == "announcement_release") {
					$subject = "Announcement approved";
				} elseif ($content_type == "announcement_delete") {
					$subject = "Announcement rejected";
				}
				else {
					$subject = "New announcement added";
				}
				break;
			case "event" :
				$query	 = "SELECT a.`event_title`, b.`community_title`
							FROM `community_events` AS a
							LEFT JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE b.`community_id` = ".$db->qstr($community_id)."
							AND a.`cevent_id` = ".$db->qstr($record_id);
				$result = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["event_title"];
				$subject = "New event added";
				break;
			case "post" :
				$query	 = "SELECT a.`topic_title`, b.`community_title`
							FROM `community_discussion_topics` AS a
							LEFT JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE b.`community_id` = ".$db->qstr($community_id)."
							AND a.`cdtopic_id` = ".$db->qstr($record_id);
				$result = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["topic_title"];
				$subject = "New discussion topic added";
				break;
			case "reply" :
				$query	 = "SELECT a.`topic_title`, b.`community_title`
							FROM `community_discussion_topics` AS a
							LEFT JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE b.`community_id` = ".$db->qstr($community_id)."
							AND a.`cdtopic_id` = ".$db->qstr($record_id);
				$result = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["topic_title"];
				$subject = "New discussion reply added";
				break;
			case "join"  :
			case "leave" :
				$query   = "SELECT a.`community_title`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`
							FROM `communities` AS a, `".AUTH_DATABASE."`.`user_data` AS b
							WHERE a.`community_id` =  ".$db->qstr($community_id)." and b.`id` = ". $db->qstr($record_id);
				$result  = $db->GetRow($query);
				$community_title = $result["community_title"];
				$content_title = $result["fullname"];
				$record_id = $permission_id;
				if ($content_type = "join") {
					$subject = "New member joined community";
				} else {
					$subject = "Member left community";
				}
				break;
			default :
				return false;
				break;
		}
		$message = "community-".$content_type."-notification.txt";
		post_notify($proxy_ids, $community_title, $content_type, $subject, $message, $url, $release_time, $record_id, $ENTRADA_USER->getID());
	} else {
		return false;
	}

	return true;
}

function quiz_generate_description($required = 0, $quiztype_code = "delayed", $quiz_timeout = 0, $quiz_questions = 1, $quiz_attempts = 0, $timeframe = "", $attendance = 0, $course_id = 0) {
	global $db, $RESOURCE_TIMEFRAMES;

	$output    = "This is %s quiz to be completed %s. You will have %s and %s to answer the %s in this quiz, and your results will be presented %s.%s";

	$string_1 = (((int) $required) ? "a required" : "an optional");
	$string_2 = ((($timeframe) && ($timeframe != "none")) ? strtolower($RESOURCE_TIMEFRAMES["event"][$timeframe]) : "when you see fit");
	$string_3 = (((int) $quiz_timeout) ? $quiz_timeout." minute".(($quiz_timeout != 1) ? "s" :"") : "no time limitation");
	$string_4 = (((int) $quiz_attempts) ? $quiz_attempts." attempt".(($quiz_attempts != 1) ? "s" : "") : "unlimited attempts");
	$string_5 = $quiz_questions." question".(($quiz_questions != 1) ? "s" : "");
	$string_6 = (($quiztype_code == "hide") ? "by a teacher, likely through ".($course_id ? "the <a href=\"".ENTRADA_URL."/profile/gradebook?section=view&id=".$course_id."\"><strong>Course Gradebook</strong></a>" : "a <a href=\"".ENTRADA_URL."/profile/gradebook\"><strong>Course Gradebook</strong></a>") : (($quiztype_code == "delayed") ? "only after the quiz expires" : "immediately after completion"));
	$string_7 = (isset($attendance) && $attendance)?"<br /><br /> This quiz requires your attendance. You will not be able to access it if you have not been marked present.":"";
	return sprintf($output, $string_1, $string_2, $string_3, $string_4, $string_5, $string_6, $string_7);
}

/**
 * This function loads the current progress based on an qprogress_id.
 *
 * @global object $db
 * @param int $qprogress_id
 * @return array Returns the users currently progress or returns false if there
 * is an error.
 */
function quiz_load_progress($qprogress_id = 0) {
	global $db;

	$output = array();

	if ($qprogress_id = (int) $qprogress_id) {
	/**
		 * Grab the specified progress identifier, but you better be sure this
		 * is the correct one, and the results are being returned to the proper
		 * user.
	 */
		$query		= "	SELECT *
						FROM `quiz_progress`
						WHERE `qprogress_id` = ".$db->qstr($qprogress_id);
		$progress	= $db->GetRow($query);
		if ($progress) {
		/**
		 * Add all of the qquestion_ids to the $output array so they're set.
		 */
			$query		= "SELECT * FROM `quiz_questions` WHERE `quiz_id` = ".$db->qstr($progress["quiz_id"])." AND `question_active` = '1' ORDER BY `question_order` ASC";
			$questions	= $db->GetAll($query);
			if ($questions) {
				foreach ($questions as $question) {
					$output[$question["qquestion_id"]] = 0;
				}
			} else {
				return false;
			}

			/**
			 * Update the $output array with any currently selected responses.
			 */
			$query		= "	SELECT *
							FROM `quiz_progress_responses`
							WHERE `qprogress_id` = ".$db->qstr($qprogress_id);
			$responses	= $db->GetAll($query);
			if ($responses) {
				foreach ($responses as $response) {
					$output[$response["qquestion_id"]] = $response["qqresponse_id"];
				}
			}
		} else {
			return false;
		}
	}

	return $output;
}

function quiz_save_response($qprogress_id, $aquiz_id, $content_id, $quiz_id, $qquestion_id, $qqresponse_id, $quiz_type = "event") {
	global $db, $ENTRADA_USER;

	/**
	 * Check to ensure that this response is associated with this question.
	 */
	$query	= "SELECT * FROM `quiz_question_responses` WHERE `qqresponse_id` = ".$db->qstr($qqresponse_id)." AND `response_active` = '1' AND `qquestion_id` = ".$db->qstr($qquestion_id);
	$result	= $db->GetRow($query);
	if ($result) {
	/**
	 * See if they have already responded to this question or not as this
	 * determines whether an INSERT or an UPDATE is required.
	 */
		$query = "	SELECT `qpresponse_id`, `qqresponse_id`
					FROM `quiz_progress_responses`
					WHERE `qprogress_id` = ".$db->qstr($qprogress_id)."
					AND `aquiz_id` = ".$db->qstr($aquiz_id)."
					AND `content_type` = ".$db->qstr($quiz_type)."
					AND `content_id` = ".$db->qstr($content_id)."
					AND `quiz_id` = ".$db->qstr($quiz_id)."
					AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
					AND `qquestion_id` = ".$db->qstr($qquestion_id);
		$result	= $db->GetRow($query);
		if ($result) {
		/**
		 * Checks to see if the response is different from what was previously
		 * stored in the event_quiz_responses table.
		 */
			if ($qqresponse_id != $result["qqresponse_id"]) {
				$quiz_response_array	= array (
					"qqresponse_id" => $qqresponse_id,
					"updated_date" => time(),
					"updated_by" => $ENTRADA_USER->getID()
				);

				if ($db->AutoExecute("quiz_progress_responses", $quiz_response_array, "UPDATE", "`qpresponse_id` = ".$db->qstr($result["qpresponse_id"]))) {
					return true;
				} else {
					application_log("error", "Unable to update a response to a question that has already been recorded. Database said: ".$db->ErrorMsg());
				}
			} else {
				return true;
			}
		} else {
			$quiz_response_array	= array (
				"qprogress_id" => $qprogress_id,
				"aquiz_id" => $aquiz_id,
				"content_type" => $quiz_type,
				"content_id" => $content_id,
				"quiz_id" => $quiz_id,
				"proxy_id" => $ENTRADA_USER->getID(),
				"qquestion_id" => $qquestion_id,
				"qqresponse_id" => $qqresponse_id,
				"updated_date" => time(),
				"updated_by" => $ENTRADA_USER->getID()
			);

			if ($db->AutoExecute("quiz_progress_responses", $quiz_response_array, "INSERT")) {
				return true;
			} else {
				application_log("error", "Unable to record a response to a question that was submitted. Database said: ".$db->ErrorMsg());
			}
		}
	} else {
		application_log("error", "A submitted qqresponse_id was not a valid response for the qquestion_id that was provided when attempting to submit a response to a question.");
	}

	return false;
}

function lp_multiple_select_popup($id, $checkboxes, $options) {

	if(!is_array($checkboxes) || $id == null) {
		return null;
	}

	$default_options = array(
		'title'			=>	'Select Multiple',
		'cancel'		=>	false,
		'cancel_text'	=>	'Cancel',
		'submit'		=>	false,
		'submit_text'	=>	'Submit',
		'filter'		=>	true,
		'class'			=>	'',
		'width'			=>	'450px',
		'hidden'		=>	true
	);

	$options = array_merge($default_options, $options);
	$classes = array("select_multiple_container");

	if(is_array($options['class'])) {
		foreach($options['class'] as $class) {
			$classes[] = $class;
		}
	} else {
		if($options['class'] != '') {
			$classes[] = $options['class'];
		}
	}

	$class_string = implode($classes, ' ');

	$return = '<div id="'.$id.'_options" class="'.$class_string.'" style="'.($options['hidden'] ? 'display:none; ' : '').'width: '.$options['width'].';"><div class="panel-head"><h3>'.$options['title'].'</h3></div><div id="'.$id.'_scroll" class="select_multiple_scroll' . ($options['type'] ? " ". $options['type'] : "") . '"><table cellspacing="0" cellpadding="0" class="select_multiple_table" width="100%">';
	$return .= lp_multiple_select_table($checkboxes, 0, 0).'</table><div style="clear: both;"></div></div><div class="select_multiple_submit">';
	if($options['filter']) {
		$return .= '<div class="select_multiple_filter"><input id="'.$id.'_select_filter" type="text" value="Search..."><span class="select_filter_clear" onclick="$(\''.$id.'_select_filter\').value = \'\'; $$(\'.filter-hidden\').invoke(\'show\');"></span></div>';
	}

	$return .= ($options['cancel'] == true ? '<input type="button" class="btn" value="'.$options['cancel_text'].'" id="'.$id.'_cancel"/>&nbsp;' : '');
	$return .= ($options['submit'] == true ? '<input type="button" class="btn btn-primary" value="'.$options['submit_text'].'" id="'.$id.'_close"/>' : '').'</div></div>';
	return $return;
}

function lp_multiple_select_inline($id, $checkboxes, $options) {

	if(!is_array($checkboxes) || $id == null) {
		return null;
	}

	$default_options = array(
		'filterbar'			=>	true,
		'filter'			=>	true,
		'class'				=>	'',
		'width'				=>	'300px',
		'hidden'			=>	false,
		'ajax'				=>	false,
		'selectboxname'		=>	'category',
		'category_check_all'=>  false
	);

	$options = array_merge($default_options, $options);
	$classes = array("select_multiple_container", "inline");

	if(is_array($options['class'])) {
		foreach($options['class'] as $class) {
			$classes[] = $class;
		}
	} else {
		if($options['class'] != '') {
			$classes[] = $options['class'];
		}
	}

	$class_string = implode($classes, ' ');

	$return = '<div id="'.$id.'_options" class="'.$class_string.'"
	  style="'.($options['hidden'] ? 'display:none; ' : '').
	'width: '.$options['width'].';">';

	if($options['filterbar']) {
		$return .= '<div class="select_multiple_submit">';
		if($options['ajax']) {
			$return .= lp_multiple_select_category_select($id, $checkboxes, $options);
		}

		if($options['filter']) {
			$return .= '<div class="select_multiple_filter '.($options['ajax'] ? 'ajax' : '').'">
						<span class="select_filter_clear" onclick="$(\''.$id.'_select_filter\').value = \'\'; $$(\'.filter-hidden\').invoke(\'show\');"></span>
		  <input id="'.$id.'_select_filter" type="text" value="Search..." style="width:112px;">
		</div>';
		}

		$return .= '</div>';
	}


	$return .= '<div id="'.$id.'_scroll" class="select_multiple_scroll" style="'.
	(isset($options['height']) ? 'height: '.$options['height'].';' : '' ).'"><table cellspacing="0" cellpadding="0" class="select_multiple_table" width="100%">';

	if($options['ajax']) {
		$return .= '<tr><td colspan="2" style="text-align: left;">Please select a '.$options['selectboxname'].' from above.</td></tr>';
	} else {
		$return .= lp_multiple_select_table($checkboxes, 0, 0, $options["category_check_all"]);
	}

	$return .='</table><div style="clear: both;"></div></div></div>';
	return $return;
}



function lp_multiple_select_table($checkboxes, $indent, $i, $category_check_all = false) {
	$return = "";
	$input_class = 'select_multiple_checkbox';

	foreach($checkboxes as $checkbox) {
		if($i%2 == 0) {
			$class = 'even';
		} else {
			$class = 'odd';
		}

		if(isset($checkbox['category']) && $checkbox['category'] == true) {
			if($category_check_all) {
				$input = '<input type="checkbox" id="'.$checkbox['value'].'_category"/ value="'.$checkbox['value'].'" />';
			} else {
				$input = "&nbsp;";
			}
			$class .= ' category';
			$name_class = "select_multiple_name_category";
			$input_class = "select_multiple_checkbox_category";
		} else if(isset($checkbox['disabled']) && $checkbox['disabled'] == true) {
				$input = "&nbsp;";
				$class .= ' disabled';
				$name_class = "select_multiple_name_disabled";
		} else {
			$input = '<input type="checkbox" id="'.$checkbox['value'].'" value="'.$checkbox['value'].'" '.$checkbox['checked'].'/>';
			$name_class = "select_multiple_name";
			if ($input_class == "select_multiple_checkbox_category") {
				$input_class = 'select_multiple_checkbox';
			}
		}
		if (isset($checkbox["class"]) && $checkbox["class"]) {
			$class .= " ".$checkbox["class"];
		}

		if(isset($checkbox['name_class'])) {
			$name_class = $checkbox['name_class'];
		}

		$i++;

		if (isset($checkbox['value']) && $checkbox['value']) {
			$return .= '<tr class="'.$class.'"><td class="'.$name_class.' indent_'.$indent.'"><label for="'.$checkbox['value'].'" id="'.$checkbox['value'].'_label">'.$checkbox['text'].'</label></td><td class="'.$input_class.'">'.$input.'</td></tr>';
		}

		if(isset($checkbox['options'])) {
			$return .= lp_multiple_select_table($checkbox['options'], $indent+1, $i);
		}
	}

	return $return;
}


function lp_multiple_select_category_select($id, $checkboxes, $options) {
	$return = '<select name="'.$id.'_category_select" id="'.$id.'_category_select">';
	if ($options["default-option"]) {
		$return .= '<option id="default_drop_option" class="select_multiple_category_drop" value="0">'.$options['default-option'].'</option>';
	}
	foreach($checkboxes as $checkbox) {

		if(isset($checkbox['class'])) {
			$class = $checkbox['class'];
		} else {
			$class = "select_multiple_category_drop";
		}

		$return .= '<optgroup class="'.$class.'" label="'.$checkbox['text'].'">';
		if(isset($checkbox['options']) && is_array($checkbox['options']) && !empty($checkbox['options'])) {
			foreach($checkbox['options'] as $select_option) {
				if(isset($select_option['class'])) {
					$class = $select_option['class'];
				} else {
					$class = "select_multiple_category_drop";
				}
				$return .= '<option id="'.$select_option['value'].'_drop_option" class="'.$class.'" value="'.$select_option['value'].'">'.$select_option['text'].'</option>';

			}
		}
		$return .= '</optgroup>';

	}
	$return .= '</select>';
	return $return;
}

/**
 * This function returns the total number of attempts the user
 * has made on the provided aquiz_id, completed, expired or otherwise.

 * @param int $aquiz_id
 * @return int
 */
function quiz_fetch_attempts($aquiz_id = 0) {
	global $db, $ENTRADA_USER;

	if ($aquiz_id = (int) $aquiz_id) {
		$query		= "	SELECT COUNT(*) AS `total`
						FROM `quiz_progress`
						WHERE `aquiz_id` = ".$db->qstr($aquiz_id)."
						AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
						AND `progress_value` <> 'inprogress'";
		$attempts	= $db->GetRow($query);
		if ($attempts) {
			return $attempts["total"];
		}
	}

	return 0;
}

/**
 * This function returns the total number of completed attempts the user
 * has made on the provided aquiz_id.

 * @param int $aquiz_id
 * @return int
 */
function quiz_completed_attempts($aquiz_id = 0) {
	global $db, $ENTRADA_USER;

	if ($aquiz_id = (int) $aquiz_id) {
		$query		= "	SELECT COUNT(*) AS `total`
						FROM `quiz_progress`
						WHERE `aquiz_id` = ".$db->qstr($aquiz_id)."
						AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
						AND `progress_value` = 'complete'";
		$completed	= $db->GetRow($query);
		if ($completed) {
			return $completed["total"];
		}
	}

	return 0;
}

/**
 * This function quite simply returns the number of questions in a quiz.
 *
 * @param int $quiz_id
 * @return int
 */
function quiz_count_questions($quiz_id = 0) {
	global $db;

	if ($quiz_id = (int) $quiz_id) {
		$query	= "SELECT COUNT(*) AS `total` FROM `quiz_questions` WHERE `quiz_id` = ".$db->qstr($quiz_id)." AND `question_active` = '1' AND `questiontype_id` = '1'";
		$result	= $db->GetRow($query);
		if ($result) {
			return $result["total"];
		}
	}

	return 0;
}

/**
 * Function takes event_id and gets the location of the elective.
 *
 * @param int $event_id
 * @return array element
 */
function clerkship_get_elective_location($event_id) {
	global $db;

	$query	= "	SELECT a.`geo_location`, a.`city`, b.`region_name`
				FROM `".CLERKSHIP_DATABASE."`.`electives` AS a
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS b
				ON a.`region_id` = b.`region_id`
				WHERE a.`event_id` = ".$db->qstr($event_id);
	$result = $db->GetRow($query);
	if ($result && $result["region_name"]) {
		$result["city"] = $result["region_name"];
	}
	return $result;
}

/**
 * Function takes $proxy_id and determines the number of weeks of electives in the system.
 *
 * @param int $proxy_id
 * @return array
 */
function clerkship_get_elective_weeks($proxy_id, $event_id = 0) {
	global $db;

	$draft_amount		= 0;
	$approved_amount	= 0;
	$trash_amount		= 0;

	$query		= "	SELECT `event_start`, `event_finish`, `event_status`,  `".CLERKSHIP_DATABASE."`.`electives`.`discipline_id`, `global_lu_disciplines`.`discipline`
					FROM `".CLERKSHIP_DATABASE."`.`events`, `".CLERKSHIP_DATABASE."`.`event_contacts`, `".CLERKSHIP_DATABASE."`.`electives`, `global_lu_disciplines`
					WHERE `event_type` = 'elective'
					AND `etype_id` = ".$db->qstr($proxy_id)."
					AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` != $event_id
					AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`event_contacts`.`event_id`
					AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`electives`.`event_id`
					AND `global_lu_disciplines`.`discipline_id` = `".CLERKSHIP_DATABASE."`.`electives`.`discipline_id`
					ORDER BY `".CLERKSHIP_DATABASE."`.`electives`.`discipline_id`";
	$results	= $db->GetAll($query);
	if ($results) {
		foreach ($results as $result) {
			$difference	= ($result["event_finish"] - $result["event_start"]) / 604800;
			$weeks		= ceil($difference);

			switch ($result["event_status"]) {
				case "published" :
					$approved_amount += $weeks;
				break;
				case "trash" :
					$trash_amount += $weeks;
				break;
				case "approval" :
				default :
					$draft_amount += $weeks;
				break;
			}
		}
	}

	return array("approval" => $draft_amount, "approved" => $approved_amount, "trash" => $trash_amount);
}

/**
 * Returns the first and last name of a contact for an event_id.
 *
 * @param int $event_id
 * @param int $proxy_id
 * @return int
 */
function clerkship_student_name($event_id = 0) {
	global $db;

	if($event_id = (int) $event_id) {
		$query	= "SELECT `firstname`, `lastname`, `role`, `user_data`.`id`
		FROM `".CLERKSHIP_DATABASE."`.`event_contacts`, `".AUTH_DATABASE."`.`user_data`, `".AUTH_DATABASE."`.`user_access`
		WHERE `event_id` = ".$db->qstr($event_id)."
		AND `etype_id` = `".AUTH_DATABASE."`.`user_data`.`id`
		AND `".AUTH_DATABASE."`.`user_data`.`id` = `".AUTH_DATABASE."`.`user_access`.`user_id`";

		$result	= $db->GetRow($query);
		if($result) {
			return $result;
		}
	}

	return 0;
}

/**
 * Helper function ( clerkship ) return the value or a blank if zero
 *
 * @param int
 * @return int
 */

 function blank_zero($value = 0) {

    return ($value) ? $value : '';
 }

/**
 * Function takes $rotation_id and determines the number of entries, clinical presentations, mandatory cps,
 *	procedures.
 *
 * @param int $rotation
 * @param int $proxy_id
 * @return array
 */
function clerkship_get_rotation_overview($rotation_id, $proxy_id = 0) {
    global $db, $ENTRADA_USER;

    if (!$rotation_id) {
		$rotation_id = MAX_ROTATION;
    }

    if (!$proxy_id) {
		$proxy_id = $ENTRADA_USER->getID();
    }

    // Count of entries entered in this rotation
    $query  = "	SELECT COUNT(*) FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` l
    			WHERE l.`proxy_id` = ".$db->qstr($proxy_id)."
    			AND l.`entry_active` = 1
    			AND	l.`rotation_id` IN
    			(
    				SELECT e.`event_id` FROM `".CLERKSHIP_DATABASE."`.`events` as e
					WHERE e.`rotation_id` = ".$db->qstr($rotation_id)."
				)";
    $entries = $db->GetOne($query);

    $query 	= "SELECT a.*, b.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
				JOIN `global_lu_objectives` AS b
				ON a.`objective_id` = b.`objective_id`
				JOIN `objective_organisation` AS c
				ON b.`objective_id` = c.`objective_id`
				WHERE a.`rotation_id` = ".$db->qstr($rotation_id)."
				AND a.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $proxy_id))."
				AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $proxy_id)).")
				AND c.`organisation_id` = ".$db->qstr(get_account_data("organisation_id", $proxy_id));
	$oresults = $db->GetAll($query);
	if ($oresults) {
		$total_required = 0;
		$total_logged = 0;
		$required_list = array();
		$missing_list = array();
		$logged_list = array();
		$objective_string = "";
		foreach ($oresults as $objective) {
			if ($objective_string) {
				$objective_string .= ",".$db->qstr($objective["objective_id"]);
			} else {
				$objective_string = $db->qstr($objective["objective_id"]);
			}
			$objectives[$objective["objective_id"]] = $objective["number_required"];
			$total_required += $objective["number_required"];
			$query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
						JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS b
						ON a.`lmobjective_id` = b.`lmobjective_id`
						JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
						ON b.`lltype_id` = c.`lltype_id`
						WHERE a.`lmobjective_id` = ".$db->qstr($objective["lmobjective_id"])."
						GROUP BY c.`lltype_id`";
			$locations = $db->GetAll($query);
			$location_string = "";
			foreach ($locations as $location) {
				$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
			}
			$required_list[$objective["objective_id"]] = $objective["objective_name"].($location_string ? " (".$location_string.")" : "");
		}
		if ($objective_string) {
			$query 	= "SELECT COUNT(a.`objective_id`) as `number_logged`, a.`objective_id`
						FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
    			ON a.`lentry_id` = b.`lentry_id`
					WHERE a.`objective_id` IN  (".$objective_string.")
					AND b.`proxy_id` = ".$db->qstr($proxy_id)."
			    AND b.`entry_active` = 1
						".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "AND b.`llocation_id` IN
				(
					SELECT dd.`llocation_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS aa
					JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS bb
					ON aa.`lmobjective_id` = bb.`lmobjective_id`
							JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS cc
					ON bb.`lltype_id` = cc.`lltype_id`
					JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS dd
							ON cc.`llocation_id` = dd.`llocation_id`
					WHERE aa.`objective_id` = a.`objective_id`
					AND aa.`rotation_id` = ".$db->qstr($rotation_id)."
					AND aa.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $proxy_id))."
					AND (aa.`grad_year_max` = 0 OR aa.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $proxy_id)).")
						)" : "")."
					GROUP BY a.`objective_id`";
			$numbers_logged = $db->GetAll($query);
			if ($numbers_logged) {
				foreach ($numbers_logged as $number_logged) {
					if ($number_logged > $objectives[$number_logged["objective_id"]]) {
						$total_logged += $objectives[$number_logged["objective_id"]];
					} else {
						$total_logged += $number_logged["number_logged"];
					}
					$logged_list[$number_logged["objective_id"]] = $required_list[$number_logged["objective_id"]];
					unset($required_list[$number_logged["objective_id"]]);
				}
			}
		}
	}

    // Count of objectives entered in this rotation
    $query  = "	SELECT COUNT(*) FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` AS a
    			INNER JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
    			ON a.`lentry_id` = b.`lentry_id`
			    WHERE b.`proxy_id` = ".$db->qstr($proxy_id)."
			    AND b.`entry_active` = 1
			    AND b.`rotation_id` IN
			    (
			    	SELECT e.`event_id` FROM `".CLERKSHIP_DATABASE."`.`events` AS e
					WHERE e.`rotation_id` = ".$db->qstr($rotation_id)."
				)";
    $objectives = $db->GetOne($query);

    $query  = "	SELECT COUNT(*) FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` AS a
    			INNER JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
    			ON a.`lentry_id` = b.`lentry_id`
				WHERE b.`proxy_id` = ".$db->qstr($proxy_id)."
				AND b.`entry_active` = 1
				AND b.`rotation_id` IN
				(
					SELECT e.`event_id` FROM `".CLERKSHIP_DATABASE."`.`events` AS e
					WHERE e.`rotation_id` = ".$db->qstr($rotation_id)."
				)";
    $procedures = $db->GetOne($query);

    return array("entries" => $entries, "objectives" => $objectives, "mandatories" => $total_logged, "all_mandatories" => $total_required, "procedures" => $procedures);
}

/**
 * Function takes rotation--or gets the current rotation--and returns the rotation of interest and rotation title.
 *
 * @param int $rotation_id
 * @param int $proxy_id
 * @return array
 */

function clerkship_get_rotation($rotation_id, $proxy_id = 0) {
    global $db, $ENTRADA_USER;

    if (!$proxy_id) {
		$proxy_id = $ENTRADA_USER->getID();
    }

    if (!$rotation_id) {  // Get current rotation
		$query	= "	SELECT *
					FROM `".CLERKSHIP_DATABASE."`.`events` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
					ON b.`event_id` = a.`event_id`
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
					ON c.`region_id` = a.`region_id`
					WHERE a.`event_finish` >= ".$db->qstr(strtotime("00:00:00", time()))."
					AND (a.`event_status` = 'published' OR a.`event_status` = 'approval')
					AND b.`econtact_type` = 'student'
					AND b.`etype_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
					ORDER BY a.`event_start` ASC";
		if ($clerkship_schedule	= $db->GetAll($query)) {
			$rotation_id =  (isset($clerkship_schedule["rotation_id"]) && $clerkship_schedule["rotation_id"]) ? $clerkship_schedule["rotation_id"] : (MAX_ROTATION - 1); // Select Overview / Elective if not a mandatory rotation
		}
    }

    // Get Rotation name
    $query  = "	SELECT `rotation_title` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`
    			WHERE `rotation_id` = ".$db->qstr($rotation_id);
    $result = $db->GetOne($query);
    return array("id" => $rotation_id, "title" => $result);
}

/**
 * Function takes rotation and determines the total number of weeks and weeks remaining.
 *
 * @param int $rotation_id
 * @param int $proxy_id
 * @return array
 */

function clerkship_get_rotation_schedule ($rotation, $proxy_id = 0) {
    global $db, $ENTRADA_USER;

   if (!$proxy_id) {
	$proxy_id = $ENTRADA_USER->getID();
    }

    if ($rotation && $rotation < MAX_ROTATION) {
	$query = "	SELECT ROUND(DATEDIFF(t2.f,t1.s) / 7) total, ROUND(DATEDIFF(t2.f,current_date) / 7) yet, DATEDIFF(t1.s,current_date) test1, DATEDIFF(t2.f,current_date) test2
				FROM
	    		(
					SELECT FROM_UNIXTIME(b.`event_start`) AS s
					FROM `".CLERKSHIP_DATABASE."`.`event_contacts` AS a
					INNER JOIN `".CLERKSHIP_DATABASE."`.`events` AS b
					ON a.`event_id` = b.`event_id`
					WHERE a.`etype_id` = ".$db->qstr($proxy_id)."
					AND b.`rotation_id` = ".$db->qstr($rotation)."
					ORDER BY b.`event_start`
					LIMIT 1
				)  t1,
	    		(
	    			SELECT FROM_UNIXTIME(b.`event_finish`) AS f
					FROM `".CLERKSHIP_DATABASE."`.`event_contacts` AS a
					INNER JOIN `".CLERKSHIP_DATABASE."`.`events` AS b
					ON a.`event_id` = b.`event_id`
					WHERE a.`etype_id` = ".$db->qstr($proxy_id)."
					AND b.`rotation_id` = ".$db->qstr($rotation)."
					ORDER BY b.`event_finish` DESC
					LIMIT 1
				) t2";
	$result = $db->GetRow($query);
	if ($result["test1"] > 0) {  // Starting in the future
	    $result["yet"] = $result["total"];
	} else if ($result["test2"] <= 0) { // Finished in the past
	    $result["yet"] = 0;
	}
	return $result;
    } else {
	return array("total" => 0, "yet" => 0);
    }
}

/**
 * Function takes an agerange index and rotation id  and returns the age range for that rotation or the default range if applicable.
 *
 * @param int $agerange_id
 * @param int $rotation_id
 * @return string
 */
function clerkship_get_agerange ($agerange_id, $rotation_id) {
    global $db;

    $query = "	SELECT `age` FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_agerange`
		where `agerange_id` = ".$db->qstr($agerange_id)." and (`rotation_id` = ".$db->qstr($rotation_id)." or `rotation_id` = 0)
		order by `rotation_id` desc limit 1";
    return $db->GetOne($query);
}

/**
 * Function takes a period index value, a rotation row, and a clerk/rotation row
 * and determines whether the clerk should be notified of their progress in the rotation.
 * If they do require to be notified, whether due to completion or delinquency, then the
 * function to send such notices is called also.
 *
 * @param int $rotation_period_index
 * @param array $rotation
 * @param array $clerk
 * @return boolean
 */
function clerkship_progress_send_notice($rotation_period_index, $rotation, $clerk) {
	global $db;
	$query 	= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_notification_history`
                WHERE `rotation_id` = ".$db->qstr($rotation["rotation_id"])."
                AND `clerk_id` = ".$db->qstr($clerk["proxy_id"])."
                AND `proxy_id` = ".$db->qstr($clerk["proxy_id"])."
                AND `notified_date` > ".$db->qstr((time() - ONE_WEEK));
	$notified = $db->GetRow($query);
    $objective_progress = clerkship_rotation_objectives_progress($clerk["proxy_id"], $rotation["rotation_id"]);
    $procedure_progress = clerkship_rotation_tasks_progress($clerk["proxy_id"], $rotation["rotation_id"]);
    $procedure_progress["required"] = (isset($procedure_progress["required"]) && $procedure_progress["required"] ? $procedure_progress["required"] : 0);
    $procedure_progress["logged"] = (isset($procedure_progress["logged"]) && $procedure_progress["logged"] ? $procedure_progress["logged"] : 0);
    switch ($rotation_period_index) {
        case CLERKSHIP_SIX_WEEKS_PAST :
            if ($objective_progress["required"] > $objective_progress["logged"]) {
                $overdue_logging = array(
                                            "proxy_id" => $clerk["proxy_id"],
                                            "rotation_id" => $rotation["rotation_id"],
                                            "event_id" => $clerk["event_id"],
                                            "logged_required" => $objective_progress["required"],
                                        "logged_completed" => $objective_progress["logged"],
                                        "procedures_required" => $procedure_progress["required"],
                                        "procedures_completed" => $procedure_progress["logged"]
                                        );
                $db->AutoExecute(CLERKSHIP_DATABASE.".logbook_overdue", $overdue_logging, "INSERT");
                application_log("notice", "A clerk [".$clerk["proxy_id"]."] has been added to the `logbook_overdue` table for deficiencies in a clerkship rotation [".$rotation["rotation_id"]."] six weeks after it ended.");
                if (!$notified && $objective_progress["required"] > $objective_progress["logged"]) {
                    clerkship_notify_clerk($rotation_period_index, $clerk, $rotation, $objective_progress);
                }
            }
        break;
        case CLERKSHIP_ROTATION_ENDED :
            if ($objective_progress["required"] > $objective_progress["logged"]) {
                $overdue_logging = array(
                                            "proxy_id" => $clerk["proxy_id"],
                                            "rotation_id" => $rotation["rotation_id"],
                                            "event_id" => $clerk["event_id"],
                                            "logged_required" => $objective_progress["required"],
                                        "logged_completed" => $objective_progress["logged"],
                                        "procedures_required" => $procedure_progress["required"],
                                        "procedures_completed" => $procedure_progress["logged"]
                                        );
                $db->AutoExecute(CLERKSHIP_DATABASE.".logbook_overdue", $overdue_logging, "INSERT");
                application_log("notice", "A clerk [".$clerk["proxy_id"]."] has been added to the `logbook_overdue` table for deficiencies in a clerkship rotation [".$rotation["rotation_id"]."] upon ending in the past week.");
                if (!$notified && $objective_progress["required"] > $objective_progress["logged"]) {
                    clerkship_notify_clerk($rotation_period_index, $clerk, $rotation, $objective_progress);
                }
            }
        break;
        case CLERKSHIP_ONE_WEEK_PRIOR :
        case CLERKSHIP_ROTATION_PERIOD :
            if ((($objective_progress["logged"] / $objective_progress["required"]) * 100) < ($rotation["percent_required"])) {
                $overdue_logging = array(
                                            "proxy_id" => $clerk["proxy_id"],
                                            "rotation_id" => $rotation["rotation_id"],
                                            "event_id" => $clerk["event_id"],
                                            "logged_required" => $objective_progress["required"],
                                        "logged_completed" => $objective_progress["logged"],
                                        "procedures_required" => $procedure_progress["required"],
                                        "procedures_completed" => $procedure_progress["logged"]
                                        );
                $db->AutoExecute(CLERKSHIP_DATABASE.".logbook_overdue", $overdue_logging, "INSERT");
                application_log("notice", "A clerk [".$clerk["proxy_id"]."] has been added to the `logbook_overdue` table for delinquencies in a clerkship rotation [".$rotation["rotation_id"]."] after the half-way-point of that rotation.");
                if (!$notified) {
                    clerkship_notify_clerk($rotation_period_index, $clerk, $rotation, $objective_progress);
                }
            }
        break;
    }
}

/**
 * This function returns the numeric suffix in English for the provided number.
 *
 * @param int $number
 * @return string
 */
function numeric_suffix($number = 0) {
    $test_number = abs($number) % 10;
    $ext = ((abs($number) %100 < 21 && abs($number) %100 > 4) ? 'th' : (($test_number < 4) ? ($test_number < 3) ? ($test_number < 2) ? ($test_number < 1) ? 'th' : 'st' : 'nd' : 'rd' : 'th'));

	return $number.$ext;
}

/**
 * Function takes a period index value, a rotation row, and a clerk/rotation row
 * and sends notices to let them know that they are deficient/delinquent in their
 * logging.
 *
 * @param int $rotation_period_index
 * @param array $rotation
 * @param array $clerk
 * @return array
 */
function clerkship_notify_clerk($rotation_period_index, $clerk, $rotation, $objective_progress) {
	global $db, $AGENT_CONTACTS, $ENTRADA_TEMPLATE, $translate;
	if (defined("CLERKSHIP_EMAIL_NOTIFICATIONS") && CLERKSHIP_EMAIL_NOTIFICATIONS) {
		$mail = new Zend_Mail();
		$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
		$mail->addHeader("X-Section", $translate->_("Clerkship") . " Notification System",true);
		$mail->clearFrom();
		$mail->clearSubject();
		$mail->setFrom($AGENT_CONTACTS["agent-notifications"]["email"], APPLICATION_NAME.' Clerkship System');
		switch ($rotation_period_index) {
			case CLERKSHIP_SIX_WEEKS_PAST :
				$mail->setSubject($translate->_("Clerkship") . " Logbook Deficiency Notification");
				break;
			case CLERKSHIP_ROTATION_ENDED :
			case CLERKSHIP_ONE_WEEK_PRIOR :
			case CLERKSHIP_ROTATION_PERIOD :
				$mail->setSubject($translate->_("Clerkship") ." Logbook Progress Notification");
				break;
		}
		$NOTIFICATION_MESSAGE		 	 = array();

		switch ($rotation_period_index) {
			case CLERKSHIP_SIX_WEEKS_PAST :
				$NOTIFICATION_MESSAGE["textbody"] = file_get_contents(ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/email/clerkship-deficiency-clerk-notification.txt");
				break;
			case CLERKSHIP_ROTATION_ENDED :
				$NOTIFICATION_MESSAGE["textbody"] = file_get_contents(ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/email/clerkship-rotation-incomplete-clerk-notification.txt");
				break;
			case CLERKSHIP_ONE_WEEK_PRIOR :
			case CLERKSHIP_ROTATION_PERIOD :
			default :
				$NOTIFICATION_MESSAGE["textbody"] = file_get_contents(ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/email/clerkship-delinquency-clerk-notification.txt");
				break;
		}

		if ($rotation) {
			$query 	= " SELECT `notified_date` FROM `".CLERKSHIP_DATABASE."`.`logbook_notification_history`
					WHERE `clerk_id` = ".$db->qstr($clerk["proxy_id"])."
					AND `proxy_id` = ".$db->qstr($clerk["proxy_id"])."
					AND `rotation_id` = ".$db->quote($clerk["rotation_id"])."
					ORDER BY `notified_date` DESC
					LIMIT 0,1";
			$last_notified = $db->GetOne($query);
			if ($last_notified <= (strtotime("-1 week"))) {

				clerkship_add_queued_notification($rotation_period_index, $clerk, $rotation, $objective_progress);

				$search		= array(
									"%CLERK_FULLNAME%",
									"%ROTATION_TITLE%",
									"%PROFILE_URL%",
									"%PROGRAM_COORDINATOR%",
									"%ROTATION_OVERVIEW_URL%",
									"%ROTATION_DEFICIENCIES%",
									"%ROTATION_DEFICIENCY_URL%",
									"%ENTRY_MANAGEMENT_URL%",
									"%APPLICATION_NAME%",
									"%ENTRADA_URL%"
								);
				$replace	= array(
									$clerk["fullname"],
									$rotation["rotation_title"],
									ENTRADA_URL."/people?id=".$clerk["proxy_id"],
									get_account_data("wholename", $rotation["director_id"])." - ".get_account_data("email", $rotation["director_id"]),
									ENTRADA_URL."/clerkship/logbook?section=view&core=".$clerk["rotation_id"],
									implode(" \n", $objective_progress["required_list"]),
									ENTRADA_URL."/clerkship/logbook?section=deficiency-plan&rotation=".$clerk["rotation_id"],
									ENTRADA_URL."/clerkship/logbook?sb=rotation&rotation=".$clerk["rotation_id"],
									APPLICATION_NAME,
									ENTRADA_URL
							);
				$mail->setBodyText(clean_input(str_replace($search, $replace, $NOTIFICATION_MESSAGE["textbody"]), array("postclean")));

				if ($clerk["proxy_id"]) {
					$NOTICE 	= Array(
										"target" => "proxy_id:".$clerk["proxy_id"],
										"notice_summary" => clean_input(str_replace($search, $replace, "It has come to our attention that you have not met the logging requirements for the [%ROTATION_TITLE%] rotation after the allotted time. Please review your logbook entries and progress, and make note of how you plan to solve this problem via the <a href=\"%ROTATION_OVERVIEW_URL%\">Rotation progress</a> section."), array("postclean")),
										"display_from" => time(),
										"display_until" => strtotime("+1 week"),
										"updated_date" => time(),
										"updated_by" => 1,
										"organisation_id" => 1
								);
					if($db->AutoExecute("notices", $NOTICE, "INSERT")) {
						if($NOTICE_ID = $db->Insert_Id()) {
							$naudience = array("notice_id"=>$NOTICE_ID,"audience_type"=>"students","audience_value"=>$clerk["proxy_id"],"updated_by"=>0,"updated_date"=>time());
							$db->AutoExecute("notice_audience", $naudience, "INSERT");
							application_log("success", "Successfully added notice ID [".$NOTICE_ID."]");
						} else {
							application_log("error", "Unable to fetch the newly inserted notice identifier for this notice.");
						}
					} else {
						application_log("error", "Unable to insert new notice into the system. Database said: ".$db->ErrorMsg());
					}
				}

				$mail->clearRecipients();
				if (strlen($clerk["email"])) {
					$mail->addTo($clerk["email"], $clerk["fullname"]);
					$sent = true;
					try {
						$mail->send();
					}
					catch (Exception $e) {
						$sent = false;
					}
					if($sent) {
						application_log("success", "Sent overdue logging notification to Clerk [".$clerk["proxy_id"]."].");
					} else {
						application_log("error", "Unable to send overdue logging notification to Clerk [".$clerk["proxy_id"]."].");
					}
					$NOTICE_HISTORY = Array(
											"clerk_id" => $clerk["proxy_id"],
											"proxy_id" => $clerk["proxy_id"],
											"rotation_id" => $clerk["rotation_id"],
											"notified_date" => time()
											);
					if($db->AutoExecute(CLERKSHIP_DATABASE.".logbook_notification_history", $NOTICE_HISTORY, "INSERT")) {
						if($HISTORY_ID = $db->Insert_Id()) {
							application_log("success", "Successfully added notification history ID [".$HISTORY_ID."]");
						} else {
							application_log("error", "Unable to fetch the newly inserted notification history identifier for this notice.");
						}
					} else {
						application_log("error", "Unable to insert new notification history record into the system. Database said: ".$db->ErrorMsg());
					}
				}
			}
		}
	}
}

/**
 * Function takes a period index value, a rotation row, and a clerk/rotation row
 * and adds notices to be sent to the program coordinator of the clerkship course
 * in batches to let them know which clerks are deficient/delinquent in their logging.
 *
 * @param int $rotation_period_index
 * @param array $rotation
 * @param array $clerk
 * @return array
 */
function clerkship_add_queued_notification($rotation_period_index, $clerk, $rotation, $objective_progress) {
	global $db, $AGENT_CONTACTS;

	if ($rotation) {
		$query 	= "SELECT `notified_date` FROM `".CLERKSHIP_DATABASE."`.`logbook_notification_history`
				WHERE `clerk_id` = ".$db->qstr($clerk["proxy_id"])."
				AND `proxy_id` = ".$db->qstr($rotation["director_id"])."
				AND `rotation_id` = ".$db->quote($clerk["rotation_id"])."
				ORDER BY `notified_date` DESC
				LIMIT 1";
		$last_notified = $db->GetOne($query);

		if ($last_notified <= (strtotime("-1 week"))) {
			if ($clerk["proxy_id"]) {
				$coordinator_notification = array(
														"clerk_id" => $clerk["proxy_id"],
														"proxy_id" => $rotation["director_id"],
														"rotation_id" => $clerk["rotation_id"],
														"timeframe" => $rotation_period_index,
														"updated_date" => time(),
														"notification_sent" => false
												);
				if (!$db->AutoExecute(CLERKSHIP_DATABASE.".clerkship_queued_notifications", $coordinator_notification, "INSERT")) {
					application_log("error", "Unable to save clerkship coordinator notification to the queue for clerk [".$clerk["proxu_id"]."]. Database said: ".$db->ErrorMsg());
				}
			}
		}
	}
}
/**
 * Function takes a rotation_id and sends all the pending emails
 * in the clerkship_queue_notifications table for that rotation
 * to the clerkship coordinators.
 *
 * @param int $rotation_id
 * @return boolean
 */
function clerkship_send_queued_notifications($rotation_id, $rotation_title, $proxy_id) {
	global $db, $AGENT_CONTACTS, $ENTRADA_TEMPLATE;
	$query 	= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`clerkship_queued_notifications`
			WHERE `rotation_id` = ".$db->qstr($rotation_id)."
			AND `clerk_id` NOT IN (
				SELECT `clerk_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_notification_history`
				WHERE `notified_date` > ".$db->qstr(strtotime("-1 week"))."
				AND `rotation_id` = ".$db->qstr($rotation_id)."
				AND `proxy_id` = ".$db->qstr($proxy_id)."
			)
			AND `notification_sent` = '0'";
	$clerk_notifications = $db->GetAll($query);
	if ($clerk_notifications) {
		$clerks = array();
		$clerk_ids_string = "";
		foreach ($clerk_notifications as $clerk_notification) {
			$clerks[$clerk_notification["clerk_id"]] = get_account_data("fullname", $clerk_notification["clerk_id"]).($clerk_notification["timeframe"] == CLERKSHIP_ROTATION_ENDED ? "*" : ($clerk_notification["timeframe"] == CLERKSHIP_SIX_WEEKS_PAST ? "**" : ""))." - ".ENTRADA_URL."/clerkship/logbook?section=view&type=missing&id=".$clerk_notification["clerk_id"]."&core=".$clerk_notification["rotation_id"];
			if ($clerk_ids_string) {
				$clerk_ids_string .= ", ".$db->qstr($clerk_notification["clerk_id"]);
			} else {
				$clerk_ids_string = $db->qstr($clerk_notification["clerk_id"]);
			}
		}
		if (isset($proxy_id) && $proxy_id && ($email = get_account_data("email", $proxy_id)) && ($fullname = get_account_data("fullname", $proxy_id))) {
			if (defined("CLERKSHIP_EMAIL_NOTIFICATIONS") && CLERKSHIP_EMAIL_NOTIFICATIONS) {
				$mail = new Zend_Mail();
				$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
				$mail->addHeader("X-Section", $translate->_("Clerkship") . " Notification System",true);
				$mail->clearFrom();
				$mail->clearSubject();
				$mail->setFrom($AGENT_CONTACTS["agent-notifications"]["email"], APPLICATION_NAME.' Clerkship System');
				$mail->setSubject($translate->_("Clerkship") . " Logbook Progress Notification");

				$NOTIFICATION_MESSAGE = array();
				$NOTIFICATION_MESSAGE["textbody"] = file_get_contents(ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/email/clerkship-coordinator-notification.txt");


				$search		= array(
									"%ROTATION_TITLE%",
									"%CLERK_LIST%",
									"%APPLICATION_NAME%",
									"%ENTRADA_URL%"
								);
				$replace	= array(
									$rotation_title,
									implode("<br />\n", $clerks),
									APPLICATION_NAME,
									ENTRADA_URL
							);
				$mail->setBodyText(clean_input(str_replace($search, $replace, $NOTIFICATION_MESSAGE["textbody"]), array("postclean")));

				if (strlen($email)) {
					$mail->clearRecipients();
					$mail->addTo($email, $fullname);
					$sent = true;
					try {
						$mail->send();
					}
					catch (Exception $e) {
						$sent = false;
					}
					if($sent) {
						application_log("success", "Sent overdue logging notification to Program Coordinator [".$proxy_id."].");
					} else {
						application_log("error", "Unable to send overdue logging notification to Program Coordinator [".$proxy_id."].");
					}
					foreach ($clerks as $clerk_id => $clerk) {
						$NOTICE_HISTORY = Array(
												"clerk_id" => $clerk_id,
												"proxy_id" => $proxy_id,
												"rotation_id" => $rotation_id,
												"notified_date" => time()
												);
						if($db->AutoExecute(CLERKSHIP_DATABASE.".logbook_notification_history", $NOTICE_HISTORY, "INSERT")) {
							if($HISTORY_ID = $db->Insert_Id()) {
								application_log("success", "Successfully added notification history ID [".$HISTORY_ID."]");
							} else {
								application_log("error", "Unable to fetch the newly inserted notification history identifier for this notice.");
							}
						} else {
							application_log("error", "Unable to insert new notification history record into the system. Database said: ".$db->ErrorMsg());
						}
					}
					$db->AutoExecute(CLERKSHIP_DATABASE.".clerkship_queued_notifications", array("notification_sent" => 1), "UPDATE", "`clerk_id` IN (".$clerk_ids_string.")");
				}
			}
		}
	}
}

/**
 * Function takes a clerk proxy id and a rotation id and returns the progress for the
 * rotation; based on the number of required clinical procedures that have been logged.
 *
 * @param int $proxy_id
 * @param int $rotation_id
 * @return array
 */
function clerkship_rotation_objectives_progress($proxy_id, $rotation_id) {
	global $db;

	$query 	= "SELECT a.*, b.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
			JOIN `global_lu_objectives` AS b
			ON a.`objective_id` = b.`objective_id`
			JOIN `objective_organisation` AS c
			ON b.`objective_id` = c.`objective_id`
			WHERE a.`rotation_id` = ".$db->qstr($rotation_id)."
			AND a.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $proxy_id))."
			AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $proxy_id)).")
			AND c.`organisation_id` = ".$db->qstr(get_account_data("organisation_id", $proxy_id));
	$oresults = $db->GetAll($query);
	if ($oresults) {
		$total_required = 0;
		$total_logged = 0;
		$required_list = array();
		$missing_list = array();
		$logged_list = array();
		$objective_string = "";
		foreach ($oresults as $objective) {
			if ($objective_string) {
				$objective_string .= ",".$db->qstr($objective["objective_id"]);
			} else {
				$objective_string = $db->qstr($objective["objective_id"]);
			}
			$objectives[$objective["objective_id"]] = $objective["number_required"];
			$total_required += $objective["number_required"];
			$query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
						JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS b
						ON a.`lmobjective_id` = b.`lmobjective_id`
						JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
						ON b.`lltype_id` = c.`lltype_id`
						WHERE a.`lmobjective_id` = ".$db->qstr($objective["lmobjective_id"])."
						GROUP BY c.`lltype_id`";
			$locations = $db->GetAll($query);
			$location_string = "";
			foreach ($locations as $location) {
				$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
			}
			$required_list[$objective["objective_id"]] = $objective["objective_name"].($location_string ? " (".$location_string.")" : "");
		}
		if ($objective_string) {
			$query 	= "SELECT COUNT(a.`objective_id`) as `number_logged`, a.`objective_id`
						FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` AS a
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
						ON a.`lentry_id` = b.`lentry_id`
						WHERE a.`objective_id` IN  (".$objective_string.")
						AND b.`proxy_id` = ".$db->qstr($proxy_id)."
						AND b.`entry_active` = 1
						".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "AND b.`llocation_id` IN
						(
							SELECT dd.`llocation_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS aa
							JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS bb
							ON aa.`lmobjective_id` = bb.`lmobjective_id`
							JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS cc
							ON bb.`lltype_id` = cc.`lltype_id`
							JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS dd
							ON cc.`llocation_id` = dd.`llocation_id`
							WHERE aa.`objective_id` = a.`objective_id`
							AND aa.`rotation_id` = ".$db->qstr($rotation_id)."
							AND aa.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $proxy_id))."
							AND (aa.`grad_year_max` = 0 OR aa.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $proxy_id)).")
						)" : "")."
					GROUP BY a.`objective_id`";
			$numbers_logged = $db->GetAll($query);
			if ($numbers_logged) {
				foreach ($numbers_logged as $number_logged) {
					if ($number_logged > $objectives[$number_logged["objective_id"]]) {
						$total_logged += $objectives[$number_logged["objective_id"]];
					} else {
						$total_logged += $number_logged["number_logged"];
					}
					$logged_list[$number_logged["objective_id"]] = $required_list[$number_logged["objective_id"]];
					unset($required_list[$number_logged["objective_id"]]);
				}
			}
		}
		return array("required" => $total_required, "logged" => $total_logged, "required_list" => $required_list, "logged_list" => $logged_list);
	}
	return false;
}

/**
 * Function takes a clerk proxy id and a rotation id and returns the progress for the
 * rotation; based on the number of required clinical tasks that have been logged.
 *
 * @param int $proxy_id
 * @param int $rotation_id
 * @return array
 */
function clerkship_rotation_tasks_progress($proxy_id, $rotation_id) {
	global $db;
	$query 	= "SELECT b.*, a.* FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS a
				JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS b
				ON a.`lprocedure_id` = b.`lprocedure_id`
				WHERE a.`rotation_id` = ".$db->qstr($rotation_id)."
				AND a.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $proxy_id))."
				AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $proxy_id)).")";
	$tresults = $db->GetAll($query);
	if ($tresults) {
		$total_required = 0;
		$total_logged = 0;
		$task_string = "";
		foreach ($tresults as $task) {
			if ($task_string) {
				$task_string .= ",".$db->qstr($task["lprocedure_id"]);
			} else {
				$task_string = $db->qstr($task["lprocedure_id"]);
			}
			$tasks[$task["lprocedure_id"]] = $task["number_required"];
			$total_required += $task["number_required"];
			$query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS a
						JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS b
						ON a.`lpprocedure_id` = b.`lpprocedure_id`
						JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
						ON b.`lltype_id` = c.`lltype_id`
						WHERE a.`lpprocedure_id` = ".$db->qstr($task["lpprocedure_id"])."
						GROUP BY c.`lltype_id`";
			$locations = $db->GetAll($query);
			$location_string = "";
			foreach ($locations as $location) {
				$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
			}
			$required_list[$task["lprocedure_id"]] = $task["procedure"].($location_string ? " (".$location_string.")" : "");
		}
		if ($task_string) {
			$query 	= "SELECT COUNT(a.`lprocedure_id`) as number_logged, a.`lprocedure_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
					ON a.`lentry_id` = b.`lentry_id`
					WHERE a.`lprocedure_id` IN  (".$task_string.")
					AND b.`proxy_id` = ".$db->qstr($proxy_id)."
					AND b.`entry_active` = 1
						".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "AND b.`llocation_id` IN
						(
							SELECT dd.`llocation_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS aa
							JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS bb
							ON aa.`lpprocedure_id` = bb.`lpprocedure_id`
							JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS cc
							ON bb.`lltype_id` = cc.`lltype_id`
							JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS dd
							ON cc.`llocation_id` = dd.`llocation_id`
							WHERE aa.`lprocedure_id` = a.`lprocedure_id`
							AND aa.`rotation_id` = ".$db->qstr($rotation_id)."
							AND aa.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $proxy_id))."
							AND (aa.`grad_year_max` = 0 OR aa.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $proxy_id)).")
						)" : "")."
					GROUP BY a.`lprocedure_id`";
			$numbers_logged = $db->GetAll($query);
			if ($numbers_logged) {
				foreach ($numbers_logged as $number_logged) {
					if ($number_logged > $tasks[$number_logged["lprocedure_id"]]) {
						$total_logged += $tasks[$number_logged["lprocedure_id"]];
					} else {
						$total_logged += $number_logged["number_logged"];
					}
				}
			}
		}
		return array("required" => $total_required, "logged" => $total_logged);
	}
	return false;
}

function clerkship_deficiency_notifications($clerk_id, $rotation_id, $administrator = false, $completed = false, $comments = false) {
	global $AGENT_CONTACTS, $db, $ENTRADA_TEMPLATE, $translate;
	if (defined("CLERKSHIP_EMAIL_NOTIFICATIONS") && CLERKSHIP_EMAIL_NOTIFICATIONS) {
		$mail = new Zend_Mail();
		$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
		$mail->addHeader("X-Section", $translate->_("Clerkship") . " Notify System", true);
		$mail->clearFrom();
		$mail->clearSubject();
		$mail->setFrom($AGENT_CONTACTS["agent-notifications"]["email"], APPLICATION_NAME." Clerkship System");
		$mail->setSubject($translate->_("Clerkship") . " Logbook Deficiency Notification");
		$NOTIFICATION_MESSAGE	= array();

		$query	 				= "SELECT CONCAT_WS(' ', `firstname`, `lastname`) as `fullname`, `email`, `id`
									FROM `".AUTH_DATABASE."`.`user_data`
									WHERE `id` = ".$db->quote($clerk_id);
		$clerk					= $db->GetRow($query);

		$query 					= "SELECT a.`rotation_title`, c.`email`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) as `fullname`, c.`id` AS `proxy_id`
									FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
									LEFT JOIN `courses` AS b
									ON a.`course_id` = b.`course_id`
									LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
									ON c.`id` = ".($administrator ? "(SELECT `proxy_id` FROM `course_contacts` WHERE `course_id` = b.`course_id` AND `contact_type` = 'director' ORDER BY `contact_order` ASC LIMIT 0, 1)" : $db->qstr($clerk_id))."
									WHERE a.`rotation_id` = ".$db->quote($rotation_id);
		$rotation				= $db->GetRow($query);

		if ($rotation) {

			$search	= array(
								"%CLERK_FULLNAME%",
								"%ROTATION_TITLE%",
								"%ADMIN_COMMENTS%",
								"%PROFILE_URL%",
								"%DEFICIENCY_PLAN_URL%",
								"%CLERK_MANAGEMENT_URL%",
								"%APPLICATION_NAME%",
								"%ENTRADA_URL%"
							);
			$replace	= array(
								$clerk["fullname"],
								$rotation["rotation_title"],
								(!$administrator && $comments ? "However, the administrator left comments which you are meant to review, these can be found on the deficiency plan page, which is linked below." : ""),
								ENTRADA_URL."/people?id=".$clerk_id,
								ENTRADA_URL."/clerkship/logbook?section=deficiency-plan&rotation=".$rotation_id.($administrator ? "&id=".$clerk_id : ""),
								ENTRADA_URL."/clerkship".($administrator ? "?section=clerk&ids=".$clerk_id : "/logbook"),
								APPLICATION_NAME,
								ENTRADA_URL
							);
			if ($administrator) {
				$NOTIFICATION_MESSAGE["textbody"] = file_get_contents(ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/email/clerk-deficiency-plan-admin-notification.txt");
			} else {
				$NOTIFICATION_MESSAGE["textbody"] = file_get_contents(ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/email/clerk-deficiency-plan-reviewed-".($completed ? "complete" : "incomplete")."-notification.txt");
			}
			$mail->setBodyText(clean_input(str_replace($search, $replace, $NOTIFICATION_MESSAGE["textbody"]), array("postclean")));

			if (($rotation["proxy_id"] && $administrator) || !$administrator) {
				if ($administrator) {
					$notice_message = "The clerk [%CLERK_FULLNAME%] has completed a plan to attain deficiencies for a rotation [%ROTATION_TITLE%] after the allotted time. Please review their <a href=\"%DEFICIENCY_PLAN_URL%\">Deficiency Plan</a> now to ensure it meets all requirements.";
				} else {
					if ($completed) {
						$notice_message = "An administrator has reviewed your rotation [%ROTATION_TITLE%] deficiency plan. Your plan was accepted to attain all deficiencies in the timeline which you outlined".($comments ? ", but you are asked to review the comments the administrator left on your <a href=\"%DEFICIENCY_PLAN_URL%\">Deficiency Plan</a>" : "").".";
					} else {
						$notice_message = "An administrator has reviewed your rotation [%ROTATION_TITLE%] deficiency plan. Unfortunately, your plan was was not accepted. More information can be found in the comments from the administrator on the <a href=\"%DEFICIENCY_PLAN_URL%\">Deficiency Plan</a> page, which we ask you to review before modifying and resubmitting your plan.";
					}
				}
				$NOTICE = Array(
									"target" => "proxy_id:".($administrator ? $rotation["proxy_id"] : $clerk_id),
									"notice_summary" => clean_input(str_replace($search, $replace, $notice_message), array("postclean")),
									"display_from" => time(),
									"display_until" => strtotime("+2 weeks"),
									"updated_date" => time(),
									"updated_by" => 1,
									"organisation_id" => 1
								);
				if($db->AutoExecute("notices", $NOTICE, "INSERT")) {
					if($NOTICE_ID = $db->Insert_Id()) {
						$naudience = array("notice_id"=>$NOTICE_ID,"audience_type"=>"students","audience_value"=>($administrator ? $rotation["proxy_id"] : $clerk_id),"updated_by"=>0,"updated_date"=>time());
						$db->AutoExecute("notice_audience",$naudience,"INSERT");
						application_log("success", "Successfully added notice ID [".$NOTICE_ID."]");
					} else {
						application_log("error", "Unable to fetch the newly inserted notice identifier for this notice.");
					}
				} else {
					application_log("error", "Unable to insert new notice into the system. Database said: ".$db->ErrorMsg());
				}
				$sent = true;
				$mail->clearRecipients();
				if (strlen($rotation['email'])) {
					$mail->addTo($rotation['email'], $rotation['fullname']);
					try {
						$mail->send();
					}
					catch (Exception $e) {
						$sent = false;
					}
					if($sent && $administrator) {
						application_log("success", "Sent overdue logging notification to Course Director ID [".$rotation["proxy_id"]."].");
					} elseif ($administrator) {
						application_log("error", "Unable to send overdue logging notification to Course Director ID [".$rotation["proxy_id"]."].");
					} elseif (!$administrator && $sent) {
						application_log("success", "Sent overdue logging notification to Clerk ID [".$clerk_id."].");
					} else {
						application_log("error", "Unable to send overdue logging notification to Clerk ID [".$clerk_id."].");
					}
					$NOTICE_HISTORY = Array(
											"clerk_id" => $clerk_id,
											"proxy_id" => ($administrator ? $rotation["proxy_id"] : $clerk_id),
											"rotation_id" => $rotation_id,
											"notified_date" => time()
											);
					if($db->AutoExecute(CLERKSHIP_DATABASE.".logbook_notification_history", $NOTICE_HISTORY, "INSERT")) {
						if($HISTORY_ID = $db->Insert_Id()) {
							application_log("success", "Successfully added notification history ID [".$HISTORY_ID."]");
						} else {
							application_log("error", "Unable to fetch the newly inserted notification history identifier for this notice.");
						}
					} else {
						application_log("error", "Unable to insert new notification history record into the system. Database said: ".$db->ErrorMsg());
					}
				}
			}
		}
	}
}

function subnavigation($tab, $module) {
    global $translate, $ENTRADA_ACL;
    $navigation = $translate->_("navigation_tabs");
    if (isset($navigation["public"][$module]["children"])) {
        $children = $navigation["public"][$module]["children"];
        $allowed_children = array_filter($children, function ($child_item) use ($ENTRADA_ACL) {
            if (isset($child_item["resource"]) && isset($child_item["permission"])) {
                return $ENTRADA_ACL->amIAllowed($child_item["resource"], $child_item["permission"]);
            } else {
                return true;
            }
        });
        $li_elements = array_map(function ($uri, $child_item) use ($tab) {
            return array(
                "title" => $child_item["title"],
                "class" => ($tab == basename($uri)) ? "active" : "",
                "url" => ENTRADA_RELATIVE."/".$uri,
            );
        }, array_keys($allowed_children), $allowed_children);

        ?>
        <div class="no-printing">
            <ul class="nav nav-tabs">
                <?php foreach ($li_elements as $li): ?>
                    <li class="<?php echo $li["class"]; ?>">
                        <a href="<?php echo $li["url"]; ?>"><?php echo $li["title"]; ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}

function search_subnavigation($tab = "details") {
    return subnavigation($tab, "curriculum/search");
}

function courses_subnavigation($course_details, $tab="details") {
	global $ENTRADA_ACL, $translate;

	// Inline queries deliver arrays, new models deliver objects, 
	// so course_id and course_organization get set dynamically
	$course_id = !is_array($course_details) ? $course_details->getID() : $course_details["course_id"];
	$course_organization = !is_array($course_details) ? $course_details->getOrganisationID() : $course_details["organisation_id"];
    $settings = new Entrada_Settings();

	echo "<div class=\"no-printing\">\n";
    echo "    <ul class=\"nav nav-tabs\">\n";
	if($ENTRADA_ACL->amIAllowed(new CourseResource($course_id, $course_organization), "update")) {
        echo "<li".($tab=="details"?" class=\"active\"":"")." style=\"width:16%;\"><a href=\"".ENTRADA_RELATIVE."/admin/courses?".replace_query(array("section" => "edit", "id" => $course_id, "step" => false))."\" >" . $translate->_("Setup") . "</a></li>\n";
	}
	if($ENTRADA_ACL->amIAllowed(new CourseContentResource($course_id, $course_organization), "read") && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course_id, $course_organization), "read", true)) {
        echo "<li".($tab=="content"?" class=\"active\"":"")." style=\"width:16%;\"><a href=\"".ENTRADA_RELATIVE."/admin/courses?".replace_query(array("section" => "content", "id" => $course_id, "step" => false))."\" >" . $translate->_("Content") . "</a></li>\n";
	}
	if($settings->read("curriculum_weeks_enabled") && $ENTRADA_ACL->amIAllowed(new CourseResource($course_id, $course_organization), "update")) {
		echo "<li".($tab=="units"?" class=\"active\"":"")." style=\"width:16%;\"><a href=\"".ENTRADA_RELATIVE."/admin/courses/units?".replace_query(array("section" => false, "assessment_id" => false, "id" => $course_id, "step" => false))."\">".$translate->_("Units")."</a></li>\n";
	}
	if($ENTRADA_ACL->amIAllowed(new CourseResource($course_id, $course_organization), "update")) {
		echo "<li".($tab=="enrolment"?" class=\"active\"":"")." style=\"width:16%;\"><a href=\"".ENTRADA_RELATIVE."/admin/courses/enrolment?".replace_query(array("section"=>false,"assessment_id" => false, "id" => $course_id, "step" => false))."\" >" . $translate->_("Enrolment") ."</a></li>\n";
	}
	if($ENTRADA_ACL->amIAllowed(new CourseResource($course_id, $course_organization), "update")) {
        echo "<li".($tab=="groups"?" class=\"active\"":"")." style=\"width:16%;\"><a href=\"".ENTRADA_RELATIVE."/admin/courses/groups?".replace_query(array("section" => false, "assessment_id" => false, "id" => $course_id, "step" => false))."\">" . $translate->_("Groups") ."</a></li>\n";
	}
	if($ENTRADA_ACL->amIAllowed(new GradebookResource($course_id, $course_organization), "read")) {
        echo "<li".($tab=="gradebook"?" class=\"active\"":"")." style=\"width:16%;\"><a href=\"".ENTRADA_RELATIVE."/admin/gradebook?section=view&amp;id=".$course_id."\">" . $translate->_("Gradebook") ."</a></li>";
	}
    if($settings->read("cbme_enabled") && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course_id, $course_organization, true), "update")) {
        echo "<li".($tab=="cbme"?" class=\"active\"":"")." style=\"width:16%;\"><a href=\"".ENTRADA_RELATIVE."/admin/courses/cbme?".replace_query(array("section"=>false,"assessment_id" => false, "id" => $course_id, "step" => false))."\" >" . $translate->_("CBME") ."</a></li>\n";
    }
	if($ENTRADA_ACL->amIAllowed(new CourseContentResource($course_id, $course_organization, true), "update")) {
		echo "<li".($tab=="reports"?" class=\"active\"":"")." style=\"width:16%;\"><a href=\"".ENTRADA_RELATIVE."/admin/courses/reports?".replace_query(array("section"=>false,"assessment_id" => false, "id" => $course_id, "step" => false))."\" >" . $translate->_("Reports") ."</a></li>\n";
	}

	echo "	</ul>\n";
	echo "</div>\n";
}

function course_fetch_course_group($cgroup_id = 0) {
	global $db;

	$cgroup_id = (int) $cgroup_id;

	if ($cgroup_id) {
		$query = "	SELECT a.*, COUNT(b.`cgaudience_id`) AS `members`
					FROM `course_groups` AS a
					LEFT JOIN `course_group_audience` AS b
					ON b.`cgroup_id` = a.`cgroup_id`
					WHERE a.`cgroup_id` = ".$db->qstr($cgroup_id)."
					GROUP BY a.`cgroup_id`";
		$result = $db->GetRow($query);
		if ($result) {
			return $result;
		}
	}

	return false;
}

function course_fetch_enrolled_course_groups($proxy_id = 0, $only_active_groups = false) {
	global $db, $ENTRADA_USER;

	$proxy_id = (int) $proxy_id;
	$only_active_groups = (bool) $only_active_groups;

	$cgroup_ids = array();

	if ($proxy_id) {
		$query = "	SELECT a.`cgroup_id`
					FROM `course_groups` AS a
					JOIN `course_group_audience` AS b
					ON b.`cgroup_id` = a.`cgroup_id`
					JOIN `courses` AS c
					ON c.`course_id` = a.`course_id`
					WHERE b.`proxy_id` = ".$db->qstr($proxy_id)."
					AND b.`active` = '1'
					".($only_active_groups ? " AND a.`active` = '1'" : "")."
					AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
		$course_groups = $db->CacheGetAll(CACHE_TIMEOUT, $query);
		if ($course_groups) {
			foreach ($course_groups as $course_group) {
				$cgroup_ids[] = (int) $course_group["cgroup_id"];
			}
		}
	}

	return $cgroup_ids;
}

/**
 * This function returns the number of events that are associated wtih the
 * provided course_id.
 *
 * @param int $course_id
 * @return int
 */
function courses_count_associated_events($course_id = 0) {
	global $db;

	if($course_id = (int) $course_id) {
		$query	= "SELECT COUNT(*) AS `total_events` FROM `events` WHERE `course_id` = ".$db->qstr($course_id);
		$result	= $db->GetRow($query);
		if($result) {
			return (int) $result["total_events"];
		}
	}

	return 0;
}

function courses_fetch_courses($only_active_courses = true, $order_by_course_code = true, $curriculum_type_id = 0, $cperiod_ids = null) {
	global $db, $ENTRADA_ACL, $ENTRADA_USER;

	$only_active_courses = (bool) $only_active_courses;
	$order_by_course_code = (bool) $order_by_course_code;

	$curriculum_type_ids = array();

	if (is_scalar($curriculum_type_id) && ($id = (int) trim($curriculum_type_id))) {
		$curriculum_type_ids[] = $id;
	} elseif (is_array($curriculum_type_id)) {
		foreach ($curriculum_type_id as $id) {
			$id = (int) trim($id);
			if ($id) {
				$curriculum_type_ids[] = $id;
			}
		}
	}

	$output = array();
	$query = "	SELECT DISTINCT(a.`course_id`), a.`course_name`, a.`course_code`, a.`course_active`, a.`organisation_id`
				FROM `courses` AS a";
	if (strtolower($ENTRADA_USER->getActiveRole()) != "admin" && strtolower($ENTRADA_USER->getActiveRole()) != "director") {
		$query .= "	LEFT JOIN `course_audience` AS b
				ON a.`course_id` = b.`course_id`
				LEFT JOIN `groups` AS c
				ON b.`audience_type` = 'group_id'
				AND b.`audience_value` = c.`group_id`
				LEFT JOIN `group_members` AS d
				ON d.`group_id` = c.`group_id`";
	}
	$query .= " WHERE `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());

	if ($ENTRADA_USER->getActiveGroup() == "student") {
		$query .="	AND (
						d.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
						OR a.`permission` = 'open'
						OR (
							b.`audience_type` = 'proxy_id' AND b.`audience_value` = ".$db->qstr($ENTRADA_USER->getID())."
						)
					)";
	}

	if ($only_active_courses) {
        $query .="    AND a.`course_active`='1'";
	}
	if (!empty($curriculum_type_ids)) {
        $query .= "    AND a.`curriculum_type_id` IN (".implode(", ", $curriculum_type_ids).")";
	}

	if ($cperiod_ids && strtolower($ENTRADA_USER->getActiveRole()) != "admin") {
        $query .= "    AND b.`cperiod_id` IN (".implode(", ", $cperiod_ids).")";
    }

	$query .= "	ORDER BY".($order_by_course_code ? " a.`course_code`," : "")." a.`course_name` ASC";

	$results = $db->GetAll($query);
	if ($results) {
		foreach ($results as $result) {
				if ($ENTRADA_ACL->amIAllowed(new CourseResource($result["course_id"], $result["organisation_id"]), "read")) {
					$output[] = $result;
				}
		}
	}

	return $output;
}

/**
 *
 * @global object $db
 * @param type $org_id - the organisation you are finding objectives for
 * @param type $course_ids
 * @param type $top_level_id - the id of the objective that is the top level, if calling from a module it should be -1
 * @param type $parent_id - the parent id of the top level curriculum objective, default to 1 if this is called from a module
 * @param type $objectives
 * @param type $objective_ids
 * @param type $event_id
 * @param type $fetch_all_text
 * @return an array containing the objectives and the top level id of the curriculum objectives, use list($curriculum_objectives,$top_level_id) to retrieve the returned values
 */
function courses_fetch_objectives($org_id, $course_ids, $top_level_id = -1, $parent_id = 1, $objectives = false, $objective_ids = false, $event_id = 0, $fetch_all_text = false) {
	global $db, $translate;

	if (!$objectives && is_array($course_ids)) {
		$objectives = array(
							"used" => array(),
							"unused" => array(),
							"objectives" => array(),
							"used_ids" => array(),
							"primary_ids" => array(),
							"secondary_ids" => array(),
							"tertiary_ids" => array());
		$escaped_course_ids = "";
		for ($i = 0; $i < (count($course_ids) - 1); $i++) {
			$escaped_course_ids .= $db->qstr($course_ids[$i]).",";
		}
		$escaped_course_ids .= $db->qstr($course_ids[(count($course_ids) - 1)]);
		$query		= "	SELECT b.`objective_name`, a.`objective_id`, a.`importance`, a.`objective_details`, a.`course_id`, b.`objective_parent`, b.`objective_order`
						FROM `course_objectives` AS a
						JOIN `global_lu_objectives` AS b
						ON a.`objective_id` = b.`objective_id`
						JOIN `objective_organisation` AS c
						ON b.`objective_id` = c.`objective_id`
						WHERE ".($fetch_all_text ? "" : "`importance` != '0'
						AND ")."`course_id` IN (".$escaped_course_ids.")
						AND a.`objective_type` = 'course'
						AND c.`organisation_id` = ".$db->qstr($org_id)."
                        AND a.`active` = '1'
						UNION
						SELECT b.`objective_name`, b.`objective_id`, a.`importance`, a.`objective_details`, a.`course_id`, b.`objective_parent`, b.`objective_order`
						FROM `course_objectives` AS a
						JOIN `global_lu_objectives` AS b
						ON a.`objective_id` = b.`objective_parent`
						AND `course_id` IN (".$escaped_course_ids.")
						JOIN `objective_organisation` AS c
						ON b.`objective_id` = c.`objective_id`
						WHERE ".($fetch_all_text ? "" : "`importance` != '0'
						AND a.`objective_type` = 'course'
						AND ")."a.`objective_type` = 'course'
						AND c.`organisation_id` = ".$db->qstr($org_id)."
                        AND a.`active` = '1'
						AND b.`objective_id` NOT IN (
							SELECT a.`objective_id`
							FROM `course_objectives` AS a
							JOIN `global_lu_objectives` AS b
							ON a.`objective_id` = b.`objective_id`
							WHERE ".($fetch_all_text ? "" : "`importance` != '0'
							AND ")."`course_id` IN (".$escaped_course_ids.")
							AND `objective_type` = 'course'
						)
						ORDER BY `objective_parent`, `objective_order` ASC";
		$results	= $db->GetAll($query);
		if($results && !is_array($objective_ids)) {
			foreach($results as $result) {
				if ($result["importance"] == 1) {
					$objectives["primary_ids"][$result["objective_id"]] = $result["objective_id"];
				} elseif ($result["importance"] == 2) {
					$objectives["secondary_ids"][$result["objective_id"]] = $result["objective_id"];
				} elseif ($result["importance"] == 3) {
					$objectives["tertiary_ids"][$result["objective_id"]] = $result["objective_id"];
				}
				$objectives["used_ids"][$result["objective_id"]] = $result["objective_id"];
				$objectives["objectives"][$result["objective_id"]] = array();
				$objectives["objectives"][$result["objective_id"]]["objective_details"] = $result["objective_details"];
			}
		}
		if (is_array($objective_ids)) {
			if (isset($objective_ids["primary"]) && is_array($objective_ids["primary"])) {
				foreach ($objective_ids["primary"] as $objective_id) {
					if (array_search($objective_id, $objectives["used_ids"]) === false) {
						$objectives["primary_ids"][$objective_id] = $objective_id;
						$objectives["used_ids"][$objective_id] = $objective_id;
					}
				}
			}
			if (isset($objective_ids["secondary"]) && is_array($objective_ids["secondary"])) {
				foreach ($objective_ids["secondary"] as $objective_id) {
					if (array_search($objective_id, $objectives["used_ids"]) === false) {
						$objectives["secondary_ids"][$objective_id] = $objective_id;
						$objectives["used_ids"][$objective_id] = $objective_id;
					}
				}
			}
			if (isset($objective_ids["tertiary"]) && is_array($objective_ids["tertiary"])) {
				foreach ($objective_ids["tertiary"] as $objective_id) {
					if (array_search($objective_id, $objectives["used_ids"]) === false) {
						$objectives["tertiary_ids"][$objective_id] = $objective_id;
						$objectives["used_ids"][$objective_id] = $objective_id;
					}
				}
			}
		}
	}

	if($top_level_id == -1){
		$objective_name = $translate->_("events_filter_controls");
		$objective_name = $objective_name["co"]["global_lu_objectives_name"];
		$query	= "SELECT a.* FROM `global_lu_objectives` AS a
					INNER JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE b.`organisation_id` = ".$db->qstr($org_id)."
					AND `objective_active` = '1'
					AND a.`objective_name` LIKE ".$db->qstr($objective_name)."
					ORDER BY a.`objective_order` ASC ";
	} else {
		$query	= "SELECT a.* FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_parent` = ".$db->qstr($parent_id)."
					AND a.`objective_active` = '1'
					AND b.`organisation_id` = ".$db->qstr($org_id)."
					ORDER BY a.`objective_order` ASC";
	}
	$results	= $db->GetAll($query);
	if($results) {
		if($top_level_id == -1){
			$top_level_id = $results[0]["objective_id"];
			$parent_id = $top_level_id;

			$query	= "SELECT a.* FROM `global_lu_objectives` AS a
						JOIN `objective_organisation` AS b
						ON a.`objective_id` = b.`objective_id`
						WHERE a.`objective_parent` = ".$db->qstr($parent_id)."
						AND b.`organisation_id` = ".$db->qstr($org_id)."
						AND a.`objective_active` = '1'
						ORDER BY a.`objective_order` ASC";


			$results = $db->GetAll($query);
		}
		foreach($results as $result) {
			if ($parent_id == $top_level_id) {
				$objectives["objectives"][$result["objective_id"]]["objective_primary_children"] = 0;
				$objectives["objectives"][$result["objective_id"]]["objective_secondary_children"] = 0;
				$objectives["objectives"][$result["objective_id"]]["objective_tertiary_children"] = 0;
				$objectives["objectives"][$result["objective_id"]]["children_primary"] = 0;
				$objectives["objectives"][$result["objective_id"]]["children_secondary"] = 0;
				$objectives["objectives"][$result["objective_id"]]["children_tertiary"] = 0;
				$objectives["objectives"][$result["objective_id"]]["name"] = $result["objective_name"];
				$objectives["objectives"][$result["objective_id"]]["description"] = (isset($objectives["objectives"][$result["objective_id"]]["objective_details"]) && $objectives["objectives"][$result["objective_id"]]["objective_details"] ? $objectives["objectives"][$result["objective_id"]]["objective_details"] : $result["objective_description"]);
				$objectives["objectives"][$result["objective_id"]]["parent"] = $top_level_id;
				$objectives["objectives"][$result["objective_id"]]["parent_ids"] = array();
				if (is_array($objectives["primary_ids"]) && array_search($result["objective_id"], $objectives["primary_ids"]) !== false) {
					$objectives["objectives"][$result["objective_id"]]["primary"] = true;
				} else {
					$objectives["objectives"][$result["objective_id"]]["primary"] = false;
				}
				if (is_array($objectives["secondary_ids"]) && array_search($result["objective_id"], $objectives["secondary_ids"]) !== false) {
					$objectives["objectives"][$result["objective_id"]]["secondary"] = true;
				} else {
					$objectives["objectives"][$result["objective_id"]]["secondary"] = false;
				}
				if (is_array($objectives["tertiary_ids"]) && array_search($result["objective_id"], $objectives["tertiary_ids"]) !== false) {
					$objectives["objectives"][$result["objective_id"]]["tertiary"] = true;
				} else {
					$objectives["objectives"][$result["objective_id"]]["tertiary"] = false;
				}
			} else {
				$objectives["objectives"][$result["objective_id"]]["objective_primary_children"] = 0;
				$objectives["objectives"][$result["objective_id"]]["objective_secondary_children"] = 0;
				$objectives["objectives"][$result["objective_id"]]["objective_tertiary_children"] = 0;
				$objectives["objectives"][$result["objective_id"]]["name"] = $result["objective_name"];
				$objectives["objectives"][$result["objective_id"]]["description"] = (isset($objectives["objectives"][$result["objective_id"]]["objective_details"]) && $objectives["objectives"][$result["objective_id"]]["objective_details"] ? $objectives["objectives"][$result["objective_id"]]["objective_details"] : $result["objective_description"]);
				$objectives["objectives"][$result["objective_id"]]["parent"] = $parent_id;
				$objectives["objectives"][$result["objective_id"]]["parent_ids"] = $objectives["objectives"][$parent_id]["parent_ids"];
				$objectives["objectives"][$result["objective_id"]]["parent_ids"][] = $parent_id;
				if (is_array($objectives["primary_ids"]) && array_search($result["objective_id"], $objectives["primary_ids"]) !== false) {
					$objectives["objectives"][$result["objective_id"]]["primary"] = true;
					foreach ($objectives["objectives"][$result["objective_id"]]["parent_ids"] as $parent_id) {
						if ($parent_id != $top_level_id) {
							$objectives["objectives"][$parent_id]["objective_primary_children"]++;
						}
					}
				} else {
					$objectives["objectives"][$result["objective_id"]]["primary"] = false;
				}
				if (is_array($objectives["secondary_ids"]) && array_search($result["objective_id"], $objectives["secondary_ids"]) !== false) {
					$objectives["objectives"][$result["objective_id"]]["secondary"] = true;
					foreach ($objectives["objectives"][$result["objective_id"]]["parent_ids"] as $parent_id) {
						if ($parent_id != $top_level_id) {
							$objectives["objectives"][$parent_id]["objective_secondary_children"]++;
						}
					}
				} else {
					$objectives["objectives"][$result["objective_id"]]["secondary"] = false;
				}
				if (is_array($objectives["tertiary_ids"]) && array_search($result["objective_id"], $objectives["tertiary_ids"]) !== false) {
					$objectives["objectives"][$result["objective_id"]]["tertiary"] = true;
					foreach ($objectives["objectives"][$result["objective_id"]]["parent_ids"] as $parent_id) {
						if ($parent_id != $top_level_id) {
							$objectives["objectives"][$parent_id]["objective_tertiary_children"]++;
						}
					}
				} else {
					$objectives["objectives"][$result["objective_id"]]["tertiary"] = false;
				}
			}
			list($objectives,$top_level_id) = courses_fetch_objectives($org_id,$course_ids,$top_level_id, $result["objective_id"], $objectives);
		}
	}
	if ($parent_id == $top_level_id) {
		foreach ($objectives["primary_ids"] as $primary_id) {
			if (is_array($objectives["objectives"][$primary_id]["parent_ids"])) {
				foreach ($objectives["objectives"][$primary_id]["parent_ids"] as $parent_id) {
					if (array_search($parent_id, $objectives["used_ids"]) !== false) {
						unset($objectives["used_ids"][$primary_id]);
						unset($objectives["primary_ids"][$primary_id]);
						$objectives["objectives"][$primary_id]["primary"] = false;
						$objectives["objectives"][$parent_id]["objective_primary_children"]--;
					}
				}
			}
		}
		foreach ($objectives["secondary_ids"] as $secondary_id) {
			if (is_array($objectives["objectives"][$secondary_id]["parent_ids"])) {
				foreach ($objectives["objectives"][$secondary_id]["parent_ids"] as $parent_id) {
					if (array_search($parent_id, $objectives["used_ids"]) !== false) {
						unset($objectives["used_ids"][$secondary_id]);
						unset($objectives["secondary_ids"][$secondary_id]);
						$objectives["objectives"][$secondary_id]["secondary"] = false;
						$objectives["objectives"][$parent_id]["objective_secondary_children"]--;
					}
				}
			}
		}
		foreach ($objectives["tertiary_ids"] as $tertiary_id) {
			if (is_array($objectives["objectives"][$tertiary_id]["parent_ids"])) {
				foreach ($objectives["objectives"][$tertiary_id]["parent_ids"] as $parent_id) {
					if (array_search($parent_id, $objectives["used_ids"]) !== false) {
						unset($objectives["used_ids"][$tertiary_id]);
						unset($objectives["tertiary_ids"][$tertiary_id]);
						$objectives["objectives"][$tertiary_id]["tertiary"] = false;
						$objectives["objectives"][$parent_id]["objective_tertiary_children"]--;
					}
				}
			}
		}
	}
	if ($event_id) {
		$event_objectives_string = "";
		foreach ($objectives["objectives"] as $objective_id => $objective) {
			if (isset($event_objectives_string) && $event_objectives_string) {
				$event_objectives_string .= ", ".$db->qstr($objective_id);
			} else {
				$event_objectives_string = $db->qstr($objective_id);
			}
		}
		$event_objectives = $db->GetAll("	SELECT a.* FROM `event_objectives` AS a
											JOIN `global_lu_objectives` AS b
											ON a.`objective_id` = b.`objective_id`
											JOIN `objective_organisation` AS c
											ON b.`objective_id` = c.`objective_id`
											WHERE a.`event_id` = ".$db->qstr($event_id)."
											AND c.`organisation_id` = ".$db->qstr($org_id)."
											AND a.`objective_type` = 'course'
											AND a.`objective_id` IN (".$event_objectives_string.")
											ORDER BY b.`objective_order` ASC");
		if ($event_objectives) {
			foreach ($event_objectives as $objective) {
				if ($objectives["objectives"][$objective["objective_id"]]["primary"] ||
						$objectives["objectives"][$objective["objective_id"]]["secondary"] ||
						$objectives["objectives"][$objective["objective_id"]]["tertiary"] ||
						(is_array($objectives["objectives"][$objective["objective_id"]]["parent_ids"]) && $objectives["used_ids"] && count(array_intersect($objectives["objectives"][$objective["objective_id"]]["parent_ids"], $objectives["used_ids"])))
						) {
					$objectives["objectives"][$objective["objective_id"]]["event_objective_details"] = $objective["objective_details"];
					$objectives["objectives"][$objective["objective_id"]]["event_objective"] = true;
				}
			}
		}
	}
	return array($objectives,$top_level_id);
}

/**
* Recursively loops up tree from mapped course objectives checking each parent to see if its the passed objective id.
* Parents are collected and passed to the next iteration as a group to save function calls
*/
function course_objective_has_child_mapped($objective_id,$course_id, $include_bottom = false){
	global $db;
	$query = "	SELECT a.*
				FROM `global_lu_objectives` a
				LEFT JOIN `course_objectives` b
				ON a.`objective_id` = b.`objective_id`
				AND b.`course_id` = ".$db->qstr($course_id)."
				WHERE b.`course_id` = ".$db->qstr($course_id)."
				AND a.`objective_active` = '1'
                AND b.`active` = '1'
				GROUP BY a.`objective_id`
				ORDER BY a.`objective_id` ASC";
	$objectives = $db->GetAll($query);
	if (!$objectives) return false;
	return course_objective_child_recursive($objectives,$objective_id,$course_id,$include_bottom);
}

function course_objective_child_recursive($objectives,$objective_id,$course_id,$include_bottom = false){
	global $db;
	$parents = array();
	foreach ($objectives as $objective) {
		if ($include_bottom && $objective["objective_id"] == $objective_id) {
			return true;
		}
		$query = "	SELECT a.*
					FROM `global_lu_objectives` a
					LEFT JOIN `course_objectives` b
					ON a.`objective_id` = b.`objective_id`
					AND b.`course_id` = ".$db->qstr($course_id)."
					WHERE a.`objective_id` = ".$db->qstr($objective["objective_parent"])."
					AND a.`objective_active` = '1'
                    AND b.`active` = '1'
					GROUP BY a.`objective_id`
					ORDER BY a.`objective_order` ASC
					";
		$parent = $db->GetRow($query);
		if ($parent) {
			//if this parent is the objective id we're looking for, return true
			if ($parent["objective_id"] == $objective_id) {
				return true;
			}
			$parents[] = $parent;
		}
	}
	//if no parents have been found for this level of parents, no children exist for this id
	if (!$parents) return false;
	return course_objective_child_recursive($parents,$objective_id,$course_id,$include_bottom);
}


/**
 *
 * @param type $objectives
 * @param type $parent_id
 * @param type $top_level_id
 * @param type $edit_importance
 * @param type $parent_active
 * @param type $importance
 * @param type $selected_only
 * @param type $top
 * @param type $display_importance
 * @param type $hierarchical
 * @return string
 */
function course_objectives_in_list($objectives, $parent_id, $top_level_id, $edit_importance = false, $parent_active = false, $importance = 1, $selected_only = false, $top = true, $display_importance = "primary", $hierarchical = true, $full_objective_list = false, $org_id = 0) {
	global $ENTRADA_USER, $translate;
	$output = "";
	$active = array("primary" => false, "secondary" => false, "tertiary" => false);
	$org_id = ($org_id == 0 ? $ENTRADA_USER->getActiveOrganisation() : (int) $org_id );

	if ($top) {
		if ($selected_only) {
			foreach ($objectives["objectives"] as $objective_id => $objective) {
				if (isset($objective["event_objective"]) && $objective["event_objective"]) {
					if (!$active["primary"] && $objective["primary"]) {
						$active["primary"] = true;
					} elseif (!$active["secondary"] && $objective["secondary"]) {
						$active["secondary"] = true;
					} elseif (!$active["tertiary"] && $objective["tertiary"]) {
						$active["tertiary"] = true;
					}
				}
			}
			if (!$active["primary"]) {
				$display_importance = "secondary";
			} elseif (!$active["secondary"] && !$active["primary"]) {
				$display_importance = "tertiary";
			} elseif (!$active["tertiary"] && !$active["secondary"] && !$active["primary"]) {
				return;
			}
		} else {
			if ($objectives["primary_ids"]) {
				$active["primary"] = true;
				$display_importance = "primary";
			}
			if ($objectives["secondary_ids"]) {
				$active["secondary"] = true;
				if (empty($objectives["primary_ids"])) {
					$display_importance = "secondary";
				}
			}
			if ($objectives["tertiary_ids"]) {
				$active["tertiary"] = true;
				if (empty($objectives["primary_ids"]) && empty($objectives["secondary_ids"])) {
					$display_importance = "tertiary";
				}
			}
		}
		$objectives = $objectives["objectives"];
		if ($display_importance == "primary" && !$active["primary"]) {
			return;
		}
	}

	if (!$full_objective_list) {
	    if (!isset($objectives["used_ids"])) {
            $objectives["used_ids"] = [];
        }
		$full_objective_list = events_fetch_objectives_structure($parent_id, $objectives["used_ids"], $org_id);
	}
	$flat_objective_list = events_flatten_objectives($full_objective_list);

	if ((is_array($objectives)) && (count($objectives))) {
		if (((isset($objectives[$parent_id]) && count($objectives[$parent_id]["parent_ids"])) || ($hierarchical && (!$selected_only || isset($objective["event_objective"]) && $objective["event_objective"] && (isset($objective[$display_importance]) && $objective[$display_importance])))) && (!isset($objectives[$parent_id]["parent_ids"]) || count($objectives[$parent_id]["parent_ids"]) < 3)) {
			$output .= "\n<ul class=\"objective-list\" id=\"objective_".$parent_id."_list\"".(((count($objectives[$parent_id]["parent_ids"]) < 2 && !$hierarchical) || ($hierarchical && $parent_id == $top_level_id)) ? " style=\"padding-left: 0; margin: 0\"" : "").">\n";
		}
		$iterated = false;
		do {
			if ($iterated) {
				if ($display_importance == "primary" && $active["secondary"]) {
					$display_importance = "secondary";
				} elseif ((($display_importance == "secondary" || $display_importance == "primary") && $active["tertiary"])) {
					$display_importance = "tertiary";
				}
			}

			if ($flat_objective_list && is_array($flat_objective_list)) {
				if ($top) {
					$output .= "<h2".($iterated && !$hierarchical ? " class=\"collapsed\"" : "")." title=\"".ucwords($display_importance)." ".$translate->_("Objectives")."\">".ucwords($display_importance)." ".$translate->_("Objectives")."</h2>\n";
					$output .= "<div id=\"".($display_importance)."-objectives\">\n";
				}

				foreach ($flat_objective_list as $objective_id => $objective_active) {
				    if (isset($objectives[$objective_id])) {
                        $objective = $objectives[$objective_id];
                        if (($objective["parent"] == $parent_id) && (((($objective["objective_" . $display_importance . "_children"]) || ((isset($objective[$display_importance]) && $objective[$display_importance]) || $parent_active)) && !$selected_only) || ($selected_only && isset($objective["event_objective"]) && $objective["event_objective"] && (isset($objective[$display_importance]) && $objective[$display_importance])))) {
                            $importance = ((isset($objective["primary"]) && $objective["primary"]) ? 1 : ((isset($objective["secondary"]) && $objective["secondary"]) ? 2 : ((isset($objective["tertiary"]) && $objective["tertiary"]) ? 3 : $importance)));
                            $output .= "<li" . ((($parent_active) || (isset($objective[$display_importance]) && $objective[$display_importance])) && (count($objective["parent_ids"]) > 2) ? " class=\"\"" : "") . " id=\"objective_" . $objective_id . "_row\">\n";
                            if (($edit_importance) && (isset($objective[$display_importance]) && $objective[$display_importance])) {
                                $output .= "<select onchange=\"javascript: moveObjective('" . $objective_id . "', this.value);\" style=\"float: right; margin: 5px\">\n";
                                $output .= "	<option value=\"primary\"" . (($objective["primary"]) ? " selected=\"selected\"" : "") . ">Primary</option>\n";
                                $output .= "	<option value=\"secondary\"" . (($objective["secondary"]) ? " selected=\"selected\"" : "") . ">Secondary</option>\n";
                                $output .= "	<option value=\"tertiary\"" . (($objective["tertiary"]) ? " selected=\"selected\"" : "") . ">Tertiary</option>\n";
                                $output .= "</select>";
                            }
                            if (count($objective["parent_ids"]) == 3) {
                                $output .= "	<div><div class=\"objective-title\">" . $objective["name"] . "</div>";
                                $output .= "	<div class=\"objective-description content-small\">" . (isset($objective["objective_details"]) && $objective["objective_details"] ? $objective["objective_details"] : $objective["description"]) . "</div>";
                            } else {
                                $output .= "	<div class=\"objective-title\" id=\"objective_" . $objective_id . "\">" . $objective["name"] . "</div>\n";
                                $output .= "	<div class=\"objective-description content-small\">" . (isset($objective["objective_details"]) && $objective["objective_details"] ? $objective["objective_details"] : $objective["description"]);
                            }
                            if (isset($objective["event_objective_details"]) && $objective["event_objective_details"]) {
                                $output .= "	<div class=\"objective-description content-small\"><em>" . $objective["event_objective_details"] . "</em></div>";
                            }
                            $output .= "	</div>";
                            $output .= "</li>";
                        }
                        if ($objective["parent"] == $parent_id) {
                            $output .= course_objectives_in_list($objectives, $objective_id, $top_level_id,
                                $edit_importance,
                                ((isset($objective[$display_importance]) && $objective[$display_importance]) || $parent_active ? true : false),
                                $importance, $selected_only, false, $display_importance, $hierarchical,
                                $full_objective_list);
                        }
                    }
				}

				if ($top) {
					$output .= "</div>\n";
				}
			}

			$iterated = true;
		} while ((($display_importance != "tertiary") && ($display_importance != "secondary" || $active["tertiary"]) && ($display_importance != "primary" || $active["secondary"] || $active["tertiary"])) && $top);

		if (((isset($objectives[$parent_id]) && count($objectives[$parent_id]["parent_ids"])) || ($hierarchical && (!$selected_only || isset($objective["event_objective"]) && $objective["event_objective"] && (isset($objective[$display_importance]) && $objective[$display_importance])))) && (!isset($objectives[$parent_id]["parent_ids"]) || count($objectives[$parent_id]["parent_ids"]) < 3)) {
			$output .= "</ul>";
		}
	}

	return $output;
}

function events_subnavigation($event_info,$tab='content'){
	global $ENTRADA_ACL;
	echo "<div class=\"no-printing\">\n";
	echo "	<ul class=\"nav nav-tabs\">";
	if ($ENTRADA_ACL->amIAllowed(new EventResource($event_info["event_id"], $event_info["course_id"], $event_info["organisation_id"]), 'update')) {
		echo "		<li".($tab=='edit'?' class="active"':'')."><a href=\"".ENTRADA_URL."/admin/events?".replace_query(array("section" => "edit", "id" => $event_info['event_id'], "step" => false))."\" >Setup</a></li>";
	}
	echo "		<li".($tab=='content'?' class="active"':'')."><a href=\"".ENTRADA_URL."/admin/events?".replace_query(array("section" => "content", "id" => $event_info['event_id'], "step" => false))."\" >Content</a></li>";

	echo "		<li".($tab=='attendance'?' class="active"':'')."><a href=\"".ENTRADA_URL."/admin/events?".replace_query(array("section" => "attendance", "id" => $event_info["event_id"],"step"=>false))."\" >Attendance</a></li>";
    if ($event_info["recurring_id"]) {
	    echo "		<li".($tab=='recurring'?' class="active"':'')."><a href=\"".ENTRADA_URL."/admin/events?".replace_query(array("section" => "recurring", "id" => $event_info["event_id"],"step"=>false))."\" >Recurring Events</a></li>";
    }

	echo "		<li".($tab=='history'?' class="active"':'')."><a href=\"".ENTRADA_URL."/admin/events?".replace_query(array("section" => "history", "id" => $event_info["event_id"],"step"=>false))."\">History</a></li>";
    echo "		<li".($tab=='statistics'?' class="active"':'')."><a href=\"".ENTRADA_URL."/admin/events?".replace_query(array("section" => "statistics", "id" => $event_info["event_id"],"step"=>false))."\">Statistics</a></li>";            
	echo "	</ul>";
	echo "</div>\n";

}


/**
 * Returns all audience members for the specified course.
 *
 * @global object $db
 * @param int $course_id
 * @return array
 */
function course_fetch_course_audience($course_id = 0, $organisation_id = false,$group = false, $role = false) {
	global $db;
	$course_id = (int) $course_id;
	if ($course_id) {
		$query = "SELECT a.* FROM `course_audience` a
					LEFT JOIN `curriculum_periods` b
					ON a.`cperiod_id` = b.`cperiod_id`
					WHERE a.`course_id` = ".$db->qstr($course_id)."
					AND (
						(
						a.`cperiod_id` != 0
						AND b.`start_date` < ".$db->qstr(time())."
						AND b.`finish_date` > ".$db->qstr(time())."
						)
						OR
						(
						a.`enroll_start` < ".$db->qstr(time())."
						AND (a.`enroll_finish` > ".$db->qstr(time())." OR a.`enroll_finish` = 0)
						)
					)";
		$results = $db->GetAll($query);
		if ($results) {
			$course_audience = array();
			foreach ($results as $result) {
				switch ($result["audience_type"]) {
					case "cohort" :	// Cohorts
					case "group_id" : // Course Groups
						$query = "	SELECT u.*,u.`id` AS proxy_id, CONCAT_WS(', ',u.`lastname`,u.`firstname`) AS fullname
									FROM `group_members` a
									JOIN `".AUTH_DATABASE."`.`user_data` u
									ON a.`proxy_id` = u.`id`
									AND a.`group_id` = ".$db->qstr($result["audience_value"])."
									AND a.`member_active` = 1
									JOIN `".AUTH_DATABASE."`.`user_access` ua
									ON u.`id` = ua.`user_id`".($group?" AND ua.`group` = ".$db->qstr($group):"").($role?" AND ua.`role` = ".$db->qstr($role):"")."
									AND ua.`app_id` IN (".AUTH_APP_IDS_STRING.") "
									.($organisation_id?
									"WHERE u.`organisation_id` = ".$db->qstr($organisation_id):"");
						$group_audience = $db->getAll($query);
						if ($group_audience) {
							$course_audience = array_merge($course_audience,$group_audience);
						}
					break;
					case "proxy_id" : // Learners
						$query = "	SELECT u.*,u.`id` AS proxy_id, CONCAT_WS(', ',u.`lastname`,u.`firstname`) AS fullname, d.`eattendance_id` AS `has_attendance` FROM
									`".AUTH_DATABASE."`.`user_data` u
									JOIN `".AUTH_DATABASE."`.`user_access` ua
									ON u.`id` = ua.`user_id`
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									WHERE u.`id` = ".$db->qstr($result["audience_value"])."
									AND ua.`app_id` IN (".AUTH_APP_IDS_STRING.")".
									($group?" AND ua.`group` = ".$db->qstr($group):"").
									($role?" AND ua.`role` = ".$db->qstr($role):"").
									($organisation_id? " AND u.`organisation_id` = ".$db->qstr($organisation_id):"");
						$user_audience = $db->getAll($query);
						if ($user_audience) {
							$course_audience = array_merge($course_audience,$user_audience);
						}
					break;
					default : // No longer supported, but include the value just in case.
						application_log("notice", "audience_type [".$result["audience_type"]."] is no longer supported, but is used in event_id [".$event_id."].");
					break;
				}
			}
			$course_audience = array_unique($course_audience,SORT_REGULAR);
			usort($course_audience,"audience_sort");
			return $course_audience;
		}
	}
	return false;
}

/**
 * Function used by public events and admin events index to setup and process the selected sort ordering
 * and pagination.
 */
function events_process_sorting() {
	/**
	 * Update requested length of time to display.
	 * Valid: day, week, month, year
	 */
	if (isset($_GET["dtype"])) {
		if (in_array(trim($_GET["dtype"]), array("day", "week", "month", "year", "ayear"))) {
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"] = trim($_GET["dtype"]);
		}

		$_SERVER["QUERY_STRING"] = replace_query(array("dtype" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"])) {
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"] = "week";
		}
	}

	/**
	 * Update requested timestamp to display.
	 * Valid: Unix timestamp
	 */
	if (isset($_GET["dstamp"])) {
		$integer = (int) trim($_GET["dstamp"]);
		if ($integer) {
			$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"] = $integer;
		}

		$_SERVER["QUERY_STRING"] = replace_query(array("dstamp" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])) {
			$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"] = time();
		}
	}

	/**
	 * Update requested column to sort by.
	 * Valid: date, course, teacher, title, term
	 */
	if (isset($_GET["sb"])) {
		if (in_array(trim($_GET["sb"]), array("date", "course", "teacher", "title", "term"))) {
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"] = trim($_GET["sb"]);
		}

		$_SERVER["QUERY_STRING"] = replace_query(array("sb" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"])) {
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["sb"] = "date";
		}
	}

	/**
	 * Update requested order to sort by.
	 * Valid: asc, desc
	 */
	if (isset($_GET["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER]["events"]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

		$_SERVER["QUERY_STRING"] = replace_query(array("so" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["so"])) {
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["so"] = "asc";
		}
	}

	/**
	 * Update requsted number of rows per page.
	 * Valid: any integer really.
	 */
	if ((isset($_GET["pp"])) && ((int) trim($_GET["pp"]))) {
		$integer = (int) trim($_GET["pp"]);

		if (($integer > 0) && ($integer <= 250)) {
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["pp"] = $integer;
		}

		$_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["pp"])) {
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["pp"] = DEFAULT_ROWS_PER_PAGE;
		}
	}
}

/**
 * Function used by community reports to output the HTML for both the filter
 * controls and current filter status (Showing Statistics That Include:) box.
 */
function tracking_output_filter_controls($module_type = "") {
	global $db, $ENTRADA_ACL, $COMMUNITY_ID;

	/**
	 * Determine whether or not this is being called from the admin section.
	 */
	if ($module_type == "admin") {
		$module_type = "/admin";
	} else {
		$module_type = "";
	}
	?>
	<table id="filterList" style="clear: both; width: 100%" cellspacing="0" cellpadding="0" border="0" summary="Event Filters">
		<tr>
			<td style="width: 53%; vertical-align: top">
				<form action="<?php echo ENTRADA_RELATIVE.$module_type; ?>/communities/reports" method="get" id="filter_edit" name="filter_edit">
				<input type="hidden" name="community" value="<?php echo $COMMUNITY_ID;?>" />
				<input type="hidden" name="action" value="filter_edit" />
				<input type="hidden" id="filter_edit_type" name="filter_type" value="" />
				<input type="hidden" id="multifilter" name="filter" value="" />
				<select id="filter_select" class="span12" onchange="showMultiSelect();">
					<option>Select Filter</option>
					<option value="members">Member Filters</option>
					<option value="module">Module Type Filters</option>
					<option value="page">Page Filters</option>
					<option value="action">Action Filters</option>
				</select>
				<span id="filter_options_loading" style="display:none; vertical-align: middle"><img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif" width="16" height="16" alt="Please Wait" title="" style="vertical-align: middle" /> Loading ... </span>
				<span id="options_container"></span>
				</form>
				<script type="text/javascript">
				var multiselect = [];
				var id;
				function showMultiSelect() {
					$$('select_multiple_container').invoke('hide');
					id = $F('filter_select');
					if (multiselect[id]) {
						multiselect[id].container.show();
					} else {
						new Ajax.Request('<?php echo ENTRADA_URL."/api/tracking_filters.api.php";?>', {
                            parameters: {options_for: id,community_id:<?php echo $COMMUNITY_ID;?>},
							method: "GET",
							onLoading: function() {
								$('filter_options_loading').show();
							},
							onSuccess: function(response) {
								$('options_container').insert(response.responseText);
								if ($(id+'_options')) {
									$('filter_edit_type').value = id;
									$(id+'_options').addClassName('multiselect-processed');

									multiselect[id] = new Control.SelectMultiple('multifilter',id+'_options',{
										checkboxSelector: 'table.select_multiple_table tr td input[type=checkbox]',
											nameSelector: 'table.select_multiple_table tr td.select_multiple_name label',
											filter: id+'_select_filter',
											resize: id+'_scroll',
											afterCheck: function(element) {
												var tr = $(element.parentNode.parentNode);
												tr.removeClassName('selected');
												if (element.checked) {
													tr.addClassName('selected');
												}
											}
									});

									$(id+'_cancel').observe('click',function(event){
										this.container.hide();
										$('filter_select').options.selectedIndex = 0;
										$('filter_select').show();
										return false;
									}.bindAsEventListener(multiselect[id]));

									$(id+'_close').observe('click',function(event){
										this.container.hide();
										$('filter_edit').submit();
										return false;
									}.bindAsEventListener(multiselect[id]));

									multiselect[id].container.show();
								}
							},
							onError: function(response) {
								alert("There was an error retrieving the events filter requested. Please try again.")
							},
							onComplete: function() {
								$('filter_options_loading').hide();
							}
						});
					}
					return false;
				}
				function setDateValue(field, date) {
					timestamp = getMSFromDate(date);
					if (field.value != timestamp) {
						window.location = '<?php echo ENTRADA_URL.$module_type."/events?".(($_SERVER["QUERY_STRING"] != "") ? replace_query(array("dstamp" => false))."&" : ""); ?>dstamp='+timestamp;
					}
					return;
				}
				</script>
			</td>
			<td style="width: 47%; vertical-align: top">
				<?php
				if ((is_array($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"])) && (count($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]))) {
					echo "<table class=\"inner-content-box\" id=\"filter-list\" cellspacing=\"0\" summary=\"Selected Filter List\">\n";
					echo "<thead>\n";
					echo "	<tr>\n";
					echo "		<td class=\"inner-content-box-head\">Showing Events That Include:</td>\n";
					echo "	</tr>\n";
					echo "</thead>\n";
					echo "<tbody>\n";
					echo "	<tr>\n";
					echo "		<td class=\"inner-content-box-body\">";
					echo "		<div id=\"filter-list-resize-handle\" style=\"margin:0px -6px -6px -7px;\">";
					echo "		<div id=\"filter-list-resize\" style=\"height: 60px; overflow: auto;  padding: 0px 6px 6px 6px;\">\n";
					foreach ($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"] as $filter_type => $filter_contents) {
						if (is_array($filter_contents)) {
							echo 	$filter_name = filter_name($filter_type);
							echo "	<div style=\"margin: 2px 0px 10px 3px\">\n";
							foreach ($filter_contents as $filter_key => $filter_value) {
								echo "	<div id=\"".$filter_type."_".$filter_key."\">";
								echo "		<a href=\"".ENTRADA_URL.$module_type."/communities/reports?community=".$COMMUNITY_ID."&action=filter_remove&amp;filter=".$filter_type."_".$filter_key."\" title=\"Remove this filter\">";
								switch ($filter_type) {
									case "members" :
									case "student" :
										echo get_account_data("fullname", $filter_value);
									break;
									case "organisation":
										echo fetch_organisation_title($filter_value);
									break;
									case 'action':
										echo ucwords(str_replace('-',' ',$filter_value));
										break;
									case 'page':
										echo get_page_name($filter_value);
										break;
									default :
										echo ucwords($filter_value);
									break;
								}
								echo "		</a>";
								echo "	</div>\n";
							}
							echo "	</div>\n";
						}
					}
					echo "		</div>\n";
					echo "		</div>\n";
					echo "		</td>\n";
					echo "	</tr>\n";
					echo "</tbody>\n";
					echo "</table>\n";
					echo "<br />\n";
					echo "<script type=\"text/javascript\">";
					echo "	new ElementResizer($('filter-list-resize'), {handleElement: $('filter-list-resize-handle'), min: 40});";
					echo "</script>";
				}
				?>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Function used by public events and admin events index to output the HTML for
 * the left hand sidebar.
 */
function events_output_sidebar($module_type = "") {
    global $translate;

    /**
     * Determine whether or not this is being called from the admin section.
     */
    if ($module_type == "admin") {
        $module_type = "/admin";
    } else {
        $module_type = "";
    }

    $sidebar_html = "";

    $sidebar_html .= "<ul class=\"menu none\">";
    $sidebar_html .= "    <li><i class=\"fa fa-filter\"></i> <a href=\"" . ENTRADA_RELATIVE . $module_type . "/events?" . replace_query(array("action" => "filter_defaults")) . "\">" . $translate->_("Apply Default Filters") . "</a></li>";
    $sidebar_html .= "    <li><i class=\"fa fa-trash\"></i> <a href=\"" . ENTRADA_RELATIVE . $module_type . "/events?" . replace_query(array("action" => "filter_removeall")) . "\">" . $translate->_("Remove All Filters") . "</a></li>";
    $sidebar_html .= "</ul>";
    $sidebar_html .= "<br /><br />";
	$sidebar_html .= "<ul class=\"menu none\">";
    if ($module_type == "/admin") {
        $sidebar_html .= "<li><img src=\"" . ENTRADA_RELATIVE . "/images/legend-not-accessible.gif\" alt=\"\" /> " . $translate->_("Currently Not Accessible") . "</li>\n";
    }
    $sidebar_html .= "    <li><img src=\"" . ENTRADA_RELATIVE . "/images/legend-updated.gif\" alt=\"\" /> " . $translate->_("Recently Updated") . "</li>\n";
    $sidebar_html .= "    <li><img src=\"" . ENTRADA_RELATIVE . "/images/legend-individual.gif\" alt=\"\" /> " . $translate->_("Individual Learning Event") . "</li>\n";
    $sidebar_html .= "</ul>\n";

	new_sidebar_item($translate->_("Learning Events"), $sidebar_html, "event-legend", "open");
}


/**
 * Function used by public events and admin events index to output the HTML for both the filter
 * controls and current filter status (Showing Events That Include:) box.
 */
function events_output_filter_controls($module_type = "") {
	global $db, $ENTRADA_ACL, $ENTRADA_USER, $translate;

	/**
	 * Determine whether or not this is being called from the admin section.
	 */
	if ($module_type == "admin") {
		$module_type = "/admin";
	} else {
		$module_type = "";
	}

	/**
	 * Retrieve this from the language file for this template.
	 */
	$filter_controls = $translate->_("events_filter_controls");
	?>

	<table id="filterList" style="clear: both; width: 100%" cellspacing="0" cellpadding="0" border="0" summary="Event Filters">
		<tr>
			<td style="width: 53%; vertical-align: top">
				<form action="<?php echo ENTRADA_RELATIVE.$module_type; ?>/events" method="get" id="filter_edit" name="filter_edit" style="position: relative;" class="form-horizontal">
				<input type="hidden" name="action" value="filter_edit" />
				<input type="hidden" id="filter_edit_type" name="filter_type" value="" />
				<input type="hidden" id="multifilter" name="filter" value="" />
				<div class="control-group">
				 <label for="filter_select" class="control-label" style="width:100px;font-weight:bold;text-align:left;vertical-align: middle">Apply Filter:</label>
				 <div class="controls" style="margin-left:100px">
				 <select id="filter_select" onchange="showMultiSelect();">
					 <option>Select Filter</option>
					 <?php
					 if ($filter_controls) {
						 foreach ($filter_controls as $value => $control) {
							 echo "<option value=\"" . $value . "\">" . $control["label"] . "</option>";
						 }
					 }
					 ?>
				 </select>
				 </div>
				</div>
				<span id="filter_options_loading" style="display:none; vertical-align: middle"><img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif" width="16" height="16" alt="Please Wait" title="" style="vertical-align: middle" /> Loading ... </span>
				<span id="options_container"></span>
				</form>
				<script type="text/javascript">
				var multiselect = [];
				var id;
				function showMultiSelect() {
					$$('select_multiple_container').invoke('hide');
					id = $F('filter_select');

					if (multiselect[id]) {
						multiselect[id].container.show();
					} else {
						new Ajax.Request('<?php echo ENTRADA_URL."/api/events_filters.api.php";?>', {
							parameters: {options_for: id},
							method: "GET",
							onLoading: function() {
								$('filter_options_loading').show();
							},
							onSuccess: function(response) {
								$('options_container').insert(response.responseText);
								if ($(id+'_options')) {
									$('filter_edit_type').value = id;
									$(id+'_options').addClassName('multiselect-processed');

									multiselect[id] = new Control.SelectMultiple('multifilter',id+'_options',{
										checkboxSelector: 'table.select_multiple_table tr td input[type=checkbox]',
											nameSelector: 'table.select_multiple_table tr td.select_multiple_name label',
											filter: id+'_select_filter',
											resize: id+'_scroll',
											afterCheck: function(element) {
												var tr = $(element.parentNode.parentNode);
												tr.removeClassName('selected');
												if (element.checked) {
													tr.addClassName('selected');
												}
											}
									});

									$(id+'_cancel').observe('click',function(event){
										this.container.hide();
										$('filter_select').options.selectedIndex = 0;
										$('filter_select').show();
										return false;
									}.bindAsEventListener(multiselect[id]));

									$(id+'_close').observe('click',function(event){
										this.container.hide();
										$('filter_edit').submit();
										return false;
									}.bindAsEventListener(multiselect[id]));

									multiselect[id].container.show();
								}
							},
							onError: function(response) {
								alert("There was an error retrieving the events filter requested. Please try again.")
							},
							onComplete: function() {
								$('filter_options_loading').hide();
							}
						});
					}
					return false;
				}

				function setDateValue(field, date) {
					timestamp = getMSFromDate(date);
					if (field.value != timestamp) {
						window.location = '<?php echo ENTRADA_URL.$module_type."/events?".(($_SERVER["QUERY_STRING"] != "") ? replace_query(array("dstamp" => false))."&" : ""); ?>dstamp='+timestamp;
					}
					return;
				}
				</script>
			</td>
			<td style="width: 47%; vertical-align: top">
				<?php
				if ((is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"])) && (count($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]))) {
                    echo "<div id=\"filter-list\" class=\"inner-content-box\">\n";
                    echo "    <div class=\"inner-content-box-head\">\n";
                    echo "        Showing Events That Include:\n";
                    echo "    </div>\n";
                    echo "    <div class=\"clearfix inner-content-box-body\">\n";
					echo "        <div id=\"filter-list-resize-handle\">";
					echo "		      <div id=\"filter-list-resize\">\n";
					foreach ($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"] as $filter_type => $filter_contents) {
						if (is_array($filter_contents)) {
							echo 	$filter_name = filter_name($filter_type);
							echo "	      <div>\n";
							foreach ($filter_contents as $filter_key => $filter_value) {
								echo "	      <div id=\"".$filter_type."_".$filter_key."\">";
								echo "		      <a href=\"".ENTRADA_URL.$module_type."/events?action=filter_remove&amp;filter=".$filter_type."_".$filter_key."\" title=\"Remove this filter\">";
								switch ($filter_type) {
									case "teacher" :
									case "student" :
										echo get_account_data("fullname", $filter_value);
									break;
									case "course" :
										echo fetch_course_title($filter_value);
									break;
									case "group" :
										echo fetch_group_title($filter_value);
									break;
									case "eventtype" :
										echo fetch_eventtype_title($filter_value);
									break;
									case "term" :
										echo fetch_term_title($filter_value);
									break;
									case "cp":
									case "co":
										echo fetch_objective_title($filter_value);
									break;
									case "topic":
										echo fetch_event_topic_title($filter_value);
									break;
									case "department":
										echo fetch_department_title($filter_value);
									break;
									case "week":
										echo fetch_week_title($filter_value);
									break;

									default :
										echo strtoupper($filter_value);
									break;
								}
								echo "            </a>";
								echo "        </div>\n";
							}
							echo "        </div>\n";
						}
					}
					echo "            </div>\n";
					echo "        </div>\n";
					echo "    </div>\n";
					echo "</div>\n";

					echo "<script type=\"text/javascript\">";
					echo "	new ElementResizer($('filter-list-resize'), {handleElement: $('filter-list-resize-handle'), min: 40});";
					echo "</script>";
				}
				?>
			</td>
		</tr>
	</table>
	<?php
}


/**
 * Function used by community reports to output the HTML for the calendar controls.
 */
function tracking_output_calendar_controls($module_type = "") {
	global $dates, $COMMUNITY_ID;

	/**
	 * Determine whether or not this is being called from the admin section.
	 */
	if ($module_type == "admin") {
		$module_type = "/admin";
	} else {
		$module_type = "";
	}
	?>
	<table style="width: 100%; margin: 10px 0px 10px 0px" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td style="width: 53%; vertical-align: top; text-align: left">
				<table style="width: 298px; height: 23px" cellspacing="0" cellpadding="0" border="0" summary="Display Duration Type">
					<tr>
						<td style="width: 22px; height: 23px"><a href="<?php echo ENTRADA_URL.$module_type."/communities?section=reports&community=".$COMMUNITY_ID."&".replace_query(array("dstamp" => ($learning_events["duration_start"] - 2))); ?>" title="Previous <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"]); ?>"><img src="<?php echo ENTRADA_URL; ?>/images/cal-back.gif" border="0" width="22" height="23" alt="Previous <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"]); ?>" title="Previous <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"]); ?>" /></a></td>
						<td style="width: 47px; height: 23px"><?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"] == "day") ? "<img src=\"".ENTRADA_URL."/images/cal-day-on.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Day View\" title=\"Day View\" />" : "<a href=\"".ENTRADA_URL.$module_type."/communities?section=reports&community=".$COMMUNITY_ID."&".replace_query(array("dtype" => "day"))."\"><img src=\"".ENTRADA_URL."/images/cal-day-off.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Day View\" title=\"Day View\" /></a>"); ?></td>
						<td style="width: 47px; height: 23px"><?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"] == "week") ? "<img src=\"".ENTRADA_URL."/images/cal-week-on.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Week View\" title=\"Week View\" />" : "<a href=\"".ENTRADA_URL.$module_type."/communities?section=reports&community=".$COMMUNITY_ID."&".replace_query(array("dtype" => "week"))."\"><img src=\"".ENTRADA_URL."/images/cal-week-off.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Week View\" title=\"Week View\" /></a>"); ?></td>
						<td style="width: 47px; height: 23px"><?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"] == "month") ? "<img src=\"".ENTRADA_URL."/images/cal-month-on.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Month View\" title=\"Month View\" />" : "<a href=\"".ENTRADA_URL.$module_type."/communities?section=reports&community=".$COMMUNITY_ID."&".replace_query(array("dtype" => "month"))."\"><img src=\"".ENTRADA_URL."/images/cal-month-off.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Month View\" title=\"Month View\" /></a>"); ?></td>
						<td style="width: 47px; height: 23px"><?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"] == "year") ? "<img src=\"".ENTRADA_URL."/images/cal-year-on.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Year View\" title=\"Year View\" />" : "<a href=\"".ENTRADA_URL.$module_type."/communities?section=reports&community=".$COMMUNITY_ID."&".replace_query(array("dtype" => "year"))."\"><img src=\"".ENTRADA_URL."/images/cal-year-off.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Year View\" title=\"Year View\" /></a>"); ?></td>
						<td style="width: 47px; height: 23px; border-left: 1px #9D9D9D solid"><a href="<?php echo ENTRADA_URL.$module_type."/events?".replace_query(array("dstamp" => ($learning_events["duration_end"] + 1))); ?>" title="Following <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"]); ?>"><img src="<?php echo ENTRADA_URL; ?>/images/cal-next.gif" border="0" width="22" height="23" alt="Following <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"]); ?>" title="Following <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"]); ?>" /></a></td>
						<td style="width: 33px; height: 23px; text-align: right"><a href="<?php echo ENTRADA_URL.$module_type; ?>/communities?section=reports&<?php echo replace_query(array("dstamp" => time())); ?>"><img src="<?php echo ENTRADA_URL; ?>/images/cal-home.gif" width="23" height="23" alt="Reset to display current calendar <?php echo $_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"]; ?>." title="Reset to display current calendar <?php echo $_SESSION[APPLICATION_IDENTIFIER]["tracking"]["dtype"]; ?>." border="0" /></a></td>
						<td style="width: 33px; height: 23px; text-align: right"><img src="<?php echo ENTRADA_URL; ?>/images/cal-calendar.gif" width="23" height="23" alt="Show Calendar" title="Show Calendar" onclick="showCalendar('', document.getElementById('dstamp'), document.getElementById('dstamp'), '<?php echo html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]); ?>', 'calendar-holder', 8, 8, 1)" style="cursor: pointer" id="calendar-holder" /></td>
					</tr>
				</table>
			</td>
			<td style="width: 47%; vertical-align: top; text-align: right">
				<?php
				if ($learning_events["total_pages"] > 1) {
					echo "<form action=\"".ENTRADA_URL.$module_type."/communities?section=reports&community=".$COMMUNITY_ID."\" method=\"get\" id=\"pageSelector\">\n";
					echo "<div style=\"white-space: nowrap\">\n";
					echo "<span style=\"width: 20px; vertical-align: middle; margin-right: 3px; text-align: left\">\n";
					if ($learning_events["page_previous"]) {
						echo "<a href=\"".ENTRADA_URL.$module_type."/communities?section=reports&community=".$COMMUNITY_ID."&".replace_query(array("pv" => $learning_events["page_previous"]))."\"><img src=\"".ENTRADA_URL."/images/record-previous-on.gif\" border=\"0\" width=\"11\" height=\"11\" alt=\"Back to page ".$learning_events["page_previous"].".\" title=\"Back to page ".$learning_events["page_previous"].".\" style=\"vertical-align: middle\" /></a>\n";
					} else {
						echo "<img src=\"".ENTRADA_URL."/images/record-previous-off.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />";
					}
					echo "</span>";
					echo "<span style=\"vertical-align: middle\">\n";
					echo "<select name=\"pv\" onchange=\"$('pageSelector').submit();\"".(($learning_events["total_pages"] <= 1) ? " disabled=\"disabled\"" : "").">\n";
					for ($i = 1; $i <= $learning_events["total_pages"]; $i++) {
						echo "<option value=\"".$i."\"".(($i == $learning_events["page_current"]) ? " selected=\"selected\"" : "").">".(($i == $learning_events["page_current"]) ? " Viewing" : "Jump To")." Page ".$i."</option>\n";
					}
					echo "</select>\n";
					echo "</span>\n";
					echo "<span style=\"width: 20px; vertical-align: middle; margin-left: 3px; text-align: right\">\n";
					if ($learning_events["page_current"] < $learning_events["total_pages"]) {
						echo "<a href=\"".ENTRADA_URL.$module_type."/communities?section=reports&community=".$COMMUNITY_ID."&".replace_query(array("pv" => $learning_events["page_next"]))."\"><img src=\"".ENTRADA_URL."/images/record-next-on.gif\" border=\"0\" width=\"11\" height=\"11\" alt=\"Forward to page ".$learning_events["page_next"].".\" title=\"Forward to page ".$learning_events["page_next"].".\" style=\"vertical-align: middle\" /></a>";
					} else {
						echo "<img src=\"".ENTRADA_URL."/images/record-next-off.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />";
					}
					echo "</span>\n";
					echo "</div>\n";
					echo "</form>\n";
				}
				?>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Function used by public events and admin events index to output the HTML for the calendar controls.
 */
function events_output_calendar_controls($module_type = "", $relative_path = "") {
	global $learning_events;

	$session_key = "events";
	
	switch ($module_type) {
		case "community" :
			$session_key = "community_page";

			$link_prefix = $relative_path;
		break;
		case "admin" :
			$link_prefix = ENTRADA_RELATIVE . "/admin/events";
		break;
		default :
			$link_prefix = ENTRADA_RELATIVE . "/events";
		break;
	}

	if ($relative_path) {
		$link_prefix = $relative_path;
	}

	?>
    <div class="row-fluid space-below<?php echo ($learning_events["total_pages"] <= 1 ? " medium" : ""); ?>">
        <div class="span4">
            <div class="btn-group">
                <a class="btn" href="<?php echo $link_prefix . "?" . replace_query(array("dstamp" => ($learning_events["duration_start"] - 2))); ?>"><i class="icon-chevron-left"></i></a>
                <a class="btn<?php echo ($_SESSION[APPLICATION_IDENTIFIER][$session_key]["dtype"] == "day" ? " active" : ""); ?>" href="<?php echo $link_prefix . "?" . replace_query(array("dtype" => "day")); ?>">Day</a>
                <a class="btn<?php echo ($_SESSION[APPLICATION_IDENTIFIER][$session_key]["dtype"] == "week" ? " active" : ""); ?>" href="<?php echo $link_prefix . "?" . replace_query(array("dtype" => "week")); ?>">Week</a>
                <a class="btn<?php echo ($_SESSION[APPLICATION_IDENTIFIER][$session_key]["dtype"] == "month" ? " active" : ""); ?>" href="<?php echo $link_prefix . "?" . replace_query(array("dtype" => "month")); ?>">Month</a>
                <a class="btn<?php echo ($_SESSION[APPLICATION_IDENTIFIER][$session_key]["dtype"] == "year" ? " active" : ""); ?>" href="<?php echo $link_prefix . "?" . replace_query(array("dtype" => "year")); ?>">Year</a>
                <a class="btn" href="<?php echo $link_prefix . "?" . replace_query(array("dstamp" => ($learning_events["duration_end"] + 1))); ?>"><i class="icon-chevron-right"></i></a>
            </div>
        </div>
        <div class="span2">
            <a class="btn" href="<?php echo $link_prefix . "?" . replace_query(array("dstamp" => time())); ?>"><i class="icon-refresh"></i></a>
            <a class="btn" href="javascript:showCalendar('', document.getElementById('dstamp'), document.getElementById('dstamp'), '<?php echo html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]); ?>', 'calendar-holder', 8, 8, 1);" id="calendar-holder"><i class="icon-calendar"></i></a>
        </div>
        <div class="span6">
            <?php
            if ($learning_events["total_pages"] > 1) {
                $pagination = new Entrada_Pagination($learning_events["page_current"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["pp"], $learning_events["total_rows"], $link_prefix, replace_query());
                echo $pagination->GetPageBar("normal", "right", false);
            }
            ?>
        </div>
    </div>
	<?php
}

/**
 * Function used by public events and admin events index to output the HTML for the calendar controls.
 */
function events_output_calendar_controls_old($module_type = "") {
	global $learning_events;

	/**
	 * Determine whether or not this is being called from the admin section.
	 */
	if ($module_type == "admin") {
		$module_type = "/admin";
	} else {
		$module_type = "";
	}
	?>
	<table style="width: 100%; margin: 10px 0px 10px 0px" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td style="width: 53%; vertical-align: top; text-align: left">
				<table style="width: 298px; height: 23px" cellspacing="0" cellpadding="0" border="0" summary="Display Duration Type">
					<tr>
						<td style="width: 22px; height: 23px"><a href="<?php echo ENTRADA_URL.$module_type."/events?".replace_query(array("dstamp" => ($learning_events["duration_start"] - 2))); ?>" title="Previous <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"]); ?>"><img src="<?php echo ENTRADA_URL; ?>/images/cal-back.gif" border="0" width="22" height="23" alt="Previous <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"]); ?>" title="Previous <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"]); ?>" /></a></td>
						<td style="width: 47px; height: 23px"><?php echo (($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"] == "day") ? "<img src=\"".ENTRADA_URL."/images/cal-day-on.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Day View\" title=\"Day View\" />" : "<a href=\"".ENTRADA_URL.$module_type."/events?".replace_query(array("dtype" => "day"))."\"><img src=\"".ENTRADA_URL."/images/cal-day-off.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Day View\" title=\"Day View\" /></a>"); ?></td>
						<td style="width: 47px; height: 23px"><?php echo (($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"] == "week") ? "<img src=\"".ENTRADA_URL."/images/cal-week-on.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Week View\" title=\"Week View\" />" : "<a href=\"".ENTRADA_URL.$module_type."/events?".replace_query(array("dtype" => "week"))."\"><img src=\"".ENTRADA_URL."/images/cal-week-off.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Week View\" title=\"Week View\" /></a>"); ?></td>
						<td style="width: 47px; height: 23px"><?php echo (($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"] == "month") ? "<img src=\"".ENTRADA_URL."/images/cal-month-on.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Month View\" title=\"Month View\" />" : "<a href=\"".ENTRADA_URL.$module_type."/events?".replace_query(array("dtype" => "month"))."\"><img src=\"".ENTRADA_URL."/images/cal-month-off.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Month View\" title=\"Month View\" /></a>"); ?></td>
						<td style="width: 47px; height: 23px"><?php echo (($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"] == "year") ? "<img src=\"".ENTRADA_URL."/images/cal-year-on.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Year View\" title=\"Year View\" />" : "<a href=\"".ENTRADA_URL.$module_type."/events?".replace_query(array("dtype" => "year"))."\"><img src=\"".ENTRADA_URL."/images/cal-year-off.gif\" width=\"47\" height=\"23\" border=\"0\" alt=\"Year View\" title=\"Year View\" /></a>"); ?></td>
						<td style="width: 47px; height: 23px; border-left: 1px #9D9D9D solid"><a href="<?php echo ENTRADA_URL.$module_type."/events?".replace_query(array("dstamp" => ($learning_events["duration_end"] + 1))); ?>" title="Following <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"]); ?>"><img src="<?php echo ENTRADA_URL; ?>/images/cal-next.gif" border="0" width="22" height="23" alt="Following <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"]); ?>" title="Following <?php echo ucwords($_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"]); ?>" /></a></td>
						<td style="width: 33px; height: 23px; text-align: right"><a href="<?php echo ENTRADA_URL.$module_type; ?>/events?<?php echo replace_query(array("dstamp" => time())); ?>"><img src="<?php echo ENTRADA_URL; ?>/images/cal-home.gif" width="23" height="23" alt="Reset to display current calendar <?php echo $_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"]; ?>." title="Reset to display current calendar <?php echo $_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"]; ?>." border="0" /></a></td>
						<td style="width: 33px; height: 23px; text-align: right"><img src="<?php echo ENTRADA_URL; ?>/images/cal-calendar.gif" width="23" height="23" alt="Show Calendar" title="Show Calendar" onclick="showCalendar('', document.getElementById('dstamp'), document.getElementById('dstamp'), '<?php echo html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]); ?>', 'calendar-holder', 8, 8, 1)" style="cursor: pointer" id="calendar-holder" /></td>
					</tr>
				</table>
			</td>
			<td style="width: 47%; vertical-align: top; text-align: right">
				<?php
				if ($learning_events["total_pages"] > 1) {
					echo "<form action=\"".ENTRADA_URL.$module_type."/events\" method=\"get\" id=\"pageSelector\">\n";
					echo "<div style=\"white-space: nowrap\">\n";
					echo "<span style=\"width: 20px; vertical-align: middle; margin-right: 3px; text-align: left\">\n";
					if ($learning_events["page_previous"]) {
						echo "<a href=\"".ENTRADA_URL.$module_type."/events?".replace_query(array("pv" => $learning_events["page_previous"]))."\"><img src=\"".ENTRADA_URL."/images/record-previous-on.gif\" border=\"0\" width=\"11\" height=\"11\" alt=\"Back to page ".$learning_events["page_previous"].".\" title=\"Back to page ".$learning_events["page_previous"].".\" style=\"vertical-align: middle\" /></a>\n";
					} else {
						echo "<img src=\"".ENTRADA_URL."/images/record-previous-off.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />";
					}
					echo "</span>";
					echo "<span style=\"vertical-align: middle\">\n";
					echo "<select name=\"pv\" onchange=\"$('pageSelector').submit();\"".(($learning_events["total_pages"] <= 1) ? " disabled=\"disabled\"" : "").">\n";
					for ($i = 1; $i <= $learning_events["total_pages"]; $i++) {
						echo "<option value=\"".$i."\"".(($i == $learning_events["page_current"]) ? " selected=\"selected\"" : "").">".(($i == $learning_events["page_current"]) ? " Viewing" : "Jump To")." Page ".$i."</option>\n";
					}
					echo "</select>\n";
					echo "</span>\n";
					echo "<span style=\"width: 20px; vertical-align: middle; margin-left: 3px; text-align: right\">\n";
					if ($learning_events["page_current"] < $learning_events["total_pages"]) {
						echo "<a href=\"".ENTRADA_URL.$module_type."/events?".replace_query(array("pv" => $learning_events["page_next"]))."\"><img src=\"".ENTRADA_URL."/images/record-next-on.gif\" border=\"0\" width=\"11\" height=\"11\" alt=\"Forward to page ".$learning_events["page_next"].".\" title=\"Forward to page ".$learning_events["page_next"].".\" style=\"vertical-align: middle\" /></a>";
					} else {
						echo "<img src=\"".ENTRADA_URL."/images/record-next-off.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />";
					}
					echo "</span>\n";
					echo "</div>\n";
					echo "</form>\n";
				}
				?>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Function used to create the default filter settings for Learning Events
 *
 * @param int $proxy_id
 * @param string $group
 * @param string $role
 * @return array Containing the default filters.
 */
function events_filters_defaults($proxy_id = 0, $group = "", $role = "", $organisation = 0, $course_id = 0) {
	$filters = array();

	switch ($group) {
		case "resident" :
		case "faculty" :
			/**
			 * Teaching faculty see events which they are involved with by default.
                         *  Add 'faculty' role. Issue #1236.  Oct 2016 Bob Walker UBC
			 */
			if (in_array($role, array("resident", "director", "lecturer", "teacher", "faculty"))) {
				$filters["teacher"][0] = (int) $proxy_id;
			}
		break;
		case "student" :
			/**
			 * Students see events they are involved with by default.
			 */
			$filters["student"][0] = (int) $proxy_id;
            if ($course_id) {
                $filters["course"][0] = (int) $course_id;
            }
		break;
		case "medtech" :
		case "staff" :
		default :
            $first_cohort = ((int) fetch_first_cohort());
            if ($first_cohort) {
                $filters["group"][0] = $first_cohort;
            }
		break;
	}

	if (!empty($filters)) {
		ksort($filters);
	}
	return $filters;
}

/**
 * Function used to create the default filter settings for Learning Events
 *
 * @param int $proxy_id
 * @param string $group
 * @param string $role
 * @return array Containing the default filters.
 */
function events_filters_faculty($course_id = 0, $group = "", $role = "", $organisation = 0) {
	$filters = array();

	switch ($group) {
		case "staff" :            
		case "resident" :
		case "medtech" :                    
		case "faculty" :
			/**
			 * Teaching faculty see events which they are involved with by default.
			 */
			if (in_array($role, array("director", "lecturer", "teacher", "staff", "admin"))) {
				$filters["course"][0] = (int) $course_id;
			}
		break;
		default :
            $first_cohort = ((int) fetch_first_cohort());
            if ($first_cohort) {
                $filters["group"][0] = $first_cohort;
            }
		break;
	}

	if (!empty($filters)) {
		ksort($filters);
	}
	return $filters;
}

/**
 * Function used by public events and admin events index to process the provided filter settings.
 */
function events_process_filters($action = "", $module_type = "") {
	global $ENTRADA_USER;
	/**
	 * Determine whether or not this is being called from the admin section.
	 */
	if ($module_type == "admin") {
		$module_type = "/admin";
	} else {
		$module_type = "";
	}

	/**
	 * Handles any page actions for this module.
	 */
	switch ($action) {
		case "filter_add" :
			if (isset($_GET["filter"])) {
				$pieces = explode("_", clean_input($_GET["filter"], array("nows", "lower", "notags")));
				$filter_key = $pieces[0];
				$filter_value = $pieces[1];
				if (($filter_key) && ($filter_value)) {
					$key = 0;

					if ((!is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_key])) || (!in_array($filter_value, $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_key]))) {
						/**
						 * Check to see if this is a student attempting to view the calendar of another student.
						 */
						if (($filter_key != "student") || ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "student") || ($filter_value == $ENTRADA_USER->getActiveId())) {
							$_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_key][] = $filter_value;

							ksort($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]);
						}
					}
				}
			}
			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false, "filter" => false));
		break;
		case "filter_edit" :
			if (isset($_GET["filter"])) {

				$filters = explode(",", clean_input($_GET["filter"], array("nows", "lower", "notags")));
				if (isset($filters[1])) {
					$pieces = explode("_", $filters[0]);
					$filter_key	= $pieces[0];
					unset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_key]);

					foreach ($filters as $filter) {
						$pieces = explode("_", $filter);
						$filter_value = $pieces[1];
						if (($filter_key != "student") || ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "student") || ($filter_value == $ENTRADA_USER->getActiveId())) {
							$_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_key][] = $filter_value;
							ksort($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]);
						}
					}
				} else {
					$pieces = explode("_", $filters[0]);
					$filter_key = $pieces[0];
					$filter_value = $pieces[1];
					if ($filter_value && $filter_key) {
						//This is an actual filter, cool dude. Erase everything else since we only got one and add this one if its not a student looking at another student
						if (($filter_key != "student") || ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "student") || ($filter_value == $ENTRADA_USER->getActiveId())) {
							unset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_key]);
							$_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_key][] = $filter_value;
							ksort($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]);
						}
					} else {
						// This is coming from the select box and nothing was selected, so erase.
						$filter_type = clean_input($_GET["filter_type"], array("nows", "lower", "notags"));
						if (is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_type])) {
							unset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_type]);
						}
					}
				}

				ksort($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]);
			}

			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false, "filter" => false));
		break;
		case "filter_remove" :
			if (isset($_GET["filter"])) {
				$pieces = explode("_", clean_input($_GET["filter"], array("nows", "lower", "notags")));
				$filter_type = $pieces[0];
				$filter_key	= $pieces[1];
				if (($filter_type) && ($filter_key != "") && (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_type][$filter_key]))) {

					unset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_type][$filter_key]);

					if (!@count($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_type])) {
						unset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"][$filter_type]);
					}

					ksort($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]);
				}
			}

			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false, "filter" => false));
		break;
		case "filter_removeall" :
			if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"])) {
				unset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]);
			}

			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false, "filter" => false));
		break;
		case "filter_defaults" :
			/**
			 * If this is the first time this page has been loaded, lets setup the default filters.
			 */
			if (!isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filter_defaults_set"])) {
				$_SESSION[APPLICATION_IDENTIFIER]["events"]["filter_defaults_set"] = true;
			}

			/**
			 * First unset any previous filters if they exist.
			 */
			if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"])) {
				unset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]);
			}

			$_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"] = events_filters_defaults(
				$ENTRADA_USER->getActiveId(),
				$ENTRADA_USER->getActiveGroup(),
				$ENTRADA_USER->getActiveRole()
			);

			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false, "filter" => false));
		break;
		default :
			continue;
		break;
	}

	if (!isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filter_defaults_set"])) {
		header("Location: ".ENTRADA_URL.$module_type."/events?action=filter_defaults");
		exit;
	}
}

function events_process_recurring_eventtimes ($period = "daily", $start_date = 0, $offset = 7, $weekdays = array(), $recurring_end = false) {
    if (!$start_date) {
        $start_date = time();
    }
    if (!$recurring_end || $recurring_end > strtotime("+1 year", $start_date) || $recurring_end <= $start_date) {
        $recurring_end = strtotime("+1 year", $start_date);
    }
    if (!$offset) {
        $offset = 1;
    }
    $output_dates = array();
    $current_session_date = $start_date;
    switch ($period) {
    	case "custom" :
    		$output_dates = array_fill(0, $offset, $current_session_date);
        break;
        case "daily" :
            while ($current_session_date <= $recurring_end) {
                $current_session_date = strtotime("+".$offset." days", $current_session_date);
                if ($current_session_date <= $recurring_end) {
                    $output_dates[] = $current_session_date;
                }
            }
        break;
        case "weekly" :
            while ($current_session_date <= $recurring_end) {
                $current_weekday = date("N", $current_session_date);
                foreach ($weekdays as $weekday) {
                    $weekday_difference = $weekday - $current_weekday;
                    if ($weekday < $current_weekday && $current_weekday == 1 && $weekday_difference < 0) {
                        $weekday_difference = 7 + $weekday_difference;
                    }
                    if ($weekday_difference > 0 || ($current_weekday == 1 && $weekday == 1)) {
                        $event_date = strtotime("+".$weekday_difference." days", $current_session_date);
                        if ($event_date <= $recurring_end && $event_date > $start_date) {
                            $output_dates[] = $event_date;
                        }
                    }
                }
                $current_session_date = strtotime("next monday", $current_session_date);
            }
        break;
        case "monthly" :
            $month_numeric = date("n", $current_session_date);
            $year = date("Y", $current_session_date);
            
            while ($current_session_date <= $recurring_end) {
                $month = date("F", mktime(0, 0, 0, $month_numeric, 10));
                $weekday = $weekdays[0];
                $current_session_date = strtotime($month." ".$year." ".$offset." ".$weekday);
                if ($current_session_date <= $recurring_end && $current_session_date != $start_date) {
                    $output_dates[] = $current_session_date;
                }
                $month_numeric++;
                if ($month_numeric > 12) {
                    $month_numeric = 1;
                    $year++;
                }
            }
        break;
    }
    return $output_dates;
}

/**
 * Function used by community tracking to process the provided filter settings.
 */
function tracking_process_filters($action = "", $module_type = "") {
	global $COMMUNITY_ID, $ENTRADA_USER;
	/**
	 * Determine whether or not this is being called from the admin section.
	 */
	if ($module_type == "admin") {
		$module_type = "/admin";
	} else {
		$module_type = "";
	}

	/**
	 * Handles any page actions for this module.
	 */
	switch ($action) {
		case "filter_add" :
			if (isset($_GET["filter"])) {
				$pieces = explode("_", clean_input($_GET["filter"], array("nows", "lower", "notags")));
				$filter_key = $pieces[0];
				$filter_value = $pieces[1];
				if (($filter_key) && ($filter_value)) {
					$key = 0;

					if ((!is_array($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_key])) || (!in_array($filter_value, $_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_key]))) {
						/**
						 * Check to see if this is a student attempting to view the calendar of another student.
						 */
						if (($filter_key != "student") || ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "student") || ($filter_value == $ENTRADA_USER->getActiveId())) {
							$_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_key][] = $filter_value;

							ksort($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]);
						}
					}
				}
			}

			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false, "filter" => false));
		break;
		case "filter_edit" :
			if (isset($_GET["filter"])) {
				$filters = explode(",", clean_input($_GET["filter"], array("nows", "lower", "notags")));
				if (isset($filters[1])) {
					$pieces = explode("_", $filters[0]);
					$filter_key	= $pieces[0];
					unset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_key]);

					foreach ($filters as $filter) {
						$pieces = explode("_", $filter);
						$filter_value = $pieces[1];
						if (($filter_key != "student") || ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "student") || ($filter_value == $ENTRADA_USER->getActiveId())) {
							$_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_key][] = $filter_value;
							ksort($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]);
						}
					}
				} else {
					$pieces = explode("_", $filters[0]);
					$filter_key = $pieces[0];
					$filter_value = $pieces[1];
					if ($filter_value && $filter_key) {
						//This is an actual filter, cool dude. Erase everything else since we only got one and add this one if its not a student looking at another student
						if (($filter_key != "student") || ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "student") || ($filter_value == $ENTRADA_USER->getActiveId())) {
							unset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_key]);
							$_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_key][] = $filter_value;
							ksort($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]);
						}
					} else {
						// This is coming from the select box and nothing was selected, so erase.
						$filter_type = clean_input($_GET["filter_type"], array("nows", "lower", "notags"));
						if (is_array($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_type])) {
							unset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_type]);
						}
					}
				}

				ksort($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]);
			}

			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false, "filter" => false));
		break;
		case "filter_remove" :
			if (isset($_GET["filter"])) {
				$pieces = explode("_", clean_input($_GET["filter"], array("nows", "lower", "notags")));
				$filter_type = $pieces[0];
				$filter_key	= $pieces[1];
				if (($filter_type) && ($filter_key != "") && (isset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_type][$filter_key]))) {

					unset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_type][$filter_key]);

					if (!@count($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_type])) {
						unset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"][$filter_type]);
					}

					ksort($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]);
				}
			}

			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false, "filter" => false));
		break;
		case "filter_removeall" :
			if (isset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"])) {
				unset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]);
			}

			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false, "filter" => false));
		break;
		case "filter_defaults" :
			/**
			 * If this is the first time this page has been loaded, lets setup the default filters.
			 */
			if (!isset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filter_defaults_set"])) {
				$_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filter_defaults_set"] = true;
			}

			/**
			 * First unset any previous filters if they exist.
			 */
			if (isset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"])) {
				unset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]);
			}

			$_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"] = array();

			$_SERVER["QUERY_STRING"] = replace_query(array("action" => false, "filter" => false));
		break;
		default :
			continue;
		break;
	}

	if (!isset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filter_defaults_set"])) {
		header("Location: ".ENTRADA_URL.$module_type."/communities/reports?community=".$COMMUNITY_ID."&action=filter_defaults");
		exit;
	}
}

function get_page_for_statistic($action_field, $action_value){
	global $db;

	$query = false;

	switch ($action_field) {
		case 'cshare_id':
			$query = "	SELECT `folder_title` AS `page`
						FROM `community_shares`
						WHERE `cshare_id` = ".$db->qstr($action_value);
			break;
		case 'cscomment_id':
			$query = "	SELECT b.`file_title` AS `page`
						FROM `community_share_comments` AS a
						LEFT JOIN `community_share_files` AS b
						ON a.`csfile_id` = b.`csfile_id`
						WHERE a.`cscomment_id` = ".$db->qstr($action_value);
			break;
		case 'csfile_id':
			$query = "	SELECT `file_title` AS `page`
						FROM `community_share_files`
						WHERE `csfile_id` = ".$db->qstr($action_value);
			break;
		case 'csfversion_id':
			$query = "	SELECT b.`file_title` AS `page`
						FROM `community_share_file_versions` AS a
						LEFT JOIN `community_share_files` AS b
						ON a.`csfile_id` = b.`csfile_id`
						WHERE a.`csfversion_id` = ".$db->qstr($action_value);
			break;
		case 'cannouncement_id':
			$query = "	SELECT `announcement_title` AS `page`
						FROM `community_announcements`
						WHERE `cannouncement_id` = ".$db->qstr($action_value);
			break;
		case 'cdiscussion_id':
			$query = "	SELECT `forum_title` AS `page`
						FROM `community_discussions`
						WHERE `cdiscussion_id` = ".$db->qstr($action_value);
			break;
		case 'cdtopic_id':
			$query = "	SELECT `topic_title` AS `page`
						FROM `community_discussion_topics`
						WHERE `cdtopic_id` = ".$db->qstr($action_value);
			break;
		case 'cdfile_id':
			$query = "	SELECT `file_title` AS `page`
						FROM `community_discussions_files`
						WHERE `cdtopic_id` = ".$db->qstr($action_value);
			break;
		case 'cevent_id':
			$query = "	SELECT `event_title` AS `page`
						FROM `community_events`
						WHERE `cevent_id` = ".$db->qstr($action_value);
			break;
		case 'cgallery_id':
			$query = "	SELECT `gallery_title` AS `page`
						FROM `community_galleries`
						WHERE `cgallery_id` = ".$db->qstr($action_value);
			break;
		case 'cgphoto_id':
			$query = "	SELECT `photo_title` AS `page`
						FROM `community_gallery_photos`
						WHERE `cgphoto_id` = ".$db->qstr($action_value);
			break;
		case 'cgcomment_id':
			$query = "	SELECT a.`gallery_title` AS `page`
						FROM `community_galleries` AS a
						LEFT JOIN `community_gallery_comments` AS b
						ON a.`cgaller_id` = b.`cgallery_id`
						WHERE `cgcomment_id` = ".$db->qstr($action_value);
			break;

	}

	if ($query) {
		$result = $db->GetOne($query);
		 return $result;
	}
	return false;
}

function tracking_fetch_filtered_events($community_id,$filters = array(),$paginate = true, $page = 1){
	global $db, $ENTRADA_ACL;

	$results_per_page = 25;

	$count = "	SELECT COUNT(*) AS `count`
				FROM `statistics` AS a
				JOIN `".AUTH_DATABASE."`.`user_data` AS b
				ON a.`proxy_id` = b.`id`
				WHERE `module` LIKE('community:".$community_id.":%')";

	$query = "	SELECT CONCAT_WS(' ',b.`firstname`,b.`lastname`) AS `fullname`,b.`id` AS `user_id`, a.*
				FROM `statistics` AS a
				JOIN `".AUTH_DATABASE."`.`user_data` AS b
				ON a.`proxy_id` = b.`id`
				WHERE `module` LIKE('community:".$community_id.":%')";
	$date_query = "	SELECT MIN(timestamp) AS 'start_date', MAX(timestamp) AS 'end_date'
					FROM `statistics` AS a
					JOIN `".AUTH_DATABASE."`.`user_data` AS b
					ON a.`proxy_id` = b.`id`
					WHERE `module` LIKE('community:".$community_id.":%')";

	$where = "";

	if (isset($filters) && !empty($filters)){
		foreach ($filters as $type=>$filter){
			if (is_array($filter) && !empty($filter)){
					switch ($type){
						case 'members':
							$where .= " AND a.`proxy_id` IN (".implode(',',$filter).")";
							break;
						case 'module':
							//converts each array element to lower case and then finds modules containing the value
							$where .= " AND a.`module` REGEXP ".$db->qstr(implode('|',unserialize(strtolower(serialize($filter)))));
							break;
						case 'action':
							$search_filter = str_replace("-","_",$filter);
							$where .= " AND a.`action` REGEXP ".$db->qstr(implode("|",$search_filter));
							break;
						case 'page':
							$where .= "AND (";
							$first = true;
							foreach($filter as $key=>$filter_instance){
								$raw_filter = explode("-",$filter_instance);
								$field = $raw_filter[0]."_id";
								$value = $raw_filter[2];
								$where .= ((!$first)?" OR":"")." (a.`action_field` = ".$db->qstr($field)." AND a.`action_value` = ".$db->qstr($value).")";
								$first = false;
							}
							$where .=")";
							break;

					}

			}
		}
	}
	$count .= $where;
	$query .= $where." ORDER BY a.`timestamp` DESC";
	$date_query .= $where." ORDER BY a.`timestamp` DESC";

	$num_results = $db->GetOne($count);
	if ($paginate) {
		$num_pages = ceil($num_results/$results_per_page);
		if ($num_pages > 1 && $page > 1 && $page <= $num_pages) {
			$lower_limit = ($page-1)*$results_per_page;
			$upper_limit = $results_per_page;
		} else {
			$lower_limit = 0;
			$upper_limit = $results_per_page;
		}
		$limit = " LIMIT ".$lower_limit.",".$upper_limit;
		$query .= $limit;
		$date_query .= $limit;

	}

	$statistics = $db->GetAll($query);
	$dates = $db->GetRow($date_query);
	if ($statistics){
		foreach ($statistics as $key=>$statistic){
			$page = get_page_for_statistic($statistic["action_field"], $statistic["action_value"]);

			if ($page) {
				$statistics[$key]['page'] = $page;
			}
		}
	}

	return array($statistics,$dates,$num_pages, $num_results, $results_per_page);
}

/**
 * This function returns the database fields that are used by events_fetch_filtered_events() and
 * a few other functions.
 *
 * @param string $sort_by
 * @return string
 */
function events_fetch_sorting_query($sort_by = "", $sort_order = "ASC") {

	switch ($sort_by) {
		case "teacher" :
			$sort_by = "`fullname` ".strtoupper($sort_order).", `events`.`event_start` ASC";
		break;
		case "title" :
			$sort_by = "`events`.`event_title` ".strtoupper($sort_order).", `events`.`event_start` ASC";
		break;
		case "course" :
			$sort_by = "`courses`.`course_code` ".strtoupper($sort_order).", `events`.`event_start` ASC";
		break;
		case "term" :
			$sort_by = "`curriculum_lu_types`.`curriculum_type_name` ".strtoupper($sort_order).", `events`.`event_start` ASC";
		break;
		case "date" :
		default :
			$sort_by = "`events`.`event_start` ".strtoupper($sort_order).", `events`.`updated_date` DESC";
		break;
	}

	return $sort_by;
}

/**
 * Function used by public events and admin events index to generate the SQL queries based on the users
 * filter settings and results that can be iterated through by these views.
 */
function events_fetch_filtered_events($proxy_id = 0, $user_group = "", $user_role = "", $organisation_id = 0, $sort_by = "", $sort_order = "", $date_type = "", $timestamp_start = 0, $timestamp_finish = 0, $filters = array(), $pagination = true, $current_page = 1, $results_per_page = 15, $community_id = false, $respect_time_release = true, $fetch_colors = false) {
    global $db, $ENTRADA_ACL, $ENTRADA_USER, $ENTRADA_CACHE;

    $output = array(
        "duration_start" => 0,
        "duration_end" => 0,
        "total_rows" => 0,
        "total_pages" => 0,
        "page_current" => 0,
        "page_previous" => 0,
        "page_next" => 0,
        "result_ids_map" => array(),
        "events" => array()
    );

    if (!$proxy_id = (int) $proxy_id) {
        return false;
    }

    $user_group = clean_input($user_group);
    $user_role = clean_input($user_role);

    if (!$organisation_id = (int) $organisation_id) {
        return false;
    }

    $sort_by = clean_input($sort_by);
    $sort_order = ((strtoupper($sort_order) == "ASC") ? "ASC" : "DESC");
    $date_type = clean_input($date_type);

    if (!$timestamp_start = (int) $timestamp_start) {
        return false;
    }

    $timestamp_finish = (int) $timestamp_finish;

    if (!is_array($filters)) {
        $filters = array();
    }

    $pagination = (bool) $pagination;

    if (!$current_page = (int) $current_page) {
        $current_page = 1;
    }

    if (!$results_per_page = (int) $results_per_page) {
        $results_per_page = 15;
    }

    $filter_clerkship_events = false;
    if (($user_group == "student") && $ENTRADA_ACL->amIAllowed("clerkship", "read")) {
        $query = "SELECT `course_id` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`";
        $courses = $db->GetAll($query);
        if ($courses) {
            $course_ids_string = "";
            $course_ids = array();
            foreach ($courses as $course) {
                if (array_search($course["course_id"], $course_ids) === false) {
                    if ($course_ids_string) {
                        $course_ids_string .= ", ".$db->qstr($course["course_id"]);
                    } else {
                        $course_ids_string = $db->qstr($course["course_id"]);
                    }
                    $course_ids[] = $course["course_id"];
                }
            }
        }
        $query = "	SELECT a.*, c.*
					FROM `".CLERKSHIP_DATABASE."`.`events` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
					ON b.`event_id` = a.`event_id`
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS c
					ON c.`rotation_id` = a.`rotation_id`
					WHERE (a.`event_status` = 'published' OR a.`event_status` = 'approval')
					AND b.`econtact_type` = 'student'
					AND b.`etype_id` = ".$db->qstr($proxy_id)."
					ORDER BY a.`event_start` ASC";
        $clerkship_events = $db->GetAll($query);
        if ($clerkship_events) {
            $clerkship_start = $clerkship_events[0]["event_start"];
            $clerkship_finish = $clerkship_events[0]["event_finish"];
            $time_periods = array();

            foreach ($clerkship_events as $clerkship_event) {
                if ($clerkship_event["event_start"] < $clerkship_start) {
                    $clerkship_start = $clerkship_event["event_start"];
                }
                if ($clerkship_event["event_finish"] > $clerkship_finish) {
                    $clerkship_finish = $clerkship_event["event_finish"];
                }

                $filter_clerkship_events = true;
                if (count($time_periods)) {
                    $time_periods[] = "OR (`courses`.`course_id` = ".$db->qstr($clerkship_event["course_id"])." AND ((`events`.`event_start` >= ".$db->qstr($clerkship_event["event_start"])." AND `events`.`event_start` <= ".$db->qstr($clerkship_event["event_finish"]).") OR (`events`.`event_finish` <= ".$db->qstr($clerkship_event["event_finish"])." AND `events`.`event_finish` >= ".$db->qstr($clerkship_event["event_start"]).") OR (`events`.`event_start` <= ".$db->qstr($clerkship_event["event_start"])." AND `events`.`event_finish` >= ".$db->qstr($clerkship_event["event_start"]).")))";
                } else {
                    $time_periods[] = "(`courses`.`course_id` = ".$db->qstr($clerkship_event["course_id"])." AND ((`events`.`event_start` >= ".$db->qstr($clerkship_event["event_start"])." AND `events`.`event_start` <= ".$db->qstr($clerkship_event["event_finish"]).") OR (`events`.`event_finish` <= ".$db->qstr($clerkship_event["event_finish"])." AND `events`.`event_finish` >= ".$db->qstr($clerkship_event["event_start"]).") OR (`events`.`event_start` <= ".$db->qstr($clerkship_event["event_start"])." AND `events`.`event_finish` >= ".$db->qstr($clerkship_event["event_start"]).")))";
                }
            }
            $time_periods[] = "OR (`events`.`event_start` < ".$db->qstr($clerkship_start).")";
            $time_periods[] = "OR (`events`.`event_finish` > ".$db->qstr($clerkship_finish).")";
        }
    }

    $sort_by = events_fetch_sorting_query($sort_by, $sort_order);

    /**
     * This fetches the unix timestamps from the first and last second of the day, week, month, year, etc.
     */
    $display_duration = fetch_timestamps($date_type, $timestamp_start, $timestamp_finish);

    $output["duration_start"] = $display_duration["start"];
    $output["duration_end"] = $display_duration["end"];

    $query_events = "";

    $query_events_select = "SELECT '".(int) $respect_time_release."' AS `respect_time_release`,
                            `events`.`event_id`,
                            `events`.`course_id`,
                            `events`.`parent_id`,
                            `events`.`recurring_id`,
                            `events`.`event_title`,
                            `events`.`event_description`,
                            `events`.`event_duration`,
                            `events`.`event_message`,
                            `events`.`room_id`,
                            IF(`events`.`room_id` IS NULL, `events`.`event_location`, CONCAT(`global_lu_buildings`.`building_code`, '-', `global_lu_rooms`.`room_number`)) AS `event_location`,
                            `events`.`event_start`,
                            `events`.`event_finish`,
                            `events`.`release_date`,
                            `events`.`release_until`,
                            `events`.`updated_date`,
                            `events`.`event_color`,
                            `events`.`objectives_release_date`,
                            `events`.`attendance_required`,
                            `event_audience`.`audience_type`,
                            `courses`.`organisation_id`,
                            `courses`.`course_code`,
                            `courses`.`course_name`,
                            `courses`.`permission`,
                            `curriculum_lu_types`.`curriculum_type_id`,
                            `curriculum_lu_types`.`curriculum_type_name` AS `event_phase`,
                            `curriculum_lu_types`.`curriculum_type_name` AS `event_term`,
                            CONCAT_WS(', ', `".AUTH_DATABASE."`.`user_data`.`lastname`, `".AUTH_DATABASE."`.`user_data`.`firstname`) AS `fullname`
                            FROM `events`
                            LEFT JOIN `global_lu_rooms`
                            ON `global_lu_rooms`.`room_id` = `events`.`room_id`
                            LEFT JOIN `global_lu_buildings`
                            ON `global_lu_rooms`.`building_id` = `global_lu_buildings`.`building_id`";

    $query_events_count = "SELECT COUNT(DISTINCT `events`.`event_id`) AS `event_count` FROM `events`";

    /**
     * If there are filters set by the user, build the SQL to reflect the filters.
     */
    if (is_array($filters) && !empty($filters)) {
        $build_query = array();

        $where_teacher = array();
        $where_student_course_ids = array();	// Students' enrolled in courses only
        $where_student_cohorts = array();		// Students' cohort events
        $where_student_proxy_ids = array();		// Students' indivdual events
        $where_student_cgroup_ids = array();	// Students' course small groups events
        $where_cohort = array();
        $where_course = array();
        $where_term = array();
        $where_eventtype = array();
        $where_clinical_presentation = array();
        $where_curriculum_objective = array();
        $where_topic = array();
        $where_department = array();
        $where_week = array();
        $join_event_contacts = array();

        $contact_sql = "";
        $objective_sql = "";
        $topic_sql = "";
        $course_audience_sql = "";

        $query_events .= "	LEFT JOIN `event_contacts` AS `primary_teacher`
							ON `primary_teacher`.`event_id` = `events`.`event_id`
							AND `primary_teacher`.`contact_order` = '0'
							LEFT JOIN `event_eventtypes`
							ON `event_eventtypes`.`event_id` = `events`.`event_id`
							LEFT JOIN `event_audience`
							ON `event_audience`.`event_id` = `events`.`event_id`
							%CONTACT_JOIN%
							LEFT JOIN `".AUTH_DATABASE."`.`user_data`
							ON `".AUTH_DATABASE."`.`user_data`.`id` = `primary_teacher`.`proxy_id`
							LEFT JOIN `courses`
							ON `courses`.`course_id` = `events`.`course_id`
							LEFT JOIN `curriculum_lu_types`
							ON `curriculum_lu_types`.`curriculum_type_id` = `courses`.`curriculum_type_id`
							%COURSE_AUDIENCE_JOIN%
							%OBJECTIVE_JOIN%
							%TOPIC_JOIN%
							WHERE `courses`.`organisation_id` = ".$db->qstr($organisation_id)."
							".($filter_clerkship_events && $course_ids_string ? "AND (`courses`.`course_id` NOT IN (".$course_ids_string.")\n OR (".implode("\n", $time_periods)."))" : "")."
							".(($display_duration) ? " AND `events`.`event_start` BETWEEN ".$db->qstr($display_duration["start"])." AND ".$db->qstr($display_duration["end"]) : "");

        if (!is_array($filters) || empty($filters)) {
            // Apply default filters.
        }

        if (!empty($filters)) {
            foreach ($filters as $filter_type => $filter_contents) {
                if ((is_array($filter_contents)) && (!empty($filter_contents))) {
                    foreach ($filter_contents as $filter_key => $filter_value) {
                        switch ($filter_type) {
                            case "teacher" :
                                $where_teacher[] = (int) $filter_value;
                                break;
                            case "student" :
                                if (($user_group != "student") || ($filter_value == $proxy_id)) {
                                    // Students' enrolled in courses only
                                    $courses = groups_get_explicitly_enrolled_course_ids_and_dates((int) $filter_value, false, $organisation_id);
                                    if ($courses) {
                                        $where_student_course_ids = $courses;
                                    }

                                    // Students' cohort events
                                    $cohorts = groups_get_cohorts((int) $filter_value);
                                    if ($cohorts) {
                                        foreach ($cohorts as $cohort) {
                                            $where_student_cohorts[] = $cohort["group_id"];
                                        }
                                    }

                                    // Students' indivdual events
                                    $where_student_proxy_ids[] = (int) $filter_value;

                                    // Students' course small groups events
                                    $cgroup_ids = course_fetch_enrolled_course_groups((int) $filter_value);
                                    if ($cgroup_ids) {
                                        $where_student_cgroup_ids = $cgroup_ids;
                                    }
                                }
                                break;
                            case "group" :
                                $where_cohort[] = (int) $filter_value;
                                break;
                            case "course" :
                                $where_course[] = (int) $filter_value;
                                break;
                            case "term" :
                                $where_term[] = (int) $filter_value;
                                break;
                            case "eventtype" :
                                $where_eventtype[] = (int) $filter_value;
                                break;
                            case "cp" :
                                $where_clinical_presentation[] = (int) $filter_value;
                                break;
                            case "co" :
                                $where_curriculum_objective[] = (int) $filter_value;
                                break;
                            case "topic" :
                                $where_topic[] = (int) $filter_value;
                                break;
                            case "department" :
                                $where_department[] = (int) $filter_value;
                                break;
                            case "week":
                                $where_week[] = (int) $filter_value;
                                break;
                            default :
                                continue;
                                break;
                        }
                    }
                }
            }
        }

        if ($where_teacher) {
            $build_query[] = "(`primary_teacher`.`proxy_id` IN (".implode(", ", $where_teacher).") OR `event_contacts`.`proxy_id` IN (".implode(", ", $where_teacher)."))";
        }

        if ($where_student_course_ids || $where_student_cohorts || $where_student_proxy_ids || $where_student_cgroup_ids) {
            $where_student = array();

            if ($where_student_course_ids) {
                $tmp_query = [];
                foreach ($where_student_course_ids as $record) {
                    $tmp_query[] = "(`event_audience`.`audience_value` = " . $db->qstr($record["course_id"]) . " AND `events`.`event_start` BETWEEN " . $db->qstr($record["start_date"]) . " AND " . $db->qstr($record["finish_date"]) . ")";
                }
                $where_student[] = "(`event_audience`.`audience_type` = 'course_id' AND ( " . implode(" OR ", $tmp_query) . ")) ";
            }

            if ($where_student_cohorts) {
                $where_student_cohorts = array_unique($where_student_cohorts);
                $where_student[] = "(`event_audience`.`audience_type` = 'cohort' AND `event_audience`.`audience_value` IN (".implode(", ", $where_student_cohorts)."))";
            }

            if ($where_student_proxy_ids) {
                $where_student_proxy_ids = array_unique($where_student_proxy_ids);
                $where_student[] = "(`event_audience`.`audience_type` = 'proxy_id' AND `event_audience`.`audience_value` IN (".implode(", ", $where_student_proxy_ids)."))";
            }

            if ($where_student_cgroup_ids) {
                $where_student_cgroup_ids = array_unique($where_student_cgroup_ids);
                $where_student[] = "(`event_audience`.`audience_type` = 'group_id' AND `event_audience`.`audience_value` IN (".implode(", ", $where_student_cgroup_ids)."))";
            }

            $build_query[] = "(".implode(" OR ", $where_student).")";
        }

        if ($where_cohort) {
            $build_query[] = "((`event_audience`.`audience_type` = 'cohort' AND `event_audience`.`audience_value` IN (".implode(", ", $where_cohort)."))" .
                             " OR (`event_audience`.`audience_type` = 'course_id' AND `course_audience`.`audience_type` = 'group_id' AND `course_audience`.`audience_value` IN (".implode(", ", $where_cohort).")" .
                             "     AND `events`.`event_start` BETWEEN `curriculum_periods`.`start_date` AND `curriculum_periods`.`finish_date`))";
            $course_audience_sql = "    LEFT JOIN `course_audience`
                                        ON `course_audience`.`course_id` = `courses`.`course_id`
                                        LEFT JOIN `curriculum_periods`
                                        ON `curriculum_periods`.`cperiod_id` = `course_audience`.`cperiod_id`";
        }

        if ($where_course) {
            $build_query[] = "(`events`.`course_id` IN (".implode(", ", $where_course)."))";
        }

        if ($where_term) {
            $build_query[] = "(`curriculum_lu_types`.`curriculum_type_id` IN (".implode(", ", $where_term)."))";
        }

        if ($where_eventtype) {
            $build_query[] = "(`event_eventtypes`.`eventtype_id` IN (".implode(", ", $where_eventtype)."))";
        }

        if ($where_clinical_presentation) {
            $build_query[] = "(`event_objectives`.`objective_id` IN (".implode(", ", $where_clinical_presentation)."))";
        }

        if ($where_curriculum_objective) {
            $build_query[] = "(`event_objectives`.`objective_id` IN (".implode(", ", $where_curriculum_objective)."))";
        }

        if ($where_topic) {
            $build_query[] = "(`event_topics`.`topic_id` IN (".implode(", ", $where_topic)."))";
        }

        if ($where_week) {
            $course_units = function() use ($db, $where_week) {
                $query = "
                    SELECT `cunit_id`
                    FROM `course_units`
                    WHERE `week_id` IN (".implode(", ", $where_week).")
                    AND `deleted_date` IS NULL";
                if (($results = $db->GetAll($query))) {
                    return array_map(function ($result) { return $result["cunit_id"]; }, $results);
                } else {
                    return array();
                }
            };
            $build_query[] = "(`events`.`cunit_id` IN (".implode(", ", $course_units())."))";
        }

        if ($build_query) {
            $query_events .= " AND (".implode(") AND (", $build_query).")";
        }

        if ($where_teacher) {
            $contact_sql = "	LEFT JOIN `event_contacts`
								ON `event_contacts`.`event_id` = `events`.`event_id`
								AND (`event_contacts`.`proxy_id` IN (".implode(", ", $where_teacher)."))";
        }

        if ($where_clinical_presentation || $where_curriculum_objective) {
            $objective_sql = "	LEFT JOIN `event_objectives`
								ON `event_objectives`.`event_id` = `events`.`event_id`";
        }

        if ($where_topic) {
            $topic_sql = "	LEFT JOIN `event_topics`
							ON `event_topics`.`event_id` = `events`.`event_id`";
        }

        if ($where_department) {
            $event_ids = "";

            // fetch the user_id of members in the selected departments
            $department_members_query = "	SELECT `a`.`id`
											FROM `".AUTH_DATABASE."`.`user_data` AS `a`
											JOIN `".AUTH_DATABASE."`.`user_departments` AS `b`
											ON `a`.`id` = `b`.`user_id`
											JOIN `".AUTH_DATABASE."`.`departments` AS `c`
											ON `b`.`dep_id` = `c`.`department_id`
											WHERE `b`.`dep_id` IN (".implode(',', $where_department).")
											GROUP BY `a`.`id`";
            $department_members = $db->GetAll($department_members_query);
            if ($department_members) {
                foreach ($department_members as $member) {
                    $members_list[] = $member["id"];
                }

                // fetch the event_id the members are assigned to
                $department_events_query = "	SELECT `a`.`event_id`
												FROM `events` AS `a`
												JOIN `event_contacts` AS `b`
												ON `a`.`event_id` = `b`.`event_id`
												WHERE `b`.`proxy_id` IN (".implode(',', $members_list).")
												AND `a`.`event_start` > ".$db->qstr($display_duration["start"])."
												AND `a`.`event_finish` < ".$db->qstr($display_duration["end"])."
												GROUP BY `a`.`event_id`";
                $department_events = $db->GetAll($department_events_query);
                if ($department_events) {
                    foreach ($department_events as $event) {
                        $event_list[] = $event["event_id"];
                    }
                }

                $event_ids = (!empty($event_list)) ? implode(", ", $event_list) : '';
            }

            $query_events .= " AND `events`.`event_id` IN (".$event_ids.")";
        }

        $query_events = str_replace("%CONTACT_JOIN%", $contact_sql, $query_events);

        $query_events = str_replace("%OBJECTIVE_JOIN%", $objective_sql, $query_events);

        $query_events = str_replace("%TOPIC_JOIN%", $topic_sql, $query_events);

        $query_events = str_replace("%COURSE_AUDIENCE_JOIN%", $course_audience_sql, $query_events);
    } else {
        $query_events .= "	LEFT JOIN `event_contacts`
							ON `event_contacts`.`event_id` = `events`.`event_id`
							AND `event_contacts`.`contact_order` = '0'
							LEFT JOIN `event_audience`
							ON `event_audience`.`event_id` = `events`.`event_id`
							LEFT JOIN `".AUTH_DATABASE."`.`user_data`
							ON `".AUTH_DATABASE."`.`user_data`.`id` = `event_contacts`.`proxy_id`
							LEFT JOIN `courses`
							ON (`courses`.`course_id` = `events`.`course_id`)
							LEFT JOIN `curriculum_lu_types`
							ON `curriculum_lu_types`.`curriculum_type_id` = `courses`.`curriculum_type_id`
							WHERE `courses`.`organisation_id` = ".$db->qstr($organisation_id)."
							".($filter_clerkship_events && $course_ids_string ? "AND (`courses`.`course_id` NOT IN (".$course_ids_string.")\n OR (".implode("\n", $time_periods)."))" : "")."
							".(($display_duration) ? "AND `events`.`event_start` BETWEEN ".$db->qstr($display_duration["start"])." AND ".$db->qstr($display_duration["end"]) : "");
    }

    /**
     * This builds the counting query that is run to see whether or not
     * the cache gets hit.
     */

    $query_events_count .= " " . $query_events;

    $query_events_select .= " " . $query_events . "GROUP BY `events`.`event_id` ORDER BY %s";

    $limitless_query_events = sprintf($query_events_select, $sort_by);

    /**
     * Provide the previous query so we can have previous / next event links on the details page.
     */
    if (session_id()) {
        $stored_query = false;
        $stored_events_count = false;
        if (($community_id && isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["limitless_query"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["limitless_query"]) || (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["limitless_query"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["limitless_query"])) {
            if ($community_id == false) {
                if (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"])) {
                    $stored_query = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["limitless_query"];
                    $stored_events_count = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["total_returned_rows"];
                }
            } else {
                if (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"])) {
                    $stored_query = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["limitless_query"];
                    $stored_events_count = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["total_returned_rows"];
                }
            }
        }

        $live_events_count = (int) $db->GetOne($query_events_count);

        $current_result_ids_map = $ENTRADA_CACHE->load(($community_id ? "community_".$community_id."_" : "")."events_map_".AUTH_APP_ID."_".$ENTRADA_USER->getID());

        if (!$stored_query || $stored_query != $limitless_query_events || !isset($current_result_ids_map) || !$current_result_ids_map || !$stored_events_count || ($live_events_count != $stored_events_count)) {
            $all_events = $db->GetAll($limitless_query_events);
            $result_ids_map = array();
            if ($all_events) {
                if ($user_group == "student") {
                    $enroled_courses = groups_get_enrolled_course_ids($proxy_id, true, $output["duration_start"], $output["duration_end"]);
                } else {
                    $enroled_courses = array();
                }

                foreach ($all_events as $map_event) {
                    if ($user_group != "student" || in_array($map_event["course_id"], $enroled_courses)) {
                        if ($respect_time_release) {
                            $event_resource = new EventResource($map_event["event_id"], $map_event["course_id"], $map_event["organisation_id"]);
                            if (((!$map_event["release_date"]) || ($map_event["release_date"] <= time())) && ((!$map_event["release_until"]) || ($map_event["release_until"] >= time())) && $ENTRADA_ACL->amIAllowed($event_resource, "read", true)) {
                                $result_ids_map[] = $map_event["event_id"];
                            }
                        } else {
                            $result_ids_map[] = $map_event["event_id"];
                        }
                    }
                }
            }

            $ENTRADA_CACHE->save($result_ids_map, ($community_id ? "community_".$community_id."_" : "")."events_map_".AUTH_APP_ID."_".$ENTRADA_USER->getID(), array("events", "community"), 10800);
        } else {
            $result_ids_map = $current_result_ids_map;
        }

        $output["total_rows"] = count($result_ids_map);

        if ($output["total_rows"] <= $results_per_page) {
            $output["total_pages"] = 1;
        } elseif (($output["total_rows"] % $results_per_page) == 0) {
            $output["total_pages"] = (int) ($output["total_rows"] / $results_per_page);
        } else {
            $output["total_pages"] = (int) ($output["total_rows"] / $results_per_page) + 1;
        }

        /**
         * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
         */
        if ($current_page) {
            $output["page_current"] = (int) trim($current_page);

            if (($output["page_current"] < 1) || ($output["page_current"] > $output["total_pages"])) {
                $output["page_current"] = 1;
            }
        } else {
            $output["page_current"] = 1;
        }

        $output["page_previous"] = (($output["page_current"] > 1) ? ($output["page_current"] - 1) : false);
        $output["page_next"] = (($output["page_current"] < $output["total_pages"]) ? ($output["page_current"] + 1) : false);
        $output["result_ids_map"] = $result_ids_map;

        if ($pagination) {
            $event_ids_string = "";
            for ($i = (($output["page_current"] - 1) * $results_per_page); $i < ($output["page_current"] * $results_per_page); $i++) {
                if (($i + 1) > count($result_ids_map)) {
                    break;
                }
                $event_ids_string .= ($event_ids_string ? ", " : "").$db->qstr($result_ids_map[$i]);
            }

            if (!strlen($event_ids_string)) {
                $event_ids_string = "0";
            }

            $query_events = "SELECT  '".(int) $respect_time_release."' AS `respect_time_release`,
                            `events`.`event_id`,
                            `events`.`course_id`,
                            `events`.`parent_id`,
                            `events`.`recurring_id`,
                            `events`.`event_title`,
                            `events`.`event_description`,
                            `events`.`event_duration`,
                            `events`.`event_message`,
                            `events`.`room_id`,
                            IF(`events`.`room_id` IS NULL, `events`.`event_location`, CONCAT(`global_lu_buildings`.`building_code`, '-', `global_lu_rooms`.`room_number`)) AS `event_location`,
                            `events`.`event_start`,
                            `events`.`event_finish`,
                            `events`.`release_date`,
                            `events`.`release_until`,
                            `events`.`updated_date`,
                            `events`.`event_color`,
                            `events`.`objectives_release_date`,
                            `events`.`attendance_required`,
                            `event_audience`.`audience_type`,
                            `courses`.`organisation_id`,
                            `courses`.`course_code`,
                            `courses`.`course_name`,
                            `courses`.`permission`,
                            `curriculum_lu_types`.`curriculum_type_id`,
                            `curriculum_lu_types`.`curriculum_type_name` AS `event_phase`,
                            `curriculum_lu_types`.`curriculum_type_name` AS `event_term`,
                            CONCAT_WS(', ', `".AUTH_DATABASE."`.`user_data`.`lastname`, `".AUTH_DATABASE."`.`user_data`.`firstname`) AS `fullname`
                            FROM `events`
                            LEFT JOIN `global_lu_rooms`
                            ON `global_lu_rooms`.`room_id` = `events`.`room_id`
                            LEFT JOIN `global_lu_buildings`
                            ON `global_lu_rooms`.`building_id` = `global_lu_buildings`.`building_id`
                            LEFT JOIN `event_contacts`
                            ON `event_contacts`.`event_id` = `events`.`event_id`
                            AND `event_contacts`.`contact_order` = '0'
                            LEFT JOIN `event_audience`
                            ON `event_audience`.`event_id` = `events`.`event_id`
                            LEFT JOIN `".AUTH_DATABASE."`.`user_data`
                            ON `".AUTH_DATABASE."`.`user_data`.`id` = `event_contacts`.`proxy_id`
                            LEFT JOIN `courses`
                            ON (`courses`.`course_id` = `events`.`course_id`)
                            LEFT JOIN `curriculum_lu_types`
                            ON `curriculum_lu_types`.`curriculum_type_id` = `courses`.`curriculum_type_id`
                            WHERE `events`.`event_id` IN (".$event_ids_string.")
                            GROUP BY `events`.`event_id`
                            ORDER BY %s";
            $query_events = sprintf($query_events, $sort_by);
            $learning_events = $db->GetAll($query_events);
        } else {
            $learning_events = $db->GetAll($limitless_query_events);
        }

        if ($learning_events) {
            if ($respect_time_release) {
                $i = 0;
                foreach ($learning_events as $event) {
                    if ($event["course_id"]) {
                        $event_resource = new EventResource($event["event_id"], $event["course_id"], $event["organisation_id"]);
                    }
                    if ((($event["release_date"] != 0 && $event["release_date"] > time()) || ($event["release_until"] != 0 && $event["release_until"] < time())) || !$ENTRADA_ACL->amIAllowed($event_resource, "read", true)) {
                        unset($learning_events[$i]);
                    }
                    $i++;
                }
            }
            if ($fetch_colors) {
                $event_ids = array();
                foreach ($learning_events as $event) {
                    $event_ids[] = $event["event_id"];
                }
                $query_colors = "SELECT a.`event_id`, COALESCE(a.`event_color`, b.`course_color`) AS `event_color`
                                 FROM `events` AS a
                                 INNER JOIN `courses` AS b ON b.`course_id` = a.`course_id`
                                 WHERE a.`event_id` IN (".implode(", ", $event_ids).")
                                 AND b.`course_active` = 1";
                $event_colors = $db->GetAll($query_colors);
                if ($event_colors === false) {
                    application_log("error", "Couldn't get event colors, DB said: \"".$db->ErrorMsg()."\"");
                } else {
                    $color_by_event = array();
                    foreach ($event_colors as $event_color) {
                        $color_by_event[$event_color["event_id"]] = $event_color["event_color"];
                    }
                    foreach ($learning_events as &$ref_event) {
                        $ref_event["event_color"] = $color_by_event[$ref_event["event_id"]];
                    }
                }
            }
            if ($user_group == "student") {
                $event_ids = array();
                foreach ($learning_events as $event) {
                    $event_ids[] = $event["event_id"];
                }
                if (!empty($event_ids)) {
                    $query = "	SELECT `action_value` AS `event_id`, MAX(`statistics`.`timestamp`) AS `last_visited` FROM `statistics`
								WHERE `action_value` IN (".implode(", ", $event_ids).")
								AND `module` = 'events'
								AND `proxy_id` = ".$db->qstr($proxy_id)."
								AND `action` = 'view'
								AND `action_field` = 'event_id'
								GROUP BY `proxy_id`, `module`, `action_field`, `action`, `action_value`";
                    $last_visited_dates = $db->GetAll($query);
                    if (!empty($last_visited_dates)) {
                        $dates_array = array();
                        foreach ($last_visited_dates as $event_last_visited) {
                            $dates_array[$event_last_visited["event_id"]] = $event_last_visited["last_visited"];
                        }
                        foreach ($learning_events as &$event) {
                            if (array_key_exists($event["event_id"], $dates_array)) {
                                $event["last_visited"] = $dates_array[$event["event_id"]];
                            }
                        }
                    }
                }

                /**
                 * Include custom times only from the Learning Events.
                 */
                foreach ($learning_events as &$event) {
                    $audience_types = array();
                    $event["custom_time"] = 0;
                    
                    $query = " SELECT *
                                FROM `event_audience`
                                WHERE `event_id` = ?";
                    $results = $db->GetAll($query, array($event["event_id"]));
                    if ($results) {
                        foreach ($results as $key => $result) {
                            if ($result["custom_time"]) {
                                switch ($result["audience_type"]) {
                                    case "cohort":
                                        //check membership in cohort
                                        //if true add to $audience_types["cohort"]
                                        if (in_array($result["audience_value"], $where_student_cohorts)) {
                                            $event["custom_time"] = 1;
                                            $audience_types["cohort"] = array("true" => true, "key" => $key);
                                        }
                                    break;
                                    case "group_id":
                                        //check membership in course group
                                        //if true add to $audience_types["group_id"]
                                        if (in_array($result["audience_value"], $where_student_cgroup_ids)) {
                                            $event["custom_time"] = 1;
                                            $audience_types["group_id"] = array("true" => true, "key" => $key);
                                        }
                                    break;
                                    case "proxy_id":
                                        if ($result["audience_value"] == $proxy_id) {
                                            $event["custom_time"] = 1;
                                            $audience_types["proxy_id"] = array("true" => true, "key" => $key);
                                        }
                                    break;
                                }
                            }
                        }

                        $event_audience_array = $audience_types;
                        $event["audience_array"] = $event_audience_array;
                        if ($event["custom_time"]) {
                            if ($audience_types["proxy_id"]["true"]) {
                                $event["custom_type"] = "proxy_id";
                                $event["custom_time_start"] = $results[$audience_types["proxy_id"]["key"]]["custom_time_start"];
                                $event["custom_time_end"] = $results[$audience_types["proxy_id"]["key"]]["custom_time_end"];
                            } else if ($audience_types["group_id"]["true"]) {
                                $event["custom_type"] = "group_id";
                                $event["custom_time_start"] = $results[$audience_types["group_id"]["key"]]["custom_time_start"];
                                $event["custom_time_end"] = $results[$audience_types["group_id"]["key"]]["custom_time_end"];
                            } else if ($audience_types["cohort"]["true"]) {
                                $event["custom_type"] = "cohort";
                                $event["custom_time_start"] = $results[$audience_types["cohort"]["key"]]["custom_time_start"];
                                $event["custom_time_end"] = $results[$audience_types["cohort"]["key"]]["custom_time_end"];
                            }
                        }
                    }
                }
            }

            $parent_ids = array();
            foreach ($learning_events as $temp_event) {
                if ($temp_event["parent_id"]) {
                    $parent_ids[] = $temp_event["parent_id"];
                }
            }

            if (!empty($parent_ids)) {
                $query = "	SELECT * FROM `events`
							WHERE `event_id` IN (".implode(", ", $parent_ids).")
							GROUP BY `event_id`";
                $parent_events = $db->GetAll($query);
                if (!empty($parent_events)) {
                    $parent_events_array = array();
                    foreach ($parent_events as $parent_event) {
                        $parent_events_array[$parent_event["event_id"]] = $parent_event;
                    }
                }
            }
            $output["events"] = $learning_events;
        }

        if ($community_id == false) {
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dashboard"]["previous_query"]["query"] = $query_events;
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dashboard"]["previous_query"]["limitless_query"] = $limitless_query_events;
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dashboard"]["previous_query"]["total_returned_rows"] = $live_events_count;
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dashboard"]["previous_query"]["total_rows"] = $output["total_rows"];

            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["query"] = $query_events;
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["limitless_query"] = $limitless_query_events;
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["total_returned_rows"] = $live_events_count;
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["total_rows"] = $output["total_rows"];
        } else {
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["query"] = $query_events;
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["limitless_query"] = $limitless_query_events;
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["total_returned_rows"] = $live_events_count;
            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["total_rows"] = $output["total_rows"];
        }
    }

    return $output;
}
/**
 * Function used by public draft events and admin draft events index to generate the SQL queries based on the users
 * filter settings and results that can be iterated through by these views.
 */
function draft_events_fetch_filtered_draft_events($proxy_id = 0, $user_group = "", $user_role = "", $organisation_id = 0, $date_type = "", $timestamp_start = 0, $timestamp_finish = 0, $draft_id = 0, $filters = array(), $pagination = true, $current_page = 1, $results_per_page = 15, $community_id = false, $respect_time_release = true) {
        global $db, $ENTRADA_ACL, $ENTRADA_USER, $ENTRADA_CACHE;

        $output = array(
            "duration_start" => 0,
            "duration_end" => 0,
            "total_rows" => 0,
            "total_pages" => 0,
            "page_current" => 0,
            "page_previous" => 0,
            "page_next" => 0,
            "result_ids_map" => array(),
            "events" => array()
        );

        if (!$proxy_id = (int) $proxy_id) {
            return false;
        }

        $user_group = clean_input($user_group);
        $user_role = clean_input($user_role);

        if (!$organisation_id = (int) $organisation_id) {
            return false;
        }

        $date_type = clean_input($date_type);

        if (!$timestamp_start = (int) $timestamp_start) {
            return false;
        }

        $timestamp_finish = (int) $timestamp_finish;

        $draft_id = (int) $draft_id;

        if (!is_array($filters)) {
            $filters = array();
        }

        $pagination = (bool) $pagination;

        if (!$current_page = (int) $current_page) {
            $current_page = 1;
        }

        if (!$results_per_page = (int) $results_per_page) {
            $results_per_page = 15;
        }

        $filter_clerkship_events = false;


        /**
         * This fetches the unix timestamps from the first and last second of the day, week, month, year, etc.
         */
        $display_duration = fetch_timestamps($date_type, $timestamp_start, $timestamp_finish);

        $output["duration_start"] = $display_duration["start"];
        $output["duration_end"] = $display_duration["end"];

        $query_events = "";

        $query_events_select = "SELECT '".(int) $respect_time_release."' AS `respect_time_release`,
                            `draft_events`.`devent_id`,
                            `draft_events`.`event_id`,
                            `draft_events`.`course_id`,
                            `draft_events`.`parent_id`,
                            `draft_events`.`recurring_id`,
                            `draft_events`.`event_title`,
                            `draft_events`.`event_description`,
                            `draft_events`.`event_duration`,
                            `draft_events`.`event_message`,
                            IF(`draft_events`.`room_id` IS NULL, `draft_events`.`event_location`, CONCAT(`global_lu_buildings`.`building_code`, '-', `global_lu_rooms`.`room_number`)) AS `event_location`,
                            `draft_events`.`event_start`,
                            `draft_events`.`event_finish`,
                            `draft_events`.`release_date`,
                            `draft_events`.`release_until`,
                            `draft_events`.`updated_date`,
                            `draft_events`.`objectives_release_date`,
                            `draft_events`.`attendance_required`,
                            `draft_audience`.`audience_type`,
                            `courses`.`organisation_id`,
                            `courses`.`course_code`,
                            `courses`.`course_name`,
                            `courses`.`permission`,
                            `curriculum_lu_types`.`curriculum_type_id`,
                            `curriculum_lu_types`.`curriculum_type_name` AS `event_phase`,
                            `curriculum_lu_types`.`curriculum_type_name` AS `event_term`,
                            CONCAT_WS(', ', `".AUTH_DATABASE."`.`user_data`.`lastname`, `".AUTH_DATABASE."`.`user_data`.`firstname`) AS `fullname`
                            FROM `draft_events`
                            LEFT JOIN `global_lu_rooms`
                            ON `global_lu_rooms`.`room_id` = `draft_events`.`room_id`
                            LEFT JOIN `global_lu_buildings`
                            ON `global_lu_rooms`.`building_id` = `global_lu_buildings`.`building_id`";

        $query_events_count = "SELECT COUNT(DISTINCT `draft_events`.`event_id`) AS `event_count` FROM `draft_events`";

        /**
         * If there are filters set by the user, build the SQL to reflect the filters.
         */
        if (is_array($filters) && !empty($filters)) {
            $build_query = array();

            $where_teacher = array();
            $where_student_course_ids = array();    // Students' enrolled in courses only
            $where_student_cohorts = array();       // Students' cohort events
            $where_student_proxy_ids = array();     // Students' indivdual events
            $where_student_cgroup_ids = array();    // Students' course small groups events
            $where_cohort = array();
            $where_course = array();
            $where_term = array();
            $where_eventtype = array();
            $where_clinical_presentation = array();
            $where_curriculum_objective = array();
            $where_topic = array();
            $where_department = array();
            $join_event_contacts = array();

            $contact_sql = "";
            $objective_sql = "";
            $topic_sql = "";

            $query_events .= "  LEFT JOIN `draft_contacts` AS `primary_teacher`
                            ON `primary_teacher`.`devent_id` = `draft_events`.`devent_id`
                            AND `primary_teacher`.`contact_order` = '0'
                            LEFT JOIN `event_eventtypes`
                            ON `draft_eventtypes`.`devent_id` = `draft_events`.`devent_id`
                            LEFT JOIN `draft_audience`
                            ON `draft_audience`.`devent_id` = `draft_events`.`devent_id`
                            %CONTACT_JOIN%
                            LEFT JOIN `".AUTH_DATABASE."`.`user_data`
                            ON `".AUTH_DATABASE."`.`user_data`.`id` = `primary_teacher`.`proxy_id`
                            LEFT JOIN `courses`
                            ON `courses`.`course_id` = `draft_events`.`course_id`
                            LEFT JOIN `curriculum_lu_types`
                            ON `curriculum_lu_types`.`curriculum_type_id` = `courses`.`curriculum_type_id`
                            %OBJECTIVE_JOIN%
                            %TOPIC_JOIN%
                            WHERE `draft_events`.`draft_id` = ".$db->qstr($draft_id)." AND `courses`.`organisation_id` = ".$db->qstr($organisation_id)."
                            ".($filter_clerkship_events && $course_ids_string ? "AND (`courses`.`course_id` NOT IN (".$course_ids_string.")\n OR (".implode("\n", $time_periods)."))" : "");
            if (!is_array($filters) || empty($filters)) {
                // Apply default filters.
            }

            if (!empty($filters)) {
                foreach ($filters as $filter_type => $filter_contents) {
                    if ((is_array($filter_contents)) && (!empty($filter_contents))) {
                        foreach ($filter_contents as $filter_key => $filter_value) {
                            switch ($filter_type) {
                                case "teacher" :
                                    $where_teacher[] = (int) $filter_value;
                                    break;
                                case "student" :
                                    if (($user_group != "student") || ($filter_value == $proxy_id)) {
                                        // Students' enrolled in courses only
                                        $courses = groups_get_explicitly_enrolled_course_ids_and_dates((int) $filter_value, false, $organisation_id);
                                        if ($courses) {
                                            $where_student_course_ids = $courses;
                                        }

                                        // Students' cohort events
                                        $cohorts = groups_get_cohorts((int) $filter_value);
                                        if ($cohorts) {
                                            foreach ($cohorts as $cohort) {
                                                $where_student_cohorts[] = $cohort["group_id"];
                                            }
                                        }

                                        // Students' indivdual events
                                        $where_student_proxy_ids[] = (int) $filter_value;

                                        // Students' course small groups events
                                        $cgroup_ids = course_fetch_enrolled_course_groups((int) $filter_value);
                                        if ($cgroup_ids) {
                                            $where_student_cgroup_ids = $cgroup_ids;
                                        }
                                    }
                                    break;
                                case "group" :
                                    $where_cohort[] = (int) $filter_value;
                                    break;
                                case "course" :
                                    $where_course[] = (int) $filter_value;
                                    break;
                                case "term" :
                                    $where_term[] = (int) $filter_value;
                                    break;
                                case "eventtype" :
                                    $where_eventtype[] = (int) $filter_value;
                                    break;
                                case "department" :
                                    $where_department[] = (int) $filter_value;
                                    break;
                                default :
                                    continue;
                                    break;
                            }
                        }
                    }
                }
            }

            if ($where_teacher) {
                $build_query[] = "(`primary_teacher`.`proxy_id` IN (".implode(", ", $where_teacher).") OR `draft_contacts`.`proxy_id` IN (".implode(", ", $where_teacher)."))";
            }

            if ($where_student_course_ids || $where_student_cohorts || $where_student_proxy_ids || $where_student_cgroup_ids) {
                $where_student = array();

                if ($where_student_course_ids) {
                    $tmp_query = [];
                    foreach ($where_student_course_ids as $record) {
                        $tmp_query[] = "(`draft_audience`.`audience_value` = " . $db->qstr($record["course_id"]) . " AND `draft_events`.`event_start` BETWEEN " . $db->qstr($record["start_date"]) . " AND " . $db->qstr($record["finish_date"]) . ")";
                    }
                    $where_student[] = "(`draft_audience`.`audience_type` = 'course_id' AND ( " . implode(" OR ", $tmp_query) . ")) ";
                }

                if ($where_student_cohorts) {
                    $where_student_cohorts = array_unique($where_student_cohorts);
                    $where_student[] = "(`draft_audience`.`audience_type` = 'cohort' AND `draft_audience`.`audience_value` IN (".implode(", ", $where_student_cohorts)."))";
                }

                if ($where_student_proxy_ids) {
                    $where_student_proxy_ids = array_unique($where_student_proxy_ids);
                    $where_student[] = "(`draft_audience`.`audience_type` = 'proxy_id' AND `draft_audience`.`audience_value` IN (".implode(", ", $where_student_proxy_ids)."))";
                }

                if ($where_student_cgroup_ids) {
                    $where_student_cgroup_ids = array_unique($where_student_cgroup_ids);
                    $where_student[] = "(`draft_audience`.`audience_type` = 'group_id' AND `draft_audience`.`audience_value` IN (".implode(", ", $where_student_cgroup_ids)."))";
                }

                $build_query[] = "(".implode(" OR ", $where_student).")";
            }

            if ($where_cohort) {
                $build_query[] = "(`draft_audience`.`audience_type` = 'cohort' AND `draft_audience`.`audience_value` IN (".implode(", ", $where_cohort)."))";
            }

            if ($where_course) {
                $build_query[] = "(`draft_events`.`course_id` IN (".implode(", ", $where_course)."))";
            }

            if ($where_term) {
                $build_query[] = "(`curriculum_lu_types`.`curriculum_type_id` IN (".implode(", ", $where_term)."))";
            }

            if ($where_eventtype) {
                $build_query[] = "(`draft_eventtypes`.`eventtype_id` IN (".implode(", ", $where_eventtype)."))";
            }

            if ($build_query) {
                $query_events .= " AND (".implode(") AND (", $build_query).")";
            }

            if ($where_teacher) {
                $contact_sql = "    LEFT JOIN `draft_contacts`
                                ON `draft_contacts`.`devent_id` = `draft_events`.`devent_id`
                                AND (`draft_contacts`.`proxy_id` IN (".implode(", ", $where_teacher)."))";
            }

            if ($where_department) {
                $event_ids = "";

                // fetch the user_id of members in the selected departments
                $department_members_query = "   SELECT `a`.`id`
                                            FROM `".AUTH_DATABASE."`.`user_data` AS `a`
                                            JOIN `".AUTH_DATABASE."`.`user_departments` AS `b`
                                            ON `a`.`id` = `b`.`user_id`
                                            JOIN `".AUTH_DATABASE."`.`departments` AS `c`
                                            ON `b`.`dep_id` = `c`.`department_id`
                                            WHERE `b`.`dep_id` IN (".implode(',', $where_department).")
                                            GROUP BY `a`.`id`";
                $department_members = $db->GetAll($department_members_query);
                if ($department_members) {
                    foreach ($department_members as $member) {
                        $members_list[] = $member["id"];
                    }

                    // fetch the event_id the members are assigned to
                    $department_events_query = "    SELECT `a`.`devent_id`
                                                FROM `draft_events` AS `a`
                                                JOIN `draft_contacts` AS `b`
                                                ON `a`.`devent_id` = `b`.`devent_id`
                                                WHERE `b`.`proxy_id` IN (".implode(',', $members_list).")
                                                AND `a`.`draft_id` = ".$db->qstr($draft_id)."
                                                GROUP BY `a`.`devent_id`";
                    $department_events = $db->GetAll($department_events_query);
                    if ($department_events) {
                        foreach ($department_events as $event) {
                            $event_list[] = $event["devent_id"];
                        }
                    }

                    $event_ids = (!empty($event_list)) ? implode(", ", $event_list) : '';
                }

                $query_events .= " AND `draft_events`.`devent_id` IN (".$event_ids.")";
            }

            $query_events = str_replace("%CONTACT_JOIN%", $contact_sql, $query_events);

            $query_events = str_replace("%OBJECTIVE_JOIN%", $objective_sql, $query_events);

            $query_events = str_replace("%TOPIC_JOIN%", $topic_sql, $query_events);
        } else {
            $query_events .= "  LEFT JOIN `draft_contacts`
                            ON `draft_contacts`.`devent_id` = `draft_events`.`devent_id`
                            AND `draft_contacts`.`contact_order` = '0'
                            LEFT JOIN `draft_audience`
                            ON `draft_audience`.`devent_id` = `draft_events`.`devent_id`
                            LEFT JOIN `".AUTH_DATABASE."`.`user_data`
                            ON `".AUTH_DATABASE."`.`user_data`.`id` = `draft_contacts`.`proxy_id`
                            LEFT JOIN `courses`
                            ON (`courses`.`course_id` = `draft_events`.`course_id`)
                            LEFT JOIN `curriculum_lu_types`
                            ON `curriculum_lu_types`.`curriculum_type_id` = `courses`.`curriculum_type_id`
                            WHERE `draft_events`.`draft_id` = ".$db->qstr($draft_id);
        }

        /**
         * This builds the counting query that is run to see whether or not
         * the cache gets hit.
         */

        $query_events_count .= " " . $query_events;

        $query_events_select .= " " . $query_events . "GROUP BY `draft_events`.`devent_id` ORDER BY `draft_events`.`event_title` DESC, `draft_events`.`event_start` ASC";

        $limitless_query_events = sprintf($query_events_select);

        /**
         * Provide the previous query so we can have previous / next event links on the details page.
         */
        if (session_id()) {
            $stored_query = false;
            $stored_events_count = false;
            if (($community_id && isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["limitless_query"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["limitless_query"]) || (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["limitless_query"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["limitless_query"])) {
                if ($community_id == false) {
                    if (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"])) {
                        $stored_query = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["limitless_query"];
                        $stored_events_count = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["events"]["previous_query"]["total_returned_rows"];
                    }
                } else {
                    if (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"])) {
                        $stored_query = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["limitless_query"];
                        $stored_events_count = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["community_page"][$community_id]["previous_query"]["total_returned_rows"];
                    }
                }
            }

            $live_events_count = (int) $db->GetOne($query_events_count);

            $current_result_ids_map = $ENTRADA_CACHE->load(($community_id ? "community_".$community_id."_" : "")."events_map_".AUTH_APP_ID."_".$ENTRADA_USER->getID());

            if (!$stored_query || $stored_query != $limitless_query_events || !isset($current_result_ids_map) || !$current_result_ids_map || !$stored_events_count || ($live_events_count != $stored_events_count)) {
                $all_events = $db->GetAll($limitless_query_events);
                $result_ids_map = array();
                if ($all_events) {
                    if ($user_group == "student") {
                        $enroled_courses = groups_get_enrolled_course_ids($proxy_id, true, $output["duration_start"], $output["duration_end"]);
                    } else {
                        $enroled_courses = array();
                    }

                    foreach ($all_events as $map_event) {
                        if ($user_group != "student" || in_array($map_event["course_id"], $enroled_courses)) {
                            if ($respect_time_release) {
                                $event_resource = new EventResource($map_event["event_id"], $map_event["course_id"], $map_event["organisation_id"]);
                                if (((!$map_event["release_date"]) || ($map_event["release_date"] <= time())) && ((!$map_event["release_until"]) || ($map_event["release_until"] >= time())) && $ENTRADA_ACL->amIAllowed($event_resource, "read", true)) {
                                    $result_ids_map[] = $map_event["event_id"];
                                }
                            } else {
                                $result_ids_map[] = $map_event["event_id"];
                            }
                        }
                    }
                }

                $ENTRADA_CACHE->save($result_ids_map, ($community_id ? "community_".$community_id."_" : "")."events_map_".AUTH_APP_ID."_".$ENTRADA_USER->getID(), array("events", "community"), 10800);
            } else {
                $result_ids_map = $current_result_ids_map;
            }

            $output["total_rows"] = count($result_ids_map);

            if ($output["total_rows"] <= $results_per_page) {
                $output["total_pages"] = 1;
            } elseif (($output["total_rows"] % $results_per_page) == 0) {
                $output["total_pages"] = (int) ($output["total_rows"] / $results_per_page);
            } else {
                $output["total_pages"] = (int) ($output["total_rows"] / $results_per_page) + 1;
            }

            /**
             * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
             */
            if ($current_page) {
                $output["page_current"] = (int) trim($current_page);

                if (($output["page_current"] < 1) || ($output["page_current"] > $output["total_pages"])) {
                    $output["page_current"] = 1;
                }
            } else {
                $output["page_current"] = 1;
            }

            $output["page_previous"] = (($output["page_current"] > 1) ? ($output["page_current"] - 1) : false);
            $output["page_next"] = (($output["page_current"] < $output["total_pages"]) ? ($output["page_current"] + 1) : false);
            $output["result_ids_map"] = $result_ids_map;

            if ($pagination) {
                $event_ids_string = "";
                for ($i = (($output["page_current"] - 1) * $results_per_page); $i < ($output["page_current"] * $results_per_page); $i++) {
                    if (($i + 1) > count($result_ids_map)) {
                        break;
                    }
                    $event_ids_string .= ($event_ids_string ? ", " : "").$db->qstr($result_ids_map[$i]);
                }

                if (!strlen($event_ids_string)) {
                    $event_ids_string = "0";
                }

                $query_events = "SELECT `draft_events`.`devent_id`,
                            `draft_events`.`course_id`,
                            `draft_events`.`parent_id`,
                            `draft_events`.`recurring_id`,
                            `draft_events`.`event_title`,
                            `draft_events`.`event_description`,
                            `draft_events`.`event_duration`,
                            `draft_events`.`event_message`,
                            IF(`draft_events`.`room_id` IS NULL, `draft_events`.`event_location`, CONCAT(`global_lu_buildings`.`building_code`, '-', `global_lu_rooms`.`room_number`)) AS `event_location`,
                            `draft_events`.`event_start`,
                            `draft_events`.`event_finish`,
                            `draft_events`.`release_date`,
                            `draft_events`.`release_until`,
                            `draft_events`.`updated_date`,
                            `draft_events`.`objectives_release_date`,
                            `draft_audience`.`audience_type`,
                            `courses`.`organisation_id`,
                            `courses`.`course_code`,
                            `courses`.`course_name`,
                            `courses`.`permission`,
                            `curriculum_lu_types`.`curriculum_type_id`,
                            `curriculum_lu_types`.`curriculum_type_name` AS `event_phase`,
                            `curriculum_lu_types`.`curriculum_type_name` AS `event_term`,
                            CONCAT_WS(', ', `".AUTH_DATABASE."`.`user_data`.`lastname`, `".AUTH_DATABASE."`.`user_data`.`firstname`) AS `fullname`
                            FROM `draft_events`
                            LEFT JOIN `global_lu_rooms`
                            ON `global_lu_rooms`.`room_id` = `draft_events`.`room_id`
                            LEFT JOIN `global_lu_buildings`
                            ON `global_lu_rooms`.`building_id` = `global_lu_buildings`.`building_id`
                            LEFT JOIN `draft_contacts`
                            ON `draft_contacts`.`devent_id` = `draft_events`.`devent_id`
                            AND `draft_contacts`.`contact_order` = '0'
                            LEFT JOIN `draft_audience`
                            ON `draft_audience`.`event_id` = `draft_events`.`event_id`
                            LEFT JOIN `".AUTH_DATABASE."`.`user_data`
                            ON `".AUTH_DATABASE."`.`user_data`.`id` = `draft_contacts`.`proxy_id`
                            LEFT JOIN `courses`
                            ON (`courses`.`course_id` = `draft_events`.`course_id`)
                            LEFT JOIN `curriculum_lu_types`
                            ON `curriculum_lu_types`.`curriculum_type_id` = `courses`.`curriculum_type_id`
                            WHERE `draft_events`.`devent_id` IN (".$event_ids_string.")
                            GROUP BY `draft_events`.`devent_id`
                            ORDER BY `draft_events`.`event_title` DESC, `draft_events`.`event_start` ASC";
                $query_events = sprintf($query_events);
                $learning_events = $db->GetAll($query_events);
            } else {
                $learning_events = $db->GetAll($limitless_query_events);
            }

            if ($learning_events) {
                if ($respect_time_release) {
                    $i = 0;
                    foreach ($learning_events as $event) {
                        if ($event["course_id"]) {
                            $event_resource = new EventResource($event["devent_id"], $event["course_id"], $event["organisation_id"]);
                        }
                        if ((($event["release_date"] != 0 && $event["release_date"] > time()) || ($event["release_until"] != 0 && $event["release_until"] < time())) || !$ENTRADA_ACL->amIAllowed($event_resource, "read", true)) {
                            unset($learning_events[$i]);
                        }
                        $i++;
                    }
                }
                if ($user_group == "student") {
                    $event_ids = array();
                    foreach ($learning_events as $event) {
                        $event_ids[] = $event["devent_id"];
                    }
                    if (!empty($event_ids)) {
                        $query = "  SELECT `action_value` AS `event_id`, MAX(`statistics`.`timestamp`) AS `last_visited` FROM `statistics`
                                WHERE `action_value` IN (".implode(", ", $event_ids).")
                                AND `module` = 'events'
                                AND `proxy_id` = ".$db->qstr($proxy_id)."
                                AND `action` = 'view'
                                AND `action_field` = 'event_id'
                                GROUP BY `proxy_id`, `module`, `action_field`, `action`, `action_value`";
                        $last_visited_dates = $db->GetAll($query);
                        if (!empty($last_visited_dates)) {
                            $dates_array = array();
                            foreach ($last_visited_dates as $event_last_visited) {
                                $dates_array[$event_last_visited["event_id"]] = $event_last_visited["last_visited"];
                            }
                            foreach ($learning_events as &$event) {
                                if (array_key_exists($event["event_id"], $dates_array)) {
                                    $event["last_visited"] = $dates_array[$event["event_id"]];
                                }
                            }
                        }
                    }

                    /**
                     * Include custom times only from the Learning Events.
                     */
                    foreach ($learning_events as &$event) {
                        $audience_types = array();
                        $event["custom_time"] = 0;

                        $query = " SELECT *
                                FROM `draft_audience`
                                WHERE `devent_id` = ?";
                        $results = $db->GetAll($query, array($event["devent_id"]));
                        if ($results) {
                            foreach ($results as $key => $result) {
                                if ($result["custom_time"]) {
                                    switch ($result["audience_type"]) {
                                        case "cohort":
                                            //check membership in cohort
                                            //if true add to $audience_types["cohort"]
                                            if (in_array($result["audience_value"], $where_student_cohorts)) {
                                                $event["custom_time"] = 1;
                                                $audience_types["cohort"] = array("true" => true, "key" => $key);
                                            }
                                            break;
                                        case "group_id":
                                            //check membership in course group
                                            //if true add to $audience_types["group_id"]
                                            if (in_array($result["audience_value"], $where_student_cgroup_ids)) {
                                                $event["custom_time"] = 1;
                                                $audience_types["group_id"] = array("true" => true, "key" => $key);
                                            }
                                            break;
                                        case "proxy_id":
                                            if ($result["audience_value"] == $proxy_id) {
                                                $event["custom_time"] = 1;
                                                $audience_types["proxy_id"] = array("true" => true, "key" => $key);
                                            }
                                            break;
                                    }
                                }
                            }

                            $event_audience_array = $audience_types;
                            $event["audience_array"] = $event_audience_array;
                            if ($event["custom_time"]) {
                                if ($audience_types["proxy_id"]["true"]) {
                                    $event["custom_type"] = "proxy_id";
                                    $event["custom_time_start"] = $results[$audience_types["proxy_id"]["key"]]["custom_time_start"];
                                    $event["custom_time_end"] = $results[$audience_types["proxy_id"]["key"]]["custom_time_end"];
                                } else if ($audience_types["group_id"]["true"]) {
                                    $event["custom_type"] = "group_id";
                                    $event["custom_time_start"] = $results[$audience_types["group_id"]["key"]]["custom_time_start"];
                                    $event["custom_time_end"] = $results[$audience_types["group_id"]["key"]]["custom_time_end"];
                                } else if ($audience_types["cohort"]["true"]) {
                                    $event["custom_type"] = "cohort";
                                    $event["custom_time_start"] = $results[$audience_types["cohort"]["key"]]["custom_time_start"];
                                    $event["custom_time_end"] = $results[$audience_types["cohort"]["key"]]["custom_time_end"];
                                }
                            }
                        }
                    }
                }

                $parent_ids = array();
                foreach ($learning_events as $temp_event) {
                    if ($temp_event["parent_id"]) {
                        $parent_ids[] = $temp_event["parent_id"];
                    }
                }

                if (!empty($parent_ids)) {
                    $query = "  SELECT * FROM `draft_events`
                            WHERE `devent_id` IN (".implode(", ", $parent_ids).")
                            GROUP BY `devent_id`";
                    $parent_events = $db->GetAll($query);
                    if (!empty($parent_events)) {
                        $parent_events_array = array();
                        foreach ($parent_events as $parent_event) {
                            $parent_events_array[$parent_event["devent_id"]] = $parent_event;
                        }
                    }
                }
                $output["events"] = $learning_events;
            }
        }

        return $output;
    }

function events_fetch_transversal_ids ($event_id, $community_id) {
	global $ENTRADA_CACHE, $ENTRADA_USER;

	$transversal_ids = array();

	$result_ids_map = $ENTRADA_CACHE->load(($community_id ? "community_".$community_id."_" : "")."events_map_".AUTH_APP_ID."_".$ENTRADA_USER->getID());
	if (!$result_ids_map || !count($result_ids_map)) {
		$learning_events = events_fetch_filtered_events(
				$ENTRADA_USER->getActiveId(),
				$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"],
				$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"],
				$ENTRADA_USER->getActiveOrganisation(),
				$_SESSION[APPLICATION_IDENTIFIER][($community_id ? "community_page" : "events")]["sb"],
				$_SESSION[APPLICATION_IDENTIFIER][($community_id ? "community_page" : "events")]["so"],
				$_SESSION[APPLICATION_IDENTIFIER][($community_id ? "community_page" : "events")]["dtype"],
				$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"],
				0,
				($community_id && isset($_SESSION[APPLICATION_IDENTIFIER]["community_page"][$COMMUNITY_ID]["filters"]) ? $_SESSION[APPLICATION_IDENTIFIER]["community_page"][$COMMUNITY_ID]["filters"] : $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]),
				true,
				1,
				15,
				($community_id ? $community_id : false));
		if (!($result_ids_map = $ENTRADA_CACHE->load(($community_id ? "community_".$community_id."_" : "")."events_map_".AUTH_APP_ID."_".$ENTRADA_USER->getID()))) {
			$result_ids_map = array();
			foreach ($learning_events["events"] as $event) {
				$result_ids_map[] = $event["event_id"];
			}
		}
	}
	if (($result_id = array_search($event_id, $result_ids_map)) !== false) {
		if ($result_id > 0) {
			$transversal_ids["prev"] = $result_ids_map[($result_id - 1)];
		}
		if ($result_id < (count($result_ids_map) - 1)) {
			$transversal_ids["next"] = $result_ids_map[($result_id + 1)];
		}
	}

	return $transversal_ids;
}

/**
 * Returns all teachers, tutors, TAs, and auditors for the specified learning event.
 *
 * @global object $db
 * @param int $event_id
 * @return array
 */
function events_fetch_event_contacts($event_id = 0) {
	global $db;

	$output = array();

	$event_id = (int) $event_id;

	if ($event_id) {
		$query = "	SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`, a.`contact_role`, a.`contact_order`
					FROM `event_contacts` AS a
					JOIN `".AUTH_DATABASE."`.`user_data` AS b
					ON b.`id` = a.`proxy_id`
					WHERE a.`event_id` = ".$db->qstr($event_id)."
					ORDER BY a.`contact_order` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				$output[$result["contact_role"]][] = $result;
			}
		}
	}

	return $output;
}

/**
 * Returns all audience members for the specified learning event.
 *
 * @global object $db
 * @param int $event_id
 * @return array
 */
function events_fetch_event_audience($event_id = 0) {
	global $db;

	$output = array();

	$event_id = (int) $event_id;

	if ($event_id) {
		$query = "SELECT * FROM `event_audience` WHERE `event_id` = ".$db->qstr($event_id);
		$results = $db->GetAll($query);
		if ($results) {
			// This puts them in the display order I want them in.
			$output = array("course_id" => array(), "cohort" => array(), "group_id" => array(), "proxy_id" => array());

			foreach ($results as $result) {
				$row = array (
						"type" => $result["audience_type"],
						"link" => "",
						"title" => "",
						"count" => 0,
                        "custom_time" => 0,
                        "custom_time_start" => "",
                        "custom_time_end" => ""
				);

				switch ($result["audience_type"]) {
					case "course_id" : // Course Audience
						$row["link"] = ENTRADA_URL . "/courses?id=".$result["audience_value"];
						$row["title"] = fetch_course_title($result["audience_value"]);
					break;
					case "cohort" :	// Cohorts
						$row["title"] = fetch_group_title($result["audience_value"]);
                        if ($result["custom_time"]) {
                            $row["custom_time"] = (int) $result["custom_time"];
                            $row["custom_time_start"] = (int) $result["custom_time_start"];
                            $row["custom_time_end"] = (int) $result["custom_time_end"];
                        }
					break;
					case "group_id" : // Course Groups
						$cgroup = course_fetch_course_group($result["audience_value"]);

						$row["title"] = $cgroup["group_name"];
						$row["count"] = $cgroup["members"];
                        if ($result["custom_time"]) {
                            $row["custom_time"] = (int) $result["custom_time"];
                            $row["custom_time_start"] = (int) $result["custom_time_start"];
                            $row["custom_time_end"] = (int) $result["custom_time_end"];
                        }
					break;
					case "proxy_id" : // Learners
						$row["link"] = ENTRADA_URL . "/people?id=".$result["audience_value"];
						$row["title"] = get_account_data("fullname", $result["audience_value"]);
                        if ($result["custom_time"]) {
                            $row["custom_time"] = (int) $result["custom_time"];
                            $row["custom_time_start"] = (int) $result["custom_time_start"];
                            $row["custom_time_end"] = (int) $result["custom_time_end"];
                        }
					break;
					default : // No longer supported, but include the value just in case.
						$row["title"] = $result["audience_value"];

						application_log("notice", "audience_type [".$result["audience_type"]."] is no longer supported, but is used in event_id [".$event_id."].");
					break;
				}

				if ($row["title"]) {
					$output[$result["audience_type"]][] = $row;
				}
			}
		}
	}

	return $output;
}
/**
 * Returns true if user is a member of the event audience, false if they are not
 *
 * @global object $db
 * @param int $event_id
 * @return array
 */
function events_fetch_event_attendance_for_user($event_id = 0, $user_id = false) {
	global $db;
	if($event_id && $user_id){
		$query = "SELECT * FROM `event_attendance` WHERE `event_id` = ".$db->qstr($event_id)." AND `proxy_id` = ".$db->qstr($user_id);
		return $db->GetRow($query);
	}
	return false;
}

/**
 * Returns true if user is a member of the event audience, false if they are not
 *
 * @global object $db
 * @param int $event_id
 * @return array
 */
function events_fetch_event_audience_for_user($event_id = 0, $user_id = false) {
	global $db;
	$user_id = (int) $user_id;
	$event_id = (int) $event_id;
	if ($event_id && $user_id) {
		$query = "SELECT * FROM `event_audience` WHERE `event_id` = ".$db->qstr($event_id);
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				switch ($result["audience_type"]) {
					case "course_id" : // Course Audience
						$query = "	SELECT u.*, d.`eattendance_id` AS `has_attendance` FROM
									`group_members` a
									RIGHT JOIN `course_audience` b
									ON b.`audience_type` = 'group_id'
									AND b.`audience_value` = a.`group_id`
									RIGHT JOIN `".AUTH_DATABASE."`.`user_data` u
									ON a.`proxy_id` = u.`id`
									OR (b.`audience_type` = 'proxy_id'
									AND b.`audience_value` = u.`id`)
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($event_id)."
									WHERE b.`course_id` = ".$db->qstr($result["audience_value"])."
									AND u.`id` = ".$db->qstr($user_id)."
									GROUP BY u.`id`";
						$course_audience = $db->getAll($query);
						if ($course_audience) {
							return true;
						}
					break;
					case "group_id" : // Course Groups
						$query = "	SELECT u.*, d.`eattendance_id` AS `has_attendance` FROM
									`course_group_audience` a
									JOIN `".AUTH_DATABASE."`.`user_data` u
									ON a.`proxy_id` = u.`id`
									AND a.`cgroup_id` = ".$db->qstr($result["audience_value"])."
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($event_id)."
                                    WHERE u.`id` = ".$db->qstr($user_id);
						$group_audience = $db->getAll($query);
						if ($group_audience) {
                            return true;
						}
						break;
					case "cohort" :	// Cohorts
						$query = "	SELECT u.*, d.`eattendance_id` AS `has_attendance` FROM
									`group_members` a
									JOIN `".AUTH_DATABASE."`.`user_data` u
									ON a.`proxy_id` = u.`id`
									AND a.`group_id` = ".$db->qstr($result["audience_value"])."
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($event_id)."
									WHERE u.`id` = ".$db->qstr($user_id);
						$group_audience = $db->getAll($query);
						if ($group_audience) {
							return true;
						}
					break;
					case "proxy_id" : // Learners
						$query = "	SELECT u.*, d.`eattendance_id` AS `has_attendance` FROM
									`".AUTH_DATABASE."`.`user_data` u
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($event_id)."
									WHERE u.`id` = ".$db->qstr($result["audience_value"])."
									AND u.`id` = ".$db->qstr($user_id);
						$user_audience = $db->getAll($query);
						if ($user_audience) {
							return true;
						}
					break;
					default : // No longer supported, but include the value just in case.
						application_log("notice", "audience_type [".$result["audience_type"]."] is no longer supported, but is used in event_id [".$event_id."].");
					break;
				}


			}
		}
	}
	return false;
}
/**
 * Returns all audience members [users, not just group names as may be the case in the function above] for the specified learning event. Also grabs their attendance.
 *
 * @global object $db
 * @param int $event_id
 * @return array
 */
function events_fetch_event_audience_attendance($event_id = 0) {
	global $db;
	$event_id = (int) $event_id;
	if ($event_id) {
		$query = "SELECT * FROM `event_audience` WHERE `event_id` = ".$db->qstr($event_id);
		$results = $db->GetAll($query);
		if ($results) {
			$event_audience = array();
			foreach ($results as $result) {
				switch ($result["audience_type"]) {
					case "course_id" : // Course Audience
						$query = "SELECT *
									FROM `course_audience` AS a
									JOIN `courses` AS b
									ON a.`course_id` = b.`course_id`
									AND a.`course_id` = " . $result["audience_value"] . "
									AND a.`audience_active` = 1
									JOIN `curriculum_periods` AS c
									ON b.`curriculum_type_id` = c.`curriculum_type_id`
									AND a.`cperiod_id` = c.`cperiod_id`
									WHERE UNIX_TIMESTAMP() BETWEEN c.`start_date` AND c.`finish_date`";
						$ca_result = $db->GetAll($query);
						foreach($ca_result as $ca) {
							if ($ca["audience_type"] == "group_id") {
								$query = "	SELECT u.*,u.`id` AS proxy_id, CONCAT_WS(', ',u.`lastname`,u.`firstname`) AS fullname, d.`eattendance_id` AS `has_attendance`
											FROM `group_members` a
											JOIN `".AUTH_DATABASE."`.`user_data` u
											ON a.`proxy_id` = u.`id`
											AND a.`group_id` = ".$db->qstr($ca["audience_value"])."
											AND a.`member_active` = 1
											JOIN `".AUTH_DATABASE."`.`user_access` ua
											ON u.`id` = ua.`user_id` AND ua.`app_id` IN (".AUTH_APP_IDS_STRING.")
											LEFT JOIN `event_attendance` d
											ON u.`id` = d.`proxy_id`
											WHERE d.`event_id` = " . $db->qstr($event_id);
							} elseif ($ca["audience_type"] == "proxy_id") {
								$query = "	SELECT DISTINCT u.*,u.`id` AS proxy_id, CONCAT_WS(', ',u.`lastname`,u.`firstname`) AS fullname, d.`eattendance_id` AS `has_attendance`
											FROM `course_audience` a
											JOIN `".AUTH_DATABASE."`.`user_data` u
											ON a.`audience_value` = u.`id`
											AND a.`audience_value` = ".$db->qstr($ca["audience_value"])."
											JOIN `".AUTH_DATABASE."`.`user_access` ua
											ON u.`id` = ua.`user_id` AND ua.`app_id` IN (".AUTH_APP_IDS_STRING.")
											LEFT JOIN `event_attendance` d
											ON u.`id` = d.`proxy_id`
											WHERE d.`event_id` = " . $db->qstr($event_id);
							}
							$course_audience = $db->getAll($query);
							$course_audience = array_unique($course_audience,SORT_REGULAR);
							usort($course_audience,"audience_sort");
							if ($course_audience) {
								$event_audience = array_merge($event_audience,$course_audience);
							}
						}
					break;
					case "group_id" : // Course Groups
						$query = "	SELECT u.*, d.`eattendance_id` AS `has_attendance` FROM
									`course_group_audience` a
									JOIN `".AUTH_DATABASE."`.`user_data` u
									ON a.`proxy_id` = u.`id`
									AND a.`cgroup_id` = ".$db->qstr($result["audience_value"])."
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($event_id);
						$group_audience = $db->getAll($query);
						if ($group_audience) {
							$event_audience = array_merge($event_audience,$group_audience);
						}
						break;
					case "cohort" :	// Cohorts
						$query = "	SELECT u.*, d.`eattendance_id` AS `has_attendance` FROM
									`group_members` a
									JOIN `".AUTH_DATABASE."`.`user_data` u
									ON a.`proxy_id` = u.`id`
									AND a.`group_id` = ".$db->qstr($result["audience_value"])."
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($event_id);
						$group_audience = $db->getAll($query);
						if ($group_audience) {
							$event_audience = array_merge($event_audience,$group_audience);
						}
					break;
					case "proxy_id" : // Learners
						$query = "	SELECT u.*, d.`eattendance_id` AS `has_attendance` FROM
									`".AUTH_DATABASE."`.`user_data` u
									LEFT JOIN `event_attendance` d
									ON u.`id` = d.`proxy_id`
									AND d.`event_id` = ".$db->qstr($event_id)."
									WHERE u.`id` = ".$db->qstr($result["audience_value"]);
						$user_audience = $db->getAll($query);
						if ($user_audience) {
							$event_audience = array_merge($event_audience,$user_audience);
						}
					break;
					default : // No longer supported, but include the value just in case.
						application_log("notice", "audience_type [".$result["audience_type"]."] is no longer supported, but is used in event_id [".$event_id."].");
					break;
				}


			}
			$event_audience = array_unique($event_audience,SORT_REGULAR);
			usort($event_audience,"audience_sort");
			return $event_audience;
		}
	}
	return false;
}
/**
 * used by usort to sort audience by last name
 * @param type $a
 * @param type $b
 * @return type
 */
function audience_sort($a,$b){
	 if ($a["lastname"] == $b["lastname"]) {
                return 0;
        }
        return ($a["lastname"] < $b["lastname"]) ? -1 : 1;
}

/**
 * This function returns arrays of the requested resources from a learning event.
 *
 * @global object $db
 * @param int $event_id
 * @param array $options
 * @return array
 */
function events_fetch_event_resources($event_id = 0, $options = array(), $exclude = array()) {
	global $db, $ENTRADA_USER;

	$fetch_files = false;
	$fetch_links = false;
	$fetch_quizzes = false;
	$fetch_discussions = false;
	$fetch_types = false;
    $fetch_lti = false;

	$output = array();

	$event_id = (int) $event_id;

	if ($event_id) {
		if (is_scalar($options)) {
			if (trim($options) != "") {
				$options = array($options);
			} else {
				$options = array();
			}
		}

		if (!count($options)) {
			$options = array("all");
		}

		if (is_scalar($exclude)) {
			if (trim($exclude) != "") {
				$exclude = array($exclude);
			} else {
				$exclude = array();
			}
		}

		if (in_array("all", $options)) {
			$fetch_files = true;
			$fetch_links = true;
			$fetch_quizzes = true;
			$fetch_discussions = true;
			$fetch_types = true;
            $fetch_lti = true;
		}

		if (in_array("files", $options)) {
			$fetch_files = true;
		}

		if (in_array("links", $options)) {
			$fetch_links = true;
		}

		if (in_array("quizzes", $options)) {
			$fetch_quizzes = true;
		}

		if (in_array("discussions", $options)) {
			$fetch_discussions = true;
		}

		if (in_array("types", $options)) {
			$fetch_types = true;
		}

		if (in_array("files", $exclude)) {
			$fetch_files = false;
		}

		if (in_array("links", $exclude)) {
			$fetch_links = false;
		}

		if (in_array("quizzes", $exclude)) {
			$fetch_quizzes = false;
		}

		if (in_array("discussions", $exclude)) {
			$fetch_discussions = false;
		}

		if (in_array("types", $exclude)) {
			$fetch_types = false;
		}

        if (in_array("lti", $exclude)) {
            $fetch_lti = false;
        }

		if ($fetch_files) {
			/**
			 * This query will get all of the files associated with this event.
			 */
			$query	= "	SELECT a.*, MAX(b.`timestamp`) AS `last_visited`
						FROM `event_files` AS a
						LEFT JOIN `statistics` AS b
						ON b.`module` = 'events'
						AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
						AND b.`action` = 'file_download'
						AND b.`action_field` = 'file_id'
						AND b.`action_value` = a.`efile_id`
						WHERE a.`event_id` = ".$db->qstr($event_id)."
						AND a.`draft` = 0
						GROUP BY a.`efile_id`
						ORDER BY a.`file_category` ASC, a.`file_title` ASC";
			$output["files"] = $db->GetAll($query);
		}

		if ($fetch_links) {
			/**
			 * This query will retrieve all of the links associated with this evevnt.
			 */
			$query	= "	SELECT a.*, MAX(b.`timestamp`) AS `last_visited`
						FROM `event_links` AS a
						LEFT JOIN `statistics` AS b
						ON b.`module` = 'events'
						AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
						AND b.`action` = 'link_access'
						AND b.`action_field` = 'link_id'
						AND b.`action_value` = a.`elink_id`
						WHERE a.`event_id` = ".$db->qstr($event_id)."
						AND a.`draft` = 0
						GROUP BY a.`elink_id`
						ORDER BY a.`link_title` ASC";
			$output["links"] = $db->GetAll($query);
		}

		if ($fetch_quizzes) {

			/**
			 * This query will retrieve all of the quizzes associated with this evevnt.
			 */
			$query	= "	SELECT a.*, b.`quiztype_code`, b.`quiztype_title`, MAX(c.`timestamp`) AS `last_visited`
						FROM `attached_quizzes` AS a
						LEFT JOIN `quizzes_lu_quiztypes` AS b
						ON b.`quiztype_id` = a.`quiztype_id`
						LEFT JOIN `statistics` AS c
						ON c.`module` = 'events'
						AND c.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
						AND c.`action` = 'quiz_complete'
						AND c.`action_field` = 'aquiz_id'
						AND c.`action_value` = a.`aquiz_id`
						WHERE a.`content_type` = 'event'
						AND a.`content_id` = ".$db->qstr($event_id)."
						AND a.`draft` = 0
						GROUP BY a.`aquiz_id`
						ORDER BY a.`required` DESC, a.`quiz_title` ASC, a.`release_until` ASC";
			$output["quizzes"] = $db->GetAll($query);
		}

		if ($fetch_discussions) {
			/**
			 * This query will retrieve all discussions associated with this event.
			 */
			$query	= "	SELECT *
						FROM `event_discussions`
						WHERE `event_id` = ".$db->qstr($event_id)."
						AND `discussion_comment` <> ''
						AND `discussion_active` = '1'
						ORDER BY `ediscussion_id` ASC";
			$output["discussions"] = $db->GetAll($query);
		}

		if ($fetch_types) {
			$query	= "	SELECT *
						FROM `event_eventtypes` AS a
						LEFT JOIN `events_lu_eventtypes` AS b
						ON a.`eventtype_id` = b.`eventtype_id`
						WHERE a.`event_id` = ".$db->qstr($event_id)."
						ORDER BY a.`eeventtype_id` ASC";
			$output["types"] = $db->GetAll($query);
		}

        if ($fetch_lti) {
            $query	= "	SELECT *
						FROM `event_lti_consumers`
						WHERE `event_id` = ".$db->qstr($event_id)."
						ORDER BY `lti_title` ASC";
            $output["lti"] = $db->GetAll($query);
        }
	}

	return $output;
}

function events_fetch_all_locations() {
	global $db, $ENTRADA_USER;

	$query = "SELECT a.`room_id`, b.`building_id`, b.`building_code`, b.`building_name`, CONCAT(b.`building_code`, ' ', a.`room_number`) AS `room_name`
			  FROM `global_lu_rooms` as a, `global_lu_buildings` as b
			  WHERE a.`building_id` = b.`building_id`
			  AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
			  ORDER BY CONCAT(b.`building_code`, ' ', a.`room_number`)";
	$results = $db->GetAll($query);
	return $results;
}

function events_fetch_all_buildings() {
	global $db, $ENTRADA_USER;

	$query = "SELECT b.`building_id`,b.`building_code`,b.`building_name` FROM `global_lu_buildings` as b
			  WHERE b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
			  ORDER BY b.`building_code` ASC";
	$results = $db->GetAll($query);
	return $results;
}
function events_fetch_all_rooms_by_building_id($building_id) {
	global $db, $ENTRADA_USER;

	$query = "SELECT CONCAT(b.`building_code`, ' ', a.`room_number`) AS `room_name`
			  FROM `global_lu_rooms` AS a, `global_lu_buildings` AS b
			  WHERE a.`building_id` = b.`building_id`
			  AND b.`building_id` = ".$db->qstr($building_id);
	$results = $db->GetAll($query);
	return $results;
}
function events_fetch_location_by_room_id($room_id) {
	global $db;
	
	$query = "SELECT CONCAT(b.`building_code`, ' ', a.`room_number`) AS `room_name`
			  FROM `global_lu_rooms` AS a, `global_lu_buildings` AS b
			  WHERE a.`building_id` = b.`building_id`
			  AND a.`room_id` = ".$db->qstr($room_id);
	$result = $db->GetRow($query);
	return ($result && $result['room_name'] ? $result['room_name'] : "");
}

function events_fetch_location_by_event_id($event_id) {
	global $db;
	
	$query = "SELECT CONCAT(b.`building_code`, ' ', a.`room_number`) AS `room_name`
			  FROM `global_lu_rooms` AS a, `global_lu_buildings` AS b, `events` AS c
			  WHERE a.`building_id` = b.`building_id`
			  AND a.`room_id` = c.`room_id`
			  AND c.`event_id` = ".$db->qstr($event_id);
	$result = $db->GetRow($query);
	return ($result && $result['room_name'] ? $result['room_name'] : "");
}

/**
* Calls event_objectives_bottom_leaves to get array of objectives to display, then calls event_objectives_display_leaf for each objective
*/
function assessment_objectives_display_leafs($objectives, $course_id, $assessment_id) {
	global $translate;
	$leaves = assessment_objectives_bottom_leaves($objectives, $course_id, $assessment_id, false);
	$displayed = array();
	foreach ($leaves as $importance => $leafs) {
		if ($leafs) {
            ?>
            <a name="#<?php echo $importance; ?>-objective-list"></a>
            <h2 id="<?php echo $importance; ?>-toggle"  title="<?php echo ucwords($importance) . " " . $translate->_("Objectives"); ?> List" class="list-heading <?php echo ($importance != "primary" ? "collapsed" : ""); ?>"><?php echo ucwords($importance) . " " . $translate->_("Objectives");?></h2>
            <div id="<?php echo $importance; ?>-objectives-list">
                <ul class="objective-list mapped-list" id="mapped_<?php echo $importance; ?>_objectives" data-importance="hierarchical">
                <?php
                foreach ($leafs as $leaf) {
                    if (!in_array($leaf["objective_id"],$displayed)){
                        array_push($displayed, $leaf["objective_id"]);
                        assessment_objectives_display_leaf($leaf);
                    }
                }
                ?>
                </ul>
            </div>
            <?php
		}
	}
}


/**
* Recursively loops through children until it finds the lowest ancestor of mapped parents
* If parent is mapped to the assessment (somehow), children of that parent are automatically mapped as well
*/
function assessment_objectives_bottom_leaves($objectives, $course_id, $assessment_id, $parent_mapped = false, $parent_importance = false){
	global $db;
	$importances = array('primary','secondary','tertiary');
	$list = array('primary' => array(),'secondary'=>array(),'tertiary'=>array());
	foreach($objectives as $objective){
		$imp = ($parent_importance?$parent_importance:($objective["importance"]?$objective["importance"]:3));
		switch($imp){
			case 1:
				$importance = "primary";
				break;
			case 2:
				$importance = "secondary";
				break;
			case 3:
				$importance = "tertiary";
				break;
			default:
				$importance;
		}
		$query = "SELECT a.*,COALESCE(b.`objective_details`,a.`objective_description`) AS `objective_description` ,COALESCE(b.`objective_type`,c.`objective_type`) AS `objective_type`,
					b.`importance`,c.`objective_details`, COALESCE(c.`aobjective_id`,0) AS `mapped`,
					COALESCE(b.`cobjective_id`,0) AS `mapped_to_course`
					FROM `global_lu_objectives` a
					LEFT JOIN `course_objectives` b
					ON a.`objective_id` = b.`objective_id`
                    AND b.`active` = '1'
					AND b.`course_id` = ".$db->qstr($course_id)."
					LEFT JOIN `assessment_objectives` c
					ON c.`objective_id` = a.`objective_id`
					AND c.`assessment_id` = ".$db->qstr($assessment_id)."
					WHERE a.`objective_active` = '1'
					AND a.`objective_parent` = ".$db->qstr($objective["objective_id"])."
					GROUP BY a.`objective_id`
					ORDER BY a.`objective_order` ASC";
		$children = $db->GetAll($query);
		$map = ($parent_mapped?true:($objective["mapped"]?true:false));
		if (!$children) {
			if ($map) {
				$objective["mapped"] = 1;
			}
			array_push($list[$importance],$objective);
		}else{
			$response = assessment_objectives_bottom_leaves($children,$course_id,$assessment_id,$map,$imp);
			if ($response) {
				if ($parent_mapped) {
					foreach($response as $imp=>$list){
						foreach($list as $k=>$item){
							$response[$imp][$k]["mapped"] = 1;
						}
					}
				}
				foreach($importances as $importance){
					$list[$importance] = array_merge($list[$importance],$response[$importance]);
				}
			}
		}
	}

	return $list;
}

/**
* Displays the objective leaf as it is on the event content page
*/
function assessment_objectives_display_leaf($objective){
	global $translate;
	$title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
	?>
		<li class = "mapped-objective"
			id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
			data-id = "<?php echo $objective["objective_id"]; ?>"
			data-title="<?php echo $title;?>"
			data-description="<?php echo htmlentities($objective["objective_description"]);?>">
			<strong><?php echo $title; ?></strong>
			<div class="objective-description">
				<?php
				$set = fetch_objective_set_for_objective_id($objective["objective_id"]);
				if ($set) {
					echo "From the " . $translate->_("Curriculum Tag Set") . ": <strong>".$set["objective_name"]."</strong><br/>";
				}
				?>
				<?php echo $objective["objective_description"];?>
			</div>
			<div class="assessment-objective-controls">
				<input type="checkbox" class="checked-mapped" id="check_mapped_<?php echo $objective['objective_id'];?>" value="<?php echo $objective['objective_id'];?>" <?php echo $objective["mapped"]?' checked="checked"':''; ?>/>
			</div>
		</li>
	<?php
}

/**
* Recursively loops up tree from mapped assessment objectives checking each parent to see if its the passed objective id.
* Parents are collected and passed to the next iteration as a group to save function calls
*/
function assessment_objective_parent_mapped_course($objective_id,$assessment_id,$include_bottom = false){
	global $db;
	$query = "	SELECT a.*, c.course_id
				FROM `global_lu_objectives` a
				JOIN `assessments` c
				ON c.`assessment_id` = ".$db->qstr($assessment_id)."
				AND c.`active` = '1'
				LEFT JOIN `assessment_objectives` b
				ON b.`objective_id` = a.`objective_id`
				AND b.`assessment_id` = c.`assessment_id`
				WHERE a.`objective_id` = ".$db->qstr($objective_id)."
				AND a.`objective_active` = '1'
				GROUP BY a.`objective_id`
				ORDER BY a.`objective_id` ASC";
	$objectives = $db->GetAll($query);
	if (!$objectives) return false;
	$course_id = $objectives[0]["course_id"];
	return assessment_objective_parent_mapped_recursive($objectives,$objective_id,$course_id,$assessment_id,$include_bottom);
}

/**
* Recursively loops up tree from mapped assessment objectives checking each parent to see if its the passed objective id.
* Parents are collected and passed to the next iteration as a group to save function calls
*/
function assessment_objective_decendant_mapped_course($objective_id,$assessment_id,$include_bottom = false){
	global $db;
	$query = "	SELECT a.*, c.course_id
				FROM `global_lu_objectives` a
				JOIN `assessments` c
				ON c.`assessment_id` = ".$db->qstr($assessment_id)."
				AND c.`active` = '1'
				LEFT JOIN `assessment_objectives` b
				ON b.`objective_id` = a.`objective_id`
				AND b.`assessment_id` = c.`assessment_id`
				WHERE a.`objective_active` = '1'
				GROUP BY a.`objective_id`
				ORDER BY a.`objective_id` ASC";
	$objectives = $db->GetAll($query);
	if (!$objectives) return false;
	$course_id = $objectives[0]["course_id"];
	return assessment_objective_parent_mapped_recursive($objectives,$objective_id,$course_id,$assessment_id,$include_bottom);
}
function assessment_objective_parent_mapped_recursive($objectives,$objective_id,$course_id,$assessment_id,$include_bottom = false){
	global $db;
	$parents = array();
	foreach ($objectives as $objective) {
		if ($include_bottom && $objective["objective_id"] == $objective_id) {
			return true;
		}
		if ($objective["objective_parent"]) {
			$query = "	SELECT a.*, COALESCE(b.`cobjective_id`, 0) AS `mapped`
						FROM `global_lu_objectives` a
						LEFT JOIN `course_objectives` b
						ON a.`objective_id` = b.`objective_id`
                        AND b.`active` = '1'
						AND b.`course_id` = ".$db->qstr($course_id)."
						LEFT JOIN `assessment_objectives` c
						ON c.`objective_id` = a.`objective_id`
						AND c.`assessment_id` = ".$db->qstr($assessment_id)."
						WHERE a.`objective_id` = ".$db->qstr($objective["objective_parent"])."
						AND a.`objective_active` = '1'
						GROUP BY a.`objective_id`
						ORDER BY a.`objective_order` ASC";
			$parent = $db->GetRow($query);
			if ($parent) {
				//if this parent is the objective id we're looking for, return true
				if ($include_bottom && $objective["objective_id"] == $objective_id) {
					return true;
				}
				if ($parent["mapped"]) {
					return true;
				}
				$parents[] = $parent;
			}
		}
	}
	//if no parents have been found for this level of parents, no children exist for this id
	if (!$parents) return false;
	return assessment_objective_parent_mapped_recursive($parents,$objective_id,$course_id,$assessment_id,$include_bottom);
}


/**
* Calls event_objectives_bottom_leaves to get array of objectives to display, then calls event_objectives_display_leaf for each objective
*/
function event_objectives_display_leafs($objectives, $course_id, $event_id, $editable = true){
	global $translate;
	$leaves = event_objectives_bottom_leaves($objectives,$course_id,$event_id, false);
	$displayed = array();
	foreach ($leaves as $importance=>$leafs) {
		//if no leaves, don't show category
		if($leafs && !empty($leafs)){
            ?>
            <a name="#<?php echo $importance;?>-objective-list"></a>
            <h2 id="<?php echo $importance;?>-toggle"  title="<?php echo ucwords($importance) . " " . $translate->_("Objectives");?> List" class="list-heading <?php echo $importance == 'primary'?'':'collapsed';?>"><?php echo ucwords($importance) . " " . $translate->_("Objectives");?></h2>
            <div id="<?php echo $importance;?>-objectives-list">
                <ul class="objective-list mapped-list" id="mapped_<?php echo $importance;?>_objectives" data-importance="hierarchical">
                    <?php
                    foreach($leafs as $leaf){
                        if (!in_array($leaf["objective_id"],$displayed)){
                            array_push($displayed,$leaf["objective_id"]);
                            event_objectives_display_leaf($leaf, $editable);
                        }
                    }
                    ?>
                </ul>
            </div>
            <?php
		}
	}
}

/**
* Recursively loops through children until it finds the lowest ancestor of mapped parents
* If parent is mapped to the event (somehow), children of that parent are automatically mapped as well
*/
function event_objectives_bottom_leaves($objectives,$course_id,$event_id, $parent_mapped = false, $parent_importance = false){
	global $db;
	$importances = array('primary','secondary','tertiary');
	$list = array('primary' => array(),'secondary'=>array(),'tertiary'=>array());
	foreach($objectives as $objective){
		$imp = ($parent_importance ? $parent_importance : ($objective["importance"] ? $objective["importance"] : 1));
		switch($imp){
			case 1:
			default:
				$importance = "primary";
				break;
			case 2:
				$importance = "secondary";
				break;
			case 3:
				$importance = "tertiary";
				break;
		}
		$query = "SELECT a.*,COALESCE(b.`objective_details`,a.`objective_description`) AS `objective_description` ,COALESCE(b.`objective_type`,c.`objective_type`) AS `objective_type`,
					b.`importance`,c.`objective_details`, COALESCE(c.`eobjective_id`,0) AS `mapped`,
					COALESCE(b.`cobjective_id`,0) AS `mapped_to_course`
					FROM `global_lu_objectives` a
					LEFT JOIN `course_objectives` b
					ON a.`objective_id` = b.`objective_id`
                    AND b.`active` = '1'
					AND b.`course_id` = ".$db->qstr($course_id)."
					LEFT JOIN `event_objectives` c
					ON c.`objective_id` = a.`objective_id`
					AND c.`event_id` = ".$db->qstr($event_id)."
                    JOIN `courses` AS d
                    ON d.`course_id` = ".$db->qstr($course_id)."
                    JOIN `objective_organisation` AS e
                    ON a.`objective_id` = e.`objective_id`
                    AND d.`organisation_id` = e.`organisation_id`
					WHERE a.`objective_active` = '1'
					AND a.`objective_parent` = ".$db->qstr($objective["objective_id"])."
					GROUP BY a.`objective_id`
					ORDER BY a.`objective_order` ASC
					";
		$children = $db->GetAll($query);
		$map = ($parent_mapped ? true : ($objective["mapped"] ? true : false));
		if (!$children) {
			if ($map) {
				$objective["mapped"] = 1;
			}
			array_push($list[$importance],$objective);
		} else {

			$response = event_objectives_bottom_leaves($children,$course_id,$event_id,$map,$imp);
			if ($response) {
				if ($parent_mapped) {
					foreach($response as $imp=>$list){
						foreach($list as $k => $item){
                            $response[$imp][$k]["mapped"] = 1;
						}
					}
				}
				foreach($importances as $importance){
					$list[$importance] = array_merge($list[$importance],$response[$importance]);
				}
			}
		}
	}

	return $list;
}

/**
* Displays the objective leaf as it is on the event content page
*/
function event_objectives_display_leaf($objective, $editable = true){
    global $translate;
    if ($objective["mapped"] || $editable) {
        $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
        ?>
            <li class = "mapped-objective"
                id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
                data-id = "<?php echo $objective["objective_id"]; ?>"
                data-title="<?php echo $title;?>"
                data-description="<?php echo htmlentities($objective["objective_description"]);?>">
                <strong><?php echo $title; ?></strong>
                <div class="objective-description">
                    <?php
                    $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                    if ($set) {
                        echo "From the " . $translate->_("Curriculum Tag Set") . ": <strong>".$set["objective_name"]."</strong><br/>";
                    }
                    ?>
                    <?php echo $objective["objective_description"];?>
                </div>
                <div class="event-objective-controls">
                    <?php
                    if ($editable) {
                        ?>
                        <input type="checkbox" class="checked-mapped" id="check_mapped_<?php echo $objective['objective_id'];?>" value="<?php echo $objective['objective_id'];?>" <?php echo ($objective["mapped"] ? ' checked="checked"':''); ?>/>
                        <?php
                    } else {
                        echo "&nbsp;";
                    }
                    ?>
                </div>
                <?php if ($objective["mapped"] && $editable) { ?>
                <div 	id="text_container_<?php echo $objective["objective_id"]; ?>"
                        class="objective_text_container"
                        data-id="<?php echo $objective["objective_id"]; ?>">
                    <label 	for="objective_text_<?php echo $objective["objective_id"]; ?>"
                            class="content-small" id="objective_<?php echo $objective["objective_id"]; ?>_append"
                            style="vertical-align: middle;">Provide your sessional free-text objective below as it relates to this curricular objective.</label>
                    <textarea 	name="objective_text[<?php echo $objective["objective_id"]; ?>]"
                                id="objective_text_<?php echo $objective["objective_id"]; ?>"
                                data-id="<?php echo $objective["objective_id"]; ?>"
                                class="expandable"
                                style="height: 28px; overflow: hidden;"><?php echo $objective["objective_details"];?></textarea>
                </div>
                <?php } ?>
            </li>
        <?php
    }
}

/**
* Recursively loops up tree from mapped event objectives checking each parent to see if its the passed objective id.
* Parents are collected and passed to the next iteration as a group to save function calls
*/
function event_objective_parent_mapped_course($objective_id,$event_id,$include_bottom = false){
	global $db;
	$query = "	SELECT a.*, c.course_id
				FROM `global_lu_objectives` a
				LEFT JOIN `event_objectives` b
				ON b.`objective_id` = a.`objective_id`
				AND b.`event_id` = ".$db->qstr($event_id)."
				LEFT JOIN `events` c
				ON b.`event_id` = c.`event_id`
				WHERE b.`event_id` = ".$db->qstr($event_id)."
				AND a.`objective_id` = ".$db->qstr($objective_id)."
				AND a.`objective_active` = '1'
				GROUP BY a.`objective_id`
				ORDER BY a.`objective_id` ASC";
	$objectives = $db->GetAll($query);
	if (!$objectives) return false;
	$course_id = $objectives[0]["course_id"];
	return event_objective_parent_mapped_recursive($objectives,$objective_id,$course_id,$event_id, $include_bottom);
}

/**
* Recursively loops up tree from mapped event objectives checking each parent to see if its the passed objective id.
* Parents are collected and passed to the next iteration as a group to save function calls
*/
function event_objective_decendant_mapped_course($objective_id,$event_id,$include_bottom = false){
	global $db;
	$query = "	SELECT a.*, c.course_id
				FROM `global_lu_objectives` a
				LEFT JOIN `event_objectives` b
				ON b.`objective_id` = a.`objective_id`
				AND b.`event_id` = ".$db->qstr($event_id)."
				LEFT JOIN `events` c
				ON b.`event_id` = c.`event_id`
				WHERE b.`event_id` = ".$db->qstr($event_id)."
				AND a.`objective_active` = '1'
				GROUP BY a.`objective_id`
				ORDER BY a.`objective_id` ASC";
	$objectives = $db->GetAll($query);
	if (!$objectives) return false;
	$course_id = $objectives[0]["course_id"];
	return event_objective_parent_mapped_recursive($objectives,$objective_id,$course_id,$event_id, $include_bottom);
}

function event_objective_parent_mapped_recursive($objectives,$objective_id,$course_id,$event_id,$include_bottom = false){
	global $db;
	$parents = array();
	foreach ($objectives as $objective) {
		if ($include_bottom && $objective["objective_id"] == $objective_id) {
			return true;
		}
		if ($objective["objective_parent"]) {
			$query = "	SELECT a.*, COALESCE(b.`cobjective_id`, 0) AS `mapped`, b.`importance`
						FROM `global_lu_objectives` a
						LEFT JOIN `course_objectives` b
						ON a.`objective_id` = b.`objective_id`
                        AND b.`active` = '1'
						AND b.`course_id` = ".$db->qstr($course_id)."
						LEFT JOIN `event_objectives` c
						ON c.`objective_id` = a.`objective_id`
						AND c.`event_id` = ".$db->qstr($event_id)."
						WHERE a.`objective_id` = ".$db->qstr($objective["objective_parent"])."
						AND a.`objective_active` = '1'
						GROUP BY a.`objective_id`
						ORDER BY a.`objective_order` ASC
						";
			$parent = $db->GetRow($query);
			if ($parent) {
				//if this parent is the objective id we're looking for, return true
				if ($include_bottom && $objective["objective_id"] == $objective_id) {
					return true;
				}
				if ($parent["mapped"]) {
					return ($parent["importance"] ? $parent["importance"] : true);
				}
				$parents[] = $parent;
			}
		}
	}
	//if no parents have been found for this level of parents, no children exist for this id
	if (!$parents) return false;
	return event_objective_parent_mapped_recursive($parents,$objective_id,$course_id,$event_id,$include_bottom);
}

function event_objectives_in_list($objectives, $parent_id, $top_level_id, $edit_text = false, $parent_active = false, $importance = 1, $course = true, $top = true, $display_importance = "primary", $full_objective_list = false, $course_id = 0) {
	global $edit_ajax, $ENTRADA_USER, $translate;

	if (!$full_objective_list) {
		$full_objective_list = events_fetch_objectives_structure($parent_id, $objectives["used_ids"]);
	}
	$flat_objective_list = events_flatten_objectives($full_objective_list);

	$output = "";
	$active = array("primary" => false, "secondary" => false, "tertiary" => false);
	if ($top) {
		if (!empty($objectives["primary_ids"])) {
			$active["primary"] = true;
		} elseif ($display_importance == "primary") {
			$display_importance == "secondary";
		}
		if (!empty($objectives["secondary_ids"])) {
			$active["secondary"] = true;
		} elseif ($display_importance == "secondary") {
			$display_importance == "tertiary";
		}
		if (!empty($objectives["tertiary_ids"])) {
			$active["tertiary"] = true;
		}
		$objectives = $objectives["objectives"];
	}
	if (!is_array($edit_ajax)) {
		$edit_ajax = array();
	}

	if ((is_array($objectives)) && ($total = count($objectives))) {
		$count	= 0;
		if ($top) {
			$output	= "\n<ul class=\"objective-list\" id=\"objective_".$parent_id."_list\"".($parent_id == $top_level_id ? " style=\"padding-left: 0; margin-top: 0\"" : "").">\n";
		}
		$iterated = false;
		do {
			if ($iterated) {
				if ($display_importance == "primary" && $active["secondary"]) {
					$display_importance = "secondary";
				} elseif ((($display_importance == "secondary" || $display_importance == "primary") && $active["tertiary"])) {
					$display_importance = "tertiary";
				}
			}
			if ($top) {
				$output .= "<h2".($iterated ? " class=\"collapsed\"" : "")." title=\"".ucwords($display_importance)." ".$translate->_("Objectives")."\">".ucwords($display_importance)." ".$translate->_("Objectives")."</h2>\n";
				$output .= "<div id=\"".($display_importance)."-objectives\">\n";
			}
			if ($flat_objective_list) {
				foreach ($flat_objective_list as $objective_id => $objective_activity) {
					$objective = $objectives[$objective_id];
					$count++;

						if (($objective["parent"] == $parent_id) && (($objective["objective_".$display_importance."_children"]) || ($objective[$display_importance]) || ($parent_active))) {
							$importance = (($objective["primary"]) ? 1 : ($objective["secondary"] ? 2 : ($objective["tertiary"] ? 3 : $importance)));

							if (((($objective[$display_importance]) || ($parent_active)) && empty($objective_activity["children"]))) {
							$output .= "<li>\n";
							if ($edit_text && !$course) {
								$output .= "<div id=\"objective_table_".$objective_id."\" class=\"content-small\" style=\"color: #000\">\n";
								$output .= "	<input type=\"checkbox\" name=\"checked_objectives[".$objective_id."]\" id=\"objective_checkbox_".$objective_id."\"".($course ? " disabled=\"true\" checked=\"checked\"" : " onclick=\"if (this.checked) { $('objective_table_".$objective_id."_details').show(); $('objective_text_".$objective_id."').focus(); } else { $('objective_table_".$objective_id."_details').hide(); }\"".($objective["event_objective"] ? " checked=\"checked\"" : ""))." style=\"float: left;\" value=\"1\" />\n";
								$output .= "	<div style=\"padding-left: 25px;\"><label for=\"objective_checkbox_".$objective_id."\">".$objective["description"]." <a class=\"external content-small\" href=\"".ENTRADA_RELATIVE."/courses/objectives?section=objective-details&amp;oid=".$objective_id."\">".$objective["name"]."</a></label></div>\n";
								$output .= "</div>\n";
								$output .= "<div id=\"objective_table_".$objective_id."_details\" style=\"padding-left: 25px; margin-top: 5px".($objective["event_objective"] ? "" : "; display: none")."\">\n";
								$output .= "	<label for=\"c_objective_".$objective_id."\" class=\"content-small\" id=\"objective_".$objective_id."_append\" style=\"vertical-align: middle;\">Provide your sessional free-text objective below as it relates to this curricular objective.</label>\n";
								$output .= "	<textarea name=\"objective_text[".$objective_id."]\" id=\"objective_text_".$objective_id."\" class=\"expandable\">".(isset($objective["event_objective_details"]) ? html_encode($objective["event_objective_details"]) : "")."</textarea>";
								$output .= "</div>\n";
							} elseif ($edit_text) {
								$edit_ajax[] = $objective_id;
								$output .= "<div id=\"objective_table_".$objective_id."\">\n";
								$output .= "	<label for=\"objective_checkbox_".$objective_id."\" class=\"heading\">".$objective["name"]."</label> ( <span id=\"edit_mode_".$objective_id."\" class=\"content-small\" style=\"cursor: pointer\">edit</span> )<span id=\"clear_objective_".$objective_id."\" style=\"margin-left: 10px;".(isset($objective["objective_details"]) && $objective["objective_details"] ? "" : " display: none;")."\">( <span id=\"revert_mode_".$objective_id."\" class=\"content-small\" onclick=\"new Ajax.Updater('objective_description_".$objective_id."', '".ENTRADA_RELATIVE."/api/objective-details.api.php', {parameters: { id: '".$objective_id."', cids: '".$course_id."', objective_details: '' }, onComplete: function() { jQuery('#clear_objective_".$objective_id."').hide(); }})\" style=\"cursor: pointer\">clear custom text</span> )</span>\n";
								$output .= "	<div class=\"content-small\" style=\"padding-left: 25px;\" id=\"objective_description_".$objective_id."\">".(isset($objective["objective_details"]) && $objective["objective_details"] ? $objective["objective_details"] : $objective["description"])."</div>\n";
								$output .= "</div>\n";
							} else {
								$output .= "<input type=\"checkbox\" id=\"objective_checkbox_".$objective_id."\" name=\"course_objectives[".$objective_id."]\"".(isset($objective["event_objective"]) && $objective["event_objective"] ? " checked=\"checked\"" : "")." onclick=\"if (this.checked) { this.parentNode.addClassName('".($importance == 2 ? "secondary" : ($importance == 3 ? "tertiary" : "primary"))."'); } else { this.parentNode.removeClassName('".($importance == 2 ? "secondary" : ($importance == 3 ? "tertiary" : "primary"))."'); }\" style=\"float: left;\" value=\"1\" />\n";
								$output .= "<label for=\"objective_checkbox_".$objective_id."\" class=\"heading\">".$objective["name"]."</label>\n";
								$output .= "<div style=\"padding-left: 25px;\">\n";
								$output .=		$objective["description"]."\n";
								if (isset($objective["objective_details"]) && $objective["objective_details"]) {
									$output .= "<br /><br />\n";
									$output .= "<em>".$objective["objective_details"]."</em>";
								}
								$output .= "</div>\n";
							}
							$output .= "</li>\n";

						} else {
								$output .= event_objectives_in_list($objectives, $objective_id,$top_level_id, $edit_text, (($objective[$display_importance] || $parent_active) ? true : false), $importance, $course, false, $display_importance, $full_objective_list, $course_id);
						}
					}
				}
			}
			$iterated = true;
			if ($top) {
				$output .= "</div>\n";
			}
		} while ((($display_importance != "tertiary") && ($display_importance != "secondary" || $active["tertiary"]) && ($display_importance != "primary" || $active["secondary"] || $active["tertiary"])) && $top);
		if ($top) {
			$output .= "</ul>\n";
		}
	}

	return $output;
}

function events_flatten_objectives ($objectives) {
	foreach ($objectives as $objective_id => $objective) {
		$flat_objectives[$objective_id] = $objective;
		if (count($objective["children"])) {
			$flat_objectives = $flat_objectives + events_flatten_objectives($objective["children"]);
		}
	}
	return $flat_objectives;
}

/**
 * This function is used in conjunction with the output of events_fetch_objectives_structure and is used to
 * find all the active objectives by objective name.
 *
 * @param type $objs - an array containing an objective set as produced by events_fetch_objectives_structure.
 * @param type $output - an array of objective names of all the active objectives as accumlated by recursive call.
 * @return type - an array of objective names of all the active objectives.
 */
function events_all_active_objectives($objs, $output = array()) {
	foreach($objs as $obj_id => $details) {
		if ($details["objective_active"]) {
			$output[] = $details["objective_name"];
		}
		if ($details["children_active"]) {
			$output = events_all_active_objectives($details["children"], $output);
		}
	}
	return $output;
}

function events_fetch_objectives_structure($parent_id, $used_ids, $org_id = 0) {
	global $db, $ENTRADA_USER;

	$org_id = ($org_id == 0 ? $ENTRADA_USER->getActiveOrganisation() : (int) $org_id );

	$full_objective_list = array();

	$query = "SELECT a.* FROM `global_lu_objectives` AS a
				JOIN `objective_organisation` AS b
				ON a.`objective_id` = b.`objective_id`
				WHERE a.`objective_parent` = ".$db->qstr($parent_id)."
				AND b.`organisation_id` = ".$db->qstr($org_id)."
				ORDER BY a.`objective_order` ASC";
	$objective_children = $db->GetAll($query);

	if ($objective_children) {
		foreach ($objective_children as $objective) {
			$full_objective_list[$objective["objective_id"]] = array(
																		"objective_name" => $objective["objective_name"],
																		"objective_active" => (is_array($used_ids) && array_search($objective["objective_id"], $used_ids) !== false ? true : false),
																		"children_active" => false,
																		"children" => array()
																	);
			$full_objective_list[$objective["objective_id"]]["children"] = events_fetch_objectives_structure($objective["objective_id"], $used_ids, $org_id);
			if (count($full_objective_list[$objective["objective_id"]]["children"])) {
				$full_objective_list[$objective["objective_id"]]["children_active"] = event_objectives_active($full_objective_list[$objective["objective_id"]]["children"]);
			}
		}
	}

	return $full_objective_list;
}

function event_objectives_active($objectives) {

	foreach ($objectives as $objective) {
		if ($objective["objective_active"]) {
			return true;
		} elseif (count($objective["children"]))  {
			if (event_objectives_active($objective["children"])) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Returns the apartment schedule ID of the accommodation if there
 * is one for the provided event and proxy ID.
 *
 * @param int $event_id
 * @param int $proxy_id
 * @return int
 */
function regionaled_apartment_check($event_id = 0, $proxy_id = 0) {
	global $db;

	if (($event_id = (int) $event_id) && ($proxy_id = (int) $proxy_id)) {
		$query = "SELECT `aschedule_id` FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` WHERE `event_id` = ".$db->qstr($event_id)." AND `proxy_id` = ".$db->qstr($proxy_id);
		$aschedule_id = $db->GetOne($query);
		if ($aschedule_id) {
			return (int) $aschedule_id;
		}
	}

	return 0;
}

function regionaled_apartment_notification($type, $to = array(), $keywords = array()) {
	global $ERROR, $NOTICE, $SUCCESS, $ERRORSTR, $NOTICESTR, $SUCCESSSTR, $AGENT_CONTACTS, $ENTRADA_TEMPLATE;

	if (!is_array($to) || !isset($to["email"]) || !valid_address($to["email"]) || !isset($to["firstname"]) || !isset($to["lastname"])) {
		application_log("error", "Attempting to send a regionaled_apartment_notification() how the recipient information was not complete.");

		return false;
	}

	if (!in_array($type, array("delete", "confirmation", "rejected"))) {
		application_log("error", "Encountered an unrecognized notification type [".$type."] when attempting to send a regionaled_apartment_notification().");

		return false;
	}

	$xml_file = $ENTRADA_TEMPLATE->absolute()."/email/regionaled-learner-accommodation-".$type.".xml";
	$xml = @simplexml_load_file($xml_file);
	if ($xml && isset($xml->lang->{DEFAULT_LANGUAGE})) {
		$subject = trim($xml->lang->{DEFAULT_LANGUAGE}->subject);
		$message = trim($xml->lang->{DEFAULT_LANGUAGE}->body);

		foreach ($keywords as $keyword => $value) {
			$subject = str_ireplace("%".strtoupper($keyword)."%", $value, $subject);
			$message = str_ireplace("%".strtoupper($keyword)."%", $value, $message);
		}

		/**
		 * Notify the learner they have been removed from this apartment.
		 */
		$mail = new Zend_Mail();
		$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
		$mail->addHeader("X-Section", $keywords["department_tile"] . " Accommodations Module", true);
		$mail->clearFrom();
		$mail->clearSubject();
		$mail->setFrom($AGENT_CONTACTS["agent-regionaled"][$keywords["department_id"]]["email"], APPLICATION_NAME. $keywords["department_tile"] . " Accommodation System");
		$mail->setSubject($subject);
		$mail->setBodyText(clean_input($message, "emailcontent"));

		$mail->clearRecipients();
		$mail->addTo($to["email"], $to["firstname"]." ".$to["lastname"]);

		if ($mail->send()) {
			return true;
		} else {
			$NOTICE++;
			$NOTICESTR[] = "We were unable to e-mail an e-mail notification <strong>".$to["email"]."</strong>.<br /><br />A system administrator was notified of this issue, but you may wish to contact this learner manually and let them know their accommodation has ben removed.";

			application_log("error", "Unable to send accommodation notification to [".$to["email"]."] / type [".$type."]. Zend_Mail said: ".$mail->ErrorInfo);
		}
	} else {
		application_log("error", "Unable to load the XML file [".$xml_file."] or the XML file did not contain the language requested [".DEFAULT_LANGUAGE."], when attempting to send a regional education notification.");
	}

	return false;
}

function regionaled_apartment_availability($apartment_ids = array(), $event_start = 0, $event_finish = 0) {
	global $db;

	if (is_scalar($apartment_ids)) {
		if ((int) $apartment_ids) {
			$apartment_ids = array($apartment_ids);
		} else {
			$apartment_ids = array();
		}
	}

	$output = array();
	$output["openings"] = 0;
	$output["apartments"] = array();

	if (count($apartment_ids) && ($event_start = (int) $event_start) && ($event_finish = (int) $event_finish)) {

		$query = "	SELECT a.*, b.`country`, c.`province`
					FROM `".CLERKSHIP_DATABASE."`.`apartments` AS a
					LEFT JOIN `global_lu_countries` AS b
					ON b.`countries_id` = a.`countries_id`
					LEFT JOIN `global_lu_provinces` AS c
					ON c.`province_id` = a.`province_id`
					WHERE a.`apartment_id` IN (".implode(", ", $apartment_ids).")
					AND (a.`available_start` = '0' OR a.`available_start` <= ".$db->qstr($event_start).")
					AND (a.`available_finish` = '0' OR a.`available_finish` > ".$db->qstr($event_finish).")";
		$apartments = $db->GetAll($query);
		if ($apartments) {
			foreach ($apartments as $apartment) {
				$occupants = regionaled_apartment_occupants($apartment["apartment_id"], $event_start, $event_finish);
				$occupants_tmp = array();
				$occupancy_totals = array();
				$concurrent_occupants = 0;

				if ($occupants && is_array($occupants)) {
					foreach ($occupants as $occupant) {
						$concurrent_occupants = 1;

						if (count($occupants_tmp)) {
							foreach ($occupants_tmp as $tmp_occupant) {
								if ((($occupant["inhabiting_start"] >= $tmp_occupant["inhabiting_start"]) || ($occupant["inhabiting_finish"] >= $tmp_occupant["inhabiting_start"])) && ($occupant["inhabiting_start"] <= $tmp_occupant["inhabiting_finish"])) {
									$concurrent_occupants++;
								}
							}
						}

						$occupants_tmp[] = $occupant;
						$occupancy_totals[] = $concurrent_occupants;
					}
				}

				if (count($occupancy_totals)) {
					$concurrent_occupants = max($occupancy_totals);
				} else {
					$concurrent_occupants = 0;
				}

				if ($concurrent_occupants < $apartment["max_occupants"]) {
					$openings = ($apartment["max_occupants"] - $concurrent_occupants);
					$output["openings"] += $openings;
					$output["apartments"][$apartment["apartment_id"]] = array (
																			"openings" => $openings,
																			"occupants" => $occupants,
																			"details" => $apartment
																		);
				}
			}
		}
	}

	return $output;
}

function regionaled_apartment_occupants($apartment_id = 0, $event_start = 0, $event_finish = 0) {
	global $db;

	if (($apartment_id = (int) $apartment_id) && ($event_start = (int) $event_start) && ($event_finish = (int) $event_finish)) {
		$query = "	SELECT a.*, b.`username`, CONCAT(b.`firstname`, ' ', b.`lastname`) AS `fullname`, b.`gender`, b.`notes`, c.`group`
					FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
					ON b.`id` = a.`proxy_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS c
					ON c.`user_id` = b.`id`
					AND c.`app_id` = ".$db->qstr(AUTH_APP_ID)."
					WHERE a.`apartment_id` = ".$db->qstr($apartment_id)."
					AND (".$db->qstr($event_start)." BETWEEN a.`inhabiting_start` AND a.`inhabiting_finish` OR
					".$db->qstr($event_finish)." BETWEEN a.`inhabiting_start` AND a.`inhabiting_finish` OR
					a.`inhabiting_start` BETWEEN ".$db->qstr($event_start)." AND ".$db->qstr($event_finish)." OR
					a.`inhabiting_finish` BETWEEN ".$db->qstr($event_start)." AND ".$db->qstr($event_finish).")
					GROUP BY a.`aschedule_id`					
					ORDER BY a.`inhabiting_start` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			return $results;
		}
	}

	return false;
}

function course_objectives_multiple_select_options_checked($id, $checkboxes, $options) {
	if ((!is_array($checkboxes)) || ($id == null)) {
		return null;
	}

	$default_options = array(
		"title"			=>"Select Multiple",
		"cancel"		=> false,
		"cancel_text"	=> "Close",
		"submit"		=> false,
		"submit_text"	=> "Submit",
		"class"			=> "",
		"width"			=> "350px",
		"hidden"		=> true
	);

	$options = array_merge($default_options, $options);
	$classes = array("select_multiple_container");

	if(is_array($options["class"])) {
		foreach($options["class"] as $class) {
			$classes[] = $class;
		}
	} else {
		if($options["class"] != "") {
			$classes[] = $options["class"];
		}
	}

	$class_string = implode(" ", $classes);

	$output  = "<div style=\"position: relative;\">\n";
	$output .= "	<div id=\"".$id."_options\" class=\"".$class_string."\" style=\"".($options["hidden"] ? "display: none; " : "")."width: ".$options["width"]."; position: absolute; background-color: #FFFFFF; top: -80px; left: -10px;\">\n";
	$output .= "		<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\">\n";
	$output .= "			<thead>\n";
	$output .= "				<tr>\n";
	$output .= "					<td class=\"inner-content-box-head\">".$options["title"]."</td>\n";
	$output .= "				</tr>\n";
	$output .= "			</thead>\n";
	$output .= "		</table>\n";
	$output .= "		<div id=\"".$id."_scroll\" class=\"select_multiple_scroll\">\n";
	$output .= "			<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\">\n";
	$output .= "			<tbody>\n";
	$output .= "				<tr>\n";
	$output .= "					<td class=\"inner-content-box-body\">\n";
	$output .= "						<div class=\"inner-content-box-body-content\" style=\"overflow: auto;\">\n";
	$output .= "							<table style=\"width: 95%\" cellspacing=\"0\" cellpadding=\"0\" class=\"select_multiple_table\">\n";
	$output .= "							<colgroup>\n";
	$output .= "								<col style=\"width: 95%\" />\n";
	$output .= "								<col style=\"width: 5%\" />\n";
	$output .= "							</colgroup>\n";
	$output .= "							<tbody>\n";
	$output .=								course_objectives_multiple_select_table($checkboxes, 0, 0);
	$output .= "							</tbody>\n";
	$output .= "							</table>\n";
	$output .= "						</div>\n";
	$output .= "						<div style=\"clear: both;\"></div>\n";
	$output .= "					</td>\n";
	$output .= "				</tr>\n";
	$output .= "			</tbody>\n";
	$output .= "			</table>\n";
	$output .= "		</div>\n";
	$output .= "						<div class=\"select_multiple_submit\">\n";
	$output .= "							<div class=\"select_multiple_filter\"></div>\n";
	$output .=								(($options['cancel'] == true) ? "<input type=\"button\" class=\"btn\" value=\"".$options["cancel_text"]."\" id=\"".$id."_cancel\" />" : "");
	$output .=								(($options['submit'] == true) ? "<input type=\"button\" class=\"btn btn-primary\" value=\"".$options["submit_text"]."\" id=\"".$id."_submit\" />" : "");
	$output .= "						</div>\n";
	$output .= "	</div>";
	$output .= "</div>";

	return $output;
}

function course_objectives_multiple_select_table($checkboxes, $indent = 0, $i = 0) {
	$output			= "";
	$parent_id		= 0;
	$parent_checked	= false;

	if ($indent > 99) {
		return false;
	}

	foreach ($checkboxes as $checkbox) {
		$is_category = false;

		if ((!isset($checkbox["category"])) || ($checkbox["category"] == false)) {
			if ($parent_id) {
				$class = "parent".$parent_id;
				if ($parent_checked) {
					$class .= " disabled";
				}
			}
		} else {
			$is_category = true;
			$class = "category";
			$parent_id = $checkbox["value"];
			$parent_checked = ($checkbox["checked"] == "checked=\"checked\"");
		}

		$output .= "<tr class=\"".$class."\" id=\"row_".$checkbox["value"]."\">\n";
		$output .= "	<td class=\"select_multiple_name indent_".$indent." description\">\n";
		$output .= "		<label for=\"".$checkbox["value"]."\">".$checkbox["text"]."</label>\n";
		$output .= "	</td>\n";
		$output .= "	<td class=\"select_multiple_checkbox\">\n";
		$output .= "		<input type=\"checkbox\" id=\"".$checkbox["value"]."\" value=\"".$checkbox["value"]."\" ".$checkbox["checked"]." />\n";
		$output .= "	</td>\n";
		$output .= "</tr>";

		if (isset($checkbox["options"])) {
			$output .= course_objectives_multiple_select_table($checkbox["options"], ($indent + 1), ($i + 1));
		}
	}

	return $output;
}

function community_module_permissions_check($proxy_id, $module, $module_section, $record_id) {
	global $db, $COMMUNITY_ID, $LOGGED_IN, $COMMUNITY_MEMBER, $COMMUNITY_ADMIN, $NOTICE, $NOTICESTR, $ERROR, $ERRORSTR, $PAGE_ID;
	switch($module) {
		case "discussions" :
			require_once(COMMUNITY_ABSOLUTE."/modules/discussions.inc.php");
			return discussion_module_access($record_id, "view-post");
			break;
		case "galleries" :
			require_once(COMMUNITY_ABSOLUTE."/modules/galleries.inc.php");
			return galleries_module_access($record_id, "view-photo");
			break;
		case "shares" :
			require_once(COMMUNITY_ABSOLUTE."/modules/shares.inc.php");
			return shares_module_access($record_id, "view-file");
			break;
		case "polls" :
			require_once(COMMUNITY_ABSOLUTE."/modules/polls.inc.php");
			return polls_module_access($record_id, "view-poll");
			break;
		default :
			return true;
			break;
	}
}

if(!function_exists("get_hash")) {
	function get_hash() {
		global $db;

		do {
			$hash = md5(uniqid(rand(), 1));
		} while($db->GetRow("SELECT `id` FROM `".AUTH_DATABASE."`.`password_reset` WHERE `hash` = ".$db->qstr($hash)));

		return $hash;
	}
}

/**
 * This function merely returns the region_name associated with the region_id passed in
 *
 * @param int $region_id
 * @return string $region_name
 */
function get_region_name($region_id = 0) {
	global $db;

	if ($region_id = (int) $region_id) {
		$query = "	SELECT `region_name`
					FROM `".CLERKSHIP_DATABASE."`.`regions`
					WHERE `region_id` = ".$db->qstr($region_id);
		$region_name = $db->GetOne($query);
		if ($region_name) {
			return $region_name;
		}
	}

	return false;
}

/**
 * This function will notify the regional education office of updates / deletes to affected apartment events.
 *
 * @param string $action
 * @param int $event_id
 * @return bool $success
 */
function notify_regional_education($action, $event_id) {
	global $db, $AGENT_CONTACTS, $event_info, $ENTRADA_USER, $translate;

	$query	= "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`events` AS a
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS b
				ON a.`region_id` = b.`region_id`
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS c
				ON a.`event_id` = c.`event_id`
				WHERE a.`event_id` = ".$db->qstr($event_id);
	$result	= $db->GetRow($query);
	if ($result) {
			/**
			 * Don't process this if the event has already ended as there's not need for notifications.
			 */
			if($result["event_finish"] > time()) {
				$whole_name	= get_account_data("firstlast", $result["etype_id"]);

				$query		= "	SELECT a.`inhabiting_start`, a.`inhabiting_finish`, b.`apartment_title`
								FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS a
								LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartments` AS b
								ON b.`apartment_id` = a.`apartment_id`
								WHERE a.`event_id` = ".$db->qstr($event_id);
				$apartments	= $db->GetAll($query);
				if ($apartments) {
					switch($action) {
						case "deleted" :
							$message  = "Attention ".$AGENT_CONTACTS["agent-regionaled"]["name"].",\n\n";
							$message .= $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." has removed an event from ".$whole_name."'s ";
							$message .= $translate->_("clerkship") . " schedule, to which you had previously assigned housing. Due to the removal of this event from the system, ";
							$message .= "the housing associated with it has also been removed.\n\n";
							$message .= "Information For Reference:\n\n";
							$message .= "Event Information:\n";
							$message .= "Event Title:\t".html_decode($result["event_title"])."\n";
							$message .= "Region:\t\t".$result["region_name"]."\n";
							$message .= "Start Date:\t".date(DEFAULT_DATETIME_FORMAT, $result["event_start"])."\n";
							$message .= "Finish Date:\t".date(DEFAULT_DATETIME_FORMAT, $result["event_finish"])."\n\n";
							if(($apartments) && ($assigned_apartments = @count($apartments))) {
								$message .= "Apartment".(($assigned_apartments != 1) ? "s" : "")." ".$whole_name." was removed from:\n";
								foreach($apartments as $apartment) {
									$message .= "Apartment Title:\t".$apartment["apartment_title"]."\n";
									$message .= "Inhabiting Start:\t".date(DEFAULT_DATETIME_FORMAT, $apartment["inhabiting_start"])."\n";
									$message .= "Inhabiting Finish:\t".date(DEFAULT_DATETIME_FORMAT, $apartment["inhabiting_finish"])."\n\n";
								}
							}
							$message .= "=======================================================\n\n";
							$message .= "Deletion Date:\t".date("r", time())."\n";
							$message .= "Deleted By:\t".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." (".$ENTRADA_USER->getID().")\n";
						break;
						case "change-critical" :
							$message  = "Attention ".$AGENT_CONTACTS["agent-regionaled"]["name"].",\n\n";
							$message .= $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." has updated an event in ".$whole_name."'s ";
							$message .= $translate->_("clerkship") . " schedule, to which you had previously assigned housing. This update involves a change to the region or the ";
							$message .= "dates that the event took place in. Due to this critical change taking place, the housing for this event for this ";
							$message .= "student has been removed.\n\n";
							if($result["manage_apartments"]) {
								$message .= "Please log into the " . $translate->_("clerkship") . " system and re-assign housing to this student for this event.\n\n";
							} else {
								$message .= "Since this event no longer is taking place in a region which is managed by Regional Education, \n";
								$message .= "no further action is required on your part in the system.\n\n";
							}
							$message .= "Information For Reference:\n\n";
							$message .= "OLD Event Information:\n";
							$message .= "Event Title:\t".$event_info["event_title"]."\n";
							$message .= "Region:\t\t".get_region_name($event_info["region_id"])."\n";
							$message .= "Start Date:\t".date(DEFAULT_DATETIME_FORMAT, $event_info["event_start"])."\n";
							$message .= "Finish Date:\t".date(DEFAULT_DATETIME_FORMAT, $event_info["event_finish"])."\n\n";
							$message .= "NEW Event Information:\n";
							$message .= "Event Title:\t".html_decode($result["event_title"])."\n";
							$message .= "Region:\t\t".$result["region_name"]."\n";
							$message .= "Start Date:\t".date(DEFAULT_DATETIME_FORMAT, $result["event_start"])."\n";
							$message .= "Finish Date:\t".date(DEFAULT_DATETIME_FORMAT, $result["event_finish"])."\n\n";
							if(($apartments) && ($assigned_apartments = @count($apartments))) {
								$message .= "Apartment".(($assigned_apartments != 1) ? "s" : "")." ".$whole_name." was removed from:\n";
								foreach($apartments as $apartment) {
									$message .= "Apartment Title:\t".$apartment["apartment_title"]."\n";
									$message .= "Inhabiting Start:\t".date(DEFAULT_DATETIME_FORMAT, $apartment["inhabiting_start"])."\n";
									$message .= "Inhabiting Finish:\t".date(DEFAULT_DATETIME_FORMAT, $apartment["inhabiting_finish"])."\n\n";
								}
							}
							$message .= "=======================================================\n\n";
							$message .= "Deletion Date:\t".date("r", time())."\n";
							$message .= "Deleted By:\t".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." (".$ENTRADA_USER->getID().")\n";
						break;
						case "change-non-critical" :
						case "updated" :
						default :
							$message  = "Attention ".$AGENT_CONTACTS["agent-regionaled"]["name"].",\n\n";
							$message .= $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." has updated an event in ".$whole_name."'s ";
							$message .= $translate->_("clerkship") . " schedule, to which you had previously assigned housing.\n\n";
							$message .= "Important:\n";
							$message .= "This update does not affect the date or region of this event, as such this change is considered non-critical ";
							$message .= "and no action is required on your part.\n\n";
							$message .= "Information For Reference:\n\n";
							$message .= "OLD Event Information:\n";
							$message .= "Event Title:\t".$event_info["event_title"]."\n";
							$message .= "Region:\t\t".get_region_name($event_info["region_id"])."\n";
							$message .= "Start Date:\t".date(DEFAULT_DATETIME_FORMAT, $event_info["event_start"])."\n";
							$message .= "Finish Date:\t".date(DEFAULT_DATETIME_FORMAT, $event_info["event_finish"])."\n\n";
							$message .= "NEW Event Information:\n";
							$message .= "Event Title:\t".html_decode($result["event_title"])."\n";
							$message .= "Region:\t\t".$result["region_name"]."\n";
							$message .= "Start Date:\t".date(DEFAULT_DATETIME_FORMAT, $result["event_start"])."\n";
							$message .= "Finish Date:\t".date(DEFAULT_DATETIME_FORMAT, $result["event_finish"])."\n\n";
							if(($apartments) && ($assigned_apartments = @count($apartments))) {
								$message .= "Apartment".(($assigned_apartments != 1) ? "s" : "")." ".$whole_name." is assigned to:\n";
								foreach($apartments as $apartment) {
									$message .= "Apartment Title:\t".$apartment["apartment_title"]."\n";
									$message .= "Inhabiting Start:\t".date(DEFAULT_DATETIME_FORMAT, $apartment["inhabiting_start"])."\n";
									$message .= "Inhabiting Finish:\t".date(DEFAULT_DATETIME_FORMAT, $apartment["inhabiting_finish"])."\n\n";
								}
							}
							$message .= "=======================================================\n\n";
							$message .= "Updated Date:\t".date("r", time())."\n";
							$message .= "Update By:\t".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." (".$ENTRADA_USER->getID().")\n";
						break;
					}
					$mail = new Zend_Mail();
					$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
					$mail->addHeader("X-Section", $translate->_("Clerkship") . " Notify System",true);
					$mail->clearFrom();
					$mail->clearSubject();
					$mail->setFrom($AGENT_CONTACTS["agent-notifications"]["email"], APPLICATION_NAME.' Clerkship System');
					$mail->setSubject("MEdTech " . $translate->_("Clerkship") . " System - ".ucwords($action)." Event");
					$mail->setBodyText($message);
					$mail->clearRecipients();
					$mail->addTo($AGENT_CONTACTS["agent-regionaled"]["email"], $AGENT_CONTACTS["agent-regionaled"]["name"]);
					$sent = true;
					try {
						$mail->send();
                                                application_log("success", "An event change notification has been sent to regional education to notify them of the changes to the event [".$event_info["event_id"]."] which will affect the apartment schedule.");
						return true;
					}
					catch (Exception $e) {
                                                system_log_data("error", "Unable to send ".$action." notification to regional education. Zend_mail said: ".$e->getMessage());

						return false;
					}
				} else {
					return true;
				}
			} else {
				// No need to notify Regional Education because the event is already over, just return true.
				return true;
			}
	} else {
		system_log_data("error", "The notify_regional_education() function returned false with no results from the database query. Database said: ".$db->ErrorMsg());

		return false;
	}
}

function number_suffix($number) {
	switch ( $number % 10 ){
		case '1': return $number . 'st';
		case '2': return $number . 'nd';
		case '3': return $number . 'rd';
		default:  return $number . 'th';
	}
}

/**
 * This function gets lookup data from the global_lu_roles table
 *
 * @return array $results
 */
function getPublicationRoles() {
    global $db;

    $query = "SELECT *
	FROM `global_lu_roles`
	ORDER BY `role_description`";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the global_lu_roles table
 *
 * @return array $result
 */
function getPublicationRoleSpecificFromID($roleID) {
    global $db;

    $query = "SELECT `role_description`
	FROM `global_lu_roles`
	WHERE `role_id` = '$roleID'";

    $result = $db->GetRow($query);

	return $result["role_description"];
}

/**
 * This function gets lookup data from the ar_lu_pr_roles table
 *
 * @return array $results
 */
function getPRPublicationRoles() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_pr_roles`
	ORDER BY `role_description`";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_pr_roles table
 *
 * @return array $result
 */
function getPRPublicationRoleSpecificFromID($roleID) {
    global $db;

    $query = "SELECT `role_description`
	FROM `ar_lu_pr_roles`
	WHERE `role_id` = '$roleID'";

    $result = $db->GetRow($query);

	return $result["role_description"];
}

/**
 * This function gets lookup data from the ar_lu_activity_types table
 *
 * @return array $results
 */
function getActivityTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_activity_types`
	ORDER BY `activity_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_clinical_locations table
 *
 * @return array $results
 */
function getClinicalLoactions() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_clinical_locations`
	ORDER BY `clinical_location` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_conference_paper_types table
 *
 * @return array $results
 */
function getConferencePaperTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_conference_paper_types`
	ORDER BY `conference_paper_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_consult_locations table
 *
 * @return array $results
 */
function getConsultLoactions() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_consult_locations`
	ORDER BY `consult_location` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_contribution_types table
 *
 * @return array $results
 */
function getContributionTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_contribution_types`
	WHERE `visible` = '1'
	ORDER BY `contribution_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_contribution_roles table
 *
 * @return array $results
 */
function getContributionRoles() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_contribution_roles`
	WHERE `visible` = '1'
	ORDER BY `contribution_role` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_degree_types table
 *
 * @return array $results
 */
function getDegreeTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_degree_types`
	WHERE `visible` = '1' OR `visible` = '2'
	ORDER BY `degree_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_education_locations table
 *
 * @return array $results
 */
function getEducationLocations() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_education_locations`
	ORDER BY `education_location` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_focus_groups table
 *
 * @return array $results
 */
function getPublicationGroups() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_focus_groups`
	ORDER BY `focus_group` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_hospital_location table
 *
 * @return array $results
 */
function getPublicationHospitals() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_hospital_location`
	ORDER BY `hosp_desc` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_innovation_types table
 *
 * @return array $results
 */
function getInnovationTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_innovation_types`
	ORDER BY `innovation_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_membership_roles table
 *
 * @return array $results
 */
function getMembershipRoles() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_membership_roles`
	ORDER BY `membership_role` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_on_call_locations table
 *
 * @return array $results
 */
function getOnCallLocations() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_on_call_locations`
	ORDER BY `on_call_location` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_other_locations table
 *
 * @return array $results
 */
function getOtherLocations() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_other_locations`
	ORDER BY `other_location` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_patent_types table
 *
 * @return array $results
 */
function getPatentTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_patent_types`
	ORDER BY `patent_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_prize_categories table
 *
 * @return array $results
 */
function getPrizeCategories() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_prize_categories`
	ORDER BY `prize_category` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_prize_types table
 *
 * @return array $results
 */
function getPrizeTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_prize_types`
	ORDER BY `prize_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_profile_roles table
 *
 * @return array $results
 */
function getProfileRoles() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_profile_roles`
	ORDER BY `profile_role` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_publication_statuses table
 *
 * @return array $results
 */
function getPulicationStatuses() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_publication_statuses`
	ORDER BY `publication_status` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_publication_type table
 *
 * @return array $results
 */
function getPublicationTypesSpecific($type) {
    global $db;

    if(is_array($type)) {
    	foreach($type as $typeDesc) {
    		if(isset($where)) {
    			$where .= " OR `type_description` = '".$typeDesc."'";
    		} else {
    			$where = " `type_description` = '".$typeDesc."'";
    		}
    	}
    	$query = "SELECT *
		FROM `ar_lu_publication_type`
		WHERE ".$where."
		ORDER BY `type_description`";
    } else {
	    $query = "SELECT *
		FROM `ar_lu_publication_type`
		WHERE `type_description` LIKE '$type%'
		ORDER BY `type_description`";
    }

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_publication_type table
 *
 * @return array $result
 */
function getPublicationTypesSpecificFromID($type_id) {
    global $db;

    $query = "SELECT `type_description`
	FROM `ar_lu_publication_type`
	WHERE `type_id`= '$type_id'";

    $result = $db->GetRow($query);

	return $result["type_description"];
}

/**
 * This function gets lookup data from the ar_lu_publication_type table
 *
 * @return array $results
 */
function getPublicationTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_publication_type`
	ORDER BY `type_description`";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_research_types table
 *
 * @return array $results
 */
function getResearchTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_research_types`
	ORDER BY `research_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_scholarly_types table
 *
 * @return array $results
 */
function getScholarlyTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_scholarly_types`
	ORDER BY `scholarly_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_self_education_types table
 *
 * @return array $results
 */
function getSelfEducationTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_self_education_types`
	ORDER BY `self_education_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_supervision_types table
 *
 * @return array $results
 */
function getSupervisionTypes() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_supervision_types`
	ORDER BY `supervision_type` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_trainee_levels table
 *
 * @return array $results
 */
function getTraineeLevels() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_trainee_levels`
	ORDER BY `trainee_level` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets lookup data from the ar_lu_undergraduate_supervision_courses table
 *
 * @return array $results
 */
function getUndergraduateSupervisionCourses() {
    global $db;

    $query = "SELECT *
	FROM `ar_lu_undergraduate_supervision_courses`
	ORDER BY `undergarduate_supervision_course` ASC";

    $results = $db->GetAll($query);

	return $results;
}

/**
 * This function builds an array of default enrollment numbers using lookup data from the events_lu_eventtypes table
 *
 * @return array $defaultEnrollmentArray
 */
function getDefaultEnrollment() {
    global $db, $ENTRADA_USER;

    $query = "	SELECT `t`.`eventtype_id`,`t`. `eventtype_title`,`t`. `eventtype_default_enrollment`
				FROM `events_lu_eventtypes` AS `t`
				LEFT JOIN  `eventtype_organisation` AS `e_o`
				ON `t`.`eventtype_id` = `e_o`.`eventtype_id`
				LEFT JOIN  `entrada_auth`.`organisations` AS `o`
				ON `o`.`organisation_id` = `e_o`.`organisation_id`
				WHERE `t`.`eventtype_default_enrollment` IS NOT NULL
				AND `o`.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
				ORDER BY `t`.`eventtype_default_enrollment` DESC";

    $results = $db->GetAll($query);

    $defaultEnrollmentArray = array();

	if ($results) {
		foreach($results as $result) {
			$defaultEnrollmentArray[$result["eventtype_id"]] = array("title" => $result["eventtype_title"], "default_enrollment" => $result["eventtype_default_enrollment"]);
		}
	}
	return $defaultEnrollmentArray;
}

/**
 * This function gets number from the user_data table
 *
 * @param int $proxy_id
 * @return array result["number"]
 */
function getNumberFromProxy($proxy_id) {
    global $db;

    $query = "SELECT `number`
	FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=". $db->qstr($proxy_id);

    $result = $db->GetRow($query);

	return $result["number"];
}

function userMKDir($dir)
{
	// may just need to be chmoded
	if(@is_dir($dir))
	{
		chmod($dir, 0777);
	}
	else
	{
		$oldumask = @umask(0);
		!@mkdir($dir, 0777);
		@umask($oldumask);
	}
}

/**
 * Function to display blurb on default enrollment numbers in education.
 *
 * @return string containing the HTML of the message or false if there is no HTML due to database connectivity problems.
 */
function display_default_enrollment($reportMode = false) {
	global $db;

	$output_html = "";

	$query = "	SELECT `eventtype_title`, `eventtype_default_enrollment` FROM `events_lu_eventtypes` AS `e`
				LEFT JOIN `eventtype_organisation` AS `e_o`
				ON `e`.`eventtype_id` = `e_o`.`eventtype_id`
				LEFT JOIN `entrada_auth`.`organisations` as `o`
				ON `o`.`organisation_id` = `e_o`. `organisation_id`
				WHERE `e`.`eventtype_active` = '1'
				AND `o`.`organisation_id` = ".$db->qstr($_SESSION["details"]["organisation_id"])."
				ORDER BY `e`.`eventtype_default_enrollment`";

	if($results = $db->GetAll($query)) {
		$previous = "";
		$outputLine = array();

		$output_html .= "<div id=\"display-error-box\" class=\"display-generic\">\n";
		$output_html .= "The following average enrollment numbers are implied";
		if(!$reportMode) {
			$output_html .= ". If yours differ substantially then please note this in the comments.";
		} else {
			$output_html .= ":";
		}
		$output_html .= "	<ul>\n";
		foreach($results as $result) {
			if($previous != "" && $previous != $result["eventtype_default_enrollment"]) {
				$output = implode(", ", $outputLine);

				$output_html .= "	<li>".$previous. " - " .$output."</li>\n";
				$outputLine = array();

				$outputLine[] = $result["eventtype_title"];
			} else {
				$outputLine[] = $result["eventtype_title"];
			}
			$previous = $result["eventtype_default_enrollment"];
		}
		$output = implode(", ", $outputLine);

		$output_html .= "	<li>".$previous. " - " .$output."</li>\n";
		$output_html .= "	</ul>\n";
		$output_html .= "</div>\n";
	}

	return (($output_html) ? $output_html : false);
}

/**
 * Function will return all pages below the specified parent_id, the current user has access to.
 *
 * @param int $identifier
 * @param int $indent
 * @return string
 */
function objectives_inlists($identifier = 0, $indent = 0) {
	global $db, $MODULE, $ORGANISATION_ID;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	$selected				= 0;

	$identifier	= (int) $identifier;
	$output		= "";

	if(($identifier===0) && ($indent === 0)) {
		$query	= "SELECT * FROM `global_lu_objectives` AS a
					LEFT JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_parent` = '0'
					AND a.`objective_active` = '1'
					AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
					ORDER BY a.`objective_order` ASC";
	} else {
		$query	= "SELECT a.* FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_parent` = ".$db->qstr($identifier)."
					AND a.`objective_active` = '1'
					AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
					ORDER BY a.`objective_order` ASC";
	}
	if ($indent < 1) {
		?>
		<script type="text/javascript">
		function showObjectiveChildren(objective_id) {
			if (!$(objective_id+'-children').visible()) {
				$('objective-'+objective_id+'-arrow').src = '<?php echo ENTRADA_URL; ?>/images/arrow-asc.gif';
				Effect.BlindDown(objective_id+'-children');
			} else {
				$('objective-'+objective_id+'-arrow').src = '<?php echo ENTRADA_URL; ?>/images/arrow-right.gif';
				Effect.BlindUp(objective_id+'-children');
			}
		}
		</script>
		<?php
	}
	$results	= $db->GetAll($query);
	if($results) {
		$output .= "<ul class=\"objectives-list\" id=\"".$identifier."-children\" ".($indent > 0 ? "style=\"display: none;\" " : "").">";
		foreach ($results as $result) {
			$output .= "<li id=\"content_".$result["objective_id"]."\">\n";
			$output .= "<div class=\"objective-container\">";
			$output .= "	<span class=\"delete\"><input type=\"checkbox\" id=\"delete_".$result["objective_id"]."\" name=\"delete[".$result["objective_id"]."][objective_id]\" value=\"".$result["objective_id"]."\"".(($selected == $result["objective_id"]) ? " checked=\"checked\"" : "")." onclick=\"$$('#".$result["objective_id"]."-children input[type=checkbox]').each(function(e){e.checked = $('delete_".$result["objective_id"]."').checked; if (e.checked) e.disable(); else e.enable();});\"/></span>\n";
			$output .= "	<span class=\"next\">";
			$query = "SELECT a.* FROM `global_lu_objectives` AS a
						JOIN `objective_organisation` AS b
						ON a.`objective_id` = b.`objective_id`
						WHERE a.`objective_parent` = ".$db->qstr($result["objective_id"])."
						AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
						AND a.`objective_active` = '1'
						ORDER BY a.`objective_order` ASC";
			if ($db->GetAll($query)) {
				$has_children = true;
			} else {
				$has_children = false;
			}
			if ($has_children) {
				$output .= "	<a class=\"objective-expand\" onclick=\"showObjectiveChildren('".$result["objective_id"]."')\"><img id=\"objective-".$result["objective_id"]."-arrow\" src=\"".ENTRADA_URL."/images/arrow-right.gif\" style=\"border: none; text-decoration: none;\" /></a>";
			}
			$output .= "	&nbsp;<a href=\"".ENTRADA_URL."/admin/objectives?".replace_query(array("section" => "edit", "step" => 1, "id" => $result["objective_id"]))."\">";
			$output .= html_encode($result["objective_name"])."</a></span>\n";
			$output .= "</div>";
			$output .= objectives_inlists($result["objective_id"], $indent + 1);
			$output .= "</li>\n";

		}
		$output .= "</ul>";
	}

	return $output;
}



/**
 * Function will return all pages below the specified parent_id, the current user has access to.
 *
 * @param int $identifier
 * @param int $indent
 * @return string
 */
function objectives_inlists_conf($identifier = 0, $indent = 0) {
	global $db, $MODULE, $ORGANISATION_ID;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	$selected				= 0;

	$identifier	= (int) $identifier;
	$output		= "";

	if(($identifier===0) && ($indent === 0)) {
		$query	= "	SELECT * FROM `global_lu_objectives` AS a
					LEFT JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_parent` = '0'
					AND a.`objective_active` = '1'
					AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
					ORDER BY a.`objective_order` ASC";
	} else {
		$query	= "	SELECT a.* FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_parent` = ".$db->qstr($identifier)."
					AND a.`objective_active` = '1'
					AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
					ORDER BY a.`objective_order` ASC";
	}
	if ($indent < 1) {
		?>
		<script type="text/javascript">
		function showObjectiveChildren(objective_id) {
			if (!$(objective_id+'-children').visible()) {
				$('objective-'+objective_id+'-arrow').src = '<?php echo ENTRADA_URL; ?>/images/arrow-asc.gif';
				Effect.BlindDown(objective_id+'-children');
			} else {
				$('objective-'+objective_id+'-arrow').src = '<?php echo ENTRADA_URL; ?>/images/arrow-right.gif';
				Effect.BlindUp(objective_id+'-children');
			}
		}
		</script>
		<?php
	}
	$results	= $db->GetAll($query);
	if($results) {
		$output .= "<ul class=\"objectives-list\" id=\"".$identifier."-children\" ".($indent > 0 ? "style=\"display: none;\" " : "").">";
		foreach ($results as $result) {
			$output .= "<li id=\"content_".$result["objective_id"]."\">\n";
			$output .= "<div class=\"objective-container\">";
			$output .= "	<span class=\"delete\"><input type=\"checkbox\" id=\"delete_".$result["objective_id"]."\" name=\"delete[".$result["objective_id"]."][objective_id]\" value=\"".$result["objective_id"]."\"".(($selected == $result["objective_id"]) ? " checked=\"checked\"" : "")." onclick=\"$$('#".$result["objective_id"]."-children input[type=checkbox]').each(function(e){e.checked = $('delete_".$result["objective_id"]."').checked; if (e.checked) e.disable(); else e.enable();});\"/></span>\n";
			$output .= "	<span class=\"next\">";
			$query = "SELECT a.* FROM `global_lu_objectives` AS a
						JOIN `objective_organisation` AS b
						ON a.`objective_id` = b.`objective_id`
						WHERE a.`objective_parent` = ".$db->qstr($result["objective_id"])."
						AND a.`objective_active` = '1'
						AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
						ORDER BY a.`objective_order` ASC";
			if ($db->GetAll($query)) {
				$has_children = true;
			} else {
				$has_children = false;
			}
			if ($has_children) {
				$output .= "	<a class=\"objective-expand\" onclick=\"showObjectiveChildren('".$result["objective_id"]."')\"><img id=\"objective-".$result["objective_id"]."-arrow\" src=\"".ENTRADA_URL."/images/arrow-right.gif\" style=\"border: none; text-decoration: none;\" /></a>";
			}
			$output .= "	&nbsp;<a href=\"".ENTRADA_RELATIVE."/admin/curriculum/objectives?".replace_query(array("section" => "edit", "step" => 1, "id" => $result["objective_id"]))."\">";
			$output .= html_encode($result["objective_name"])."</a></span>\n";
			$output .= "</div>";
			$output .= objectives_inlists_conf($result["objective_id"], $indent + 1);
			$output .= "</li>\n";

		}
		$output .= "</ul>";
	}

	return $output;
}




/**
 * Function will return all objectives below the specified parent_id, as option elements of an input select.
 * This is a recursive function that has a fall-out of 99 runs.
 *
 * @param int $parent_id
 * @param array $current_selected
 * @param int $indent
 * @param array $exclude
 * @return string
 */
function objectives_inselect($parent_id = 0, &$current_selected, $indent = 0, &$exclude = array()) {
	global $db, $MODULE, $COMMUNITY_ID, $ENTRADA_USER;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	if(!is_array($current_selected)) {
		$current_selected = array($current_selected);
	}

	$output	= "";
	$query	= "SELECT a.* FROM `global_lu_objectives` AS a
				JOIN `objective_organisation` AS b
				ON a.`objective_id` = b.`objective_id`
				WHERE a.`objective_active` = '1'
				AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
				AND a.`objective_parent` = ".$db->qstr($parent_id)."
				ORDER BY a.`objective_id` ASC";
	$results	= $db->GetAll($query);
	if($results) {
		foreach ($results as $result) {
			if((!@in_array($result["objective_id"], $exclude)) && (!@in_array($parent_id, $exclude))) {
				$output .= "<option value=\"".(int) $result["objective_id"]."\"".((@in_array($result["objective_id"], $current_selected)) ? " selected=\"selected\"" : "").">".str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $indent).(($indent > 0) ? "&rarr;&nbsp;" : "").html_encode($result["objective_name"])."</option>\n";
			} else {
				$exclude[] = (int) $result["objective_id"];
			}
			$output .= objectives_inselect($result["objective_id"], $current_selected, $indent + 1, $exclude, $community_id);
		}
	}

	return $output;
}

/**
 * Function will delete all pages below the specified parent_id.
 *
 * @param int $parent_id
 * @return true
 */
function objectives_delete($objective_id = 0, $children_move_target = 0, $level = 0) {
	global $db, $deleted_count;

	if($level > 99) {
		application_log("error", "Stopped an infinite loop in the objectives_delete() function.");

		return false;
	}

	if($objective_id = (int) $objective_id) {
		$query = "	UPDATE `global_lu_objectives`
					SET `objective_active` = '0'
					WHERE `objective_id` = ".$db->qstr($objective_id);
		if(!$db->Execute($query)) {
			application_log("error", "Unable to deactivate objective_id [".$objective_id."]. Database said: ".$db->ErrorMsg());
			$success = false;
		} else {
			$success = true;
			if ($level) {
				$deleted_count++;
			}
		}
		if($children_move_target === false) {
			$query		= "	SELECT `objective_id` FROM `global_lu_objectives`
							WHERE `objective_active` = '1'
							AND `objective_parent` = ".$db->qstr($objective_id);
			$results	= $db->GetAll($query);
			if($results) {
				foreach ($results as $result) {
					$success = objectives_delete($result["objective_id"], 0, $level+1);
				}
			}
		}
	}
	return $success;
}

/**
 * Function will delete all pages below the specified parent_id.
 *
 * @param int $parent_id
 * @return true
 */
function objectives_delete_for_org($organisation_id=0, $objective_id = 0, $children_move_target = 0, $level = 0) {
	global $db, $deleted_count;


	$query = "SELECT COUNT(*) FROM `objective_organisation`
				WHERE `objective_id` = ".$db->qstr($objective_id);

	$result = (int)$db->GetOne($query);
	$success = true;

	if($result == 1){
		$success = objectives_delete($objective_id,$children_move_target,$level);
	}

	$query = "DELETE FROM `objective_organisation`
				WHERE `objective_id` = ".$db->qstr($objective_id)."
				AND `organisation_id` = ".$db->qstr($organisation_id);
	$result = $db->Execute($query);
	if (!isset($result) || !$result) {
		$success = false;
	}

	return $success;
}

/**
 * Function will return all objectives below the specified objective_parent.
 *
 * @param int $identifier
 * @param int $indent
 * @return string
 */
function objectives_intable($ORGANISATION_ID, $identifier = 0, $indent = 0, $excluded_objectives = false) {
	global $db, $ONLOAD;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	$selected				= 0;
	$selectable_children	= true;


	$identifier	= (int) $identifier;
	$output		= "";

	if(($identifier)) {
		$query	= "SELECT * FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_id` = ".$db->qstr((int)$identifier)."
					AND a.`objective_active` = '1'
					AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
					ORDER BY a.`objective_order` ASC";
	}

	$result	= $db->GetRow($query);
	if($result) {
		$output .= "<tr id=\"content_".$result["objective_id"]."\">\n";
		$output .= "	<td>&nbsp;</td>\n";
		$output .= "	<td style=\"padding-left: ".($indent * 25)."px; vertical-align: middle\">";
		$output .= "		<img src=\"".ENTRADA_URL."/images/record-next-off.gif\" width=\"11\" height=\"11\" border=\"0\" alt=\"\" title=\"\" style=\"vertical-align: middle; margin-right: 5px\" />";
		$output .= "		".html_encode($result["objective_name"]);
		$output .= "		<input type=\"hidden\" name=\"delete[".((int)$identifier)."][objective_id]\" value=\"".((int)$identifier)."\" />";
		$output .= "</td>\n";
		$output .= "</tr>\n";
		$query = "SELECT COUNT(a.`objective_id`) FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_active` = '1'
					AND b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
					GROUP BY a.`objective_parent`
					HAVING a.`objective_parent` = ".$db->qstr((int)$identifier);
		$children = $db->GetOne($query);
		if ($children) {
			$output .= "<tbody id=\"delete-".((int)$identifier)."-children\">";
			$output .= "</tbody>";
			$output .= "	<tr>";
			$output .= "		<td>&nbsp;</td>\n";
			$output .= "		<td style=\"vertical-align: top;\">";
			$output .= "		<div style=\"padding-left: 30px\">";
			$output .= "		<span class=\"content-small\">There are children residing under <strong>".$result["objective_name"]."</strong>.</span>";
			$output .= "		</div>";
			$output .= "		<div style=\"padding-left: 30px\">";
			$output .= "			<input type=\"radio\" name=\"delete[".((int)$identifier)."][move]\" id=\"delete_".((int)$identifier)."_children\" value=\"0\" onclick=\"$('move-".((int)$identifier)."-children').hide();\" checked=\"checked\"/>";
			$output .= "			<label for=\"delete_".((int)$identifier)."_children\" class=\"form-nrequired\"><strong>Deactivate</strong> all children</label>";
			$output .= "			<br />";
			$output .= "			<input type=\"radio\" name=\"delete[".((int)$identifier)."][move]\" id=\"move_".((int)$identifier)."_children\" value=\"1\" onclick=\"$('move-".((int)$identifier)."-children').show();\" />";
			$output .= "			<label for=\"move_".((int)$identifier)."_children\" class=\"form-nrequired\"><strong>Move</strong> all children</label>";
			$output .= "			<br /><br />";
			$output .= "		</div>";
			$output .= "		</td>";
			$output .= "	</tr>";
			$output .= "<tbody id=\"move-".((int)$identifier)."-children\" style=\"display: none;\">";
			$output .= "	<tr>";
			$output .= "		<td>&nbsp;</td>\n";
			$output .= "		<td style=\"vertical-align: top; padding: 0px 0px 0px 30px\">";
			$output .= "			<div id=\"selectParent".(int)$identifier."Field\"></div>";
			$output .= "		</td>";
			$output .= "	</tr>";
			$output .= "	<tr>";
			$output .= "		<td colspan=\"2\">&nbsp;</td>";
			$output .= "	</tr>";
			$output .= "</tbody>";
			$ONLOAD[]	= "selectObjective(0, ".$identifier.", '".$excluded_objectives."')";

		}
	}

	return $output;
}


/**
 * Produces an option tag with the values filled in
 * @param unknown_type $value
 * @param unknown_type $label
 * @param unknown_type $selected
 * @return String Returns an html string for an option tag.
 */
function build_option($value, $label, $selected = false) {
	return "<option value=\"".$value."\"". ($selected ? "selected=\"selected\"" : "") .">".$label."</option>";
}



/**
 * routine to display standard status messages, Error, Notice, and Success
 * @param bool $fade true if the messages should fade out
 */
function display_status_messages($fade = false) {
	echo "<div class=\"status_messages\">";
	if (has_error()) {
		if ($fade) fade_element("out", "display-error-box");
		echo display_error();
	}

	if (has_success()) {
		if ($fade) fade_element("out", "display-success-box");
		echo display_success();
	}

	if (has_notice()) {
		if ($fade) fade_element("out", "display-notice-box");
		echo display_notice();
	}
	echo "</div>";
}

/**
 * Returns formatted mspr data supporting getDetails(), at this time only Leaves of absence, formal remdiation, and disciplinary actions
 */
function display_mspr_details($data) {
	ob_start();
	?>
	<ul class="mspr-list">
	<?php
	if ($data && ($data->count() > 0)) {
		foreach($data as $datum) {
		?>
		<li class="entry">
			<?php echo clean_input($datum->getDetails(), array("notags", "specialchars")) ?>
		</li>
		<?php
		}
	} else {
		?>
		<li>
		None
		</li>
		<?php
	}
	?>
	</ul>
	<?php
	return ob_get_clean();
}

/**
 * converts a month number (1-12) into a month name (January-December)
 * @param int $month_number
 */
function getMonthName($month_number) {
	static $months;

	//initialization of static if not done
	if (!$months) {
		$months=array();
		for($month_num = 1; $month_num <= 12; $month_num++) {
			$time = mktime(0,0,0,$month_num,1);
			$month_name= date("F", $time);
			$months[$month_num] = $month_name;
		}
	}
	//the -1 and +1 are to ensure the month num is from 1 to 12, not 0 to 11. The mod is done to  ensure the value is ithin bounds
	$month_number = (($month_number - 1) % 12) + 1;

	$month_name = $months[$month_number];
	return $month_name;
}

/**
 * Given two dates, this function will return a human-readable range
 * @param array $start_date {"d" => day, "m" => month, "y" => year}
 * @param array $end_date {"d" => day, "m" => month, "y" => year}
 */
function formatDateRange($start_date, $end_date) {

	$ds = $start_date["d"];
	$ms = $start_date["m"];
	$ys = $start_date["y"];

	$de = $end_date['d'];
	$me = $end_date['m'];
	$ye = $end_date['y'];

	//first determine if the range should be
	//year - year, or month year - month year
	//month month year or just year
	if ($ye && $ye != $ys) {
		if ($ms || $me){
			//if one of them is mising assume they are the same
			if (!$me) {
				$me = $ms;
			} elseif(!$ms) {
				$ms = $me;
			}
			if ($ds || $de) {
				//if one of them is mising assume they are the same
				if (!$de) {
					$de = $ds;
				} elseif(!$ds) {
					$ds = $de;
				}
				//full case: month day, year - month day, year
				$start = getMonthName($ms) . " " . $ds . ", " . $ys;
				$end = getMonthName($me) .   " " . $de . ", " . $ye;
			} else {
				//no day info: month year - month year
				$start = getMonthName($ms) . " " . $ys;
				$end = getMonthName($me) .   " " . $ye;
			}
			$period = $start . " - " . $end;
		} else {
			//year range without months at all...
			$period = $ys . " - " . $ye;
			//no check for days because days without months would be meaningless.
		}

	} else {
		//there is either no end year, or the end year is the same as the start year (equivalent)
		if ($ms || $me){
			if (!$me) {
				$me = $ms;
			} elseif(!$ms) {
				$ms = $me;
			}

			if ($me == $ms) {
				$month_name = getMonthName($ms);
				if ($ds || $de) {
					if ($ds && $de && $ds != $de) {
						$period = $month_name . " " . $ds . " - " . $de . ", " . $ys;
					} else {
						$period = $month_name . " " . ($ds ? $ds : $de) . ", " . $ys;
					}
				} else {
					$period = $month_name . " " . $ys;
				}
			} else {
				//months are different.
				if ($de || $ds) {
					//we already have a range

					//assume same start and end day if only one exists
					if (!$de) {
						$de = $ds;
					} elseif(!$ds) {
						$ds = $de;
					}
					$start = getMonthName($ms) . " " . $ds;
					$end = getMonthName($me) . " " . $de .  ", " . $ys;
				} else {
					//no day info...
					$start = getMonthName($ms);
					$end = getMonthName($me) .   " " . $ys;
				}
				$period = $start . " - " . $end;
			}


		} else {
			//single year entry
			$period = $ys;
		}
	}
	return $period;
}

/**
 * This function gets all of the departments a user is in
 * @param string $user_id
 * @return array $results
 */
function get_user_departments($user_id, $join_on_organisation = true) {
	global $db;

	$query = "	SELECT c.`department_title`, c.`department_id`
				FROM `".AUTH_DATABASE."`.`user_departments` AS a
				JOIN `".AUTH_DATABASE."`.`user_access` AS b
				ON a.`user_id` = b.`user_id`
				JOIN `".AUTH_DATABASE."`.`departments` AS c
				ON a.`dep_id` = c.`department_id`" .
				($join_on_organisation ? "AND b.`organisation_id` = c.`organisation_id`" : "") . "
				WHERE a.`user_id` = ?
				GROUP BY c.`department_id`";

	$results = $db->GetAll($query, array($user_id));

	return $results;
}

/**
 * This function gets all of the departments in the user_departments table
 * @param string $user_id
 * @return array $results
 */
function get_distinct_user_departments() {
	global $db;

	$query = "	SELECT DISTINCT b.`department_title`, b.`department_id`
				FROM `".AUTH_DATABASE."`.`user_departments` AS a
				JOIN `".AUTH_DATABASE."`.`departments` AS b
				ON a.`dep_id` = b.`department_id`
				WHERE (b.`department_active` = '1' OR b.`department_active` = '3') AND b.`parent_id`='0'
				ORDER BY b.`department_title`";

	$results = $db->GetAll($query);

	return $results;
}

/**
 * This function gets all of the users in a specific department
 * @param string $dep_id
 * @return array $results
 */
function get_users_in_department($dep_id) {
	global $db;

	$query = "	SELECT `user_id`
				FROM `".AUTH_DATABASE."`.`user_departments`
				WHERE `dep_id` IN(".$dep_id.")";

	$results = $db->GetAll($query);

	return $results;
}

/**
 * This function determines if a user is a department head
 * @param int $user_id
 * @return int $department_id, bool returns false otherwise
 */
function is_department_head($user_id) {
	global $db;

	$query = "	SELECT `department_id`
				FROM `".AUTH_DATABASE."`.`department_heads`
				WHERE `user_id`=".$db->qstr($user_id);

	if($result = $db->GetRow($query)) {
		return $result["department_id"];
	} else {
		return false;
	}
}

/**
 * This function determines if a user is a dean
 * @param int $user_id
 * @return bool true, bool returns false otherwise
 */
function is_dean($user_id) {
	global $db;

	$query = "	SELECT `user_id`
				FROM `".AUTH_DATABASE."`.`deans`
				WHERE `user_id`=".$db->qstr($user_id);

	if($result = $db->GetRow($query)) {
		return true;
	} else {
		return false;
	}
}

/**
 * This function generates a 2 dimensional array of the competencies
 * and the courses which they are associated with, used for building
 * a table to display the aforementioned matrix.
 *
 * @return array $obectives
 */
function objectives_build_course_competencies_array($objective_id = NULL) {
	global $db, $translate, $ENTRADA_USER;
	$courses_array = array("courses" => array(), "competencies" => array());

	$query = "	SELECT a.*, b.`curriculum_type_name` FROM `courses` AS a
				LEFT JOIN `curriculum_lu_types` AS b
				ON a.`curriculum_type_id` = b.`curriculum_type_id`
				WHERE (
					a.`course_id` IN (
						SELECT DISTINCT(`course_id`) FROM `course_objectives`
						WHERE `objective_type` = 'course'
                        AND `active` = '1'
					)
					OR b.`curriculum_type_active` = '1'
				)
				AND a.`course_active` = 1
				AND a.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
				ORDER BY a.`curriculum_type_id` ASC, a.`course_code` ASC";
	$courses = $db->GetAll($query);
	if ($courses) {
		$last_term_name = "";
		$term_course_id = 0;
		$count = 0;
		foreach ($courses as $course) {
			$courses_array["courses"][$course["course_id"]]["competencies"] = array();
			$courses_array["competencies"] = array();
			$courses_array["courses"][$course["course_id"]]["course_name"] = $course["course_name"];
			$courses_array["courses"][$course["course_id"]]["term_name"] = (isset($course["curriculum_type_name"]) && $course["curriculum_type_name"] ? $course["curriculum_type_name"] : "Other courses");
		}
		$reorder_courses = $courses_array["courses"];
		$courses_array["courses"] = array();
		foreach ($reorder_courses as $course_id => $course) {
			if (isset($course["term_name"]) && $course["term_name"] != "Other courses") {
				$courses_array["courses"][$course_id] = $course;
			}
		}
		foreach ($reorder_courses as $course_id => $course) {
			if (!isset($course["term_name"]) || $course["term_name"] == "Other courses") {
				$courses_array["courses"][$course_id] = $course;
			}
		}

		foreach ($courses_array["courses"] as $course_id => &$course) {
			$course["new_term"] = ((isset($last_term_name) && $last_term_name && $last_term_name != $course["term_name"]) ? true : false);
			if ($last_term_name != $course["term_name"]) {
				$last_term_name = (isset($course["term_name"]) && $course["term_name"] ? $course["term_name"] : "Other courses");
				if ($term_course_id) {
					$courses_array["courses"][$term_course_id]["total_in_term"] = $count;
				}
				$term_course_id = $course_id;
				$count = 1;
			} else {
				$count++;
			}
		}
		if ($term_course_id) {
			$courses_array["courses"][$term_course_id]["total_in_term"] = $count;
		}

        if (is_null($objective_id)) { 
            $objective_name = $translate->_("events_filter_controls");
            $objective_name = $objective_name["co"]["global_lu_objectives_name"];
            $where = "WHERE a.`objective_name` LIKE ".$db->qstr($objective_name)."";
        } else {
            $objective_id = (int) $objective_id;
            $where = "WHERE a.`objective_id` LIKE ".$db->qstr($objective_id)."";
        }
        
        $query = "	SELECT a.`objective_id` FROM `global_lu_objectives` AS a
					LEFT JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`"
					.$where."
					AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
		$parent_obj = $db->GetOne($query);

		$query = "	SELECT a.* FROM `global_lu_objectives` AS a
					JOIN `objective_organisation` AS b
					ON a.`objective_id` = b.`objective_id`
					WHERE a.`objective_parent` IN (
						SELECT `objective_id` FROM `global_lu_objectives`
						WHERE `objective_parent` = ".(isset($parent_obj)?$db->qstr($parent_obj):$db->qstr(CURRICULAR_OBJECTIVES_PARENT_ID))."
					)
					AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
		$competencies = $db->GetAll($query);
		if ($competencies && count($competencies)) {
			foreach ($competencies as $competency) {
				$courses_array["competencies"][$competency["objective_id"]] = $competency["objective_name"];
				$objective_ids_string = objectives_build_objective_descendants_id_string($competency["objective_id"], $db->qstr($competency["objective_id"]));
				if ($objective_ids_string) {
					foreach ($courses_array["courses"] as $course_id => &$course) {
						$query = "	SELECT MIN(`importance`) as `importance` FROM `course_objectives`
									WHERE `objective_type` = 'course'
									AND `course_id` = ".$db->qstr($course_id)."
									AND `objective_id` IN (".$objective_ids_string.")
                                    AND `active` = '1'";
						$found = $db->GetRow($query);
						if ($found) {
							$course["competencies"][$competency["objective_id"]] = $found["importance"];
						} else {
							$course["competencies"][$competency["objective_id"]] = false;
						}
					}
				}
			}
		}
	}
	return $courses_array;
}

/**
 * This function returns a string containing all of the objectives which
 * are descendants of the objective_id received.
 *
 * @param $objective_id
 * @param $objective_ids_string
 * @return $objective_ids_string
 */
function objectives_build_objective_descendants_id_string($objective_id = 0, $objective_ids_string = "") {
	global $db, $ENTRADA_USER;
	$query = "	SELECT a.`objective_id` FROM `global_lu_objectives` AS a
				JOIN `objective_organisation` AS b
				ON a.`objective_id` = b.`objective_id`
				WHERE a.`objective_parent` = ".$db->qstr($objective_id)."
				AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
	$objective_ids = $db->GetAll($query);
	if ($objective_ids) {
		foreach ($objective_ids as $objective_id) {
			if ($objective_ids_string) {
				$objective_ids_string .= ", ".$db->qstr($objective_id["objective_id"]);
			} else {
				$objective_ids_string = $db->qstr($objective_id["objective_id"]);
			}
			$objective_ids_string = objectives_build_objective_descendants_id_string($objective_id["objective_id"], $objective_ids_string);
		}
	}
	return $objective_ids_string;
}

/**
 * This function returns a string containing all of the objectives which
 * are attached to the selected course.
 *
 * @param $objective_id
 * @param $objective_ids_string
 * @return $objective_ids_string
 */
function objectives_build_course_objectives_id_string($course_id = 0) {
	global $db;
	$query = "	SELECT `objective_id` FROM `course_objectives`
				WHERE `course_id` = ".$db->qstr($course_id)."
                AND `active` = '1'";
	$objective_ids = $db->GetAll($query);
	if ($objective_ids) {
		$objective_ids_string = false;
		foreach ($objective_ids as $objective_id) {
			if ($objective_ids_string) {
				$objective_ids_string .= ", ".$db->qstr($objective_id["objective_id"]);
			} else {
				$objective_ids_string = $db->qstr($objective_id["objective_id"]);
			}
		}
		return $objective_ids_string;
	}
	return false;
}

/**
 * This function returns a string containing all of the courses which
 * are attached to the selected competency.
 *
 * @param $objective_id
 * @param $objective_ids_string
 * @return $objective_ids_string
 */
function objectives_competency_courses($competency_id = 0) {
	global $db, $ENTRADA_USER;
	$query = "	SELECT a.*, MIN(b.`importance`) AS `importance`
				FROM `courses` AS a
				JOIN `course_objectives` AS b
				ON a.`course_id` = b.`course_id`
				AND `objective_id` IN (".objectives_build_objective_descendants_id_string($competency_id).")
				AND a.`course_active` = 1
				AND a.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                AND b.`active` = '1'
				GROUP BY a.`course_id`";
	$courses = $db->GetAll($query);
	if ($courses) {
		$courses_array = false;
		foreach ($courses as $course) {
			$courses_array[$course["course_id"]] = $course;
		}
	}
	return $courses_array;
}

/**
 * @author http://roshanbh.com.np/2008/05/date-format-validation-php.html
 * @param string $date
 * @return bool
 */
function checkDateFormat($date) {
  //match the format of the date
  if (preg_match ("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/", $date, $parts))
  {
    //check whether the date is valid of not
        return(checkdate($parts[2],$parts[3],$parts[1]));
  }
  else
    return false;
}

/**
 * Easier method for writing a file.
 * @param string $filename
 * @param string $contents
 * @return bool Returns false on error; true otherwise.
 */
function writeFile($filename, $contents) {
	if (!($res = fopen($filename, "w"))) {
		return false;
	}
	if (!fwrite($res,$contents)) {
		return false;
	}
	if(!fclose($res)) {
		return false;
	}
	return true;
}


class Latin1UTF8 {

    private $latin1_to_utf8;
    private $utf8_to_latin1;
    public function __construct() {
        for($i=32; $i<=255; $i++) {
            $this->latin1_to_utf8[chr($i)] = utf8_encode(chr($i));
            $this->utf8_to_latin1[utf8_encode(chr($i))] = chr($i);
        }
    }

    public function mixed_to_latin1($text) {
        foreach( $this->utf8_to_latin1 as $key => $val ) {
            $text = str_replace($key, $val, $text);
        }
        return $text;
    }

    public function mixed_to_utf8($text) {
        return utf8_encode($this->mixed_to_latin1($text));
    }
}

/**
 * Generates a PDF file from the string of html provided. If a filename is supplied, it will be written to the file; otherwise it will be returned from the function
 * @param unknown_type $html
 * @param unknown_type $output_filename
 */
function generatePDF($html,$output_filename=null, $charset=DEFAULT_CHARSET) {
	global $APPLICATION_PATH;

	$cv = new Latin1UTF8();
	$html = $cv->mixed_to_latin1($html);

	//and just in case there's still anything left...
	$html = preg_replace('/[^(\x20-\x7F)]*/','', $html);
	@set_time_limit(0);
	if((is_array($APPLICATION_PATH)) && (isset($APPLICATION_PATH["htmldoc"])) && (@is_executable($APPLICATION_PATH["htmldoc"]))) {

		//This used to have every option separated by a backslash and newline. In testing it was discovered that there was a magical limit of 4 backslashes -- beyond which it would barf.
		$exec_command = $APPLICATION_PATH["htmldoc"]." --format pdf14 --charset ".$charset." --size Letter --pagemode document --no-duplex --encryption --owner-password ".PDF_PASSWORD." --compression=6 --permissions print,modify --header ... --footer ... --headfootsize 0 --browserwidth 800 --top 1cm --bottom 1cm --left 2cm --right 2cm --embedfonts --bodyfont Times --headfootsize 8 --headfootfont Times --headingfont Times --firstpage p1 --quiet --book --color --no-toc --no-title --no-links --textfont Times - ";

		if ($output_filename) {
			@exec($exec_command);
			@exec("chmod 644 ".$output_filename);
		} else {
			/**
			 * This section needs a little explanation.
			 *
			 * exec and shell_exec were not used because they cannot receive standard input.
			 * proc_open allows the specification of pipes (or files) for standard input/output/error
			 * hence the descriptorsepc array specifiying pipes for all three
			 * and writing to pipe[0] for standard input
			 * and reading the stream from pipe[1] for standard output.
			 */

			$descriptorspec = array(
			   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			   2 => array("pipe", "w")   // stderr is a pipe that the child will write to
			);

			$proc = proc_open($exec_command, $descriptorspec, $pipes);

			fwrite($pipes[0], $html);
			fclose($pipes[0]);

			$pdf_string = stream_get_contents($pipes[1]);
			fclose($pipes[1]);

			fclose($pipes[2]); //just close we're not interested in the error info

			$return_val = proc_close($proc);

			return $pdf_string;
		}
	}
}

/**
 * Function used by public events and admin events index to output the HTML for the calendar controls.
 */
function objectives_output_calendar_controls() {
	global $display_duration, $page_current, $total_pages, $OBJECTIVE_ID, $COURSE_ID;
	if (date("n") >= 9) {
		$base_year = date("Y");
	} else {
		$base_year = date("Y") - 1;
	}
	?>
	<table style="width: 100%; margin: 10px 0px 10px 0px" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td style="width: 53%; vertical-align: top; text-align: left">
				<table style="width: 415px; height: 23px" cellspacing="0" cellpadding="0" border="0" summary="Display Duration Type">
					<tr>
						<td style="width: 22px; height: 23px"><a href="<?php echo ENTRADA_URL."/courses/objectives?".replace_query(array("dstamp" => (strtotime("-1 year", $display_duration["start"])))); ?>" title="Previous Academic Year"><img src="<?php echo ENTRADA_URL; ?>/images/cal-back.gif" border="0" width="22" height="23" alt="Previous Academic Year" title="Previous Academic Year" /></a></td>
						<td style="width: 77px; height: 23px"><?php echo ((date("Y", $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]) == ($base_year - 3)) ? "<img src=\"".ENTRADA_URL."/images/cal-academic-year.gif\" width=\"78\" height=\"23\" border=\"0\" alt=\"".($base_year - 3)."/".($base_year - 2)."\" title=\"".($base_year - 3)."/".($base_year - 2)."\" /><span style=\"font-weight: bold; position: absolute; float: left; margin-top: 4px; margin-left: -70px; font-size: 11px; color: #000000;\">".($base_year - 3)."/".($base_year - 2)."</span>" : "<a href=\"".ENTRADA_URL."/courses/objectives?".replace_query(array("dstamp" => strtotime("08:00:00 September 1st, ".($base_year - 3))))."\"><img src=\"".ENTRADA_URL."/images/cal-academic-year.gif\" width=\"78\" height=\"23\" border=\"0\" alt=\"".($base_year - 3)."/".($base_year - 2)."\" title=\"".($base_year - 3)."/".($base_year - 2)."\" /><span style=\"position: absolute; margin-top: 4px; margin-left: -70px; font-size: 11px; color: #000000;\">".($base_year - 3)."/".($base_year - 2)."</span></a>"); ?></td>
						<td style="width: 77px; height: 23px"><?php echo ((date("Y", $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]) == ($base_year - 2)) ? "<img src=\"".ENTRADA_URL."/images/cal-academic-year.gif\" width=\"78\" height=\"23\" border=\"0\" alt=\"".($base_year - 2)."/".($base_year - 1)."\" title=\"".($base_year - 2)."/".($base_year - 1)."\" /><span style=\"font-weight: bold; position: absolute; float: left; margin-top: 4px; margin-left: -70px; font-size: 11px; color: #000000;\">".($base_year - 2)."/".($base_year - 1)."</span>" : "<a href=\"".ENTRADA_URL."/courses/objectives?".replace_query(array("dstamp" => strtotime("08:00:00 September 1st, ".($base_year - 2))))."\"><img src=\"".ENTRADA_URL."/images/cal-academic-year.gif\" width=\"78\" height=\"23\" border=\"0\" alt=\"".($base_year - 2)."/".($base_year - 1)."\" title=\"".($base_year - 2)."/".($base_year - 1)."\" /><span style=\"position: absolute; margin-top: 4px; margin-left: -70px; font-size: 11px; color: #000000;\">".($base_year - 2)."/".($base_year - 1)."</span></a>"); ?></td>
						<td style="width: 77px; height: 23px"><?php echo ((date("Y", $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]) == ($base_year - 1)) ? "<img src=\"".ENTRADA_URL."/images/cal-academic-year.gif\" width=\"78\" height=\"23\" border=\"0\" alt=\"".($base_year - 1)."/".($base_year)."\" title=\"".($base_year - 1)."/".($base_year)."\" /><span style=\"font-weight: bold; position: absolute; float: left; margin-top: 4px; margin-left: -70px; font-size: 11px; color: #000000;\">".($base_year - 1)."/".($base_year)."</span>" : "<a href=\"".ENTRADA_URL."/courses/objectives?".replace_query(array("dstamp" => strtotime("08:00:00 September 1st, ".($base_year - 1))))."\"><img src=\"".ENTRADA_URL."/images/cal-academic-year.gif\" width=\"78\" height=\"23\" border=\"0\" alt=\"".($base_year - 1)."/".($base_year)."\" title=\"".($base_year - 1)."/".($base_year)."\" /><span style=\"position: absolute; margin-top: 4px; margin-left: -70px; font-size: 11px; color: #000000;\">".($base_year - 1)."/".($base_year)."</span></a>"); ?></td>
						<td style="width: 77px; height: 23px"><?php echo ((date("Y", $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]) == ($base_year)) ? "<img src=\"".ENTRADA_URL."/images/cal-academic-year.gif\" width=\"78\" height=\"23\" border=\"0\" alt=\"".($base_year)."/".($base_year + 1)."\" title=\"".($base_year)."/".($base_year + 1)."\" /><span style=\"font-weight: bold; position: absolute; float: left; margin-top: 4px; margin-left: -70px; font-size: 11px; color: #000000;\">".($base_year)."/".($base_year + 1)."</span>" : "<a href=\"".ENTRADA_URL."/courses/objectives?".replace_query(array("dstamp" => strtotime("08:00:00 September 1st, ".($base_year))))."\"><img src=\"".ENTRADA_URL."/images/cal-academic-year.gif\" width=\"78\" height=\"23\" border=\"0\" alt=\"".($base_year)."/".($base_year + 1)."\" title=\"".($base_year)."/".($base_year + 1)."\" /><span style=\"position: absolute; margin-top: 4px; margin-left: -70px; font-size: 11px; color: #000000;\">".($base_year)."/".($base_year + 1)."</span></a>"); ?></td>
						<td style="width: 77px; height: 23px; border-left: 1px #9D9D9D solid"><a href="<?php echo ENTRADA_URL."/courses/objectives?".replace_query(array("dstamp" => (strtotime("+1 year", $display_duration["start"])))); ?>" title="Following Academic Year"><img src="<?php echo ENTRADA_URL; ?>/images/cal-next.gif" border="0" width="22" height="23" alt="Following Academic Year" title="Following Academic Year" /></a></td>
						<td style="width: 33px; height: 23px; text-align: right"><a href="<?php echo ENTRADA_URL.$module_type; ?>/events?<?php echo replace_query(array("dstamp" => time())); ?>"><img src="<?php echo ENTRADA_URL; ?>/images/cal-home.gif" width="23" height="23" alt="Reset to display current Academic Year." title="Reset to display current Academic Year." border="0" /></a></td>
					</tr>
				</table>
			</td>
			<td style="width: 47%; vertical-align: top; text-align: right">
				<?php
				if ($total_pages > 1) {
					echo "<form action=\"".ENTRADA_URL."/courses/objectives\" method=\"get\" id=\"pageSelector\">\n";
					echo "<input type=\"hidden\" name=\"oid\" value=\"".$OBJECTIVE_ID."\" />\n";
					echo "<input type=\"hidden\" name=\"cid\" value=\"".$COURSE_ID."\" />\n";
					echo "<div style=\"white-space: nowrap\">\n";
					echo "<span style=\"width: 20px; vertical-align: middle; margin-right: 3px; text-align: left\">\n";
					if (($page_current - 1)) {
						echo "<a href=\"".ENTRADA_URL."/courses/objectives?".replace_query(array("pv" => ($page_current - 1)))."\"><img src=\"".ENTRADA_URL."/images/record-previous-on.gif\" border=\"0\" width=\"11\" height=\"11\" alt=\"Back to page ".($page_current - 1).".\" title=\"Back to page ".($page_current - 1).".\" style=\"vertical-align: middle\" /></a>\n";
					} else {
						echo "<img src=\"".ENTRADA_URL."/images/record-previous-off.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />";
					}
					echo "</span>";
					echo "<span style=\"vertical-align: middle\">\n";
					echo "<select name=\"pv\" onchange=\"$('pageSelector').submit();\"".(($total_pages <= 1) ? " disabled=\"disabled\"" : "").">\n";
					for ($i = 1; $i <= $total_pages; $i++) {
						echo "<option value=\"".$i."\"".(($i == $page_current) ? " selected=\"selected\"" : "").">".(($i == $page_current) ? " Viewing" : "Jump To")." Page ".$i."</option>\n";
					}
					echo "</select>\n";
					echo "</span>\n";
					echo "<span style=\"width: 20px; vertical-align: middle; margin-left: 3px; text-align: right\">\n";
					if ($page_current < $total_pages) {
						echo "<a href=\"".ENTRADA_URL."/courses/objectives?".replace_query(array("pv" => ($page_current + 1)))."\"><img src=\"".ENTRADA_URL."/images/record-next-on.gif\" border=\"0\" width=\"11\" height=\"11\" alt=\"Forward to page ".($page_current + 1).".\" title=\"Forward to page ".($page_current + 1).".\" style=\"vertical-align: middle\" /></a>";
					} else {
						echo "<img src=\"".ENTRADA_URL."/images/record-next-off.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />";
					}
					echo "</span>\n";
					echo "</div>\n";
					echo "</form>\n";
				}
				?>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * This function gets clinical flag from the user_data table
 *
 * @param int $proxy_id
 * @return array result["clinical"]
 */
function getClinicalFromProxy($proxy_id) {
    global $db;

    $query = "SELECT `clinical`
	FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=". $db->qstr($proxy_id);

    $result = $db->GetRow($query);

	return $result["clinical"];
}

/**
 * This function determines whether to allow users to edit the Year Reported value within the Annual
 * Reporting module. If the uer is attemtping to edit a previous year then the year reported field cannot
 * be changed. If they are editing a current year and changing it to a previous year then they should
 * be allowed to change the value after a submit containing an error (uses $allowEdit for this).
 *
 * @param int $year_reported, $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, $allowEdit
 * @return displays appropriate HTML
 */
function displayARYearReported($year_reported, $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, $allowEdit = false) {
	if(isset($year_reported) && $year_reported < $AR_CUR_YEAR && !$allowEdit) {
		echo "<td>".$year_reported." - <span class=\"content-small\"><strong>Note: </strong>Previous Reporting Years cannot be changed.</span>
		<input type=\"hidden\" name=\"year_reported\" value=\"".$year_reported."\" />";
	} else {
	?>
	<td><select name="year_reported" id="year_reported" style="vertical-align: middle">
	<?php
		for($i=$AR_PAST_YEARS; $i<=$AR_FUTURE_YEARS; $i++) {
			if(isset($year_reported) && $year_reported != '') {
				$defaultYear = $year_reported;
			} else if(isset($year_reported) && $year_reported != '') {
				$defaultYear = $year_reported;
			} else  {
				$defaultYear = $AR_CUR_YEAR;
			}
			echo "<option value=\"".$i."\"".(($defaultYear == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
		}
		?>
		</select>
		<?php
		}

	?>
	</td>
	<?php
}

/**
 * Adds an error message
 * @param string $message
 */
function add_error($message) {
	add_message("error",$message);
}

/**
 * Adds a notice message
 * @param string $message
 */
function add_notice($message) {
	add_message("notice",$message);
}

/**
 * Adds a success message
 * @param string $message
 */
function add_success($message) {
	add_message("success",$message);
}

/**
 * Adds a generic message
 * @param string $message
 */
function add_generic($message) {
	add_message("generic",$message);
}

/**
 * Adds the supplied message to the type-specified collection of messages
 * @param string $type At this time, one of "success","error",or "notice"
 * @param string $message
 */
function add_message($type,$message) {
	$type = strtoupper($type);
	$strings = $type."STR";
	global ${$type}, ${$strings};
	${$type}++;
	${$strings}[] = $message;
}

/**
 * Returns true if there are any messages of the specified type
 * @param string $type At this time, one of "success","error",or "notice"
 * @return bool
 */
function has_message($type) {
	switch ($type) {
		case "success":
		case "error":
		case "notice":
			$type = strtoupper($type);
			$strings = $type."STR";
			global ${$type}, ${$strings};
			return (${$type} || ${$strings});
	}
}

/**
 * Returns true if there are any error messages
 * @return bool
 */
function has_error() {
	return has_message("error");
}

/**
 * Returns true if there are any notice messages
 * @return bool
 */
function has_notice() {
	return has_message("notice");
}

/**
 * Returns true if there are any success messages
 * @return bool
 */
function has_success() {
	return has_message("success");
}

/**
 * Clears error messages
 */
function clear_error(){
	clear_message("error");
}

/**
 * Clears success messages
 */
function clear_success() {
	clear_message("success");
}

/**
 * Clears notice messages
 */
function clear_notice() {
	clear_message("notice");
}

/**
 * Empties the the specified message type
 * @param string $type At this time, one of "success","error",or "notice"
 */
function clear_message($type) {
	switch ($type) {
		case "success":
		case "error":
		case "notice":
			$type = strtoupper($type);
			$strings = $type."STR";
			global ${$type}, ${$strings};
			${$type} = 0;
			${$strings} = array();
	}
}

/**
 * This function gets the min and max years that are in the Annual Reporting Module for report generation purposes
 *
 * @param null
 * @return array(start, end)
 */
function getMinMaxARYears() {
    global $db;

    $query = "SELECT MIN(year_reported) AS `start_year`, MAX(year_reported) AS `end_year`
	FROM `ar_profile`";

    $result = $db->GetRow($query);

	return $result;
}

function get_redirect_message($url, $page_title, $message) {
	return "<p>".$message."</p><p>You will now be redirected to the <strong>".$page_title."</strong>; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\">click here</a> to continue.</p>";
}

function success_redirect($url, $page_title, $success_message) {
	add_success(get_redirect_message($url, $page_title, $success_message));
	status_redirect($url);
}

function error_redirect($url, $page_title, $error_message) {
	add_error(get_redirect_message($url, $page_title, $error_message));
	status_redirect($url);
}

function notice_redirect($url, $page_title, $notice_message) {
	add_notice(get_redirect_message($url, $page_title, $notice_message));
	status_redirect($url);
}

function status_redirect($url) {
	header( "refresh:5;url=".$url );
	display_status_messages();
}

function fetch_evaluation_target_title($evaluation_target = array(), $number_of_targets = 1, $target_shortname) {
	global $db;
	if ($number_of_targets == 1) {
		if (!empty($evaluation_target)) {
			switch ($target_shortname) {
				case "course" :
					$query = "SELECT `course_code` FROM `courses` WHERE `course_id` = ".$db->qstr($evaluation_target);
					if ($course_code = $db->GetOne($query)) {
						return $course_code;
					}
					break;
				case "preceptor" :
				case "rotation_core" :
				case "rotation_elective" :
					$query = "SELECT `event_title` FROM `".CLERKSHIP_DATABASE."`.`events` WHERE `event_id` = ".$db->qstr($evaluation_target);
					if ($event_name = $db->GetOne($query)) {
						return $event_name.($target_shortname == "preceptor" ? " Preceptor" : "");
					}
					break;
				case "self" :
						return "Yourself";
					break;
				case "resident" :
				case "teacher" :
				case "student" :
				case "peer" :
				default :
					$query = "SELECT CONCAT_WS(' ', `firstname`, `lastname`) AS `fullname` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($evaluation_target);
					if ($teacher_name = $db->GetOne($query)) {
						return $teacher_name;
					}
					break;
			}
		}
	} else {
		if (!empty($evaluation_target)) {
			switch ($target_shortname) {
				case "course" :
					return $number_of_targets." Courses";
				break;
				case "resident" :
					return $number_of_targets." Residents";
				break;
				case "student" :
					return $number_of_targets." Students";
				break;
				case "peer" :
					return $number_of_targets." Peers";
				break;
				case "rotation_core" :
				case "rotation_elective" :
					return $number_of_targets." Events";
				break;
				case "preceptor" :
					return $number_of_targets." Events' Preceptors";
				break;
				case "teacher" :
				default :
					return $number_of_targets." Faculty Members";
				break;
			}
		}
	}
	return false;
}

/**
 * This function returns the total number of attempts the user
 * has made on the provided evaluation_id, completed, expired or otherwise.

 * @param int $aquiz_id
 * @return int
 */
function evaluations_fetch_attempts($evaluation_id = 0) {
	global $db, $ENTRADA_USER;

	if ($evaluation_id = (int) $evaluation_id) {
		$query		= "	SELECT COUNT(*) AS `total`
						FROM `evaluation_progress`
						WHERE `evaluation_id` = ".$db->qstr($evaluation_id)."
						AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
						AND `progress_value` <> 'inprogress'";
		$attempts	= $db->GetRow($query);
		if ($attempts) {
			return $attempts["total"];
		}
	}

	return 0;
}

function evaluation_generate_description($min_submittable = 0, $evaluation_questions = 1, $evaluation_attempts = 0, $evaluation_finish = 0) {
	global $db;

	$output	= "This is %s evaluation which is to be completed %s. You will have no time limitation and %s to answer the %s in this evaluation.";

	$string_1 = (((int) $min_submittable) ? "a required" : "an optional");
	$string_2 = ((isset($evaluation_finish) && ($evaluation_finish)) ? "by ".date(DEFAULT_DATETIME_FORMAT, $evaluation_finish) : "when you see fit");
	$string_3 = (((int) $evaluation_attempts) ? $evaluation_attempts." attempt".(($evaluation_attempts != 1) ? "s" : "") : "unlimited attempts");
	$string_4 = $evaluation_questions." question".(($evaluation_questions != 1) ? "s" : "");

	return sprintf($output, $string_1, $string_2, $string_3, $string_4);
}

function gradebook_get_weighted_grades($course_id, $cperiod_id, $proxy_id, $assessment_id = false, $assessment_ids_string = false, $learner = true) {
	global $db;
	$weighted_grade = 0;
	$weighted_total = 0;
	$weighted_percent = 0;
    $query = "	SELECT a.*, b.`handler`
				FROM `assessments` AS a
				LEFT JOIN `assessment_marking_schemes` AS b
				ON b.`id` = a.`marking_scheme_id`
				WHERE a.`course_id` = ".$db->qstr($course_id)."
				".(!isset($learner) || $learner ? "
                AND (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).")
                AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time()).")
                AND a.`show_learner` = '1'" : "")."
                AND a.`active` = '1'
				AND a.`cperiod_id` = ".$db->qstr($cperiod_id).
                ($assessment_id ? " AND a.`assessment_id` = ".$db->qstr($assessment_id) : ($assessment_ids_string ? " AND a.`assessment_id` IN (".$assessment_ids_string.")" : ""));
	$assessments = $db->GetAll($query);
	if($assessments) {
        foreach ($assessments as $assessment) {
            $query = "SELECT a.`value`, b.`grade_weighting` FROM `assessment_grades` AS a
                        LEFT JOIN `assessment_exceptions` AS b
                        ON a.`assessment_id` = b.`assessment_id`
                        AND b.`proxy_id` = a.`proxy_id`
                        WHERE a.`proxy_id` = ".$db->qstr($proxy_id)."
                        AND a.`assessment_id` = ".$db->qstr($assessment["assessment_id"]);
            $grade = $db->GetRow($query);
            if ($grade) {
                if ($grade["grade_weighting"] !== NULL) {
                    $grade_weighting = $grade["grade_weighting"];
                } else {
                    $grade_weighting = $assessment["grade_weighting"];
                }
                if(isset($grade["value"])) {
                    $grade_value = format_retrieved_grade($grade["value"], $assessment);
                    $weighted_total += $grade_weighting;
                    $weighted_grade += (($assessment["handler"] == "Numeric" ? ($grade_value / $assessment["numeric_grade_points_total"]) : (($assessment["handler"] == "Percentage" ? ((float)$grade_value / 100.0) : ($assessment["handler"] == "IncompleteComplete" ? ($grade_value == "C" ? 100 : 0) / 100 : $grade_value))))) * $grade_weighting;
                }
            }
        }
	}
	if ($weighted_grade && $weighted_total) {
		$weighted_percent = number_format(($weighted_grade / $weighted_total) * 100, 2);
	}
	return Array(	"total" => $weighted_total,
					"grade" => $weighted_grade,
					"percent" => $weighted_percent);
}

/**
 * Substitutes variables of the form %VAR NAME% in the string, with variables from the array, keys in the form of var name, Var Name, etc.
 *
 * @param string $str
 * @param array $arr
 * @return string
 */
function substitute_vars($str, array $arr) {
	//first process the keys of arr
	$n_arr = array();
	foreach ($arr as $key=>$value) {
		$n_arr["%".strtoupper($key)."%"] = $value;
	}

	return strtr($str,$n_arr);
}


/**
 * Returns null if the $value is not in the array $arr; return the the $value otherwise. XXX NOTE, a bug prior to php 5.2.6 causes epic php failure if callbacks return false. null returned as workaround
 * @param $value
 * @param $arr
 * @return mixed
 */
function validate_in_array($value, array $arr) {
	if (in_array($value, $arr)){
		return $value;
	} else {
		return;
	}
}

/**
 * Returns an array of valid user ids and null otherwise. XXX NOTE, a bug prior to php 5.2.6 causes epic php failure if callbacks return false. null returned as workaround
 * @param $value
 */
function validate_user_ids($value) {
	global $db;
	$clean = filter_var($value,  FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^\d+$|^(\d+,)+\d+$/')));
	if (false !== $clean) {
		$query = "	SELECT a.id
					FROM `".AUTH_DATABASE."`.`user_data` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
					ON a.`id` = b.`user_id`
					WHERE a.`id` IN ( " . $clean .  ")
					AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
					AND b.`account_active` = 'true'
					AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
					AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
		$results	= $db->GetCol($query);
		if($results && (0 < count($results))) {
			return $results;
		}
	}
}

/**
 * Returns null if $value is not a valid or existent course_id; returns the $value otheriwse. XXX NOTE, a bug prior to php 5.2.6 causes epic php failure if callbacks return false. null returned as workaround
 * @param unknown_type $value
 * @return mixed
 */
function validate_course_id($value) {
	$course_id = filter_var($value, FILTER_VALIDATE_INT, array('min_range' => 1));

	if (false === $course_id || !Course::get($course_id)) {
		return;
	} else {
		return $course_id;
	}
}

/**
 * Returns null if the $value provided is not a valid organisation_id; returns the $value otherwise. XXX NOTE, a bug prior to php 5.2.6 causes epic php failure if callbacks return false. null returned as workaround
 * @param unknown_type $value
 * @return mixed
 */
function validate_organisation_id($value) {
	$organisation_id = filter_var($value, FILTER_VALIDATE_INT, array('min_range' => 1));

	if (false === $organisation_id || !Organisation::get($organisation_id)) {
		return;
	} else {
		return $organisation_id;
	}
}

/**
 * Returns the binary OR of $val_a and $val_b -- convenince function for callbacks
 * @param int $val_a
 * @param int $val_b
 * @return int
 */
function or_bin($val_a, $val_b) {
	return $val_a | $val_b;
}

/**
 * Returns string after passing through the clean_input function for allowed_tags -- convenience method for callbacks.
 * @param string $value
 * @return string
 */
function allowed_tags($value) {
	return clean_input($value, array("allowedtags"));
}

/**
 * This displays a person's name, picture etc. including basic biographical information and assistant info if relevant
 * @param User $user
 */
function display_person(User $user) {
	global $ENTRADA_ACL;
	$photos = $user->getPhotos();
	$user_id = $user->getID();

	$is_administrator = $ENTRADA_ACL->amIallowed('user', 'update');

	$prefix = $user->getPrefix();
	$firstname = $user->getFirstname();
	$lastname = $user->getLastname();
	$fullname = $user->getName("%f %l");

	$departments = $user->getDepartments();

	if (0 < count($departments)) {
		$dep_titles = array();
		foreach ($departments as $department) {
			$dep_titles[] = ucwords($department->getTitle());
		}
		$group_line = implode("<br />", $dep_titles);
	} else {
		$group = $user->getGroup();
		$role = $user->getRole();
		$group_line =  ucwords($group. " > " . (($group == "student") ? "Class of " : "") . $role);
	}

	$privacy_level = $user->getPrivacyLevel();

	$organisation = $user->getOrganisation();
	$org_name = ($organisation) ? $organisation->getTitle() : "" ;

	$email = ((1 < $privacy_level) || $is_administrator) ? $user->getEmail() : "";
	$email_alt = $user->getAlternateEmail();

	if ((2 < $privacy_level) || $is_administrator) {
		$show_address = true;
		$city = $user->getCity();
		$province = $user->getProvince();
		$prov_name = $province->getName();
		$country = $user->getCountry();
		$country_name = $country->getName();
		$phone = $user->getTelephone();
		$fax = $user->getFax();
		$address = $user->getAddress();
		$postcode = $user->getPostalCode();
		$office_hours = $user->getOfficeHours();
	}

	$assistants = $user->getAssistants();
	//there are 4 photo cases (at time of writing): no photos, official only, uploaded only, or both.
	//privacy options also need to be considered here.
	ob_start();
	?>
	<div id="result-<?php echo $user_id; ?>" class="person-result">
		<div id="img-holder-<?php echo $user_id; ?>" class="img-holder">
		<?php
		$num_photos = count($photos);
		if (0===$num_photos) {
			echo display_photo_placeholder();
		} else {
			foreach($photos as $photo) {
				echo display_photo($photo);
			}
			if (2 <= $num_photos) {
				$label = 0;
				foreach($photos as $photo) {
					echo display_photo_link($photo, ++$label);
				}
			}
			echo display_zoom_controls($user_id);
		}
		?>
		</div>
		<div class="person-data">
			<div class="basic">
				<span class="person-name"><?php echo html_encode($fullname); ?></span>
				<span class="person-group"><?php echo html_encode($group_line); ?></span>
				<span class="person-organisation"><?php echo html_encode($org_name); ?></span>
				<div class="email-container">
				<?php
					if ($email) {
						echo display_person_email($email);
						if($email_alt) {
							echo display_person_email($email_alt);
						}
					}
				?>
				</div>
			</div>
			<div class="address">
			<?php
				if ($show_address) {
					if ($phone) {
						?>
						<div>
							<span class="address-label">Telephone:</span>
							<span class="address-value"><?php echo html_encode($phone); ?></span>
						</div>
						<?php
					}
					if ($fax) {
						?>
						<div>
							<span class="address-label">Fax:</span>
							<span class="address-value"><?php echo html_encode($fax); ?></span>
						</div>
						<?php
					}
					if ($address && $city) {
						?>
						<div>
							<span class="address-label">Address:</span><br />
							<span class="address-value">
							<?php
								echo html_encode($address)."<br />".html_encode($city);
								if ($prov_name) echo ", ".html_encode($prov_name);
								echo "<br />";
								echo html_encode($country_name);
								if ($postcode) echo ", ".html_encode($postcode);

							?>
							</span>
						</div>
						<?php
					}
					if ($office_hours) {
						?>
						<div>
							<span class="address-label">Office Hours:</span>
							<span class="address-value"><?php echo html_encode($office_hours); ?></span>
						</div>
						<?php
					}

				}
			?>
			</div>
			<div class="assistant"><?php if (count($assistants) > 0) { ?>
				<span class="content-small">Administrative Assistants:</span>
				<ul class="assistant-list">
					<?php
					foreach ($assistants as $assistant) {
						echo "<li>".display_person_email($assistant->getEmail(),$assistant->getName("%f %l"))."</li>";

					}
					?>
				</ul><?php } ?>
			</div>
		</div>
		<div></div>
		<div class="clearfix">&nbsp;</div>
	</div>

	<?php
	return ob_get_clean();
}

function display_person_email($email,$label=null) {
	if (!trim($label)) $label = $email;
	return "<a class='person-email' href='mailto:".html_encode($email)."'>".html_encode($label)."</a>";
}

function display_photo(UserPhoto $photo) {
	$user_id = $photo->getUserID();
	$user = User::fetchRowByID($user_id);
	$titled_name = implode(" ", array($user->getPrefix(),$user->getFirstname(), $user->getLastname()));
	$name = implode(" ", array($user->getFirstname(), $user->getLastname()));
	$type = $photo->getPhotoType();
	ob_start();
	?>
	<img id="<?php echo $type; ?>_photo_<?php echo $user_id; ?>" class="<?php echo $type; ?>" src="<?php echo $photo->getFilename(); ?>" width="72" height="100" alt="<?php echo html_encode($titled_name); ?>" title="<?php echo html_encode($titled_name); ?>" />
	<?php
	return ob_get_clean();
}

function display_photo_link(UserPhoto $photo, $label) {
	$user_id = $photo->getUserID();
	$type = $photo->getPhotoType();
	ob_start();
	?>
		<a id="<?php echo $type; ?>_link_<?php echo $user_id; ?>" class="img-selector" onclick="<?php echo ($type == UserPhoto::OFFICIAL) ? "show" : "hide"; ?>Official($('official_photo_<?php echo $user_id; ?>'), $('official_link_<?php echo $user_id; ?>'), $('upload_link_<?php echo $user_id; ?>'));" href="javascript:void(0);"><?php echo $label; ?></a>
	<?php
	return ob_get_clean();
}

function display_photo_placeholder() {
	return "<img src=\"".ENTRADA_URL."/images/headshot-male.gif\" width=\"72\" height=\"100\" alt=\"No Photo Available\" title=\"No Photo Available\" />";
}

function display_zoom_controls($user_id) {
	ob_start();
	$params = '$("official_photo_'.$user_id.'"), $("upload_photo_'.$user_id.'"), $("official_link_'.$user_id.'"), $("upload_link_'.$user_id.'"), $("zoomout_photo_'.$user_id.'")';
	?>
	<a id="zoomin_photo_<?php echo $user_id; ?>" class="zoomin" onclick="growPic(<?php echo $params; ?>);">+</a>
	<a id="zoomout_photo_<?php echo $user_id; ?>" class="zoomout" onclick="shrinkPic(<?php echo $params; ?>);"></a>
	<?php
	return ob_get_clean();
}

function generateMasks($organisation, $group, $role, $user) {
	$masks = array();
	$masks["organisation"] = $organisation;
	$masks["organisation:group"] = $organisation.":".$group;
	$masks["organisation:group:role"] = $organisation.":".$group.":".$role;
	$masks["group"] = $group;
	$masks["group:role"] = $group.":".$role;
	$masks["user"] = $user;
	$masks["organisation:user"] = $organisation .":".$user;

	//we want to filter out any entries that are empty, begin or terminate with a colon, or have two colons together
	$pattern = "/^$|^:|:$|::/";

	$masks = preg_grep($pattern, $masks, PREG_GREP_INVERT);
	return $masks;
}

function generateMaskConditions($organisation, $group, $role, $user) {
	global $db;
	$masks = generateMasks($organisation, $group, $role, $user);
	$mask_strs = array();
	foreach($masks as $condition=>$value) {
		$mask_strs[] = "(`entity_type` = ".$db->qstr($condition)." AND `entity_value` = " . $db->qstr($value) ." )\n";
	}
	return implode(" OR ", $mask_strs);
}

function generateAccessConditions($organisation, $group, $role, $proxy_id, $table_prefix = NULL) {
	global $db;
	$masks = array();
	$masks['`organisation_id`'] = $organisation;
	$masks['`group`'] = $group;
	$masks['`role`'] = $role;
	$masks['a.`id`'] = $proxy_id;

	$masks = array_filter($masks);

	$mask_strs = array();

	foreach ($masks as $field=>$condition) {
		$mask_strs[] = (($table_prefix != NULL && !strstr($field, "a.")) ? $table_prefix."." : "").$field."=".$db->qstr($condition);
	}
	if ($mask_strs) {
		return implode(" AND ",$mask_strs);
	}
}

/*
 * This function validates the input variable by first trimming it and then checking to
 * see that it contains integers only and that it is not blank or null.
 *
 * @return the input integer on successful validation, 0 on failed validation.
 */
function validate_integer_field($input){
	$input = trim($input);
	$int_test = preg_match("/[^\d+]/", $input);
	if (!$int_test  && $input != ""  && !is_null($input)) {
		$output = $input;
		return $output;
	} else {
		return 0;
	}
}

/**
 * This function returns the name of the group for the id given
 *
 * @param int $group_id
 * @return string $group_name
 */
function groups_get_name($group_id = 0) {
	global $db;

	$group_id = (int) $group_id;

	if ($group_id) {
		$query = "SELECT `group_name` FROM `groups` WHERE `group_id` = ".$db->qstr($group_id);
		$group_name = $db->GetOne($query);
		if ($group_name) {
			return $group_name;
		}
	}

	return false;
}

/**
 * This function returns each of the cohort group ids associated with the provided proxy id
 *
 * @param int $proxy_id
 * @return array $group
 */
function groups_get_cohorts($proxy_id = 0, $organisation_id = 0, $strict = false) {
    global $db, $ENTRADA_USER;

    $proxy_id = (int) $proxy_id;

    if ($proxy_id) {
        $query = "	SELECT a.*
					FROM `groups` AS a
					JOIN `group_members` AS b
					ON b.`group_id` = a.`group_id`
					JOIN `group_organisations` AS c
					ON c.`group_id` = a.`group_id`
					WHERE b.`proxy_id` = ".$db->qstr($proxy_id)."
					AND b.`member_active` = '1'
					AND a.`group_type` = 'cohort'
					AND c.`organisation_id` = ".$db->qstr((isset($organisation_id) && ((int)$organisation_id) ? ((int) $organisation_id) : $ENTRADA_USER->getActiveOrganisation()));
        $cohorts = $db->CacheGetAll(CACHE_TIMEOUT, $query);
        if ($cohorts) {
            return $cohorts;
        } elseif (!$strict) {
            $query = "	SELECT a.*
						FROM `groups` AS a
						JOIN `group_organisations` AS b
						ON a.`group_id` = b.`group_id`
						WHERE b.`organisation_id` = ".$db->qstr((isset($organisation_id) && ((int)$organisation_id) ? ((int) $organisation_id) : $ENTRADA_USER->getActiveOrganisation()))."
						AND a.`group_type` = 'cohort'
						ORDER BY a.`group_id` DESC";
            $cohorts = $db->CacheGetAll(CACHE_TIMEOUT,$query);
            if ($cohorts) {
                return $cohorts;
            }
        }
    }

    return false;
}

/**
 * This function returns the first cohort record related to the given proxy_id
 *
 * @param int $proxy_id
 * @return array $group
 */
function groups_get_cohort($proxy_id = 0, $organisation_id = 0, $strict = false) {
	global $db, $ENTRADA_USER;

	$proxy_id = (int) $proxy_id;

	if ($proxy_id) {
		$query = "	SELECT a.*
					FROM `groups` AS a
					JOIN `group_members` AS b
					ON b.`group_id` = a.`group_id`
					JOIN `group_organisations` AS c
					ON c.`group_id` = a.`group_id`
					WHERE b.`proxy_id` = ".$db->qstr($proxy_id)."
					AND b.`member_active` = '1'
					AND a.`group_type` = 'cohort'
					AND c.`organisation_id` = ".$db->qstr((isset($organisation_id) && ((int)$organisation_id) ? ((int) $organisation_id) : $ENTRADA_USER->getActiveOrganisation()));
		$cohort = $db->CacheGetRow(CACHE_TIMEOUT, $query);
		if ($cohort) {
			return $cohort;
		} elseif (!$strict) {
			$query = "	SELECT a.*
						FROM `groups` AS a
						JOIN `group_organisations` AS b
						ON a.`group_id` = b.`group_id`
						WHERE b.`organisation_id` = ".$db->qstr((isset($organisation_id) && ((int)$organisation_id) ? ((int) $organisation_id) : $ENTRADA_USER->getActiveOrganisation()))."
						AND a.`group_type` = 'cohort'
						ORDER BY a.`group_id` DESC";
			$cohort = $db->CacheGetRow(CACHE_TIMEOUT,$query);
			if ($cohort) {
				return $cohort;
			}
		}
	}

	return false;
}

/**
 * This function returns the first cohort record related to the given proxy_id
 *
 * @param int $proxy_id
 * @return array $group
 */
function groups_get_enrolled_course_ids($proxy_id = 0, $only_active_enrolment = false, $start_date = 0, $finish_date = 0) {
	global $db, $ENTRADA_USER;

	$proxy_id = (int) $proxy_id;
	$only_active_enrolment = (bool) $only_active_enrolment;
	$start_date = (int) $start_date;
    if (!$start_date) {
        $start_date = time();
    }

	$finish_date = (int) $finish_date;
    if (!$finish_date) {
        $finish_date = time();
    }

	$course_ids = array();

	if ($proxy_id) {
		$query  = "SELECT DISTINCT a.`course_id`
                    FROM `courses` AS a
					LEFT JOIN `course_audience` AS b
					ON a.`course_id` = b.`course_id`
                    LEFT JOIN `curriculum_periods` AS c
                    ON b.`cperiod_id` = c.`cperiod_id`
					WHERE a.`permission` = 'open'
					OR (
						(
							(
                                b.`audience_type` = 'group_id' AND b.`audience_value` IN (
									SELECT a.`group_id`
                                    FROM `groups` AS a
									JOIN `group_members` AS b
									ON b.`group_id` = a.`group_id`
									JOIN `group_organisations` AS c
									ON c.`group_id` = a.`group_id`
									WHERE b.`proxy_id` = ".$db->qstr($proxy_id);
		if ($only_active_enrolment) {
            $query .= "             AND (
                                        (b.`start_date` = '0' AND b.`finish_date` = '0') OR
                                        (b.`start_date` < ".$db->qstr($finish_date)." AND b.`finish_date` = '0') OR
                                        (b.`start_date` = '0' AND b.`finish_date` > ".$db->qstr($start_date).") OR
                                        (".$db->qstr($start_date)." BETWEEN b.`start_date` AND b.`finish_date`) OR
                                        (".$db->qstr($finish_date)." BETWEEN b.`start_date` AND b.`finish_date`) OR
                                        (b.`start_date` BETWEEN ".$db->qstr($start_date)." AND ".$db->qstr($finish_date).") OR
                                        (b.`finish_date` BETWEEN ".$db->qstr($start_date)." AND ".$db->qstr($finish_date).")
                                    )";
        }

        $query .= "                 AND b.`member_active` = '1'
									AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
								)
							)
                            OR (
								b.`audience_type` = 'proxy_id' AND b.`audience_value` = ".$db->qstr($proxy_id)."
							)
						)
						AND b.`audience_active` = '1'";
        if ($only_active_enrolment) {
            $query .= " AND (
                            (".$db->qstr($start_date)." BETWEEN c.`start_date` AND c.`finish_date`) OR
                            (".$db->qstr($finish_date)." BETWEEN c.`start_date` AND c.`finish_date`) OR
                            (c.`start_date` BETWEEN ".$db->qstr($start_date)." AND ".$db->qstr($finish_date).") OR
                            (c.`finish_date` BETWEEN ".$db->qstr($start_date)." AND ".$db->qstr($finish_date).")
                        )";
        }

		$query .= " )";

		$course_list = $db->CacheGetAll(CACHE_TIMEOUT, $query);
		if ($course_list) {
			foreach ($course_list as $course) {
				$course_ids[] = (int) $course["course_id"];
			}
		}
	}

	return $course_ids;
}

/**
 * This function returns the courses that the proxy id is explicitly enrolled in
 *
 * @param int $proxy_id
 * @return array $group
 */
function groups_get_explicitly_enrolled_course_ids($proxy_id = 0, $only_active_groups = false, $organisation_id = 0) {
	global $db, $ENTRADA_USER;

	$proxy_id = (int) $proxy_id;
	$only_active_groups = (bool) $only_active_groups;

	$course_ids = array();

	if (!$organisation_id) {
		$organisation_id = $ENTRADA_USER->getActiveOrganisation();
	}

	if ($proxy_id) {
				$query = "	SELECT a.course_id FROM courses AS a
					LEFT JOIN course_audience AS b
					ON a.course_id = b.course_id
					WHERE a.`organisation_id` = ".$db->qstr($organisation_id)."
					AND (
						(
							(
								audience_type = 'group_id'
								AND audience_value IN(
									SELECT a.group_id FROM `groups` AS a
									JOIN `group_members` AS b
									ON b.`group_id` = a.`group_id`
									JOIN `group_organisations` AS c
									ON c.`group_id` = a.`group_id`
									WHERE b.`proxy_id` = ".$db->qstr($proxy_id)."
                                    AND (a.`expire_date` IS NULL OR a.`expire_date` = '0' OR a.`expire_date` >= UNIX_TIMESTAMP())
									AND (b.`start_date` IS NULL OR b.`start_date` = 0
									OR b.`start_date` <= UNIX_TIMESTAMP())
									AND (b.`finish_date` IS NULL OR b.`finish_date` = 0 OR b.`finish_date` >= UNIX_TIMESTAMP())
									AND b.`member_active` = '1'
									AND c.`organisation_id` = ".$db->qstr($organisation_id)."
								)
							)
							OR (
								audience_type='proxy_id'
								AND audience_value = ".$db->qstr($proxy_id)."
							)
						)
						AND audience_active = '1'
					)";


		$course_list = $db->CacheGetAll(CACHE_TIMEOUT, $query);
		if ($course_list) {
			foreach ($course_list as $course) {
				$course_ids[] = (int) $course["course_id"];
			}
		}
	}

	return $course_ids;
}

/**
 * This function returns the courses that the proxy id is explicitly enrolled in with the dates of enrollment
 *
 * @param int $proxy_id
 * @param bool $only_active_groups
 * @param int $organisation_id
 * @return array $group
 */
function groups_get_explicitly_enrolled_course_ids_and_dates($proxy_id = 0, $only_active_groups = false, $organisation_id = 0) {
    global $db, $ENTRADA_USER;

    $proxy_id = (int) $proxy_id;
    $only_active_groups = (bool) $only_active_groups;

    $course_ids = array();

    if (!$organisation_id) {
        $organisation_id = $ENTRADA_USER->getActiveOrganisation();
    }

    if ($proxy_id) {
        $query = "SELECT a.`course_id`, c.`start_date`, c.`finish_date`
                    FROM `courses` AS a
					LEFT JOIN `course_audience` AS b
					ON a.`course_id` = b.`course_id`
					LEFT JOIN `curriculum_periods` AS c
					ON c.`cperiod_id` = b.`cperiod_id` 
					WHERE a.`organisation_id` = ".$db->qstr($organisation_id)."
					AND (
						(
							(
								audience_type = 'group_id'
								AND audience_value IN(
									SELECT a.group_id FROM `groups` AS a
									JOIN `group_members` AS b
									ON b.`group_id` = a.`group_id`
									JOIN `group_organisations` AS c
									ON c.`group_id` = a.`group_id`
									WHERE b.`proxy_id` = ".$db->qstr($proxy_id)."
                                    AND (a.`expire_date` IS NULL OR a.`expire_date` = 0 OR a.`expire_date` >= UNIX_TIMESTAMP())
									AND (b.`start_date` IS NULL OR b.`start_date` = 0 OR b.`start_date` <= UNIX_TIMESTAMP())
									AND (b.`finish_date` IS NULL OR b.`finish_date` = 0 OR b.`finish_date` >= UNIX_TIMESTAMP())
									AND b.`member_active` = 1
									".($only_active_groups ? "AND a.`group_active` = 1" : "")."
									AND c.`organisation_id` = ".$db->qstr($organisation_id)."
								)
							)
							OR (
								audience_type='proxy_id'
								AND audience_value = ".$db->qstr($proxy_id)."
							)
						)
						AND audience_active = '1'
					)";


        $course_ids = $db->CacheGetAll(CACHE_TIMEOUT, $query);
    }

    return $course_ids;
}

/**
 * This function returns the groups the enrolled in by a given proxy_id
 *
 * @param int $proxy_id
 * @return array $group
 */
function groups_get_enrolled_group_ids($proxy_id = 0, $only_active_groups = false, $organisation_id = 0, $organisation_specific = true) {
	global $db, $ENTRADA_USER;

	$proxy_id = (int) $proxy_id;
	$only_active_groups = (bool) $only_active_groups;
    if (!$organisation_id) {
        $organisation_id = $ENTRADA_USER->getActiveOrganisation();
    }

	$group_ids = array();

	if ($proxy_id) {
		$query = "SELECT a.group_id FROM `groups` AS a
                    JOIN `group_members` AS b
                    ON b.`group_id` = a.`group_id`
                    JOIN `group_organisations` AS c
                    ON c.`group_id` = a.`group_id`
                    WHERE b.`proxy_id` = ".$db->qstr($proxy_id)."
                    AND (b.`start_date` = 0
                    OR b.`start_date` <= UNIX_TIMESTAMP())
                    AND (b.`finish_date` = 0 OR b.`finish_date` >= UNIX_TIMESTAMP())
                    AND b.`member_active` = '1'
                    ".($only_active_groups ? "AND a.`group_active` = 1" : "")."
                    ".($organisation_specific ? "AND c.`organisation_id` = ".$db->qstr($organisation_id) : "");
		$group_list = $db->CacheGetAll(CACHE_TIMEOUT, $query);
		if ($group_list) {
			foreach ($group_list as $group) {
				$group_ids[] = (int) $group["group_id"];
			}
		}
	}

	return $group_ids;
}

/**
 * This function returns the cohort records related to the given organisation_id
 *
 * @param int $organisation_id
 * @return array $groups
 */
function groups_get_all_cohorts($organisation_id = 0, $only_active_groups = false) {
	global $db;

	$organisation_id = (int) $organisation_id;

	if ($organisation_id) {
		$query = "	SELECT a.*
					FROM `groups` AS a
					JOIN `group_organisations` AS b
					ON a.`group_id` = b.`group_id`
					WHERE b.`organisation_id` = ".$db->qstr($organisation_id)."
					".($only_active_groups ? " AND a.`group_active` = '1'" : "")."
					AND a.`group_type` = 'cohort'
					ORDER BY `group_name` DESC";
		$cohorts = $db->GetAll($query);
		if ($cohorts) {
			return $cohorts;
		}
	}

	return false;
}

/**
 * This function returns the course_list records related to the given organisation_id
 *
 * @param int $organisation_id
 * @return array $groups
 */
function groups_get_all_course_lists($organisation_id = 0, $only_active_groups = false) {
	global $db;

	$organisation_id = (int) $organisation_id;

	if ($organisation_id) {
		$query = "	SELECT a.*
					FROM `groups` AS a
					JOIN `group_organisations` AS b
					ON a.`group_id` = b.`group_id`
					WHERE b.`organisation_id` = ".$db->qstr($organisation_id)."
					".($only_active_groups ? " AND a.`group_active` = '1'" : "")."
					AND a.`group_type` = 'course_list'
					ORDER BY `group_name` DESC";
		$course_lists = $db->GetAll($query);
		if ($course_lists) {
			return $course_lists;
		}
	}

	return false;
}

/**
 * This function returns the group records related to the given organisation_id
 *
 * @param int $organisation_id
 * @return array $groups
 */
function groups_get_all_groups($organisation_id = 0, $only_active_groups = false) {
	global $db;

	$organisation_id = (int) $organisation_id;

	if ($organisation_id) {
		$query = "	SELECT a.*
					FROM `groups` AS a
					JOIN `group_organisations` AS b
					ON a.`group_id` = b.`group_id`
					WHERE b.`organisation_id` = ".$db->qstr($organisation_id)."
					".($only_active_groups ? " AND a.`group_active` = '1'" : "")."
					ORDER BY `group_name` DESC";
		$groups = $db->GetAll($query);
		if ($groups) {
			return $groups;
		}
	}

	return false;
}

/**
 * This function returns the cohort records related to the given organisation_id
 *
 * @param int $organisation_id
 * @return array $groups
 */
function groups_get_active_cohorts($organisation_id = 0, $limit = 6) {
	global $db;

	$organisation_id = (int) $organisation_id;
	$limit = (int) $limit;

	if ($organisation_id) {
		$query = "	SELECT a.*
					FROM `groups` AS a
					JOIN `group_organisations` AS b
					ON a.`group_id` = b.`group_id`
					WHERE b.`organisation_id` = ?
					AND a.`group_type` = 'cohort'
					AND a.`group_active` = 1
					ORDER BY a.`group_name` DESC
					LIMIT 0, ?";
		$cohorts = $db->GetAll($query, array($organisation_id, $limit));
		if ($cohorts) {
			return $cohorts;
		}
	}

	return false;
}

/**
 * This function returns the curriculum level related to a course code
 *
 * @param int $organisation_id
 * @return array $groups
 */
function fetch_curriculum_level($course_code) {
	global $db, $ENTRADA_USER;

	$query = "SELECT `curriculum_level` FROM `curriculum_lu_levels`, `courses`, `curriculum_lu_types`
	WHERE `courses`.`course_code` = ".$db->qstr($course_code)."
	AND `courses`.`curriculum_type_id` = `curriculum_lu_types`.`curriculum_type_id`
	AND `curriculum_lu_types`.`curriculum_level_id` = `curriculum_lu_levels`.`curriculum_level_id`";

	$curriculum_level = $db->GetROw($query);

	return $curriculum_level["curriculum_level"];
}

// This function returns a trim and tidylist of words
function filtered_words() {
	global $search, $translate;

	$search = array();
	$filtered_words = $translate->_("evaluation_filtered_words");

	if($filtered_words) {
		$search = explode("; ", $filtered_words);
		if(@is_array($search)) {
			$search = clean_empty_values($search);
			array_walk($search, "prepare_filter_string");
		}
	}

	return array_unique($search);
}

// Cleans empty values from an array.
function clean_empty_values($array = array()) {
	foreach ($array as $index => $value) {
		if (trim($value) == "") {
			unset($array[$index]);
		}
	}
	return $array;
}

// This function is used by the filtered_words function to strim whitespace.
function prepare_filter_string(&$string) {
	if(is_string($string)) {
		$string = "/(".trim(quotemeta($string)).")/ie";	// Trims, cleans and converts filter string to a regex.
	}
	return $string;
}

// Function will return all categories in an array.
function categories_inarray($parent_id, $indent = 0) {
	global $db, $sub_category_ids;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	$query		= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `category_parent` = ".$db->qstr($parent_id)." AND `category_status` <> 'trash' ORDER BY `category_order` ASC";
	$results	= $db->GetAll($query);
	foreach($results as $result) {
		$sub_category_ids[] = $result["category_id"];
		categories_inarray($result["category_id"], $indent + 1);
	}

	return ((@count($sub_category_ids) > 0) ? true : false);
}

/*
 * This function recursively deactivates child categories.
 *
 * @param int $parent_id
 * @param int $i
 * @return boolean
 */
function categories_deactivate_children($parent_id, $level = 0) {
	global $db, $ENTRADA_USER;
	if ($level > 99) {
		exit;
	}

	$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
				WHERE `category_parent` = ".$db->qstr($parent_id)."
				AND `category_status` != 'trash'
				GROUP BY `category_id`";
	$categories = $db->GetAll($query);
	if ($categories) {
		foreach ($categories as $category) {
            $query = "UPDATE `".CLERKSHIP_DATABASE."`.`categories` SET `category_status` = 'trash', `updated_date` = " . $db->qstr(time()) . ", `updated_by` = " . $db->qstr($ENTRADA_USER->getID()) . " WHERE `category_id` = ".$db->qstr($category["category_id"]);
			$level++;
            if ($db->Execute($query)) {
                return categories_deactivate_children($category["category_id"], $level);
            }
		}
	} elseif ($level) {
        return true;
    }
	return false;
}

/**
 * Function will return all categories below the specified category_parent.
 *
 * @param int $identifier
 * @param int $indent
 * @return string
 */
function categories_intable($identifier = 0, $indent = 0, $excluded_categories = false) {
	global $db, $ONLOAD;

	if($indent > 99) {
		die("Preventing infinite loop");
	}

	$selected				= 0;
	$selectable_children	= true;


	$identifier	= (int) $identifier;
	$output		= "";

	if(($identifier)) {
		$query	= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
					WHERE `category_id` = ".$db->qstr((int)$identifier)."
					AND `category_status` != 'trash'
					ORDER BY `category_order` ASC";
	}

	$result	= $db->GetRow($query);
	if($result) {
		$output .= "<tr id=\"content_".$result["category_id"]."\">\n";
		$output .= "	<td>&nbsp;</td>\n";
		$output .= "	<td style=\"padding-left: ".($indent * 25)."px; vertical-align: middle\">";
		$output .= "		<img src=\"".ENTRADA_URL."/images/record-next-off.gif\" width=\"11\" height=\"11\" border=\"0\" alt=\"\" title=\"\" style=\"vertical-align: middle; margin-right: 5px\" />";
		$output .= "		".html_encode($result["category_name"]);
		$output .= "		<input type=\"hidden\" name=\"delete[".((int)$identifier)."][category_id]\" value=\"".((int)$identifier)."\" />";
		$output .= "</td>\n";
		$output .= "</tr>\n";
		$query = "SELECT COUNT(`category_id`) FROM `".CLERKSHIP_DATABASE."`.`categories`
					WHERE `category_status` != 'trash'
					GROUP BY `category_parent`
					HAVING `category_parent` = ".$db->qstr((int)$identifier);
		$children = $db->GetOne($query);
		if ($children) {
			$output .= "<tbody id=\"delete-".((int)$identifier)."-children\">";
			$output .= "</tbody>";
			$output .= "	<tr>";
			$output .= "		<td>&nbsp;</td>\n";
			$output .= "		<td style=\"vertical-align: top;\">";
			$output .= "		<div style=\"padding-left: 30px\">";
			$output .= "		<span class=\"content-small\">There are children residing under <strong>".$result["category_name"]."</strong>.</span>";
			$output .= "		</div>";
			$output .= "		<div style=\"padding-left: 30px\">";
			$output .= "			<input type=\"radio\" name=\"delete[".((int)$identifier)."][move]\" id=\"delete_".((int)$identifier)."_children\" value=\"0\" onclick=\"$('move-".((int)$identifier)."-children').hide();\" checked=\"checked\"/>";
			$output .= "			<label for=\"delete_".((int)$identifier)."_children\" class=\"form-nrequired\"><strong>Deactivate</strong> all children</label>";
			$output .= "			<br />";
			$output .= "			<input type=\"radio\" name=\"delete[".((int)$identifier)."][move]\" id=\"move_".((int)$identifier)."_children\" value=\"1\" onclick=\"$('move-".((int)$identifier)."-children').show();\" />";
			$output .= "			<label for=\"move_".((int)$identifier)."_children\" class=\"form-nrequired\"><strong>Move</strong> all children</label>";
			$output .= "			<br /><br />";
			$output .= "		</div>";
			$output .= "		</td>";
			$output .= "	</tr>";
			$output .= "<tbody id=\"move-".((int)$identifier)."-children\" style=\"display: none;\">";
			$output .= "	<tr>";
			$output .= "		<td>&nbsp;</td>\n";
			$output .= "		<td style=\"vertical-align: top; padding: 0px 0px 0px 30px\">";
			$output .= "			<div id=\"selectParent".(int)$identifier."Field\"></div>";
			$output .= "		</td>";
			$output .= "	</tr>";
			$output .= "	<tr>";
			$output .= "		<td colspan=\"2\">&nbsp;</td>";
			$output .= "	</tr>";
			$output .= "</tbody>";
			$ONLOAD[]	= "selectCategory(0, ".$identifier.", '".$excluded_categories."')";

		}
	}

	return $output;
}

/**
 * This function handles adding update records of event_history changes.
 *
 * @param int $event
 * @param string $message
 * @param int $proxy_id
 * @param bigint $updated_date
 * @return null
 */
function history_log($event, $message, $updater = 0, $time = 0) {
	global $db, $ENTRADA_USER;

	if (!$updater) { // Ignore recording updates if made by the sole author.
		$result = $db->GetOne("SELECT count(*) FROM `event_history` WHERE `event_id` = ".$db->qstr($event));
		if (!$result) {
			$result = $db->GetOne("SELECT `updated_by` FROM `events` WHERE `event_id` = ".$db->qstr($event));
			if ($result == $ENTRADA_USER->getID()) {
				return ;
			}
		}
	}

	if (!$db->AutoExecute("event_history", array("event_id" => $event, "proxy_id" => ($updater ? $updater : $ENTRADA_USER->getID()), "history_message" => $message, "history_timestamp" => ($time ? $time : time())), "INSERT")) {
		add_error("There was an error while trying to save the selected <strong>Event content update</strong> for this event.<br /><br />The system administrator was informed of this error; please try again later.");

		application_log("error", "Unable to insert a new event_history record while updating this event. Database said: ".$db->ErrorMsg());
	}
}

/**
 * This function returns if event message has changed.
 *
 * @param int $event_id
 * @param strint event field
 * @return boolean
 */
function event_text_change($event, $field, $table = 'events') {
	global $db;
	$ret = false;
	if (isset($_POST["$field"])) {
		$message_length = strlen($_POST["$field"]);
		$result = $db->GetOne("SELECT `$field` FROM `$table` WHERE `event_id` = " . $db->qstr($event));
		if ($result) {
			$result_length = strlen($result);
			if ($message_length != $result_length) {
				$ret = true;
			} elseif ($result_length) {
				$ret = strcmp($result, $_POST["$field"]);
			}
		} else {
			$ret = $message_length;
		}
	}
    $return = array (
        'ret' => $ret,
        'query' => $result
    );
	return $return;
}

/**
 * This function returns true if the field has changed.
 * @global object $db
 * @param int $id
 * @param string $id_field_name
 * @param string $field
 * @param string $value
 * @param string $table
 * @return boolean
 */
function md5_change_value($id, $id_field_name, $field, $value, $table) {
    global $db;
    $changed = false;
    $pattern = array('/\\r\n/', '/\\r/', '/\\n/', '/\\r\n\n/', '/\r/', '/\\&nbsp;/', '/\\&#160;/', '~\x{00a0}~siu', '/\t/');
    $replacement = array('', '', '', '', '', ' ', ' ', ' ', '');
    if (isset($value)) {
        $value = preg_replace($pattern,$replacement,$value);
        $result = $db->GetOne("SELECT `$field` FROM `$table` WHERE `$id_field_name` = " . $db->qstr($id));
        if ($result) {
            $result = preg_replace($pattern,$replacement,$result);
        } else {
            $result = 0;
        }
        
        if (md5($value) === md5($result)){
            $changed = false;
        } else {
            $changed = true;
        }
        
        if ($value === "" && $result === 0) {
            $changed = false;
        }
    }
//    $array = Array(
//        'value' => $value,
//        'result' => $result,
//        'changed' => $changed
//    );
    return $changed;
}

/**
 * This function is used to tell if a value exists in either array
 * 
 * @param array $array1
 * @param array $array2
 * @return boolean
 * 
 */
function compare_array_values($array1, $array2) {
    $changed = false;
    sort($array1);
    sort($array2); 
    //added
    if (array_diff($array1, $array2)) {
        $changed = true;
    }
    //removed
    if (array_diff($array2, $array1)) {
        $changed = true;
    }
    return $changed;
}
/**
 * 
 * This function compares arrays using serialization to compare the changes
 * It returns an array of rows to add and remove
 * used in the learning events edit and content pages.
 * 
 * @param array $current_array
 * @param array $new_array
 * @param string $primary_key
 * @return array
 * 
 * 
 */
function compare_mlti_array($current_array, $new_array, $primary_key = null) {
    $return = false;
	$array_diff_add = array();
	$array_diff_remove = array();
    
    if (is_array($current_array) && count($current_array)) {
        foreach($current_array AS $current_item) {
			if ($primary_key) {
				$key = $current_item[$primary_key];
				$current_serialized[$key] = serialize($current_item);
			} else {
				$current_serialized[] = serialize($current_item);
			}
        }
    } else {
		$current_serialized = array();
	}
        
    if (is_array($new_array) && count($new_array)) {
        foreach($new_array AS $new_item) {
			if ($primary_key) {
				$key = $new_item[$primary_key];
				$new_serialized[$key] = serialize($new_item);
			} else {
				$new_serialized[] = serialize($new_item);
			}
        }
    } else {
		$new_serialized = array();
	}

	if (is_array($new_serialized) && is_array($current_serialized)) {
		$array_diff_add 	= array_diff_assoc($new_serialized, $current_serialized);
		$array_diff_remove 	= array_diff_assoc($current_serialized, $new_serialized);
	}
    
    if ((isset($array_diff_add) && is_array($array_diff_add)) || (isset($array_diff_remove)  && is_array($array_diff_remove))) {
        $return = array(
            "add" 		=> $array_diff_add,
            "remove" 	=> $array_diff_remove,
		);
    }
    
    return $return;
}

/*
 * This function recursively deactivates child objectives.
 *
 * @param int $parent_id
 * @param int $organisation_id
 * @param int $i
 * @return boolean
 */
function deactivate_objective_children($parent_id, $organisation_id, $i = 0) {
	if ($i > 99) {
		exit;
	}
	global $db, $ENTRADA_USER;

	$query = "	SELECT a.*, GROUP_CONCAT(b.`organisation_id`) AS `organisations` FROM `global_lu_objectives` AS a
				JOIN `objective_organisation` AS b
				ON a.`objective_id` = b.`objective_id`
				WHERE a.`objective_parent` = ".$db->qstr($parent_id)."
				AND a.`objective_active` = '1'
				GROUP BY `objective_id`";
	$objectives = $db->GetAll($query);
	if ($objectives) {
		$i = 0;
		foreach ($objectives as $objective) {
			$organisations = explode(",", $objective["organisations"]);
			/*
			* Remove the objective_organisation record.
			*/
			$query = "DELETE FROM `objective_organisation` WHERE `organisation_id` = ".$db->qstr($organisation_id)." AND `objective_id` = ".$db->qstr($objective["objective_id"]);
			if (!$db->Execute($query)) {
				application_log("Failed to remove entry from [objective_organisation], DB said: ".$db->ErrorMsg());
				echo $db->ErrorMsg();
			}
			/*
			* If $organisations has more than 1 entry the objective is active across multiple, and should not be deactivated in `global_lu_objectives`
			*/
			if (count($organisations) <= 1) {
				$query = "UPDATE `global_lu_objectives` SET `objective_active` = '0', `updated_date` = " . $db->qstr(time()) . ", `updated_by` = " . $db->qstr($ENTRADA_USER->getID()) . " WHERE `objective_id` = ".$db->qstr($objective["objective_id"]);
				if ($db->Execute($query)) {
					deactivate_objective_children($objective["objective_id"], $organisation_id, $i);
				}
			}
			$i++;
		}
		return true;
	} else {
		return false;
	}
}

function flatten_array($array) {
	if (!is_array($array)) {
		// nothing to do if it's not an array
		return array($array);
	}

	$result = array();
	foreach ($array as $value) {
		// explode the sub-array, and add the parts
		$result = array_merge($result, flatten_array($value));
	}

	return $result;
}

/*
 * Function to resize and move profile images.
 *
 */
function moveImage($source, $id, $coords, $dimensions, $type = "user", $sizes = array("upload" => array("width" => 192, "height" => 250), "upload-thumbnail" => array("width" => 75, "height" => 98))) {
	$coords = explode(",", $coords);
	$dimensions = explode(",", $dimensions);

	if ($id) {
		if (DEMO_MODE) {
			$source = DEMO_PHOTO;
		}

		$image_details = getimagesize($source);

		switch($image_details["mime"]) {
			case "image/jpeg" :
				$image = imagecreatefromjpeg($source);
			break;
			case "image/gif" :
				$image = imagecreatefromgif($source);
			break;
			case "image/png" :
				$image = imagecreatefrompng($source);
			break;
			default:
				$return = false;
			break;
		}

        if (!DEMO_MODE) {
            if ($image) {
                copy($source, STORAGE_USER_PHOTOS . "/" . $id . "-upload-original");

                $image_scale = round($image_details[0] / $dimensions[0], 2);

                foreach ($coords as $coord) {
                    $scaled_coords[] = (int) round($coord * $image_scale, 2);
                }

                $path = STORAGE_USER_PHOTOS . '/../public/images/' . ($type == "team" ? "teams/" : "") . $id .'/' . $id;

                foreach ($sizes as $size_name => $size) {
                    $resized_image = imagecreatetruecolor($size["width"], $size["height"]);
                    imagecopyresampled($resized_image, $image, 0, 0, $scaled_coords[0], $scaled_coords[1], $size["width"], $size["height"], $scaled_coords[2] - $scaled_coords[0], $scaled_coords[3] - $scaled_coords[1]);
                    $scaled_image =  CACHE_DIRECTORY . "/profile-img-" . $id . "-".$size["width"]."x".$size["height"].".png";
                    imagepng($resized_image, $scaled_image);
                    if (!copy($scaled_image, STORAGE_USER_PHOTOS . "/" . $id . "-" . $size_name)) {
                        $return = false;
                    } else {
                        unlink($scaled_image);
                    }
                }
            }
        } else {
			copy(DEMO_PHOTO, STORAGE_USER_PHOTOS . "/" . $id . "-upload");
        }
	}

	return filesize(STORAGE_USER_PHOTOS . "/" . $id . "-upload");
}

/*
 * A function to fetch the department custom fields for a user
 */
function fetch_department_fields($proxy_id = NULL, $organisation_id = 0) {
	global $db, $ENTRADA_USER;
	if ($proxy_id == NULL) {
        $proxy_id = $ENTRADA_USER->getID();
	}
	/*
	 * Fetch the departments this use is a part of.
	 */
	$departments = get_user_departments($proxy_id);
	$custom_fields = array();

	if ($departments) {

		foreach ($departments as $department) {
			$department_list[] = (int) $department["department_id"];
		}
		/*
		* Fetch the custom fields and responses for the user.
		*/
		$query = "	SELECT a.*, b.`value`
					FROM `profile_custom_fields` AS a
					LEFT JOIN `profile_custom_responses` AS b
					ON a.`id` = b.`field_id`
					AND (b.`proxy_id` = ".$db->qstr($proxy_id)." OR b.`proxy_id` IS NULL)
					WHERE a.`department_id` IN ('".implode("','", $department_list)."')
					".($organisation_id ? "AND a.`organisation_id` = ".$db->qstr($organisation_id) : "")."
					AND a.`active` = '1'
					GROUP BY a.`id`
					ORDER BY a.`organisation_id`, a.`department_id`, a.`order`";
		$dep_custom_fields = $db->GetAll($query);

		if ($dep_custom_fields) {
		   foreach ($dep_custom_fields as $field) {
			   $custom_fields[$field["department_id"]][$field["id"]] = $field;
		   }
		}

	}

	return $custom_fields;
}

//function displays bytes as KB, MB or GB
function formatSizeUnits($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}


//function sorts name arrays by lastname and then firstname
function cmp_last_first($a, $b) {
    if ($a["lastname"] == $b["lastname"]) {
        return strcmp($a["firstname"], $b["firstname"]);
    }
    return strcmp($a["lastname"], $b["lastname"]);
}


//function sorts team array by team name
function cmp_group_name($a, $b) {
    return strcmp($a["group_name"], $b["group_name"]);
}

//these functions are used to sort the gradebook stats array

//sorts name ASC
function cmp_names_ASC($a, $b) {
    if ($a["lastname"] == $b["lastname"]) {
        return strcmp($a["firstname"], $b["firstname"]);
    }
    return strcmp($a["lastname"], $b["lastname"]);
}

//sorts name DESC
function cmp_names_DESC($a, $b) {
    if ($a["lastname"] == $b["lastname"]) {
        return strcmp($a["firstname"], $b["firstname"]);
    }
    return strcmp($b["lastname"], $a["lastname"]);
}

//sorts views ASC
function cmp_views_ASC($a, $b) {
    return strcmp($a["views"], $b["views"]);
}

//sorts views DESC
function cmp_views_DESC($a, $b) {
    return strcmp($b["views"], $a["views"]);
}

//sorts first views ASC
function cmp_first_view_ASC($a, $b) {
    return strcmp($a["firstviewed"], $b["firstviewed"]);
}

//sorts first views DESC
function cmp_first_view_DESC($a, $b) {
    return strcmp($b["firstviewed"], $a["firstviewed"]);
}

//sorts first views ASC
function cmp_last_view_ASC($a, $b) {
    return strcmp($a["lastviewed"], $b["lastviewed"]);
}

//sorts first views DESC
function cmp_last_view_DESC($a, $b) {
    return strcmp($b["lastviewed"], $a["lastviewed"]);
}

//sorts by value
function cmp_number($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}

/*
 * 
 * Converts a string in the UNIX date format to a php formated date style
 * Ignores dates of 0 in this case as they're empty dates, not a date from 1969
 * 
 * @param string $string
 * $return string
 */
function unixStringtoDate($string){
    $string = (int)$string;
    if ($string == 0){
        return "";
    } else {
        return date("m-j-Y g:ia", $string);
    }
}

/**
 *
 * Load the active organisation for the user including their permissions,
 * template, system groups, etc.
 *
 * @param int $organisation_id
 * @param int $user_access_id
 */
function load_active_organisation($organisation_id = 0, $user_access_id = 0) {
    global $db, $ENTRADA_USER, $ENTRADA_TEMPLATE, $SYSTEM_GROUPS;

    $allow_organisation_id_set = false;
    $allow_access_id_set = false;

    $organisation_id = (int) $organisation_id;
    $user_access_id = (int) $user_access_id;

    if ($ENTRADA_USER && $ENTRADA_TEMPLATE) {
        $_SESSION["permissions"] = permissions_load();

        /**
         * Load active organisation from preferences if one exists.
         */
        $active_organisation = preferences_load("organisation_switcher");

        /**
         * Check whether we are trying to set a new org and access_id or use one
         * from user preferences, or the default.
         */
        if (!$organisation_id || !$user_access_id) {
            if (isset($active_organisation["organisation_id"]) && ($active_organisation["organisation_id"] != $ENTRADA_USER->getActiveOrganisation()) && isset($active_organisation["access_id"]) && $active_organisation["access_id"]) {
                $organisation_id = (int) $active_organisation["organisation_id"];
                $user_access_id = (int) $active_organisation["access_id"];
            } else {
                $organisation_id = (int) $ENTRADA_USER->getActiveOrganisation();
                $user_access_id = (int) $ENTRADA_USER->getAccessId();
            }
        }

        /**
         * Interate through existing permissions to ensure
         */
        foreach ($_SESSION["permissions"] as $access_id => $permission) {
            if ($permission["organisation_id"] == $organisation_id) {
                $allow_organisation_id_set = true;

                if ($access_id == $user_access_id) {
                    $allow_access_id_set = true;
                }
            }
        }

        if ($allow_organisation_id_set && $allow_access_id_set) {
            $ENTRADA_USER->setActiveOrganisation($organisation_id);
            $ENTRADA_USER->setAccessId($user_access_id);

            $_SESSION[APPLICATION_IDENTIFIER]["organisation_switcher"]["organisation_id"] = $organisation_id;
            $_SESSION[APPLICATION_IDENTIFIER]["organisation_switcher"]["access_id"] = $user_access_id;
        } else {
            application_log("error", "User [".$ENTRADA_USER->getId()."] attempted to change to organisation [".$organisation_id."] and access_id [".$user_access_id."] but was unsuccessful.");
        }

        /**
         * Returns all of the system groups and roles within the active organisation.
         * @todo This seems like a very odd spot for this, but I'm not going to move out yet.
         */
        $query = "SELECT a.*
                  FROM `" . AUTH_DATABASE . "`.`system_groups` AS a,
                  `" . AUTH_DATABASE . "`.`system_group_organisation` AS c
                  WHERE a.`id` = c.`groups_id`
                  AND c.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
                  ORDER BY a.`group_name` ASC";
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $SYSTEM_GROUPS[$result["group_name"]] = array();
                $query = "SELECT a.*
                            FROM `" . AUTH_DATABASE . "`.`system_roles` a
                            WHERE a.`groups_id` = " . $result["id"] . "
                            ORDER BY a.`role_name` ASC";
                $roles = $db->GetAll($query);
                if ($roles) {
                    foreach ($roles as $role) {
                        $SYSTEM_GROUPS[$result["group_name"]][] = $role["role_name"];
                    }
                }
            }
        }

        preferences_update("organisation_switcher", $active_organisation);

        $ENTRADA_TEMPLATE->setActiveTemplate($ENTRADA_USER->getActiveOrganisation());
    }
}

function get_allowed_mime_types($identifier = "default") {
    $valid_mimetypes = array();
    $json_data = Entrada_Settings::fetchValueByShortname("valid_mimetypes");
    if ($json_data) {
        $all_mimetypes = json_decode($json_data, true);
        if (!empty($all_mimetypes) && is_array($all_mimetypes)) {
            if (array_key_exists($identifier, $all_mimetypes)) {
                $valid_mimetypes = $all_mimetypes[$identifier];
            }

            if (!$valid_mimetypes && array_key_exists("default", $all_mimetypes)) {
                $valid_mimetypes = $all_mimetypes["default"];
            }
        }
    }

    return $valid_mimetypes;
}

function assessments_items_subnavigation($tab = "items") {
    global $ENTRADA_ACL;

    echo "<div class=\"no-printing\">\n";
    echo "    <ul class=\"nav nav-tabs\">\n";
    if($ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
        echo "<li".($tab=="items"?" class=\"active\"":"")."><a href=\"".ENTRADA_RELATIVE."/admin/assessments/items\">Items</a></li>\n";
        if(!isset($_GET["id"])) {
            echo "<li" . ($tab == "rubrics" ? " class=\"active\"" : "") . "><a href=\"" . ENTRADA_RELATIVE . "/admin/assessments/rubrics\">Grouped Items</a></li>\n";
        }
    }
    echo "	</ul>\n";
    echo "</div>\n";
}

function fetchAssessmentTargets($adistibution_id) {
    global $ENTRADA_USER;
    $targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($adistibution_id);
    $target_members = array();

    if ($targets) {
        foreach($targets as $target) {
            switch($target->getTargetType()) {
                case "proxy_id":
                    $user = User::fetchRowByID($target->getTargetID());
                    if ($user) {
                        $target_members[] = array("name" => $user->getFullname(true), "proxy_id" => $user->getProxyId());
                    }
                    break;
                case "group_id":
                    $group_members = Models_Group_Member::getAssessmentGroupMembers($ENTRADA_USER->getActiveOrganisation(), $target->getTargetID());
                    if ($group_members) {
                        foreach($group_members as $member) {
                            $target_members[] = array("name" => $member["name"], "proxy_id" => $member["proxy_id"]);
                        }
                    }
                    break;
                case "cgroup_id":

                    break;
                case "schedule_id":
                    $schedule = Models_Schedule::fetchRowByID($target->getTargetID());
                    if ($schedule->getScheduleParentID()) {
                        $schedule_parent = Models_Schedule::fetchRowByID($schedule->getScheduleParentID());
                        $target_text = $schedule_parent->getTitle();
                    } else {
                        $target_text = $schedule->getTitle();
                    }

                    $target_members[] = array("name" => $target_text, "schedule_id" => $target->getTargetID());
                    break;
            }
        }
    }

    return $target_members;
}

function fetchFormTypeTitle($distribution_id) {
    $target = Models_Assessments_Distribution_Target::fetchRowByDistributionID($distribution_id);
    //$target_type_option = Models_Assessments_Distribution_TargetTypesOptions::fetchRowByID($target->getAdttoID());
    //$target_type = Models_Assessments_Distribution_TargetType::fetchRowByID($target_type_option->getAdttypeID());
    //$target_option = Models_Assessments_Distribution_TargetOption::fetchRowByID($target_type_option->getAdtoptionID());
    $form_type_data = array();

    if ($target) {
        switch($target->getTargetType()) {
            case "proxy_id":
                $user = User::fetchRowByID($target->getTargetID());
                $form_type_data["title"] = $user->getFullname(true);
                $form_type_data["type"] = "proxy_id";
                break;
            case "cgroup_id":
                $course = Models_Course::fetchRowByID($target->getTargetID());
                $form_type_data["title"] = "";
                if ($course) {
                    $form_type_data["title"] = $course->getCourseName();
                }
                $form_type_data["type"] = "course_id";
                break;
            case "group_id":
                $group = Models_Group::fetchRowByID($target->getTargetID());
                $form_type_data["title"] = "Group Name Not Found";
                /*if ($target_option && $group) {
                    switch($target_option->getName()) {
                        case "group":
                            $form_type_data["title"] = $group->getGroupName();
                            break;
                        case "individuals":
                            $form_type_data["title"] = $group->getGroupName();
                            break;
                        case "schedule":
                            $form_type_data["title"]= $group->getGroupName();
                            break;
                    }
                }*/
                $form_type_data["type"] = "group_id";
                break;
            case "cgroup_id":
                $form_type_data["title"] = "Assessment of Learners";
                $form_type_data["type"] = "cgroup_id";
                break;
            case "schedule_id":
                $schedule = Models_Schedule::fetchRowByID($target->getTargetID());
                if ($schedule->getScheduleParentID()) {
                    $schedule_parent = Models_Schedule::fetchRowByID($schedule->getScheduleParentID());
                    $form_type_data["title"] = $schedule_parent->getTitle();
                } else {
                    $form_type_data["title"] = $schedule->getTitle();
                }
                $form_type_data["type"] = "schedule_id";
                break;
            default:
                $form_type_data["title"] = "";
                $form_type_data["type"] = "";
                break;
        }
    }

    return $form_type_data;
}

function fetchTargetStatus($targets, $assessor, $schedule_child) {
    global $ENTRADA_USER;

    $progress_value = array();
    if ($targets) {
        $target_num = 0;
        $progress_value["name"] = "Awaiting Completion";
        $progress_value["shortname"] = "awaitingcompletion";
        foreach($targets as $t) {
            $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDLearningContextID($assessor->getAdistributionID(), $ENTRADA_USER->getActiveId(), $t["proxy_id"], $schedule_child->getID());
            if ($progress && $progress->getProgressValue() == "inprogress") {
                $progress_value["name"] = "In Progress";
                $progress_value["shortname"] = "inprogress";
            }
            if ($progress && $progress->getProgressValue() == "complete") {
                $target_num++;
            }
        }
        if ($target_num == count($targets)) {
            $progress_value["name"] = "Complete";
            $progress_value["shortname"] = "complete";
        }
    }
    return $progress_value;
}

function fetchUserPhoto($proxy_id) {
    global $ENTRADA_ACL, $db;

    $user = User::fetchRowByID($proxy_id);
    $user = $user->toArray();

    $image = "";

    $query			= "SELECT `privacy_level` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($proxy_id);
    $privacy_level	= $db->GetOne($query);

    $official_file_active	= false;
    $uploaded_file_active	= false;

    /**
     * If the photo file actually exists, and either
     * 	If the user is in an administration group, or
     *  If the user is trying to view their own photo, or
     *  If the proxy_id has their privacy set to "Any Information"
     */
    if ((@file_exists(STORAGE_USER_PHOTOS."/".$proxy_id."-official")) && ($ENTRADA_ACL->amIAllowed(new PhotoResource($proxy_id, (int) $privacy_level, "official"), "read"))) {
        $official_file_active	= true;
    }

    /**
     * If the photo file actually exists, and
     * If the uploaded file is active in the user_photos table, and
     * If the proxy_id has their privacy set to "Basic Information" or higher.
     */
    $query			= "SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = '1' AND `photo_active` = '1' AND `proxy_id` = ".$db->qstr($proxy_id);
    $photo_active	= $db->GetOne($query);
    if ((@file_exists(STORAGE_USER_PHOTOS."/".$proxy_id."-upload")) && ($photo_active) && ($ENTRADA_ACL->amIAllowed(new PhotoResource($proxy_id, (int) $privacy_level, "upload"), "read"))) {
        $uploaded_file_active = true;
    }
    $image .= "<div id=\"img-holder-".$proxy_id."\" class=\"img-holder pull-left\">";
    if ($official_file_active) {
        $image .= "		<img id=\"official_photo_".$proxy_id."\" class=\"official people-search-thumb img-rounded\" src=\"".webservice_url("photo", array($proxy_id, "official"))."\" width=\"72\" height=\"100\" alt=\"".html_encode($user["prefix"]." ".$user["firstname"]." ".$user["lastname"])."\" title=\"".html_encode($user["prefix"]." ".$user["firstname"]." ".$user["lastname"])."\" />\n";
    }

    if ($uploaded_file_active) {
        $image .= "		<img id=\"uploaded_photo_".$proxy_id."\" class=\"uploaded people-search-thumb img-rounded\" src=\"".webservice_url("photo", array($proxy_id, "upload"))."\" width=\"72\" height=\"100\" alt=\"".html_encode($user["prefix"]." ".$user["firstname"]." ".$user["lastname"])."\" title=\"".html_encode($user["prefix"]." ".$user["firstname"]." ".$user["lastname"])."\" />\n";
    }

    if (($official_file_active) || ($uploaded_file_active)) {
        $image .= "		<a id=\"zoomin_photo_".$proxy_id."\" class=\"zoomin\" onclick=\"growPic($('official_photo_".$proxy_id."'), $('uploaded_photo_".$proxy_id."'), $('official_link_".$proxy_id."'), $('uploaded_link_".$proxy_id."'), $('zoomout_photo_".$proxy_id."'));\">+</a>";
        $image .= "		<a id=\"zoomout_photo_".$proxy_id."\" class=\"zoomout\" onclick=\"shrinkPic($('official_photo_".$proxy_id."'), $('uploaded_photo_".$proxy_id."'), $('official_link_".$proxy_id."'), $('uploaded_link_".$proxy_id."'), $('zoomout_photo_".$proxy_id."'));\"></a>";
    } else {
        $image .= "		<img class=\"media-object people-search-thumb img-rounded\" src=\"".ENTRADA_URL."/images/headshot-male.gif\" width=\"72\" height=\"100\" alt=\"No Photo Available\" title=\"No Photo Available\" />\n";
    }

    if (($official_file_active) && ($uploaded_file_active)) {
        $image .= "		<a id=\"official_link_".$proxy_id."\" class=\"img-selector one\" onclick=\"showOfficial($('official_photo_".$proxy_id."'), $('official_link_".$proxy_id."'), $('uploaded_link_".$proxy_id."'));\" href=\"javascript: void(0);\">1</a>";
        $image .= "		<a id=\"uploaded_link_".$proxy_id."\" class=\"img-selector two\" onclick=\"hideOfficial($('official_photo_".$proxy_id."'), $('official_link_".$proxy_id."'), $('uploaded_link_".$proxy_id."'));\" href=\"javascript: void(0);\">2</a>";
    }
    $image .= "</div>";

    return $image;
}

function get_objective_text($objective, $always_show_code = false) {
    if ($objective["objective_code"]) {
        return $objective["objective_code"] . ": " . $objective["objective_name"];
    } else {
        $is_code = preg_match("/^[A-Z]+\-[\d\.]+$/", $objective["objective_name"]);
        if ($objective["objective_description"] && $is_code) {
            if ($always_show_code) {
                return $objective["objective_name"] . ": " . $objective["objective_description"];
            } else {
                return $objective["objective_description"];
            }
        } else {
            return $objective["objective_name"];
        }
    }
}

/**
 * Validate the supplied date
 *
 * @param type $date
 * @param type $format
 */
function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

/*
 * Return the URL of the current page
*/

function getCurrentUrl() {
    $url  = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
    $url .= '://' . $_SERVER['SERVER_NAME'];
    $url .= in_array( $_SERVER['SERVER_PORT'], array('80', '443') ) ? '' : ':' . $_SERVER['SERVER_PORT'];
    $url .= $_SERVER['REQUEST_URI'];
    return $url;
}

/**
 * Removes all DOM elements of type $tagname from $document.
 * 
 * @param string $tagName
 * @param DOMDocument $document
 */
function removeElementsByTagName($tagName, $document) {
    if (is_array($tagName)) {
        $tags = $tagName;
    } else {
        $tags = array($tagName);
    }
    foreach ($tags as $tag) {
        $nodeList = $document->getElementsByTagName($tag);
        for ($nodeIdx = $nodeList->length; --$nodeIdx >= 0; ) {
            $node = $nodeList->item($nodeIdx);
            $node->parentNode->removeChild($node);
        }
    }
}

function hidden_params(array $params, $key_template = "%s") {
    ob_start();
    ?>
    <?php foreach ($params as $key => $value): ?>
        <?php if (is_array($value)): ?>
            <?php echo hidden_params($value, sprintf($key_template, $key) . "[%s]"); ?>
        <?php elseif (!is_null($value)): ?>
            <input type="hidden" name="<?php echo sprintf($key_template, $key); ?>" value="<?php echo $value; ?>"/>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php
    return ob_get_clean();
}

function isUsingSecureBrowser(){
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'SEB') !== false && array_key_exists('HTTP_X_SAFEEXAMBROWSER_REQUESTHASH', $_SERVER)){
		return true;
	} else {
		return false;
	}
}
