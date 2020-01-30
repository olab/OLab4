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
 * @property integer $user_id
 * @property integer $map_id
 * @property integer $map_node_id
 * @property string $save_date
 * @property string $state_data
 */

class UserState extends BaseModel {

  public $timestamps = true;

  protected $table = 'user_state';
  protected $fillable = ['user_id','map_id','map_node_id','save_date','state_data'];
  protected $validations = ['user_id' => 'exists:users,id|integer|min:0|required',
                            'map_id' => 'exists:maps,id|integer|min:0|required',
                            'session_id' => 'exists:maps,id|integer|min:0|required',
                            'map_node_id' => 'exists:map_nodes,id|integer|min:0|required',
                            'state_data' => 'required'];

  public function scopeBySessionId( $query, $sessionId )  {
    return $query->where( 'session_id', $sessionId );
  }

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

  public function getUserState() {    
    return json_decode( $this->state_data, true );
  }

  public function putUserState( $aObject ) {
    $this->state_data = json_encode( $aObject );
  }

  public function GetValue( $key ) {

    $aState = json_decode( $this->state_data, true );

    if ( array_key_exists( $key, $aState )) {
      return $aState[$key];        
    }

    return null;
  }

  public function getCounter( $counter_id ) {
    
    $aState = $this->getUserState();

    foreach( $aState['map'] as &$item ) {
      if ( $item['id'] == $counter_id ) {
        return $item;
      }
    }

    foreach( $aState['node'] as &$item ) {
      if ( $item['id'] == $counter_id ) {
        return $item;
      }
    }

    return null;
  }

  public function updateCounter( $counter_id, $value ) {
  
    $aState = $this->getUserState();
    $bResult = false;

    foreach( $aState['map'] as &$item ) {
      if ( $item['id'] == $counter_id ) {
        $item['value'] = $value;
        $bResult = true;
        break;
      }
    }

    foreach( $aState['node'] as &$item ) {
      if ( $item['id'] == $counter_id ) {
        $item['value'] = $value;
        $bResult = true;
        break;
      }
    }

    $this->putUserState( $aState );

    return $bResult;
  }

  public function Maps() {
    return $this->belongsTo('Entrada\Modules\Olab\Models\Maps');
  }

  public function MapNodes() {
    return $this->belongsTo('Entrada\Modules\Olab\Models\MapNodes');
  }

  public function Users() {
    return $this->belongsTo('Entrada\Modules\Olab\Models\Users');
  }

}