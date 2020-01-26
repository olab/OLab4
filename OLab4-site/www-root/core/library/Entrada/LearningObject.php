<?php

define("SCORM_12", 1.2);
define("SCORM_2004", 2004);

class Entrada_LearningObject
{
    protected $id;
    var $lrs;
    protected $xml_file;
    protected $title;
    protected $description;
    protected $launch_file;
    protected $activity_id;

    public function __construct() {
        $lrs_endpoint   = Entrada_Settings::read("lrs_endpoint");
        $lrs_version    = Entrada_Settings::read("lrs_version");
        $lrs_username   = Entrada_Settings::read("lrs_username");
        $lrs_password   = Entrada_Settings::read("lrs_password");

        if ( !$lrs_endpoint || !$lrs_version || !$lrs_username || !$lrs_password) {
            $lrs_endpoint = ENTRADA_URL . "/api/lrs-to-stats.api.php/";
            $lrs_version = "1.0.0";
            $lrs_username = "null";
            $lrs_password = "null";
        }

        $this->lrs = new \TinCan\RemoteLRS(
            $lrs_endpoint,
            $lrs_version,
            $lrs_username,
            $lrs_password
        );
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $xml_file
     */
    public function setXmlFile($xml_file) {
        $this->xml_file = $xml_file;
    }

    /**
     * @return string
     */
    public function getXmlFile() {
        return $this->xml_file;
    }

    /**
     * @return mixed
     */
    public function getActivityId() {
        return $this->activity_id;
    }

    /**
     * @return mixed
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getLaunchFile() {
        return $this->launch_file;
    }

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Validate the xml file.
     *
     * @return bool
     */
    public function validate() {
        global $filesystem;

        if ($filesystem->has($this->xml_file)) {
            try {
                $xml_content = $filesystem->read($this->xml_file);
                $xml = XMLReader::XML($xml_content);
                $xml->setParserProperty(XMLReader::VALIDATE, true);

                return $xml->isValid();
            } catch (Exception $e) {
                application_log("error", "Failed to validate manifest XML file: " . $e->getMessage());
            }
        }

        return false;
    }

    public function getGlobalParametersAndState($key, $actor) {
        return $this->lrs->retrieveState($this->getActivityId(),$actor,$key);

    }

    public function getRegistrationID() {
        $tinCanPHPUtil = new \TinCan\Util();
        return $tinCanPHPUtil->getUUID();
    }

    public function launchStatement($registration_id, $actor) {
        $tinCanPHPUtil = new \TinCan\Util();
        $statementid = $tinCanPHPUtil->getUUID();

        $statement = new \TinCan\Statement(
            array(
                'id' => $statementid,
                'actor' => $actor,
                'verb' => array(
                    'id' => 'http://adlnet.gov/expapi/verbs/launched',
                    'display' => array(
                        'en-US' => 'launched'
                    )
                ),

                'object' => array(
                    'id' =>  $this->getActivityId(),
                    'objectType' => "Activity"
                ),

                "context" => array(
                    "registration" => $registration_id,
                    "contextActivities" => array(
                        "category"  => array(
                            array(
                                "id" => "https://entrada.org",
                                "objectType" => "Activity",
                                "definition" => array (
                                    "type" => "http://id.tincanapi.com/activitytype/source"
                                )
                            )
                        )
                    )
                ),
                "timestamp" => date(DATE_ATOM)
            )
        );

        $response = $this->lrs->saveStatement($statement);
        return $response;
    }
}