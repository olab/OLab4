<?php

class Entrada_LearningObject_Scorm extends Entrada_LearningObject
{
    protected $attempt;
    protected $scorm_version;
    protected $auto;

    /**
     * @return mixed
     */
    public function getAttempt()
    {
        return $this->attempt;
    }

    /**
     * @return mixed
     */
    public function getAuto()
    {
        return $this->auto;
    }

    /**
     * @return mixed
     */
    public function getScormVersion()
    {
        return $this->scorm_version;
    }

    public static function loadScormModule($xml_file) {
        $version = Entrada_LearningObject_ScormUtils::scormVersion($xml_file);

        switch ($version) {
            case SCORM_12:
                return new Entrada_LearningObject_Scorm12($xml_file);
                break;

            case SCORM_2004:
                return new Entrada_LearningObject_Scorm2004($xml_file);
                break;

            default:
                return null;
        }
    }
}