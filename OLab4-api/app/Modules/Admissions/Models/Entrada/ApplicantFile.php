<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ApplicantFile extends Model
{
    use SoftDeletes;

    //
    const CREATED_AT = "created_date";
    const UPDATED_AT = "updated_date";
    const DELETED_AT = "deleted_date";
    protected $dateFormat = 'U';

    protected $connection = "entrada_database";
    protected $table = "admissions_applicant_file";
    protected $primaryKey = "file_id";

    protected $fillable = [
        "cycle_id", "filename", "file_num", "path", "applicant_id", "type", "subtype", "doc_id", "additional"
    ];
    protected $visible = [
        "file_id", "applicant_id", "filename", "file_num",
                "type", "subtype", "additional", "doc_id", "flags"
    ];

    /**
     * @var string The directory where we store our demo files
     */
    private static $demo_dir = __DIR__ . "/../../Resources/DemoData";

    public function flags() {
        return $this->morphMany(Flag::class, "entity");
    }

    public function applicant() {
        return $this->belongsTo(Applicant::class, "applicant_id", "applicant_id");
    }


    /**
     * Copy the demo files from the demo_dir to storage if they don't exist then return the array
     *
     * This method is used during the demo data generation process
     *
     * @return array the array of demo filenames in storage
     */
    public static function demoFiles() {

        $path = self::$demo_dir . "/Files/";

        $demoFiles = [
            "auto_sketch" => "demo_autobiographical_sketch.pdf",
            "detail_sketch" => "demo_detail_sketch.pdf",
            "datasheet" => "demo_data_sheet.pdf",
            "verifer" => "demo_verifier.pdf",
            "reference" => "demo_other.pdf",
            "other" => "demo_other.pdf"
        ];

        foreach ($demoFiles as $file) {
            if (file_exists($path.$file)) {
                File::copy($path.$file, self::basePath() . "/" . $file);
            } else {
                Log::debug("Demo File {$file} does not exist");
            }
        }

        return $demoFiles;
    }

    /**
     * The qualified path for this File
     *
     * @return string the path
     */
    public function filePath() {
        return self::basePath() . "/" .$this->path;
    }

    /**
     * The full base path (where files are stored)
     *
     * @return string the path
     */
    private static function basePath() {
        return storage_path("admissions/unzipped");
    }

}
