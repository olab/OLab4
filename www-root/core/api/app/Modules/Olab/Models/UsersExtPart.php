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
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $nickname
 * @property integer $language_id
 * @property integer $type_id
 * @property string $resetHashKey
 * @property string $resetHashKeyTime
 * @property integer $resetAttempt
 * @property string $resetTimestamp
 * @property integer $visualEditorAutosaveTime
 * @property integer $oauth_provider_id
 * @property string $oauth_id
 * @property string $history
 * @property string $history_readonly
 * @property integer $history_timestamp
 */

class UsersExtPart extends BaseModel {

    protected $table = 'users_ext_part';
    protected $fillable = ['username','password','email','nickname','language_id','type_id','resetHashKey','resetHashKeyTime','resetAttempt','resetTimestamp','visualEditorAutosaveTime','oauth_provider_id','oauth_id','history','history_readonly','history_timestamp'];
    protected $validations = ['username' => 'max:40|string',
                            'password' => 'max:800|string',
                            'email' => 'max:250|string',
                            'nickname' => 'max:120|string',
                            'language_id' => 'integer|required',
                            'type_id' => 'integer|required',
                            'resetHashKey' => 'max:255|string',
                            'resetHashKeyTime' => 'date',
                            'resetAttempt' => 'integer',
                            'resetTimestamp' => 'date',
                            'visualEditorAutosaveTime' => 'integer',
                            'oauth_provider_id' => 'integer',
                            'oauth_id' => 'max:300|string',
                            'history' => 'max:255|string',
                            'history_readonly' => 'integer',
                            'history_timestamp' => 'integer'];


}