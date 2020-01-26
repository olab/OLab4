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
 * @property integer $session_id
 * @property integer $user_id
 * @property integer $map_id
 * @property integer $node_id
 * @property string $is_redirected
 * @property string $counters
 * @property string $date_stamp
 * @property string $confidence
 * @property string $dams
 * @property string $bookmark_made
 * @property string $bookmark_used
 * @property string $end_date_stamp
 */

class StatisticsUserSessiontraces extends BaseModel {

    protected $table = 'statistics_user_sessiontraces';
    protected $fillable = ['session_id','user_id','map_id','node_id','is_redirected','counters','date_stamp','confidence','dams','bookmark_made','bookmark_used','end_date_stamp'];
    protected $validations = ['session_id' => 'integer|min:0|required',
                            'user_id' => 'integer|min:0|required',
                            'map_id' => 'integer|min:0|required',
                            'node_id' => 'integer|min:0|required',
                            'is_redirected' => 'integer|required',
                            'counters' => 'max:700|string',
                            'date_stamp' => 'numeric',
                            'confidence' => 'integer',
                            'dams' => 'max:700|string',
                            'bookmark_made' => 'numeric',
                            'bookmark_used' => 'numeric',
                            'end_date_stamp' => 'numeric'];


}