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
 * A class to handle the syncronization of course enrollment with an LDAP server.
 * Most of the heavy lifting is done by the private functions, the constructor acts as
 * the controller.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Entrada_Sync_Course_Ldap {

    private $ldap_connection,
            $course, $course_codes,
            $suffix, $ldap_code, $app_id,
            $group_id,
            $community_id, $community_audience,
            $course_audience_id, $course_audience,
            $ldap_audience = array();
    
    protected $sync_offset = "1209600";
    
    /**
     * Synchronize a single course ID.
     * @global type $db
     * @param int $course_id
     */
    public function __construct($course_id = null, $cperiod_id = null) {
        global $db;
        
        $course = Models_Course::get($course_id);
        if ($course) {
            $audience = $course->getAudience($cperiod_id);
            if ($audience) {
                $ldap_sync_date = time();
                foreach ($audience as $a) {
                    $a->setLdapSyncDate($ldap_sync_date);
                    if (!$a->update()) {
                        application_log("error", "Unable to update ldap_sync_date for caudience_id ". $a->getID() .".");
                    }
                }
            }
        }
        
        $this->ldap_connection = NewADOConnection("ldap");
        $this->ldap_connection->SetFetchMode(ADODB_FETCH_ASSOC);
        
        $query = "SELECT DISTINCT a.`course_id`, a.`course_code`, a.`organisation_id`, a.`curriculum_type_id`, b.`cperiod_id`, b.`start_date`, b.`finish_date`, c.`caudience_id`, d.`group_id`, a.`sync_ldap_courses`, a.`sync_groups`
                    FROM `courses` AS a
                    JOIN `curriculum_periods` AS b
                    ON a.`curriculum_type_id` = b.`curriculum_type_id`
                    JOIN `course_audience` AS c
                    ON b.`cperiod_id` = c.`cperiod_id`
                    AND c.`course_id` = a.`course_id`
                    LEFT JOIN `groups` AS d
                    ON c.`audience_value` = d.`group_id`
                    WHERE a.`course_id` = ?
                    AND a.`sync_ldap` = '1'
                    AND a.`course_active` = '1'
                    AND b.`active` = '1'
                    AND UNIX_TIMESTAMP(NOW()) > b.`start_date` - ? 
                    AND UNIX_TIMESTAMP(NOW()) < b.`finish_date`
                    AND (d.`group_active` = '1' OR d.`group_active` IS NULL)
                    ORDER BY b.`start_date` DESC";
        $results = $db->GetAll($query, array($course_id, $this->sync_offset));
        if ($results) {
            
            $this->course["course_id"] = $course_id;
            
            if (!$this->fetchGroupID()) {
                // $this->createCourseGroup();
                // if a group ID can't be found then don't attempt to sync. 
                $skip = true;
                application_log("notice", "Skipped syncronization of [".$this->course["course_code"]."], no group found.");
            }
            
            if ($this->fetchCommunityID()) {
                $this->fetchCommunityAudience();
            }
            
            $this->fetchCourseAudienceMembers();
            
            foreach ($results as $result) {
                $this->ldap_audience = array();
                $this->course = $result;
                $this->course_year = date("Y", $this->course["start_date"]);

                $this->setQueryParams($this->course["start_date"], $this->course["organisation_id"]);

                $this->fetchCourseCodes();

                $skip = false;
               
                if ($skip == false) {
                    if (!empty($this->course_codes)) {
                        if ($this->fetchLdapAudience()) {
                            foreach ($this->ldap_audience as $course_audience_member) {
                                $member_ldap_data = $this->fetchLdapAudienceMemberDetails($course_audience_member);
                                $this->handleUser($member_ldap_data);
                            }
                        }

                    } else {
                        application_log("error", "There were no course codes attached to this course. That should be impossible.");
                    }
                }
                
                if ($this->course["sync_groups"] == "1") {
                    $this->syncCourseGroups();
                }
            }
            
            if (!empty($this->course_audience)) {
                // The audience members remaining were not in the ldap sync, they need to be deactiviated.
                foreach ($this->course_audience as $audience_member_proxy_id => $member) {
                    if ($member["entrada_only"] != "1") {
                        if (!$db->AutoExecute("group_members", array("member_active" => "0"), "UPDATE", "`gmember_id` = " . $db->qstr($member["gmember_id"]))) {
                            application_log("error", "Failed to deactivate `group_members` record [".$member["gmember_id"]."], DB said: " . $db->ErrorMsg());
                        }
                    } else {
                        // Audience member was manually added to enrollment, should not be removed from community.
                        unset($this->community_audience[$audience_member_proxy_id]);
                    }
                }
            }
            if (!empty($this->community_audience)) {
                // The audience members remaining were not in the ldap sync, they need to be deactiviated.
                foreach ($this->community_audience as $audience_member => $cmember) {
                    $this->removeCommunityAudienceMember($audience_member);
                }
            }
        } else {
            return false;
        }
    }
    
    /**
     * Sync the course groups
     * @global type $db
     */
    private function syncCourseGroups() {
        global $db;
        
        if ($this->ldap_connection->Connect(LDAP_HOST, LDAP_SEARCH_DN, LDAP_SEARCH_DN_PASS, LDAP_CGROUP_BASE_DN)) {
            $users = array();
            $new_results = array();
            
            $course_code_base = clean_input($this->course["course_code"], "alpha") . "_" . clean_input($this->course["course_code"], "numeric");
            $search_query = "cn=".$course_code_base."*".$this->ldap_code."*";
            $results = $this->ldap_connection->GetAll($search_query);
            
            if ($results) {
                $course_group_lists = array();
                
                $s = array();
                foreach ($results as $k => $result) {
                    $s[$k] = $result["cn"];
                }

                asort($s);
                foreach ($s as $k => $cn) {
                    $new_results[] = $results[$k];
                }
                
                foreach ($new_results as $result) {
                    $collison = false;
                    if(!empty($result[LDAP_USER_IDENTIFIER])) {
                        foreach ($result[LDAP_USER_IDENTIFIER] as $user) {
                            $u_d = explode(",", $user);
                            foreach ($u_d as $kv_pair) {
                                list($k, $v) = explode("=", $kv_pair);
                                $v = strtolower($v);
                                if (strtolower($k) == strtolower(LDAP_MEMBER_ATTR)) {
                                    if (!in_array($v, $users)) {
                                       $users[] = $v;
                                    } else {
                                       $course_group_lists[$result["cn"]][] = $v;
                                    }
                                }
                            }
                        }
                    }
                }
                
                if ($course_group_lists) {
                    $i = 1;
                    foreach ($course_group_lists as $course_group_list => $users) {
                        $query = "SELECT * FROM `course_groups` WHERE `course_id` = ".$db->qstr($this->course["course_id"])." AND `active` = '1' AND `group_name` = '" . $this->course["course_code"] . $this->suffix . " " . date("Y", $this->course["start_date"]) . " Course Group " . ($i < 10 ? "0" . $i : $i) . "'";
                        $course_group = $db->GetRow($query);
                        if (!$course_group) {
                            $course_group = array(
                                "course_id"     => $this->course["course_id"],
                                "group_name"    => $this->course["course_code"] . $this->suffix . " " . date("Y", $this->course["start_date"]) . " Course Group " . ($i < 10 ? "0" . $i : $i),
                                "active"        => "1" 
                            );
                            if ($db->AutoExecute("`course_groups`", $course_group, "INSERT")) {
                                $course_group["cgroup_id"] = $db->Insert_ID();
                            }
                        }
                        if ($course_group) {
                            $query = "SELECT a.`cgaudience_id`, a.`proxy_id` 
                                        FROM `course_group_audience` AS a
                                        JOIN `course_groups` AS b
                                        ON a.`cgroup_id` = b.`cgroup_id`
                                        AND a.`cgroup_id` = ?";
                            $current_cgroup_audience = $db->GetAssoc($query, array($course_group["cgroup_id"]));
                            
                            foreach ($users as $k => $username) {
                                $query = "SELECT a.`id`, b.`cgaudience_id`, b.`active`
                                            FROM `".AUTH_DATABASE."`.`user_data` AS a 
                                            LEFT JOIN `course_group_audience` AS b
                                            ON a.`id` = b.`proxy_id`
                                            AND b.`cgroup_id` = " . $db->qstr($course_group["cgroup_id"]) . "
                                            WHERE a.`username` = " . $db->qstr($username);
                                $user = $db->GetRow($query);
                                if ($user) {
                                    if ($temp = array_search($user["id"], $current_cgroup_audience)) {
                                        unset($current_cgroup_audience[$temp]);
                                    }
                                    if (!$user["cgaudience_id"]) {
                                        $cgaudience = array(
                                            "cgroup_id" => $course_group["cgroup_id"],
                                            "proxy_id"  => $user["id"],
                                            "start_date"    => $this->course["start_date"],
                                            "finish_date"   => $this->course["finish_date"],
                                            "active"        => "1"
                                        );
                                        $db->AutoExecute("`course_group_audience`", $cgaudience, "INSERT");
                                    } elseif ($user["active"] == "0") {
                                        $db->AutoExecute("`course_group_audience`", array("active" => "1", "finish_date" => $this->course["finish_date"]), "UPDATE", "`cgaudience_id` = " . $db->qstr($user["cgaudience_id"]));
                                    }
                                }
                            }
                            
                            if (isset($current_cgroup_audience) && !empty($current_cgroup_audience)) {
                                foreach($current_cgroup_audience as $cgaudience_id => $proxy_id) {
                                    $db->AutoExecute("`course_group_audience`", array("active" => "0", "finish_date" => time()), "UPDATE", "`cgaudience_id` = ".$db->qstr($cgaudience_id));
                                }
                            }
                        }
                        $i++;
                    }
                }
            }
            $this->ldap_connection->Close();
        }
    }
    
    /**
     * Deletes a community audience member.
     * @global type $db
     * @param int $audience_member
     * @return boolean
     */
    private function removeCommunityAudienceMember($audience_member) {
        global $db;
        $query = "DELETE FROM `community_members` WHERE `community_id` = ? AND `proxy_id` = ?";
        if ($db->Execute($query, array($this->community_id, $audience_member))) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Creates user data / user access records
     * @global type $db
     * @param type $member_ldap_data
     * @return int $status
     */
    private function handleUser($member_ldap_data) {
        global $db;
        $number = str_replace("S", "", $member_ldap_data[LDAP_USER_QUERY_FIELD]);
        if ($number && $number > 0) {
            $GRAD = date("Y", time()) + 4;
            $user_id = "";
            $query = "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `number` = ?";
            $result = $db->GetRow($query, array($number));
            if (!$result) {
                if(isset($member_ldap_data["sn"]) && isset($member_ldap_data["givenName"]) && $member_ldap_data["sn"] && $member_ldap_data["givenName"]){
                    $names[0] = $member_ldap_data["givenName"];
                    $names[1] = $member_ldap_data["sn"];
                }else{
                    $names = explode(" ", $member_ldap_data["cn"], 2);
                }
                $student = array(	
                    "number"			=> $number,
                    "username"			=> strtolower($member_ldap_data[LDAP_MEMBER_ATTR]),
                    "password"			=> md5(generate_password(8)),
                    "organisation_id"	=> $this->course["organisation_id"],
                    "firstname"			=> trim($names[0]),
                    "lastname"			=> trim($names[1]),
                    "prefix"			=> "",
                    "email"				=> isset($member_ldap_data["mail"]) ? $member_ldap_data["mail"] : strtolower($member_ldap_data[LDAP_MEMBER_ATTR]) . "@queensu.ca",
                    "email_alt"			=> "",
                    "email_updated"		=> time(),
                    "telephone"			=> "",
                    "fax"				=> "",
                    "address"			=> "",
                    "city"				=> DEFAULT_CITY,
                    "postcode"			=> DEFAULT_POSTALCODE,
                    "country"			=> "",
                    "country_id"		=> DEFAULT_COUNTRY_ID,
                    "province"			=> "",
                    "province_id"		=> DEFAULT_PROVINCE_ID,
                    "notes"				=> "",
                    "privacy_level"		=> "0",
                    "notifications"		=> "0",
                    "entry_year"		=> date("Y", time()),
                    "grad_year"			=> $GRAD,
                    "gender"			=> "0",
                    "clinical"			=> "0",
                    "updated_date"		=> time(),
                    "updated_by"		=> "1"
                );
                if ($db->AutoExecute("`".AUTH_DATABASE."`.`user_data`", $student, "INSERT")) {
                    $user_id = $db->Insert_ID();
                    $access = array(
                        "user_id"			=> $user_id,
                        "app_id"			=> $this->app_id,
                        "organisation_id"	=> $this->course["organisation_id"],
                        "account_active"	=> "true",
                        "access_starts"		=> time(),
                        "access_expires"	=> "0",
                        "last_login"		=> "0",
                        "last_ip"			=> "",
                        "role"				=> $GRAD,
                        "group"				=> "student",
                        "extras"			=> "",
                        "private_hash"		=> generate_hash(32),
                        "notes"				=> ""
                    );
                    if ($db->AutoExecute("`".AUTH_DATABASE."`.`user_access`", $access, "INSERT")) {
                        application_log("error", "Failed to create user access record, DB said: ".$db->ErrorMsg());
                    }   
                } else {
                    application_log("error", "Failed to create user data record, DB said: ".$db->ErrorMsg());
                }
            } else {
                $user_id = $result["id"];
                $query = "SELECT * FROM `".AUTH_DATABASE."`.`user_access`
                            WHERE `user_id` = ".$db->qstr($result["id"])." AND `organisation_id` = ".$db->qstr($this->course["organisation_id"]);
                $access_record = $db->GetRow($query);
                if (!$access_record) {
                    $access = array(
                        "user_id"			=> $user_id,
                        "app_id"			=> $this->app_id,
                        "organisation_id"	=> $this->course["organisation_id"],
                        "account_active"	=> "true",
                        "access_starts"		=> time(),
                        "access_expires"	=> "0",
                        "last_login"		=> "0",
                        "last_ip"			=> "",
                        "role"				=> $GRAD,
                        "group"				=> "student",
                        "extras"			=> "",
                        "private_hash"		=> generate_hash(32),
                        "notes"				=> ""
                    );
                    if (!$db->AutoExecute("`".AUTH_DATABASE."`.`user_access`", $access, "INSERT")) {
                        application_log("error", "Failed to create user access record, DB said: ".$db->ErrorMsg());
                    }
                }
            }

            $query = "SELECT * FROM `group_members` 
                        WHERE `proxy_id` = ".$db->qstr($user_id)."
                        AND `group_id` = ".$db->qstr($this->group_id);
            $group_member = $db->GetRow($query);
            if (!$group_member) {
                $values = array(
                    "group_id"		=> $this->group_id,
                    "proxy_id"		=> $user_id,
                    "start_date"	=> $this->course["start_date"],
                    "expire_date"	=> $this->course["end_date"],
                    "member_active" => "1",
                    "entrada_only"	=> "0",
                    "updated_date"	=> time(),
                    "updated_by"	=> "1"
                );
                if (!$db->AutoExecute("group_members",$values,"INSERT")) {												
                    application_log("error", "User was not added to group_members table, DB said: ".$db->ErrorMsg());
                }
            } else if ($group_member["member_active"] == 0) {
                // group member has been deactivated, but is in the ldap results; reactivate the group membership.
                if (!$db->AutoExecute("group_members", array("member_active" => "1"), "UPDATE", "`gmember_id` = ".$db->qstr($group_member["gmember_id"]))) {
                    application_log("error", "group_members record [".$group_member["gmember_id"]."] was unable to be updated, DB said: ".$db->ErrorMsg());
                }
            }
            unset($this->course_audience[$user_id]);

            if ($this->community_id) {
                $query = "SELECT * FROM `community_members` WHERE `proxy_id` = ? AND `community_id` = ?";
                $community_membership = $db->GetRow($query, array($user_id, $this->community_id));
                if (!$community_membership) {
                    $values = array(
                        "community_id" => $this->community_id,
                        "proxy_id" => $user_id,
                        "member_active" => "1",
                        "member_joined" => time(),
                        "member_acl" => "0"
                    );
                    if (!$db->AutoExecute("`community_members`", $values, "INSERT")) {
                        application_log("error", "Failed to add user to community, DB said: ".$db->ErrorMsg());
                    }
                } else if ($community_membership["member_active"] == 0) {
                    if (!$db->AutoExecute("community_members", array("member_active" => "1"), "UPDATE", "`cmember_id` = " . $db->qstr($community_membership["cmember_id"]))) {
                        application_log("error", "community_members record [".$community_membership["cmember_id"]."] was unable to be updated, DB said: ".$db->ErrorMsg());
                    }
                }
            }
            unset($this->community_audience[$user_id]);
        } else {
            return false;
        }
    }
    
    /**
     * Creates the course audience
     * @global type $db
     * @return boolean
     */
    public function createCourseAudience() {
        global $db;
        $values = array(
            "course_id"         => $this->course["course_id"],
            "audience_type"     => "group_id",
            "audience_value"    => $this->group_id,
            "enroll_finish"     => $this->course["end_date"],
            "audience_active"   => "1",
            "cperiod_id"        => $this->course["cperiod_id"]
        );
        if ($db->AutoExecute("course_audience",$values,"INSERT")) {
            $this->course_audience_id = $db->Insert_ID();
            return true;
        } else {
            return false;
        }
    }
        
    /**
     * Fetch the user's details from the LDAP server.
     * @param type $member
     * @return array
     */
    private function fetchLdapAudienceMemberDetails($member) {
        if ($this->ldap_connection->Connect(LDAP_HOST, LDAP_SEARCH_DN, LDAP_SEARCH_DN_PASS, LDAP_PEOPLE_BASE_DN)) {
            $user = $this->ldap_connection->GetRow(LDAP_MEMBER_ATTR."=".$member);
            if (!$user) {
                echo $this->ldap_connection->ErrorMsg();
            }
        }
        $this->ldap_connection->Close();
        return $user;
    }
    
    /**
     * Fetch the course audience from the LDAP server
     * @return boolean
     */
    private function fetchLdapAudience() {
        if ($this->ldap_connection->Connect(LDAP_HOST, LDAP_SEARCH_DN, LDAP_SEARCH_DN_PASS, LDAP_GROUPS_BASE_DN)) {	
            foreach ($this->course_codes as $code) {
                $course_code_base   = clean_input($code, "alpha")."_".clean_input($code, "numeric");
                $search_query       = "cn=" . $course_code_base . "*". $this->ldap_code . "*";

                /**
                * Fetch course from LDAP server
                */
                $results = $this->ldap_connection->GetAll($search_query);
                if ($results) {
                    foreach ($results as $result) {
                        if (isset($result[LDAP_USER_IDENTIFIER]) && !is_array($result[LDAP_USER_IDENTIFIER])) {
                            $result[LDAP_USER_IDENTIFIER] = array($result[LDAP_USER_IDENTIFIER]);
                        }
                        if (isset($result[LDAP_USER_IDENTIFIER])) {
                            foreach ($result[LDAP_USER_IDENTIFIER] as $member) {
                                $uid = explode(",", $member);
                                $uuid = str_replace(strtolower(LDAP_MEMBER_ATTR)."=", "", strtolower($uid[0]));
                                if (!in_array($uuid, $this->ldap_audience)) {
                                    $this->ldap_audience[] = $uuid;
                                }
                            }
                        }
                    }
                }
            }
        } else {
            application_log("error", "Unable to connect to LDAP server, error: [" . $this->ldap_connection->ErrorMsg() . "]");
        }
        $this->ldap_connection->Close();
        if (!empty($this->ldap_audience)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Checks for CSV course codes, sets up array appropriately
     */
    private function fetchCourseCodes() {
        if (!empty($this->course["sync_ldap_courses"]) && !is_null($this->course["sync_ldap_courses"])) {
            $c_codes = explode(",", $this->course["sync_ldap_courses"]);
            foreach ($c_codes as $course_code) {
                $tmp_input = clean_input($course_code, array("trim", "alphanumeric"));
                if (!empty($tmp_input)) {
                    $this->course_codes[] = strtoupper($tmp_input);
                }
            }
            if (empty($this->course_codes)) {
                $this->course_codes[] = $this->course["course_code"];
            }
            return true;
        } elseif (!empty($this->course["course_code"])) {
            $this->course_codes[] = $this->course["course_code"];
            return true;
        } else {
            // courses should never be without course codes...
            return false;
        }
    }
    
    /**
     * Fetch the community audience members.
     * @global type $db
     * @return boolean
     */
    private function fetchCommunityAudience() {
        global $db;
        
        $query = "SELECT `proxy_id`, `cmember_id` FROM `community_members` WHERE `community_id` = ? AND `member_active` = '1' AND `member_acl` = '0'";
        $results = $db->GetAssoc($query, array($this->community_id));
        if ($results) {
            $this->community_audience = $results;
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 
     * @global type $db
     * @return boolean
     */
    private function fetchCommunityID() {
        global $db;
        
        $query = "SELECT `community_id` FROM `community_courses` WHERE `course_id` = ".$db->qstr($this->course["course_id"]);
    	$result = $db->GetOne($query);
        if ($result) {
            $this->community_id = $result;
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Get the current course audience
     * @global type $db
     * @return boolean
     */
    private function fetchCourseAudienceMembers() {
        global $db;
        
        /**
         * Fetch current audience that's attached to the course
         */
        $query = "	SELECT a.`id`, a.`number`, b.`member_active`, b.`entrada_only`, b.`gmember_id`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a 
                    JOIN `group_members` AS b	
                    ON a.`id` = b.`proxy_id` 
                    JOIN `groups` AS c 
                    ON b.`group_id` = c.`group_id`
                    WHERE c.`group_type` = 'course_list' 
                    AND c.`group_value` = ".$db->qstr($this->course["course_id"])."
                    AND c.`group_id` = ".$db->qstr($this->group_id);
        $audience = $db->GetAssoc($query);
        if ($audience) {
            $this->course_audience = $audience;
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Check for the existing course group ID
     * @global type $db
     * @return boolean
     */
    private function fetchGroupID() {
        global $db;
        
        $query = "SELECT `group_id` FROM `groups` WHERE `group_type` = 'course_list' AND `group_value` = ".$db->qstr($this->course["course_id"])." AND `group_name` LIKE '%".$this->course["course_code"].$this->suffix."%".$this->course_year."' ORDER BY `group_id` DESC";
        $this->group_id = $db->GetOne($query);
        if ($this->group_id === false) {
            $query = "SELECT `group_id` FROM `groups` WHERE `group_type` = 'course_list' AND `group_value` = ".$db->qstr($this->course["course_id"])." AND `group_name` LIKE '%".$this->course_year."' ORDER BY `group_id` DESC";
            $this->group_id = $db->GetOne($query);
        }
        
        if ($this->group_id) {
            return true;
        } else {
            return false;
        }
        
    }
    
    /**
     * Creates a course group and set the course group id.
     * @global type $db
     * @return boolean
     */
    private function createCourseGroup() {
        global $db;
        
        $group_values = array(
            "group_name"	=> $this->course["course_code"] . $this->suffix . " Class List " . $this->course_year,
            "group_type"	=> "course_list",
            "group_value"	=> $this->course["course_id"],
            "start_date"	=> $this->course["start_date"],
            "expire_date"	=> $this->course["end_date"],
            "group_active"	=> "1",
            "updated_date"	=> time(),
            "updated_by"	=> "1"
        );
        
        if ($db->AutoExecute("groups", $group_values, "INSERT")) {
            $this->group_id = $db->Insert_Id();
            
            $group_org_values						= array();
            $group_org_values["group_id"]			= $this->group_id;
            $group_org_values["organisation_id"]	= $this->course["organisation_id"];
            $group_org_values["updated_date"]		= time();
            $group_org_values["updated_by"]		= "1";

            if ($db->AutoExecute("group_organisations", $group_org_values, "INSERT")) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Set the LDAP query parameters
     * @param int $date 
     * @param int $org_id
     * @return bool
     */
    private function setQueryParams($date = NULL, $org_id = 1) {
        if (is_null($date)) {
            $date = time();
        }
        
        switch($org_id){
            case 4:
                $app = 700;
            break;
            case 5:
                $app = 101;
            break;
            case 9:
                $app = 105;
            break;
            case 1:
            default:
                $app = 1;
            break;
        }

        $this->app_id = $app;
        
        $m = date('n', $date);
        $params = false;
        
        switch(true){
            case $m < 4:
                $params = array("W", "_1_");
            break;
            case $m < 9:
                if ($org_id == 5 && $m < 6) {
                    $params = array("Sp", "_5_");
                }
                $params = array("S", "_5_");
            break;
            case $m < 12:
                $params = array("F", "_9_");
            break;
        }
        
        if ($params != false) {
            $this->suffix       = $params[0];
            $this->ldap_code    = $params[1];
            return true;
        } else {
            return false;
        }
        
    }
        
    /**
     * Synchronize a course by the course code rather than course id.
     * @global type $db
     * @param string $course_code alphanumeric course code
     * @param int $active active flag
     * @return boolean
     */
    public static function syncByCourseCode($course_code, $active = 1) {
        global $db;
        $query = "SELECT `course_id` FROM `courses` WHERE `course_code` = ? AND `active` = ?";
        $results = $db->GetRow($query, array($course_code, $active));
        if ($result) {
            return new self($result["course_id"]);
        } else {
            return false;
        }
    }
    
}

?>
