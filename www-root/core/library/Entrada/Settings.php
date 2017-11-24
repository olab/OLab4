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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Entrada_Settings extends Entrada_Base {

    protected $setting_id,
              $shortname,
              $organisation_id,
              $value;

    protected static $table_name = "settings";
    protected static $default_sort_column = "setting_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->setting_id;
    }

    public function getShortname() {
        return $this->shortname;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getValue() {
        return $this->value;
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->setting_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }

    public function update() {
        global $db;
        if (isset($this->setting_id)) {
            if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`setting_id` = ".$db->qstr($this->setting_id))) {
                return $this;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function delete() {
        $this->active = 0;
        return $this->update();
    }

    public static function fetchByID($setting_id = 0) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "setting_id", "value" => $setting_id, "method" => "=", "mode" => "AND")
            )
        );
    }
    
    public static function fetchByShortname($shortname, $organisation_id = 1) {
        global $db;
        
        $query = "SELECT * FROM `settings` 
                    WHERE `shortname` = ? AND 
                    (`organisation_id` = ? OR `organisation_id` IS NULL)
                    ORDER BY CASE WHEN `organisation_id` IS NULL THEN 1 ELSE 0 END, `organisation_id` ASC";
        $result = $db->GetRow($query, array($shortname, $organisation_id));
        if ($result) {
            return new self($result);
        } else {
            return false;
        }
    }
    
    public static function fetchAllRecords($organisation_id = 1) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "organisation_id",
                "method"    => "=",
                "value"     => $organisation_id
            ),
            array(
                "mode"      => "OR",
                "key"       => "organisation_id",
                "method"    => "=",
                "value"     => $organisation_id
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND");
        $output = array();

        if ($objs && @count($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    public static function fetchValueByShortname($shortname, $organisation_id = 1) {
        $self = new self();
        return $self->read($shortname, $organisation_id);
    }

    public function read($shortname, $organisation_id = 1) {
        global $db;
        
        $query = "SELECT * FROM `settings` 
                    WHERE `shortname` = ? AND 
                    (`organisation_id` = ? OR `organisation_id` IS NULL)
                    ORDER BY CASE WHEN `organisation_id` IS NULL THEN 1 ELSE 0 END, `organisation_id` ASC";
        $result = $db->GetRow($query, array($shortname, $organisation_id));
        if ($result) {
            $self = new self($result);
            return $self->getValue();
        }

        return false;
    }

}
