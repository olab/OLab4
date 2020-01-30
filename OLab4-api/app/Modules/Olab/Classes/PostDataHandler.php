<?php
/**
 * OpenLabyrinth [ http://www.openlabyrinth.ca ]
 *
 * OpenLabyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenLabyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenLabyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A class to manage scoped objects from the database
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Classes;

use Auth;
use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Entrada\Modules\Olab\Models\MapNodeTypes;
use Entrada\Modules\Olab\Models\QuestionTypes;
use Entrada\Modules\Olab\Models\Constants;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\Autoload\MimeTypes\OlabMimeTypeBase;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\CustomAssetManager;

class PostDataHandler
{
  private $oRequest;
  private $aValues;
  private $aValue = null;

  const NOT_FOUND = PHP_INT_MIN;

  function __construct( Request $request ) {

    $this->oRequest = $request;

    if ( $this->oRequest->has( 'data' ) ) {
      $this->aValues = $request->all()['data'];
    }
    else {
      $this->aValues = array();
    }

    $this->aValue = $this->aValues;
  }

  private function get_value( $alias_name, $throw = true ) {
    
    $value = self::NOT_FOUND;
    $key_exists = $this->key_exists( $alias_name, $throw );

    // test if post key/value exists
    if ( $key_exists ) {
      $value = $this->aValue[ $alias_name ];
    }

    return $value;

  }

  private function key_exists( $alias_name, $throw = true ) {

    // test if key exists
    if ( !array_key_exists( $alias_name, $this->aValue ) ) {

      if ( $throw ) {
        throw new Exception("$alias_name is not defined.");
      }

      return false;
    }

    return true;
  }

  public function count() {
    return sizeof( $this->aValue );
  }

  public function get_at( $index ) {

    $this->aValue = $this->aValues[ $index ];
  }

  public function get_color( &$target_field, $alias_name, $throw = true ) {

    // test if key exists. throw if not
    if ( $this->key_exists( $alias_name, $throw ) ) {

      $value = $this->aValue[$alias_name];

      if (preg_match('/^#[a-f0-9]{6}$/i', $value)) {

        $target_field = $value;
        return $value;
      }
      
      if ( $throw ) {
        throw new Exception("$alias_name is not a valid color.");
      }

    }

    $target_field = self::NOT_FOUND;
    return self::NOT_FOUND;

  }

  public function get_color_optional( &$target, $alias_name ) {

    $value = $this->get_value( $alias_name, false );
    if ( $value == self::NOT_FOUND ) {
      return $value;
    }

    if (!preg_match('/^#[a-f0-9]{6}$/i', $value)) {
      throw new Exception("$alias_name is not a valid color.");
    }

    // test if object is a BaseModel derivation, meaning we may
    // need to translate the POST field name to a physical one
    // ONLY WORKS when $target is a direct derivation of BaseModel
    $is_model_object = OLabUtilities::is_of_type( $target , "BaseModel" );
    
    if ( $is_model_object ) {

      $alias_name = $target->GetFieldTranslation($alias_name);
      $target->$alias_name = $value;        
    }
    else {
      $target = $value;
    }

    return $value;
  }

  public function get_integer_optional( &$target, $alias_name, $default = self::NOT_FOUND ) {

    // test if object is a BaseModel derivation, meaning we may
    // need to translate the POST field name to a physical one
    // ONLY WORKS when $target is a direct derivation of BaseModel
    $is_model_object = OLabUtilities::is_of_type( $target , "BaseModel" );

    $value = $this->get_value( $alias_name, false );
    if ( $value == self::NOT_FOUND ) {

      if ( $default != self::NOT_FOUND ) {
        $value = $default;
      }
      else {
        return $value;
      }
    }

    $value = (int)$value;

    if ( $is_model_object ) {

      $alias_name = $target->GetFieldTranslation($alias_name);
      $target->$alias_name = $value;        
    }
    else {
      if ( is_array($target) ) {
        $target[$alias_name] = $value;
      } else {
        $target = $value;
      }
    }

    return $value;

  }

  public function get_text_optional( &$target, $alias_name ) {

    $value = $this->get_value( $alias_name, false );

    if ( $value == self::NOT_FOUND ) {
      return $value;
    }

    // test if object is a BaseModel derivation, meaning we may
    // need to translate the POST field name to a physical one
    // ONLY WORKS when $target is a direct derivation of BaseModel
    $is_model_object = OLabUtilities::is_of_type( $target , "BaseModel" );
    
    if ( $is_model_object ) {

      $alias_name = $target->GetFieldTranslation($alias_name);

      $target->$alias_name = $value;        
    }
    else {
      $target = $value;
    }

    return $value;

  }

  public function get_text( &$target, $alias_name ) {

    $value = $this->get_value( $alias_name, true );

    // test if object is a BaseModel derivation, meaning we may
    // need to translate the POST field name to a physical one
    // ONLY WORKS when $target is a direct derivation of BaseModel
    $is_model_object = OLabUtilities::is_of_type( $target , "BaseModel" );

    if ( $is_model_object ) {

      $alias_name = $target->GetFieldTranslation($alias_name);
      $target->$alias_name = $value;        
    }
    else {
      $target = $value;
    }

    return $value;

  }

  public function get_integer( &$target, $alias_name ) {

    $value = $this->get_value( $alias_name, true );

    // test if object is a BaseModel derivation, meaning we may
    // need to translate the POST field name to a physical one
    // ONLY WORKS when $target is a direct derivation of BaseModel
    $is_model_object = OLabUtilities::is_of_type( $target , "BaseModel" );

    if ( is_int( (int)$value ) ) {

      if ( $is_model_object ) {

        $alias_name = $target->GetFieldTranslation($alias_name);
        $target->$alias_name = (int)$value;        
      }
      else {
        $target = (int)$value;
      }

    }
    else {
      throw new Exception("$alias_name is not a valid integer.");
    }

    return (int)$value;

  }

}