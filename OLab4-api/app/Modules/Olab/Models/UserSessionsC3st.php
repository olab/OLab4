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
 * @property integer $user_id
 * @property integer $map_id
 * @property integer $start_time
 * @property string $user_ip
 * @property integer $webinar_id
 * @property integer $webinar_step
 */

class UserSessionsC3st extends BaseModel {

    protected $table = 'user_sessions_c3st';
    protected $fillable = ['user_id','map_id','start_time','user_ip','webinar_id','webinar_step'];
    protected $validations = ['user_id' => 'integer|min:0|required',
                            'map_id' => 'exists:maps,id|integer|min:0|required',
                            'start_time' => 'integer|required',
                            'user_ip' => 'max:50|string',
                            'webinar_id' => 'integer',
                            'webinar_step' => 'integer'];

    public function Maps() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Maps');
    }

}