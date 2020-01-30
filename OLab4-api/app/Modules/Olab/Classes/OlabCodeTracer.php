<?php
/**
 * OpenLabyrinth [ http://www.openlabyrinth.ca ]
 *
 * OpenLabyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenLabyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenLabyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A class to trace function execution times
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Classes;

use Illuminate\Support\Facades\Log;
use \Exception;

class OlabCodeTracer {

    private $nStartTime;
    public $sBlockName;

    public function __construct( $sClassName, $sMethodName = null ) {

        try
        {
            $this->sBlockName = substr($sClassName, strrpos($sClassName, '\\') + 1);
            if ( $sMethodName != null ) {
                $this->sBlockName .= "::" . $sMethodName;
            }

            $this->nStartTime = microtime(true);
            Log::debug( $this->sBlockName . " entry");
        }
        catch (Exception $exception)
        {
            /* eat all exceptions */
        }

    }

    public function __destruct() {
        Log::debug( $this->sBlockName . " exit. Elapsed time " . $this->elapsedTime() . " sec" );
    }

    /**
     * Utility class for function timing (in seconds)
     * @return mixed
     */
    public function elapsedTime()
    {
        $now = microtime(true);
        return ($now - $this->nStartTime);
    }
}