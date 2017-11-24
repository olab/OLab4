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
 * @property integer $condition_id
 * @property integer $scenario_id
 * @property integer $node_id
 * @property string $value
 * @property string $appears
 */

class ConditionsChange extends BaseModel {

    protected $table = 'conditions_change';
    protected $fillable = ['condition_id','scenario_id','node_id','value','appears'];
    protected $validations = ['condition_id' => 'exists:conditions,id|integer|required',
                            'scenario_id' => 'exists:webinars,id|integer|required',
                            'node_id' => 'exists:map_nodes,id|integer|min:0|required',
                            'value' => 'string',
                            'appears' => 'integer|required'];

    public function Conditions() {
        return $this->belongsTo('App\Modules\Olab\Models\Conditions');
    }
    public function MapNodes() {
        return $this->belongsTo('App\Modules\Olab\Models\MapNodes');
    }
    public function Webinars() {
        return $this->belongsTo('App\Modules\Olab\Models\Webinars');
    }
    public function Conditions() {
        return $this->belongsTo('App\Modules\Olab\Models\Conditions');
    }
    public function MapNodes() {
        return $this->belongsTo('App\Modules\Olab\Models\MapNodes');
    }
    public function Webinars() {
        return $this->belongsTo('App\Modules\Olab\Models\Webinars');
    }

}