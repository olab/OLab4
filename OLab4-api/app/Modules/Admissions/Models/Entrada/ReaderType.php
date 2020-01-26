<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Entrada\Modules\Admissions\Scopes\CycleScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReaderType extends Model
{

    use SoftDeletes;

    const CREATED_AT = "created_date";
    const UPDATED_AT = "updated_date";
    const DELETED_AT = "deleted_date";
    protected $dateFormat = 'U';

    protected $connection = "entrada_database";
    protected $table = "admissions_reader_type";
    protected $primaryKey = "reader_type_id";

    protected $fillable = [
        "name", "shortname", "cycle_id", "pool_id"
    ];

    protected $visible = [
        'reader_type_id', 'cycle_id', 'pool_id', 'name', 'shortname'
    ];

    public function readers() {
        return $this->hasMany(Reader::class, "reader_type", "shortname");
    }

    /**
     * This method takes a text string and attempts to find an unused slug in the database
     *
     * @param $text string the string to convert to slug and insert (possibly with suffix)
     * @param null|integer $cycle_id the cycle to which this ReaderType is assigned, defaults to current cycle
     * @return string
     */
    public static function newSlug($text, $cycle_id = null) {

        $suff = ""; // lets try the suffix-less slug first
        $slug = self::slugify($text);
        // Kind of tedious, but
        do {
            $newSlug = $slug . $suff;
            $readerType = ReaderType::where([
                "cycle_id" => empty($cycle_id) ? Cycle::currentCycleID() : $cycle_id,
                "shortname" => $newSlug
            ])->first();

            $suff = empty($suff) ? 1 : intval($suff) + 1; // continue to increment suffix until we find one unused
        } while (!empty($readerType));

        return $newSlug;
    }

    /**
     * Create a slug version of the given text
     *
     * @param $text string the text to slugify
     * @return mixed|string the slug
     */
    private static function slugify($text) {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Add the Pool param locally
     * Invoking this scope will filter ReaderTypes through the pool_id if set
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
                return $query->whereNull($this->getTable().".reader_type_id");
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
