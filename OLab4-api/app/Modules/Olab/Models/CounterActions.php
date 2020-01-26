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
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Models\PolymorphicModel;

/**
 * @property integer $id
 * @property integer $counter_id
 * @property string $operation_type
 * @property string $expression
 * @property integer $imageable_id
 * @property string $imagable_type
 */

class CounterActions extends PolymorphicModel {

    protected $table = 'system_counter_actions';
    protected $attributes = array(
      'operation_type' => 'open',
      'imageable_type' => MapNodes::IMAGEABLE_TYPE,
      'visible' => 1
    );

    protected $fillable = ['counter_id','map_id', 'operation_type','expression','imageable_id','imagable_type', 'visible'];
    protected $validations = ['counter_id' => 'exists:system_counters,id|integer|min:0|required',
                            'operation_type' => 'max:45|string',
                            'expression' => 'max:256|string',
                            'visible' => 'integer|required',
                            'map_id' => 'integer',
                            'imageable_id' => 'integer|required',
                            'imagable_type' => 'max:45|string'];

    public function toArray() {
      
      $aObj = parent::toArray();

      OLabUtilities::safe_rename( $aObj, 'operation_type');
      OLabUtilities::safe_rename( $aObj, 'map_id');
      OLabUtilities::safe_rename( $aObj, 'counter_id', 'counterId');
      OLabUtilities::safe_rename( $aObj, 'imageable_type');
      OLabUtilities::safe_rename( $aObj, 'imageable_id', 'nodeId');

      return $aObj;

    }

    public function scopeAt( $query, $id ) {
        $oData = $query->where( 'id', '=', $id )->first();
        return $oData;
    }

    public function scopeWithAction( $query, $actionName ) {
        return $query->where( 'operation_type', '=', $actionName );
    }

    public function Counters() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Counters');
    }

    private function create_record( $map_id, $node_id, $counter_id ) {

      $record = new CounterActions();
      $record->counter_id = $counter_id;
      $record->map_id = $map_id;
      $record->imageable_id = $node_id;

      $record->save();
    }

    private function get_records( $query, $map_id ) {
      
      // get all records for nodes in the map
      return $query->where('map_id', '=', $map_id)
                   ->orderBy( 'imageable_id', 'asc' )
                   ->get();
    }

    private function delete_grid_extras( $query, $map_id, $actual_records, $node_ids, $counter_ids ) {
      
      // delete all action records where node isn't assigned to map any more
      $records = $query->where('map_id', '=', $map_id)
                       ->whereNotIn('imageable_id', $node_ids )->get();
      foreach ($records as $record) {
      	$record->forceDelete();
      }
      
      // delete all action records where counter isn't assigned to map any more
      $records = $query->where('map_id', '=', $map_id)
                       ->whereNotIn('counter_id', $counter_ids )->get();
      foreach ($records as $record) {
      	$record->forceDelete();
      }

    }

    private function fill_grid_gaps( $query, $map_id, $actual_records, $node_ids, $counter_ids ) {
      
      // build associative array so can quickly find out what is stored
      $array_map = array();
      foreach ($actual_records as $record ) {
        $array_map[$record->imageable_id][$record->counter_id] = 0;
      }

      foreach ($node_ids as $node_id) {
        
        foreach ($counter_ids as $counter_id) {

          // if node_id isn't in db, then create record
          if ( !array_key_exists( $node_id, $array_map )) {
            $this->create_record( $map_id, $node_id, $counter_id );
          }

          // if node_id is in db, but counter isn't then create record
          else {
            if ( !array_key_exists( $counter_id, $array_map[ $node_id ])) {
              $this->create_record( $map_id, $node_id, $counter_id );                
            }
          }
        }
        
      }

    }

    public function scopeByMap( $query, $map_id ) {  
      
      $rebuild_grid = false;

      $aoCounters = Counters::ByMapId( $map_id )->select('id')->get()->toArray();
      $counter_count = sizeof($aoCounters);
      $counter_ids = array_column($aoCounters, 'id');

      $aoNodes = MapNodes::ByMapId( $map_id )->select('id')->get()->toArray();
      $map_node_count = sizeof( $aoNodes );
      $node_ids = array_column($aoNodes, 'id');

      $expected_record_count = $counter_count * $map_node_count;

      // get all records for nodes in the map
      $actual_records = $this->get_records( $query, $map_id );

      $actual_record_count = $actual_records->count();

      $rebuild_grid = $expected_record_count != $actual_record_count;

      if ( $rebuild_grid ) {

        $this->fill_grid_gaps( $query, $map_id, $actual_records, $node_ids, $counter_ids );

        $this->delete_grid_extras( $query, $map_id, $actual_records, $node_ids, $counter_ids );

        // re-read the (hopefully) complete set of records
        return CounterActions::ByMap($map_id);
      }

      // get list of distinct node id's that have actions
      //$b = $query->distinct()->where('imageable_type', '=', MapNodes::IMAGEABLE_TYPE)->whereIn( 'imageable_id', $node_ids )->get(['imageable_id']);
      //$db_node_count = $b->count();

      // get list of distinct counter id's that have actions
      //$c = $query->distinct()->where('imageable_type', '=', MapNodes::IMAGEABLE_TYPE)->whereIn( 'counter_id', $counter_ids )->get(['counter_id']);
      //$db_counter_count = $c->count();
                  
      return $actual_records;
    }
}