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

class MapNodes extends BaseModel {

    protected $table = 'map_nodes';
    protected $fillable = ['map_id','title','text','type_id','probability','conditional','conditional_message','info','is_private','link_style_id','link_type_id','priority_id','kfp','undo','end','x','y','rgb','show_info','annotation'];
    protected $validations = ['map_id' => 'exists:maps,id|integer|min:0|required',
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
                            'x' => '',
                            'y' => '',
                            'rgb' => 'max:8|string',
                            'show_info' => 'integer|required',
                            'annotation' => 'string'];

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

    public function scopeConstants( $query )  {
        return $query->with( 'Constants' );
    }

    public function scopeCounters( $query )  {
        return $query->with( [ 'Counters' => function ( $query ) {
            $query->select( 'system_counters.id',
                            'system_counters.name',
                            'system_counters.start_value as value',
                            'system_counters.imageable_id');
        } ] );
    }

    public function scopeCounterActions( $query )  {
        return $query->with('CounterActions');
    }

    public function scopeFiles( $query )  {
        return $query->with('Files');
    }

    public function scopeLinks( $query )  {
        return $query->with( [ 'MapNodeLinks' => function( $query ) {
            $query->select( 'map_node_links.text',
                            'map_node_links.link_type_id',
                            'map_node_links.link_style_id'  );
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

    public function scopeQuestions( $query ) {
        return $query->with( [ 'Questions' => function( $query ) { $query->select('id', 'value'); } ] )
                     ->with( [ 'Questions.QuestionTypes' => function( $query ) { $query->select('id', 'value'); } ] );
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
                     ->counters()
                     ->counterActions()
                     ->files();
    }

    public function ConditionsChange() {
        return $this->hasMany('App\Modules\Olab\Models\ConditionsChange');
    }

    /**
     * Get node constants
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function Constants() {
        return $this->morphMany('App\Modules\Olab\Models\Constants', 'imageable');
    }

    /**
     * Get node constants
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function Files() {
        return $this->morphMany('App\Modules\Olab\Models\Files', 'imageable');
    }

    /**
     * Get node questions
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function Questions() {
        return $this->morphMany('App\Modules\Olab\Models\Questions', 'imageable');
    }

    /**
     * Get node counters
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Counters() {
        return $this->morphMany('App\Modules\Olab\Models\Counters', 'imageable' );
    }

    /**
     * Get map counters actions
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function CounterActions() {
        return $this->morphMany('App\Modules\Olab\Models\CounterActions', 'imageable');
    }

    /**
     * Gets the counters for a map node
     * @return MapCounters
     */
    //public function NodeCounters() {
    //    return $this->belongsToMany('App\Modules\Olab\Models\MapCounters', 'map_node_counters', 'node_id', 'counter_id' );
    //}

    public function MapNodeLinks() {
        return $this->hasMany('App\Modules\Olab\Models\MapNodeLinks', 'node_id_1' );
    }

    public function MapNodeSectionNodes() {
        return $this->hasMany('App\Modules\Olab\Models\MapNodeSectionNodes');
    }

    public function PatientConditionChange() {
        return $this->hasMany('App\Modules\Olab\Models\PatientConditionChange');
    }
    public function UserBookmarks() {
        return $this->hasMany('App\Modules\Olab\Models\UserBookmarks');
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
    public function WebinarNodePoll() {
        return $this->hasMany('App\Modules\Olab\Models\WebinarNodePoll');
    }
    public function WebinarPoll() {
        return $this->hasMany('App\Modules\Olab\Models\WebinarPoll');
    }

    public function Map() {
        return $this->belongsTo('App\Modules\Olab\Models\Maps');
    }
    public function MapNodeLinkStylies() {
        return $this->belongsTo('App\Modules\Olab\Models\MapNodeLinkStylies');
    }

}