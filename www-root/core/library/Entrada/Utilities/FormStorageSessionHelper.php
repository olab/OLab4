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
 * A class to manage the assessment form referrer session storage functionality.
 * This class' methods are all static. They all reference either themselves
 * or the session.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_FormStorageSessionHelper extends Entrada_Base {

    /**
     * Initialize the session's forms_storage array if it isn't already set.
     */
    static public function configure() {
        if (!isset($_SESSION[APPLICATION_IDENTIFIER]["forms_storage"])) {
            $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"] = array();
        }
    }

    /**
     * Create a referral hash for storage indexing.
     *
     * @param int $related_id
     * @param string $type
     * @return string md5
     */
    static public function buildStorageHash($related_id, $type) {
        $hash_array = array($related_id, $type);
        return md5(serialize($hash_array)); // TODO: Maybe, in the future, we could use "hashids" library to shorten the url
    }

    /**
     * Fetch the stored session data via the hash key.
     *
     * @param string $hash
     * @return array
     */
    static public function fetch($hash) {
        if ($hash) {
            if (isset($_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash])) {
                return $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash];
            }
        }
        return array();
    }

    /**
     * Combine all the ref appending functionality into one.
     * Prioritize form and rubric ref variables over the fallback ID method of appending refs.
     *
     * @param $url
     * @param null $form_ref
     * @param null $rubric_ref
     * @param null $form_id
     * @param null $rubric_id
     * @return string
     */
    static public function buildRefURL($url, $form_ref = null, $rubric_ref = null, $form_id = null, $rubric_id = null) {
        if (!$form_ref && !$rubric_ref && !$form_id && !$rubric_id) {
            return $url;
        }
        if ($form_ref) {
            $url = self::appendFormRefToURI($form_ref, $url);
        } else if ($form_id) {
            $url = self::appendFormRefToURIByFormID($form_id, $url);
        }
        if ($rubric_ref) {
            $url = self::appendRubricRefToURI($rubric_ref, $url);
        } else if ($rubric_id) {
            $url = self::appendRubricRefToURIByRubricID($rubric_id, $url);
        }
        return $url;
    }

    /**
     * Append the form ref token to the given URI.
     *
     * @param string $hash
     * @param string $url
     * @return string
     */
    static public function appendFormRefToURI($hash, $url) {
        if ($hash) {
            if (strpos($url, "fref=$hash") === false) {
                $url .= "&fref=$hash";
            }
        }
        return $url;
    }

    /**
     * Append the rubric ref token to the given URI.
     *
     * @param string $hash
     * @param string $url
     * @return string
     */
    static public function appendRubricRefToURI($hash, $url) {
        if ($hash) {
            if (strpos($url, "rref=$hash") === false) {
                $url .= "&rref=$hash";
            }
        }
        return $url;
    }

    /**
     * Append the form ref token to the URI, first finding the token by form ID.
     *
     * @param int $form_id
     * @param string $url
     * @return string
     */
    static public function appendFormRefToURIByFormID($form_id, $url) {
        if ($form_id && $url) {
            $hash = self::buildStorageHash($form_id, "form");
            if ($hash) {
                if (strpos($url, "fref=$hash") === false) {
                    $url .= "&fref=$hash";
                }
            }        }
        return $url;
    }

    /**
     * Append the rubric ref token to the URL, first finding the token by rubric ID.
     *
     * @param int $rubric_id
     * @param string $url
     * @return string
     */
    static public function appendRubricRefToURIByRubricID($rubric_id, $url) {
        if ($rubric_id && $url) {
            $hash = self::buildStorageHash($rubric_id, "rubric");
            if ($hash) {
                if (strpos($url, "rref=$hash") === false) {
                    $url .= "&rref=$hash";
                }
            }
        }
        return $url;
    }

    /**
     * Get the storage reference variable for a form from POST/GET.
     * Prioritize a POST'd ref vs a GET'd ref.
     *
     * @return null|string
     */
    static public function getFormRef() {
        $ref = null;
        if (isset($_POST["fref"]) && $tmp_input = clean_input($_POST["fref"], "alphanumeric")) {
            $ref = $tmp_input;
        } elseif (isset($_GET["fref"]) && $tmp_input = clean_input($_GET["fref"], "alphanumeric")) {
            $ref = $tmp_input;
        }
        return $ref;
    }

    /**
     * Get the storage reference variable for a rubric from POST/GET.
     * Prioritize a POST'd ref vs a GET'd ref.
     *
     * @return null|string
     */
    static public function getRubricRef() {
        $ref = null;
        if (isset($_POST["rref"]) && $tmp_input = clean_input($_POST["rref"], "alphanumeric")) {
            $ref = $tmp_input;
        } elseif (isset($_GET["rref"]) && $tmp_input = clean_input($_GET["rref"], "alphanumeric")) {
            $ref = $tmp_input;
        }
        return $ref;
    }

    /**
     * Build a storage reference hash for a form.
     *
     * @param int $id
     * @return string
     */
    static public function buildFormRef($id) {
        return self::buildStorageHash($id, "form");
    }

    /**
     * Build a storage reference hash for a rubric.
     *
     * @param int $id
     * @return string
     */
    static public function buildRubricRef($id) {
        return self::buildStorageHash($id, "rubric");
    }

    /**
     * Remove session storage data related to specific IDs.
     *
     * @param int $form_id
     * @param int $item_id
     * @param int $rubric_id
     */
    static public function cleanup($form_id = null, $item_id = null, $rubric_id = null) {
        // NOT IMPLEMENTED
        if ($form_id) {
        }
        if ($item_id) {
        }
        if ($rubric_id) {
        }
    }

    // Referrer is a form
    static public function addFormReferrerURL($form_id, $url) {
        $hash = self::buildStorageHash($form_id, "rubric");
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["type"] = "form";
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["form_id"] = $form_id;
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["referrer_url"] = $url;
        return $hash;
    }

    static public function addRubricReferrerData($rubric_id, &$rubric_data, $url) {
        $hash = self::buildStorageHash($rubric_id, "form");

        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["type"] = "form";
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["form_id"] = $rubric_id;

        // Save the new rubric referrer data to the session (width, descriptors, types)
        self::addRubricEditURL($rubric_id, $url);

        if (!empty($rubric_data)) {
            self::addRubricWidth($rubric_id, $rubric_data["meta"]["width"]); // Only display rubrics of this width. Ignored when 0.
            self::addRubricTypes($rubric_id, array("scale", "rubric_line")); // Only show scale and rubric_lines.

            $descriptors = null;
            if (!empty($rubric_data["descriptors"])) {
                $descriptors = array();
                foreach ($rubric_data["descriptors"] as $descriptor) {
                    $descriptors[] = $descriptor["ardescriptor_id"];
                }
            }
            self::addRubricDescriptors($rubric_id, $descriptors);

            $line_ids = null;
            if (!empty($rubric_data["lines"])) {
                $line_ids = array();
                foreach ($rubric_data["lines"] as $line) {
                    if (!in_array($line["item"]["item_id"], $line_ids)) {
                        $line_ids[] = $line["item"]["item_id"];
                    }
                }
            }
            self::addRubricItemIDs($rubric_id, $line_ids);
        }
        return $hash;
    }

    static public function addRubricEditURL($rubric_id, $url) {
        $hash = self::buildStorageHash($rubric_id, "rubric");
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["type"] = "rubric";
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["rubric_id"] = $rubric_id;
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["referrer_url"] = $url;
        return $hash;
    }

    static public function addRubricWidth($rubric_id, $width) {
        $hash = self::buildStorageHash($rubric_id, "rubric");
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["type"] = "rubric";
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["rubric_id"] = $rubric_id;
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["width"] = $width;
        return $hash;
    }

    static public function addRubricTypes($rubric_id, $types = array()) {
        $hash = self::buildStorageHash($rubric_id, "rubric");
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["type"] = "rubric";
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["rubric_id"] = $rubric_id;
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["types"] = $types;
        return $hash;
    }

    static public function addRubricDescriptors($rubric_id, $descriptors) {
        $hash = self::buildStorageHash($rubric_id, "rubric");
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["type"] = "rubric";
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["rubric_id"] = $rubric_id;
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["descriptors"] = $descriptors;
        return $hash;
    }

    static public function addRubricItemIDs($rubric_id, $item_ids) {
        $hash = self::buildStorageHash($rubric_id, "rubric");
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["type"] = "rubric";
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["rubric_id"] = $rubric_id;
        $_SESSION[APPLICATION_IDENTIFIER]["forms_storage"][$hash]["items"] = $item_ids;
        return $hash;
    }

    /**
     * Given form and rubric referrer tokens, create one appropriate redirect URI.
     * When both tokens are given, we prioritize rubric referrals over form ones, but
     * we must keep both referral tokens in the URI. Otherwise, we use whichever one is available in order.
     *
     * @param string $fref
     * @param string $rref
     * @return string
     */
    static public function determineReferrerURI($fref, $rref) {
        $rubric_referrer_data = self::fetch($rref);
        $form_referrer_data = self::fetch($fref);
        $url = "";
        if ($rref && $fref && @$rubric_referrer_data["referrer_url"]) {
            $url = $rubric_referrer_data["referrer_url"];
            $url = self::appendFormRefToURI($fref, $url);
        } else if ($rref) {
            $url = @$rubric_referrer_data["referrer_url"];
        } else if ($fref) {
            $url = @$form_referrer_data["referrer_url"];
        }
        return $url;
    }

    /**
     * Given form and rubric referrer tokens, determine which referrer we're supposed to use.
     *
     * @param string $fref
     * @param string $rref
     * @return string
     */
    static public function determineReferrerType($fref, $rref) {
        if ($rref) {
            return "rubric";
        } else if ($fref) {
            return "form";
        }
        return "none";
    }
}