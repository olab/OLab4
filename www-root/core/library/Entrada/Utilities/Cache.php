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
 * Utility to cache data
 *
 * @author Organization: Queen's University.
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Utilities_Cache extends Entrada_Utilities_Assessments_Base {
    private $cache = null;

    public function getCache() {
        return $this->cache;
    }

    public function __construct($options = array()) {
        $this->setZendCache();
    }

    private function setZendCache($days = 30) {
        $this->cache = Zend_Cache::factory(
            "Core",
            "File",
            array(
                "lifetime" => 86400 * $days,
                "automatic_serialization" => true
            ),
            array(
                "cache_dir" => CACHE_DIRECTORY
            )
        );
    }

    public function loadCache($id) {
        if (is_int($id)) {
            $id = (string)$id;
        }
        return $this->cache->load($id);
    }

    public function cacheImage($file_path, $id, $type = null) {
        if (!empty($file_path) && is_string($file_path) && file_exists($file_path)) {
            if (is_int($id)) {
                $id = (string)$id;
            }

            $file_data["mime_type"] = "";
            if (is_null($type)) {
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $file_data["mime_type"] = @finfo_file($file_info, $file_path);
                finfo_close($file_info);
            } else {
                $file_data["mime_type"] = $type;
            }

            $file_data["photo"] = "";
            $photo = fopen($file_path, "r");
            if ($photo) {
                while (!feof($photo)) {
                    $plain = fread($photo, 57 * 143);
                    $encoded = base64_encode($plain);
                    $encoded = chunk_split($encoded, 76, "\r\n");
                    $file_data["photo"] .= $encoded;
                }
            }

            if (isset($file_data["mime_type"]) && !empty($file_data["mime_type"]) && $file_data["mime_type"] != "" &&
                isset($file_data["photo"]) && !empty($file_data["photo"]) && $file_data["photo"] != "") {
                $this->cache->save($file_data, $id);
            }
        }
    }
}