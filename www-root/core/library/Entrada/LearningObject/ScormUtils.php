<?php

class Entrada_LearningObject_ScormUtils {
    /**
     * Build up the JavaScript representation of an array element
     *
     * @param string $sversion SCORM API version
     * @param array $userdata User track data
     * @param string $element_name Name of array element to get values for
     * @param array $children list of sub elements of this array element that also need instantiating
     * @return None
     */
    public static function scormBuildArray($sversion, $element_name, $children) {
        global $ENTRADA_USER;

        // reconstitute comments_from_learner and comments_from_lms
        $current = '';
        $current_subelement = '';
        $current_sub = '';
        $count = 0;
        $count_sub = 0;
        $scormseperator = '_';
        if ($sversion == SCORM_2004) { //scorm 1.3 elements use a . instead of an _
            $scormseperator = '.';
        }

        // filter out the ones we want
        $element_list = array(
            "session_key" => session_id(),
            "id" => $ENTRADA_USER->getActiveID(),
            "name" => $ENTRADA_USER->getFullname(false),
            "email" => $ENTRADA_USER->getEmail(),
            "datafromlms" => ''
        );

        // generate JavaScript
        foreach ($element_list as $element => $value) {
            if ($sversion == SCORM_2004) {
                $element = preg_replace('/\.(\d+)\./', ".N\$1.", $element);
                preg_match('/\.(N\d+)\./', $element, $matches);
            } else {
                $element = preg_replace('/\.(\d+)\./', "_\$1.", $element);
                preg_match('/\_(\d+)\./', $element, $matches);
            }
            if (count($matches) > 0 && $current != $matches[1]) {
                if ($count_sub > 0) {
                    echo '    '.$element_name.$scormseperator.$current.'.'.$current_subelement.'._count = '.$count_sub.";\n";
                }
                $current = $matches[1];
                $count++;
                $current_subelement = '';
                $current_sub = '';
                $count_sub = 0;
                $end = strpos($element, $matches[1])+strlen($matches[1]);
                $subelement = substr($element, 0, $end);
                echo '    '.$subelement." = new Object();\n";
                // now add the children
                foreach ($children as $child) {
                    echo '    '.$subelement.".".$child." = new Object();\n";
                    echo '    '.$subelement.".".$child."._children = ".$child."_children;\n";
                }
            }

            // now - flesh out the second level elements if there are any
            if ($sversion == SCORM_2004) {
                $element = preg_replace('/(.*?\.N\d+\..*?)\.(\d+)\./', "\$1.N\$2.", $element);
                preg_match('/.*?\.N\d+\.(.*?)\.(N\d+)\./', $element, $matches);
            } else {
                $element = preg_replace('/(.*?\_\d+\..*?)\.(\d+)\./', "\$1_\$2.", $element);
                preg_match('/.*?\_\d+\.(.*?)\_(\d+)\./', $element, $matches);
            }

            // check the sub element type
            if (count($matches) > 0 && $current_subelement != $matches[1]) {
                if ($count_sub > 0) {
                    echo '    '.$element_name.$scormseperator.$current.'.'.$current_subelement.'._count = '.$count_sub.";\n";
                }
                $current_subelement = $matches[1];
                $current_sub = '';
                $count_sub = 0;
                $end = strpos($element, $matches[1])+strlen($matches[1]);
                $subelement = substr($element, 0, $end);
                echo '    '.$subelement." = new Object();\n";
            }

            // now check the subelement subscript
            if (count($matches) > 0 && $current_sub != $matches[2]) {
                $current_sub = $matches[2];
                $count_sub++;
                $end = strrpos($element, $matches[2])+strlen($matches[2]);
                $subelement = substr($element, 0, $end);
                echo '    '.$subelement." = new Object();\n";
            }

            echo '    '.$element.' = \''.$value."';\n";
        }
        if ($count_sub > 0) {
            echo '    '.$element_name.$scormseperator.$current.'.'.$current_subelement.'._count = '.$count_sub.";\n";
        }
        if ($count > 0) {
            echo '    '.$element_name.'._count = '.$count.";\n";
        }
    }

