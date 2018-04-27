<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Script to decode all of the HTML encoded strings in the global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
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
 * Array of HTML encoded entities that could be in the objective_name field.
 */

$entities = array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;");
$replacements = array("&", "<", ">", "\"", "'");

$objective_set = new Models_ObjectiveSet();
$cvr = $objective_set->fetchRowByShortname("contextual_variable_responses");
$procedure_attribute = $objective_set->fetchRowByShortname("procedure_attribute");

$objective_model = new Models_Objective();
$objectives = $objective_model->fetchAllByObjectiveSetIDs(array($cvr->getID(),$procedure_attribute->getID()));
$objectives = array_merge($objectives, $objective_model->fetchAllByObjectiveSetIDs(array($cvr->getID(),$procedure_attribute->getID()), 0));
$count = 0;
if ($objectives) {
    foreach ($objectives as $objective) {
        $new_name = str_replace($entities, $replacements, $objective->getName(), $num_replacements);
        if ($num_replacements > 0) {
            //We have a match, lets fix it
            $objective->setObjectiveName($new_name);
            $objective->update();
            $count++;
            echo $translate->_("Updated objective: ".$objective->getID()."\n");
        }
    }
    echo $translate->_("Updated " . $count . " objectives");
} else {
    echo "No objectives were found";
}
