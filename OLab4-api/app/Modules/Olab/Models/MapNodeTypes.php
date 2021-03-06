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
 * @property integer $id
 * @property string $name
 * @property string $description
 */

class MapNodeTypes extends BaseModel {

    protected $table = 'map_node_types';
    protected $fillable = ['name','description'];
    protected $validations = ['name' => 'max:70|string',
                            'description' => 'max:500|string'];

    private static $rootId = null;
    private static $childId = null;

    const ROOT_TYPE = 'root';
    const CHILD_TYPE = 'child';

    /**
     * Get id of ROOT type node
     * @return mixed
     */
    public static function scopeRootTypeId()
    {
        if ( self::$rootId == null ) {
            $record = ( MapNodeTypes::where( 'name', '=', self::ROOT_TYPE )->firstOrFail(['id']) );
            self::$rootId = $record['id'];
        }

        return self::$rootId;
    }

    /**
     * Get id of CHILD type node
     * @return mixed
     */
    public static function scopeChildTypeId()
    {
        if ( self::$childId == null ) {
            $record = ( MapNodeTypes::where( 'name', '=', self::CHILD_TYPE )->firstOrFail(['id']) );
            self::$childId = $record['id'];
        }

        return self::$childId;
    }

}