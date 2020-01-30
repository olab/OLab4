<?php

namespace Entrada\Modules\Locations\Models\Entrada;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = "global_lu_rooms";
    protected $primaryKey = "room_id";
    protected $fillable = [
        "building_id",
        "room_number",
        "room_name",
        "room_description",
        "room_max_occupancy"
    ];
    public $timestamps = false;
}
