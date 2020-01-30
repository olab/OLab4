<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class ApplicantReaderScore extends Model
{
    use SoftDeletes;

    const CREATED_AT = "created_date";
    const UPDATED_AT = "updated_date";
    const DELETED_AT = "deleted_date";

    protected $dateFormat = "U";

    protected $connection = "entrada_database";
    protected $table = "admissions_applicant_score";
    protected $primaryKey = "score_id";

    protected $fillable = [
        'value', 'file_id', 'file_type', 'applicant_id', 'reader_id'
    ];

    protected $visible = [
        'score_id', 'value', 'file_type'
    ];

    private static $_maxScore;
    private static $_minScore;


    public function file() {
        return $this->hasOne(ApplicantFile::class, "admissions_applicant_file", "file_id");
    }

    public function applicant() {
        return $this->hasOne(Applicant::class, "applicant_id", "applicant_id");
    }

    public function getGroupAttribute() {
        return $this->reader->group();
    }

    public function reader() {
        return $this->hasOne(Reader::class, "reader_id", "reader_id");
    }

    public function readerType() {
        return $this->reader->type;
    }

    public function readerTypename() {
        return $this->reader->type->shortname;
    }

    /**
     * Verifies that the given score value is within a valid range [default 0 - 100]
     *
     * Based on system settings admissions_min_score and admissions_max_score
     *
     * @param $value int the value to verify
     * @return bool true if the value is within the range (inclusive), false if not
     */
    private static function inScoreRange($value) {
        if (!isset(self::$_minScore) || !isset(self::$_maxScore)) {
            self::$_minScore = Setting::fetch("admissions_min_score") ?: 0;
            self::$_maxScore = Setting::fetch("admissions_max_score") ?: 100;
        }

        return ((self::$_minScore <= $value) && ($value <= self::$_maxScore));
    }

    /**
     * Set the value of this score
     * @param $value int|float the value of the score
     * @return bool returns false if the Score failed to save, true otherwise
     * @throws \Exception if the desired value is not in the valid range (default 0-100, set in Settings)
     */
    public function setScore($value) {

        if (!self::inScoreRange($value)) {
            throw new \Exception("Value ({$value}) must be in range ".self::$_minScore."-".self::$_maxScore);
        }

        $this->value = $value;
        if ($this->save()) {
            return true;
        } else {
            Log::debug("Score value failed to update with error: ".$this->errors);
            return false;
        }
    }


    /**
     * Create a new ApplicantReaderScore object that connects the Reader and ApplicantFile
     * @param Reader $reader the Reader to connect
     * @param ApplicantFile $file the ApplicantFile to connect
     * @param bool $overwrite if true, and an ApplicantReaderScore already exists for these values, it will be overwritten
     * @return self|Model
     */
    public static function newScore(Reader $reader, ApplicantFile $file, $overwrite = false) {

        $score = self::firstOrNew([
            "reader_id" => $reader->reader_id,
            "file_id" => $file->file_id
        ]);

        // If $overwrite is false, this function is Idempotent.
        if ($score->exists && !$overwrite) {
            return $score;
        }

        // TODO We'll get actual FileTypes from an object probably
        switch($file->type) {
            case "letter":
                $file_type = "Letter";
                break;
            case  "sketch":
            default:
                $file_type = "Sketch";
                break;
        }
        // =====

        $score->fill([
            'value' => null,    // null means the score isn't set. 0 means the score is zero.
            'file_id' => $file->file_id,
            'file_type' => $file_type,
            'applicant_id' => $file->applicant_id, //file_id and applicant_id are linked, so this just makes relations easier
            'reader_id' => $reader->reader_id
        ]);
        $score->save();

        return $score;
    }
}
