<?php

namespace Entrada\Modules\Olab\Classes\xAPI;

use \Exception;
use Illuminate\Support\Facades\Log;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Models\Lrs;
use Entrada\Modules\Olab\Models\LrsStatement;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\Statements;
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Models\UserSessions;
use Entrada\Modules\Olab\Classes\xAPI\xAPICustomStatement;
use Entrada\Modules\Olab\Classes\xAPI\xAPIStatement;
use \DateTime;
use \TinCan\RemoteLRS;

/**
 * Map short summary.
 *
 * Map description.
 *
 * @version 1.0
 * @author wirunc
 */
class xAPIQuestions extends xAPI
{
    const ENTRY_TYPE_SINGLE_LINE = 1;
    const ENTRY_TYPE_MULTI_LNE = 2;
    const ENTRY_TYPE_MCQ = 3;
    const ENTRY_TYPE_PCQ = 4;
    const ENTRY_TYPE_SLIDER = 5;
    const ENTRY_TYPE_DRAG_AND_DROP = 6;
    const ENTRY_TYPE_SCT = 7;
    const ENTRY_TYPE_SJT = 8;
    const ENTRY_TYPE_CUMULATIVE = 9;
    const ENTRY_TYPE_RICH_TEXT = 10;
    const ENTRY_TYPE_TURK_TALK = 11;
    const ENTRY_TYPE_DROP_DOWN = 12;
    const ENTRY_TYPE_MCQ_GRID = 13;
    const ENTRY_TYPE_PCQ_GRID = 14;

    private $object;

    /**
     * Summary of __construct
     * @param Maps $obj 
     */
    public function __construct( Questions $obj ) {

        parent::__construct();
        $this->object = $obj;    
    }

    public static function getAdminBaseUrl()
    {
        return OLabUtilities::base(true) . 'questionManager/question/';
    }

    public function getAdminUrl()
    {
        return static::getAdminBaseUrl() . '/' . $this->object->imageable_type  
                                         . '/' . $this->object->imageable_id . '/' 
                                         . $this->object->entry_type_id . '/' . $this->object->id;
    }

    public function toxAPIExtensionObject()
    {
        $obj = $this->object;

        $result = $obj->toArray();
        $result['id'] = $this->getAdminUrl();
        $result['internal_id'] = $obj->id;

        return $result;
    }

    public function toxAPIObject()
    {
        $url = $this->getAdminUrl();

        $object = array(
            'id' => $url,
            'definition' => array(
                'name' => array(
                    'en-US' => 'question (#' . $this->object->id . ')'
                ),
                'description' => array(
                    'en-US' => 'Question stem: ' . xAPIStatement::sanitizeString($this->object->stem)
                ),
                'type' => 'http://adlnet.gov/expapi/activities/cmi.interaction',
                'moreInfo' => $url,
            ),

        );

        $object['definition']['extensions'][xAPIStatement::getExtensionQuestionKey()] 
            = $this->toxAPIExtensionObject();

        return $object;
    }

}