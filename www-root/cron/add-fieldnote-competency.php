<?php
@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    get_include_path(),
)));


/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

$descriptor = new Models_Assessments_Response_Descriptor(array(
    "organisation_id" => 8,
    "descriptor" => "Flagged/ Concerning Performance",
    "reportable" => 1,
    "order" => 4,
    "created_date" => time(),
    "created_by" => 5555
));

if ($descriptor->insert()) {
    $query = "  SELECT a.*, b.* FROM `cbl_assessments_lu_items` AS a
            JOIN `cbl_assessments_lu_item_responses` AS b
            ON a.`item_id` = b.`item_id`
            WHERE `itemtype_id` = 13
            AND a.`deleted_date` IS NULL
            AND b.`deleted_date` IS NULL";

    $order = 2;
    $results = $db->GetAll($query);
    if ($results) {
        foreach ($results as $item) {
            $item_response = Models_Assessments_Item_Response::fetchRowByID($item["iresponse_id"]);
            if ($item_response) {
                $item_response->fromArray(array("order" => $order))->update();
            }
            $order++;
            if ($order > 4) {
                $order = 2;
            }
        }
    }

    $query = "SELECT * FROM `cbl_assessments_lu_items` WHERE `itemtype_id` = 13 AND `deleted_date` IS NULL";
    $results = $db->GetAll($query);
    $rows_inserted = 0;
    if ($results) {
        foreach ($results as $item) {
            $item_response = new Models_Assessments_Item_Response(array(
                "item_id" => $item["item_id"],
                "text" => "<ul><li>A concerning performance of an important skill, requiring correction.</li><li>Flagged status of this Field Note requires follow up and resolution of the concerning issue.</li><li>Academic Advisors, preceptors or residents may change the flagged status of this Field Note to resolved once the issue has been addressed.</li></ul>",
                "order" => 1,
                "allow_html" => 0,
                "flag_response" => 0,
                "ardescriptor_id" => $descriptor->getID()
            ));

            if (!$item_response->insert()) {
                echo "A problem occured while attempting to save the item response DB said: " . $db->ErrorMsg() . "/n";
            } else {
                $rows_inserted++;
            }
        }
    }
    echo "[" . $rows_inserted . "] item responses were successfully inserted. \n";
} else {
    echo "A problem occured while attempting to save the item response descriptor DB said: " . $db->ErrorMsg() . "/n";
}