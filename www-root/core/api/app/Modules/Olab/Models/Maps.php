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

namespace App\Modules\Olab\Models;
use App\Modules\Olab\Models\BaseModel;

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

class Maps extends BaseModel {

    protected $table = 'maps';
    protected $fillable = ['name','author_id','abstract','startScore','threshold','keywords','type_id','units','security_id','guid','timing','delta_time','reminder_msg','reminder_time','show_bar','show_score','skin_id','enabled','section_id','language_id','feedback','dev_notes','source','source_id','verification','assign_forum_id','author_rights','revisable_answers','send_xapi_statements'];
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
                            'skin_id' => 'exists:map_skins,id|integer|min:0|required',
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
                            'send_xapi_statements' => 'integer|required'];

    /**
     * Query scope to retrieve only active (not locked) maps
     * @param mixed $query
     * @return mixed
     */
    public function scopeActive($query) {
        return $query->where( 'security_id', '<>', '2' )->where('enabled', '1' );
    }

    public function scopeAbbreviated($query) {
        return $query->select( 'maps.id', 'maps.name' );
    }

    public function scopeAt( $query, $id ) {
        return $query->where( 'id', '=', $id )
                     ->first();
    }

    public function scopeConstants( $query )  {
        return $query->with( [ 'Constants' => function ( $query ) {
            $query->select( 'system_constants.id', 'system_constants.value' );
        } ] );
    }

    public function scopeCounterActions( $query )  {
        return $query->with('CounterActions');
    }

    public function scopeCounters( $query ) {
      return $query->with( [ 'Counters' => function ( $query ) {
        $query->select( 'system_counters.id',
                        'system_counters.name',
                        'system_counters.start_value as value',
                        'system_counters.imageable_id',
                        'system_counters.imageable_type' );
      } ] );
    }

    public function scopeFiles( $query )  {
        return $query->with( 'Files' );
    }

    public function scopeQuestions( $query ) {
        return $query->with( [ 'Questions' =>
                        function( $query ) { $query->select('id', 'value'); } ] )
                     ->with( [ 'Questions.QuestionTypes' =>
                        function( $query ) { $query->select('id', 'value'); } ] )
                     ->with( 'Questions.QuestionResponses' );
    }

    public function scopeWithObjects( $query ) {
        return $query->Constants()
                     ->Files()
                     ->Questions()
                     ->CounterActions()
                     ->Counters();
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
      if ( $nodeId == null ) {
            $node = $this->MapNodes()
                         ->RootNode()
                         ->Abbreviated()
                         ->WithObjects()
                         ->first();
        }
        else {
            $node = $this->MapNodes()
                ->where('id', $nodeId )
                ->Abbreviated()
                ->WithObjects()
                ->first();
        }

        return $node;
    }

    /**
     * Get map avatars
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapAvatars() {
        return $this->hasMany('App\Modules\Olab\Models\MapAvatars');
    }

    /**
     * Get map chats
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapChats() {
        return $this->hasMany('App\Modules\Olab\Models\MapChats');
    }

    /**
     * Get map collections
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapCollectionMaps() {
        return $this->hasMany('App\Modules\Olab\Models\MapCollectionMaps');
    }

    /**
     * Get map constants
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function Constants() {
        return $this->morphMany('App\Modules\Olab\Models\Constants', 'imageable');
    }

    /**
     * Get map counters
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function Counters() {
        return $this->morphMany('App\Modules\Olab\Models\Counters', 'imageable');
    }

    /**
     * Get map counters actions
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function CounterActions() {
      return $this->morphMany('App\Modules\Olab\Models\CounterActions', 'imageable');
    }

    /**
     * Get map constants
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function Files() {
        return $this->morphMany('App\Modules\Olab\Models\Files', 'imageable');
    }

    /**
     * Get map questions
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function Questions() {
        return $this->morphMany('App\Modules\Olab\Models\Questions', 'imageable');
    }

    /**
     * Get map contributors
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapContributors() {
        return $this->hasMany('App\Modules\Olab\Models\MapContributors');
    }

    /**
     * Get map counter common rules
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapCounterCommonRules() {
        return $this->hasMany('App\Modules\Olab\Models\MapCounterCommonRules');
    }

    /**
     * Get map counters
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    //public function MapCounters() {
    //    return $this->hasMany('App\Modules\Olab\Models\MapCounters', 'map_id');
    //}

    /**
     * Get map dams
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapDams() {
        return $this->hasMany('App\Modules\Olab\Models\MapDams');
    }

    /**
     * Get map elements
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapElements() {
        return $this->hasMany('App\Modules\Olab\Models\MapElements');
    }

    /**
     * Get map feedback rules
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapFeedbackRules() {
        return $this->hasMany('App\Modules\Olab\Models\MapFeedbackRules');
    }

    /**
     * Get map keys
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapKeys() {
        return $this->hasMany('App\Modules\Olab\Models\MapKeys');
    }

    /**
     * Get map mode links
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapNodeLinks() {
        return $this->hasMany('App\Modules\Olab\Models\MapNodeLinks', 'map_id');
    }

    /**
     * Get map nodes sections
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function MapNodeSections() {
        return $this->hasMany('App\Modules\Olab\Models\MapNodeSections');
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
      return $this->hasMany('App\Modules\Olab\Models\MapNodes', 'map_id');
    }

    public function MapUsers() {
        return $this->hasMany('App\Modules\Olab\Models\MapUsers');
    }

    public function QCumulative() {
        return $this->hasMany('App\Modules\Olab\Models\QCumulative');
    }

    public function ScenarioMaps() {
        return $this->hasMany('App\Modules\Olab\Models\ScenarioMaps');
    }

    public function UserSessions() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessions');
    }
    public function UserSessionsCBref() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessionsCBref');
    }
    public function UserSessionsC3st() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessionsC3st');
    }
    public function UserSessionsExt() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessionsExt');
    }
    public function UserSessionsPart() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessionsPart');
    }
    public function UserSessiontraces() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessiontraces');
    }
    public function UserSessiontracesCBRef() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessiontracesCBRef');
    }
    public function UserSessiontracesC3st() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessiontracesC3st');
    }
    public function UserSessiontracesExt() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessiontracesExt');
    }
    public function UserSessiontracesPart() {
        return $this->hasMany('App\Modules\Olab\Models\UserSessiontracesPart');
    }
    public function MapSecurities() {
        return $this->belongsTo('App\Modules\Olab\Models\MapSecurities');
    }
    public function MapTypes() {
        return $this->belongsTo('App\Modules\Olab\Models\MapTypes');
    }
    public function MapSkins() {
        return $this->belongsTo('App\Modules\Olab\Models\MapSkins');
    }
    public function MapSections() {
        return $this->belongsTo('App\Modules\Olab\Models\MapSections');
    }
    public function Languages() {
        return $this->belongsTo('App\Modules\Olab\Models\Languages');
    }

}