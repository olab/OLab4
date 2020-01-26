<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Script to verify and clean up rubrics.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
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
    echo "\nUsage: rubric-consistency-check-and-fix.php [options] [form id] [rubric id]";
    echo "\n   --usage       Brings up this help screen.";
    echo "\n   --preview     Generates a preview of the changes that will take place upon execution.";
    echo "\n   --execute     Proceeds with the rubric cleanup.";
    echo "\n\n";
    exit();
}

/**
 * Find the form, rubric, and associated items. Exits script on any error.
 *
 * @param $form_id
 * @param $rubric_id
 * @return array
 */
function fetch_relevant_items($form_id, $rubric_id) {
    $form = Models_Assessments_Form::fetchRowByID($form_id);
    if (!$form) {
        echo "\nForm with id ($form_id) was not found\n";
        show_this_usage();
    }

    $rubric = Models_Assessments_Rubric::fetchRowByID($rubric_id);
    if (!$rubric) {
        echo "\nRubric with id ($rubric_id) was not found\n";
        show_this_usage();
    }

    $form_items = Models_Assessments_Form_Element::fetchAllByFormID($form_id);
    if (empty($form_items)) {
        echo "\nThere were no items found for form id $form_id.\n";
        show_this_usage();
    }

    $rubric_items = Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($rubric_id);
    if (empty($rubric_items)) {
        echo "\nThere were no rubric items found for rubric id $rubric_id\n";
        show_this_usage();
    }

    return array("form" => $form, "rubric" => $rubric, "form_items" => $form_items, "rubric_items" => $rubric_items);
}

/**
 * Examine a rubric given an array of form item objects.
 *
 * @param int $rubric_id
 * @param array $form_items
 * @param array $rubric_items
 * @return array
 */
function examine_rubric($rubric_id, $form_items, $rubric_items) {
    // Determine which items are appropriately attached to the form and which are not.
    $all_attached_to_form = array();
    $erroneous_items = array();
    $appropriate_items = array();

    foreach ($form_items as $form_item) {

        // This rubric attached to the form is the one we're looking for
        if ($form_item->getRubricID() == $rubric_id) {

            $all_attached_to_form[] = $form_item;

            // Store which ones are valid
            foreach ($rubric_items as $rubric_item) {
                if ($rubric_item->getItemID() == $form_item->getElementID()) {
                    $appropriate_items[] = $form_item;
                }
            }
        }
    }

    // Go through and find the ones that aren't valid
    foreach ($all_attached_to_form as $all_item) {
        if (!is_appropriate($appropriate_items, $all_item)) {
            $erroneous_items[] = $all_item;
        }
    }

    return array("all_attached" => $all_attached_to_form, "erroneous_items" => $erroneous_items, "appropriate_items" => $appropriate_items);
}

/**
 * Check if the passed in item exists with the maching element ID in the appropriate_items array.
 *
 * @param array $appropriate_items
 * @param Models_Assessments_Form_Element $check_item
 * @return bool
 */
function is_appropriate(&$appropriate_items, $check_item) {
    foreach ($appropriate_items as $appropriate_item) {
        if ($check_item->getElementID() == $appropriate_item->getElementID()) {
            return true;
        }
    }
    return false;
}

/**
 * Main point of execution
 *
 * @param $argc
 * @param $argv
 */
function run($argc, &$argv) {
    $action = "--usage";
    $rubric_id = $form_id = 0;
    if ($argc > 1 && !empty($argv)) {
        $action = @$argv[1];
        $form_id = @$argv[2];
        $rubric_id = @$argv[3];
    }

    switch ($action) {
        case "--preview":
            if (!$rubric_id || !$form_id) {
                show_this_usage();
            }
            $relevant_items = fetch_relevant_items($form_id, $rubric_id);
            $examination_results = examine_rubric($rubric_id, $relevant_items["form_items"], $relevant_items["rubric_items"]);

            echo "There are ".count($examination_results["all_attached"])." items for this rubric attached to the form.\n";
            echo "There are ".count($examination_results["erroneous_items"])." invalid rubric items attached to the form.\n";
            echo "There should only be ".count($examination_results["appropriate_items"])." attached to the form.\n";

            if (empty($examination_results["erroneous_items"])) {
                echo "There are no erroneously assigned form items. Exiting...\n";
                exit();
            }

            echo "The following records should be marked as deleted:";
            foreach ($examination_results["erroneous_items"] as $erroneus) {
                echo "\nRecord ID: '{$erroneus->getID()}', Element ID: '{$erroneus->getElementID()}'";
            }
            echo "\n";
            break;

        case "--execute":
            if (!$rubric_id && !$form_id) {
                show_this_usage();
            }
            $relevant_items = fetch_relevant_items($form_id, $rubric_id);
            $examination_results = examine_rubric($rubric_id, $relevant_items["form_items"], $relevant_items["rubric_items"]);

            echo "There are ".count($examination_results["all_attached"])." items for this rubric attached to the form.\n";
            echo "There are ".count($examination_results["erroneous_items"])." invalid rubric items attached to the form.\n";
            echo "There should only be ".count($examination_results["appropriate_items"])." attached to the form.\n";

            if (empty($examination_results["erroneous_items"])) {
                echo "There are no erroneously assigned form items. Exiting...\n";
                exit();
            }

            echo "\nThe following records WILL BE marked as deleted:";
            foreach ($examination_results["erroneous_items"] as $erroneus) {
                echo "\nRecord ID: '{$erroneus->getID()}', Element ID: '{$erroneus->getElementID()}'";
            }

            echo "\nType \"Yes\" to continue...\n";
            $handle = fopen ("php://stdin","r");
            $line = fgets($handle);
            if(trim($line) != 'Yes'){
                echo "Aborting.\n";
                exit;
            }
            fclose($handle);

            echo "\n";
            echo "Proceeding...\n";
            foreach ($examination_results["erroneous_items"] as $erroneus) {
                $array_version = $erroneus->toArray();
                $array_version["deleted_date"] = time();
                $array_version["updated_by"] = 1;
                $array_version["updated_date"] = time();
                $new_version = new Models_Assessments_Form_Element($array_version);
                if (!$new_version->update()) {
                    echo "ERROR updating form item with id: {$array_version["afelement_id"]}\n";
                }
            }
            echo "\nProcess complete. Records marked as deleted.\n";
            echo "\n";
            break;

        case "--help":
        case "--usage":
        default :
            show_this_usage();
            break;
    }

}

// Execute
run($argc, $argv);