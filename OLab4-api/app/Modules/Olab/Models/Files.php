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
 * A model for OLab files.
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Models;

use Exception;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\SiteFileHandler;
use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Models\PolymorphicModel;
use Illuminate\Support\Facades\Log;

/**
 * @property integer $id
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
 * @property string $encoded_content
 * @property integer $is_embedded
 * @property integer $imageable_id
 * @property string $imageable_type
 */

class Files extends PolymorphicModel {

  const WIKI_TAG_MEDIA_RESOURCE = "MR";

  const FILES_TYPE_INLINE = 0;
  const FILES_TYPE_DELAYED_PUBLIC = 1;
  const FILES_TYPE_DELAYED_PRIVATE = 2;

  protected $table = 'system_files';

  protected $fillable = ['mime','name','description','path','file_size', 'args','width',
                         'width_type','height', 'height_type','h_align','v_align',
                         'is_shared','is_private','is_embedded', 'encoded_content',
                         'imageable_id','imageable_type', 'is_system', 'file_size',
                         'origin_url', 'copyright', 'type'];
  protected $validations = ['mime' => 'max:500|string',
                          'name' => 'max:200|string',
                          'description' => 'string',
                          'path' => 'max:300|string',
                          'args' => 'max:100|string',
                          'width' => 'integer',
                          'width_type' => 'max:2|string',
                          'height' => 'integer',
                          'height_type' => 'max:2|string',
                          'h_align' => 'max:20|string',
                          'v_align' => 'max:20|string',
                          'is_shared' => 'integer|required',
                          'is_private' => 'integer|required',
                          'is_embedded' => 'integer',
                          'file_size' => 'integer',
                          'encoded_content' => '',
                          'is_system' => 'integer',
                          'imageable_id' => 'integer|required',
                          'imageable_type' => 'max:45|string'];

  protected $attributes = array(
                          'is_system' => 0,
                          'id' => null
                          );

  protected $post_to_db_column_translation = [ 

    // alias => raw
    'isSystem' => 'is_system',
    'fileName' => 'path',
    'widthType' => 'width_type',
    'heightType' => 'height_type',
    'fileSize' => 'file_size',
    'url' => 'path',
    'hAlign' => 'h_align',
    'vAlign' => 'v_align',
    'isShared' => 'is_shared',
    'isPrivate' => 'is_private',
    'isEmbedded' => 'is_embedded',
    'contents' => 'encoded_content',
    'originUrl' => 'origin_url',
    'copyright' => 'copyright',
    'scopeLevel' => 'imageable_type',
    'parentId' => 'imageable_id'

  ];

  public function toArray() {
    
    $aObj = parent::toArray();

    OLabUtilities::safe_rename( $aObj, 'is_system', 'isSystem' );

    OLabUtilities::safe_rename( $aObj, 'width_type', 'widthType');
    OLabUtilities::safe_rename( $aObj, 'height_type', 'heightType');
    OLabUtilities::safe_rename( $aObj, 'file_size', 'fileSize' );
    OLabUtilities::safe_rename( $aObj, 'origin_url', 'originUrl' );
    OLabUtilities::safe_rename( $aObj, 'h_align' );
    OLabUtilities::safe_rename( $aObj, 'v_align' );
    OLabUtilities::safe_rename( $aObj, 'is_shared' );
    OLabUtilities::safe_rename( $aObj, 'is_private' );
    OLabUtilities::safe_rename( $aObj, 'is_embedded', 'isEmbedded' );
    OLabUtilities::safe_rename( $aObj, 'encoded_content' );

    if ( $this->type == Files::FILES_TYPE_INLINE ) {
      $aObj['resourceUrl'] = OLabUtilities::concat_path( HostSystemApi::getRootUrl(), "images/olab/files" );
      $aObj['resourceUrl'] = OLabUtilities::concat_path( $aObj['resourceUrl'], $this->path );
    }

    OLabUtilities::safe_rename( $aObj, 'path' );

    return $aObj;

  }

  public function scopeAt( $query, $id ) {
    return $query->where( 'id', '=', $id )
                 ->first();
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
    if ( $sClassName != "MapElements")
      throw new Exception("Unknown source type '" . $sClassName . ".");

    $instance = new self();

    $instance->mime               = $oSourceObj->mime;
    $instance->name               = $oSourceObj->name;
    $instance->path               = "/" . $oSourceObj->path;
    $instance->args               = $oSourceObj->args;
    $instance->width              = $oSourceObj->width;
    $instance->width_type         = $oSourceObj->width_type;
    $instance->height             = $oSourceObj->height;
    $instance->height_type        = $oSourceObj->height_type;
    $instance->h_align            = $oSourceObj->h_align;
    $instance->v_align            = $oSourceObj->v_align;
    $instance->is_shared          = $oSourceObj->is_shared;
    $instance->is_private         = $oSourceObj->is_private;
    $instance->is_embedded        = $oSourceObj->is_embedded;
    $instance->encoded_content    = $oSourceObj->encoded_content;
    $instance->is_system          = 0;

    $instance->imageable_id       = $nParentId;
    $instance->imageable_type     = "Maps";

    return $instance;
  }

  public static function GetPublicFileRoot() {
    return HostSystemApi::getFileRoot() . DIRECTORY_SEPARATOR . "images/olab/files";    
  }

  public static function GetPrivateFileRoot() {
    return HostSystemApi::getFileRoot() . DIRECTORY_SEPARATOR . "core/storage/olab";    
  }

  public function delete() {

    SiteFileHandler::deleteFile( $this );
    parent::delete();
  }

}
