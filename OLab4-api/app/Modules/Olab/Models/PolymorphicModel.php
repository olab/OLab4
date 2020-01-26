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

use Illuminate\Database\Eloquent\Model;
use Validator;
use Illuminate\Support\Facades\DB;
use Entrada\Modules\Olab\Classes\OLabUtilities;

class PolymorphicModel extends BaseModel
{
  // define this model as polymorphic
  public function imageable() {
    return $this->morphTo();
  }

  public function scopeAbbreviated( $query ) {
    return $query->select( 'id', 'name' );    
  }

  public function toArray() {
    
    $aObj = parent::toArray();

    OLabUtilities::safe_rename( $aObj, 'updated_at', "updatedAt" );
    OLabUtilities::safe_rename( $aObj, 'created_at', 'createdAt' );
    OLabUtilities::safe_rename( $aObj, 'imageable_id', "parentId" );
    OLabUtilities::safe_rename( $aObj, 'imageable_type', 'scopeLevel' );

    return $aObj;
  }

  public static function getPickList( $objs ) {

    $list = array();

    foreach ($objs as $obj) {
    	
      $item = array();
      $item['id'] = $obj['id'];
      $item['name'] = $obj['name'];
      $item['description'] = $obj['description'];

      array_push( $list, $item );
    }
    
    return $list;
  }
}