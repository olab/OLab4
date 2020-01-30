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
 * @property integer $visual_id
 * @property integer $x
 * @property integer $y
 * @property integer $width
 * @property integer $height
 * @property string $background_color
 * @property integer $border_size
 * @property string $border_color
 * @property integer $border_radius
 * @property integer $angle
 * @property integer $z_index
 */

class MapVisualDisplayPanels extends BaseModel {

    protected $table = 'map_visual_display_panels';
    protected $fillable = ['visual_id','x','y','width','height','background_color','border_size','border_color','border_radius','angle','z_index'];
    protected $validations = ['visual_id' => 'exists:map_visual_displays,id|integer|min:0|required',
                            'x' => 'integer',
                            'y' => 'integer',
                            'width' => 'integer|min:0',
                            'height' => 'integer|min:0',
                            'background_color' => 'max:40|string',
                            'border_size' => 'integer|min:0',
                            'border_color' => 'max:40|string',
                            'border_radius' => 'integer|min:0',
                            'angle' => 'integer',
                            'z_index' => 'integer|min:0'];

    public function MapVisualDisplays() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapVisualDisplays');
    }

}