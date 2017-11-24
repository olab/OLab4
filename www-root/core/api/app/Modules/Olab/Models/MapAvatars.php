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
 * @property string $skin_1
 * @property string $skin_2
 * @property string $cloth
 * @property string $nose
 * @property string $hair
 * @property string $environment
 * @property string $accessory_1
 * @property string $bkd
 * @property string $sex
 * @property string $mouth
 * @property string $outfit
 * @property string $bubble
 * @property string $bubble_text
 * @property string $accessory_2
 * @property string $accessory_3
 * @property string $age
 * @property string $eyes
 * @property string $hair_color
 * @property string $image
 * @property integer $is_private
 */

class MapAvatars extends BaseModel {

    protected $table = 'map_avatars';
    protected $fillable = ['map_id','skin_1','skin_2','cloth','nose','hair','environment','accessory_1','bkd','sex','mouth','outfit','bubble','bubble_text','accessory_2','accessory_3','age','eyes','hair_color','image','is_private'];
    protected $validations = ['map_id' => 'exists:maps,id|integer|min:0|required',
                            'skin_1' => 'max:6|string',
                            'skin_2' => 'max:6|string',
                            'cloth' => 'max:6|string',
                            'nose' => 'max:20|string',
                            'hair' => 'max:20|string',
                            'environment' => 'max:20|string',
                            'accessory_1' => 'max:20|string',
                            'bkd' => 'max:6|string',
                            'sex' => 'max:20|string',
                            'mouth' => 'max:20|string',
                            'outfit' => 'max:20|string',
                            'bubble' => 'max:20|string',
                            'bubble_text' => 'max:100|string',
                            'accessory_2' => 'max:20|string',
                            'accessory_3' => 'max:20|string',
                            'age' => 'max:2|string',
                            'eyes' => 'max:20|string',
                            'hair_color' => 'max:6|string',
                            'image' => 'max:100|string',
                            'is_private' => 'integer|required'];

    public function Maps() {
        return $this->belongsTo('App\Modules\Olab\Models\Maps');
    }

}