<?php
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

$items = Models_Assessments_Item::fetchAllRecords();
$deleted_items = Models_Assessments_Item::fetchAllRecords(time());
$all_items = array_merge($items, $deleted_items);

if ($all_items) {
    foreach ($all_items as $item) {
        switch ($item->getItemtypeID()) {
            case "1" :
            case "2" :
            case "3" :
            case "9" :
            case "11" :
            case "12" :
                /*
                 * Single response items
                 */
                if ($item->fromArray(array("mandatory" => 1))->update()) {
                    echo "\nSuccessfully updated item {$item->getID()}.";
                } else {
                    echo "\nUnable to update item {$item->getID()}.";
                }
                break;
            case "4" :
            case "5" :
            case "6" :
                /*
                 * Multi response items
                 */
                if ($item->fromArray(array("mandatory" => 1))->update()) {
                    echo "\nSuccessfully updated item {$item->getID()}.";
                } else {
                    echo "\nUnable to update item {$item->getID()}.";
                }
                break;
            case "7" :
            case "10" :
                /*
                 * Free text items
                 */
                if ($item->fromArray(array("mandatory" => 0))->update()) {
                    echo "\nSuccessfully updated item {$item->getID()}.";
                } else {
                    echo "\nUnable to update item {$item->getID()}.";
                }
                break;
            case "8" :
                /*
                 * Date items
                 */
                if ($item->fromArray(array("mandatory" => 0))->update()) {
                    echo "\nSuccessfully updated item {$item->getID()}.";
                } else {
                    echo "\nUnable to update item {$item->getID()}.";
                }
                break;
        }
    }
}