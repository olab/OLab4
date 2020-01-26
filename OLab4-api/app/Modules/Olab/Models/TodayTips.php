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
 * @property string $title
 * @property string $text
 * @property string $start_date
 * @property integer $weight
 * @property string $is_active
 * @property string $is_archived
 * @property string $end_date
 */

class TodayTips extends BaseModel {

    protected $table = 'today_tips';
    protected $fillable = ['title','text','start_date','weight','is_active','is_archived','end_date'];
    protected $validations = ['title' => 'max:300|string',
                            'text' => 'string',
                            'start_date' => 'required|date',
                            'weight' => 'integer|required',
                            'is_active' => 'integer|required',
                            'is_archived' => 'integer|required',
                            'end_date' => 'date'];


}