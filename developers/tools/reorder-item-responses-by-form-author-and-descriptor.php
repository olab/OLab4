<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Script to reorder an item responsewith a specified descriptor to a certain order
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../www-root/core",
    dirname(__FILE__) . "/../../www-root/core/includes",
    dirname(__FILE__) . "/../../www-root/core/library",
    dirname(__FILE__) . "/../../www-root/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

/**
 * Usage blurb. When displayed, exits script.
 */
function show_this_usage() {
    echo "\nUsage: reorder-item-responses-by-form-author-and-descriptor.php [options] [author type] [author id] [descriptor id] [desired order]";
    echo "\n   --usage       Brings up this help screen.";
    echo "\n   --execute     Reorders responses with the specified descriptor to the desired order and adjusts other responses accordingly for all items in the forms with the authors provided.";
    echo "\n\n";
    exit();
}

/**
 * Main point of execution
 *
 * @param $argc
 * @param $argv
 */
function run($argc, &$argv) {
    $action = "--usage";
    $author_type = $author_value = $descriptor_id = $descriptor_id = $order = 0;
    if ($argc > 1 && !empty($argv)) {
        $action = @$argv[1];
        $author_type = @$argv[2];
        $author_value = @$argv[3];
        $descriptor_id = @$argv[4];
        $order = @$argv[5];
    }

    switch ($action) {
        case "--execute":
            if (!$author_type || !$author_value || !$descriptor_id || !$order) {
                show_this_usage();
            }

            $descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($descriptor_id);
            if ($descriptor) {
                $authors = Models_Assessments_Form_Author::fetchAllByFormIDAuthorIDAuthorType($author_value, $author_type);
                if ($authors) {
                    foreach ($authors as $author) {
                        echo "\n\nProcessing form {$author->getFormID()}.";
                        $form_elements = Models_Assessments_Form_Element::fetchAllByFormID($author->getFormID());
                        if ($form_elements) {
                            foreach ($form_elements as $form_element) {
                                if ($form_element->getElementType() == "item") {
                                    echo "\nProcessing item {$form_element->getElementID()}.";
                                    $item_responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($form_element->getElementID());
                                    if ($item_responses) {
                                        $response_set_has_descriptor_needs_edit = false;
                                        foreach ($item_responses as $item_response) {
                                            if ($item_response->getARDescriptorID() == $descriptor->getID() && $item_response->getOrder() != $order) {
                                                $response_set_has_descriptor_needs_edit = true;
                                            } else {
                                                echo "\nDesired response already in correct position.";
                                            }
                                        }
                                        if ($response_set_has_descriptor_needs_edit) {
                                            reorderResponses($item_responses, $descriptor->getID(), $order);
                                        } else {
                                            echo "\nResponse set does not have descriptor.";
                                        }
                                    } else {
                                        echo "\nNo item responses for item {$form_element->getElementID()}.";
                                    }
                                }
                            }
                        } else {
                            echo "\nNo form elements found for form {$author->getFormID()}. Skipping...";
                        }
                    }
                } else {
                    echo "\n\nNo form authors found for the given course.";
                }
            } else {
                echo "\n\nSpecified descriptor not found. Ending script.";
            }

            echo "\n";
            exit;
            break;
        case "--help":
        case "--usage":
        default :
            show_this_usage();
            break;
    }
}

function reorderResponses($item_responses, $descriptor_id, $desired_order) {
    global $db;
    foreach ($item_responses as $item_response) {
        if ($item_response->getARDescriptorID() != $descriptor_id) {
            if ($item_response->getOrder() >= $desired_order) {
                if ($item_response->fromArray(array($item_response->getOrder()+1))->update()) {
                    echo "\nRepositioned item response {$item_response->getID()} to {$item_response->getOrder()}";
                } else {
                    echo "\nUnable to reposition item response {$item_response->getID()} to {$item_response->getOrder()}, DB said: {$db->ErrorMsg()}.";
                }
            }
        } else {
            if ($item_response->getOrder() != $desired_order) {
                if ($item_response->fromArray(array("order" => $desired_order))->update()) {
                    echo "\nRepositioned item response --- WITH MATCHING DESCRIPTOR -- {$item_response->getID()} to {$item_response->getOrder()}";
                } else {
                    echo "\nUnable to reposition item response --- WITH MATCHING DESCRIPTOR -- {$item_response->getID()} to {$item_response->getOrder()}, DB said: {$db->ErrorMsg()}.";
                }
            }
        }
    }
}

// Execute
run($argc, $argv);