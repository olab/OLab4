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
use Entrada\Modules\Olab\Models\BaseModel;

/**
 * @property integer $id
 * @property string $created_at
 * @property string $updated_at
 * @property integer $user_id
 * @property string $title
 * @property integer $library_id
 * @property string $parameters
 * @property string $filtered
 * @property string $slug
 * @property string $embed_type
 * @property integer $disable
 * @property string $content_type
 * @property string $author
 * @property string $license
 * @property string $keywords
 * @property string $description
 */

class H5pContents extends BaseModel {

    protected $table = 'h5p_contents';
    protected $fillable = ['user_id','title','library_id','parameters','filtered','slug','embed_type','disable','content_type','author','license','keywords','description'];
    protected $validations = ['user_id' => 'integer|min:0|required',
                            'title' => 'max:255|string',
                            'library_id' => 'integer|min:0|required',
                            'parameters' => 'string',
                            'filtered' => 'string',
                            'slug' => 'max:127|string',
                            'embed_type' => 'max:127|string',
                            'disable' => 'integer|min:0|required',
                            'content_type' => 'max:127|string',
                            'author' => 'max:127|string',
                            'license' => 'max:7|string',
                            'keywords' => 'string',
                            'description' => 'string'];

    /**
     * @param mixed $query
     * @return int $id
     */
    public function scopeAt( $query, $id ) {
        return $query->where( 'id', '=', $id )->first();
    }

    /**
     * @param mixed $query
     * @return array $atts
     */
    public function scopeByArray($query, $atts) {
        return $query->where( $atts )->first();
    }

    public function scopeLibrary( $query ) {
      return $query->with('Library')  ;
    }

    public function Library() {
      return $this->hasOne('Entrada\Modules\Olab\Models\H5pLibraries', 'id', 'library_id');
    }

    public static function LibraryContentCount() {
      
      $sql = "SELECT l.name, l.major_version, l.minor_version, COUNT(*) AS count
              FROM h5p_contents c, h5p_libraries l
              WHERE c.library_id = l.id
              GROUP BY l.name, l.major_version, l.minor_version";

      $content = BaseModel::GetRawSqlRows( $sql );

      return $content;
    }

    public static function isContentSlugAvailable( $slug ) {
      
      $sql = vsprintf( "SELECT slug FROM h5p_contents WHERE slug = '%s'", $slug );
      $content = BaseModel::GetRawSqlRow( $sql );

      return $content != null;

    }

    public static function LoadContent( $id ) {
      
      $sql = vsprintf( 
          "SELECT hc.id
              , hc.title
              , hc.parameters AS params
              , hc.filtered
              , hc.slug AS slug
              , hc.user_id
              , hc.embed_type AS embedType
              , hc.disable
              , hl.id AS libraryId
              , hl.name AS libraryName
              , hl.major_version AS libraryMajorVersion
              , hl.minor_version AS libraryMinorVersion
              , hl.embed_types AS libraryEmbedTypes
              , hl.fullscreen AS libraryFullscreen
        FROM h5p_contents hc
        JOIN h5p_libraries hl ON hl.id = hc.library_id
        WHERE hc.id = %d", $id );

      $content = BaseModel::GetRawSqlRow( $sql );

      // need this for compatibility with newer H5PCore
      $content['metadata'] = [];

      return $content;
    }

    public static function GetAuthorCount() {
      
      $sql = "SELECT COUNT(DISTINCT user_id) as AuthorCount FROM h5p_contents";

      $content = BaseModel::GetRawSqlRow( $sql );

      // need this for compatibility with newer H5PCore
      return ( int )$content['AuthorCount'];

    }
}