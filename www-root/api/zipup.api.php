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
 * Outputs all files found in a week of a course.
 * I used UCLA's code a bit here, and made sure to reuse the same library they were going to use.
 * which is called: zipstream
 *
 * @see Composer file & maennchen/zipstream-php on github.
 * @author Craig Parsons <Craig.Parsons@ubc.ca>
 * @copyright Copyright 2017 University of British Columbia. All Rights Reserved.
 */

/**
 * Disable gzip, which forces Transfer-Encoding: chunked for large files.
 */
ini_set("zlib.output_compression", "Off");
ini_set("output_buffering", "Off");
ini_set("output_handler", "");
apache_setenv("no-gzip", 1);

/**
 * Turn off error reporting so that zip files don't get interleaved with error messages.
 */
//error_reporting(0);

/**
 * Set no maximum execution time for this script, so that it doesn't timeout
 * in the middle of a big download.
 */
set_time_limit(0);

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

/**
 * An array of files to zip with their internal/external file names. Files will
 * be added to this array when it has been verified that they exist and are
 * viewable by the user. They will be zipped up at the end after headers have been set.
 */
$files_to_zip = array();

if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    $headerLocation = '';
    if (isset($_SERVER["REQUEST_URI"])) {
        $headerLocation .= "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url")));
    }
    header("Location: ".ENTRADA_URL.$headerLocation);
    exit;
}

if ((isset($_GET["cunit_id"]))) {
    $cunit_id = clean_input(
        $_GET["cunit_id"], array("nows", "int")
    );
}
if ((isset($_GET["course_id"]))) {
    $course_id = clean_input(
        $_GET["course_id"], array("nows", "int")
    );
}

$batchWeek = new Entrada_BatchUnit();

$files_to_zip = $batchWeek->getFilesToZipByCourseUnit($cunit_id);
$file_name = $batchWeek->getZipFileNameByCourseUnit($cunit_id);

if (count($files_to_zip) > 0) {

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/x-zip");
    header("Content-Disposition: attachment; filename=\"".$file_name.'.zip"');
    header("Content-Transfer-Encoding: binary");
    setcookie("batch-download-completed", "true");

    $zipstream = new ZipStream\ZipStream(null, array("large_file_size" => 1));

    foreach ($files_to_zip as $file_to_zip) {
        $zipstream->addFileFromPath(
            $file_to_zip["name"],
            $file_to_zip["path"]
        );
    }

    // Downloads the zip file.
    $zipstream->finish();

    $batchWeek->addStatistics($files_to_zip);
}
