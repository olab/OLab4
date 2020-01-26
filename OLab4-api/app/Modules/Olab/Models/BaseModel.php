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

use Illuminate\Database\Eloquent\Model;
use Validator;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model {

  protected $post_to_db_column_translation = array();

  /**
   * Look up a post field name and see if there's a translation
   * @param mixed $key 
   * @return mixed
   */
  public function GetFieldTranslation($key) { 

    if ( array_key_exists( $key, $this->post_to_db_column_translation ) ) {
      return $this->post_to_db_column_translation[ $key ];
    }

    return $key;
  }

  // this gets rid of properties in form of 'map_id' and changes it to 'mapId'
  public static $snakeAttributes = false;

  protected $validations = array();

  // disable auto updating of an updated_at or created_at support
  // when writing to the database
  public $timestamps = false;

  /**
   * Database override
   */
  protected $connection = 'olab_database';

  public static function GetRawSqlRows( $sql ) {

    $result = self::getRawConnection()->select( $sql );
    return json_decode(json_encode($result), true);      

  }

  public static function GetRawSqlRow( $sql ) {

    $result = BaseModel::getRawConnection()->select( $sql );
    if ( sizeof($result) == 0 )
      return [];
    $content = json_decode(json_encode($result), true)[0];
    return $content;

  }

  public static function getRawConnection() {
    return DB::connection('olab_database');
  }

  public static function enableLogging() {
    $conn = DB::connection('olab_database');
    $conn->enableQueryLog();        
  }

  public static function getQueryLog() {
    $conn = DB::connection('olab_database');
    return $conn->getQueryLog();                
  }

  public static function beginTransaction() {
    $conn = DB::connection('olab_database');
    $conn->beginTransaction();
  }

  public static function commit() {
    $conn = DB::connection('olab_database');
    $conn->commit();
  }

  public static function rollBack() {
    $conn = DB::connection('olab_database');
    $conn->rollBack();
  }   

  public function validate() {
    $array = $this->toArray();
    $validator = Validator::make($array, $this->validations);
    if ($validator->fails()) {
      $messages = $validator->messages();
      return $messages->all();
    } else {
      return true;
    }
  }

  protected static function buildXMLFileName() {
    
  }

  public function scopeById( $query, $id ) {
    return $query->where( 'id', '=', $id );
  }

  public function scopeByIdOrName( $query, $id ) {
    return $query->where( 'id', '=', $id )
                 ->orWhere( 'name', '=', $id )
                 ->first();
  }

  /**
   * Get object from wiki tag
   * @param mixed $wiki_tag 
   */
  public static function GetObjectFromWiki( $tag, $id ) {
    
    switch ($tag)
    {
      case Questions::WIKI_TAG_QUESTION:
        return Questions::ByIdOrName( $id );

      case Constants::WIKI_TAG_CONSTANT:
        return Constants::ByIdOrName( $id );

    	default:
        return null; 
    }
    
  }
}