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
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Illuminate\Support\Facades\DB;
use XMLReader;
use DOMDocument;
use Illuminate\Support\Facades\Log;

/**
 * @property integer $id
 * @property integer $map_id
 * @property string $title
 * @property string $text
 * @property integer $type_id
 * @property string $probability
 * @property string $conditional
 * @property string $conditional_message
 * @property string $info
 * @property integer $is_private
 * @property integer $link_style_id
 * @property integer $link_type_id
 * @property integer $priority_id
 * @property string $kfp
 * @property string $undo
 * @property string $end
 * @property string $x
 * @property string $y
 * @property string $rgb
 * @property string $show_info
 * @property string $annotation
 */

class MapNodes extends BaseScopedObjectOwnerModel {

    const XML_FILE = "map_node.xml";
    const XML_ROOT_ELEMENT = "map_node_";
    const IMAGEABLE_TYPE = "Nodes";
    const DEFAULT_COLOR = "#566e94";

    const DEFAULT_HEIGHT = 160;
    const DEFAULT_WIDTH = 300;

    protected $table = 'map_nodes';
    protected $attributes = array(
      'title' => 'New Node',
      'text' => "<b>New node text</b>",
      'type_id' => 2,
      'x' => 0,
      'y' => 0,
      'locked' => 0, 
      'collapsed' => 0, 
      'height' => self::DEFAULT_HEIGHT,
      'width' => self::DEFAULT_WIDTH,
      'rgb' => self::DEFAULT_COLOR,
      'probability' => 0,
      'conditional' => '',
      'conditional_message' => '',
      'info' => '',
      'is_private' => 0,
      'link_style_id' => 5,
      'link_type_id' => 1,
      'priority_id' => 1,
      'kfp' => 0,
      'undo' => 0,
      'end' => 0,
      'show_info' => 0,
      'annotation' => '',
      'visit_once' => 0
    );

    protected $fillable = ['map_id','title','text','type_id','probability','conditional',
                           'conditional_message','info','is_private','link_style_id',
                           'link_type_id','priority_id','kfp','undo','end','x','y',
                           'rgb','show_info','annotation', 'height', 'width', 'locked',
                           'collapsed', 'visit_once', 'force_reload'];
    protected $validations = [ 'map_id' => 'exists:maps,id|integer|min:0|required',
                            'title' => 'max:200|string',
                            'text' => 'string',
                            'type_id' => 'integer',
                            'probability' => 'integer',
                            'conditional' => 'max:500|string',
                            'conditional_message' => 'max:1000|string',
                            'info' => 'string',
                            'is_private' => 'integer|required',
                            'link_style_id' => 'exists:map_node_link_stylies,id|integer|min:0',
                            'link_type_id' => 'integer',
                            'priority_id' => 'integer',
                            'kfp' => 'integer',
                            'undo' => 'integer',
                            'end' => 'integer',
                            'height' => 'integer',
                            'width' => 'integer',
                            'locked' => 'integer',
                            'collapsed' => 'integer',
                            'x' => '',
                            'y' => '',
                            'rgb' => 'max:8|string',
                            'show_info' => 'integer|required',
                            'annotation' => 'string'];

    protected $post_to_db_column_translation = [ 
      // POST name => physical DB column name
      'linkStyleId' => 'link_style_id',
      'linkTypeId' => 'link_type_id',
      'typeId' => 'type_id',
      'isPrivate' => 'is_private',
      'color' => 'rgb',
      'visitOnce' => 'visit_once',
      'isEnd' => 'end',
      'forceReload' => "force_reload",
      'priorityId' => "priority_id"
    ];

