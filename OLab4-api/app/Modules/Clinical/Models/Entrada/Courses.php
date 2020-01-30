<?php

namespace Entrada\Modules\Clinical\Models\Entrada;

use Illuminate\Database\Eloquent\Model;

class Courses extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'courses';

    protected $primaryKey = "course_id";

}
