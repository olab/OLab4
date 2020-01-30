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
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\PolymorphicModel;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use XMLReader;
use DOMDocument;

/**
 * @property integer $id
 * @property integer $parent_id
 * @property string $stem
 * @property integer $entry_type_id
 * @property integer $width
 * @property integer $height
 * @property string $feedback
 * @property string $prompt
 * @property string $show_answer
 * @property integer $counter_id
 * @property integer $num_tries
 * @property string $show_submit
 * @property integer $redirect_node_id
 * @property string $submit_text
 * @property integer $type_display
 * @property string $settings
 * @property integer $is_private
 * @property integer $order
 * @property string $external_source_id
 * @property integer $imageable_id
 * @property string $imageable_type
 */

class Questions extends PolymorphicModel {

    const XML_FILE = "map_question.xml";
    const XML_ROOT_ELEMENT = "map_question_";
    const WIKI_TAG_QUESTION = "QU";

    protected $table = 'system_questions';
    protected $fillable = ['parent_id','name','description', 'stem','entry_type_id',
                           'width','height','feedback','prompt','show_answer','counter_id',
                           'num_tries','show_submit','redirect_node_id','submit_text',
                           'type_display','settings','is_private','order','external_source_id',
                           'imageable_id','imageable_type'];

    protected $validations = ['parent_id' => 'exists:system_questions,id|integer|min:0',
                            'name' => 'max:50|string',
                            'description' => 'string',
                            'stem' => 'max:500|string',
                            'entry_type_id' => 'integer|required',
                            'width' => 'integer|required',
                            'height' => 'integer|required',
                            'feedback' => 'max:1000|string',
                            'prompt' => 'string',
                            'show_answer' => 'integer|required',
                            'counter_id' => 'integer',
                            'num_tries' => 'integer|required',
                            'show_submit' => 'integer|required',
                            'redirect_node_id' => 'integer|min:0',
                            'submit_text' => 'max:200|string',
                            'type_display' => 'integer|required',
                            'settings' => 'string',
                            'is_private' => 'integer|required',
                            'order' => 'integer',
                            'external_source_id' => 'max:255|string',
                            'imageable_id' => 'integer|required',
                            'imageable_type' => 'max:45|string'];

    protected $post_to_db_column_translation = [ 

      // alias => raw
      'questionType' => 'entry_type_id',
      'placeholder' => 'prompt',
      'showAnswer' => 'show_answer',
      'counterId' => 'counter_id',
      'numTries' => 'num_tries',
      'showSubmit' => 'show_submit',
      'redirectNodeId' => 'redirect_node_id',
      'submitText' => 'submit_text',
      'layoutType' => 'type_display',
      'isPrivate' => 'is_private',
      'externalSourceId' => 'external_source_id',
      'scopeLevel' => 'imageable_type',
      'parentId' => 'imageable_id'

    ];

    protected $attributes = array(
                          'width' => 60,
                          'height' => 20,
                          'id' => null
                          );

    public function toArray() {
      
      $aObj = parent::toArray();

      OLabUtilities::safe_rename( $aObj, 'parent_id' );
      OLabUtilities::safe_rename( $aObj, 'is_private' );
      OLabUtilities::safe_rename( $aObj, 'counter_id' );
      OLabUtilities::safe_rename( $aObj, 'submit_text' );
      OLabUtilities::safe_rename( $aObj, 'num_tries' );
      OLabUtilities::safe_rename( $aObj, 'external_source_id' );
      OLabUtilities::safe_rename( $aObj, 'redirect_node_id' );
      OLabUtilities::safe_rename( $aObj, 'settings' );
      OLabUtilities::safe_rename( $aObj, 'prompt', 'placeholder' );
      OLabUtilities::safe_rename( $aObj, 'entry_type_id', 'questionType');
      OLabUtilities::safe_rename( $aObj, 'show_answer', 'showAnswer');
      OLabUtilities::safe_rename( $aObj, 'counter_id', 'counterId');
      OLabUtilities::safe_rename( $aObj, 'num_tries', 'numTries');
      OLabUtilities::safe_rename( $aObj, 'show_submit', 'showSubmit');
      OLabUtilities::safe_rename( $aObj, 'redirect_node_id', 'redirectNodeId');
      OLabUtilities::safe_rename( $aObj, 'submit_text', 'submitText');
      OLabUtilities::safe_rename( $aObj, 'type_display', 'layoutType');
      OLabUtilities::safe_rename( $aObj, 'is_private', 'isPrivate');
      OLabUtilities::safe_rename( $aObj, 'external_source_id', 'externalSourceId');

      OLabUtilities::safe_rename( $aObj, 'imageable_type', 'scopeLevel' );
      OLabUtilities::safe_rename( $aObj, 'imageable_id', "parentId" );

      return $aObj;

    }

