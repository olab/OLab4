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
 * API Endpoint for Gradebook/view ajax calls
 *
 * @author Organisation: bitHeads Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

if ($ENTRADA_ACL->isUserAuthorized("gradebook", "update", false, array("PARENT_INCLUDED", "IN_GRADEBOOK"))) {

    // Clear buffers to deliver plain-text response
    ob_clear_open_buffers();

    // Serve as json
    header('Content-Type: application/json');

    // get request method (GET or POST)
    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    // $url_params will contain sanitized version of params requested
    $url_params = Entrada_Utilities::getCleanUrlParams(array('cperiod_id' => 'int', 'search' => array('trim', 'striptags'), 'method' => array('trim', 'striptags')));

    switch ($request) {
        case 'POST':

            switch ($url_params['method']) {
                
                case 'update-assessments-order':

                    $error = false;

                    if ($_POST['new_order']) {

                        $i = 0;
                        foreach($_POST['new_order'] as $raw_assessment_id) {
                            $i++;
                            $assessment_id = clean_input($raw_assessment_id, 'int');
                            $assessment = new Models_Gradebook_Assessment(array('assessment_id' => $assessment_id));
                            $assessment->setOrder($i);
                            $update = $assessment->update(array("assessment_id", "order"));
                        }
                    
                    } else {
                        // $_POST['assessments'] is not set
                        $error = true;
                        $response = array('status' => 'fail', 'message' => $translate->_('No assessments were set to be updated.'));
                    }
                    
                    // After all is run, return a success if no errors occured
                    if (!$error) {
                        $response = array('status' => 'success', 'assessments' => $update);
                    }

                    // echo response
                    echo json_encode($response);

                break;

                case 'set-order':

                    $error = false;

                    // if this isn't set, no info to update
                    if ($_POST['assessments']) {

                        foreach($_POST['assessments'] as $raw_assessment) {

                            // clean inputs
                            $assessment_id = clean_input($raw_assessment['assessment_id'], 'int');
                            $order = clean_input($raw_assessment['order'], 'int');

                            // if both are present and are integers, update the order for that assessment
                            if (is_int($assessment_id) && is_int($order)) {
                                $assignment = new Models_Gradebook_Assessment(array('assessment_id' => $assessment_id));
                                $assignment->setOrder($order);
                                $update = $assignment->update(array('assessment_id', 'order'));
                            } else {
                                // if either id or order are not present
                                $error = true;
                                $response = array('status' => 'fail', 'message' => $translate->_('Missing assignment id or order.'));
                            }
                        }

                    } else {
                        // $_POST['assessments'] is not set
                        $error = true;
                        $response = array('status' => 'fail', 'message' => $translate->_('No assessments were set to be updated.'));
                    }
                    
                    // After all is run, return a success if no errors occured
                    if (!$error) {
                        $response = array('status' => 'success', 'assessments' => $raw_assessment);
                    }

                    // echo response
                    echo json_encode($response);

                break;

                case 'copy':

                    $error = false;

                    // Parse serialized string
                    parse_str($_POST['assessments'], $raw_assessments);

                    // Clean each input
                    foreach($raw_assessments['assessments'] as $raw_assessment) {
                        $assessment_ids[] = clean_input($raw_assessment, array('int'));
                    }

                    // Get all records within ID array
                    $assessment_model = new Models_Gradebook_Assessment();
                    $assessments = $assessment_model->fetchAssessmentsInIDArray($assessment_ids);

                    // Insert each assessment
                    foreach($assessments as $assessment) {

                        // Set the new cperiod_id
                        $assessment->setCurriculumPeriodID($url_params['cperiod_id']);

                        // Set group assessment to false for all copied assessment
                        $assessment->setGroupAssessment(false);

                        // Get old assessment ID
                        $old_assessment_id = $assessment->getAssessmentID();

                        // Insert each
                        if (!$new_assessment = $assessment->insertRemoveID()) {
                            $error = true;
                            $response = array('status' => 'fail', 'message' => $translate->_('Could not insert record.'));
                        } else {

                            // Get new dropbox
                            $existing_assignment_model = new Models_Assignment(array("assessment_id" => $old_assessment_id));
                            $existing_assignment = $existing_assignment_model->fetchRowByAssessmentID();

                            if ($existing_assignment) {
                                $new_assignment_array = $existing_assignment->toArray();

                                $new_assignment_array["assessment_id"] = $new_assessment->getAssessmentID();
                                $new_assignment_array["updated_date"] = time();
                                $new_assignment_array["updated_by"] = $ENTRADA_USER->getActiveId();

                                unset($new_assignment_array["assignment_id"]);
                                unset($new_assignment_array["notice_id"]);

                                $new_assignment = new Models_Assignment($new_assignment_array);
                                $inserted_assignment = $new_assignment->insert();

                                // Get new assignment contacts
                                if ($inserted_assignment->getAssignmentID()) {
                                    $assignment_contacts_model = new Models_Assignment_Contact(array("assignment_id" => $existing_assignment->getAssignmentID()));
                                    $assignment_contacts = $assignment_contacts_model->fetchAllByAssignmentID();

                                    if ($assignment_contacts) {
                                        foreach($assignment_contacts as $contact) {
                                            $assignment_contact = $contact->toArray();

                                            $assignment_contact["assignment_id"] = $inserted_assignment->getAssignmentID();
                                            $assignment_contact["updated_date"] = time();
                                            $assignment_contact["updated_by"] = $ENTRADA_USER->getActiveId();

                                            unset($assignment_contact["acontact_id"]);

                                            $new_assignment_contact = new Models_Assignment_Contact($assignment_contact);
                                            $inserted_assignment_contact = $new_assignment_contact->insert();
                                        }
                                    }
                                }
                            }

                            // If form attached, get scores and weights
                            if ($new_assessment->getFormID()) {
                                $form_elements_model = new Models_Gradebook_Assessment_Form_Element(array("assessment_id" => $old_assessment_id));
                                $weights = $form_elements_model->fetchAllByAssessmentID();

                                $new_weights = array();

                                if ($weights) {
                                    foreach($weights as $weight) {
                                        $new_weight_array = $weight->toArray();

                                        $new_weight_array["assessment_id"] = $new_assessment->getAssessmentID();

                                        unset($new_weight_array["gafelement_id"]);

                                        $new_weights[] = $new_weight_array;
                                    }

                                    $inserted_weights = $form_elements_model->insertBulk($new_weights);
                                }

                                $item_responses_model = new Models_Gradebook_Assessment_Item_Response(array("assessment_id" => $old_assessment_id));
                                $item_responses = $item_responses_model->fetchAllByAssessmentID();

                                $new_item_responses = array();

                                if ($item_responses) {
                                    foreach($item_responses as $item_response) {
                                        $new_item_response_array = $item_response->toArray();

                                        $new_item_response_array["assessment_id"] = $new_assessment->getAssessmentID();

                                        unset($new_item_response_array["gairesponse_id"]);

                                        $new_item_responses[] = $new_item_response_array;
                                    }

                                    $inserted_item_responses = $item_responses_model->insertBulk($new_item_responses);
                                }
                            }

                            // Add to new assessments array to be sent back to frontend if all went well
                            $new_assessments[] = $new_assessment->toArray();
                        }
                    }

                    if (!$error) {
                        $response = array('status' => 'success');
                    }

                    // echo response
                    echo json_encode($response);

                break;

                case 'delete':

                    $error = false;

                    // Parse serialized string
                    parse_str($_POST['assessments'], $raw_assessments);

                    // Clean each input
                    foreach($raw_assessments['assessments'] as $raw_assessment) {
                        $assessment_ids[] = clean_input($raw_assessment, array('int'));
                    }

                    // Get all records within ID array
                    $assessment_model = new Models_Gradebook_Assessment();
                    $assessments = $assessment_model->fetchAssessmentsInIDArray($assessment_ids);

                    // Insert each assessment
                    foreach($assessments as $assessment) {
                        // Setting the active flag to 0 means it is "deleted"
                        $assessment->setActive(0);

                        // Insert each
                        if (!$assessment->update()) {
                            $error = true;
                            $response = array('status' => 'fail', 'message' => $translate->_('Could not delete record.'));
                        }
                    }

                    if (!$error) {
                        $response = array('status' => 'success');
                    }

                    // echo response
                    echo json_encode($response);

                break;

                case 'add-collection':

                    $title = clean_input($_POST['title'], array("notags", "trim"));
                    $description = clean_input($_POST['description'], array("notags", "trim"));
                    $course_id = clean_input($_POST['id'], array('int'));

                    if (!empty($title)) {

                        $collection_by_title = Models_Gradebook_Assessment_Collection::fetchRowByTitle($title, $course_id);
                        // disallow add a new collection with a redundant name
                        if ($collection_by_title) {
                            $response = array('status' => 'abort', 'collection_id' => $collection_by_title->getID());
                        } else {
                            $collection = new Models_Gradebook_Assessment_Collection(array("title" => $title, "description" => $description, "course_id" => $course_id));
                            $row = $collection->insert();
                            
                            if ($row) {
                                $html = '<option value="'. $row->getID() .'" desc="' . $row->getDescription() . '">'. $row->getTitle() . '</option>';
                                $response = array('status' => 'success', 'data' => $html);
                            } else {
                                $response = array('status' => 'fail', 'message' => $translate->_('Could not insert record.'));
                            }
                        }
                    } else {
                        $response = array('status' => 'fail', 'message' => "please fill in the title field.");
                    }

                    echo json_encode($response);

                break;

                case 'add-to-collection':
                    
                    $error = false;
                    
                    parse_str($_POST['assessments'], $raw_assessments);
                    
                    // Clean each input
                    foreach($raw_assessments['assessments'] as $raw_assessment) {
                        $assessment_ids[] = clean_input($raw_assessment, array('int'));
                    }
                    $collection_id = clean_input($_POST['collection_id']);
                   
                    if (count($assessment_ids)) {
                        foreach ($assessment_ids as $assessment_id) {
                            $assessment = Models_Gradebook_Assessment::fetchRowByID($assessment_id);

                            if (!is_null($assessment)) {
                                $assessment->setCollectionID($collection_id);
                                $error = !($assessment->update());
                            } else {
                                $error = true;
                            }
                        }
                    } else {
                        $error = true;
                    }

                    if (!$error) {
                        $response = array('status' => 'success', 'message' => $translate->_('Update table assessments successfully.'));
                    } else {
                        $response = array('status' => 'fail', 'message' => $translate->_('Could not update table assessments.'));
                    }

                    echo json_encode($response);

                break;

                case 'remove-from-collection':

                    $error = false;
                    
                    parse_str($_POST['assessments'], $raw_assessments);
                    
                    // Clean each input
                    foreach($raw_assessments['assessments'] as $raw_assessment) {
                        $assessment_ids[] = clean_input($raw_assessment, array('int'));
                    }

                    if (count($assessment_ids)) {
                        foreach ($assessment_ids as $assessment_id) {
                            $assessment = Models_Gradebook_Assessment::fetchRowByID($assessment_id);

                            if (!is_null($assessment)) {
                                $assessment->setCollectionID(null);
                                $error = !($assessment->update());
                            } else {
                                $error = true;
                            }
                        }
                    } else {
                        $error = true;
                    }

                    if (!$error) {
                        $response = array('status' => 'success', 'message' => $translate->_('Update assessments successfully.'));
                    } else {
                        $response = array('status' => 'fail', 'message' => $translate->_('Could not update table assessments.'));
                    }

                    echo json_encode($response);

                break;

                case 'update-collection':
                    $collection_id = clean_input($_POST['collection_id'], array("int"));
                    $title = clean_input($_POST['title'], array("notags", "trim"));
                    $description = clean_input($_POST['description'], array("notags", "trim"));

                    if (!empty($title) && !empty($collection_id)) {
                        $collection = Models_Gradebook_Assessment_Collection::fetchRowByID($collection_id);
                        $collection->setTitle($title);
                        $collection->setDescription($description);

                        if ($collection->update()) {
                            $response = array('status' => 'success', 'message' => $translate->_('Successfully update record.'));
                        } else {
                            $response = array('status' => 'fail', 'message' => $translate->_('Could not update record.'));
                        }
                    } else {
                        $response = array('status' => 'fail', 'message' => "please fill in the title field.");
                    }

                    echo json_encode($response);
                
                break;

                case 'empty-collection':

                    $error = false;
                    
                    parse_str($_POST['collections'], $raw_collections);
                    // Clean each input
                    foreach($raw_collections['collections'] as $raw_collection) {
                        $collection_ids[] = clean_input($raw_collection, array('int'));
                    }

                    $assessments = Models_Gradebook_Assessment::fetchAssessmentsByCollectionIds($collection_ids);

                    if ($assessments) {

                        foreach ($assessments as $assessment) {
                            $assessment->setCollectionID(null);
                            if (!$assessment->update()) {
                                $error = "cannot set collection ids to null";
                            }
                        }
                    } else {
                        $error = "cannot fetch any assessment";
                    }

                    if (!$error) {
                        $response = array('status' => 'success', 'message' => $translate->_('Update assessments successfully.'));
                    } else {
                        $response = array('status' => 'fail', 'message' => $error);
                    }

                    echo json_encode($response);

                break;

                case 'delete-collection':

                    $error = false;
                    
                    parse_str($_POST['collections'], $raw_collections);
                    
                    // Clean each input
                    foreach($raw_collections['collections'] as $raw_collection) {
                        $collection_ids[] = clean_input($raw_collection, array('int'));
                    }

                    // do we need to check if these collection_ids are not in the assessments table before purging from assessment_collections table?

                    foreach ($collection_ids as $collection_id) {
                        $row = Models_Gradebook_Assessment_Collection::fetchRowByID($collection_id);
                        $row->setActive(0);

                        if (!$row->update()) {
                            $error = "cannot deactivate a collection";
                        };
                    }

                    if (!$error) {
                        $response = array('status' => 'success', 'message' => $translate->_('Deactivated collections successfully.'));
                    } else {
                        $response = array('status' => 'fail', 'message' => $error);
                    }

                    echo json_encode($response);

                break;
            }

        break;

        case 'GET':

            switch ($url_params['method']) {
                case 'list':

                    if ($url_params['cperiod_id'] && $COURSE_ID) {

                        // cperiod_id has been selected, so save the preference in session settings, but for 'courses' and not for 'gradebook'
                        $_SESSION[APPLICATION_IDENTIFIER]["courses"]["selected_curriculum_period"] = $url_params["cperiod_id"];
                        
                        // get assessments list with attached assignments and views
                        $assessment_model = new Models_Gradebook_Assessment();
                        $assessments = $assessment_model->fetchAssessmentsByCurriculumPeriodIDWithAssignments($COURSE_ID, $url_params['cperiod_id']);
                        
                        if ($assessments) {
                            $response = array('status' => 'success', 'data' => $assessments);
                        } else {
                            $response = array('status' => 'fail', 'data' => false);
                        }

                        // echo response
                        echo json_encode($response);
                        preferences_update("courses");
                    }

                break;

                case 'fetch-collection-list':

                    $collections = Models_Gradebook_Assessment_Collection::fetchAllRowsByCourseID($COURSE_ID);
                    $html = '';

                    foreach ($collections as $collection) {
                        $html .= '<option value="'. $collection->getID() .'" desc="' . $collection->getDescription() . '">'. $collection->getTitle() . '</option>';
                    }

                    if ($html) {
                        $response = array('status' => 'success', 'data' => $html);
                    } else {
                        $response = array('status' => 'fail', 'data' => false);
                    }
                    
                    echo json_encode($response);

                break;

                case 'load-table':

                    if ($url_params['cperiod_id'] && $COURSE_ID) {
                        // cperiod_id has been selected, so save the preference in session settings, but for 'courses' and not for 'gradebook'
                        $_SESSION[APPLICATION_IDENTIFIER]["courses"]["selected_curriculum_period"] = $url_params["cperiod_id"];
                        $assessment_model = new Models_Gradebook_Assessment();
                        $assessments = $assessment_model->fetchAssessmentsByCurriculumPeriodIDWithAssignments($COURSE_ID, $url_params['cperiod_id'], (isset($url_params['search']) ? $url_params['search'] : ''));

                        $datatable = new Views_Gradebook_DivTable(
                            array(
                                "id" => "datatable-assessments",
                                "class" => "table table-striped table-bordered",
                                "assessments" => $assessments,
                                "search" =>  (isset($url_params['search']) ? $url_params['search'] : '')
                            )
                        );

                        $html = $datatable->render(array(), false);

                        if ($html) {
                            $response = array('status' => 'success', 'data' => $html);
                        } else {
                            $response = array('status' => 'fail', 'data' => false);
                        }
                        
                        preferences_update("courses");
                        echo json_encode($response);
                    }
                break;
            }
    }
}

// Necessary to not expose any template code not caught by ob_clear_open_buffers()
exit;