    public static function scormVersion($manifest) {
        global $filesystem;

        if ($filesystem->has($manifest)) {
            try {
                $xml_content = $filesystem->read($manifest);
                $xml = new SimpleXMLElement($xml_content);

                if ($xml->metadata->schemaversion == "1.2") {
                    return SCORM_12;
                } else if ($xml->metadata->schemaversion == "CAM 1.3" || substr($xml->metadata->schemaversion, 0, 4) == "2004") {
                    return SCORM_2004;
                }
            } catch (Exception $e) {
                application_log("error", "Error loading SCORM manifest file: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * This method translate SCORM 1.2 and 2004 events into xAPI verbs.
     *
     * @param $element
     * @param $value
     * @return string
     */
    public static function getScorm2xAPIverbs($element, $value) {
        if ($element == "cmi_comments_from_learner" || $element == "cmi_comments") {
            return "commented";
        }

        if (($element == "cmi_completion_status" || $element == "cmi_core_lesson_status") && ($value == "completed")) {
            return "passsed";
        }

        if (($element == "cmi_completion_status" || $element == "cmi_core_lesson_status") && ($value == "unknown")) {
            return "completed";
        }

        if (($element == "cmi_completion_status" || $element == "cmi_core_lesson_status") && ($value == "failed")) {
            return "failed";
        }

        if (($element == "cmi_entry" || $element == "cmi_core_entry") && $value == "ab-initio") {
            return "initialized";
        }

        if (($element == "cmi_entry" || $element == "cmi_core_entry") && $value == "resume") {
            return "resumed";
        }

        if (($element == "cmi_exit" && $value == "normal") || ($element == "cmi_core_exit" && $value == "")) {
            return "exited";
        }

        if (($element == "cmi_exit" && $value == "suspend") || ($element == "cmi_core_exit" && $value == "suspend")) {
            return "exited";
        }

        if (($element == "cmi_objectives_n_success_status" || $element == "cmi_objectives_n_status") && $value == "passed") {
            return "passed";
        }

        if ($element == "cmi_progress_measure") {
            return "progressed";
        }

        if ($element == "cmi_score_scaled" || $element == "cmi_core_score_raw") {
            return "scored";
        }

        if ($element == "cmi_session_time" || $element == "cmi_core_session_time") {
            return "interacted";
        }

        if (($element == "cmi_success_status" || $element == "cmi_core_lesson_status") && $value == "passed") {
            return "passed";
        }

        if (($element == "cmi_success_status" || $element == "cmi_core_lesson_status") && $value == "failed") {
            return "failed";
        }

        return "";
    }

    /**
     * This function send xAPI statements to the configured LRS if a verb mapping
     * is returned by the getScorm2xAPIverbs() function.
     *
     * @param $module
     * @param $scorm
     * @param $element
     * @param $value
     * @return string
     */
    public static function sendTracking($module, $scorm, $element, $value) {
        global $ENTRADA_USER;

        if ($ENTRADA_USER) {
            $date = new DateTime();

            $verb = self::getScorm2xAPIverbs($element, $value);

            if ($verb != "") {
                $statement = array(
                    "actor" => array(
                        "name" => $ENTRADA_USER->getFullname(false),
                        "mbox" => $ENTRADA_USER->getEmail(),
                    ),
                    "timestamp" => $date->format("c"),
                    "verb" => array(
                        "id" => "http://adlnet.gov/expapi/verbs/" . $verb,
                        "display" => array(
                            "en-GB" => $verb,
                            "en-US" => $verb
                        )
                    ),
                    "object" => array(
                        "id" => ENTRADA_URL . "/" . $scorm->getTitle() . "/" . $element
                    )
                );

                if ($verb == "terminated" && !empty($value)) {
                    $statement["result"] = array(
                        "duration" => $value
                    );
                }

                $response = $scorm->lrs->saveStatement($statement);

                return $response;
            } else {
                trigger_error("No mapping found for " . $element . ":" . $value);
            }
        }

        return true;
    }
}