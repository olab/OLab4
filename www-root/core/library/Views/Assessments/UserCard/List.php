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
 * View class for rendering assessment users
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_UserCard_List extends Views_HTML
{
    protected $id, $class, $assessment_label = "%s assessments", $view_assessment_label = "%s assessments &rtrif;", $no_results_label = "No users found matching your search.", $users = array(), $group = "student", $logbook_url = null;

    /**
     * Get the assessment label
     * @return string
     */

    public function getAssessmentLabel()
    {
        return $this->assessment_label;
    }

    /**
     * Set the assessment label
     * @param $assessment_label
     */

    public function setAssessmentLabel($assessment_label)
    {
        $this->assessment_label = $assessment_label;
    }

    /**
     * Get the view assessment label
     * @return string
     */

    public function getViewAssessmentLabel()
    {
        return $this->view_assessment_label;
    }

    /**
     * Set the view assessment label
     * @param string $view_assessment_label
     */

    public function setViewAssessmentLabel($view_assessment_label)
    {
        $this->view_assessment_label = $view_assessment_label;
    }

    /**
     * Get the no search results label
     * @return string
     */

    public function getNoResultsLabel()
    {
        return $this->no_results_label;
    }

    /**
     * Set the no search results label
     * @param string $no_results_label
     */

    public function setNoResultsLabel($no_results_label)
    {
        $this->no_results_label = $no_results_label;
    }

    /**
     * Get the users
     * @return array
     */

    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set the users
     * @param array $learners
     */

    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * Get the group
     * @return string
     */

    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set the group
     * @param $group
     */

    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * Get the logbook url
     * @return string
     */

    public function getLogbookUrl()
    {
        return $this->logbook_url;
    }

    /**
     * Set the logbook url
     * @param $logbook_url
     */

    public function setLogbookUrl($logbook_url)
    {
        $this->logbook_url = $logbook_url;
    }

    /**
     * Generate the html for the learner list item
     * @param array $options
     * @return string
     */

    protected function renderView($options = array())
    {
        global $translate;
        $ENTRADA_URL = ENTRADA_URL;
        $html = array();

        if ($this->getUsers()) {
            $photo_class = "class=\"img-circle\"";
            $name_size = "h3";
            $pdf_user_name_class = "";
            $cache = new Entrada_Utilities_Cache();

            $html[] = "<ul id=\"" . html_encode($this->getID()) . "\" class=\"user-list-card\">";
            foreach ($this->getUsers() as $user) {
                $user_id = $user["id"];
                $external = isset($user["type"]) && $user["type"] == "external";

                $user_image_set = true;
                $image_data = $cache->loadCache($user_id);
                if ($image_data === false) {
                    $user_image_set = false;
                    $image_data = $cache->loadCache("default_photo");
                }
                $mime_type = $image_data["mime_type"];
                $encoded_image = $image_data["photo"];

                if (isset($options["hide"])) {
                    $photo_class = "";
                    $name_size = "h5";
                    $pdf_user_name_class = "class=\"pdf-user-name\"";
                }

                if (!$external) {
                    $html[] = "<li class=\"" . html_encode($this->getClass()) . "\" data-cperiod_ids=\"" . (!$external && isset($user["cperiod_ids"]) && $user["cperiod_ids"] ? html_encode(implode("-", $user["cperiod_ids"])) : "") . "\">";
                    $html[] = "    <div class=\"user-card-wrapper\">";
                    $html[] = "        <div class=\"user-card-container\">";

                    if ($user_image_set):
                        $html[] = "            <img id=\"user-photo-{$user_id}\" src=\"data:{$mime_type};base64,{$encoded_image}\" $photo_class width=\"42\" />";
                    else:
                        $html[] = "            <div class=\"user-photo-upload-container\">";
                        $html[] = "                <img id=\"user-photo-{$user_id}\" src=\"data:{$mime_type};base64,{$encoded_image}\" $photo_class width=\"42\" />";
                        $html[] = "                <a id=\"upload-user-photo-{$user_id}\" href=\"#upload-image\" data-toggle=\"modal\" data-proxy-id=\"{$user_id}\" class=\"upload-image-modal-btn hide\">";
                        $html[] = "                    <i class=\"fa fa-upload\" aria-hidden=\"true\" data-toggle=\"tooltip\" title=\"" . html_encode($translate->_("Upload User Photo")) . "\"></i>";
                        $html[] = "                </a>";
                        $html[] = "            </div>";
                    endif;

                    $html[] = "            <$name_size $pdf_user_name_class>" . html_encode($user["lastname"]) . ", " . html_encode($user["firstname"]) . ($this->group == "student" && !isset($options["hide"]) ? "<span>" . html_encode($user["number"]) . "</span>" : "") . "</$name_size>";
                    $html[] = "            <a href=\"mailto:" . html_encode($user["email"]) . "\">" . html_encode($user["email"]) . "</a><span " . ($user["cbme"] && array_key_exists("stage_name", $user) ? "data-toggle=\"tooltip\" title=\"" . html_encode($user["stage_name"]) . "\"" : "") . " class=\"learner-level-badge pull-right " . ($user["cbme"] ? "cbme" : "") . "\">" . ($user["cbme"] && array_key_exists("stage_code", $user) && !empty($user["stage_code"]) ? html_encode($user["stage_code"]) . " &bull; " : "") . html_encode($user["learner_level"]) . "</span>";
                    $html[] = "         </div>";

                    if (!isset($options["hide"])) {
                        $logbook_label = $translate->_("View Logbook");
                        $cbme_enabled = (new Entrada_Settings)->read("cbme_enabled");
                        if ($this->group == "student") {
                            if (count(Models_Logbook_Entry::fetchAll($user_id)) > 0) {
                                $html[] = " <div class=\"user-card-parent\">";
                                if ($cbme_enabled) {
                                    $html[] = "     <div class=\"user-card-child user-card-child-divider\">";
                                    $html[] = "         <a data-id=\"" . $user["id"] . "\" href=\"" . html_encode($ENTRADA_URL . "/assessments/learner/cbme?proxy_id=" . $user["id"]) . "\" class=\"all-assessments learner-dashboard\">" . $translate->_("CBME Dashboard") . " &rtrif; " . "</a>";
                                    $html[] = "     </div>";
                                }
                                $html[] = "     <div class=\"user-card-child user-card-child-divider\">";
                                $html[] = "         <a href=\"" . html_encode($ENTRADA_URL . "/assessments/learner?proxy_id=" . $user["id"]) . "\" class=\"all-assessments\">" . $this->getViewAssessmentLabel() . "</a>";
                                $html[] = "     </div>";
                                $html[] = "     <div class=\"user-card-child\">";
                                $html[] = "         <a href=\"" . html_encode($this->getLogbookUrl() . $user_id) . "\" class=\"all-assessments\">$logbook_label &rtrif;</a>";
                                $html[] = "     </div>";
                                $html[] = " </div>";
                            } else {
                                $html[] = "<div class=\"user-card-parent\">";
                                if ($cbme_enabled) {
                                    $html[] = "     <div class=\"user-card-child user-card-child-divider\">";
                                    $html[] = "         <a data-id=\"" . $user["id"] . "\" href=\"" . html_encode($ENTRADA_URL . "/assessments/learner/cbme?proxy_id=" . $user["id"]) . "\" class=\"all-assessments learner-dashboard\">" . $translate->_("CBME Dashboard") . " &rtrif; " . "</a>";
                                    $html[] = "     </div>";
                                }
                                $html[] = "     <div class=\"user-card-child\">";
                                $html[] = "         <a href=\"" . html_encode($ENTRADA_URL . "/assessments/learner?proxy_id=" . $user["id"]) . "\" class=\"all-assessments\">" . $this->getViewAssessmentLabel() . "</a>";
                                $html[] = "     </div>";
                                $html[] = "</div>";
                            }
                        } else {
                            $internal_label = $translate->_("Internal");
                            $html[] = "<div class=\"user-card-parent\">";
                            $html[] = "    <a href=\"" . html_encode($ENTRADA_URL . "/assessments/faculty?proxy_id=" . $user["id"]) . "\" class=\"all-assessments\">" . $this->getViewAssessmentLabel() . "</a>";
                            $html[] = "</div>";
                            $html[] = "<div>";
                            $html[] = "    <span class=\"assessor-type-badge hide\">$internal_label</span>";
                            $html[] = "</div>";
                        }
                    }
                    $html[] = "    </div>";
                    $html[] = "</li>";
                } else {
                    if (!isset($options["hide"])) {
                        $hide_card_class = "";
                        $hide_card_label = $translate->_("Hide Card");
                        $external_label = $translate->_("External");
                        $email_label = $translate->_("Update Email");
                        if (isset($options["hidden_external_assessor_id_list"])) {
                            if (in_array($user_id, $options["hidden_external_assessor_id_list"])) {
                                $hide_card_class = "hide hidden";
                                $hide_card_label = $translate->_("Unhide Card");
                            }
                        }
                        $html[] = "<li class=\"faculty-card $hide_card_class\">";
                        $html[] = "    <div class=\"user-card-wrapper\">";
                        $html[] = "        <div class=\"user-card-container\">";
                        $html[] = "            <div>";
                        $html[] = "                <span class=\"assessor-type-badge pull-right\">$external_label</span>";
                        $html[] = "            </div>";
                        $html[] = "            <img src=\"data:{$mime_type};base64,{$encoded_image}\" $photo_class width=\"32\" />";
                        $html[] = "            <h3>" . html_encode(html_encode($user["lastname"]) . ", " . $user["firstname"]) . "</h3>";
                        $html[] = "            <a class=\"external-email\" href=\"mailto:" . html_encode($user["email"]) . "\">" . html_encode($user["email"]) . "</a>";
                        $html[] = "        </div>";
                        $html[] = "        <div class=\"faculty-card-edit user-card-parent all-assessments\">";
                        $html[] = "            <div class=\"user-card-child faculty-card-child-divider external-assessments-tasks\">";
                        $html[] = "                <a href=\"" . $options["url"] . "/assessments/faculty?proxy_id=" . html_encode($user_id) . "&external=true\">" . $this->getViewAssessmentLabel() . "</a>";
                        $html[] = "            </div>";
                        $html[] = "            <div class=\"user-card-child faculty-card-child-divider change-external-assessor-visibility-wrap\">";
                        $html[] = "                <a href=\"javascript:void(0);\" class=\"change-external-assessor-visibility\" data-proxy-id=\"" . html_encode($user_id) . "\">$hide_card_label</a>";
                        $html[] = "            </div>";
                        $html[] = "            <div class=\"user-card-child update-external-assessor-email-wrap\">";
                        $html[] = "                 <a href=\"javascript:void(0);\" class=\"update-external-assessor-email\" data-proxy-id=\"" . html_encode($user_id) . "\">$email_label</a>";
                        $html[] = "            </div>";
                        $html[] = "        </div>";
                        $html[] = "    </div>";
                        $html[] = "</li>";
                    }
                }
            }
            $html[] = "<div class=\"clearfix\"></div>";
            $html[] = "</ul>";
        } else {
            $html[] = "<p class=\"no-search-targets\">" . html_encode($this->getNoResultsLabel()) . "</p>";
        }

        echo implode("\n", $html);
    }
}