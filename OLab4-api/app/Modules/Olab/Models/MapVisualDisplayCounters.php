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
 * @property integer $counter_id
 * @property integer $label_x
 * @property integer $label_y
 * @property integer $label_angle
 * @property string $label_font_style
 * @property string $label_text
 * @property integer $label_z_index
 * @property integer $value_x
 * @property integer $value_y
 * @property integer $value_angle
 * @property string $value_font_style
 * @property integer $value_z_index
 */

class MapVisualDisplayCounters extends BaseModel {

    protected $table = 'map_visual_display_counters';
    protected $fillable = ['visual_id','counter_id','label_x','label_y','label_angle','label_font_style','label_text','label_z_index','value_x','value_y','value_angle','value_font_style','value_z_index'];
    protected $validations = ['visual_id' => 'exists:map_visual_displays,id|integer|min:0|required',
                            'counter_id' => 'exists:map_counters,id|integer|min:0|required',
                            'label_x' => 'integer',
                            'label_y' => 'integer',
                            'label_angle' => 'integer',
                            'label_font_style' => 'max:300|string',
                            'label_text' => 'string',
                            'label_z_index' => 'integer|min:0',
                            'value_x' => 'integer',
                            'value_y' => 'integer',
                            'value_angle' => 'integer',
                            'value_font_style' => 'max:300|string',
                            'value_z_index' => 'integer|min:0'];

    public function MapVisualDisplays() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapVisualDisplays');
    }
    public function MapCounters() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapCounters');
    }

}