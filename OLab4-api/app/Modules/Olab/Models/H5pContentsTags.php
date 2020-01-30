<?php

namespace Entrada\Modules\Olab\Models;
use Entrada\Modules\Olab\Models\BaseModel;

/**
 * @property integer $content_id
 * @property integer $tag_id
 */

class H5pContentsTags extends BaseModel {

    protected $table = 'h5p_contents_tags';
    protected $fillable = ['content_id','tag_id'];
    protected $validations = ['content_id' => 'integer|min:0|required',
                            'tag_id' => 'integer|min:0|required'];


}