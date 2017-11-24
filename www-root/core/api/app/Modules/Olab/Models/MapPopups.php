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
 * @property string $title
 * @property string $text
 * @property integer $position_type
 * @property integer $position_id
 * @property integer $time_before
 * @property integer $time_length
 * @property string $is_enabled
 * @property integer $title_hide
 * @property string $annotation
 */

class MapPopups extends BaseModel {

    protected $table = 'map_popups';
    protected $fillable = ['map_id','title','text','position_type','position_id','time_before','time_length','is_enabled','title_hide','annotation'];
    protected $validations = ['map_id' => 'integer|required',
                            'title' => 'max:300|string',
                            'text' => 'string',
                            'position_type' => 'integer|required',
                            'position_id' => 'integer|required',
                            'time_before' => 'integer|required',
                            'time_length' => 'integer|required',
                            'is_enabled' => 'integer|required',
                            'title_hide' => 'integer|required',
                            'annotation' => 'max:50|string'];

    public function MapPopupsCounters() {
        return $this->hasMany('App\Modules\Olab\Models\MapPopupsCounters');
    }

}