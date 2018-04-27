<?php

class Entrada_LearningObject_Scorm2004 extends Entrada_LearningObject_Scorm {

    public function __construct($xml_file = "") {
        if ($xml_file != "") {
            $this->xml_file = $xml_file;
            if ($this->validate()) {
                $this->load();
            }
        }

        parent::__construct();
    }

    public function load() {
        global $filesystem;

        try {
            $xml_content = $filesystem->read($this->xml_file);
            $xml = new SimpleXMLElement($xml_content);

            if (isset($xml->metadata->schema) && $xml->metadata->schema == "ADL SCORM") {
                $this->activity_id = (string) $xml->attributes()->identifier;
                $this->description = "";
                $this->title = (string) $xml->organizations->organization->title;
                $this->launch_file = (string) $xml->resources->resource->attributes()->href;
                $this->scorm_version = SCORM_2004;

                /**
                 * Below settings (and probably many more) should come from the database
                 */
                $this->id = rand(1, 200);
                $this->attempt = rand(1, 5);

                return true;
            }
        } catch (Exception $e) {
            application_log("error", "Error loading SCORM 2004 file: " . $e->getMessage());
        }

        return false;
    }
}