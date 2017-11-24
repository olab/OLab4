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
 * @property string $name
 * @property integer $scope_type_id
 * @property integer $owning_id
 * @property string $value
 */

class Constants extends BaseModel {

    const OWNEDTYPE_NODE = 'App\Modules\Olab\Models\MapNodes';
    const OWNEDTYPE_MAP = 'App\Modules\Olab\Models\Maps';

    protected $table = 'system_constants';
    protected $fillable = ['name','imageable_id','imageable_type','value'];
    protected $validations = ['name' => 'max:200|string',
                            'imageable_id' => 'integer|required',
                            'imageable_type' => 'max:45|string',
                            'value' => ''];

    // define this model as polymorphic
    public function imageable() {
        return $this->morphTo();
    }

}