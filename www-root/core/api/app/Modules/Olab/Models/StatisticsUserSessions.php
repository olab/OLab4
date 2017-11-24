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
 * @property integer $user_id
 * @property integer $map_id
 * @property string $start_time
 * @property string $end_time
 * @property string $user_ip
 * @property integer $webinar_id
 * @property integer $webinar_step
 * @property integer $date_save_id
 */

class StatisticsUserSessions extends BaseModel {

    protected $table = 'statistics_user_sessions';
    protected $fillable = ['user_id','map_id','start_time','end_time','user_ip','webinar_id','webinar_step','date_save_id'];
    protected $validations = ['user_id' => 'integer|required',
                            'map_id' => 'integer|required',
                            'start_time' => 'required|numeric',
                            'end_time' => 'numeric',
                            'user_ip' => 'max:50|string',
                            'webinar_id' => 'integer|required',
                            'webinar_step' => 'integer|required',
                            'date_save_id' => 'integer|required'];


}