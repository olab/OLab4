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
 * @property string $mime
 * @property string $name
 * @property string $path
 * @property string $args
 * @property integer $width
 * @property string $width_type
 * @property integer $height
 * @property string $height_type
 * @property string $h_align
 * @property string $v_align
 * @property string $is_shared
 * @property string $is_private
 */

class MapElements extends BaseModel {

    protected $table = 'map_elements';
    protected $fillable = ['map_id','mime','name','path','args','width','width_type','height','height_type','h_align','v_align','is_shared','is_private'];
    protected $validations = ['map_id' => 'exists:maps,id|integer|min:0|required',
                            'mime' => 'max:500|string',
                            'name' => 'max:200|string',
                            'path' => 'max:300|string',
                            'args' => 'max:100|string',
                            'width' => 'integer',
                            'width_type' => 'max:2|string',
                            'height' => 'integer',
                            'height_type' => 'max:2|string',
                            'h_align' => 'max:20|string',
                            'v_align' => 'max:20|string',
                            'is_shared' => 'integer|required',
                            'is_private' => 'integer|required'];

    public function Maps() {
        return $this->belongsTo('App\Modules\Olab\Models\Maps');
    }

}