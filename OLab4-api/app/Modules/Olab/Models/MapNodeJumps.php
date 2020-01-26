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

use \Exception;
use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use XMLReader;
use DOMDocument;
use Illuminate\Support\Facades\Log;

/**
 * @property integer $id
 * @property integer $map_id
 * @property integer $node_id
 * @property integer $image_id
 * @property string $text
 * @property integer $order
 * @property integer $probability
 * @property string $hidden
 */

class MapNodeJumps extends BaseModel {

    const XML_FILE = "map_node_jumps.xml";
    const XML_ROOT_ELEMENT = "map_node_jumps_";
    const DEFAULT_COLOR = "#566e94";
    const DEFAULT_THICKNESS = 2;
    const DEFAULT_LINETYPE = 1;

    protected $table = 'map_node_jumps';
    protected $fillable = ['map_id','node_id','image_id','text',
                           'order','probability','hidden','thickness', 'color', 
                           'line_type', 'link_style_id', 'follow_once' ];
    protected $attributes = array(
      'text' => '',
      'hidden' => 0,
      'order' => 0,
      'probability' => 0,
      'link_style_id' => 5,
      'image_id' => 0, 
      'line_type' => self::DEFAULT_LINETYPE,
      'thickness' => self::DEFAULT_THICKNESS,
      'color' => self::DEFAULT_COLOR,
      'follow_once' => 0
    );

    protected $validations = ['map_id' => 'exists:maps,id|integer|min:0|required',
                              'node_id' => 'exists:map_nodes,id|integer|min:0|required',
                              'image_id' => 'integer',
                              'text' => 'max:500|string',
                              'color' => 'max:20|string',
                              'order' => 'integer',
                              'probability' => 'integer',
                              'hidden' => 'integer',
                              'line_type' => 'integer',
                              'thickness' => 'integer' ];


    protected $post_to_db_column_translation = [ 

      'linkStyleId' => 'link_style_id',
      'imageId' => 'image_id',
      'lineType'  => 'line_type',
      'destinationId' => 'node_id',
      'mapId' => 'map_id',
      'followOnce' => 'follow_once'
    ];

    public function toArray($raw = false)
    {
      $aObj = parent::toArray();

      OLabUtilities::safe_rename( $aObj, 'link_style_id', 'linkStyleId' );

      OLabUtilities::safe_rename( $aObj, 'image_id', 'imageId' );
      OLabUtilities::safe_rename( $aObj, 'line_type', 'lineType' );

      OLabUtilities::safe_rename( $aObj, 'node_id', 'destinationId' );
      OLabUtilities::safe_rename( $aObj, 'map_id', 'mapId' );
      OLabUtilities::safe_rename( $aObj, 'follow_once', 'followOnce' );

      return $aObj;
    }

    public function scopeByMap( $query, $map_id ) {
      return $query->where( 'map_id', '=', $map_id );
    }

    public function scopeAt( $query, $id ) {
        return $query->where( 'id', '=', $id )
                     ->first();
    }

    public function Maps() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Maps');
    }

    public function MapNodes() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapNodes');
    }

    public function DestinationNode() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapNodes', 'node_id' );
    }

    public static function createFrom( $source ) {

      $instance = new self();

      $instance->id                 = (int)$source->id           ;
      $instance->map_id             = (int)$source->map_id       ;
      $instance->node_id            = (int)$source->node_id      ;
      $instance->image_id           = (int)$source->image_id     ;
      $instance->text               = $source->text              ;
      $instance->order              = (int)$source->order        ;
      $instance->probability        = (int)$source->probability  ;
      $instance->hidden             = (int)$source->hidden       ;
      $instance->line_type          = self::DEFAULT_LINETYPE;
      $instance->thickness          = self::DEFAULT_THICKNESS;
      $instance->color              = self::DEFAULT_COLOR;
      $instance->follow_once        = (int)$source->follow_once  ;
      $instance->link_style_id      = (int)$source->link_style_id  ;

      return $instance;
    }

}