    public function toArray($raw = false)
    {
      $aObj = parent::toArray();

      OLabUtilities::safe_rename( $aObj, 'link_style_id', 'linkStyleId' );
      OLabUtilities::safe_rename( $aObj, 'link_type_id', 'linkTypeId' );

      OLabUtilities::safe_rename( $aObj, 'type_id', 'typeId' );
      OLabUtilities::safe_rename( $aObj, 'is_private', 'isPrivate' );
      OLabUtilities::safe_rename( $aObj, 'rgb', 'color' );
      OLabUtilities::safe_rename( $aObj, 'map_id', 'mapId' );

      OLabUtilities::safe_rename( $aObj, 'visit_once', 'visitOnce' );
      OLabUtilities::safe_rename( $aObj, 'Avatars', 'avatars');

      OLabUtilities::safe_rename( $aObj, 'priority_id', 'priorityId' );
      OLabUtilities::safe_rename( $aObj, 'conditional');
      OLabUtilities::safe_rename( $aObj, 'show_info');
      OLabUtilities::safe_rename( $aObj, 'conditional_message');

      OLabUtilities::safe_rename( $aObj, 'force_reload', 'forceReload' );
      OLabUtilities::safe_rename( $aObj, 'kfp' );
      OLabUtilities::safe_rename( $aObj, 'undo' );
      OLabUtilities::safe_rename( $aObj, 'mapId' );

      OLabUtilities::safe_rename( $aObj, 'end', 'isEnd' );

      return $aObj;
    }

    // these rename DB column 'rgb' to 'color', which is exposed to outside world.
    public function getColorAttribute() {
      return $this->rgb;
    }

    public function setColorAttribute( $value ) {
      $this->attributes['rgb'] = $value;
    }

    public function scopeAbbreviated( $query ) {
        return $query->select( 'map_nodes.id',
                               'map_nodes.title',
                               'map_nodes.map_id',
                               'map_nodes.text',
                               'map_nodes.type_id' );
    }

    /**
     * At scope (should be called last in query chain)
     * @param mixed $query
     * @return mixed
     */
    public function scopeAt( $query, $id ) {
        return $query->where( 'id', '=', $id )
                     ->first();
    }

    public function scopeByMapId( $query, $map_id )  {
      return $query->select('id')
                   ->where( 'map_id', '=', $map_id );
    }

    public function scopeRootNodeId( $query )  {
        return $query->where( 'type_id', '=', MapNodeTypes::RootTypeId() );
    }

    public function scopeNotes( $query )  {
        return $query->with('Notes');
    }

    public function scopeLinks( $query )  {

        return $query->with( [ 'MapNodeLinks' => function( $query ) {
                                  $query->select( 'map_node_links.text',
                                                  'map_node_links.link_type_id',
                                                  'map_node_links.order',
                                                  'map_node_links.link_style_id');
                                } ] )
                     ->with( [ 'MapNodeLinks.SourceNode' => function( $query ) {
                            $query->select( 'map_nodes.id',
                                            'map_nodes.title',
                                            'map_nodes.type_id' );
                            } ] )
                     ->with( [ 'MapNodeLinks.DestinationNode' => function( $query ) {
                            $query->select( 'map_nodes.id',
                            'map_nodes.title',
                            'map_nodes.type_id' );
                        } ] );
    }

    /**
     * RootNode scope (should be called last in query chain)
     * @param mixed $query
     * @return mixed
     */
    public function scopeRootNode( $query ) {
        return $query->where('type_id', MapNodeTypes::RootTypeId() );

    }

    public function scopeWithObjects( $query ) {

        return $query->links()
                     ->questions()
                     ->constants()
                     ->scripts()
                     ->counters()
                     ->counterActions()
                     ->notes()
                     ->files();
    }

