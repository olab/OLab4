<?php

namespace Entrada\Modules\Olab\Models;
use Entrada\Modules\Olab\Models\BaseModel;

/**
 * @property integer $id
 * @property string $name
 * @property string $status
 * @property string $date
 * @property integer $author_id
 * @property string $settings
 * @property string $security_id
 * @property integer $forum_id
 * @property integer $node_id
 */

class Dtopic extends BaseModel {

    protected $table = 'dtopic';
    protected $fillable = ['name','status','date','author_id','settings','security_id','forum_id','node_id'];
    protected $validations = ['name' => 'max:255|string',
                            'status' => 'integer|required',
                            'date' => 'required|date',
                            'author_id' => 'integer|required',
                            'settings' => 'string',
                            'security_id' => 'integer|required',
                            'forum_id' => 'integer|required',
                            'node_id' => 'integer'];


}