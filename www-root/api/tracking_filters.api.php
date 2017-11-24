<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves a particular calendar in either JSON or ICS depending on the extension of the $_GET["request"];
 * http://www.yourschool.ca/calendars/username.json
 * http://www.yourschool.ca/calendars/username.ics
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

$options_for = false;

if (isset($_GET["options_for"])) {
    $options_for = clean_input($_GET["options_for"], array("trim"));
}
if (isset($_GET["community_id"])) {
    $community_id = clean_input($_GET["community_id"], array("int"));
}else{
	exit;
}
if (($options_for) && (isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
    $query = "SELECT `organisation_id`,`organisation_title` FROM `".AUTH_DATABASE."`.`organisations` ORDER BY `organisation_title` ASC";
    $organisation_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
    $organisation_ids_string = "";
    if ($organisation_results) {
        $organisations = array();
        foreach ($organisation_results as $result) {
            if($ENTRADA_ACL->amIAllowed("resourceorganisation".$result["organisation_id"], "read")) {
                if (!$organisation_ids_string) {
                    $organisation_ids_string = $db->qstr($result["organisation_id"]);
                } else {
                    $organisation_ids_string .= ", ".$db->qstr($result["organisation_id"]);
                }
                if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["organisation"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["organisation"]) && (in_array($result["organisation_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["organisation"]))) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = '';
                }
                $organisations[$result["organisation_id"]] = array('text' => $result["organisation_title"], 'value' => 'organisation_'.$result["organisation_id"], 'checked' => $checked);
                $organisation_categories[$result["organisation_id"]] = array('text' => $result["organisation_title"], 'value' => 'organisation_'.$result["organisation_id"], 'category'=>true);
            }
        }
    }
    if (!$organisation_ids_string) {
        $organisation_ids_string = $db->qstr($ORGANISATION_ID);
    }

    switch($options_for) {
    case "members":
        // Get the possible Student filters
		//$organisation_categories[0] = array('text' => "Members", 'value' => 'member_title', 'category'=>true);
        $query = "	SELECT `id` AS `proxy_id`, `organisation_id`, CONCAT_WS(' ',`firstname`,`lastname`) AS `fullname` 
					FROM `".AUTH_DATABASE."`.`user_data` 
					WHERE `id` IN (
					SELECT `proxy_id` 
					FROM `statistics` 
                    WHERE `module` LIKE 'community:".$community_id.":%')";
        $student_results = $db->GetAll($query);
        if ($student_results) {
            $students = $organisation_categories;
            foreach ($student_results as $r) {
                if (isset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["members"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["members"]) && (in_array($r['id'], $_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["members"]))) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = '';
                }
                $students[$r['organisation_id']]['options'][] = array('text' => $r['fullname'], 'value' => 'members_'.$r['proxy_id'], 'checked' => $checked);
            }
            echo lp_multiple_select_popup('members', $students, array('title'=>'Select Members:', 'submit_text'=>'Apply', 'cancel'=>true, 'submit'=>true));
        }
        break;
    case "module":
        // Get the possible courses filters
        $query = "	SELECT DISTINCT `module` 
					FROM `statistics` 
					WHERE `module` LIKE 'community:".$community_id.":%'";
        $module_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
        if ($module_results) {
            $modules = array();
            foreach ($module_results as $k=>$m) {
				$mod_arr = explode(':',$m['module']);
				$mod_name = ucwords($mod_arr[2]);
                if (isset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["module"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["module"]) && (in_array($mod_name, $_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["module"]))) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = '';
                }

                $modules[] = array('text' => $mod_name, 'value' => 'module_'.$mod_name, 'checked' => $checked);
            }

            echo lp_multiple_select_popup('module', $modules, array('title'=>'Select Module Type:', 'submit_text'=>'Apply', 'cancel'=>true, 'submit'=>true));
        }
        break;
    case "page":
		

		$query = "SELECT DISTINCT `action_field` 
				FROM `statistics` 
                WHERE `module` LIKE 'community:".$community_id.":%'";
		$page_type_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
		//$organisation_ids_string = "";
		if ($page_type_results) {
			$page_types = array();
			foreach ($page_type_results as $result) {
					if (isset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["page_types"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["page_types"]) && (in_array($result["action_field"], $_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["page_types"]))) {
						$checked = 'checked="checked"';
					} else {
						$checked = '';
					}
					$id = str_replace("_","-",$result["action_field"]);
					
					switch ($result['action_field']){
						case 'cshare_id':
								$text  = "Community Shares";
							break;
						case 'cscomment_id':
								$text  = "Community Share Comments"; 
							break;
						case 'csfile_id':
								$text  = "Community Share Files";
							break;
						case 'csfversion_id':
								$text  = "Community Share File Versions";
							break;	
						case 'cannouncement_id':
								$text  = "Community Announcements";
							break;
						case 'cdiscussion_id':
								$text  = "Community Discussions";
							break;		
						case 'cdtopic_id':
								$text  = "Community Discussion Topics";
							break;	
						case 'cevent_id':
								$text  = "Community Events";
							break;				
						case 'cgallery_id':
								$text  = "Community Galleries";
							break;			
						case 'cgphoto_id':
								$text  = "Community Gallery Photos";
							break;
						case 'cgcomment_id':
								$text  = "Community Gallery Comments";
							break;							
					}
					
					$page_types[$result["action_field"]] = array('text' => $text, 'value' => 'page_type_'.$id, 'category'=>true);
			}
		}
		
		
		
        // Get the possible small group filters
		
        $query = "	SELECT DISTINCT `action_field`,`action_value` 
					FROM `statistics` 
                    WHERE `module` LIKE 'community:".$community_id.":%'";
        $stat_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
		
		if ($stat_results){
			$page_results = array();
			foreach ($stat_results as $key=>$statistic){
				unset($query);
				switch ($statistic['action_field']){
					case 'cshare_id':
							$query = "	SELECT `folder_title` AS `page` 
										FROM `community_shares` 
										WHERE `cshare_id` = ".$db->qstr($statistic['action_value']);
						break;
					case 'cscomment_id':
							$query = "	SELECT b.`file_title` AS `page` 
										FROM `community_share_comments` AS a 
										LEFT JOIN `community_share_files` AS b 
										ON a.`csfile_id` = b.`csfile_id` 
										WHERE a.`cscomment_id` = ".$db->qstr($statistic['action_value']);
						break;
					case 'csfile_id':
							$query = "	SELECT b.`file_title` AS `page` 
										FROM `community_share_files` A
										WHERE a.`csfile_id` = ".$db->qstr($statistic['action_value']);
						break;
					case 'csfversion_id':
							$query = "	SELECT b.`file_title` AS `page` 
										FROM `community_share_file_versions` AS a 
										LEFT JOIN `community_share_files` AS b 
										ON a.`csfile_id` = b.`csfile_id` 
										WHERE a.`csfversion_id` = ".$db->qstr($statistic['action_value']);
						break;	
					case 'cannouncement_id':
							$query = "	SELECT `announcement_title` AS `page` 
										FROM `community_announcements`
										WHERE `cannouncement_id` = ".$db->qstr($statistic['action_value']);
						break;
					case 'cdiscussion_id':
							$query = "	SELECT `forum_title` AS `page` 
										FROM `community_discussions`
										WHERE `cdiscussion_id` = ".$db->qstr($statistic['action_value']);
						break;		
					case 'cdtopic_id':
							$query = "	SELECT `topic_title` AS `page` 
										FROM `community_discussion_topics`
										WHERE `cdtopic_id` = ".$db->qstr($statistic['action_value']);
						break;	
					case 'cevent_id':
							$query = "	SELECT `event_title` AS `page` 
										FROM `community_events`
										WHERE `cevent_id` = ".$db->qstr($statistic['action_value']);
						break;				
					case 'cgallery_id':
							$query = "	SELECT `gallery_title` AS `page` 
										FROM `community_galleries`
										WHERE `cgallery_id` = ".$db->qstr($statistic['action_value']);
						break;			
					case 'cgphoto_id':
							$query = "	SELECT `photo_title` AS `page` 
										FROM `community_gallery_photos`
										WHERE `cgphoto_id` = ".$db->qstr($statistic['action_value']);
						break;
					case 'cgcomment_id':
							$query = "	SELECT a.`gallery_title` AS `page` 
										FROM `community_galleries` AS a
										LEFT JOIN `community_gallery_comments` AS b 
										ON a.`cgaller_id` = b.`cgallery_id`
										WHERE `cgcomment_id` = ".$db->qstr($statistic['action_value']);
						break;							

				}
				if($query){
					$result = $db->GetOne($query);
					$page_results[] = array('action_field' => $statistic['action_field'],'action_value' => $statistic['action_value'],'page'=>$result);
				}
			}
		}
		
        if ($page_results) {
            $pages = $page_types;
            foreach ($page_results as $page) {
				$action_field = str_replace("_","-",$page["action_field"]);
				$page_id = $action_field.'-'.$page['action_value'];
				
                if (isset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["pages"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["pages"]) && (in_array($page_id, $_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["pages"]))) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = '';
                }

                $pages[$page["action_field"]]["options"][] = array('text' => ucwords($page['page']), 'value' => 'page_'.$page_id, 'checked' => $checked);
            }

            echo lp_multiple_select_popup('page', $pages, array('title'=>'Select Pages:', 'submit_text'=>'Apply', 'cancel'=>true, 'submit'=>true));
        }
        break;

    case "action":
        // Get the possible event type filters
        $query = "	SELECT DISTINCT `action` 
					FROM `statistics` 
                    WHERE `module` LIKE 'community:".$community_id.":%'";
        $action_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
        if ($action_results) {
            $actions = array();
            foreach ($action_results as $result) {
                if (isset($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["action"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["action"]) && (in_array($result["action"], $_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"]["action"]))) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = '';
                }
				$action_arr = explode('_',$result['action']);
				if (count($action_arr)==1)
					$action_name = $action_arr[0];
				else
					$action_name = $action_arr[1]." ".$action_arr[0];
				$action_value = implode('-',$action_arr);
				
                $actions[] = array('text' => ucwords($action_name), 'value' => 'action_'.$action_value, 'checked' => $checked);
            }

            echo lp_multiple_select_popup('action', $actions, array('title'=>'Select Actions:', 'submit_text'=>'Apply', 'cancel'=>true, 'submit'=>true));
        }

        break;
    case "total":
        $syear		= (date("Y", time()) - 1);
        $eyear		= (date("Y", time()) + 4);
        $gradyears = array();
        for ($year = $syear; $year <= $eyear; $year++) {
            if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["grad"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["grad"]) && (in_array($year, $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["grad"]))) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            $gradyears[] = array('text' => "Graduating in $year", 'value' => "grad_".$year, 'checked' => $checked);
        }

        echo lp_multiple_select_popup('grad', $gradyears, array('title'=>'Select Gradutating Years:', 'submit_text'=>'Apply', 'cancel'=>true, 'submit'=>true));

        break;
    }   
}
?>
