<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Illuminate\Database\Eloquent\Model;



class ActivityLog extends Model
{

    protected $connection = "entrada_database";
    protected $table = "admissions_activity_log";
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'user_id', 'logged_at','event_type','event_data'
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * Log an activity performed by a user
     *
     * @param $event_type string The type of event (log in, search, etc)
     * @param int $user_id integer the User ID for the user that performed the action
     * @param null $event_data array an associative array of data related to the event. The data in this array will change based on the event type
     * @return ActivityLog the created ActivityLog object
     */
    public static function logEvent($event_type, $user_id = 0, $event_data = null) {
        $event = new ActivityLog([
            "user_id" => $user_id,
            "event_type" => $event_type,
            "event_data" => json_encode($event_data)
        ]);

        $event->save();

        return $event;
    }
}
