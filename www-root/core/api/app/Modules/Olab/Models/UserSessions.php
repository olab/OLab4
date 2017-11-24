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
 * @property string $uuid
 * @property integer $user_id
 * @property integer $map_id
 * @property string $start_time
 * @property string $user_ip
 * @property integer $webinar_id
 * @property integer $webinar_step
 * @property string $notCumulative
 * @property string $reset_at
 * @property string $end_time
 */

class UserSessions extends BaseModel {

    protected $table = 'user_sessions';
    protected $fillable = ['uuid','user_id','map_id','start_time','user_ip','webinar_id','webinar_step','notCumulative','reset_at','end_time'];
    protected $validations = ['uuid' => 'max:255|string',
                            'user_id' => 'integer|min:0|required',
                            'map_id' => 'exists:maps,id|integer|min:0|required',
                            'start_time' => 'required|numeric',
                            'user_ip' => 'max:50|string',
                            'webinar_id' => 'integer',
                            'webinar_step' => 'integer',
                            'notCumulative' => 'integer|required',
                            'reset_at' => 'numeric',
                            'end_time' => 'numeric'];

    public function Statements() {
        return $this->hasMany('App\Modules\Olab\Models\Statements');
    }
    public function UserBookmarks() {
        return $this->hasMany('App\Modules\Olab\Models\UserBookmarks');
    }
    public function UserBookmarks() {
        return $this->hasMany('App\Modules\Olab\Models\UserBookmarks');
    }
    public function UserNotes() {
        return $this->hasMany('App\Modules\Olab\Models\UserNotes');
    }
    public function UserResponses() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponses');
    }
    public function UserResponsesCBRef() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponsesCBRef');
    }
    public function UserResponsesC3st() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponsesC3st');
    }
    public function UserResponsesCopyExt() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponsesCopyExt');
    }
    public function UserResponsesExt() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponsesExt');
    }
    public function UserResponsesPart() {
        return $this->hasMany('App\Modules\Olab\Models\UserResponsesPart');
    }
    public function UserSessiontraces() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessiontraces');
    }
    public function UserSessiontracesCBRef() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessiontracesCBRef');
    }
    public function UserSessiontracesC3st() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessiontracesC3st');
    }
    public function UserSessiontracesExt() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessiontracesExt');
    }
    public function UserSessiontracesPart() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessiontracesPart');
    }
    public function Maps() {
        return $this->belongsTo('App\Modules\Olab\Models\Maps');
    }

}