<?php
class Migrate_2017_11_08_085227_2444 extends Entrada_Cli_Migrate {

    private function fetch_and_add_response_descriptors($organisation_id, $proxy_id) {
        $descriptor_texts = array(
            "Accepted standards not yet met, frequent errors uncorrected",
            "Achieved",
            "Achieving (ready for independent practice)",
            "Achieving",
            "Achieves standard expected",
            "Advanced beginner",
            "Almost",
            "Borderline high",
            "Borderline low",
            "Clearly exceeds standard",
            "Competent and safe throughout procedure, no uncorrected errors",
            "Competent",
            "Developing",
            "Direct supervision",
            "Direct, proactive supervision",
            "Emerging",
            "Established",
            "Expert",
            "Flagged for review",
            "Highly skilled performance",
            "I did not need to be there",
            "I had to do",
            "I had to prompt them from time to time",
            "I had to talk them through",
            "I needed to be there in the room just in case",
            "Independent performance (with remote supervision)",
            "Indirect, reactive supervision",
            "Is almost there",
            "Limited",
            "Needs attention",
            "No",
            "Not observed",
            "Not yet",
            "Novice",
            "Observation only (no execution)",
            "Opportunities for growth",
            "Proficient",
            "Some standards not yet met, aspects to be improved, some errors uncorrected",
            "Shows critical weaknesses",
            "Supervision for refinement",
            "Supervision of trainees",
            "Supervision on demand",
            "Very limited",
            "Yes"
        );

        // Attempt to create or find the response descriptors
        $response_descriptors = array();
        foreach ($descriptor_texts as $descriptor_text) {
            echo "\nFetch (or build) descriptor: '$descriptor_text'";
            if (!$response_descriptor = Models_Assessments_Response_Descriptor::fetchRowByOrganisationIDDescriptorText($organisation_id, $descriptor_text)) {
                $response_descriptor = new Models_Assessments_Response_Descriptor();
                $response_descriptor_data = $response_descriptor->toArray();
                $response_descriptor_data["organisation_id"] = $organisation_id;
                $response_descriptor_data["descriptor"] = $descriptor_text;
                $response_descriptor_data["reportable"] = 1;
                $response_descriptor_data["created_date"] = time();
                $response_descriptor_data["created_by"] = $proxy_id;
                $response_descriptor_data["order"] = $response_descriptor->fetchNextOrder();
                $response_descriptor->fromArray($response_descriptor_data);
                if (!$response_descriptor->insert()) {
                    echo "\nFailed to create new response descriptor!\n";
                    exit("Exiting...\n");
                } else {
                    echo "\nCreated new response descriptor: '{$response_descriptor->getDescriptor()}'";
                }
            } else {
                echo "\nDescriptor '$descriptor_text' already exists (not creating new)";
            }
            $response_descriptors[$descriptor_text] = $response_descriptor->toArray();
        }
        return $response_descriptors;
    }

    private function add_rating_scale_types($organisation_id, $proxy_id) {
        $rating_scale_type_data = array(
            array(
                "rating_scale_type_id" => null,
                "organisation_id" => $organisation_id,
                "shortname" => "global_assessment",
                "title" => "Global Assessment",
                "description" => "",
                "active" => 1,
                "created_by" => $proxy_id,
                "created_date" => time(),
            ),
            array(
                "rating_scale_type_id" => null,
                "organisation_id" => $organisation_id,
                "shortname" => "milestone_ec",
                "title" => "MS/EC",
                "description" => "Milestones / EC",
                "active" => 1,
                "created_by" => $proxy_id,
                "created_date" => time(),
            )
        );
        foreach ($rating_scale_type_data as $scale_type_data) {
            $scale_type = new Models_Assessments_RatingScale_Type();
            if ($scale_type->fetchRatingScaleTypeByShortnameOrganisationID($scale_type_data["shortname"], $organisation_id)) {
                echo "\nScale type {$scale_type_data["shortname"]} already exists (skipping)\n";
                continue;
            }
            $scale_type->fromArray($scale_type_data);
            if (!$scale_type->insert()) {
                echo "\nFailed to insert new rating scale type! '{$scale_type_data["shortname"]}'";
                return false;
            } else {
                echo "\nAdded scale type '{$scale_type->getShortname()}' (ID: {$scale_type->getID()})";
            }
        }
        return true;
    }