    public function scopeQuestionTypes( $query ) {
        return $query->with( [ 'QuestionTypes' => function ( $query ) {
            $query->select( 'system_question_types.id', 'system_question_types.value' );
        } ] );
    }

    public function scopeQuestionResponses( $query ) {
        return $query->with( 'QuestionResponses' );
    }

    public function scopeWithTypes( $query )  {
        return $query->QuestionTypes()
                     ->QuestionResponses();
    }

    public function Counter() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Counters', 'counter_id');
    }
    
    public function QuestionTypes() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\QuestionTypes', 'entry_type_id');
    }

    public function QuestionResponses() {
        return $this->hasMany('Entrada\Modules\Olab\Models\QuestionResponses', 'question_id');
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
        if ( $sClassName != "MapQuestions")
            throw new Exception("Unknown source type '" . $sClassName . ".");

        $instance = new self();

        $instance->entry_type_id      = $oSourceObj->entry_type_id     ;
        $instance->external_source_id = $oSourceObj->external_source_id;
        $instance->feedback           = $oSourceObj->feedback          ;
        $instance->is_private         = $oSourceObj->is_private        ;
        $instance->num_tries          = $oSourceObj->num_tries         ;
        $instance->order              = $oSourceObj->order             ;
        $instance->parent_id          = $oSourceObj->parent_id         ;
        $instance->prompt             = $oSourceObj->prompt            ;
        $instance->redirect_node_id   = $oSourceObj->redirect_node_id  ;
        $instance->settings           = $oSourceObj->settings          ;
        $instance->show_answer        = $oSourceObj->show_answer       ;
        $instance->show_submit        = $oSourceObj->show_submit       ;
        $instance->stem               = $oSourceObj->stem              ;
        $instance->submit_text        = $oSourceObj->submit_text       ;
        $instance->type_display       = $oSourceObj->type_display      ;
        $instance->width              = $oSourceObj->width             ;

        $instance->imageable_id       = $nParentId                     ;
        $instance->imageable_type     = Maps::IMAGEABLE_TYPE;

        return $instance;
    }

    /**
     * Import from xml file
     * @param mixed $import_directory Base import directory
     * @throws Exception 
     * @return array Maps
     */
    public static function import( $import_directory, $parent_id ) {

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
            $instance->id                   = (int)$node->id                  ;
            $instance->stem                 = base64_decode($node->stem)      ;
            $instance->entry_type_id        = (int)$node->entry_type_id       ;
            $instance->width                = (int)$node->width               ;
            $instance->height               = (int)$node->height              ;
            $instance->feedback             = base64_decode($node->feedback)  ;
            $instance->prompt               = (int)$node->prompt              ;
            $instance->show_answer          = (int)$node->show_answer         ;
            $instance->counter_id           = (int)$node->counter_id          ;
            $instance->num_tries            = (int)$node->num_tries           ;
            $instance->show_submit          = (int)$node->show_submit         ;
            $instance->redirect_node_id     = (int)$node->redirect_node_id    ;
            $instance->submit_text          = $node->submit_text              ;
            $instance->type_display         = $node->type_display             ;
            $instance->settings             = $node->settings                 ;
            $instance->is_private           = (int)$node->is_private          ;
            $instance->order                = (int)$node->order               ;
            $instance->external_source_id   = (int)$node->external_source_id  ;

            $instance->imageable_id         = $parent_id                      ;
            $instance->imageable_type       = Maps::IMAGEABLE_TYPE;
            
            array_push( $items, $instance );

            // update element to next record in sequence
            $index++;
            $current_root_name = self::XML_ROOT_ELEMENT . $index;
            $xmlReader->next( $current_root_name );
        }

        return $items;

    }

}