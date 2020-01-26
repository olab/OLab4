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

/**
 * @property integer $id
 * @property integer $map_id
 * @property string $mime
 * @property string $name
 * @property string $path
 * @property string $args
 * @property integer $width
 * @property string $width_type
 * @property integer $height
 * @property string $height_type
 * @property string $h_align
 * @property string $v_align
 * @property string $is_shared
 * @property string $is_private
 */

class MapElements extends BaseModel {

    const XML_FILE = "map_element.xml";
    const XML_ROOT_ELEMENT = "map_element_";

    protected $table = 'map_elements';
    protected $fillable = ['map_id','mime','name','path','args','width','width_type','height','height_type','h_align','v_align','is_shared','is_private'];
    protected $validations = ['map_id' => 'exists:maps,id|integer|min:0|required',
                              'mime' => 'max:500|string',
                              'name' => 'max:200|string',
                              'path' => 'max:300|string',
                              'args' => 'max:100|string',
                              'width' => 'integer',
                              'width_type' => 'max:2|string',
                              'height' => 'integer',
                              'height_type' => 'max:2|string',
                              'h_align' => 'max:20|string',
                              'v_align' => 'max:20|string',
                              'is_shared' => 'integer|required',
                              'is_private' => 'integer|required'];

    public function Maps() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Maps');
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

            $instance->id           = (int)$node->id              ;
            $instance->map_id       = (int)$node->map_id          ;
            $instance->mime         = base64_decode($node->mime)  ;
            $instance->name         = base64_decode($node->name)  ;
            $instance->path         = base64_decode( $node->path) ;
            $instance->args         = $node->args                 ;
            $instance->width        = (int)$node->width           ;
            $instance->width_type   = $node->width_type           ;
            $instance->height       = (int)$node->height          ;
            $instance->height_type  = $node->height_type          ;
            $instance->h_align      = (int)$node->h_align         ;
            $instance->v_align      = (int)$node->v_align         ;
            $instance->is_shared    = (int)$node->is_shared       ;
            $instance->is_private   = (int)$node->is_private      ;

            array_push( $items, $instance );

            // update element to next record in sequence
            $index++;
            $current_root_name = self::XML_ROOT_ELEMENT . $index;
            $xmlReader->next( $current_root_name );
        }

        return $items;

    }

    public function scopeAt( $query, $id ) {
        return $query->where('id', $id );    
    }

    public function scopeByMap( $query, $id ) {
        return $query->where('map_id', $id );    
    }

}