<?php

namespace Entrada\Modules\Olab\Classes\Autoload;

use Entrada\Modules\Olab\Classes\HostSystemApi;
use Illuminate\Support\Facades\Log;

/**
 * Base class for autoload class groups.
 *
 * @version 1.0
 * @author wirunc
 */
class OlabAutoloadBase
{
    /**
     * Get the autoload physical base path
     * @return mixed
     */
    protected static function GetAutoLoadBasePath() {
        return HostSystemApi::getAPIFileRoot() . "/app/Modules/Olab/Classes/Autoload";
    }

}