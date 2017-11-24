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
 * A class to manage the search filters related to the advanced search widget.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_AdvancedSearchHelper extends Entrada_Base {

    /**
     * Cleans up the filters session variables, keeping them consistent with the given source array.
     *
     * The $source array is what to compare against. We check in this array (passed by reference) for request variables, usually the $_GET or $_POST array. The source array
     * will always be a simple array, indexed 0..n with data elements being the IDs of the relevant data.
     *
     * The session will contain [module][submodule]["selected_filters"][filter type][related id] => "related name"
     *
     * @param array $source
     * @param string $module
     * @param string $submodule
     * @param string $filter_type
     */
    public static function cleanupSessionFilters(&$source, $module, $submodule, $filter_type) {

        if (isset($source[$filter_type]) && is_array($source[$filter_type])) {

            // Only keep the filters posted to us (the whole lot of them will be posted each time), extra ones in the session must be removed.
            if (isset($_SESSION[APPLICATION_IDENTIFIER][$module][$submodule]["selected_filters"][$filter_type])) {
                foreach ($_SESSION[APPLICATION_IDENTIFIER][$module][$submodule]["selected_filters"][$filter_type] as $id => $name) {
                    if (!in_array($id, $source[$filter_type])) {
                        unset($_SESSION[APPLICATION_IDENTIFIER][$module][$submodule]["selected_filters"][$filter_type][$id]);
                    }
                }
            }
        } else {
            if (isset($_SESSION[APPLICATION_IDENTIFIER][$module][$submodule]["selected_filters"][$filter_type])) {
                unset($_SESSION[APPLICATION_IDENTIFIER][$module][$submodule]["selected_filters"][$filter_type]);
            }
        }

        if (empty($_SESSION[APPLICATION_IDENTIFIER][$module][$submodule]["selected_filters"])) {
            unset($_SESSION[APPLICATION_IDENTIFIER][$module][$submodule]["selected_filters"]);
        }
    }

    /**
     * Find the comparison_item by fieldname inside of the advanced search datasource.
     * Valid fieldnames are "target_id" and "target_label", but the logic can apply to any indecies.
     *
     * @param $advancesearch_datasource
     * @param $fieldname
     * @param $comparison_item
     * @return bool
     */
    public static function getSearchItemByField($advancesearch_datasource, $fieldname, $comparison_item) {
        if (!$fieldname || empty($advancesearch_datasource)) {
            return false;
        }
        foreach ($advancesearch_datasource as $datasource_item) {
            if (array_key_exists($fieldname, $datasource_item)) {
                if ($datasource_item[$fieldname] == $comparison_item) {
                    return $datasource_item;
                }
            }
        }
        return false;
    }

    /**
     * Given an array of model objects (or arrays via a result set), create an advanced search data source array.
     * Specify the index in the source array(s) for which index/property will be the target_id and target_label.
     *
     * @param $records
     * @param $target_id_index
     * @param $target_label_index
     * @param array $additional_properties
     * @return array
     */
    public static function buildSearchSource($records, $target_id_index, $target_label_index, $additional_properties = array()) {
        if (empty($records)) {
            return array();
        }

        $search_datasource = array();
        foreach ($records as $record) {
            if (is_object($record)) {
                $record = $record->toArray();
            }
            if (!is_array($record)) {
                continue; // skip if we failed to make or receive an array as input
            }
            $search_item = array();
            $search_item["target_id"] = @$record[$target_id_index];
            $search_item["target_label"] = @$record[$target_label_index];

            // Add additional properties (but don't overwrite target_id or target_label)
            foreach ($additional_properties as $additional_property) {
                if ($additional_property != "target_id" && $additional_property != "target_label") {
                    $search_item[$additional_property] = @$record[$additional_property];
                }
            }
            $search_datasource[] = $search_item;
        }
        return $search_datasource;
    }
}