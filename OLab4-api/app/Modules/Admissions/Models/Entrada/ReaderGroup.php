<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Entrada\Modules\Admissions\Scopes\CycleScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReaderGroup extends Model
{
    use SoftDeletes;

    const CREATED_AT = "created_date";
    const UPDATED_AT = "updated_date";
    const DELETED_AT = "deleted_date";
    protected $dateFormat = 'U';

    protected $table = "admissions_reader_group";
    protected $primaryKey = "group_id";
    protected $connection = "entrada_database";

    protected $fillable = ['group_type', 'group_name', 'cycle_id', 'pool_id'];
    protected $visible = ['group_id', 'group_type', 'group_name', 'cycle_id', 'pool_id', 'readers'];

    public function readers() {
        return $this->hasMany(Reader::class, "group_id", "group_id");
    }

    public function applicants() {
        return $this->belongsToMany(Applicant::class, "admissions_reader_group_applicant", "group_id", "applicant_id");
    }

    public function getReadersAttribute() {

        return $this->getRelationValue('readers')->keyBy('reader_type')->toArray();
    }


    /**
     * Attempt to create new Score objects for each Reader and File in the Group
     */
    public function generateScores() {
        foreach ($this->applicants as $applicant) {
            foreach ($applicant->files as $file) {

                foreach ($this->readers as $reader) {
                    ApplicantReaderScore::newScore($reader, $file);
                }
            }
        }
    }

    /**
     * Update Reader array with IDs in readerIds array
     *
     * @param $readerIds array the ids of the readers to attach to this Group
     * @param bool $detach if true, readerIds will overwrite existing relationships, else the items will be added
     */
    public function syncReaders($readerIds, $detach = true) {

        if ($detach) {
            $this->readers()->dissociate();
            $this->readers()->associate($readerIds);
        } else {
            $this->readers()->associate($readerIds);
        }
    }

    /**
     * Assign Applicants to Groups, as evenly as possible based on the number of available Groups, Readers and Applicants
     *
     * @param bool $detach
     * @return array|bool
     */
    public static function assignApplicants($detach = true) {

        // TODO If detach is false, we should only grab applicants that don't already have groups.
        $applicants = Applicant::fileReview()->get(["applicant_id"])->toArray();
        $groups = self::all()->all();

        // This is mostly to avoid divide by zero errors, but if anyone asks, its about performance...
        if (empty($groups) || empty($applicants)) {
            return false;
        }

        // Get the number of applicants and groups, and the quotient of the two
        $appCount = count($applicants);
        $groupCount = count($groups);
        $apps_per_group = ceil($appCount/$groupCount);

        $chunks = array_chunk($applicants, $apps_per_group);
        foreach ($chunks as $chunk) {

            // Because of the way chunk works, there may be empty chunks.
            //      Unless we want to detach previous assignments, we can skip the rest of this
            if (!$detach && empty($chunk)) {
                continue;
            }

            $group = array_shift($groups);
            if ($detach) {
                // Add the new ids to this groups. I think this should be the default
                $group->applicants()->sync($chunk);
            } else {
                // Remove the old ones and reassign
                $group->applicants()->syncWithoutDetaching($chunk);
            }

            $group->generateScores();
        }

        // return some stats!
        return [
            "applicants" => $appCount,
            "groups" => $groupCount,
            "apps_per_group" => $apps_per_group
        ];
    }

    /**
     * Takes an array of attributes and attempts to update a ReaderGroup
     *  if no ID is specified, a new ReaderGroup is created
     *
     * Also syncs readers if a sub-array of readers is set
     *
     * @param $data []
     * @return mixed Returns successfully saved model, or false on failure
     */
    public static function saveWithArray($data) {

        $model = empty($data['group_id']) ? new self() : self::find($data['group_id']);

        // Model will be empty if the ID is set but the item does not exist
        if (empty($model)) {
            Log::debug("Reader Group with ID: ".$data['group_id']. " does not exist");
            return false;
        }

        $model->fill($data);

        if (!$model->save()) {
            Log::debug("Reader Group failed to save with message: ".$model->errors);
            return false;
        }

        if (!empty($data['readers'])) {
            $model->syncReaders($data['readers']);
        }

        return $model;
    }

    /**
     * Add the CycleScope globally to this model
     */
    protected static function boot() {
        parent::boot();

        static::addGlobalScope(new CycleScope());
    }

}
