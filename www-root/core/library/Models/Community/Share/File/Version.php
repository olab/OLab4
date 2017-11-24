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
 * A model for handling Communities share file versions
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 */

class Models_Community_Share_File_Version extends Models_Base {
    protected   $csfversion_id,
                $csfile_id,
                $cshare_id,
                $community_id,
                $proxy_id,
                $file_version,
                $file_mimetype,
                $file_filename,
                $file_filesize,
                $file_active,
                $updated_date,
                $updated_by;

    protected static $table_name = "community_share_file_versions";
    protected static $default_sort_column = "csfversion_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getCSFVersionID() {
        return $this->csfversion_id;
    }

    public function setCSFVersionID($csfversion_id) {
        $this->csfversion_id = $csfversion_id;
    }

    public function getCSFileID() {
        return $this->csfile_id;
    }

    public function getShareID() {
        return $this->cshare_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getFileVersion() {
        return $this->file_version;
    }

    public function getFileMimeType() {
        return $this->file_mimetype;
    }

    public function getFileName() {
        return $this->file_filename;
    }

    public function getFileSize() {
        return $this->file_filesize;
    }

    public function getFileActive() {
        return $this->file_active;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdateBy() {
        return $this->updated_by;
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute($this->table_name, $this->toArray(), "INSERT")) {
            return $this;
        } else {
            return false;
        }
    }

    public function update() {
        global $db;
        if ($db->AutoExecute($this->table_name, $this->toArray(), "UPDATE", "`csfversion_id` = " . $db->qstr($this->getCSFVersionID()))) {
            return true;
        } else {
            return false;
        }
    }

    public static function fetchAllByCSFile_ID($csfile_id = 0) {
        $self = new self();

        $constraints = array(
            array(
                "key"       => "csfile_id",
                "value"     => $csfile_id,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    public static function fetchRowByID($csfversion_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "csfversion_id", "value" => $csfversion_id, "method" => "=")
        ));
    }
}
?>