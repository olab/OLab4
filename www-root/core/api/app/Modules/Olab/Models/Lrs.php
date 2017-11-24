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
 * @property string $is_enabled
 * @property string $name
 * @property string $url
 * @property string $username
 * @property string $password
 * @property string $api_version
 */

class Lrs extends BaseModel {

    protected $table = 'lrs';
    protected $fillable = ['is_enabled','name','url','username','password','api_version'];
    protected $validations = ['is_enabled' => 'integer|required',
                            'name' => 'max:255|string',
                            'url' => 'max:255|string',
                            'username' => 'max:255|string',
                            'password' => 'max:255|string',
                            'api_version' => 'integer|min:0|required'];

    public function LrsStatement() {
        return $this->hasMany('App\Modules\Olab\Models\LrsStatement');
    }

}