    public static function createFrom( $source ) {
      
      $instance = new self();

      $instance->map_id               = (int)$source->map_id;
      $instance->id                   = (int)$source->id;
      $instance->title                = OLabUtilities::safe_base64_decode((string)$source->title);
      $instance->text                 = OLabUtilities::safe_base64_decode((string)$source->text);
      $instance->type_id              = (int)$source->type_id;
      $instance->probability          = (int)$source->probability;
      $instance->conditional          = (string)$source->conditional;
      $instance->conditional_message  = (string)$source->conditional_message;
      $instance->info                 = OLabUtilities::safe_base64_decode((string)$source->info);
      $instance->is_private           = (int)$source->is_private;
      $instance->link_style_id        = (int)$source->link_style_id;
      $instance->link_type_id         = (int)$source->link_type_id;
      $instance->priority_id          = (int)$source->priority_id;
      $instance->kfp                  = (int)$source->kfp;
      $instance->undo                 = (int)$source->undo;
      $instance->end                  = (int)$source->end;
      $instance->x                    = (int)$source->x;
      $instance->y                    = (int)$source->y;
      $instance->rgb                  = OLabUtilities::safe_base64_decode((string)$source->rgb);
      $instance->show_info            = (int)$source->show_info;
      $instance->annotation           = OLabUtilities::safe_base64_decode((string)$source->annotation);
      $instance->color                = self::DEFAULT_COLOR;
      $instance->locked               = (int)$source->locked;
      $instance->collapsed            = (int)$source->collapsed;
      $instance->height               = isset($source->height) ? (int)$source->height : self::DEFAULT_HEIGHT;
      $instance->width                = isset($source->width) ? (int)$source->width : self::DEFAULT_WIDTH;
      $instance->visit_once           = (int)$source->visit_once;

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

        $xmlText = file_get_contents( $file_name );
        $oXml = simplexml_load_string( $xmlText );

        // build element to look for
        $index = 0;
        $current_root_name = self::XML_ROOT_ELEMENT . $index;

        // move to the first record
        while ($xmlReader->read() && $xmlReader->name !== $current_root_name );

        // now that we're at the right depth, hop to the next record until the end of the tree
        while ( $xmlReader->name === $current_root_name )
        {
            // either one should work
            $source = simplexml_import_dom($doc->importNode($xmlReader->expand(), true));

            $instance = MapNodes::createFrom( $source );
            array_push( $items, $instance );

            // update element to next record in sequence
            $index++;
            $current_root_name = self::XML_ROOT_ELEMENT . $index;
            $xmlReader->next( $current_root_name );
        }

        return $items;

    }

    // helper for determining if node is a root node
    public function IsRootNode() {
        return $this->type_id == MapNodeTypes::RootTypeId();
    }

    // helper for determining if node is an exit node
    public function IsExitNode() {
        return $this->end == 1;
    }

    public function ConditionsChange() {
        return $this->hasMany('Entrada\Modules\Olab\Models\ConditionsChange');
    }

    /**
     * Gets the counters for a map node
     * @return MapCounters
     */
    //public function NodeCounters() {
    //    return $this->belongsToMany('Entrada\Modules\Olab\Models\MapCounters', 'map_node_counters', 'node_id', 'counter_id' );
    //}

    public function delete() {  

      // delete dependant objects, then delete node
      $this->MapNodeLinks()->delete();
      $this->Questions()->delete();
      $this->Counters()->delete();
      $this->Files()->delete();
      $this->Scripts()->delete();
      $this->Constants()->delete();

      $res = parent::delete();

      return $res;
    }

    /**
     * Get node annotations
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function Notes() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapNodeNotes', 'map_node_id' );
    }

    public function MapNodeLinks() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapNodeLinks', 'node_id_1' )
                    ->orderBy( 'order', 'asc' );
    }

    public function MapNodeSectionNodes() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapNodeSectionNodes');
    }
    public function PatientConditionChange() {
        return $this->hasMany('Entrada\Modules\Olab\Models\PatientConditionChange');
    }
    public function UserBookmarks() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserBookmarks');
    }
    public function UserSessiontraces() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserSessiontraces');
    }
    public function UserSessiontracesCBRef() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserSessiontracesCBRef');
    }
    public function UserSessiontracesC3st() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserSessiontracesC3st');
    }
    public function UserSessiontracesExt() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserSessiontracesExt');
    }
    public function UserSessiontracesPart() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserSessiontracesPart');
    }
    public function WebinarNodePoll() {
        return $this->hasMany('Entrada\Modules\Olab\Models\WebinarNodePoll');
    }
    public function WebinarPoll() {
        return $this->hasMany('Entrada\Modules\Olab\Models\WebinarPoll');
    }
    public function Map() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Maps');
    }
    public function MapNodeLinkStylies() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapNodeLinkStylies');
    }

}