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
 * This is an abstraction layer for handling assessment user related data.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Assessments_Users extends Entrada_Assessments_Base {
    /**
     * This function handles a preceptor access request by either creating a new
     * faculty => lecturer user_access record or by notifying all course PAs via Email
     * when a user_data record isn't found, or their faculty => lecturer user_access record is not active.
     *
     * @param int $organisation_id
     * @param string $email
     * @param string $email_alt
     * @param int $number
     * @return array
     */
    public function handlePreceptorAccessRequest($proxy_id = 0, $organisation_id = 0, $access_request_record = array()) {
        /**
         * Get all PAs for a supplied course
         */
        $contacts = $this->determineContacts($proxy_id, $organisation_id, $access_request_record["course_id"], "pcoordinator");

        /**
         * Check to see if the requested user exists in the system already based on the supplied email and number
         */
        $preceptor = $this->fetchPreceptor($access_request_record["requested_user_email"], $access_request_record["requested_user_email"], $access_request_record["requested_user_number"]);
        if ($preceptor) {

            /**
             * The requested user exists so check if they have faculty lecturer access
             */
            $faculty_lecturer_access_record = $this->fetchUserGroupRole($preceptor["id"], $organisation_id, "lecturer", "faculty");
            if ($faculty_lecturer_access_record) {

                /**
                 * The requested user has faculty lecturer access so check if that access record is active
                 */
                if ($faculty_lecturer_access_record["account_active"] == "true") {

                    /**
                     * The user_access record is active so return appropriate data so we can automatically select this user in
                     * the advancedSearch widget
                     */
                    return array("proxy_id" => $preceptor["id"], "name" => $preceptor["firstname"] . " " . $preceptor["lastname"]);
                } else {

                    /**
                     * The user_access record was inactive so queue a notification for all course PA's to inform them of this
                     */
                    if ($contacts) {
                        foreach ($contacts as $contact_proxy_id) {

                            /**
                             * Check to see if there is already a user_access_request record for this user and the person they are requesting access for
                             */
                            if (!$this->accessRequestRecordExists($contact_proxy_id, $access_request_record["requested_user_email"], $access_request_record["requested_group"], $access_request_record["requested_role"])) {
                                $access_request_record["receiving_proxy_id"] = $contact_proxy_id;
                                if ($preceptor_access = $this->savePreceptorAccessRequest($access_request_record)) {
                                    $this->queueInactiveFacultyLecturerNotification($contact_proxy_id, $preceptor_access->getID());
                                }
                            }
                        }
                    }

                    return array();
                }
            } else {

                /**
                 * The requested user does not have faculty lecturer access, so grant it to them
                 */
                $user_access_array = array(
                    "user_id" => $preceptor["id"],
                    "app_id" => 1,
                    "organisation_id" => $organisation_id,
                    "account_active" => "true",
                    "access_starts" => time(),
                    "access_expires" => 0,
                    "last_login" => 0,
                    "last_ip" => 0,
                    "login_attempts" => NULL,
                    "locked_out_until" => NULL,
                    "role" => "lecturer",
                    "group" => "faculty",
                    "extras" => "",
                    "private_hash" => generate_hash(),
                    "notes" => "Access record created from Preceptor Access Request"
                );

                if ($this->saveUserAccessRecord($user_access_array)) {
                    return array("proxy_id" => $preceptor["id"], "name" => $preceptor["firstname"] . " " . $preceptor["lastname"]);
                }
            }
        }

        /**
         * No preceptor was found in the system matching the provided information, so save user_access_request records
         * and queue a notification for each course PA.
         */
        if ($contacts) {
            foreach ($contacts as $contact_proxy_id) {

                /**
                 * Check to see if there is already a user_access_request record for this user and the person they are requesting access for
                 */
                if (!$this->accessRequestRecordExists($contact_proxy_id, $access_request_record["requested_user_email"], $access_request_record["requested_group"], $access_request_record["requested_role"])) {
                    $access_request_record["receiving_proxy_id"] = $contact_proxy_id;
                    if ($preceptor_access = $this->savePreceptorAccessRequest($access_request_record)) {
                        // Email all course PAs
                        $this->queueNoUserFoundNotification($contact_proxy_id, $preceptor_access->getID());
                    }
                }
            }
        }

        return array();
    }

    /**
     * This function fetches a user_data record based on the provided number, email or email_alt
     * @param array $search_fileds
     * @return bool
     */
    private function fetchPreceptor($email = "", $email_alt = "", $number = 0) {
        global $db;
        $user = array();
        $query_params = array();
        $query = "SELECT * FROM `". AUTH_DATABASE ."`.`user_data` WHERE ". ($number ? "number = ? OR" : "") . " `email` = ? OR `email_alt` = ?";

        if ($number) {
            $query_params[] = $number;
        }
        $query_params[] = $email;
        $query_params[] = $email_alt;

        $result = $db->GetRow($query, $query_params);
        if ($result) {
            $user = $result;
        }
        return $user;
    }

    /**
     * @param int $proxy_id
     * @param int $organisation_id
     * @param string $role
     * @param string $group
     * @return array
     */
    private function fetchUserGroupRole($proxy_id = 0, $organisation_id = 0, $role = "", $group = "") {
        $user_access = array();
        $user_access_record = Models_User_Access::fetchRowByUserIDOrganisationIDRoleGroupIgnoreActive($proxy_id, $organisation_id, $role, $group);
        if ($user_access_record) {
            $user_access = $user_access_record->toArray();
        }
        return $user_access;
    }

    /**
     * This function saves a user_access record for a user
     * @param array $record -  the user access record array
     * @return Models_User_Access
     */
    private function saveUserAccessRecord($record = array()) {
        global $db, $translate;
        $user_access_model = new Models_User_Access($record);
        if (!$user_access_model->insert()) {
            add_error($translate->_("An error occurred while attempting to create an access record for this user. Please try again later."));
            application_log("error", "An error occurred while attempting to create a " . $record["group"] . " " . $record["role"] . " access record for user " . $record["id"] . ". DB said: " . $db->ErrorMsg());
            return false;
        } else {
            return $user_access_model;
        }
    }

    /**
     * This function saves a user_access_requests record for a user
     * @param array $record -  the user access request record array
     * @return Models_User_Access
     */
    private function savePreceptorAccessRequest($record = array()) {
        global $db, $translate;
        $user_access_request_model = new Models_User_Access_Request($record);
        if (!$user_access_request_model->insert()) {
            add_error($translate->_("An error occurred while attempting to create an access request for this user. Please try again later."));
            application_log("error", "An error occurred while attempting to create a user access request DB said: " . $db->ErrorMsg());
            return false;
        }

        return $user_access_request_model;
    }

    /**
     * This function checks for the existence of a user_access_request based on the receiving_proxy_id, requested_user_email, requested_group, requested_role
     *
     * @param int $proxy_id
     * @param string $email
     * @param string $group
     * @param string $role
     * @return bool
     */
    private function accessRequestRecordExists($proxy_id = 0, $email = "", $group = "", $role = "") {
        $record_exists = false;
        $user_access_request_model = new Models_User_Access_Request();
        $user_access_request = $user_access_request_model->fetchRowByProxyIDEmailGroupRole($proxy_id, $email, $group, $role);
        if ($user_access_request) {
            $record_exists = true;
        }
        return $record_exists;
    }

    /**
     * This function checks for default_course_contacts in the course_settings table and returns those users,
     * if there is no default_course_contacts value in the cbme_request_preceptor_access setting it returns all PAs for a course
     *
     * @param int $course_id
     * @param string $contact_type
     * @return array
     */
    private function determineContacts($proxy_id = 0, $organisation_id = 0, $course_id = 0, $contact_type = "pcoordinator") {
        /**
         * The contact proxy_ids to return
         */
        $contact_ids = array();

        /**
         * Instantiate the CBME visualization abstraction layer
         */
        $cbme_progress_api = new Entrada_CBME_Visualization(array(
            "actor_proxy_id" => $proxy_id,
            "actor_organisation_id" => $organisation_id,
            "datasource_type" => "progress"
        ));

        /**
         * Fetch the cbme_request_preceptor_access for this course
         */
        $request_access_setting = $cbme_progress_api->fetchCourseSettingsByShortname($course_id, "cbme_request_preceptor_access");

        /**
         * If the course has cbme_request_preceptor_access settings check to see if they have default course contacts,
         * if they do then populate the $contact_ids array
         */
        if ($request_access_setting) {
            $settings = @json_decode($request_access_setting["value"], true);
            if ($settings && is_array($settings) && array_key_exists("default_course_contacts", $settings)) {
                if ($settings["default_course_contacts"]) {
                    foreach ($settings["default_course_contacts"] as $proxy_id) {
                        $contact_ids[] = $proxy_id;
                    }
                }
            } else {

                /**
                 * There is a cbme_request_preceptor_access setting for this course, but there are no default_course_contacts set
                 * so populate $contact_ids with all PAs from the course
                 */
                $contact_ids = $this->getCourseContactIDs($course_id, $contact_type);
            }
        } else {

            /**
             * There is no cbme_request_preceptor_access setting for this course so populate $contact_ids with all
             * PAs from the course
             */
            $contact_ids = $this->getCourseContactIDs($course_id, $contact_type);
        }

        return $contact_ids;
    }

    /**
     * This function fetches course contacts by course_id and contact type
     * @param int $course_id
     * @param string $contact_type
     * @return array
     */
    private function getCourseContactIDs($course_id = 0, $contact_type = "pccordinator") {
        $contact_ids = array();
        $contact_pcoordinators = Models_Course_Contact::fetchAllByCourseIDContactType($course_id, $contact_type);
        if ($contact_pcoordinators) {
            foreach ($contact_pcoordinators as $contact_pcoordinator) {
                $contact_ids[] = $contact_pcoordinator->getProxyID();
            }
        }

        return $contact_ids;
    }
}