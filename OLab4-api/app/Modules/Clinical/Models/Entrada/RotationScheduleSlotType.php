<?php

namespace Entrada\Modules\Clinical\Models\Entrada;

use Illuminate\Database\Eloquent\Model;

class RotationScheduleSlotType extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cbl_schedule_slot_types';

    protected $primaryKey = "slot_type_id";

}
