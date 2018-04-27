<?php

class Entrada_LearningObject_TinCan extends Entrada_LearningObject
{
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

            if (isset($xml->activities->activity[0]->name)) {
                $this->activity_id = (string)$xml->activities->activity[0]->attributes()->id;
                $this->description = (!is_object($xml->activities->activity[0]->description)) ? $xml->activities->activity[0]->description : "";
                $this->title = $xml->activities->activity[0]->name;
                $this->launch_file = $xml->activities->activity[0]->launch;

                return true;
            }
        } catch (Exception $e) {
            application_log("error", "Failed to load TinCan manifest XML file: " . $e->getMessage());
        }

        return false;
    }
}