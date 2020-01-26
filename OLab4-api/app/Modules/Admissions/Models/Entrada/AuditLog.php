<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLog extends Model
{

    protected $connection = "entrada_database";
    protected $table = "admissions_edit_log";
    protected $primaryKey = "log_id";

    public $timestamps = false;

    protected $fillable = [
        'proxy_id',
        'action',
        'entity',
        'field',
        'old_value',
        'new_value',
        'page',
        'page_section',
        'sub_field',
        'cycle_id',
        'logged_date'
    ];

    public static function logEdit($data) {

        $log = new self();
        $log->fill($data);

        $log->proxy_id = Auth::user()->id;
        $log->logged_date = strtotime("now");

        if (!$log->save()) {
            Log::debug("Admission Log failed to save with message: ".$log->errors);
        }

        return $log;
    }

}
