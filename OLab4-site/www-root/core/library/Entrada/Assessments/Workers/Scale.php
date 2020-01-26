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
 * This class acts as the primary point of interaction with assessment
 * form related functionality with respect to rubrics. All input/output of
 * for rubric data manipulation should utilize this class.
 *
 * There are two ways to fetch the data of a rubric, either by specifying
 * the ID of the rubric, or fetching the form that contains the rubric.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Assessments_Workers_Scale extends Entrada_Assessments_Workers_Base {
    protected $rating_scale_id = null;
    protected $global_storage = "Entrada_Assessments_Workers_GlobalStorage";

    public function getID() {
        return $this->rating_scale_id;
    }

    public function setID($id) {
        $this->rating_scale_id = $id;
    }

    public function getScaleID() {
        return $this->rating_scale_id;
    }

    public function setScaleID($id) {
        $this->rating_scale_id = $id;
    }

    public function saveData() {
        if (is_array($this->dataset) && !empty($this->dataset)) {
            if ($this->dataset["meta"]["rating_scale_id"]) {
                // Item already exists, so let's make it consistent with what we've validated.
                // That means we have to soft-delete anything that isn't in our new dataset.
                return $this->saveDatasetAsExistingScale();
            } else {
                // Item does not already exist, so let's create it.
                return $this->saveDatasetAsNewScale();
            }
        }
        return false;
    }

    public function loadData($data, $validate = true) {
        // Check for an ID, and check if the item is in the DB already (and fetch it)
        $existing_data = array();
        if (isset($data["rating_scale"]["rating_scale_id"])) {
            $existing_data = $this->buildDataset();
        }
        // Load the supplied data into a new dataset structure.
        if (empty($existing_data)) {
            $new_dataset = $this->buildDatasetAsNewScale($data);
        } else {
            $new_dataset = $this->buildDatasetAsExistingScale($data, $existing_data);
        }

        // Update the metadata after the load
        $new_dataset["meta"]["responses_count"] = count($new_dataset["responses"]);
        $new_dataset["meta"]["descriptor_count"] = count($new_dataset["descriptors"]);

        // Validate if specified
        if ($validate) {
            if ($this->validate($new_dataset)) {
                $this->dataset = $new_dataset;
            } else {
                return false; // Failed validation
            }
        } else {
            $this->dataset = $new_dataset;
        }
        return true;
    }

    /**
     * Mark the current dataset as stale and remove it from global cache.
     */
    public function invalidateDataset() {
        $this->setStale();
        if ($this->rating_scale_id) {
            $this->removeFromStorage("scale_dataset", $this->rating_scale_id);
        }
    }

    public function delete() {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        $scale = Models_Assessments_RatingScale::fetchRowByID($this->rating_scale_id);
        if ($scale) {
            $scale->fromArray(array("deleted_date" => time(),
                "updated_date" => time(),
                "updated_by" => $this->actor_proxy_id));

            if (!$scale->update()) {
                $this->addErrorMessage($translate->_("Unable to delete a Rating Scale."));
                return false;
            } else {
                $responses = Models_Assessments_RatingScale_Response::fetchRowsByRatingScaleID($this->rating_scale_id);
                foreach($responses as $response) {
                    $response->fromArray(array(
                        "deleted_date" => time(),
                        "updated_date" => time(),
                        "updated_by" => $this->actor_proxy_id)
                    );
                    if (!$response->update()) {
                        $this->addErrorMessage($translate->_("Unable to delete Rating Scale Responses"));
                        return false;
                    }
                }
                $authors = Models_Assessments_RatingScale_Author::fetchRowsByRatingScaleID($this->rating_scale_id);
                foreach($authors as $author) {
                    $author->fromArray(array(
                        "deleted_date" => time(),
                        "updated_date" => time(),
                        "updated_by" => $this->actor_proxy_id)
                    );
                    if (!$author->update()) {
                        $this->addErrorMessage($translate->_("Unable to delete Rating Scale Authors"));
                        return false;
                    }
                }
            }
        } else {
            $this->addErrorMessage(sprintf($translate->_("Unable to load scale object with id %s"), $this->rating_scale_id));
            return false;
        }

        return true;
    }

    /**
     * Fetch all related data points, return in a data structure.
     *
     * @param bool $cached
     * @return false|array
     */
    public function fetchData($cached = true) {
        if ($cached) {
            // Attempt to find a cached version of the dataset
            if ($this->isInStorage("rating_scale_dataset", $this->rating_scale_id)) {
                $this->dataset = $this->fetchFromStorage("rating_scale_dataset", $this->rating_scale_id);
            }
        }
        if (empty($this->dataset)) {
            $this->dataset = $this->buildDataset();
        } else {
            if (!array_key_exists("is_stale", $this->dataset) || $this->dataset["is_stale"]) {
                $this->dataset = $this->buildDataset();
            }
        }
        if ($cached) {
            $this->addToStorage("rating_scale_dataset", $this->dataset, $this->rating_scale_id);
        }
        return $this->dataset;
    }

    /**
     * Simple validation for input data.
     *
     * @param $data
     * @return bool
     */
    private function validate($data) {
        if (!is_array($data) || empty($data)) {
            return false;
        }
        return true;
    }

    /**
     * Return whether this form object is in use.
     *
     * @return bool
     */
    public function isInUse() {
        if ($this->rating_scale_id) {
            $this->fetchData();
            if (empty($this->dataset)) {
                return false;
            }
            $count_in_use = is_array($this->dataset["meta"]["in_use_by"]["rubrics"]) ? count($this->dataset["meta"]["in_use_by"]["rubrics"]) : 0;
            $count_in_use += is_array($this->dataset["meta"]["in_use_by"]["items"]) ? count($this->dataset["meta"]["in_use_by"]["items"]) : 0;
            if ($count_in_use == 0) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Save the current dataset as a new rating scale in the database.
     * Optionally save a cloned relationship.
     *
     * @return bool
     */
    private function saveDatasetAsNewScale() {
        global $translate;

        if (!$this->validateActor()) {
            return false;
        }

        if (empty($this->dataset)) {
            // Can't save empty dataset
            $this->addErrorMessage($translate->_("Unable to save new rating scale with empty dataset."));
            return false;
        }

        // Save the rating scale
        $scale = new Models_Assessments_RatingScale($this->dataset["rating_scale"]);
        if (!$scale->insert()) {
            application_log("error", "Forms_Rating_Scale:: Failed to insert new rating scale.");
            $this->addErrorMessage($translate->_("Unable to save new rating scale to the database."));
            return false;
        }

        $this->rating_scale_id = $scale->getID();

        // Save responses
        if (is_array($this->dataset["responses"])) {
            foreach ($this->dataset["responses"] as $response_data) {
                $new_response = new Models_Assessments_RatingScale_Response(
                    array(
                        "order" => $response_data["order"],
                        "flag_response" => $response_data["flag_response"],
                        "rating_scale_id" => $this->rating_scale_id,
                        "ardescriptor_id" => $response_data["ardescriptor_id"],
                        "text" => $response_data["text"]
                    )
                );
                if (!$new_response->insert()) {
                    application_log("error", "Forms_Scale:: Failed to insert new response. Data may be inconsistent.");
                    $this->addErrorMessage($translate->_("Unable to save new rating scale response to the database."));
                    return false;
                }
            }
        }

        // Save current author
        $scale_author = new Models_Assessments_RatingScale_Author(
            array(
                "rating_scale_id" => $this->rating_scale_id,
                "author_type" => "proxy_id",
                "author_id" => $this->actor_proxy_id,
                "created_date" => time(),
                "created_by" => $this->actor_proxy_id,
                "updated_date" => time(),
                "updated_by" => $this->actor_proxy_id,
                "deleted_date" => null
            )
        );

        if (!$scale_author->insert()) {
            application_log("error", "Forms_Scale:: Unable to add rating scale author");
        }

        // Save any other authors given
        if (is_array($this->dataset["authors"])) {
            foreach ($this->dataset["authors"] as $author) {
                if ($author["author_id"] != $this->actor_proxy_id) {
                    $author["rating_scale_id"] = $this->rating_scale_id;
                    $author["created_date"] = time();
                    $author["created_by"] = $this->actor_proxy_id;
                    $author["updated_date"] = time();
                    $author["updated_by"] = $this->actor_proxy_id;
                    $new_author = new Models_Assessments_RatingScale_Author($author);
                    if (!$new_author->insert()) {
                        application_log("error", "Forms_Scale:: Unable to add additional scale rating author");
                    }
                }
            }
        }

        return true;
    }

    /**
     * Save the current dataset to the database, updating the existing rating scale.
     *
     * @return bool
     */
    private function saveDatasetAsExistingScale() {
        global $translate;

        if (empty($this->dataset)) {
            // Can't save empty dataset
            $this->addErrorMessage($translate->_("Unable to update rating scale with empty dataset."));
            return false;
        }

        $scale_id = $this->dataset["meta"]["rating_scale_id"];

        // Update the rating scale record
        $scale = new Models_Assessments_RatingScale($this->dataset["rating_scale"]);
        if (!$scale->update()) {
            application_log("error", "Forms_Scale:: Failed to update existing rating scale (rating scale id = '{$this->dataset["meta"]["rating_scale_id"]}'.");
            $this->addErrorMessage($translate->_("Failed to update rating scale."));
            return false;
        }

        // Save any new or updated responses
        Models_Assessments_RatingScale_Response::deleteRowsByRatingScaleID($scale->getID());
        if (is_array($this->dataset["responses"])) {
            foreach ($this->dataset["responses"] as $response_data) {
                // New response
                $new_response = new Models_Assessments_RatingScale_Response(
                    array(
                        "order" => $response_data["order"],
                        "flag_response" => $response_data["flag_response"],
                        "rating_scale_id" => $scale->getID(),
                        "ardescriptor_id" => $response_data["ardescriptor_id"],
                        "text" => $response_data["text"],
                        "delete_date" => null
                    )
                );
                if (!$new_response->insert()) {
                    application_log("error", "Forms_Scale::saveData: Failed to insert new or update response. Data may be inconsistent.");
                    $this->addErrorMessage($translate->_("Unable to save rating scale response to the database."));
                }

            }
        }

        // Save any new authors
        if (is_array($this->dataset["authors"])) {
            foreach ($this->dataset["authors"] as $author) {
                if (!isset($author["rating_scale_author_id"])) {
                    // This is a new one, so let's create it
                    $new_author = new Models_Assessments_RatingScale_Author($author);
                    if (!$new_author->insert()) {
                        application_log("error", "Unable to save new rating scale author record for scale id = '$scale_id'");
                    }
                }
            }
        }

        // Saved.
        $this->removeFromStorage("rating_scale_dataset", $this->rating_scale_id);
        return true;
    }

    /**
     * Fetch and cache the rating scale responses.
     *
     * @return array|bool|mixed
     */
    private function fetchCachedRatingScaleResponses() {
        if ($this->isInStorage("rating-scale-responses", $this->rating_scale_id)) {
            return $this->fetchFromStorage("rating-scale-responses", $this->rating_scale_id);
        } else {
            $scale_responses = Models_Assessments_RatingScale_Response::fetchRowsByRatingScaleID($this->rating_scale_id);
            $this->addToStorage("rating-scale-responses", $scale_responses, $this->rating_scale_id);
            return $scale_responses;
        }
    }

    /**
     * Determine what rubrics and items use this scale.
     *
     * @return array
     */
    protected function determineUsage() {
        $in_use_by = array(
            "rubrics" => array(),
            "items" => array()
        );
        if (!$this->determine_meta) {
            return $in_use_by;
        }
        if ($this->rating_scale_id) {
            $in_use_by["rubrics"] = Models_Assessments_Rubric_Item::fetchRubricIDsByScaleID($this->rating_scale_id);
            $in_use_by["items"] = Models_Assessments_Item::fetchItemIDsByScaleID($this->rating_scale_id);
        }
        return $in_use_by;
    }

    /**
     * Determine if this object can be edited.
     * If it is part of a form that has been delivered, the it is NOT editable.
     *
     * @return bool
     */
    protected function determineEditable() {
        if (!$this->determine_meta) {
            return true;
        }

        if (!$this->rating_scale_id) {
            return true; // No scale ID, it's a new rating scale, so it's editable.
        }

        // Check which forms use this rating scale
        $form_ids = Models_Assessments_RatingScale::fetchFormIDsByRatingScaleID($this->rating_scale_id);
        if (empty($form_ids)) {
            return true; // None found, so it's editable
        }

        // This rating scale is a part of some forms, so let's find out if there are progress records for them
        $progress_ids = Models_Assessments_Progress_Response::fetchInProgressAndCompleteProgressResponseIDsByFormIDs($form_ids);
        if (!empty($progress_ids)) {
            return false; // There's some in-progress or complete data using a form that uses this rating scale. That means it's not editable.
        }

        // There's no progress records, so let's check if there are any assessment tasks delivered, but not started.
        $dassessment_ids = Models_Assessments_Assessor::fetchDassessmentIDsByFormIDs($form_ids);
        if (!empty($dassessment_ids)) {
            // Assessment IDs were found, meaning assessment tasks were delivered that use this rating scale. It is not editable.
            return false;
        }

        return true; //passed checks, it's editable
    }

    /**
     * Load the given data into a dataset structure, treating it as a new rating scale (i.e., one that does not already exist in the database).
     * This method returns the new dataset structure, and does not replace the existing one, if there is one.
     *
     * @param array $data_to_load
     * @return bool|array (the new dataset)
     */
    private function buildDatasetAsNewScale($data_to_load) {

        if (!$this->validateActor()) {
            return false;
        }

        /* This is a brand new rating scale. */

        $new_dataset = $this->buildDefaultScaleStructure();

        $new_dataset["meta"]["rating_scale_id"] = null;

        $new_dataset["rating_scale"]["organisation_id"] = $this->actor_organisation_id;
        $new_dataset["rating_scale"]["created_date"] = time();
        $new_dataset["rating_scale"]["created_by"] = $this->actor_proxy_id;
        $new_dataset["rating_scale"]["updated_date"] = time();
        $new_dataset["rating_scale"]["updated_by"] = $this->actor_proxy_id;
        $new_dataset["rating_scale"]["deleted_date"] = null;

        $new_dataset["rating_scale"]["rating_scale_id"] = null;
        $new_dataset["rating_scale"]["rating_scale_type"] = $data_to_load["rating_scale"]["rating_scale_type"];
        $new_dataset["rating_scale"]["rating_scale_title"] = $data_to_load["rating_scale"]["rating_scale_title"];
        $new_dataset["rating_scale"]["rating_scale_description"] = $data_to_load["rating_scale"]["rating_scale_description"];

        // Responses = the descriptor ids
        $order = 1;
        if (isset($data_to_load["responses"]) && is_array($data_to_load["responses"])) {
            foreach ($data_to_load["responses"] as $i => $load_response) {
                $new_dataset["responses"][$i]["order"] = $order;
                $new_dataset["responses"][$i]["text"] = $this->fetchResponseDescriptorText($load_response);
                $new_dataset["responses"][$i]["flag_response"] = @$data_to_load["flag_response"][$i] ? $data_to_load["flag_response"][$i] : 0;
                $new_dataset["responses"][$i]["ardescriptor_id"] = $load_response;
                $order++;
            }
        }
        if (isset($data_to_load["descriptors"]) && is_array($data_to_load["descriptors"])) {
            foreach ($data_to_load["descriptors"] as $descriptor_id) {
                $descriptor = $this->fetchResponseDescriptor($descriptor_id);
                if ($descriptor) {
                    $new_dataset["descriptors"][$descriptor_id] = $descriptor->toArray();
                } else {
                    // add error, descriptor not found
                    application_log("error", "Forms_Scale::loadData: Response descriptor not found");
                }
            }
        }
        if ($data_to_load["rating_scale"]["rating_scale_type"]) {
            if ($type_record = Models_Assessments_RatingScale_Type::fetchRowByID($data_to_load["rating_scale"]["rating_scale_type"])) {
                $new_dataset["rating_scale_type"] = $type_record->toArray();
            }
        }

        return $new_dataset;
    }

    /**
     * Load the given data, comparing it against the existing data specified, and return an updated dataset structure.
     * This does not affect the internal dataset property, it simply creates a new dataset that can be used to replace it.
     *
     * Contained in the dataset is all of the rating scale related responses and metadata, as well as what items to prune (applied when saveData is called).
     *
     * @param array $data_to_load
     * @param array $existing_data
     * @return bool|array (The new dataset)
     */
    private function buildDatasetAsExistingScale($data_to_load, $existing_data) {

        if (!$this->validateActor()) {
            return false;
        }

        /* Data exists already, so match the required parts, mark extraneous to be deleted. */
        $new_dataset = $this->buildDefaultScaleStructure();

        $new_dataset["meta"]["rating_scale_id"] = $existing_data["rating_scale"]["rating_scale_id"];

        $new_dataset["rating_scale"] = $existing_data["rating_scale"];
        $new_dataset["rating_scale"]["updated_date"] = time();
        $new_dataset["rating_scale"]["updated_by"] = $this->actor_proxy_id;

        $new_dataset["rating_scale"]["rating_scale_id"] = $data_to_load["rating_scale"]["rating_scale_id"];
        $new_dataset["rating_scale"]["rating_scale_type"] = $data_to_load["rating_scale"]["rating_scale_type"];
        $new_dataset["rating_scale"]["rating_scale_title"] = $data_to_load["rating_scale"]["rating_scale_title"];
        $new_dataset["rating_scale"]["rating_scale_description"] = $data_to_load["rating_scale"]["rating_scale_description"];


        // responses is a flat array of ardescriptor_ids
        if (!empty($data_to_load["responses"])) {
            $order = 1;

            // Store all the given responses
            foreach ($data_to_load["responses"] as $i => $response_id) {
                $new_dataset["responses"][$i]["order"] = $order;
                $new_dataset["responses"][$i]["text"] = $this->fetchResponseDescriptorText($response_id);
                $new_dataset["responses"][$i]["flag_response"] = @$data_to_load["flag_response"][$i] ? $data_to_load["flag_response"][$i] : 0;
                $new_dataset["responses"][$i]["ardescriptor_id"] = $response_id;
                $order++;
            }
            // Has duplicate descriptors?
            $has_duplicates = false;
            foreach ($new_dataset["responses"] as $i => $response_data_haystack) {
                foreach ($new_dataset["responses"] as $j => $response_data_needles) {
                    if ($i != $j) {
                        if ($response_data_needles["ardescriptor_id"] == $response_data_haystack["ardescriptor_id"]) {
                            $has_duplicates = true;
                        }
                    }
                }
            }

            if ($has_duplicates) {
                // There are duplicate descriptors, so we can't reliably update the ordering of existing items.
                // We must clear all responses and make new ones.
                foreach ($existing_data["responses"] as $existing_response) {
                    // Mark all for deletion
                    $new_dataset["meta"]["property_cleanup"]["responses"][$existing_response["rating_scale_response_id"]] = $existing_response["rating_scale_response_id"];
                }

            } else {
                // There are no duplicate descriptors, so we can examine the existing data set and reorder as necessary.
                foreach ($new_dataset["responses"] as $i => $response_data) {
                    // Search the existing data for these responses.
                    foreach ($existing_data["responses"] as $iresponse_id => $load_response) {
                        if ($response_data["text"] == $load_response["text"] &&
                            $response_data["ardescriptor_id"] == $load_response["ardescriptor_id"]) {
                            // Found the exact item.

                            // Preserve new ordering
                            $new_order = $response_data["order"];
                            $new_flagging = $response_data["flag_response"];

                            // Keep this
                            $new_dataset["responses"][$i] = $load_response;

                            // Update the response order
                            $new_dataset["responses"][$i]["order"] = $new_order;
                            $new_dataset["responses"][$i]["flag_response"] = $new_flagging;
                        }
                    }
                }

                // If there are responses in the database that do not match our current dataset, then mark them for deletion
                foreach ($existing_data["responses"] as $existing_response) {
                    $found = false;
                    foreach ($new_dataset["responses"] as $new_response) {
                        if (isset($new_response["iresponse_id"])) { // Check for an existing response (item_struct can contain new items as well as old ones)
                            if ($existing_response["rating_scale_response_id"] == $new_response["rating_scale_response_id"]) {
                                $found = true;
                            }
                        }
                    }
                    if (!$found) {
                        // The existing response is not in the new set, so mark it for deletion
                        $new_dataset["meta"]["property_cleanup"]["responses"][$existing_response["rating_scale_response_id"]] = $existing_response["rating_scale_response_id"];
                    }
                }
            }
        }

        // Keep new descriptors. In this case, we don't really care about the previous descriptors, we just save the new ones. There's no related table
        // that contains this information, as it is included on the rating scale response record.
        if (!empty($data_to_load["descriptors"])) {
            foreach ($data_to_load["descriptors"] as $i => $new_descriptor) {
                if (isset($existing_data["descriptors"][$new_descriptor])) {
                    $new_dataset["descriptors"][$new_descriptor] = $existing_data["descriptors"][$new_descriptor];
                } else {
                    if ($descriptor = $this->fetchResponseDescriptor($new_descriptor)) {
                        $new_dataset["descriptors"][$descriptor->getID()] = $descriptor->toArray();
                    }
                }
            }
        }

        // Keep existing authors. (We let an external API call modify authorship).
        foreach ($existing_data["authors"] as $i => $load_authors) {
            $new_dataset["authors"][$i] = $load_authors;
        }

        // Save the scale type
        if ($data_to_load["rating_scale"]["rating_scale_type"]) {
            if ($type_record = Models_Assessments_RatingScale_Type::fetchRowByID($data_to_load["rating_scale"]["rating_scale_type"])) {
                $new_dataset["rating_scale_type"] = $type_record->toArray();
            }
        }

        return $new_dataset;
    }

    /**
     * Assemble the related data for this rating scale. Fetches data based on the internal property rating_scale_id.
     *
     * @return array|bool
     */
    private function buildDataset() {
        global $translate;

        if (!$this->rating_scale_id) {
            application_log("error", "fetchScaleData: Unable to fetch rating scale without ID");
            $this->addErrorMessage($translate->_("Please specify a rating scale identifier."));
            return false;
        }

        $scale = Models_Assessments_RatingScale::fetchRowByID($this->rating_scale_id);
        if (!$scale) {
            // Invalid Scale ID
            application_log("error", "fetchScaleData: Invalid rating scale ID (form record doesn't exist)");
            $this->addErrorMessage($translate->_("Rating scale not found."));
            return false;
        }

        $scale_data = $this->buildDefaultScaleStructure();
        $scale_data["meta"]["rating_scale_id"] = $this->rating_scale_id;

        // Store base rating scale record
        $scale_data["rating_scale"] = $scale->toArray();


        if (empty($this->limit_dataset) || in_array("rating_scale_type", $this->limit_dataset)) {
            if ($scale->getRatingScaleType()) {
                // Store scale type
                if ($scale_type = Models_Assessments_RatingScale_Type::fetchRowByID($scale->getRatingScaleType())) {
                    $scale_data["rating_scale_type"] = $scale_type->toArray();
                }
            }
        }

        if (empty($this->limit_dataset) || in_array("authors", $this->limit_dataset)) {
            // Store authors
            $scale_authors = Models_Assessments_RatingScale_Author::getAllAuthors($this->rating_scale_id);
            $authors = array();
            if (is_array($scale_authors)) {
                foreach ($scale_authors as $author) {
                    $authors[] = $author->toArray();
                }
            }
            $scale_data["authors"] = $authors;
        }

        if (empty($this->limit_dataset) || in_array("responses", $this->limit_dataset)) {
            // Store possible responses
            $responses = $this->fetchCachedRatingScaleResponses();
            if ($responses && is_array($responses)) {
                foreach ($responses as $response) {
                    $scale_data["responses"][$response->getID()] = $response->toArray();

                    // Check for descriptors
                    if ($this->isInStorage("response-descriptors-object", $response->getArDescriptorID())) {
                        $descriptor = $this->fetchFromStorage("response-descriptors-object", $response->getArDescriptorID());
                    } else {
                        $descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($response->getArDescriptorID());
                        $this->addToStorage("response-descriptors-object", $descriptor, $response->getArDescriptorID());
                    }
                    if ($descriptor) {
                        if (!isset($scale_data["descriptors"][$descriptor->getID()])) {
                            $scale_data["descriptors"][$descriptor->getID()] = $descriptor->toArray();
                        }
                    }
                }
            }
        }

        $scale_data["meta"]["responses_count"] = count($scale_data["responses"]);
        $scale_data["meta"]["descriptor_count"] = count($scale_data["descriptors"]);
        return $scale_data;
    }

    /**
     * Create the default dataset structure.
     *
     * @return array
     */
    private function buildDefaultScaleStructure() {
        $scale_data = array();
        $scale_data["is_stale"] = false;
        $scale_data["meta"] = array();
        $scale_data["meta"]["rating_scale_id"] = $this->rating_scale_id;
        $scale_data["meta"]["responses_count"] = 0;
        $scale_data["meta"]["objectives_count"] = 0;
        $scale_data["meta"]["is_editable"] = $this->determineEditable();
        $scale_data["meta"]["in_use_by"] = $this->determineUsage();
        $scale_data["meta"]["property_cleanup"] = array("responses" => array(), "authors" => array());
        $scale_data["rating_scale"] = array();
        $scale_data["rating_scale_type"] = array();
        $scale_data["authors"] = array();
        $scale_data["responses"] = array();
        $scale_data["descriptors"] = array();
        return $scale_data;
    }
}
