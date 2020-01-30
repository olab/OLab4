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

/**
 * @property string $type
 * @property string $library_name
 * @property string $library_version
 * @property integer $num
 */

class H5pCounters extends BaseModel {

    protected $table = 'h5p_counters';
    protected $fillable = ['type','library_name','library_version','num'];
    protected $validations = ['type' => 'max:63|string',
                            'library_name' => 'max:127|string',
                            'library_version' => 'max:31|string',
                            'num' => 'integer|min:0|required'];

    public function scopeLibraryStats( $type ) {
      
      $sql = vsprintf( 
          "SELECT library_name AS name,
               library_version AS version,
               num
          FROM h5p_counters
          WHERE type = %s", $type );

      $content = BaseModel::GetRawSqlRows( $sql );

      return $content;
    }
}