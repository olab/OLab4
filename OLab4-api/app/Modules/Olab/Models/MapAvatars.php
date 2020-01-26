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
 * @property string $skin_1
 * @property string $skin_2
 * @property string $cloth
 * @property string $nose
 * @property string $hair
 * @property string $environment
 * @property string $accessory_1
 * @property string $bkd
 * @property string $sex
 * @property string $mouth
 * @property string $outfit
 * @property string $bubble
 * @property string $bubble_text
 * @property string $accessory_2
 * @property string $accessory_3
 * @property string $age
 * @property string $eyes
 * @property string $hair_color
 * @property string $image
 * @property integer $is_private
 */

class MapAvatars extends BaseModel {

    const XML_FILE = "map_avatar.xml";
    const XML_ROOT_ELEMENT = "map_avatar_";

    protected $table = 'map_avatars';
    protected $fillable = ['map_id','skin_1','skin_2','cloth','nose','hair','environment','accessory_1','bkd','sex','mouth','outfit','bubble','bubble_text','accessory_2','accessory_3','age','eyes','hair_color','image','is_private'];
    protected $validations = ['map_id' => 'exists:maps,id|integer|min:0|required',
                            'skin_1' => 'max:6|string',
                            'skin_2' => 'max:6|string',
                            'cloth' => 'max:6|string',
                            'nose' => 'max:20|string',
                            'hair' => 'max:20|string',
                            'environment' => 'max:20|string',
                            'accessory_1' => 'max:20|string',
                            'bkd' => 'max:6|string',
                            'sex' => 'max:20|string',
                            'mouth' => 'max:20|string',
                            'outfit' => 'max:20|string',
                            'bubble' => 'max:20|string',
                            'bubble_text' => 'max:100|string',
                            'accessory_2' => 'max:20|string',
                            'accessory_3' => 'max:20|string',
                            'age' => 'max:2|string',
                            'eyes' => 'max:20|string',
                            'hair_color' => 'max:6|string',
                            'image' => 'max:100|string',
                            'is_private' => 'integer|required'];

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

            $instance->id            = (int)$node->id    ;
            $instance->map_id        = (int)$node->map_id;
            $instance->skin_1        = base64_decode($node->skin_1)    ;
            $instance->skin_2        = base64_decode($node->skin_2)    ;
            $instance->cloth         = base64_decode($node->cloth)     ;
            $instance->nose          = base64_decode($node->nose)      ;
            $instance->hair          = base64_decode($node->hair)      ;
            $instance->environment   = base64_decode($node->environment);
            $instance->accessory_1   = base64_decode($node->accessory_1);
            $instance->bkd           = $node->bkd        ;
            $instance->sex           = base64_decode($node->sex)       ;
            $instance->mouth         = base64_decode($node->mouth)     ;
            $instance->outfit        = base64_decode($node->outfit)    ;
            $instance->bubble        = base64_decode($node->bubble)    ;
            $instance->bubble_text   = base64_decode($node->bubble_text);
            $instance->accessory_2   = base64_decode($node->accessory_2);
            $instance->accessory_3   = base64_decode($node->accessory_3);
            $instance->age           = (int)$node->age                 ;
            $instance->eyes          = base64_decode($node->eyes)      ;
            $instance->hair_color    = base64_decode($node->hair_color);
            $instance->image         = base64_decode($node->image)     ;
            $instance->is_private    = (int)$node->is_private          ;

            array_push( $items, $instance );

            // update element to next record in sequence
            $index++;
            $current_root_name = self::XML_ROOT_ELEMENT . $index;
            $xmlReader->next( $current_root_name );
        }

        return $items;

    }

    public function Maps() {
        return $this->belongsTo('Entrada\Modules\Olab\Models\Maps');
    }

}