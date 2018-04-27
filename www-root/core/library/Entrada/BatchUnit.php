<?php

/**
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
 * Class Entrada_Batch
 * Reusable Class to help gather a list of files compress in order to output as a file to the user.
 *
 * @author Organisation: UBC
 * @author Unit: MedIT
 * @author Developer: Craig Parsons(Craig.Parsons@ubc.ca)
 * @copyright Copyright 2017 University of British Columbia. All Rights Reserved.
 */

/**
 * Functions related to zipping up multiple event resource files into one.
 */
class Entrada_BatchUnit
{
    protected $statistic_model;

    public function __construct($statistic_model = false)
    {
        if (!$statistic_model instanceof Models_Statistic) {
            $this->statistic_model = new Models_Statistic();
        } else {
            $this->statistic_model = $statistic_model;
        }
    }

    /**
     * Removes whitespace and illegal characters from path names.
     *
     * @param string $path
     *
     * @return string
     */
    private function getCleanPath($path)
    {
        return preg_replace('/\s+/', ' ', preg_replace('/[^a-zA-Z0-9_\s\-\(\)\.]/', ' ', $path));
    }

    /**
     * Return an array of files and file paths to zip up.
     * requires cunit_id (integer $cunit_id)
     * to get the measurement of time in which we are returning the events in. Ex: weeks
     *
     * @param integer $cunit_id
     *
     * @return array Each file will be arrays $file_to_zip["name"], $file_to_zip["path"]
     *               name is the name of what the filename will be inside the zip file.
     *               path will be the path and filename of what you are adding.
     */
    public function getFilesToZipByCourseUnit($cunit_id)
    {
        global $db;

        if ($cunit_id) {
            $event_ids = [];

            $unit = Models_Course_Unit::fetchRowByID($cunit_id);
            $events = $unit->getEvents();

            foreach ($events as $event) {
                $event_ids[] = $event->getID();
            }
        }

        return $this->getFileNamesAndDirectoriesByEventIds($event_ids);
    }

    /**
     * Return an array of files and file paths to zip up.
     *
     * @param integer $event_ids
     *
     * @return array
     */
    public function getFileNamesAndDirectoriesByEventIds($event_ids)
    {
        $files_to_zip = array();

        $events_repository = Models_Repository_Events::getInstance();
        $efile_ids = $events_repository->fetchEventResourcesByEventIDs($event_ids);

        foreach ($efile_ids as $efile_id => $model_event) {
            foreach ($model_event as $event_id => $event_resource) {
                $file_data = array();

                // Double checks that files are there.
                if ((file_exists(FILE_STORAGE_PATH.DIRECTORY_SEPARATOR.$efile_id)) && (is_readable(FILE_STORAGE_PATH.DIRECTORY_SEPARATOR.$efile_id))) {
                    $files_to_zip[] = array(
                        "efile_id" => $efile_id,
                        "name" => $event_resource->file_name,
                        // The files are just numbers, they don't contain any file type extention.
                        "path" => FILE_STORAGE_PATH.DIRECTORY_SEPARATOR.$efile_id
                    );
                }
            }
        }

        return $files_to_zip;
    }

    /**
     * Return the Zip Files file name description.
     *
     * @param integer $cunit_id
     *
     * @return string
     */
    public function getZipFileNameByCourseUnit($cunit_id)
    {
        $filename = 'blank';

        $cunit = Models_Course_Unit::fetchRowByID($cunit_id);
        $course = Models_Course::fetchRowByID($cunit->getCourseID());

        $title = $cunit->getUnitTitle();

        if ($cunit->getUnitCode()) {
            $title = $cunit->getUnitCode() . "-" . $title;
        }

        if ($course->getCourseCode()) {
            $title = $course->getCourseCode() . "-" . $title;
        }

        // Make sure file name is correct, data entry might have bad characters.
        $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $title);
        $filename = mb_ereg_replace("([\.]{2,})", '', $filename);
        $filename = str_replace( array('(',')'), '', $filename);
        $filename = str_replace(' ', '_', $filename);

        return $filename;
    }

    /**
     * Add file view statistics for each of the files to zip
     *
     * @param array $files_to_zip array("efile_id" => each file id)
     */
    public function addStatistics(array $files_to_zip)
    {
        foreach ($files_to_zip as $file_to_zip) {
            $this->statistic_model->addStatistic("events", "file_download", "file_id", $file_to_zip["efile_id"]);
        }
    }
}
