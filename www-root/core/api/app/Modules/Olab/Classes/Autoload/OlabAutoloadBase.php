<?php

namespace App\Modules\Olab\Classes\Autoload;

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
        return ENTRADA_ABSOLUTE . "/core/api/app/Modules/Olab/Classes/Autoload";
    }
}