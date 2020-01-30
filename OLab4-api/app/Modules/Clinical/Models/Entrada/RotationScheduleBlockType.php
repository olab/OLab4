<?php

namespace Entrada\Modules\Clinical\Models\Entrada;

use Illuminate\Database\Eloquent\Model;

class RotationScheduleBlockType extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cbl_schedule_lu_block_types';

    protected $primaryKey = "block_type_id";
}
