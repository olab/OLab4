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
 * @property integer $node_id_1
 * @property integer $node_id_2
 * @property integer $image_id
 * @property string $text
 * @property integer $order
 * @property integer $probability
 * @property string $hidden
 */

class MapNodeLinks extends BaseModel {

    const XML_FILE = "map_node_link.xml";
    const XML_ROOT_ELEMENT = "map_node_link_";
    const DEFAULT_COLOR = "#566e94";
    const DEFAULT_THICKNESS = 2;
    const DEFAULT_LINETYPE = 1;

    protected $table = 'map_node_links';
    protected $fillable = ['map_id','node_id_1','node_id_2','image_id','text',
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
                              'node_id_1' => 'exists:map_nodes,id|integer|min:0|required',
                              'node_id_2' => 'exists:map_nodes,id|integer|min:0|required',
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
      'sourceId'  => 'node_id_1',
      'destinationId' => 'node_id_2',
      'mapId' => 'map_id',
      'followOnce' => 'follow_once'
    ];

    public function toArray($raw = false)
    {
      $aObj = parent::toArray();

      OLabUtilities::safe_rename( $aObj, 'link_style_id', 'linkStyleId' );

      OLabUtilities::safe_rename( $aObj, 'image_id' );
      OLabUtilities::safe_rename( $aObj, 'line_type', 'lineType' );

      OLabUtilities::safe_rename( $aObj, 'node_id_1', 'sourceId' );
      OLabUtilities::safe_rename( $aObj, 'node_id_2', 'destinationId' );
      OLabUtilities::safe_rename( $aObj, 'map_id' );
      OLabUtilities::safe_rename( $aObj, 'follow_once', 'followOnce' );

      OLabUtilities::safe_rename( $aObj, "created_at",    'createdAt');
      OLabUtilities::safe_rename( $aObj, "updated_At",    'updatedAt');
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

    public function SourceNode() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapNodes', 'node_id_1' );
    }

    public function DestinationNode() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapNodes', 'node_id_2' );
    }

    /**
     * Create object from legacy source object
     * @param mixed $nParentId 
     * @param mixed $oSourceObj map_node record
     * @return Questions|null new Question, or null is source not of expected type
     */
    public static function Create( $nParentId, $oSourceObj ) {

        // get class name to ensure the source is supported
        $sClassName = get_class( $oSourceObj );
        $parts = explode('\\', $sClassName );
        $sClassName = array_pop( $parts );

        // we can only create an object from MapQuestions,
        // return if it's not what we expect
        if ( $sClassName != "MapNodeLinks")
            throw new Exception("Unknown source type '" . $sClassName . ".");

        $instance = new self();

        $instance->map_id             = $nParentId                     ;
        $instance->node_id_1          = (int)$oSourceObj->node_id_1    ;
        $instance->node_id_2          = (int)$oSourceObj->node_id_2    ;
        $instance->image_id           = (int)$oSourceObj->image_id     ;
        $instance->text               = $oSourceObj->text              ;
        $instance->order              = $oSourceObj->order             ;
        $instance->probability        = (int)$oSourceObj->probability  ;
        $instance->hidden             = (int)$oSourceObj->hidden       ;
        $instance->color              = self::DEFAULT_COLOR;
        $instance->line_type          = self::DEFAULT_LINETYPE;
        $instance->thickness          = self::DEFAULT_THICKNESS;

        return $instance;
    }

    public static function createFrom( $source ) {

      $instance = new self();

      $instance->id                 = (int)$source->id           ;
      $instance->map_id             = (int)$source->map_id       ;
      $instance->node_id_1          = (int)$source->node_id_1    ;
      $instance->node_id_2          = (int)$source->node_id_2    ;
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

   /**
     * Import from xml file
     * @param mixed $import_directory Base import directory
     * @throws Exception 
     * @return Maps
     */
    public static function import( $import_directory ) {

        $items = array();

        $file_name = $import_directory . DIRECTORY_SEPARATOR . self::XML_FILE;

        if ( !file_exists( $file_name ))
            throw new Exception( "Cannot open import file: " . $file_name );

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

            $instance = MapNodeLinks::createFrom( $node );
            array_push( $items, $instance );

            // update element to next record in sequence
            $index++;
            $current_root_name = self::XML_ROOT_ELEMENT . $index;
            $xmlReader->next( $current_root_name );
        }

        return $items;

    }


}