    private function add_rating_scales(&$all_new_scales, $response_descriptors = array(), $organisation_id, $proxy_id) {
        $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $proxy_id, "actor_organisation_id" => $organisation_id));

        // Fetch the scale types; we're creating 2 types of scales
        $ga_scale_types = Models_Assessments_RatingScale_Type::fetchRatingScaleTypeByShortnameOrganisationID("global_assessment", $organisation_id);
        if (empty($ga_scale_types) || count($ga_scale_types) > 1) {
            echo "\nUnable to determine Global Assessment scale type record ";
            if (empty($ga_scale_types)) {
                echo "(it doesn't exist)\n";
            } else {
                echo "(shortname is not unique to organisation)\n";
            }
            return false;
        }
        $ga_scale_type = array_shift($ga_scale_types);

        $msec_scale_types = Models_Assessments_RatingScale_Type::fetchRatingScaleTypeByShortnameOrganisationID("milestone_ec", $organisation_id);
        if (empty($msec_scale_types) || count($msec_scale_types) > 1) {
            echo "\nUnable to determine Milestones/EC scale type record ";
            if (empty($msec_scale_types)) {
                echo "(it doesn't exist)\n";
            } else {
                echo "(shortname is not unique to organisation)\n";
            }
            return false;
        }
        $ms_ec_scale_type = array_shift($msec_scale_types);

        // MS/EC Scales (final)

        $scale_data["rating_scale"]["rating_scale_title"] = "Queen's Six Point";
        $scale_data["rating_scale"]["rating_scale_type"] = $ms_ec_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_description"] = "Developmental";
        $scale_data["responses"] = array(
            $response_descriptors["Not observed"]["ardescriptor_id"],
            $response_descriptors["Very limited"]["ardescriptor_id"],
            $response_descriptors["Limited"]["ardescriptor_id"],
            $response_descriptors["Emerging"]["ardescriptor_id"],
            $response_descriptors["Developing"]["ardescriptor_id"],
            $response_descriptors["Achieving"]["ardescriptor_id"],
            $response_descriptors["Established"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data["rating_scale"]["rating_scale_title"] = "Queen's Five Point";
        $scale_data["rating_scale"]["rating_scale_type"] = $ms_ec_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_description"] = "Developmental";
        $scale_data["responses"] = array(
            $response_descriptors["Not observed"]["ardescriptor_id"],
            $response_descriptors["Limited"]["ardescriptor_id"],
            $response_descriptors["Emerging"]["ardescriptor_id"],
            $response_descriptors["Developing"]["ardescriptor_id"],
            $response_descriptors["Achieving"]["ardescriptor_id"],
            $response_descriptors["Established"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data["rating_scale"]["rating_scale_title"] = "Queen's Rubric Version";
        $scale_data["rating_scale"]["rating_scale_type"] = $ms_ec_scale_type->getID();
        $scale_data["responses"] = array(
            $response_descriptors["Not observed"]["ardescriptor_id"],
            $response_descriptors["Opportunities for growth"]["ardescriptor_id"],
            $response_descriptors["Borderline low"]["ardescriptor_id"],
            $response_descriptors["Developing"]["ardescriptor_id"],
            $response_descriptors["Borderline high"]["ardescriptor_id"],
            $response_descriptors["Achieving (ready for independent practice)"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data["rating_scale"]["rating_scale_title"] = "Dreyfus Scale";
        $scale_data["rating_scale"]["rating_scale_description"] = "Developmental";
        $scale_data["rating_scale"]["rating_scale_type"] = $ms_ec_scale_type->getID();
        $scale_data["responses"] = array(
            $response_descriptors["Not observed"]["ardescriptor_id"],
            $response_descriptors["Novice"]["ardescriptor_id"],
            $response_descriptors["Advanced beginner"]["ardescriptor_id"],
            $response_descriptors["Competent"]["ardescriptor_id"],
            $response_descriptors["Proficient"]["ardescriptor_id"],
            $response_descriptors["Expert"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data["rating_scale"]["rating_scale_title"] = "O-Score";
        $scale_data["rating_scale"]["rating_scale_type"] = $ms_ec_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_description"] = "(Goften et al. 2012) – Rater-centric (\"looking at the learner through the lens of yourself\")";
        $scale_data["responses"] = array(
            $response_descriptors["Not observed"]["ardescriptor_id"],
            $response_descriptors["I had to do"]["ardescriptor_id"],
            $response_descriptors["I had to talk them through"]["ardescriptor_id"],
            $response_descriptors["I had to prompt them from time to time"]["ardescriptor_id"],
            $response_descriptors["I needed to be there in the room just in case"]["ardescriptor_id"],
            $response_descriptors["I did not need to be there"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data["rating_scale"]["rating_scale_title"] = "Entrustment";
        $scale_data["rating_scale"]["rating_scale_type"] = $ms_ec_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_description"] = "(ten Cate et al. 2015) - developmental";
        $scale_data["responses"] = array(
            $response_descriptors["Not observed"]["ardescriptor_id"],
            $response_descriptors["Observation only (no execution)"]["ardescriptor_id"],
            $response_descriptors["Direct, proactive supervision"]["ardescriptor_id"],
            $response_descriptors["Indirect, reactive supervision"]["ardescriptor_id"],
            $response_descriptors["Independent performance (with remote supervision)"]["ardescriptor_id"],
            $response_descriptors["Supervision of trainees"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data["rating_scale"]["rating_scale_title"] = "Queen's Family Medicine (Field Note)";
        $scale_data["rating_scale"]["rating_scale_type"] = $ms_ec_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_description"] = "";
        $scale_data["responses"] = array(
            $response_descriptors["Not observed"]["ardescriptor_id"],
            $response_descriptors["Flagged for review"]["ardescriptor_id"],
            $response_descriptors["Direct supervision"]["ardescriptor_id"],
            $response_descriptors["Supervision on demand"]["ardescriptor_id"],
            $response_descriptors["Supervision for refinement"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data["rating_scale"]["rating_scale_title"] =  "Queen's DOPs";
        $scale_data["rating_scale"]["rating_scale_type"] = $ms_ec_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_description"] = "Direct Observation of Procedural Skills";
        $scale_data["responses"] = array(
            $response_descriptors["Not observed"]["ardescriptor_id"],
            $response_descriptors["Accepted standards not yet met, frequent errors uncorrected"]["ardescriptor_id"],
            $response_descriptors["Some standards not yet met, aspects to be improved, some errors uncorrected"]["ardescriptor_id"],
            $response_descriptors["Competent and safe throughout procedure, no uncorrected errors"]["ardescriptor_id"],
            $response_descriptors["Highly skilled performance"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data["rating_scale"]["rating_scale_title"] = "Queen's Three Point";
        $scale_data["rating_scale"]["rating_scale_type"] = $ms_ec_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_description"] = "";
        $scale_data["responses"] = array(
            $response_descriptors["Not observed"]["ardescriptor_id"],
            $response_descriptors["Needs attention"]["ardescriptor_id"],
            $response_descriptors["Developing"]["ardescriptor_id"],
            $response_descriptors["Achieved"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data["rating_scale"]["rating_scale_title"] = "Queen's Entrustment Scale";
        $scale_data["rating_scale"]["rating_scale_type"] = $ms_ec_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_description"] = "";
        $scale_data["responses"] = array(
            $response_descriptors["Not observed"]["ardescriptor_id"],
            $response_descriptors["Not yet"]["ardescriptor_id"],
            $response_descriptors["Almost"]["ardescriptor_id"],
            $response_descriptors["Yes"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        //-- Global assessment scales

        $scale_data = array();
        $scale_data["rating_scale"]["rating_scale_type"] = $ga_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_title"] = "O-Score";
        $scale_data["rating_scale"]["rating_scale_description"] = "(Gofton et al. 2012) – Rater-centric (\"looking at the learner through the lens of yourself\")";
        $scale_data["responses"] = array(
            $response_descriptors["I had to do"]["ardescriptor_id"],
            $response_descriptors["I had to talk them through"]["ardescriptor_id"],
            $response_descriptors["I had to prompt them from time to time"]["ardescriptor_id"],
            $response_descriptors["I needed to be there in the room just in case"]["ardescriptor_id"],
            $response_descriptors["I did not need to be there"]["ardescriptor_id"]
        );
        $all_new_scales[] = $scale_data;

        $scale_data = array();
        $scale_data["rating_scale"]["rating_scale_type"] = $ga_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_title"] = "Entrustment";
        $scale_data["rating_scale"]["rating_scale_description"] = "(ten Cate et al. 2015) - developmental";
        $scale_data["responses"] = array(
            $response_descriptors["Observation only (no execution)"]["ardescriptor_id"],
            $response_descriptors["Direct, proactive supervision"]["ardescriptor_id"],
            $response_descriptors["Indirect, reactive supervision"]["ardescriptor_id"],
            $response_descriptors["Independent performance (with remote supervision)"]["ardescriptor_id"],
            $response_descriptors["Supervision of trainees"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data = array();
        $scale_data["rating_scale"]["rating_scale_type"] = $ga_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_title"] = "Queen's Developmental Score";
        $scale_data["rating_scale"]["rating_scale_description"] = "";
        $scale_data["responses"] = array(
            $response_descriptors["Very limited"]["ardescriptor_id"],
            $response_descriptors["Limited"]["ardescriptor_id"],
            $response_descriptors["Emerging"]["ardescriptor_id"],
            $response_descriptors["Developing"]["ardescriptor_id"],
            $response_descriptors["Achieving"]["ardescriptor_id"],
            $response_descriptors["Established"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data = array();
        $scale_data["rating_scale"]["rating_scale_type"] = $ga_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_title"] = "Queen's Family Medicine (Field Note)";
        $scale_data["rating_scale"]["rating_scale_description"] = "";
        $scale_data["responses"] = array(
            $response_descriptors["Flagged for review"]["ardescriptor_id"],
            $response_descriptors["Direct supervision"]["ardescriptor_id"],
            $response_descriptors["Supervision on demand"]["ardescriptor_id"],
            $response_descriptors["Supervision for refinement"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        $scale_data = array();
        $scale_data["rating_scale"]["rating_scale_type"] = $ga_scale_type->getID();
        $scale_data["rating_scale"]["rating_scale_title"] = "Queen's Global Rating Scale";
        $scale_data["rating_scale"]["rating_scale_description"] = "";
        $scale_data["responses"] = array(
            $response_descriptors["Shows critical weaknesses"]["ardescriptor_id"],
            $response_descriptors["Needs attention"]["ardescriptor_id"],
            $response_descriptors["Is almost there"]["ardescriptor_id"],
            $response_descriptors["Achieves standard expected"]["ardescriptor_id"],
            $response_descriptors["Clearly exceeds standard"]["ardescriptor_id"],
        );
        $all_new_scales[] = $scale_data;

        foreach ($all_new_scales as $new_scale_data) {
            // Check if a scale by that exact name and type already exists.
            $existing = Models_Assessments_RatingScale::fetchRowByTitleTypeIDOrganisationID(
                $new_scale_data["rating_scale"]["rating_scale_title"],
                $new_scale_data["rating_scale"]["rating_scale_type"],
                $organisation_id
            );
            if ($existing) {
                echo "Scale '{$new_scale_data["rating_scale"]["rating_scale_title"]}' already exists. Not recreating.\n";
                continue;
            }

            echo "\nCreating scale (title: {$new_scale_data["rating_scale"]["rating_scale_title"]} [type: {$new_scale_data["rating_scale"]["rating_scale_type"]}])";
            if (!$forms_api->saveScale($new_scale_data)) {
                foreach ($forms_api->getErrorMessages() as $error_message) {
                    echo "\n$error_message\n";
                }
            } else {
                // Add org as an author
                $scale_author = new Models_Assessments_RatingScale_Author(
                    array(
                        "rating_scale_id" => $forms_api->getScaleID(),
                        "author_id" => $organisation_id,
                        "author_type" => "organisation_id",
                        "created_date" => time(),
                        "created_by" => $proxy_id
                    )
                );
                if (!$scale_author->insert()) {
                    echo "\nFailed to insert scale author for scale ID {$forms_api->getScaleID()} (continuing...)\n";
                }
            }
        }
        return true;
    }

    private function insert_concerns_rubric($form_type_id, $component_order, $concerns_rubric_shortname, $blueprint_type, $no_descriptor_id, $yes_descriptor_id) {

        // Create the "Concerns" container rubric, group and template. 

        $rubric_group = new Models_Assessments_Item_Group(
            array(
                "form_type_id" => $form_type_id,
                "item_type" => "rubric",
                "shortname" => $concerns_rubric_shortname,
                "title" => "Concerns",
                "description" => "",
                "active" => 1,
                "created_date" => time(),
                "created_by" => 1
            )
        );
        $rubric_group->insert();
        $item_group_id = $rubric_group->getID();

        $bp_tpl_item1 = new Models_Assessments_Form_Blueprint_ItemTemplate(
            array(
                "form_type_id" => $form_type_id,
                "active" => 1,
                "parent_item" => 0,
                "ordering" => 1,
                "component_order" => $component_order,
                "item_definition" => json_encode(
                    array (
                        'element_type' => 'rubric',
                        'element_definition' =>
                            array (
                                'item_group_id' => $item_group_id,
                                'item_text' => 'Concerns',
                                'rating_scale_id' => NULL,
                                'comment_type' => 'flagged',
                            ),
                    )
                ),
                "created_date" => time(),
                "created_by" => 1,
                "updated_by" => null,
                "deleted_date" => null
            )
        );
        $bp_tpl_item1->insert();
        $rubric_parent_id = $bp_tpl_item1->getID();

        // First rubric line

        $rubric_group = new Models_Assessments_Item_Group(
            array(
                "form_type_id" => $form_type_id,
                "item_type" => "item",
                "shortname" => $concerns_rubric_shortname . "_item_1",
                "title" => "Do you have patient safety concerns related to this resident's performance?",
                "description" => "",
                "active" => 1,
                "created_date" => time(),
                "created_by" => 1
            )
        );
        $rubric_group->insert();
        $item_group_id = $rubric_group->getID();

        $bp_tpl_item2 = new Models_Assessments_Form_Blueprint_ItemTemplate(
            array(
                "form_type_id" => $form_type_id,
                "active" => 1,
                "parent_item" => $rubric_parent_id,
                "ordering" => 1,
                "component_order" => 0,
                "item_definition" => json_encode(
                    array(
                        'element_type' => 'item',
                        'element_definition' =>
                            array(
                                'item_group_id' => $item_group_id,
                                'item_text' => "Do you have patient safety concerns related to this resident's performance?",
                                'itemtype_shortname' => 'horizontal_multiple_choice_single',
                                'item_code' => "CBME_{$blueprint_type}_form_item",
                                'mandatory' => 1,
                                'responses' =>
                                    array(
                                        1 => '',
                                        2 => '',
                                    ),
                                'comment_type' => 'flagged',
                                'rating_scale_id' => NULL,
                                'allow_default' => 1,
                                'default_response' => 1,
                                'flagged_response' =>
                                    array(
                                        2 => 1,
                                    ),
                                'descriptors' =>
                                    array(
                                        1 => $no_descriptor_id,
                                        2 => $yes_descriptor_id,
                                    ),
                                'objectives' =>
                                    array(),
                                'item_description' => NULL,
                                'attributes' =>
                                    array(
                                        'mutators' =>
                                            array(
                                                0 => 'invisible',
                                            ),
                                    ),
                            ),
                    )
                ),
                "created_date" => time(),
                "created_by" => 1,
                "updated_by" => null,
                "deleted_date" => null
            )
        );
        $bp_tpl_item2->insert();        
        
        // Second rubric line

        $rubric_group = new Models_Assessments_Item_Group(
            array(
                "form_type_id" => $form_type_id,
                "item_type" => "item",
                "shortname" => $concerns_rubric_shortname . "_item_2",
                "title" => "Do you have professionalism concerns about this resident's performance?",
                "description" => "",
                "active" => 1,
                "created_date" => time(),
                "created_by" => 1
            )
        );
        $rubric_group->insert();
        $item_group_id = $rubric_group->getID();

        $bp_tpl_item3 = new Models_Assessments_Form_Blueprint_ItemTemplate(
            array(
                "form_type_id" => $form_type_id,
                "active" => 1,
                "parent_item" => $rubric_parent_id,
                "ordering" => 2,
                "component_order" => 0,
                "item_definition" => json_encode(
                    array(
                        'element_type' => 'item',
                        'element_definition' =>
                            array(
                                'item_group_id' => $item_group_id,
                                'item_text' => "Do you have professionalism concerns about this resident's performance?",
                                'itemtype_shortname' => 'horizontal_multiple_choice_single',
                                'item_code' => "CBME_{$blueprint_type}_form_item",
                                'mandatory' => 1,
                                'responses' =>
                                    array(
                                        1 => '',
                                        2 => '',
                                    ),
                                'comment_type' => 'flagged',
                                'rating_scale_id' => NULL,
                                'allow_default' => 1,
                                'default_response' => 1,
                                'flagged_response' =>
                                    array(
                                        2 => 1,
                                    ),
                                'descriptors' =>
                                    array(
                                        1 => $no_descriptor_id,
                                        2 => $yes_descriptor_id,
                                    ),
                                'objectives' =>
                                    array(),
                                'item_description' => NULL,
                                'attributes' =>
                                    array(
                                        'mutators' =>
                                            array(
                                                0 => 'invisible',
                                            ),
                                    ),
                            ),
                    )
                ),
                "created_date" => time(),
                "created_by" => 1,
                "updated_by" => null,
                "deleted_date" => null
            )
        );
        $bp_tpl_item3->insert();

        // Third rubric line

        $rubric_group = new Models_Assessments_Item_Group(
            array(
                "form_type_id" => $form_type_id,
                "item_type" => "item",
                "shortname" => $concerns_rubric_shortname . "_item_3",
                "title" => "Are there other reasons to flag this assessment?",
                "description" => "",
                "active" => 1,
                "created_date" => time(),
                "created_by" => 1
            )
        );
        $rubric_group->insert();
        $item_group_id = $rubric_group->getID();

        $bp_tpl_item4 = new Models_Assessments_Form_Blueprint_ItemTemplate(
            array(
                "form_type_id" => $form_type_id,
                "active" => 1,
                "parent_item" => $rubric_parent_id,
                "ordering" => 3,
                "component_order" => 0,
                "item_definition" => json_encode(
                    array(
                        'element_type' => 'item',
                        'element_definition' =>
                            array(
                                'item_group_id' => $item_group_id,
                                'item_text' => "Are there other reasons to flag this assessment?",
                                'itemtype_shortname' => 'horizontal_multiple_choice_single',
                                'item_code' => "CBME_{$blueprint_type}_form_item",
                                'mandatory' => 1,
                                'responses' =>
                                    array(
                                        1 => '',
                                        2 => '',
                                    ),
                                'comment_type' => 'flagged',
                                'rating_scale_id' => NULL,
                                'allow_default' => 1,
                                'default_response' => 1,
                                'flagged_response' =>
                                    array(
                                        2 => 1,
                                    ),
                                'descriptors' =>
                                    array(
                                        1 => $no_descriptor_id,
                                        2 => $yes_descriptor_id,
                                    ),
                                'objectives' =>
                                    array(),
                                'item_description' => NULL,
                                'attributes' =>
                                    array(
                                        'mutators' =>
                                            array(
                                                0 => 'invisible',
                                            ),
                                    ),
                            ),
                    )
                ),
                "created_date" => time(),
                "created_by" => 1,
                "updated_by" => null,
                "deleted_date" => null
            )
        );
        $bp_tpl_item4->insert();
    }

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;

        $successes = 0;
        $failures = 0;

        // Fetch all organisations
        if (!$organisations = Models_Organisation::fetchAllOrganisations()) {
            echo "\nNo organisations were found, unable to configure CBME Form Template data.";
            return array("success" => 0, "fail" => 1);
        }

        // Insert the missing blueprint component types.
        $result = $db->Execute(
            "INSERT INTO `cbl_assessments_lu_form_blueprint_components` (`shortname`, `description`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`)
            VALUES
              ('standard_item', 'Standard Form Item', UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
              ('free_text_element', 'Free Text Element', UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
              ('role_selector', 'Role Selector', UNIX_TIMESTAMP(), 1, NULL, NULL, NULL)"
        );
        if (!$result) {
            $failures++;
        }

        $all_new_scales = array();
        // Fetch/create all of the response descriptors for each organisation.
        foreach ($organisations as $organisation) {
            $organisation_id = $organisation["organisation_id"];
            $descriptors = $this->fetch_and_add_response_descriptors($organisation_id, 1);
            $this->add_rating_scale_types($organisation_id, 1);

            $all_new_scales[$organisation_id] = array();
            $this->add_rating_scales($all_new_scales[$organisation_id], $descriptors, $organisation_id, 1);
            echo "\nScales added for organisation $organisation_id\n";
        }

        // The scales don't honour organisation ID, so we can use whichever descriptor was fetched/created last.
        $no_descriptor_id = $descriptors["No"]["ardescriptor_id"];
        $yes_descriptor_id = $descriptors["Yes"]["ardescriptor_id"];

        // Configure the supervisor template; component settings, template records, and item groups
        if ($supervisor_form_type = Models_Assessments_Form_Type::fetchRowByShortname("cbme_supervisor")) {
            $supervisor_form_type_id = $supervisor_form_type->getID();

            $db->Execute("DELETE FROM `cbl_assessments_form_blueprint_item_templates` WHERE `form_type_id` = ?", array($supervisor_form_type_id));
            $db->Execute("DELETE FROM `cbl_assessments_lu_item_groups` WHERE `form_type_id` = ?", array($supervisor_form_type_id));

            // 4th component = "next steps" free text
            $item_group = new Models_Assessments_Item_Group(
                array(
                    "form_type_id" => $supervisor_form_type_id,
                    "item_type" => "item",
                    "shortname" => "cbme_supervisor_next_steps",
                    "title" => "Next Steps",
                    "description" => "",
                    "active" => 1,
                    "created_date" => time(),
                    "created_by" => 1
                )
            );
            $item_group->insert();
            $item_group_id = $item_group->getID();

            $bp_tpl_sup_item1 = new Models_Assessments_Form_Blueprint_ItemTemplate(
                array(
                    "form_type_id" => $supervisor_form_type_id,
                    "active" => 1,
                    "parent_item" => 0,
                    "ordering" => 1,
                    "component_order" => 4,
                    "item_definition" => json_encode(
                        array(
                            'element_type' => 'item',
                            'element_definition' =>
                                array(
                                    'item_group_id' => $item_group_id,
                                    'item_text' => 'Next Steps',
                                    'itemtype_shortname' => 'free_text',
                                    'item_code' => 'CBME_supervisor_item',
                                    'mandatory' => NULL,
                                    'responses' =>
                                        array(),
                                    'comment_type' => 'disabled',
                                    'rating_scale_id' => NULL,
                                    'flagged_response' =>
                                        array(),
                                    'descriptors' =>
                                        array(),
                                    'objectives' =>
                                        array(),
                                    'item_description' => NULL,
                                ),
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $bp_tpl_sup_item1->insert();

            // 5th item = concerns rubric
            $this->insert_concerns_rubric(
                $supervisor_form_type_id,
                5,
                "cbme_supervisor_rubric_concerns",
                "supervisor",
                $no_descriptor_id,
                $yes_descriptor_id
            );

            // 6th item = feedback horizontal
            $item_group = new Models_Assessments_Item_Group(
                array(
                    "form_type_id" => $supervisor_form_type_id,
                    "item_type" => "item",
                    "shortname" => "cbme_supervisor_feedback",
                    "title" => 'Have feedback about this form? (eg, "Missing Dx", etc.)',
                    "description" => "",
                    "active" => 1,
                    "created_date" => time(),
                    "created_by" => 1
                )
            );
            $item_group->insert();
            $item_group_id = $item_group->getID();

            $bp_tpl_sup_item2 = new Models_Assessments_Form_Blueprint_ItemTemplate(
                array(
                    "form_type_id" => $supervisor_form_type_id,
                    "active" => 1,
                    "parent_item" => 0,
                    "ordering" => 1,
                    "component_order" => 6,
                    "item_definition" => json_encode(
                        array(
                            'element_type' => 'item',
                            'element_definition' =>
                                array(
                                    'item_group_id' => $item_group_id,
                                    'item_text' => 'Have feedback about this form? (eg, "Missing Dx", etc.)',
                                    'itemtype_shortname' => 'horizontal_multiple_choice_single',
                                    'item_code' => 'CBME_supervisor_form_item',
                                    'mandatory' => 1,
                                    'allow_default' => 1,
                                    'default_response' => 1,
                                    'responses' =>
                                        array(
                                            1 => 'No',
                                            2 => 'Yes',
                                        ),
                                    'comment_type' => 'flagged',
                                    'rating_scale_id' => NULL,
                                    'flagged_response' =>
                                        array(
                                            2 => 1,
                                        ),
                                    'descriptors' =>
                                        array(),
                                    'objectives' =>
                                        array(),
                                    'item_description' => NULL,
                                    'attributes' =>
                                        array(
                                            'mutators' =>
                                                array(
                                                    0 => 'invisible',
                                                ),
                                        ),
                                ),
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $bp_tpl_sup_item2->insert();

            // Add the component settings for this form type
            $component_setting = new Models_Assessments_Form_Blueprint_ComponentSettings(
                array(
                    "form_type_id" => $supervisor_form_type_id,
                    "component_order" => 0,
                    "settings" => '{"max_milestones":0,"allow_milestones_selection":1}',
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => null,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $component_setting->insert();
            $component_setting = new Models_Assessments_Form_Blueprint_ComponentSettings(
                array(
                    "form_type_id" => $supervisor_form_type_id,
                    "component_order" => 1,
                    "settings" => '{"min_variables":1,"max_variables":6,"required_types":[]}',
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => null,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $component_setting->insert();
            $component_setting = new Models_Assessments_Form_Blueprint_ComponentSettings(
                array(
                    "form_type_id" => $supervisor_form_type_id,
                    "component_order" => 3,
                    "settings" => json_encode(
                        array(
                            'component_header' => 'Select the scale to use for the Entrustment Question',
                            'allow_default_response' => false,
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => null,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $component_setting->insert();

            $successes++;
        } else {
            $failures++;
            echo "Unable to add SUPERVISOR form template definition\n";
        }

        if ($field_note_form_type = Models_Assessments_Form_Type::fetchRowByShortname("cbme_fieldnote")) {
            $field_note_form_type_id = $field_note_form_type->getID();

            $db->Execute("DELETE FROM `cbl_assessments_form_blueprint_item_templates` WHERE `form_type_id` = ?", array($field_note_form_type_id));
            $db->Execute("DELETE FROM `cbl_assessments_lu_item_groups` WHERE `form_type_id` = ?", array($field_note_form_type_id));

            // Continue freetext

            $item_group = new Models_Assessments_Item_Group(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "item_type" => "item",
                    "shortname" => "cbme_fieldnote_continue",
                    "title" => "Continue...",
                    "description" => "",
                    "active" => 1,
                    "created_date" => time(),
                    "created_by" => 1
                )
            );
            $item_group->insert();
            $item_group_id = $item_group->getID();
            $bp_tpl_item = new Models_Assessments_Form_Blueprint_ItemTemplate(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "active" => 1,
                    "parent_item" => 0,
                    "ordering" => 1,
                    "component_order" => 3,
                    "item_definition" => json_encode(
                        array(
                            'element_type' => 'item',
                            'element_definition' =>
                                array(
                                    'item_group_id' => $item_group_id,
                                    'item_text' => 'Continue...',
                                    'itemtype_shortname' => 'free_text',
                                    'item_code' => 'CBME_fieldnote_freetext',
                                    'mandatory' => NULL,
                                    'responses' =>
                                        array(),
                                    'comment_type' => 'disabled',
                                    'rating_scale_id' => NULL,
                                    'flagged_response' =>
                                        array(),
                                    'descriptors' =>
                                        array(),
                                    'objectives' =>
                                        array(),
                                    'item_description' => NULL,
                                ),
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $bp_tpl_item->insert();


            // Consider freetext

            $item_group = new Models_Assessments_Item_Group(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "item_type" => "item",
                    "shortname" => "cbme_fieldnote_consider",
                    "title" => "Consider...",
                    "description" => "",
                    "active" => 1,
                    "created_date" => time(),
                    "created_by" => 1
                )
            );
            $item_group->insert();
            $item_group_id = $item_group->getID();
            $bp_tpl_item = new Models_Assessments_Form_Blueprint_ItemTemplate(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "active" => 1,
                    "parent_item" => 0,
                    "ordering" => 1,
                    "component_order" => 4,
                    "item_definition" => json_encode(
                        array(
                            'element_type' => 'item',
                            'element_definition' =>
                                array(
                                    'item_group_id' => $item_group_id,
                                    'item_text' => 'Consider...',
                                    'itemtype_shortname' => 'free_text',
                                    'item_code' => 'CBME_fieldnote_freetext',
                                    'mandatory' => NULL,
                                    'responses' =>
                                        array(),
                                    'comment_type' => 'disabled',
                                    'rating_scale_id' => NULL,
                                    'flagged_response' =>
                                        array(),
                                    'descriptors' =>
                                        array(),
                                    'objectives' =>
                                        array(),
                                    'item_description' => NULL,
                                ),
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $bp_tpl_item->insert();

            // Next Steps freetext

            $item_group = new Models_Assessments_Item_Group(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "item_type" => "item",
                    "shortname" => "cbme_fieldnote_next_steps",
                    "title" => "Next Steps",
                    "description" => "",
                    "active" => 1,
                    "created_date" => time(),
                    "created_by" => 1
                )
            );
            $item_group->insert();
            $item_group_id = $item_group->getID();
            $bp_tpl_item = new Models_Assessments_Form_Blueprint_ItemTemplate(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "active" => 1,
                    "parent_item" => 0,
                    "ordering" => 1,
                    "component_order" => 6,
                    "item_definition" => json_encode(
                        array(
                            'element_type' => 'item',
                            'element_definition' =>
                                array(
                                    'item_group_id' => $item_group_id,
                                    'item_text' => 'Next Steps',
                                    'itemtype_shortname' => 'free_text',
                                    'item_code' => 'CBME_fieldnote_item',
                                    'mandatory' => NULL,
                                    'responses' =>
                                        array(),
                                    'comment_type' => 'disabled',
                                    'rating_scale_id' => NULL,
                                    'flagged_response' =>
                                        array(),
                                    'descriptors' =>
                                        array(),
                                    'objectives' =>
                                        array(),
                                    'item_description' => NULL,
                                ),
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $bp_tpl_item->insert();


            // 7th item = concerns rubric
            $this->insert_concerns_rubric(
                $field_note_form_type_id,
                7,
                "cbme_fieldnote_rubric_concerns",
                "fieldnote",
                $no_descriptor_id,
                $yes_descriptor_id
            );

            // Feedback horizontal

            $item_group = new Models_Assessments_Item_Group(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "item_type" => "item",
                    "shortname" => "cbme_fieldnote_feedback",
                    "title" => 'Have feedback about this form? (eg, "Missing Dx", etc.)',
                    "description" => "",
                    "active" => 1,
                    "created_date" => time(),
                    "created_by" => 1
                )
            );
            $item_group->insert();
            $item_group_id = $item_group->getID();
            $bp_tpl_item = new Models_Assessments_Form_Blueprint_ItemTemplate(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "active" => 1,
                    "parent_item" => 0,
                    "ordering" => 1,
                    "component_order" => 8,
                    "item_definition" => json_encode(
                        array(
                            'element_type' => 'item',
                            'element_definition' =>
                                array(
                                    'item_group_id' => $item_group_id,
                                    'item_text' => 'Have feedback about this form? (eg, "Missing Dx", etc.)',
                                    'itemtype_shortname' => 'horizontal_multiple_choice_single',
                                    'item_code' => 'CBME_fieldnote_form_item',
                                    'mandatory' => 1,
                                    'allow_default' => 1,
                                    'default_response' => 1,
                                    'responses' =>
                                        array(
                                            1 => 'No',
                                            2 => 'Yes',
                                        ),
                                    'comment_type' => 'flagged',
                                    'rating_scale_id' => NULL,
                                    'flagged_response' =>
                                        array(
                                            2 => 1,
                                        ),
                                    'descriptors' =>
                                        array(),
                                    'objectives' =>
                                        array(),
                                    'item_description' => NULL,
                                    'attributes' =>
                                        array(
                                            'mutators' =>
                                                array(
                                                    0 => 'invisible',
                                                ),
                                        ),
                                ),
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $bp_tpl_item->insert();

            // Field Note component settings

            $component_setting = new Models_Assessments_Form_Blueprint_ComponentSettings(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "component_order" => 0,
                    "settings" => '{"max_milestones":8,"allow_milestones_selection":0}',
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => null,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $component_setting->insert();
            $component_setting = new Models_Assessments_Form_Blueprint_ComponentSettings(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "component_order" => 1,
                    "settings" => '{"min_variables":1,"max_variables":6,"required_types":[]}',
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => null,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $component_setting->insert();
            $component_setting = new Models_Assessments_Form_Blueprint_ComponentSettings(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "component_order" => 2,
                    "settings" => '{"element_text":"<h3>Feedback to Resident:</h3>"}',
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => null,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $component_setting->insert();
            $component_setting = new Models_Assessments_Form_Blueprint_ComponentSettings(
                array(
                    "form_type_id" => $field_note_form_type_id,
                    "component_order" => 5,
                    "settings" => json_encode(
                        array(
                            'component_header' => 'Select the scale to use for the Entrustment Question',
                            'allow_default_response' => false,
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => null,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $component_setting->insert();

            $successes++;
        } else {
            $failures++;
            echo "Unable to add FIELD NOTE form template definition\n";
        }

        if ($procedure_form_type = Models_Assessments_Form_Type::fetchRowByShortname("cbme_procedure")) {
            $procedure_form_type_id = $procedure_form_type->getID();

            $db->Execute("DELETE FROM `cbl_assessments_form_blueprint_item_templates` WHERE `form_type_id` = ?", array($procedure_form_type_id));
            $db->Execute("DELETE FROM `cbl_assessments_lu_item_groups` WHERE `form_type_id` = ?", array($procedure_form_type_id));

            // Next Steps freetext

            $item_group = new Models_Assessments_Item_Group(
                array(
                    "form_type_id" => $procedure_form_type_id,
                    "item_type" => "item",
                    "shortname" => "cbme_procedure_next_steps",
                    "title" => "Next Steps",
                    "description" => "",
                    "active" => 1,
                    "created_date" => time(),
                    "created_by" => 1
                )
            );
            $item_group->insert();
            $item_group_id = $item_group->getID();
            $bp_tpl_item = new Models_Assessments_Form_Blueprint_ItemTemplate(
                array(
                    "form_type_id" => $procedure_form_type_id,
                    "active" => 1,
                    "parent_item" => 0,
                    "ordering" => 1,
                    "component_order" => 4,
                    "item_definition" => json_encode(
                        array(
                            'element_type' => 'item',
                            'element_definition' =>
                                array(
                                    'item_group_id' => $item_group_id,
                                    'item_text' => 'Next Steps',
                                    'itemtype_shortname' => 'free_text',
                                    'item_code' => 'CBME_procedure_item',
                                    'mandatory' => NULL,
                                    'responses' =>
                                        array(),
                                    'comment_type' => 'disabled',
                                    'rating_scale_id' => NULL,
                                    'flagged_response' =>
                                        array(),
                                    'descriptors' =>
                                        array(),
                                    'objectives' =>
                                        array(),
                                    'item_description' => NULL,
                                ),
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $bp_tpl_item->insert();


            // Concerns rubric
            $this->insert_concerns_rubric(
                $procedure_form_type_id,
                5,
                "cbme_procedure_rubric_concerns",
                "procedure",
                $no_descriptor_id,
                $yes_descriptor_id
            );

            // Feedback horizontal

            $item_group = new Models_Assessments_Item_Group(
                array(
                    "form_type_id" => $procedure_form_type_id,
                    "item_type" => "item",
                    "shortname" => "cbme_procedure_feedback",
                    "title" => 'Have feedback about this form? (eg, "Missing Dx", etc.)',
                    "description" => "",
                    "active" => 1,
                    "created_date" => time(),
                    "created_by" => 1
                )
            );
            $item_group->insert();
            $item_group_id = $item_group->getID();
            $bp_tpl_item = new Models_Assessments_Form_Blueprint_ItemTemplate(
                array(
                    "form_type_id" => $procedure_form_type_id,
                    "active" => 1,
                    "parent_item" => 0,
                    "ordering" => 1,
                    "component_order" => 6,
                    "item_definition" => json_encode(
                        array(
                            'element_type' => 'item',
                            'element_definition' =>
                                array(
                                    'item_group_id' => $item_group_id,
                                    'item_text' => 'Have feedback about this form? (eg, "Missing Dx", etc.)',
                                    'itemtype_shortname' => 'horizontal_multiple_choice_single',
                                    'item_code' => 'CBME_procedure_form_item',
                                    'mandatory' => 1,
                                    'allow_default' => 1,
                                    'default_response' => 1,
                                    'responses' =>
                                        array(
                                            1 => 'No',
                                            2 => 'Yes',
                                        ),
                                    'comment_type' => 'flagged',
                                    'rating_scale_id' => NULL,
                                    'flagged_response' =>
                                        array(
                                            2 => 1,
                                        ),
                                    'descriptors' =>
                                        array(),
                                    'objectives' =>
                                        array(),
                                    'item_description' => NULL,
                                    'attributes' =>
                                        array(
                                            'mutators' =>
                                                array(
                                                    0 => 'invisible',
                                                ),
                                        ),
                                ),
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $bp_tpl_item->insert();

            // Procedure blueprint component settings

            $component_setting = new Models_Assessments_Form_Blueprint_ComponentSettings(
                array(
                    "form_type_id" => $procedure_form_type_id,
                    "component_order" => 0,
                    "settings" => '{"max_milestones":0,"allow_milestones_selection":0}',
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => null,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $component_setting->insert();
            $component_setting = new Models_Assessments_Form_Blueprint_ComponentSettings(
                array(
                    "form_type_id" => $procedure_form_type_id,
                    "component_order" => 1,
                    "settings" => '{"min_variables":2,"max_variables":6,"required_types":["procedure"]}',
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => null,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $component_setting->insert();
            $component_setting = new Models_Assessments_Form_Blueprint_ComponentSettings(
                array(
                    "form_type_id" => $procedure_form_type_id,
                    "component_order" => 3,
                    "settings" => json_encode(
                        array(
                            'component_header' => 'Select the scale to use for the Entrustment Question',
                            'allow_default_response' => false,
                        )
                    ),
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => null,
                    "updated_by" => null,
                    "deleted_date" => null
                )
            );
            $component_setting->insert();

            $successes++;
        } else {
            $failures++;
            echo "Unable to add PROCEDURE form template definition\n";
        }

        /**
         * Configure Assessment Methods (required to be able to trigger the assessments created by blueprints)
         */
        $methods_model = new Models_Assessments_Method();
        $all_methods = $methods_model->fetchAllRecords();
        if (!$all_methods) {
            $failures++;
            echo "No assessment methods are defined. Unable to add method organisation and group data (assessments will be un-triggerable).\n";
        } else {

            foreach ($all_methods as $assessment_method) {

                foreach ($organisations as $organisation) {
                    $organisation_id = $organisation["organisation_id"];
                    echo "Configuring Assessment Methods for organisation $organisation_id, '{$assessment_method->getShortname()}'\n";

                    $db->Execute("INSERT INTO `cbl_assessment_method_organisations` (`assessment_method_id`, `organisation_id`, `created_date`, `created_by`) VALUES (?, ?, ?, 1)", array($assessment_method->getID(), $organisation_id, time()));

                    switch ($assessment_method->getShortname()) {
                        case "send_blank_form":
                            // student, admin
                            $db->Execute("INSERT INTO `cbl_assessment_method_groups` (`assessment_method_id`, `group`, `admin`) VALUES (?, 'student', 0)", array($assessment_method->getID()));
                            $db->Execute("INSERT INTO `cbl_assessment_method_groups` (`assessment_method_id`, `group`, `admin`) VALUES (?, 'medtech', 1)", array($assessment_method->getID()));
                            break;
                        case "complete_and_confirm_by_email":
                        case "complete_and_confirm_by_pin":
                        case "double_blind_assessment":
                            // student, faculty
                            $db->Execute("INSERT INTO `cbl_assessment_method_groups` (`assessment_method_id`, `group`, `admin`) VALUES (?, 'student', 0)", array($assessment_method->getID()));
                            $db->Execute("INSERT INTO `cbl_assessment_method_groups` (`assessment_method_id`, `group`, `admin`) VALUES (?, 'faculty', 0)", array($assessment_method->getID()));
                            break;
                    }

                    switch ($assessment_method->getShortname()) {
                        case "default":
                            $db->Execute("
                                INSERT INTO `cbl_assessment_method_group_meta` (`assessment_method_id`, `group`, `title`, `description`, `instructions`, `button_text`, `skip_validation`, `created_date`, `created_by`)
                                VALUES 
                                    (?, '', 'Standard Assessment', NULL, NULL, 'Submit', 0, UNIX_TIMESTAMP(), 1)",
                                array($assessment_method->getID())
                            );
                            break;
                        case "send_blank_form":
                            $db->Execute("
                                INSERT INTO `cbl_assessment_method_group_meta` (`assessment_method_id`, `group`, `title`, `description`, `instructions`, `button_text`, `skip_validation`, `created_date`, `created_by`)
                                VALUES
                                    (?, 'student', 'Email blank form', 'The attending will receive an email notification to complete an assessment based on the selected tool.', 'Once you have submitted this assessment, the selected attending will receive a blank assessment task containing this form.', 'Submit and send attending a blank form', 0, UNIX_TIMESTAMP(), 1),
                                    (?, 'faculty', 'Email blank form', 'The attending will receive an email notification to complete an assessment based on the selected tool.', 'Once you have submitted this assessment, the result will appear on the target\'s dashboard.', 'Submit', 0, UNIX_TIMESTAMP(), 1),
                                    (?, 'medtech', 'Email blank form', 'Complete an assessment using the selected tool. Upon completion, the attending will receive an email notification asking them to review/edit and confirm the assessment.', 'Once you have submitted this assessment, the result will appear on the target\'s dashboard.', 'Submit', 0, UNIX_TIMESTAMP(), 1)",
                                array($assessment_method->getID(), $assessment_method->getID(), $assessment_method->getID())
                            );
                            break;
                        case "complete_and_confirm_by_email":
                            $db->Execute("
                                INSERT INTO `cbl_assessment_method_group_meta` (`assessment_method_id`, `group`, `title`, `description`, `instructions`, `button_text`, `skip_validation`, `created_date`, `created_by`)
                                VALUES
                                    (?, 'student', 'Complete and confirm via email', 'Complete an assessment using the selected tool. Upon completion, the attending will receive an email notification asking them to review/edit and confirm the assessment.', 'Once you have submitted this assessment, the selected attending will receive an email link to complete this assessment task.', 'Submit and notify attending by email', 1, UNIX_TIMESTAMP(), 1),
                                    (?, 'faculty', 'Complete and confirm via email', 'Complete an assessment using the selected tool. Upon completion, the attending will receive an email notification asking them to review/edit and confirm the assessment.', 'Once you have submitted this assessment, the result will appear on the target\'s dashboard.', 'Submit', 0, UNIX_TIMESTAMP(), 1)",
                                array($assessment_method->getID(), $assessment_method->getID())
                            );
                            break;
                        case "complete_and_confirm_by_pin":
                            $db->Execute("
                                INSERT INTO `cbl_assessment_method_group_meta` (`assessment_method_id`, `group`, `title`, `description`, `instructions`, `button_text`, `skip_validation`, `created_date`, `created_by`)
                                VALUES
                                    (?, 'student', 'Complete and confirm via pin', 'Complete an assessment based on the selected tool. Upon completion the assessment, the attending will confirm it on the spot and adjust your assessment as necessary.', 'Once you have submitted this assessment, the attending will be prompted to enter their PIN and complete this assessment task.', 'Submit and have attending confirm by PIN', 0, UNIX_TIMESTAMP(), 1),
                                    (?, 'faculty', 'Complete and confirm via pin', 'Complete an assessment based on the selected tool. Upon completion the assessment, the attending will confirm it on the spot and adjust your assessment as necessary.', 'Once your PIN has been entered, the result of this assessment will appear on the target\'s dashboard.', 'Submit', 0, UNIX_TIMESTAMP(), 1)",
                                array($assessment_method->getID(), $assessment_method->getID())
                            );
                            break;
                        case "double_blind_assessment":
                            $db->Execute("
                                INSERT INTO `cbl_assessment_method_group_meta` (`assessment_method_id`, `group`, `title`, `description`, `instructions`, `button_text`, `skip_validation`, `created_date`, `created_by`)
                                VALUES
                                    (?, 'student', 'Self Assessment, then email blank form', 'Complete an assessment based on the selected tool. Upon completion, the attending will receive a blank assessment task with the same assessment tool.', 'Once you have submitted this assessment, the selected attending will receive an email link to complete a blank assessment task containing this form.', 'Submit and send attending a blank form', 0, UNIX_TIMESTAMP(), 1),
                                    (?, 'faculty', 'Self Assessment, then email blank form', 'Complete an assessment based on the selected tool. Upon completion, the attending will receive a blank assessment task with the same assessment tool.', 'Once you have submitted this assessment, the result will appear on the target\'s dashboard.', 'Submit', 0, UNIX_TIMESTAMP(), 1)",
                                array($assessment_method->getID(), $assessment_method->getID())
                            );
                            break;
                        case "faculty_triggered_assessment":
                            $db->Execute("
                                INSERT INTO `cbl_assessment_method_group_meta` (`assessment_method_id`, `group`, `title`, `description`, `instructions`, `button_text`, `skip_validation`, `created_date`, `created_by`)
                                VALUES
                                    (?, 'faculty', 'Faculty Triggered Assessment', NULL, 'Once you have submitted this assessment, the result will appear on the target\'s dashboard.', 'Submit', 0, UNIX_TIMESTAMP(), 1)",
                                array($assessment_method->getID())
                            );
                            break;
                    }
                }
            }
            $successes++;
        }

        // For all of the form types, ensure that they behave properly on the assessment rendering side.
        if ($form_types = Models_Assessments_Form_Type::fetchAllRecords(1)) {
            foreach ($form_types as $form_type) {
                foreach ($organisations as $organisation) {
                    $organisation_id = $organisation["organisation_id"];
                    echo "Configuring form type metadata for organisation {$organisation_id} / type = {$form_type->getShortname()}\n";
                    switch ($form_type->getShortname()) {
                        case "rubric_form":
                            $db->Execute("
                                INSERT INTO `cbl_assessment_form_type_meta` (`form_type_id`, `organisation_id`, `meta_name`, `meta_value`, `active`, `created_date`, `created_by`)
                                VALUES
                                    (?, ?, 'hide_from_dashboard', '1', 1, UNIX_TIMESTAMP(), 1)",
                                array($form_type->getID(), $organisation_id)
                            );
                            break;
                        case "cbme_fieldnote":
                        case "cbme_supervisor":
                            $db->Execute("
                                INSERT INTO `cbl_assessment_form_type_meta` (`form_type_id`, `organisation_id`, `meta_name`, `meta_value`, `active`, `created_date`, `created_by`)
                                VALUES
                                    (?, ?, 'show_objectives', '1', 1, UNIX_TIMESTAMP(), 1),
                                    (?, ?, 'show_entrustment', '1', 1, UNIX_TIMESTAMP(), 1)",
                                array($form_type->getID(), $organisation_id, $form_type->getID(), $organisation_id)
                            );
                            break;
                        case "cbme_procedure":
                            $db->Execute("
                                INSERT INTO `cbl_assessment_form_type_meta` (`form_type_id`, `organisation_id`, `meta_name`, `meta_value`, `active`, `created_date`, `created_by`)
                                VALUES
                                    (?, ?, 'show_procedures', '1', 1, UNIX_TIMESTAMP(), 1),
                                    (?, ?, 'show_objectives', '1', 1, UNIX_TIMESTAMP(), 1),
                                    (?, ?, 'show_entrustment', '1', 1, UNIX_TIMESTAMP(), 1)",
                                array($form_type->getID(), $organisation_id, $form_type->getID(), $organisation_id, $form_type->getID(), $organisation_id)
                            );
                            break;
                        case "cbme_ppa_form":
                            $db->Execute("
                                INSERT INTO `cbl_assessment_form_type_meta` (`form_type_id`, `organisation_id`, `meta_name`, `meta_value`, `active`, `created_date`, `created_by`)
                                VALUES
                                    (?, ?, 'show_objectives', '1', 1, UNIX_TIMESTAMP(), 1)",
                                array($form_type->getID(), $organisation_id)
                            );
                            break;
                    }
                }
            }
            $successes++;
        } else {
            echo "No form types are defined\n";
            $failures++;
        }

        if ($assessment_types = Models_Assessments_Type::fetchAllRecords()) {
            echo "Adding assessment types for all organisations\n";
            foreach ($assessment_types as $assessment_type) {
                foreach ($organisations as $organisation) {
                    $db->Execute(
                        "INSERT INTO `cbl_assessment_type_organisations` (`assessment_type_id`, `organisation_id`, `created_date`, `created_by`) VALUES (?, ?, UNIX_TIMESTAMP(), 1)",
                        array($assessment_type->getID(), $organisation["organisation_id"])
                    );
                }
            }
            $successes++;
        } else {
            echo "No assessment types are defined.\n";
            $failures++;
        }

        return array("success" => $successes, "fail" => $failures);
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        -- These must be truncated
        TRUNCATE `cbl_assessments_form_blueprint_item_templates`;
        TRUNCATE `cbl_assessments_lu_item_groups`;
        TRUNCATE `cbl_assessments_form_type_component_settings`;
        TRUNCATE `cbl_assessment_type_organisations`;
        TRUNCATE `cbl_assessment_form_type_meta`;
        TRUNCATE `cbl_assessment_method_organisations`;
        TRUNCATE `cbl_assessment_method_groups`;
        TRUNCATE `cbl_assessment_method_group_meta`;

        -- These are components we added that can be removed
        DELETE FROM `cbl_assessments_lu_form_blueprint_components` WHERE `shortname` IN ('standard_item', 'free_text_element', 'role_selector');

        -- NOTE: Scales and response descriptors will not be truncated or modified since it is not possible for us to reliably determine what we previously created.
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Optional: PHP that verifies whether or not the changes outlined
     * in "up" are present in the active database.
     *
     * Return Values: -1 (not run) | 0 (changes not present or complete) | 1 (present)
     *
     * @return int
     */
    public function audit() {
        global $db;
        // Check if some tables have data. If they do, then we can assume that the migration has already been run.
        // cbl_assessments_form_blueprint_item_templates, cbl_assessments_form_type_component_settings, cbl_assessment_type_organisations
        $result = $db->GetRow("SELECT COUNT(*) AS `count` FROM `cbl_assessments_form_blueprint_item_templates`");
        if ($result["count"] > 0) {
            $result = $db->GetRow("SELECT COUNT(*) AS `count` FROM `cbl_assessments_form_type_component_settings`");
            if ($result["count"] > 0) {
                $result = $db->GetRow("SELECT COUNT(*) AS `count` FROM `cbl_assessment_type_organisations`");
                if ($result["count"] > 0) {
                    return 1;
                }
            }
        }
        return 0;

    }
}
