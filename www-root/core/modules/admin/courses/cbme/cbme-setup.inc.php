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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CBME"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"%s\">%s</a> for assistance."), "mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $ajax = (isset($_GET["ajax"]) && $_GET["ajax"] ? true : false);

    $cbme_checked = false;
    //Required tags and tag sets for CBME to function
    $cbme_tag_sets = [];
    $cbme_tag_sets[] = ["title" => "Royal College Entrustable Professional Activities", "shortname" => "epa", "standard" => 0];
    $cbme_tag_sets[] = ["title" => "CanMEDS Key Competencies", "shortname" => "kc", "standard" => 1];
    $cbme_tag_sets[] = ["title" => "CanMEDS Enabling Competencies", "shortname" => "ec", "standard" => 1];
    $cbme_tag_sets[] = ["title" => "CanMEDS Milestones", "shortname" => "milestone", "standard" => 0];
    $cbme_tag_sets[] = ["title" => "CanMEDS Roles", "shortname" => "role", "standard" => 1];
    $cbme_tag_sets[] = ["title" => "Royal College Stages", "shortname" => "stage", "standard" => 1];
    $cbme_tag_sets[] = ["title" => "Contextual Variables", "shortname" => "contextual_variable", "standard" => 1];
    $cbme_tag_sets[] = ["title" => "Contextual Variable Responses", "shortname" => "contextual_variable_responses", "standard" => 0];
    $cbme_tag_sets[] = ["title" => "Procedure Attribute", "shortname" => "procedure_attribute", "standard" => 0];

    $role_tags = [];
    $role_tags[] = ["objective_code" => "HA", "objective_name" => "Health Advocate", "objective_description" => "Physicians are accountable to society and recognize their duty to contribute to efforts to improve the health and well-being of their patients, their communities, and the broader populations they serve.* Physicians possess medical knowledge and abilities that provide unique perspectives on health. Physicians also have privileged access to patients? accounts of their experience with illness and the health care system. Improving health is not limited to mitigating illness or trauma, but also involves disease prevention, health promotion, and health protection. Improving health also includes promoting health equity, whereby individuals and populations reach their full health potential without being disadvantaged by, for example, race, ethnicity, religion, gender, sexual orientation, age, social class, economic status, or level of education. Physicians leverage their position to support patients in navigating the health care system and to advocate with them to access appropriate resources in a timely manner. Physicians seek to improve the quality of both their clinical practice and associated organizations by addressing the health needs of the patients, communities, or populations they serve. Physicians promote healthy communities and populations by influencing the system (or by supporting others who influence the system), both within and outside of their work environments. Advocacy requires action. Physicians contribute their knowledge of the determinants of health to positively influence the health of the patients, communities, or populations they serve. Physicians gather information and perceptions about issues, working with patients and their families to develop an understanding of needs and potential mechanisms to address these needs. Physicians support patients, communities, or populations to call for change, and they speak on behalf of others when needed. Physicians increase awareness about important health issues at the patient, community, or population level. They support or lead the mobilization of resources (e.g. financial, material, or human resources) on small or large scales. Physician advocacy occurs within complex systems and thus requires the development of partnerships with patients, their families and support networks, or community agencies and organizations to influence health determinants. Advocacy often requires engaging other health care professionals, community agencies, administrators, and policy-makers.",
        "objective_order" => 4];
    $role_tags[] = ["objective_code" => "PR", "objective_name" => "Professional", "objective_description" => "Physicians serve an essential societal role as professionals dedicated to the health and care of others.Their work requires mastery of the art, science, and practice of medicine. A physician?s professional identity is central to this Role. The Professional Role reflects contemporary society?s expectations of physicians, which include clinical competence, a commitment to ongoing professional development, promotion of the public good, adherence to ethical standards, and values such as integrity, honesty, altruism, humility, respect for diversity, and transparency with respect to potential conflicts of interest. It is also recognized that, to provide optimal patient care, physicians must take responsibility for their own health and well-being and that of their colleagues. Professionalism is the basis of the implicit contract between society and the medical profession, granting the privilege of physician-led regulation with the understanding that physicians are accountable to those served, to society, to their profession, and to themselves.",
        "objective_order" => 6];
    $role_tags[] = ["objective_code" => "SC", "objective_name" => "Scholar", "objective_description" => "Physicians acquire scholarly abilities to enhance practice and advance health care. Physicians pursue excellence by continually evaluating the processes and outcomes of their daily work, sharing and comparing their work with that of others, and actively seeking feedback in the interest of quality and patient safety. Using multiple ways of learning, they strive to meet the needs of individual patients and their families* and of the health care system. Physicians strive to master their domains of expertise and to share their knowledge. As lifelong learners, they implement a planned approach to learning in order to improve in each CanMEDS Role. They recognize the need to continually learn and to model the practice of lifelong learning for others. As teachers they facilitate, individually and through teams, the education of students and physicians in training, colleagues, co-workers, the public, and others. Physicians are able to identify pertinent evidence, evaluate it using specific criteria, and apply it in their practice and scholarly activities. Through their engagement in evidence-informed and shared decision-making, they recognize uncertainty in practice and formulate questions to address knowledge gaps. Using skills in navigating information resources, they identify evidence syntheses that are relevant to these questions and arrive at clinical decisions that are informed by evidence while taking patient values and preferences into account. Finally, physicians? scholarly abilities allow them to contribute to the application, dissemination, translation, and creation of knowledge and practices applicable to health and health care.",
        "objective_order" => 5];
    $role_tags[] = ["objective_code" => "LD", "objective_name" => "Leader", "objective_description" => "The CanMEDS Leader Role describes the engagement of all physicians in shared decisionmaking for the operation and ongoing evolution of the health care system. As a societal expectation, physicians demonstrate collaborative leadership and management within the health care system. At a system level, physicians contribute to the development and delivery of continuously improving health care and engage with others in working toward this goal. Physicians integrate their personal lives with their clinical, administrative, scholarly, and teaching responsibilities. They function as individual care providers, as members of teams, and as participants and leaders in the health care system locally, regionally, nationally, and globally",
        "objective_order" => 3];
    $role_tags[] = ["objective_code" => "CL", "objective_name" => "Collaborator", "objective_description" => "Collaboration is essential for safe, high-quality, patientcentred care, and involves patients and their families,* physicians and other colleagues in the health care professions, community partners, and health system stakeholders. Collaboration requires relationships based in trust, respect, and shared decision-making among a variety of individuals with complementary skills in multiple settings across the continuum of care. It involves sharing knowledge, perspectives, and responsibilities, and a willingness to learn together. This requires understanding the roles of others, pursuing common goals and outcomes, and managing differences. Collaboration skills are broadly applicable to activities beyond clinical care, such as administration, education, advocacy, and scholarship.",
        "objective_order" => 2];
    $role_tags[] = ["objective_code" => "CM", "objective_name" => "Communicator", "objective_description" => "Physicians enable patient-centred therapeutic communication by exploring the patient?s symptoms, which may be suggestive of disease, and by actively listening to the patient?s experience of his or her illness. Physicians explore the patient?s perspective, including his or her fears, ideas about the illness, feelings about the impact of the illness, and expectations of health care and health care professionals. The physician integrates this knowledge with an understanding of the patient?s context, including socio-economic status, medical history, family history, stage of life, living situation, work or school setting, and other relevant psychological and social issues. Central to a patient-centred approach is shared decision-making: finding common ground with the patient in developing a plan to address his or her medical problems and health goals in a manner that reflects the patient?s needs, values, and preferences. This plan should be informed by evidence and guidelines. Because illness affects not only patients but also their families, physicians must be able to communicate effectively with everyone involved in the patient?s care.",
        "objective_order" => 1];
    $role_tags[] = ["objective_code" => "ME", "objective_name" => "Medical Expert", "objective_description" => "As Medical Experts who provide high-quality, safe, patient-centred care, physicians draw upon an evolving body of knowledge, their clinical skills, and their professional values. They collect and interpret information, make clinical decisions, and carry out diagnostic and therapeutic interventions. They do so within their scope of practice and with an understanding of the limits of their expertise. Their decision-making is informed by best practices and research evidence, and takes into account the patient?s circumstances and preferences as well as the availability of resources. Their clinical practice is up-to-date, ethical, and resourceefficient, and is conducted in collaboration with patients and their families,* other health care professionals, and the community. The Medical Expert Role is central to the function of physicians and draws on the competencies included in the Intrinsic Roles (Communicator, Collaborator, Leader, Health Advocate, Scholar, and Professional).",
        "objective_order" => 0];

    $stage_tags = [];
    $stage_tags[] = ["objective_code" => "D", "objective_name" => "Transition to Discipline", "objective_description" => "",
        "objective_order" => 0];
    $stage_tags[] = ["objective_code" => "F", "objective_name" => "Foundations of Discipline", "objective_description" => "",
        "objective_order" => 1];
    $stage_tags[] = ["objective_code" => "C", "objective_name" => "Core Discipline", "objective_description" => "",
        "objective_order" => 2];
    $stage_tags[] = ["objective_code" => "P", "objective_name" => "Transition to Practice", "objective_description" => "",
        "objective_order" => 3];

    $contextual_variable_tags = [];
    $contextual_variable_tags[] = ["objective_code" => "diagnosis", "objective_name" => "Diagnosis", "objective_description" => "Diagnosis",
        "objective_order" => 0];
    $contextual_variable_tags[] = ["objective_code" => "clinical_presentation", "objective_name" => "Clinical Presentation", "objective_description" => "Clinical Presentation",
        "objective_order" => 1];
    $contextual_variable_tags[] = ["objective_code" => "organ_system", "objective_name" => "Organ System", "objective_description" => "Organ System",
        "objective_order" => 2];
    $contextual_variable_tags[] = ["objective_code" => "procedure", "objective_name" => "Procedure", "objective_description" => "Procedure",
        "objective_order" => 3];
    $contextual_variable_tags[] = ["objective_code" => "scope_of_assessment", "objective_name" => "Scope of Assessment", "objective_description" => "Scope of Assessment",
        "objective_order" => 4];
    $contextual_variable_tags[] = ["objective_code" => "basis_of_assessment", "objective_name" => "Basis of Assessment", "objective_description" => "Basis of Assessment",
        "objective_order" => 5];
    $contextual_variable_tags[] = ["objective_code" => "clinical_setting", "objective_name" => "Clinical Setting", "objective_description" => "Clinical Setting",
        "objective_order" => 6];
    $contextual_variable_tags[] = ["objective_code" => "case_complexity", "objective_name" => "Case Complexity", "objective_description" => "Case Complexity",
        "objective_order" => 7];
    $contextual_variable_tags[] = ["objective_code" => "technical_difficulty", "objective_name" => "Technical Difficulty", "objective_description" => "Technical Difficulty",
        "objective_order" => 8];
    $contextual_variable_tags[] = ["objective_code" => "assessors_role", "objective_name" => "Assessor's Role", "objective_description" => "Assessor's Role",
        "objective_order" => 9];
    $contextual_variable_tags[] = ["objective_code" => "patient_demographics", "objective_name" => "Patient Demographics", "objective_description" => "Patient Demographics",
        "objective_order" => 10];
    $contextual_variable_tags[] = ["objective_code" => "encounters_with_resident", "objective_name" => "Encounters With Resident", "objective_description" => "Encounters With Resident",
        "objective_order" => 11];
    $contextual_variable_tags[] = ["objective_code" => "case_type", "objective_name" => "Case Type", "objective_description" => "Case Type",
        "objective_order" => 12];

    $cbme_tags = [];
    $cbme_tags["role"] = $role_tags;
    $cbme_tags["stage"] = $stage_tags;
    $cbme_tags["contextual_variable"] = $contextual_variable_tags;

    $form_types = Models_Assessments_Form_Type::fetchAllByCategories(["blueprint", "cbme_form"]);
    $assessment_type = Models_Assessments_Type::fetchAssessmentTypeIDByShortname("cbme");
    $assessment_method_model = new Models_Assessments_Method();
    $assessments_method = $assessment_method_model->fetchAllRecords();

    $organisation_id = $ENTRADA_USER->getActiveOrganisation();

    if ($ajax) {
        ob_clear_open_buffers();
        if ($_POST["method"] && $_POST["method"] == "cbme-objective-sets-setup") {

            //support languages
            $json_data = Entrada_Settings::fetchValueByShortname("language_supported");
            if ($json_data) {
                $language_supported = json_decode($json_data, true);
                $languages = [];
                foreach ($language_supported as $index => $value) {
                    $languages[] = $index;
                }
            }

            foreach ($cbme_tag_sets as $objective_set) {
                $obj_set = Models_ObjectiveSet::fetchRowByShortname($objective_set["shortname"], null, true);

                //requirements field
                $requirements = [];
                $requirements["code"] = ($objective_set["shortname"] == "kc" || $objective_set["shortname"] == "ec" || $objective_set["shortname"] == "contextual_variable" ? ["required" => true] : ["required" => false]);
                $requirements["title"] = ["required" => true];
                $requirements["description"] = ["required" => false];

                if (!$obj_set) {
                    $objective_set_arr = array(
                        "title" => $objective_set["title"],
                        "description" => $objective_set["title"],
                        "shortname" => $objective_set["shortname"],
                        "maximum_levels" => 1,
                        "short_method" => "%t",
                        "long_method" => "<h4 class=\"tag-title\">%t</h4><p class=\"tag-description\">%d</p>",
                        "languages" => json_encode($languages, JSON_FORCE_OBJECT),
                        "requirements" => json_encode($requirements),
                        "start_date" => null,
                        "end_date" => null,
                        "standard" => $objective_set["standard"],
                        "created_date" => time(),
                        "updated_date" => time(),
                        "created_by" => $ENTRADA_USER->getActiveId(),
                        "updated_by" => $ENTRADA_USER->getActiveId()
                    );

                    $obj_set = new Models_ObjectiveSet($objective_set_arr);
                    if (!$obj_set->insert()) {
                        add_error($translate->_("An error occurred while trying to insert a new objective set."));
                        application_log("error", "There was an error inserting an objective set. Database said: " . $db->ErrorMsg());
                    }
                } else if ($obj_set && $obj_set->getDeletedDate() != NULL) {
                    $obj_set->setUpdatedDate(time());
                    $obj_set->setUpdatedBy($ENTRADA_USER->getActiveID());
                    $obj_set->setDeletedDate(null);
                    $update_obj_set = true;
                }
                if (!has_error() && $obj_set->getTitle() != $objective_set["title"]) {
                    $obj_set->fromArray(array("title" => $objective_set["title"], "description" => $objective_set["title"]));
                    $update_obj_set = true;
                }
                if (!has_error() && json_decode($obj_set->getRequirements(), true) != json_encode($requirements)) {
                    $obj_set->fromArray(array("requirements" => json_encode($requirements)));
                    $update_obj_set = true;
                }
                if (isset($update_obj_set) && $update_obj_set && !$obj_set->update()) {
                    add_error($translate->_("An error occurred while trying to update an objective set."));
                    application_log("error", "There was an error updating an objective set. Database said: " . $db->ErrorMsg());
                }
                if (!has_error() && $obj_set) {
                    $tag_first_level = Models_Objective::fetchRowBySetIDParentID($obj_set->getID(), 0);

                    $objective = [];
                    $objective["objective_code"] = $obj_set->getCode();
                    $objective["objective_name"] = $obj_set->getTitle();
                    $objective["objective_description"] = $obj_set->getDescription();
                    $objective["objective_set_id"] = $obj_set->getID();
                    $objective["objective_order"] = 0;
                    $objective["objective_status_id"] = Entrada_Settings::read("curriculum_tags_default_status");
                    $objective["updated_date"] = time();
                    $objective["updated_by"] = $ENTRADA_USER->getActiveId();

                    if (!$tag_first_level) {
                        $objective["objective_parent"] = 0;

                        $tag_first_level = new Models_Objective();
                        $tag_first_level->fromArray($objective);
                        if ($tag_first_level->insert()) {
                            $tag_first_level->insertOrganisationId($organisation_id);

                            $objective_audience = array(
                                "objective_id" => $tag_first_level->getID(),
                                "organisation_id" => $organisation_id,
                                "audience_type" => "COURSE",
                                "audience_value" => "none",
                                "updated_date" => time(),
                                "updated_by" => $ENTRADA_USER->getID()
                            );

                            $obj_audience_model = new Models_Objective_Audience();
                            $obj_audience_model->fromArray($objective_audience);

                            if (!$obj_audience_model->insert()) {
                                add_error($translate->_("An error occurred while trying to add an audience to the objective."));
                                application_log("error", "There was an error inserting an audience. Database said: " . $db->ErrorMsg());
                            }
                        } else {
                            add_error($translate->_("An error occurred while trying to add an objective."));
                            application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                        }
                    } else if ($tag_first_level && !Models_Objective::fetchRowBySetIDParentID($obj_set->getID(), 0, $organisation_id)) {
                        $tag_first_level->insertOrganisationId($organisation_id);
                    }
                    if (!has_error() && $tag_first_level && $tag_first_level->getName() != $obj_set->getTitle()) {
                        $tag_first_level->fromArray(array("objective_name" => $obj_set->getTitle()));
                        if ($tag_first_level->getDescription() != $obj_set->getDescription()) {
                            $tag_first_level->fromArray(array("objective_description" => $obj_set->getDescription()));
                        }
                        $tag_first_level->update();
                    }
                    if (!has_error() && $tag_first_level) {
                        if (isset($cbme_tags[$obj_set->getShortname()])) {
                            foreach ($cbme_tags[$obj_set->getShortname()] as $cbme_tag) {
                                $tag = Models_Objective::fetchRowBySetIDCodeName($obj_set->getID(), $cbme_tag["objective_code"], $cbme_tag["objective_name"]);

                                if (!$tag) {
                                    $objective["objective_code"] = $cbme_tag["objective_code"];
                                    $objective["objective_name"] = $cbme_tag["objective_name"];
                                    $objective["objective_description"] = $cbme_tag["objective_description"];
                                    $objective["objective_parent"] = $tag_first_level->getID();
                                    $objective["objective_set_id"] = $obj_set->getID();
                                    $objective["objective_order"] = $cbme_tag["objective_order"];

                                    $objective_model = new Models_Objective();
                                    $objective_model->fromArray($objective);
                                    if ($objective_model->insert()) {
                                        $objective_model->insertOrganisationId($organisation_id);
                                    } else {
                                        add_error($translate->_("An error occurred while trying to add an objective."));
                                        application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                    }
                                } else if ($tag && !Models_Objective::fetchRowBySetIDCodeName($obj_set->getID(), $cbme_tag["objective_code"], $cbme_tag["objective_name"], $organisation_id)) {
                                    $tag->insertOrganisationId($organisation_id);
                                }
                            }
                        }
                    }
                }
            }

            $PROCESSED_FORM = [];
            $PROCESSED_FORM["organisation_id"] = $organisation_id;
            $PROCESSED_FORM["created_date"] = time();
            $PROCESSED_FORM["created_by"] = $ENTRADA_USER->getID();
            $PROCESSED_FORM["updated_date"] = time();
            $PROCESSED_FORM["updated_by"] = $ENTRADA_USER->getID();

            if ($form_types) {
                foreach ($form_types as $form_type) {
                    if (!Models_Assessments_Form_TypeOrganisation::fetchRowByOrganisationTypeID($organisation_id, $form_type->getID())) {
                        $PROCESSED_FORM["form_type_id"] = $form_type->getID();
                        $form_type_organisation = new Models_Assessments_Form_TypeOrganisation($PROCESSED_FORM);
                        if (!$form_type_organisation->insert()) {
                            application_log("error", "Unable to add form type organisation, DB said: " . $db->ErrorMsg());
                        }
                    }
                }
            }

            if ($assessment_type) {
                if (!Models_Assessments_Type_Organisation::fetchRowByOrganisationAssessmentTypeID($organisation_id, $assessment_type)) {
                    $PROCESSED_FORM["assessment_type_id"] = $assessment_type;
                    $assessment_type_organisation = new Models_Assessments_Type_Organisation($PROCESSED_FORM);
                    if (!$assessment_type_organisation->insert()) {
                        application_log("error", "Unable to add assessment type organisation, DB said: " . $db->ErrorMsg());
                    }
                }
            }

            if ($assessments_method) {
                foreach ($assessments_method as $assessment_method) {
                    if ($assessment_method->getShortname() != "default") {
                        if (!Models_Assessments_Method_Organisation::fetchRowByOrganisationAssessmentMethodID($organisation_id, $assessment_method->getID())) {
                            $PROCESSED_FORM["assessment_method_id"] = $assessment_method->getID();
                            $assessment_method_organisation = new Models_Assessments_Method_Organisation($PROCESSED_FORM);
                            if (!$assessment_method_organisation->insert()) {
                                application_log("error", "Unable to add assessment method organisation, DB said: " . $db->ErrorMsg());
                            }
                        }
                    }
                }
            }

            if (!has_error()) {
                echo json_encode(array("status" => "success", "data" => "success"));
            } else {
                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
            }
        }
        exit;

    } else {
        echo "<div id=\"cbme-msgs\"></div>";

        $check_assessments = true;

        foreach ($form_types as $form_type) {
            if (!Models_Assessments_Form_TypeOrganisation::fetchRowByOrganisationTypeID($organisation_id, $form_type->getID())) {
                $check_assessments = false;
            }
        }

        if ($check_assessments && !Models_Assessments_Type_Organisation::fetchRowByOrganisationAssessmentTypeID($organisation_id, $assessment_type)) {
            $check_assessments = false;
        }

        if ($check_assessments) {
            foreach ($assessments_method as $assessment_method) {
                if ($assessment_method->getShortname() != "default") {
                    if (!Models_Assessments_Method_Organisation::fetchRowByOrganisationAssessmentMethodID($organisation_id, $assessment_method->getID())) {
                        $check_assessments = false;
                    }
                }
            }
        }
        $objective_set_model = new Models_ObjectiveSet();
        if (!$objective_set_model->checkForObjectiveSetsObjectives($cbme_tag_sets, $cbme_tags, $organisation_id) || !$check_assessments) {
            if ($ENTRADA_ACL->amIAllowed("curriculum", "create", false)) {
                add_notice(sprintf($translate->_("It looks like you’re trying to use the Competency-Based Medical Education features within %s; however, some setup information is required in order to proceed.<br>Would you like us to automatically set this up for you?"), APPLICATION_NAME) . " <a href=\"#\" id=\"cbme-setup\">" . $translate->_("Yes, enable CBME features") . ".</a>");
                echo display_notice();
            } else {
                add_error(sprintf($translate->_("It looks like you’re trying to use the Competency-Based Medical Education features within %s, but unfortunately the system administrator has not yet set this up. Please contact your technical support team in order to proceed."), APPLICATION_NAME));

                echo display_error();
            }
        } else {
            $kc_obj_set = $objective_set_model->fetchRowByShortname("kc");
            $ec_obj_set = $objective_set_model->fetchRowByShortname("ec");
            $objective_model = new Models_Objective();
            if (empty($objective_model->fetchAllChildrenByObjectiveSetID($kc_obj_set->getID(), $organisation_id)) || empty($objective_model->fetchAllChildrenByObjectiveSetID($ec_obj_set->getID(), $organisation_id))) {
                if ($ENTRADA_ACL->amIAllowed("curriculum", "create", false)) {
                    add_notice(sprintf($translate->_("Great, it looks like you’ve created all of the taxonomies required to use the Competency-Based Medical Education features within %s. The next step is to import the CanMEDS Key Competencies and CanMEDS Enabling Competencies distributed by the Royal College. To do this go to <a href=\"%s\" target=\"_blank\">Admin > Manage Curriculum > Curriculum Tags</a> and click the \"Import From CSV\" button in both of those Curriculum Tag Sets."), APPLICATION_NAME, ENTRADA_URL . "/admin/curriculum/tags"));
                    echo display_notice();
                } else {
                    add_error(sprintf($translate->_("It looks like you’re trying to use the Competency-Based Medical Education features within %s, but unfortunately the system administrator has not yet set this up. Please contact your technical support team in order to proceed."), APPLICATION_NAME));
                    echo display_error();
                }
            } else {
                $cbme_checked = true;
            }
        }
    }

    ?>
    <script type="text/javascript">
        jQuery(function ($) {
            $("#cbme-setup").on("click", function (e) {
                $.ajax({
                    url: ENTRADA_URL + "/admin/courses/cbme?section=cbme-setup&ajax=1",
                    data: { method: "cbme-objective-sets-setup"},
                    type: "POST",
                    success: function (data) {
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.status === "success") {
                            window.location.reload();
                        } else {
                            display_error(jsonResponse.data, "#cbme-msgs", "append");
                        }
                    }
                });
            });
        });
    </script>
    <?php
}
