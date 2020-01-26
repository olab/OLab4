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
 * @property integer $content_id
 * @property integer $library_id
 * @property string $dependency_type
 * @property string $weight
 * @property string $drop_css
 */

class H5pContentsLibraries extends BaseModel {

    protected $table = 'h5p_contents_libraries';
    protected $fillable = ['content_id','library_id','dependency_type','weight','drop_css'];
    protected $validations = ['content_id' => 'integer|min:0|required',
                            'library_id' => 'integer|min:0|required',
                            'dependency_type' => 'max:31|string',
                            'weight' => 'integer|min:0|required',
                            'drop_css' => 'integer|min:0|required'];

    public static function LoadContentDependencies( $id, $type ) {

      $sql = vsprintf( "SELECT hl.id
                        , hl.name AS machineName
                        , hl.major_version AS majorVersion
                        , hl.minor_version AS minorVersion
                        , hl.patch_version AS patchVersion
                        , hl.preloaded_css AS preloadedCss
                        , hl.preloaded_js AS preloadedJs
                        , hcl.drop_css AS dropCss
                        , hcl.dependency_type AS dependencyType
                  FROM h5p_contents_libraries hcl
                  JOIN h5p_libraries hl ON hcl.library_id = hl.id
                  WHERE hcl.content_id = %d", $id );

      if ($type !== null) {
          $sql .= vsprintf(" AND hcl.dependency_type = '%s'", $type );
      }

      $sql .= " ORDER BY hcl.weight";

      $result = BaseModel::GetRawSqlRows( $sql );

      return $result;
    }

}