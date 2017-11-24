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

namespace App\Modules\Olab\Models;
use App\Modules\Olab\Models\BaseModel;

/**
 * @property integer $id
 * @property integer $user_id
 * @property integer $map_id
 * @property integer $map_node_id
 * @property string $save_date
 * @property string $state_data
 */

class UserState extends BaseModel {

  protected $table = 'user_state';
  protected $fillable = ['user_id','map_id','map_node_id','save_date','state_data'];
  protected $validations = ['user_id' => 'exists:users,id|integer|min:0|required',
                            'map_id' => 'exists:maps,id|integer|min:0|required',
                            'map_node_id' => 'exists:map_nodes,id|integer|min:0|required',
                            'state_data' => 'required'];

  public function scopeByUser( $query, $userId )  {
    return $query->where( 'user_id', $userId );
  }
  
  public function scopeByMap( $query, $userId, $iMapId )  {
    return $query->where( 'user_id', $userId )
                 ->where( 'map_id' , $iMapId );
  }

  public function scopeByNode( $query, $userId, $mapId, $nodeId ) {
    return $query->where( 'user_id', $userId )
      ->where( 'map_id', $mapId )
      ->where( 'map_node_id', $nodeId );
  }

  public function Maps() {
    return $this->belongsTo('App\Modules\Olab\Models\Maps');
  }

  public function MapNodes() {
    return $this->belongsTo('App\Modules\Olab\Models\MapNodes');
  }

  public function Users() {
    return $this->belongsTo('App\Modules\Olab\Models\Users');
  }

}