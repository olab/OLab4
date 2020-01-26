<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Entrada\Modules\Admissions\Scopes\CycleScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Reader extends Model
{
    use SoftDeletes;
    //
    const CREATED_AT = "created_date";
    const UPDATED_AT = "updated_date";
    const DELETED_AT = "deleted_date";
    protected $dateFormat = 'U';

    protected $connection = "entrada_database";
    protected $table = "admissions_reader";
    protected $primaryKey = "reader_id";

    protected $fillable = [
        "proxy_id", "name", "email", "reader_type_id", "cycle_id", "pool_id", "group_id"
    ];

    protected $visible = [
        "reader_id",
        // "proxy_id",
        "name",
        "email",
        "reader_type_id",
        "cycle_id",
        "group_id",
        "pool_id",
        "type"
    ];

    public function applicants() {
        $groupObj = new ReaderGroup();
        return $this->belongsToMany(Applicant::class, $groupObj->getTable(), "group_id", "applicant_id");
    }

    public function readerGroups() {
        return $this->belongsTo(ReaderGroup::class, "group_id", "group_id");
    }

    public function readerGroup() {
        return $this->hasOne(ReaderGroup::class, "group_id", "group_id");
    }

    public function groups() {
        return $this->readerGroups();
    }
    public function group() {
        return $this->readerGroup();
    }

    public function type() {
        return $this->hasOne(ReaderType::class, "reader_type_id", "reader_type_id");
    }

    /**
     * Takes an array from the CSV row and inserts it as a new Reader
     *      If a reader_id is provided (in the map function), Reader will be found based on reader_id
     *
     * @param $line_array
     * @param $map_function callable a function to map the CSV input to the Reader fillable attributes
     * @return bool
     */
    public static function createFromCSVLine($line_array, $map_function) {

        $data = $map_function($line_array);

        $reader = empty($data['reader_id']) ? new Reader() : Reader::find($data['reader_id']);

        if (empty($reader)) {
            Log::debug("Reader ID set, but reader not found");
            return false;
        }

        $reader->forceFill($data);

        if ($reader->save()) {
            return true;
        } else {
            Log::debug("Reader failed to save with error: ".$reader->error);
            return false;
        }

    }

    /**
     * Add the Pool param locally
     * Invoking this scope will filter Readers through the pool_id if set
     *
     * @param $query
     * @param null $user
     * @return bool
     */
    public function scopeByPool($query, $user = null) {
        $user = empty($user) ? CycleRole::admissionsUserFromUser(Auth::user()) : $user;

        // Check if the pool_id parameter is set in the request
        $request = app(Request::class);
        $paramPoolSet = $request->has("pool_id");

        // If no, but we're an admin, return everything
        if (empty($paramPoolSet) && $user->isAdmin()) {

            return $query;
        } elseif (empty($paramPoolSet)) {
            // filter by User's default Pool
            $pool_id = $user->getPoolID();
            if ($pool_id) {
                return $query->where($this->getTable().".pool_id", "=", $pool_id);
            } else {
                // this shouldn't be null ever, so this clears the query and returns nothing.
                return $query->whereNull($this->getTable().".reader_id");
            }
        } else {
            // else try to use the request param
            // note: this does not validate that the pool exists
            return $query->where($this->getTable().".pool_id", "=", $request->input("pool_id"));
        }
    }

    /**
     * Add the CycleScope globally to this model
     */
    protected static function boot() {
        parent::boot();
        static::addGlobalScope(new CycleScope());
    }

}
