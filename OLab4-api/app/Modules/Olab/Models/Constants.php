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
use Entrada\Modules\Olab\Models\Constants;

/**
 * @property integer $id
 * @property string $name
 * @property integer $scope_type_id
 * @property integer $owning_id
 * @property string $value
 */

class Constants extends PolymorphicModel {

  const OWNEDTYPE_NODE = 'Entrada\Modules\Olab\Models\MapNodes';
  const OWNEDTYPE_MAP = 'Entrada\Modules\Olab\Models\Maps';
  const WIKI_TAG_CONSTANT = "CONST";

  const RESERVED_CONST_TIME = "SystemTime";
  const RESERVED_CONST_MAPID = "MapId";
  const RESERVED_CONST_NODEID = "NodeId";
  const RESERVED_CONST_MAPNAME = "MapName";
  const RESERVED_CONST_NODENAME = "NodeName";

  protected $table = 'system_constants';
  protected $fillable = ['name','description', 'imageable_id','imageable_type','value', 'is_system'];
  protected $validations = ['name' => 'max:200|string',
                          'description' => 'string',
                          'is_system' => 'integer',
                          'imageable_id' => 'integer|required',
                          'imageable_type' => 'max:45|string',
                          'value' => ''];

  protected $attributes = array(
                          'is_system' => 0,
                          'id' => null
                          );

  protected $post_to_db_column_translation = [ 

    // alias => raw
    'isSystem' => 'is_system',
    'scopeLevel' => 'imageable_type',
    'parentId' => 'imageable_id'

  ];

  public function toArray() {
    
    $aObj = parent::toArray();

    OLabUtilities::safe_rename( $aObj, 'is_system', 'isSystem' );
    OLabUtilities::safe_rename( $aObj, 'imageable_id', "parentId" );
    OLabUtilities::safe_rename( $aObj, 'imageable_type', 'scopeLevel' );

    return $aObj;

  }

  /**
   * Create object from legacy source object
   * @param mixed $nParentId 
   * @param mixed $oSourceObj legacy record record
   * @return Constants|null new Constant, or null is source not of expected type
   */
  public static function Create( $nParentId, $oSourceObj ) {

    // get class name to ensure the source is supported
    $sClassName = get_class( $oSourceObj );
    $parts = explode('\\', $sClassName );
    $sClassName = array_pop( $parts );

    // we can only create an object from MapQuestions,
    // return if it's not what we expect
    if ( $sClassName != "MapVpdElements")
      throw new Exception("Unknown source type '" . $sClassName . ".");

    $instance = new self();

    $instance->imageable_id       = $nParentId                     ;
    $instance->imageable_type     = "Nodes"                        ;
    $instance->value              = $oSourceObj->value             ;
    $instance->is_system          = 0;

    return $instance;
  }

}