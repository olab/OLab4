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
* A model for handling evaluation forms
*
* @author Organisation: Queen's University
* @author Unit: School of Medicine
* @author Developer: James Ellis <james.ellis@queensu.ca>
* @copyright Copyright 2014 Queen's University. All Rights Reserved.
*/

class Models_Evaluation_Form extends Models_Base {
    protected   $eform_id,
                $organisation_id,
                $target_id,
                $form_parent,
                $form_title,
                $form_description,
                $form_active,
                $updated_date,
                $updated_by;

    protected static $table_name = "evaluation_forms";
    protected static $default_sort_column = "eform_id";
    protected static $primary_key = "eform_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getFormID() {
        return $this->eform_id;
    }

    public function getID() {
        return $this->eform_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getTargetID() {
        return $this->target_id;
    }

    public function getFormParent() {
        return $this->form_parent;
    }

    public function getParentID() {
        return $this->form_parent;
    }

    public function getFormTitle() {
        return $this->form_title;
    }

    public function getFormDescription() {
        return $this->form_description;
    }

    public function getFormActive() {
        return $this->form_active;
    }

    public function getActive() {
        return $this->form_active;
    }

    public function update() {
        global $db;
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`eform_id` = ".$db->qstr($this->getID()))) {
            return true;
        } else {
            return false;
        }
    }

    public function delete() {
        $this->form_active = 0;
        return $this->update();
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->eform_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }

    public static function fetchAllByAuthorAndTitle ($proxy_id, $title, $active = 1) {
        global $db;
        
        $output = array();

        $query = "SELECT a.* FROM `".static::$table_name."` AS a
                    JOIN `evaluation_form_contacts` AS b
                    ON a.`eform_id` = b.`eform_id`
                    WHERE b.`contact_role` = 'author'
                    AND b.`proxy_id` = ?
                    AND a.`form_title` = ?
                    AND a.`form_active` = ?";
        $results = $db->GetAll($query, array($proxy_id, $title, $active));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new Models_Evaluation_Form($result);
            }
        }

        return $output;
    }
}
