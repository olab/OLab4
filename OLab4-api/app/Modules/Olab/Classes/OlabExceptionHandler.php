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
 * A class to expose information from a hosting system
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Classes;

use Illuminate\Support\Facades\Log;

class OlabExceptionHandler
{
    /**
     * Logger for exceptions
     * @param \Exception $exception
     * @param string $sSource
     * @param boolean $bRethrow
     * @throws \Exception
     */
    public static function logException( $sSource, $exception, $bRethrow = true ) {

        $message = $sSource . ": " . $exception->getMessage();
        Log::error( $message );

        $message = $exception->getTraceAsString();
        Log::error( $message );

        if ( $bRethrow ) {
            throw $exception;
        }
    }

    /**
     * Handler for RestAPI errors
     * @param mixed $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public static function restApiError( $exception ) {

        $fileName = pathinfo( $exception->getFile(), PATHINFO_BASENAME );
        $message = $exception->getMessage() . ": " . $fileName. "(" . $exception->getLine() . ")";
        Log::error( $message );

        $payload['error'] = $message;
        $payload['exception'] = $exception;
        return response()->json($payload);
    }
}