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
 * @property string $modeUI
 * @property string $is_lti
 * @property string $settings
 */

class Users extends BaseModel {

    protected $table = 'users';
    protected $fillable = ['username','password','email','nickname','language_id','type_id','resetHashKey','resetHashKeyTime','resetAttempt','resetTimestamp','visualEditorAutosaveTime','oauth_provider_id','oauth_id','history','history_readonly','history_timestamp','modeUI','is_lti','settings'];
    protected $validations = ['username' => 'max:255|string',
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
                            'history_timestamp' => 'integer',
                            'modeUI' => 'required',
                            'is_lti' => 'integer',
                            'settings' => 'string'];

    public function MapUsers() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapUsers');
    }
    public function UserBookmarks() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserBookmarks');
    }
    public function UserGroups() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserGroups');
    }
    public function UserNotes() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserNotes');
    }
    public function WebinarUsers() {
        return $this->hasMany('Entrada\Modules\Olab\Models\WebinarUsers');
    }

}