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
 * @property integer $visual_id
 * @property string $name
 * @property integer $width
 * @property integer $height
 * @property integer $angle
 * @property integer $z_index
 * @property integer $x
 * @property integer $y
 */

class MapVisualDisplayImages extends BaseModel {

    protected $table = 'map_visual_display_images';
    protected $fillable = ['visual_id','name','width','height','angle','z_index','x','y'];
    protected $validations = ['visual_id' => 'exists:map_visual_displays,id|integer|min:0|required',
                            'name' => 'max:400|string',
                            'width' => 'integer|min:0',
                            'height' => 'integer|min:0',
                            'angle' => 'integer',
                            'z_index' => 'integer|min:0',
                            'x' => 'integer',
                            'y' => 'integer'];

    public function MapVisualDisplays() {
        return $this->belongsTo('App\Modules\Olab\Models\MapVisualDisplays');
    }

}