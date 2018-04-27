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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

abstract class Models_Repository implements Models_IRepository {

    public function fetchOneByID($id) {
        $models = $this->fetchAllByIDs(array($id));
        if (isset($models[$id])) {
            return $models[$id];
        } else {
            return false;
        }
    }

    public function toArrays(array $objects) {
        return array_map(function ($object) { return $object->toArray(); }, $objects);
    }

    public function flatten(array $data_by_key) {
        $flattened_data = array();
        foreach ($data_by_key as $key => $data) {
            foreach ($data as $sub_key => $sub_data) {
                $flattened_data[$sub_key] = $sub_data;
            }
        }
        return $flattened_data;
    }

    /**
     * @return Models_Base $object
     */
    abstract protected function fromArray(array $result);

    protected function fromArrays($results) {
        return $this->fromArraysByMany(array(), $results);
    }

    protected function fromArraysBy($key, $results) {
        return $this->fromArraysByMany(array($key), $results);
    }

    protected function fromArraysByMany(array $keys, $results) {
        global $db;
        if ($results === false) {
            application_log("error", "Database error in ".get_called_class().". DB Said: " . $db->ErrorMsg());
            throw new Exception("Database error fetching data in ".get_called_class()." records");
        } else {
            $objects_by_key = array();
            foreach ($results as $result) {
                $object = $this->fromArray($result);
                $objects_for_value = &$objects_by_key;
                foreach ($keys as $key) {
                    $value = $result[$key];
                    if (!isset($objects_for_value[$value])) {
                        $objects_for_value[$value] = array();
                    }
                    $objects_for_value = &$objects_for_value[$value];
                }
                $objects_for_value[$object->getID()] = $object;
            }
            return $objects_by_key;
        }
    }

    protected function quoteIDs(array $ids) {
        global $db;
        return implode(", ", array_map(array($db, "qstr"), $ids));
    }
}
