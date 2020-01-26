<?php
/**
 * OLab [ http://www.openlabyrith.ca ]
 *
 * OLab is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OLab is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A model for OLab maps.
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Models;

use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Illuminate\Support\Facades\Log;

/**
 * @property integer $id
 * @property string $name
 * @property string $description
 */

class Courses extends BaseScopedObjectOwnerModel {

  const DEFAULT_LOCAL_ID = 1;
  const IMAGEABLE_TYPE = "Courses";

  protected $table = 'system_courses';

  public function scopeAt( $query, $id ) {

    // if no id passed in, assume local server
    if ( $id == null ) {
      $id = self::DEFAULT_LOCAL_ID;
    }

    $oObj = $query->where( 'id', '=', $id )
                    ->Constants()
                    ->Scripts()
                    ->Files()
                    ->Questions()
                    ->Counters()
                    ->first();

    //$oServer = $query->where( 'id', '=', $id )
    //             ->with( ScopedObjectManager::RELATION_NAME_CONSTANTS )
    //             ->with( 'Scripts' )
    //             ->with( 'Files' )
    //             ->with( ScopedObjectManager::RELATION_NAME_QUESTIONS )
    //             ->with( ScopedObjectManager::RELATION_NAME_COUNTERS )
    //             ->first();

    Log::debug( "server " . $id . " #Constants = " . $oObj->Constants->count() );
    Log::debug( "server " . $id . " #Files = " . $oObj->Files->count() );
    Log::debug( "server " . $id . " #Questions = " . $oObj->Questions->count() );
    Log::debug( "server " . $id . " #Counters = " . $oObj->Counters->count() );

    return $oObj;
  }
}