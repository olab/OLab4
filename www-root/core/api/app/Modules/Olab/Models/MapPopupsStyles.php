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
 * @property integer $map_popup_id
 * @property string $is_default_background_color
 * @property string $is_background_transparent
 * @property string $background_color
 * @property string $font_color
 * @property string $border_color
 * @property string $is_border_transparent
 * @property string $background_transparent
 * @property string $border_transparent
 */

class MapPopupsStyles extends BaseModel {

    protected $table = 'map_popups_styles';
    protected $fillable = ['map_popup_id','is_default_background_color','is_background_transparent','background_color','font_color','border_color','is_border_transparent','background_transparent','border_transparent'];
    protected $validations = ['map_popup_id' => 'integer|required',
                            'is_default_background_color' => 'integer|required',
                            'is_background_transparent' => 'integer|required',
                            'background_color' => 'max:10|string',
                            'font_color' => 'max:10|string',
                            'border_color' => 'max:10|string',
                            'is_border_transparent' => 'integer|required',
                            'background_transparent' => 'max:4|string',
                            'border_transparent' => 'max:4|string'];


}