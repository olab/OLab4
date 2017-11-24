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
 * @property integer $map_id
 * @property string $name
 * @property string $description
 * @property string $start_value
 * @property integer $icon_id
 * @property string $prefix
 * @property string $suffix
 * @property string $visible
 * @property integer $out_of
 * @property integer $status
 */

class MapCounters extends BaseModel {

    protected $table = 'map_counters';
    protected $fillable = ['map_id','name','description','start_value','icon_id','prefix','suffix','visible','out_of','status'];
    protected $validations = ['map_id' => 'exists:maps,id|integer|min:0',
                            'name' => 'max:200|string',
                            'description' => 'string',
                            'start_value' => 'required',
                            'icon_id' => 'integer',
                            'prefix' => 'max:20|string',
                            'suffix' => 'max:20|string',
                            'visible' => 'integer',
                            'out_of' => 'integer',
                            'status' => 'integer|required'];

    // this is added to hide the generated 'pivot' column from showing in the many-to-many queries
    protected $hidden = array('pivot');

    public function MapCounterRules() {
        return $this->hasMany('App\Modules\Olab\Models\MapCounterRules');
    }

    public function MapVisualDisplayCounters() {
        return $this->hasMany('App\Modules\Olab\Models\MapVisualDisplayCounters');
    }

    public function Map() {
        return $this->belongsTo('App\Modules\Olab\Models\Maps');
    }

    public function MapNodes() {
        return $this->belongsToMany('App\Modules\Olab\Models\MapNodes', 'map_node_counters', 'counter_id', 'node_id' );
    }

}