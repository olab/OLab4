<?php
/**
 * Entrada Tools [ https://entrada.org ]
 *
 * Create User Photos
 * 
 * Run this script to create official user photos from JPG files in the
 * tools/data directory. Photos are created as proxy_id-official then stored in
 * the STORAGE_USER_PHOTOS directory that is defined in config.inc.php.
 * 
 * Developer Instructions:
 *
 * 1. Copy all original JPG files into the developer/tools/data directory in the
 *    following format: Lastname_Firstname.jpg and/or StudentNumber.jpg
 *    
 * 2. Run "php create-user-photos.php -status" to see all of the files that
 *    this script was able to match and not match based on what it found in the
 *    AUTH_DATABASE.user_data table.
 *    
 * 3. When you are satisfied that you are able to process the majority of the
 *    photos run "php create-user-photos.php -create" to create both the full size
 *    image (250 x 250) and the thumbnail (128 x 128) and have them saved to the
 *    STORAGE_USER_PHOTOS directory that is defined in config.inc.php.
 *   
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    __DIR__ . "/../../www-root/core",
    __DIR__ . "/../../www-root/core/includes",
    __DIR__ . "/../../www-root/core/library",
    __DIR__ . "/../../www-root/core/library/vendor",
    get_include_path(),
)));

set_time_limit(0);

/**
 * Register the Composer autoloader.
 */
require_once("autoload.php");

require_once("config/settings.inc.php");

require_once("functions.inc.php");
require_once("dbconnection.inc.php");

$data_directory	= __DIR__ . "/data";

$action = (isset($_SERVER["argv"][1]) ? trim($_SERVER["argv"][1]) : "");

if (!in_array($action, ["-status", "-create"])) {
    $action = "-usage";
}

$filename_name_separator = "_";

$filename_name_reverse = true;

$photo_width = 250;
$photo_height = 250;
$thumb_width = 128;
$thumb_height = 128;

/**
 * This function (used only within this file) takes a $filename in the format of Lastname_Firstname.jpg and tries
 * to lookup a corresponding proxy_id from the entrada_auth.user_data table. Alternatively the filename could be
 * number.jpg (i.e. 72564589.jpg) and it will assume you're looking for entrada_auth.user_data.number = 72564589.
 *
 * @param string $filename
 * @param string $filename_name_separator
 * @param bool $filename_name_reverse
 * @return array
 */
function create_user_photos_fetch_user($filename = "", $filename_name_separator = "_", $filename_name_reverse = true) {
    $filename = clean_input($filename, "file");
    if ($filename && $filename_name_separator) {
        $pieces = pathinfo($filename);
        if ($pieces && isset($pieces["filename"]) && isset($pieces["extension"]) && (in_array($pieces["extension"], ["jpg", "jpeg"]))) {
            if (is_numeric($pieces["filename"]) && ($number = clean_input($pieces["filename"], "int"))) {
                $user = new Models_User();
                $result = $user->fetchRowByNumber($number);
                if ($result) {
                    return ["found" => 1, "record" => $result];
                }
            } else {
                $fullname = explode($filename_name_separator, $pieces["filename"]);
                if ($fullname && (count($fullname) == 2)) {

                    $firstname = trim($fullname[($filename_name_reverse ? 1 : 0)]);
                    $lastname = trim($fullname[($filename_name_reverse ? 0 : 1)]);

                    $user = new Models_User();
                    $results = $user->fetchAllByName($firstname, $lastname);
                    if ($results) {
                        $found = count($results);
                        if ($found == 1) {
                            return ["found" => $found, "record" => $results[0]];
                        } else {
                            return ["found" => $found, "record" => false];
                        }
                    }
                }
            }
        }
    }

    return ["found" => 0, "record" => false];
}

if ($action != "-usage") {
    $matches = [
        "none" => [],
        "single" => [],
        "multi" => [],
    ];

    if (is_dir($data_directory)) {
        if ($handle = opendir($data_directory)) {
            while (($filename = readdir($handle)) !== false) {
                if (substr($filename, 0, 1) != ".") {
                    $fetch_user = create_user_photos_fetch_user($filename, $filename_name_separator, $filename_name_reverse);
                    if ($fetch_user["found"] < 1) {
                        $matches["none"][] = $filename;
                    } else if ($fetch_user["found"] == 1) {
                        $matches["single"][$filename] = $fetch_user["record"];
                    } else {
                        $matches["multi"][] = $filename;
                    }
                }
            }
            closedir($handle);
        } else {
            echo "\n[ERROR] Unable to open your data directory [" . $data_directory . "] .\n\n";
            exit;
        }
    } else {
        echo "\n[ERROR] Your data directory [" . $data_directory . "] is not a directory.\n\n";
        exit;
    }
}

switch ($action) {
    case "-status" :
        $reported = 0;

        $files = count($matches["single"]);
        if ($files) {
            $reported++;

            echo "\nThe following files are matched successfully:";
            echo "\n- " . implode("\n- ", array_keys($matches["single"]));
            echo "\n";
        }

        $files = count($matches["multi"]);
        if ($files) {
            $reported++;

            echo "\nThe following files have multiple matches and will be skipped:";
            echo "\n- " . implode("\n- ", $matches["multi"]);
            echo "\n";
        }

        $files = count($matches["none"]);
        if ($files) {
            $reported++;

            echo "\nThe following files have no corresponding matches and will be skipped:";
            echo "\n- " . implode("\n- ", $matches["none"]);
            echo "\n";
        }

        if (!$reported) {
            echo "\n[ERROR] You do not appear to have any jpeg files in your data directory to process.";
        }

        break;
    case "-create" :
        /*
         * Global variable required in the process_user_photo() function.
         */
        $VALID_MAX_DIMENSIONS = [
            "photo-width" => $photo_width,
            "photo-height" => $photo_height,
            "thumb-width" => $thumb_width,
            "thumb-height" => $thumb_height,
        ];

        echo "\nResizing and creating official files for perfectly matched users:\n";

        $files = count($matches["single"]);
        if ($files) {
            foreach ($matches["single"] as $filename => $ENTRADA_USER) {
                if (process_user_photo($data_directory . "/" . $filename, $ENTRADA_USER->getID(), "official")) {
                    echo "\n[SUCCESS] Created official photo for " . $ENTRADA_USER->getFullname() . " [" . $ENTRADA_USER->getID() . "].";
                } else {
                    echo "\n[ERROR] Unable to create official photo for " . $ENTRADA_USER->getFullname() . " [" . $ENTRADA_USER->getID() . "].";
                }
            }
        }
        break;
    case "-usage" :
    default :
        echo "\nUsage: " . basename(__FILE__) . " [options]";
        echo "\n   -usage                Brings up this help screen.";
        echo "\n   -status               Shows the status of each photo.";
        echo "\n   -create               Creates all of the matchable pictures.";
        break;
}

echo "\n\n";
