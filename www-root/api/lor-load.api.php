<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 *  Provides learning object files loading them thought entrada;
 *  When requesting a file this api os loaded twice:
 *  In the first request the id of learning object instance should be specified,
 *  with that the learning object in retrieved and this api file is called again
 *  to load the file in the browser.
 *
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joabe Mendes <jm409@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */
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

// Verifying if this is a authenticated request
if (!isset($_SESSION["isAuthorized"]) || !(bool)$_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
}

// If the is set on the request, this is the first time the api is being called.
// Let's verify if there's a learning module instance with that id a retrieve it if exists.
if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], "int"))) {
    $learning_object_id = $tmp_input;

    // Retrieving the leaning resource based on the specified id
    $learning_object = Models_LearningObject::fetchRowByID($learning_object_id);

    if ($learning_object) {

        // Check if it's still viewable ?
        if ((time() < $learning_object->getViewableStart() && $learning_object->getViewableStart() != 0) ||
            (time() > $learning_object->getViewableEnd() && $learning_object->getViewableEnd() != 0)) {

            add_error($translate->_("The learning resource is not viewable at this time."));
            echo display_error();
        }

        $lrs_endpoint = Entrada_Settings::read("lrs_endpoint");
        $lrs_version = Entrada_Settings::read("lrs_version");
        $lrs_username = Entrada_Settings::read("lrs_username");
        $lrs_password = Entrada_Settings::read("lrs_password");

        if (!$lrs_endpoint || !$lrs_version || !$lrs_username || !$lrs_password) {
            $lrs_endpoint = ENTRADA_URL . "/api/lrs-to-stats.api.php";
            $lrs_version = "1.0.0";
            $lrs_username = "null";
            $lrs_password = "null";
        }

        $lrs_actor = array(
            "name" => $ENTRADA_USER->getFullname(false),
            "mbox" => $ENTRADA_USER->getEmail(),
            "objectType" => "Agent"
        );

        // There're 3 kings of learning resource: 'tincan', 'scorm' and 'url'.
        // For each of those types, except 'url', we should create a uri with the file information to call this api again

        switch ($learning_object->getObjectType()) {
            case "tincan" :

                $module_directory = $learning_object->getFilenameHashed();
                $hash_path = Entrada_Utilities_Files::getPathFromFilename($learning_object->getFilenameHashed());
                $xml_file = STORAGE_LOR . "/" . $hash_path . $module_directory . "/tincan.xml";

                // By the tincan xml file we can load the authentication info to request the file in the origin LMS
                $tincan = new Entrada_LearningObject_TinCan($xml_file);

                if ($tincan) {
                    $_SESSION[$module] = "auth";
                    $registration_id = $tincan->getRegistrationID();
                    $state = $tincan->getGlobalParametersAndState(
                        ENTRADA_URL . "/" . $learning_object->getFilenameHashed(),
                        $lrs_actor
                    );
                    $statement = $tincan->launchStatement($registration_id, $lrs_actor);
                    $basic_auth = "Basic " . base64_encode($lrs_username . ":" . $lrs_password);

                    //Here we create the url with the learning object info
                    $launch_url = ENTRADA_URL . "/object/" . $learning_object->getFilenameHashed() . "/story.html?" .
                        http_build_query(
                            array(
                                "endpoint" => $lrs_endpoint,
                                "auth" => $basic_auth,
                                "actor" => json_encode($lrs_actor),
                                "registration" => $registration_id
                            ),
                            '',
                            '&',
                            PHP_QUERY_RFC3986
                        );

                    // When heading to the launch url, this api will be called again with the info to load the file
                    // through the url
                    header("Location: " . $launch_url);
                    exit;
                }
                break;
            case "scorm" :

                $module_directory = $learning_object->getFilenameHashed();
                $hash_path = Entrada_Utilities_Files::getPathFromFilename($learning_object->getFilenameHashed());
                $xml_file = STORAGE_LOR . "/" . $hash_path . $module_directory . "/imsmanifest.xml";

                // By the scrom xml file we can load the information about the file
                $scorm = Entrada_LearningObject_Scorm::loadScormModule($xml_file);

                if ($scorm) {

                    $registration_id = $scorm->getRegistrationID();
                    $state = $scorm->getGlobalParametersAndState(
                        ENTRADA_URL . "/" . $learning_object->getFilenameHashed(),
                        $lrs_actor
                    );

                    $statement = $scorm->launchStatement($registration_id, $lrs_actor);
                    $basic_auth = "Basic " . base64_encode($lrs_username . ":" . $lrs_password);

                    //Here we create the url with the learning object info
                    $launch_url = ENTRADA_URL . "/object/" . $learning_object->getFilenameHashed() . "/" . $scorm->getLaunchFile();
                    // Different from the 'tincan' type, the lauch url will be loaded through javascript
                    // This is done because some file information is loaded directly from the school LMS

                    $delayseconds = 2;

                    $LMS_api = ($scorm->getScormVersion() == SCORM_12) ? 'API' : 'API_1484_11';

                    ?>
                    <script src="<?php echo ENTRADA_URL; ?>/javascript/jquery/jquery.min.js?release=<?php html_encode(APPLICATION_VERSION) ?>"></script>
                    <script>

                        // This is whole javascript routine provides the authentication settings to load the file

                        //<![CDATA[
                        <?php
                        if ($scorm->getScormVersion() == SCORM_2004) {
                            include_once(ENTRADA_ABSOLUTE . "/javascript/scorm.2004.js.php");
                        } else {
                            include_once(ENTRADA_ABSOLUTE . "/javascript/scorm.12.js.php");
                        }
                        ?>

                        var myApiHandle = null;
                        var myFindAPITries = 0;

                        function myGetAPIHandle() {
                            myFindAPITries = 0;
                            if (myApiHandle == null) {
                                myApiHandle = myGetAPI();
                            }
                            return myApiHandle;
                        }

                        function myFindAPI(win) {
                            while ((win.<?php echo $LMS_api; ?> == null) && (win.parent != null) && (win.parent != win)) {
                                myFindAPITries++;
                                // Note: 7 is an arbitrary number, but should be more than sufficient
                                if (myFindAPITries > 7) {
                                    return null;
                                }
                                win = win.parent;
                            }
                            return win.<?php echo $LMS_api; ?>;
                        }

                        // hun for the API - needs to be loaded before we can launch the package
                        function myGetAPI() {
                            var theAPI = myFindAPI(window);
                            if ((theAPI == null) && (window.opener != null) && (typeof(window.opener) != "undefined")) {
                                theAPI = myFindAPI(window.opener);
                            }
                            if (theAPI == null) {
                                return null;
                            }
                            return theAPI;
                        }

                        function doredirect() {
                            $("#countdown-div").html(
                                "<div style=\"text-align: center;\">" +
                                "<p><?php echo $translate->_("Launching requested learning module... Please wait "); ?>" +
                                "<span id=\"countdown\"><?php echo $delayseconds ?></span> seconds.<p></div>"
                            );
                            var e = document.getElementById("countdown");
                            var cSeconds = parseInt(e.innerHTML);
                            var timer = setInterval(function () {
                                if (cSeconds && myGetAPIHandle() == null) {
                                    e.innerHTML = --cSeconds;
                                } else {
                                    clearInterval(timer);
                                    $("#countdown-div").html(
                                        "<div style=\"text-align: center;\">" +
                                        "<p>Launching requested learning module now...</p>" +
                                        "</div>"
                                    );
                                    // When heading to the launch url, this api will be called again with the info to load the file
                                    // through the url
                                    window.open("<?php echo $launch_url; ?>");

                                }
                            }, 1000);
                        }

                        //]]>
                    </script>
                    <noscript>
                        <meta http-equiv="refresh" content="0;url=<?php echo $launch_url ?>"/>
                    </noscript>

                    <div id="countdown-div" style="text-align: center;">Launching Learning Module... Please Wait</p></div>

                    <script>
                        doredirect();
                    </script>
                    <?php
                    exit;
                } else {
                    // Display an error about the Scorm file and log it.
                }
                break;
            case "link" :
                header("Location:" . $learning_object->getUrl());
                break;
        }
    } else {
        // If the leaning resource doesn't exist lets return a 400 response.
        add_error($translate->_("400 Bad Request Error: The specified leaning module id doesn't exists"));
        echo display_error();
    }
} else {

    // If the id isn't set the api is being called again to load the file.
    // The path to the file is retrieved by the lauch url defined when this api was called
    // for the first time, lets treat that uri to get that file path

    $raw_path = $_SERVER["REQUEST_URI"];

    if (strstr($raw_path, "../")) {
        add_error($translate->_("400 Bad Request Error: Invalid characters in the request."));
        echo display_error();
    }

    try {
        // Here we build the direct path to the file in the storage folder
        $full_path = preg_replace(
            "/((?<=\.html).*)/",
            "", $raw_path
        );
        $path = str_replace("/object", "", $full_path);
        $path_parts = explode("/", $path);
        $hash_path = Entrada_Utilities_Files::getPathFromFilename($path_parts[1]);
        $file = STORAGE_LOR . "/" . $hash_path . substr($path, 1);
    } catch (Exeption $e) {
        application_log("error", "Unable to build path to the file: " . $e->getMessage());
    }

    // Using the FlySystem library we verify if the files exists, identify it's mime time and then we
    // Read the file in the browser
    if ($filesystem->has($file)) {
        ob_clear_open_buffers();

        // Reading the file mim type
        // $mimetype = $filesystem->getMimetype($file);
        // We cannot use this right now because of a bug in FlySystem
        // https://github.com/thephpleague/flysystem/issues/828

        // Reading the mime type manually with some help of FlySystem
        try {
            $location = $config->entrada_storage . "/" . $file;
            $finfo = new Finfo(FILEINFO_MIME_TYPE);
            $mimetype = $finfo->file($location);

            if (in_array($mimetype, ["application/octet-stream", "inode/x-empty", "text/plain"])) {
                $mimetype = League\Flysystem\Util\MimeType::detectByFilename($location);
            }
        } catch (Exception $e) {
            application_log("error", "Unable to determine mimetype when loading a learning object file: " . $e->getMessage());
        }

        //Define the header of the file
        header("Content-Type: " . $mimetype);

        //Display the file content in the browser
        echo $filesystem->read($file);

        exit;
    }
}

