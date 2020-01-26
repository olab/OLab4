<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Entrada\Models\Auth\User as AuthUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flag extends Model {
    //

    use SoftDeletes;

    const CREATED_AT = "created_date";
    const UPDATED_AT = "updated_date";
    const DELETED_AT = "deleted_date";
    protected $dateFormat = 'U';

    protected $connection = "entrada_database";
    protected $table = "admissions_flag";
    protected $primaryKey = "flag_id";

    protected $fillable = [
        "reason",
        "flagged_by"
    ];

    protected $visible = [
        'flag_id', 'reason', 'flagged_by', 'flagger_name', 'flagged_date'
    ];
    
    protected $appends = ['flagger_name', 'flagged_date', 'file_id', 'file'];

    public function flagger() {

        return $this->hasOne(AuthUser::class, "id", "flagged_by");
    }

    public function getFlaggerNameAttribute() {
        $flagger = $this->flagger;
        return empty($flagger) ? "" : "{$flagger->firstname} {$flagger->lastname}";
    }

    public function getFileIdAttribute() {
        return $this->entity_type == "file" ? $this->entity_id : null;
    }

    public function getFileAttribute() {
        return $this->entity_type == "file" ? $this->entity : null;
    }

    public function getFlaggedDateAttribute() {
        return $this->{self::CREATED_AT}->format("F jS, Y");
    }

    public function deleter() {
        return $this->hasOne(AuthUser::class, "id", "deleted_by");
    }

    /**
     * Returns the Entity that has been flagged
     *
     * Multiple objects can be Flagged (eg, Files) so this is a polymorphic relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function entity() {
        return $this->morphTo();
    }

}

