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
 * @property integer $counter_id
 * @property integer $relation_id
 * @property string $value
 * @property string $function
 * @property integer $redirect_node_id
 * @property string $message
 * @property integer $counter
 * @property string $counter_value
 */

class MapCounterRules extends BaseModel {

    protected $table = 'map_counter_rules';
    protected $fillable = ['counter_id','relation_id','value','function','redirect_node_id','message','counter','counter_value'];
    protected $validations = ['counter_id' => 'exists:map_counters,id|integer|min:0|required',
                            'relation_id' => 'integer|required',
                            'value' => 'required',
                            'function' => 'max:50|string',
                            'redirect_node_id' => 'integer',
                            'message' => 'max:500|string',
                            'counter' => 'integer',
                            'counter_value' => 'max:50|string'];

    public function MapCounters() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapCounters');
    }

}