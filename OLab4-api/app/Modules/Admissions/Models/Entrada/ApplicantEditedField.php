<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Log;

class ApplicantEditedField extends Model
{
    //
    protected $connection = "entrada_database";
    protected $table = "admissions_edited_fields";
    protected $primaryKey = "edited_field_id";

    protected $fillable = [
        'table_name',
        'row_id',
        'column_name',
        'edit_date'
    ];

    public $timestamps = false;

    protected $dateFormat = 'U';

    /**
    public function record() {

    }
     */
}
