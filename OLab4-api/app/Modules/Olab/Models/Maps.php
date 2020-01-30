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
<a href="MapNodes.php">MapNodes.php</a>
*
* @author Organisation: Cumming School of Medicine, University of Calgary
* @author Developer: Corey Wirun (corey@cardinalcreek.ca)
* @copyright Copyright 2017 University of Calgary. All Rights Reserved.
*/
namespace Entrada\Modules\Olab\Models;

use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use XMLReader;
use DOMDocument;
use \Exception;

/**
* @property integer $id
* @property string $name
* @property integer $author_id
* @property string $abstract
* @property integer $startScore
* @property integer $threshold
* @property string $keywords
* @property integer $type_id
* @property string $units
* @property integer $security_id
* @property string $guid
* @property string $timing
* @property integer $delta_time
* @property string $reminder_msg
* @property integer $reminder_time
* @property string $show_bar
* @property string $show_score
* @property integer $skin_id
* @property string $enabled
* @property integer $section_id
* @property integer $language_id
* @property string $feedback
* @property string $dev_notes
* @property string $source
* @property integer $source_id
* @property string $verification
* @property integer $assign_forum_id
* @property integer $author_rights
* @property string $revisable_answers
* @property string $send_xapi_statements
*/
class Maps extends BaseScopedObjectOwnerModel {

    const XML_FILE = "map.xml";
    const IMAGEABLE_TYPE = "Maps";

    protected $table = 'maps';
    protected $attributes = array(
      'name' => 'New Map',
      'abstract' => '<b>New Map</b>',
      'enabled' => 1,
      'skin_id' => 1,
      'security_id' => 3,
      'send_xapi_statements' => 0,
      'renderer_version' => 4,
      'type_id' => 11,
      'section_id' => 2,
      'is_template' => 0,
      'keywords' => '',
      'delta_time' => 0,
      'reminder_msg' => '',
      'reminder_time' => 0,
      'show_bar' => 0,
      'show_score' => 0,
      'language_id' => 1,
      'dev_notes' => '',
      'source_id' => 0,
      'verification' => '{}');

    protected $fillable = ['name','author_id','abstract','startScore','threshold','keywords',
                           'type_id','units','security_id','guid','timing','delta_time',
                           'reminder_msg','reminder_time','show_bar','show_score','skin_id',
                           'enabled','section_id','language_id','feedback','dev_notes','source',
                           'source_id','verification','assign_forum_id','author_rights',
                           'revisable_answers','send_xapi_statements', 'is_template'];
    protected $validations = ['name' => 'max:200|string',
                                'author_id' => 'integer|min:0|required',
                                'abstract' => 'max:2000|string',
                                'startScore' => 'integer|required',
                                'threshold' => 'integer|required',
                                'keywords' => 'max:500|string',
                                'type_id' => 'exists:map_types,id|integer|min:0|required',
                                'units' => 'max:10|string',
                                'security_id' => 'exists:map_securities,id|integer|min:0|required',
                                'guid' => 'max:50|string',
                                'timing' => 'integer|required',
                                'delta_time' => 'integer|required',
                                'reminder_msg' => 'max:255|string',
                                'reminder_time' => 'integer|required',
                                'show_bar' => 'integer|required',
                                'show_score' => 'integer|required',
                                'enabled' => 'integer|required',
                                'section_id' => 'exists:map_sections,id|integer|min:0|required',
                                'language_id' => 'exists:languages,id|integer|min:0',
                                'feedback' => 'max:2000|string',
                                'dev_notes' => 'max:1000|string',
                                'source' => 'max:50|string',
                                'source_id' => 'integer|required',
                                'verification' => 'string',
                                'assign_forum_id' => 'integer',
                                'author_rights' => 'integer|required',
                                'revisable_answers' => 'integer|required',
                                'renderer_version' => 'float|required',
                                'send_xapi_statements' => 'integer|required',
                                'is_template' => 'integer'];

    protected $post_to_db_column_translation = [ 

      'authorId' => 'author_id',
      'description' => 'abstract',
      'securityType' => 'security_id',
      'sendXapiStatements' => 'send_xapi_statements',
      'themeId' => 'skin_id',
      'notes' => 'dev_notes'

    ];

