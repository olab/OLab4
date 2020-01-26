<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
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

class Servers extends BaseScopedObjectOwnerModel {

  const DEFAULT_LOCAL_ID = 1;
  const IMAGEABLE_TYPE = "Servers";

  protected $table = 'system_servers';

  public function scopeAt( $query, $id ) {

    // if no id passed in, assume local server
    if ( $id == null ) {
      $id = self::DEFAULT_LOCAL_ID;
    }

    $oObj = $query->where( 'id', '=', $id )
                    ->Constants()
                    ->Themes()
                    ->Scripts()
                    ->Files()
                    ->Questions()
                    ->Counters()
                    ->first();

    Log::debug( "server " . $id . " #Constants = " . $oObj->Constants->count() );
    Log::debug( "server " . $id . " #Files = " . $oObj->Files->count() );
    Log::debug( "server " . $id . " #Questions = " . $oObj->Questions->count() );
    Log::debug( "server " . $id . " #Counters = " . $oObj->Counters->count() );

    return $oObj;
  }

  public function scopeDefault( $query ) {
    return $this->scopeAt( $query, null );
  }

}