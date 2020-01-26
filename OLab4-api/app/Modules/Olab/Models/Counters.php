<?php
/**
 * OLab [ http://www.openlabyrith.ca ]
 *
 * OLab is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OLab is distributed in the hope that it will be useful,
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

use \Exception;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Models\PolymorphicModel;
use XMLReader;
use DOMDocument;
use Illuminate\Support\Facades\Log;

/**
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $start_value
 * @property integer $icon_id
 * @property string $prefix
 * @property string $suffix
 * @property string $visible
 * @property integer $out_of
 * @property integer $status
 * @property integer $imageable_id
 * @property string $imageable_type
 */

class Counters extends PolymorphicModel {

  const XML_FILE = "map_counter.xml";
  const XML_ROOT_ELEMENT = "map_counter_";
  const WIKI_TAG_COUNTER = "CR";

  const SYSTEM_NODE_COUNTER_NAME = "nodeCounter";

  protected $table = 'system_counters';
  protected $fillable = ['name','description','start_value','icon_id','prefix',
                         'suffix','visible','out_of','status','is_system', 
                         'imageable_id','imageable_type'];

  protected $validations = ['name' => 'max:200|string',
                          'description' => 'string',
                          'start_value' => 'required',
                          'is_system' => 'integer',
                          'icon_id' => 'integer',
                          'visible' => 'integer',
                          'out_of' => 'integer',
                          'status' => 'integer|required',
                          'imageable_id' => 'integer|required',
                          'imageable_type' => 'max:45|string'];

  protected $attributes = array(
                          'is_system' => 0,
                          'visible' => 1,
                          'id' => null
                          );

  protected $post_to_db_column_translation = [ 

    // alias => raw
    'startValue' => 'start_value',
    'scopeLevel' => 'imageable_type',
    'parentId' => 'imageable_id',
    'isSystem' => 'is_system'

  ];

  public static function toString( $aCounter ) {
  
    $string = "";
    $string = "'" . $aCounter['name'] . "'(" . $aCounter['id'] . ") = " 
                  . $aCounter['value'] . " @ " . $aCounter['scopeLevel'] 
                  . "(" . $aCounter['parentId'] . ")";
    return $string;
  }

  public function toArray() {
    
    $aObj = parent::toArray();

    OLabUtilities::safe_rename( $aObj, 'is_system', 'isSystem' );
    OLabUtilities::safe_rename( $aObj, 'start_value', 'startValue');
    OLabUtilities::safe_rename( $aObj, 'icon_id' );
    OLabUtilities::safe_rename( $aObj, 'prefix' );
    OLabUtilities::safe_rename( $aObj, 'suffix' );
    OLabUtilities::safe_rename( $aObj, 'out_of' );

    OLabUtilities::safe_rename( $aObj, 'imageable_id', "parentId" );
    OLabUtilities::safe_rename( $aObj, 'imageable_type', 'scopeLevel' );

    return $aObj;

  }

  public function scopeByMapId( $query, $map_id ) {

    return $query->where([ ['imageable_id', '=', $map_id ],
                           ['imageable_type', '=', Maps::IMAGEABLE_TYPE ]]);
  }

  public function scopeAbbreviated( $query ) {
    return $query->select( 'system_counters.id',
                           'system_counters.name',
                           'system_counters.visible',
                           'system_counters.start_value' );
  }

  public function scopeAtByName( $query, $name ) {
    return $query->where( 'name', '=', $name )
                 ->first();
  }
  public function scopeMapMainCounter( $query, $map_id ) {
    return $query->where( [['imageable_id', '=', $map_id ],
                           ['status', '=', 1 ],
                           ['imageable_type', '=', Maps::IMAGEABLE_TYPE ]]);
  }

  /**
   * Import from xml file
   * @param mixed $import_directory Base import directory
   * @throws Exception 
   * @return Maps
   */
  public static function import( $import_directory ) {

    $items = array();

    $file_name = $import_directory . DIRECTORY_SEPARATOR . self::XML_FILE;

    // file is optional
    if ( !file_exists( $file_name ))
      return $items;

    $xmlReader = new XMLReader;
    $xmlReader->open( $file_name );
    $doc = new DOMDocument;

    // build element to look for
    $index = 0;
    $current_root_name = self::XML_ROOT_ELEMENT . $index;

    // move to the first record
    while ($xmlReader->read() && $xmlReader->name !== $current_root_name );

    // now that we're at the right depth, hop to the next record until the end of the tree
    while ( $xmlReader->name === $current_root_name )
    {
      // either one should work
      $node = simplexml_import_dom($doc->importNode($xmlReader->expand(), true));

      $instance = new self();

      $instance->id             = (int)$node->id;
      $instance->name           = base64_decode($node->name);
      $instance->description    = $node->description;
      $instance->start_value    = (int)$node->start_value;
      $instance->icon_id        = (int)$node->icon_id;
      $instance->prefix         = $node->prefix;
      $instance->suffix         = $node->suffix;
      $instance->visible        = (int)$node->visible;
      $instance->out_of         = (int)$node->out_of;
      $instance->status         = (int)$node->status;
      $instance->imageable_id   = $node->imageable_id;
      $instance->imageable_type = "Maps";
      $instance->is_system      = 0;

      array_push( $items, $instance );

      // update element to next record in sequence
      $index++;
      $current_root_name = self::XML_ROOT_ELEMENT . $index;
      $xmlReader->next( $current_root_name );
    }

    return $items;

  }
}