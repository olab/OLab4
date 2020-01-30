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
 * A model for OLab settings.
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Models;
use Entrada\Modules\Olab\Models\BaseModel;

/**
 * @property integer $id
 * @property string $key
 * @property string $description
 * @property string $value
 */

class SystemSettings extends BaseModel {

    const FILEROOT_KEY = 'FileRoot';

    protected $table = 'system_settings';
    protected $fillable = ['key','description','value'];
    protected $validations = ['key' => 'max:45|string',
                            'description' => 'max:256|string',
                            'value' => 'max:45|string'];

    public function scopeAt( $query, $id ) {
        return $query->where( 'id', '=', $id )->first();
    }

    public function scopeByKey( $query, $key ) {
        return $query->where( 'key', '=', $key )->first();
    }

}