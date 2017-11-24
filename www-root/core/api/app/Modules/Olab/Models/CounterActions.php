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
 * @property integer $counter_id
 * @property string $operation_type
 * @property string $expression
 * @property integer $imageable_id
 * @property string $imagable_type
 */

class CounterActions extends BaseModel {

    protected $table = 'system_counter_actions';
    protected $fillable = ['counter_id','operation_type','expression','imageable_id','imagable_type'];
    protected $validations = ['counter_id' => 'exists:system_counters,id|integer|min:0|required',
                            'operation_type' => 'max:45|string',
                            'expression' => 'max:256|string',
                            'imageable_id' => 'integer|required',
                            'imagable_type' => 'max:45|string'];

    // define this model as polymorphic
    public function imageable() {
      return $this->morphTo();
    }

    public function scopeWithAction( $query, $actionName ) {
        return $query->where( 'operation_type', '=', $actionName );
    }

    public function Counters() {
        return $this->belongsTo('App\Modules\Olab\Models\Counters');
    }

}