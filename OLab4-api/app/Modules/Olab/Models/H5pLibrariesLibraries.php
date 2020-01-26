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
use Entrada\Modules\Olab\Models\BaseModel;;

/**
 * @property integer $library_id
 * @property integer $required_library_id
 * @property string $dependency_type
 */

class H5pLibrariesLibraries extends BaseModel {

    protected $primaryKey = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $table = 'h5p_libraries_libraries';
    protected $fillable = ['library_id','required_library_id','dependency_type'];
    protected $validations = ['library_id' => 'integer|min:0|required',
                            'required_library_id' => 'integer|min:0|required',
                            'dependency_type' => 'max:31|string'];

    public static function GetByLibrary( $id ) {

      $sql = vsprintf( 
          "SELECT hl.name as machineName, 
                  hl.major_version as majorVersion, 
                  hl.minor_version as minorVersion, 
                  hll.dependency_type as dependencyType
                  FROM h5p_libraries_libraries hll
                  JOIN h5p_libraries hl ON hll.required_library_id = hl.id
                  WHERE hll.library_id = %d", $id );

      $content = BaseModel::GetRawSqlRows( $sql );

      return $content;

    }


}