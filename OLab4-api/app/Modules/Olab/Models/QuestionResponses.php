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
use XMLReader;
use DOMDocument;
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Classes\OLabUtilities;

/**
 * @property integer $id
 * @property integer $parent_id
 * @property integer $question_id
 * @property string $response
 * @property string $feedback
 * @property string $is_correct
 * @property integer $score
 * @property string $from
 * @property string $to
 * @property integer $order
 */

class QuestionResponses extends BaseModel {

    const XML_FILE = "map_question_response.xml";
    const XML_ROOT_ELEMENT = "map_question_response_";

    protected $table = 'system_question_responses';
    protected $fillable = ['parent_id','name','description', 'question_id','response','feedback','is_correct','score',
                           'from','to','order'];
    protected $validations = ['parent_id' => 'exists:system_question_responses,id|integer|min:0',
                            'name' => 'max:50|string',
                            'description' => 'string',
                            'question_id' => 'exists:system_questions,id|integer|min:0',
                            'response' => 'max:250|string',
                            'feedback' => 'string',
                            'is_correct' => 'integer',
                            'score' => 'integer',
                            'from' => 'max:200|string',
                            'to' => 'max:200|string',
                            'order' => 'integer|min:0|required'];

    protected $attributes = array(
      'response' => '',
      'score' => 0 );

    protected $post_to_db_column_translation = [ 

      // alias => raw
      'questionId' => 'question_id',
      'isCorrect' => 'is_correct'
    ];

    public function toArray() {
      
      $aObj = parent::toArray();

      OLabUtilities::safe_rename( $aObj, 'question_id' );
      OLabUtilities::safe_rename( $aObj, 'parent_id' );
      OLabUtilities::safe_rename( $aObj, 'feedback' );
      OLabUtilities::safe_rename( $aObj, 'from' );
      OLabUtilities::safe_rename( $aObj, 'to' );
      OLabUtilities::safe_rename( $aObj, 'is_correct', 'isCorrect');

      OLabUtilities::safe_rename( $aObj, 'imageable_type', 'scopeLevel' );
      OLabUtilities::safe_rename( $aObj, 'imageable_id', "parentId" );

      return $aObj;

    }

    public function Question() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Questions');
    }

    /**
     * Create object from legacy source object
     * @param mixed $nParentId 
     * @param mixed $oSourceObj map_node record
     * @return QuestionResponses|null new question response, or null is source not of expected type
     */
    public static function Create( $nParentId, $oSourceObj ) {

        // get class name to ensure the source is supported
        $sClassName = get_class( $oSourceObj );
        $parts = explode('\\', $sClassName );
        $sClassName = array_pop( $parts );

        // we can only create an object from MapQuestions,
        // return if it's not what we expect
        if ( $sClassName != "MapQuestionResponses")
            throw new Exception("Unknown source type '" . $sClassName . ".");

        $instance = new self();

        $instance->parent_id    = $oSourceObj->parent_id  ;
        $instance->question_id  = $nParentId              ;
        $instance->response     = $oSourceObj->response   ;
        $instance->feedback     = $oSourceObj->feedback   ;
        $instance->is_correct   = $oSourceObj->is_correct ;
        $instance->score        = $oSourceObj->score      ;
        $instance->from         = $oSourceObj->from       ;
        $instance->to           = $oSourceObj->to         ;
        $instance->order        = $oSourceObj->order      ;
        
        return $instance;
    }

    /**
     * Import from xml file
     * @param mixed $import_directory Base import directory
     * @throws Exception 
     * @return Maps
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

            $instance->id           = (int)$node->id                ;
            $instance->parent_id    = null                          ;
            $instance->question_id  = (int)$node->question_id       ;
            $instance->response     = base64_decode( $node->response );
            $instance->feedback     = $node->feedback               ;
            $instance->is_correct   = (int)$node->is_correct        ;
            $instance->score        = (int)$node->score             ;
            $instance->from         = $node->from                   ;
            $instance->to           = $node->to                     ;
            $instance->order        = $node->order                  ;

            array_push( $items, $instance );

            // update element to next record in sequence
            $index++;
            $current_root_name = self::XML_ROOT_ELEMENT . $index;
            $xmlReader->next( $current_root_name );
        }

        return $items;

    }

}