    public function toArray() {
      
      $aObj = parent::toArray();

      // fix deprecated security id 3 (Olab3)
      if ( $aObj['security_id'] == 3 ) {
        $aObj['security_id'] = 4;
      }

      OLabUtilities::safe_rename( $aObj, 'abstract', 'description');
      OLabUtilities::safe_rename( $aObj, 'dev_notes', 'notes');

      // unpackage validation fields into individual flag fields
      if ( $this->verification == null ) {
        $aVerification = array();
      }
      else {
        $aVerification = json_decode( $this->verification );
      }

      $aObj['linkLogicVerified']      = array_key_exists('linkLogicVerified',         $aVerification) ? $aVerification->linkLogicVerified       : 0;
      $aObj['nodeContentVerified']    = array_key_exists('nodeContentVerified',       $aVerification) ? $aVerification->nodeContentVerified     : 0;
      $aObj['mediaContentVerified']   = array_key_exists('mediaContentVerified',      $aVerification) ? $aVerification->mediaContentVerified    : 0;
      $aObj['mediaContentComplete']   = array_key_exists('mediaContentComplete',      $aVerification) ? $aVerification->mediaContentComplete    : 0;
      $aObj['mediaCopyrightVerified'] = array_key_exists('mediaCopyrightVerified',    $aVerification) ? $aVerification->mediaCopyrightVerified  : 0;
      $aObj['instructorGuideComplete'] = array_key_exists('instructorGuideComplete',  $aVerification) ? $aVerification->instructorGuideComplete : 0;

      OLabUtilities::safe_rename( $aObj, 'security_id', 'securityType');
      OLabUtilities::safe_rename( $aObj, 'send_xapi_statements', 'sendXapiStatements');
      OLabUtilities::safe_rename( $aObj, 'skin_id', 'themeId');
      OLabUtilities::safe_rename( $aObj, 'is_template', 'isTemplate');

      OLabUtilities::safe_rename( $aObj, 'MapNodes', 'nodes');
      OLabUtilities::safe_rename( $aObj, 'MapNodeLinks', 'links');
      OLabUtilities::safe_rename( $aObj, 'MapNodeJumps', 'jumps');

      OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_QUESTIONS, 'questions');
      OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_CONSTANTS, 'constants');
      OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_COUNTERS, 'counters');
      OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_FILES, 'files');
      OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_AVATARS, 'avatars');
      OLabUtilities::safe_rename( $aObj, ScopedObjectManager::RELATION_NAME_SCRIPTS, 'scripts');

      OLabUtilities::safe_rename( $aObj, 'author_id', 'author' );
      OLabUtilities::safe_rename( $aObj, 'dev_notes', 'notes' );
      OLabUtilities::safe_rename( $aObj, 'type_id');
      OLabUtilities::safe_rename( $aObj, 'author_rights');
      OLabUtilities::safe_rename( $aObj, 'assign_forum_id');
      OLabUtilities::safe_rename( $aObj, 'renderer_version');
      OLabUtilities::safe_rename( $aObj, 'revisable_answers');
      OLabUtilities::safe_rename( $aObj, 'verification');
      OLabUtilities::safe_rename( $aObj, 'startScore');
      OLabUtilities::safe_rename( $aObj, 'threshold');
      OLabUtilities::safe_rename( $aObj, 'units');
      OLabUtilities::safe_rename( $aObj, 'guid');
      OLabUtilities::safe_rename( $aObj, 'timing');
      OLabUtilities::safe_rename( $aObj, 'delta_time');
      OLabUtilities::safe_rename( $aObj, 'reminder_msg');
      OLabUtilities::safe_rename( $aObj, 'reminder_time');
      OLabUtilities::safe_rename( $aObj, 'show_bar');
      OLabUtilities::safe_rename( $aObj, 'show_score');
      OLabUtilities::safe_rename( $aObj, 'section_id');
      OLabUtilities::safe_rename( $aObj, 'language_id');
      OLabUtilities::safe_rename( $aObj, 'feedback');
      OLabUtilities::safe_rename( $aObj, 'source');
      OLabUtilities::safe_rename( $aObj, 'source_id');

      return $aObj;

    }

    /**
     * Return order list of active maps
     * @param mixed $query
     * @return mixed
     */
    public function scopeActiveIds($query) {
        return $query->where('enabled', '1' )
                     ->select('id')
                     ->orderBy('id');
    }

    public function scopeVersion4($query) {
        return $query->where('renderer_version', '>=', '4.0' );    
    }

    public function scopeIsMap($query) {
        return $query->where('is_template', '0' );          
    }

    /**
     * Query scope to retrieve only active (not locked) maps
     * @param mixed $query
     * @return mixed
     */
    public function scopeActive($query) {
        return $query->where('enabled', '1' )->IsMap()->version4();    
    }

    public function scopeAbbreviated($query) {
        return $query->select( 'maps.id', 'maps.name', 'maps.send_xapi_statements', 'maps.security_id');
    }

    public function scopeAt( $query, $id ) {
        $oData = $query->where( 'id', '=', $id )->first();
        return $oData;
    }

    public function scopeByVersion( $query, $version ) {
        return $query->where( 'renderer_version', '<=', $version );        
    }

    public function scopeByName( $query, $name ) {
        return $query->where( 'name', '=', $name );
    }

    public function scopeAvatars( $query )  {
        return $query->with( 'Avatars' );
    }

    public function scopeNodesAbbreviated( $query )  {
        return $query->with( [ 'MapNodes' => function( $query ) { $query->select('id', 'map_id', 'title'); } ] );
    }

    public function scopeJumps( $query )  {

        return $query->with( [ 'MapNodeJumps' => function( $query ) {
                                  $query->select( 'map_node_jumps.text',
                                                  'map_node_jumps.link_type_id',
                                                  'map_node_jumps.order',
                                                  'map_node_jumps.link_style_id');
                                } ] )
                     ->with( [ 'MapNodeJumps.DestinationNode' => function( $query ) {
                            $query->select( 'map_nodes.id',
                            'map_nodes.title',
                            'map_nodes.type_id' );
                        } ] );
    }

    public function scopeWithObjects( $query ) {

        $oData = $query->Constants()
                       ->Scripts()
                       ->Files()
                       ->Avatars()
                       ->Questions()
                       ->Jumps()
                       ->Themes()
                       ->Counters();
        return $oData;
    }

    public function delete() {
      
      // delete dependant objects, then delete node
      $aoNodes = $this->MapNodes()->get();
      foreach ($aoNodes as $oNode) {
      	$oNode->delete();
      }
      
      $res = parent::delete();

      return $res;
    }

    /**
     * Copy object to target object (not deep copy)
     * @param mixed $oSource 
     * @param mixed $oDestination 
     */
    protected static function copyFrom( $oSource, &$oDestination ) {
      
      $oDestination->name                    = OLabUtilities::safe_base64_decode((string)$oSource->name);
      $oDestination->author_id               = (int)$oSource->author_id;
      $oDestination->abstract                = OLabUtilities::safe_base64_decode((string)$oSource->abstract);
      $oDestination->startScore              = (int)$oSource->startScore;
      $oDestination->threshold               = (string)$oSource->threshold;
      $oDestination->keywords                = OLabUtilities::safe_base64_decode((string)$oSource->keywords);
      $oDestination->type_id                 = (int)$oSource->type_id;
      $oDestination->units                   = (string)$oSource->units;
      $oDestination->security_id             = (int)$oSource->security_id;
      $oDestination->guid                    = (string)$oSource->guid;
      $oDestination->timing                  = (string)$oSource->timing;
      $oDestination->delta_time              = (string)$oSource->delta_time;
      $oDestination->reminder_msg            = OLabUtilities::safe_base64_decode((string)$oSource->reminder_msg);
      $oDestination->reminder_time           = (string)$oSource->reminder_time;
      $oDestination->show_bar                = (string)$oSource->show_bar;
      $oDestination->show_score              = (int)$oSource->show_score;
      $oDestination->enabled                 = (int)$oSource->enabled;
      $oDestination->section_id              = (int)$oSource->section_id;
      $oDestination->language_id             = (int)$oSource->language_id;
      $oDestination->feedback                = (string)$oSource->feedback;
      $oDestination->dev_notes               = OLabUtilities::safe_base64_decode((string)$oSource->dev_notes);
      $oDestination->source                  = (string)$oSource->source;
      $oDestination->source_id               = (int)$oSource->source_id;
      $oDestination->verification            = OLabUtilities::safe_base64_decode((string)$oSource->verification);
      $oDestination->assign_forum_id         = (int)$oSource->assign_forum_id;
      $oDestination->author_rights           = (string)$oSource->author_rights;
      $oDestination->revisable_answers       = (string)$oSource->revisable_answers;
      $oDestination->renderer_version        = (string)$oSource->renderer_version;
      $oDestination->send_xapi_statements    = (int)$oSource->send_xapi_statements;
      $oDestination->is_template             = (int)$oSource->is_template;

    }

    public static function createFrom( $source ) {

      $instance = new self();        
      Maps::copyFrom( $source, $instance );
      $instance->is_template = 0;

      return $instance;
    }

    /**
     * Import from xml file
     * @param mixed $import_directory Base improt directory
     * @throws Exception 
     * @return Maps
     */
    public static function import( $import_directory ) {

        $instance = new self();
        $file_name = $import_directory . DIRECTORY_SEPARATOR . self::XML_FILE;

        if ( !file_exists( $file_name ))
            throw new Exception( "Cannot open import file: " . $file_name );

        $xmlReader = new XMLReader;
        $xmlReader->open( $file_name );
        $doc = new DOMDocument;

        // move to the first map node
        while ($xmlReader->read() && $xmlReader->name !== 'map');

        // now that we're at the right depth, hop to the next record until the end of the tree
        if ($xmlReader->name === 'map')
        {
            $source = simplexml_import_dom($doc->importNode($xmlReader->expand(), true));
            $instance = Maps::createFrom( $source );
        }

        return $instance;

    }

    /**
     * Get map root node
     * @return mixed
     */
    public function RootNode()
    {
        return $this->MapNodes()
        ->RootNode()
        ->Abbreviated()
        ->WithObjects()
        ->first();
    }

    /**
     * Get a map node
     * @param mixed $nodeId
     * @return mixed
     */
    public function NodeAt( $nodeId = null )
    {
        // Test if no node_id passed, meaning get root node
        if ( $nodeId == null ) {
            $node = $this->MapNodes()
                         ->WithObjects( )
                         ->RootNode()
                         ->first();

            // if no root node, get first node defined
            if ( $node == null ) {
              $node = $this->MapNodes()
                           ->WithObjects( )
                           ->first();
            }
        }
        else {
            $node = $this->MapNodes()
            ->WithObjects( )
            ->where('id', $nodeId )
            ->first();
        }
        return $node;
    }

    /**
     * Get map avatars
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function Avatars() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapAvatars', 'map_id');
    }

    /**
     * Get map chats
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapChats() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapChats');
    }

    /**
     * Get map collections
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapCollectionMaps() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapCollectionMaps');
    }

    /**
     * Get map contributors
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapContributors() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapContributors');
    }

    /**
     * Get map counter common rules
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapCounterCommonRules() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapCounterCommonRules');
    }

    /**
     * Get map counters
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    //public function MapCounters() {
    //    return $this->hasMany('Entrada\Modules\Olab\Models\MapCounters', 'map_id');
    //}
    /**
     * Get map dams
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapDams() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapDams');
    }

    /**
     * Get map elements
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function Elements() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapElements', 'map_id');
    }

    /**
     * Get map feedback rules
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapFeedbackRules() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapFeedbackRules');
    }

    /**
     * Get map keys
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapKeys() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapKeys');
    }

    /**
     * Get map mode links
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapNodeLinks() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapNodeLinks', 'map_id');
    }

    /**
     * Get map mode jumps
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapNodeJumps() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapNodeJumps', 'map_id');
    }

    /**
     * Get map nodes sections
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapNodeSections() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapNodeSections');
    }

    /**
     * Get specific map node
     * @param mixed $node_id Specified node id, or ROOT node if empty
     * @return mixed MapNode
     */
    public function MapNode( $nodeId ) {
        return $this->MapNodes()->At( $nodeId );
    }

    /**
     * Get map nodes
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapNodes() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapNodes', 'map_id');
    }

    public function MapUsers() {
        return $this->hasMany('Entrada\Modules\Olab\Models\MapUsers');
    }
    public function QCumulative() {
        return $this->hasMany('Entrada\Modules\Olab\Models\QCumulative');
    }
    public function ScenarioMaps() {
        return $this->hasMany('Entrada\Modules\Olab\Models\ScenarioMaps');
    }
    public function UserSessions() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserSessions');
    }
    public function UserSessionsCBref() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserSessionsCBref');
    }
    public function UserSessionsC3st() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserSessionsC3st');
    }
    public function UserSessionsExt() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserSessionsExt');
    }
    public function UserSessionsPart() {
        return $this->hasMany('Entrada\Modules\Olab\Models\UserSessionsPart');
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
    public function MapSecurities() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapSecurities');
    }
    public function MapTypes() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapTypes');
    }
    public function MapSkins() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapSkins');
    }
    public function MapSections() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\MapSections');
    }
    public function Languages() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Languages');
    }

    public function IsxAPIEnabled() {      
        
        return ( ( $this->send_xapi_statements == 1 ) &&
                 ( Lrs::isLRSEnabled() ) );

    }

    /**
     * Override of delete to force a cascade delete of
     * a polymorphic relation into questions
     */
    //public function delete() {
        
    //    $this->Questions()->delete();
    //    parent::delete();
    //}
}