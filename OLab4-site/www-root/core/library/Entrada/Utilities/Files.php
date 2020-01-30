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
 * This class contains the logic for folders/files naming scheme. Behaviour is controlled
 * by the settings: filesystem_hash and filesystem_chunksplit which respectively
 * controls the hash used for file naming, and the chunk size used to create
 * folders.
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_Files {

    /**
     *
     * This function returns the name of the specified file in hash
     *
     * @param $file
     * @return bool|string
     */
    public static function getFileHash($file) {
        $hash_type = Entrada_Settings::read("filesystem_hash");
        if (! $hash_type) {
            $hash_type = "sha256";
        }

        if (!file_exists($file)) {
            return false;
        }

        return hash_file($hash_type, $file);
    }

    /**
     * This function returns the path to a file folder based on the file name hash.
     *
     *
     * @param $filename name of the file in hash format
     * @param int $split_size_default the size of each folder name in the path of the file
     * @return string path to the specified file
     */
    public static function getPathFromFilename($filename) {

       $split_size = Entrada_Settings::read("filesystem_chunksplit");
       $split_size = intval($split_size) ? intval($split_size) : 8;

        $file_parts = pathinfo($filename);

        if (strlen($file_parts['filename']) <= $split_size) {
            $split_size = strlen($file_parts['filename']);
        }

        $folders = chunk_split($file_parts['filename'], $split_size, '/');

        return $folders;
    }
}