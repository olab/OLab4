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

namespace App\Modules\Olab\Models;
use App\Modules\Olab\Models\BaseModel;

/**
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $start_value
 * @property integer $icon_id
 * @property string $prefix
 * @property string $suffix
 * @property string $visible
 * @property integer $out_of
 * @property integer $status
 * @property integer $imageable_id
 * @property string $imageable_type
 */

class Counters extends BaseModel {

    const SYSTEM_NODE_COUNTER_NAME = "sys:nodeCounter";

    // disable automatic timestamps
    public $timestamps = false;

    protected $table = 'system_counters';
    protected $fillable = ['name','description','start_value','icon_id','prefix','suffix','visible','out_of','status','imageable_id','imageable_type'];
    protected $validations = ['name' => 'max:200|string',
                            'description' => 'string',
                            'start_value' => 'required',
                            'icon_id' => 'integer',
                            'prefix' => 'max:20|string',
                            'suffix' => 'max:20|string',
                            'visible' => 'integer',
                            'out_of' => 'integer',
                            'status' => 'integer|required',
                            'imageable_id' => 'integer|required',
                            'imageable_type' => 'max:45|string'];

    // define this model as polymorphic
    public function imageable() {
        return $this->morphTo();
    }

    public function scopeAbbreviated( $query ) {
        return $query->select( 'system_counters.id',
                               'system_counters.name',
                               'system_counters.visible',
                               'system_counters.start_value' );
    }

    public function scopeAtByName( $query, $name ) {
        return $query->where( 'name', '=', $name )
                     ->first();
    }

}