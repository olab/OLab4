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
 * @property string $text
 * @property integer $user_id
 * @property string $date
 * @property string $type
 * @property string $isEdit
 * @property integer $forum_id
 */

class DforumMessages extends BaseModel {

    protected $table = 'dforum_messages';
    protected $fillable = ['text','user_id','date','type','isEdit','forum_id'];
    protected $validations = ['text' => 'string',
                            'user_id' => 'integer|required',
                            'date' => 'required|date',
                            'type' => 'integer|required',
                            'isEdit' => 'integer|required',
                            'forum_id' => 'integer|required'];


}