<?php
class Entrada_LearningObject_Upload
{
    protected $file, $directory;

    public function __construct($options = array()) {
        // if $options property exists among class properties, set it
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function process() {
        $result = $this->validate_upload();
        if ($result !== true) {
            return $result;
        }

        return $this->extract_file();
        if ($result !== true) {
            return $result;
        }
    }

    private function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last)
        {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }

    private function max_file_upload_in_bytes() {
        //select maximum upload size
        $max_upload = self::return_bytes(ini_get('upload_max_filesize'));
        //select post limit
        $max_post = self::return_bytes(ini_get('post_max_size'));
        //select memory limit
        $memory_limit = self::return_bytes(ini_get('memory_limit'));
        // return the smallest of them, this defines the real limit
        return min($max_upload, $max_post, $memory_limit);
    }

    private function validate_upload() {
        // Undefined | Multiple Files | $_FILES Corruption Attack
        // If this request falls under any of them, treat it invalid.
        if (!isset($this->file['error']) || is_array($this->file['error'])) {
            return "Invalid parameters";
        }

        // Check $this->file['error'] value.
        switch ($this->file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return "No file sent.";
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "Exceeded filesize limit";
            default:
                return "Unknown errors";
        }

        // You should also check filesize here.
        if ($this->file['size'] > self::max_file_upload_in_bytes()) {
            return "Exceeded filesize limit.";
        }

        // DO NOT TRUST $this->file['mime'] VALUE !!
        // Check MIME Type by yourself.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if (false === $ext = array_search(
                $finfo->file($this->file['tmp_name']),
                array(
                    'zip' => 'application/zip'
                ),
                true
            )) {
            return "Invalid file format.";
        }

        return true;
    }

    private function extract_file() {
        global $filesystem;

        $zip = new ZipArchive();

        if ($zip->open($this->file["tmp_name"]) !== true ) {
            return "Failed to open archive.";
        }

        $file_hash = Entrada_Utilities_Files::getFileHash($this->file["tmp_name"]);
        $path = Entrada_Utilities_Files::getPathFromFilename($file_hash);

        /**
         * Check fo tincan.xml, if it exists, extract info about the module.
         */
        if ($zip->statName("tincan.xml")) {
            $xml = new SimpleXMLElement($zip->getFromName('tincan.xml'));

            if (!isset($xml->activities->activity[0]->name)) {
                return "Invalid tincan xml.";
            }
        } else if ($zip->statName("imsmanifest.xml")) {
            $xml = new SimpleXMLElement($zip->getFromName("imsmanifest.xml"));

            if (!isset($xml->metadata->schema) || $xml->metadata->schema != "ADL SCORM") {
                echo "Invalid Scorm imsmanifest.xml file";
            }
        } else {
            return "Unrecognized learning module archive file.";
        }

        $filesystem->createDir($this->directory . "/" . $path . $file_hash);
        for($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $fp = $zip->getStream($filename);
            try {
                $filesystem->putStream($this->directory . "/" . $path . $file_hash . "/" . $filename, $fp);
            } catch (Exception $e) {
                application_log("error", "Unable to write extracted Learning Object file to filesystem: ". $e->getMessage());
            }
            fclose($fp);
        }
        $zip->close();

        return true;
    }
}