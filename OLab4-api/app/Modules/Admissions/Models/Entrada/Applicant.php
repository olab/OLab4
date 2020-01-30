<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Entrada\Modules\Admissions\Scopes\CycleScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class Applicant extends Model
{
    use Authorizable, SoftDeletes;

    const ADVANCING = 'A';
    const PENDING = 'P';
    const REJECTED = 'R';
    const NO_STATUS = '';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date';
    protected $dateFormat = 'U';
    //
    protected $connection = "entrada_database";
    protected $table = "admissions_applicant";
    protected $primaryKey = "applicant_id";


    protected $fillable = [
        "year", "pool_id", "cycle_id", "reference_number", "given_name", "surname", "sex", "birthdate", "age", "total_credits",
        "cumulative_avg", "average_last_2_years", "grad_indicator", "aboriginal_status", "apply_to_mdphd",
        "citizenship", "mcat_total",
        "bbfl", // biological and biochemical foundations of living systems
        "cpbs", // chemical and physical foundations of biological sciences
        "cars", // critical analysis and reasoning skills
        "psbb", // psychological, social and biological foundations of behaviour

        'local_address1',
        'local_address2',
        'local_address3',
        'local_address4',
        'local_telephone',
        'email_address',
        'last_university_name',

        "has_reference_letters", "has_sketch_review", "application_status", 'emp_id',
        "last_roo_num", "last_hom_num", "last_crs_num", "last_cor_num",
        "last_skp_num", "last_abs_num", "last_aca_num",

        // These fields are unset before saving
        'mcat_sum_numeric_scores',
        'mcat_verbal_reasoning',
        'mcat_physical_science',
        'mcat_writing_sample',
        'mcat_biological_science'
    ];

    /*protected $visible = ['courses', 'institutions'];*/

    protected $hidden = ["last_roo_num", "last_hom_num", "last_crs_num", "last_cor_num",
        "last_skp_num", "last_abs_num", "last_aca_num", self::CREATED_AT, self::UPDATED_AT, self::DELETED_AT, "scores"];


    protected $appends = ['scores'];

    private static $pools;
    private static $log_entity = "applicant";
    private static $recent_models;

    public function courses() {
        return QUEXCRSData::where([
            "reference_number" => $this->reference_number,
            "year" => $this->year,
            "file_number" => $this->last_crs_num
        ])->get();
    }

    public function institutions() {
        return QUEXACAData::where([
            "reference_number" => $this->reference_number,
            "year" => $this->year,
            "file_number" => $this->last_aca_num
        ])->get();
    }

    public function groups() {
        return $this->belongsToMany(ReaderGroup::class, "admissions_reader_group_applicant", "applicant_id", "group_id");
    }

    public function group() {
        return $this->groups()->first();
    }

    public function readerGroups() {
        return $this->belongsToMany(ReaderGroup::class, "admissions_reader_group_applicant", "applicant_id", "group_id");
    }

    public function readerGroup() {
        return $this->readerGroups->first();
    }

    public function readers() {
        //
        $group = $this->group();
        return ($group) ? $group->readers : [];
    }

    /*public function scores() {
        return $this->hasMany(ApplicantReaderScore::class, "applicant_id", "applicant_id");

    }*/

    /**
     * TODO This can either be deleted when Scores are handled properly or used to format Scores by filetype
     *
     * @return array
     */
    public function getScoresAttribute() {
        return [
            1 => [
                'Sketch'    => 0,
                'Letter'    => 0,
            ],
            2 => [
                'Sketch'    => 0,
                'Letter'    => 0,
            ]
        ];
    }

    public function files() {
        return $this->hasMany(ApplicantFile::class, "applicant_id", "applicant_id")->with("flags");
    }

    /**
     * Get related files based on type and/or subtype. Note, type and subtype are case-insensitive
     *
     * @param null| $type The type category of the file (if empty, fetches all of subtype)
     * @param null|string $subtype the subtype of the file (if empty, fetches all of type)
     * @param bool $first if true, returns the first File, otherwise returns Collection
     * @return \Illuminate\Database\Eloquent\Collection|Model|null|static|static[]
     */
    public function fileWithType($type = null, $subtype = null, $first = false) {
        $where = [];

        if (!empty($type)) {
            $where['type'] = strtolower($type);
        }

        if (!empty($subtype)) {
            $where['subtype'] = strtolower($subtype);
        }

        $files = $this->files()->where($where);

        return $first ? $files->get()->first() : $files->get();
    }

    /**
     * Get the Academic Reference files for this Applicant
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function references() {

        return $this->files()->where([
            'type' => "academic",
            'subtype' => "references"
        ])
            // We only the most recent of each file_number
            ->orderBy("created_date", "DESC")
            ->groupBy("file_num")
            ->get();
    }

    /**
     * Returns all audit log elements related to this Applicant
     */
    public function auditLog() {
        AuditLog::where([
            "entity" => self::$log_entity,
            "sub_field" => $this->id,
        ])->get();
    }

    /**
     * Returns the attributes that have been manually overridden by the user
     *  For use in the QUEX loading process
     *
     * @param bool $as_keys if true, the edited fields are the keys of the return array, if false, they are the values
     * @return array the array of attributes that have been edited
     */
    public function editedFields($as_keys = false) {
        $rows = AuditLog::where([
            "entity" => self::$log_entity,
            "action" => "edit",
            "sub_field" => $this->applicant_id,
        ])->orderBy("log_id", "DESC")->groupBy("field")
            ->pluck("field")->toArray();

        if ($as_keys) {
            $rows = array_flip($rows);
        }

        return $rows;
    }


    public function scopeFileReview($query) {

        return $query->where([
            "application_status" => self::ADVANCING
        ])->with(["files", "files.flags", "files.flags.flagger"]);
    }

    /**
     * Add the Pool param locally
     * Invoking this scope will filter Applicants through the pool_id if set
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
            // if no, and we're no an admin, return nothing

            $pool_id = $user->getPoolID();
            if ($pool_id) {
                return $query->where($this->getTable().".pool_id", "=", $pool_id);
            } else {
                // this shouldn't be null ever, so this clears the query and returns nothing.
                return $query->whereNull($this->getTable().".applicant_id");
            }
        } else {
            // else try to use the request param
            // note: this does not validate that the pool exists
            return $query->where($this->getTable().".pool_id", "=", $request->input("pool_id"));
        }
    }


    /**
     * Returns a sub-array of $data without values that:
     *  a.) aren't fillable
     *  b.) have been manually edited by a user, if $preserve_edits is true
     *
     * Used by the QUEX data loading process, so data isn't overwritten
     *
     * @param $data array the array of fields we are 'trying' to update
     * @param $preserve_edits boolean if true, custom user edits are not overwritten by data imports
     * @return array the array of fields we are allowed to update
     */
    private function loadableData($data, $preserve_edits = true) {
        $data = $this->fillableFromArray($data);

        if ($preserve_edits) {
            // This can be replaced by any method that returns an array of edited fields!
            $edited_fields = $this->editedFields(true);

            $data = array_diff_key($data, $edited_fields);
        }

        return $data;
    }

    /**
     * Updates and Saves this Applicant's data with fields provided in $data
     * Dirty attributes are logged
     *
     * @param $data array The data we are trying to update
     * @return bool true if the save was successful, false if not
     */
    public function updateData($data) {

        $cycle_id = Cycle::currentCycle()->cycle_id;

        $old_attrs = $this->attributes;
        $this->fill($data);
        $save = $this->save();
        // Only set the fields edited if the save is successful!
        if ($save) {
            foreach ($data as $key => $val) {

                if ($val == $old_attrs[$key]) {
                    continue;
                }

                // Create an EditedField object for this field, if one does not exists
                // TODO remove this when no longer needed (usurped by AuditLog)
                ApplicantEditedField::firstOrCreate([
                    "table_name" => $this->table,
                    "column_name" => $key,
                    "row_id" => $this->applicant_id
                ], [
                    'edit_date' => strtotime("now")
                ]);

                // Log this edit in the audit trail!
                AuditLog::logEdit([
                    "entity" => self::$log_entity,
                    "action" => "edit",
                    "field" => $key,
                    "old_value" => $old_attrs[$key],
                    "new_value" => $val,
                    "page" => "",           // TODO edit this when you find out what it means
                    "page_section" => "",   // TODO edit this when you find out what it means
                    "sub_field" => $this->applicant_id,
                    "cycle_id" => $cycle_id
                ]);
            }
        }

        return $save;
    }

    private function set($key, $val, $override = false) {
        $this->$key = $val;
    }

    public function addFile($filepath, $type = "none", $subtype = "", $data = []) {
        $data = array_merge([
            "applicant_id" => $this->applicant_id,
            "type" => $type,
            "subtype" => $subtype
        ], $data);

        array_walk($data, function(&$val) {
            $val = is_string($val) ? strtolower($val) : $val;
        });


        $file = ApplicantFile::firstOrNew($data);

        echo json_encode($file)."\n";

        // At some point, more user-friendly names might be required. For now, just set the filepath.
        $file->filename = $file->path = $filepath;

        if (!$file->save()) {
            Log::debug("File failed to save with error:" . $file->error);
        }
    }
    /**
     * Assigns the current user to one of the given pools in the following order of precedence:
     * International, Aboriginal, MDPhD, MD
     *
     * @param $pools Pool[] the array of active Pools
     * @return bool true if the pool was assigned properly, false if not (usually because the pools weren't loaded)
     */
    private function assignPool($pools) {
        if (!empty($this->citizenship) && $this->citizenship !== "CANADA" && isset($pools['InternationalPool'])) {
            $this->pool_id = $pools['InternationalPool']->pool_id;
            return true;
        } elseif ($this->aboriginal_status == "Y" && isset($pools['AboriginalPool'])) {
            $this->pool_id = $pools['AboriginalPool']->pool_id;
            return true;
        } elseif ($this->apply_to_mdphd == "Y" && isset($pools['MDPhDPool'])) {
            $this->pool_id = $pools['MDPhDPool']->pool_id;
            return true;
        } elseif (isset($pools['MdPool'])) {
            $this->pool_id = $pools['MdPool']->pool_id;
            return true;
        }

        return false;
    }


    /**
     * Fetches the available pools for the current admissions cycle
     *
     * In the interest of being resource-conservative, a static array is created the first time this function is called
     *      and that array is returned for every subsequent call
     *
     * @return Pool[] the array of available Pools
     */
    private static function getPools() {
        if (!isset(self::$pools)) {
            $pool_collection = Pool::where(['cycle_id' => Setting::fetch('admissions_cycle')])->get();
            self::$pools = [];
            foreach ($pool_collection as $pool) {
                self::$pools[$pool->classname] = $pool;
            }
        }

        return self::$pools;
    }

    /**
     * Assigns the Applicant status based on the condition of his or her mcat scores, GPA and letters
     */
    public function assessStatus() {

        // If an Applicant's MCAT score or GPA is 0, they are "pending", otherwise the status is blank.
        if  (
            (empty($this->application_status) || $this->application_status == "P")
            && (empty($this->mcat_total) && empty($this->cumulative_avg))
        ) {
            $this->application_status = "P";
        } elseif ($this->application_status == "P") {
            // If the status WAS pending, but new values have been set, we can return this Applicant to blank
            $this->application_status = "";
        }
    }

    /**
     * Translates scores from the QUEX data into our Applicant format.
     *
     * Any business logic that needs to be applied to the scores should be done here.
     */
    private function translateScores() {
        // TODO This section will change when the schema does

        $this->bbfl = empty($this->mcat_biological_science) ? 0 : $this->mcat_biological_science;
        $this->cpbs = empty($this->mcat_biological_science) ? 0 : $this->mcat_biological_science;
        $this->cars = empty($this->mcat_verbal_reasoning) ? 0 : $this->mcat_verbal_reasoning;
        $this->psbb = empty($this->mcat_physical_science) ? 0 : $this->mcat_physical_science;
        $this->mcat_total = empty($this->sum_numeric_scores) ? 0 : $this->sum_numeric_scores;

        // These aren't saved or used. We can unset them
        unset($this->mcat_sum_numeric_scores,
            $this->mcat_verbal_reasoning,
            $this->mcat_physical_science,
            $this->mcat_writing_sample,
            $this->mcat_biological_science);
    }

    /**
     * Converts the averages from implied decimal form to single decimal form
     */
    private function normalizeAverages() {
        while ($this->average_last_2_years > 10) {
            $this->average_last_2_years /= 10;
        }

        while ($this->cumulative_avg > 10) {
            $this->cumulative_avg /= 10;
        }
    }

    /**
     * Calculates the total number of relevant credits, accumulated from the most recent QuexCRS data
     */
    private function calculatecredits() {
        $institutions = $this->institutions();

        $total = 0;
        foreach ($institutions as $institution) {
            $total += $institution->institution_length;
        }

        $this->total_credits = ($total/10);  // INSLENGTH comes in with implied decimal, so we'll divide it
    }

    private function checkFiles() {

        // Sketch review check


        // Reference check!
        $refs = $this->references();
        $refs_required = Setting::fetch("admissions_refs_required");
        $this->has_reference_letters = (empty($refs_required) && count($refs) < $refs_required);
    }

    /**
     * Loads all loadable data from the $data_model with congruent field names
     *  Also executes special class methods depending on the type of Quex file passed
     *
     * @param $data_model QuexData the QUEXData file with attributes to save
     * @param $type string the 3-character type of the data_model
     * @param $num integer the
     * @return bool returns true if changes were made, false if not
     */
    public function loadData($data_model, $type, $num) {
        $column = "last_".strtolower($type)."_num";

        if ($this->$column >= $num) {
            return false;
        }

        $valid_data = $this->loadableData($data_model->getAttributes());

        $this->fill($valid_data);
        $this->$column = $num;

        $this->processAdditional($type);

        return true;
    }

    public function processAdditional($key, $save = false) {
        // Reasonably, special things should only happen for certain file types
        switch (strtolower($key)) {
            case "aca" :
                $this->calculatecredits();
                break;
            case "roo" :
                $this->assignPool(self::getPools());
                $this->translateScores();
                $this->assessStatus();
                $this->normalizeAverages();
                break;
            case "file":
                $this->checkFiles();
                break;
            default:
                break;
        }

        if ($save) {
            $this->save();
        }
    }

    /**
     * Attempts to load data from the QUEXData object into the Applicant object with the corresponding reference_number
     *  If one does not exist, a new Applicant is created.
     *
     * @param $data_model QuexData the data object being inserted into the Applicant model
     * @param $type string the 3-character representation of the data model type (roo, aca, etc)
     * @param $num integer the number of the quex file being loaded. If $num is lower than the last_<type>_num of the applicant object, it will be ignored
     * @return bool
     */
    public static function loadSingle($data_model, $type, $num) {

        $column = "last_".strtolower($type)."_num";
        $applicant =  Applicant::where("reference_number", "=", $data_model->reference_number)
                            ->orderBy($column, "DESC")->first();

        // There are no applicants with this reference number
        if (empty($applicant)) {
            $applicant = new Applicant();
        } elseif ($applicant->$column >= $num) {
            return true;
        }

        $applicant->loadData($data_model, $type, $num);

        if ($applicant->save()) {
            return true;
        } else {
            Log::debug("Applicant failed to save with message: ". $applicant->errors);
            return false;
        }
    }

    /**
     * Attempts to update Applicant data based on the array of $quex_models
     *
     * @param $quex_models QUEXData[] the array of models
     * @param $type string the 3-character type of the models
     */
    public static function loadAll($quex_models, $type) {
        self::fetchRecent();

        foreach($quex_models as $quex) {
            $applicant = empty(self::$recent_models[$quex->reference_number])
                ? new Applicant()
                : self::$recent_models[$quex->reference_number];

            $applicant->loadData($quex, $type, $quex->file_number);
            self::$recent_models[$quex->reference_number] = $applicant;
        }
    }

    /**
     * Generates an array based on Request variables to limit or extens
     * @param $request
     */
    public static function responseFormat(Request $request) {
        $pluckArr = [
            "year", "pool_id", "reference_number", "given_name", "surname", "sex", "birthdate", "age", "total_credits",
            "citizenship", 'local_address1', 'local_address2','local_address3', 'local_address4','local_telephone',
            'email_address', 'last_university_name', "has_reference_letters", "has_sketch_review", "application_status",
            'emp_id'
        ];

        if ($request->get("grades", true)) {
            $pluckArr = array_merge($pluckArr, ["cumulative_avg", "average_last_2_years", "grad_indicator",
                "mcat_total","bbfl","cpbs","cars", "psbb"]);
        }

        if ($request->get("files", false)) {
            $pluckArr = array_merge($pluckArr, ["files", "files.flags"]);
        }

        return $pluckArr;
    }


    /**
     * Generates an array based on Request variables to limit or extens
     * @param $request
     */
    public static function responseWith(Request $request) {
        $pluckArr = [];

        if ($request->get("files", false)) {
            $pluckArr = array_merge($pluckArr, ["files", "files.flags", "files.flags.flagger"]);
        }

        return $pluckArr;
    }

    public static function fileReviewParams() {
        return ["applicant_id", "given_name", "surname", "pool_id", "cycle_id", "grad_indicator"];
    }

    /**
     * Performs a save on all models in the $recent_models static array
     */
    public static function saveRecent() {
        foreach (self::$recent_models as $applicant) {
            if (empty($applicant->cycle_id)) {
                $applicant->cycle_id = Setting::fetch("admissions_cycle");
            }

            $applicant->save();
        }
    }


    /**
     * Fetches all current Applicants and stores them in a static array indexed by reference number
     * This is performance precaution when loading data
     * @return bool
     */
    private static function fetchRecent() {
        if (empty(self::$recent_models)) {
            $models = self::get();
            $arr = [];
            foreach ($models as $model) {
                $arr[$model->reference_num] = $model;
            }

            self::$recent_models = $arr;
        }

        return true;
    }

    /**
     * Add the CycleScope globally to this model
     */
    protected static function boot() {
        parent::boot();

        static::addGlobalScope(new CycleScope());
    }

}
