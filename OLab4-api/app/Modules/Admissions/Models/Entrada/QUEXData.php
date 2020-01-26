<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Carbon\Carbon;
use Chumper\Zipper\Zipper;
use Entrada\Modules\Admissions\Libraries\File\Parser;
use Entrada\Modules\Admissions\Libraries\File\Schema\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


class QUEXData extends Model
{

    /**
     *
     * @var string The directory where we store our schema JSON files
     */
    private static $schema_dir = __DIR__ . "/../../Resources/Schemas";
    /**
     * @var string A sub-folder of our base storage path where we upload our OMSAS ZIP files
     */
    private static $zip_folder = "admissions";

    private static $decrypt_folder = "decrypted";
    /**
     * @var string a sub-folder of the zip folder (above) to store ZIP files that have been downloaded, but not yet decrypted
     */
    private static $download_folder = "encrypted";

    /**
     * @var string a sub-folder of the zip folder (above) to put the text files after they have been unzipped
     */
    private static $unzip_to = "unzipped";
    /**
     * @var bool Delete zip files once they have been unzipped? Set to false while testing
     */
    private static $remove_zip_after_load = false;
    /**
     * @var bool Delete text files once they have been parsed? Set to false while testing
     */
    private static $remove_txt_after_load = false;

    private static $quex_complete = [
        "ABS" => 0,
        "ACA" => 0,
        "COR" => 0,
        "CRS" => 0,
        "HOM" => 0,
        "ROO" => 0,
        "SKP" => 0,
    ];

    private static $latest_models = [];


    /**
     * Fetch files from a server!
     *
     * @param $host string the [s]ftp hostname
     * @param $remote_file_path string the directory path on the server
     * @param $username string the login username
     * @param $password string the login password
     * @param int $port string the connection port (default: 22)
     * @param null|string $decrypt_key If empty, files are not decrypted at all. If string, decryption is attempted
     * @throws \Exception
     */
    public static function fetchFilesFromServer($hostData, $remote_file_path = "/",$decrypt_key = null) {

        list($host, $username, $password, $port) = $hostData;

        $connection = NULL;
        $local_file_path = self::getDownloadFolder();

        $connection = \ssh2_connect($host, $port);
        if (!$connection) {
            throw new \Exception("Could not connect to $host on port $port");
        }

        $auth = \ssh2_auth_password($connection, $username, $password);
        if (!$auth) {
            throw new \Exception("Could not authenticate with username $username and password ");
        }

        $sftp = \ssh2_sftp($connection);
        if (!$sftp) {
            throw new \Exception("Could not initialize SFTP subsystem.");
        }

        $files    = scandir("ssh2.sftp://" . intval($sftp) . $remote_file_path);
        $num = 0;
        if (!empty($files)) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    \ssh2_scp_recv($connection, "$remote_file_path/$file", "$local_file_path/$file");
                    $num ++;
                }
            }
        }
        $connection = NULL;
        if ($decrypt_key) {
            self::decryptFiles($decrypt_key);
        }

        return $num;
    }

    /**
     * Decrypts pgp files using the decrypt_key and moves them, if a folder is specified
     *
     * @param string $decrypt_key the filename of the keyfile, or the key itself
     * @param bool $move_unencrypted if true, moves unencrypted files as well. If false, only encrypted files are moved (after decryption)
     * @param string|null $source_folder the folder where we have placed our encrypted files. If null, uses class's 'download_folder'
     * @param string|null $destination the folder where the encrypted files will be moved after decryption. If null, uses class's 'zip_folder'
     */
    public static function decryptFiles($move_unencrypted = true, $source_folder = null, $destination = null) {
        $source_folder = is_null($source_folder) ? self::getDownloadFolder() : $source_folder;
        $destination = is_null($destination) ? self::getZipFolder() : $destination;

        $all = glob($source_folder . "/*.*", GLOB_NOSORT);
        $pgp = glob($source_folder . "/*.{gpg,pgp}", GLOB_BRACE | GLOB_NOSORT);

        $gpg = ENV("GPG_LOCATION", config("entrada.admissions.gpg_location",'gpg'));
        $passphrase = ENV("GPG_PASSPHRASE", config("entrada.admissions.gpg_passphrase", null));

        if (is_null($passphrase)) {
            Log::debug("Attempted decryption with no passphrase");
            return false;
        }

        // First we decrypt and move all PGP files
        foreach ($pgp as $encrypted_file) {
            $newname = substr($encrypted_file, 0, -4);
            $unencrypted_file = str_replace($source_folder, $destination, $newname);

            // echo "echo $passphrase | $gpg --batch --yes --pinentry-mode loopback --passphrase-fd 0 -o $unencrypted_file -d $encrypted_file\n\n";
            $response = shell_exec("echo $passphrase | $gpg --batch --yes --pinentry-mode loopback --passphrase-fd 0 -o $unencrypted_file -d $encrypted_file");
            //$response = shell_exec("$gpg --batch --passphrase $passphrase -o $unencrypted_file -d $encrypted_file");
        }

        // There may be files here that aren't encrypted, we can just move them
        if ($move_unencrypted) {
            foreach (array_diff($all, $pgp) as $file) {
                // TODO Move files!
            }
        }

        return true;
    }

    /**
     * Extracts all .zip files in storage directory noted by the class variable $zipFolder
     *  into the sub-folder <storage_path/$zipFolder/$unzipTo>
     *
     * Scans said sub-folder for all .txt files and runs the parser
     *
     */
    public static function unzipFiles() {

        $zip_folder = self::getZipFolder();
        $unzip_folder = self::getUnzipFolder();

        /*
        * Unzip all .zip files in our zip_folder and extract text files to our unzip_folder
        * if self::$removeZIPAfterLoad is true, delete the zip file after extracting
        */

        $globArr = glob($zip_folder . "/*.zip", GLOB_NOSORT);
        if (empty($globArr)) {
            return 0;
        }

        foreach ($globArr as $zip_path) {
            $unzipper = new Zipper();

            $unzipper->make($zip_path)
                ->extractTo($unzip_folder);

            if (self::$remove_zip_after_load) {
                unlink($zip_path);
            }
        }

        return count($globArr);
    }

    /**
     * Scan and attempt to load, parse and save all data from QUEX text files in the unzip_folder
     */
    public static function scanFiles() {

        // locate folder to which we have unzipped our TXT files
        $unzip_folder = self::getUnzipFolder();

        // If we sort the files using natural order and reverse the array, we'll get the highest numbers first
        //      this will save us a lot of processing time, because most of the files can be skipped
        $quexfiles = glob($unzip_folder . "/*.txt", GLOB_NOSORT);
        natsort($quexfiles);
        $quexfiles = array_reverse($quexfiles);

        if (empty($quexfiles)) {
            return 0;
        }

        /*
         * Grab an array of all .txt files in our folder
         * Fetch the 3-character file type and integer file number
         *
         * For each file:
         *  Find the related Parser function for the file type
         *  Open file
         *  Pass contents of file to Parser line-by-line
         *
         * If self::$removeTXTAfterLoad is true, delete the text files after parsing
         *
         */
        foreach ($quexfiles as $file) {

            $file_matches = [];
            // fetch type and number
            preg_match("/QUEX([A-Z]{3})([0-9]+)/", $file, $file_matches);
            $file_type = empty($file_matches[1]) ? "ZZZ" : $file_matches[1];
            $file_num = empty($file_matches[2]) ? -1 : intval($file_matches[2]);

            if ($file_num < self::$quex_complete[$file_type]) {
                continue;
            }

            $schema_file_name = self::$schema_dir . "/QUEX$file_type.schema.json";
            if (file_exists($schema_file_name)) {
                $parse_schema = new Schema($schema_file_name);

                // get parser based on type
                $parser = new Parser($parse_schema);
                $parse_function = "parse";
                $model_name = 'Entrada\Modules\Admissions\Models\Entrada\QUEX' . $file_type . 'Data';

                // read file line by line
                $file_handle = fopen($file, "r");
                while (!feof($file_handle)) {

                    $line = fgets($file_handle);

                    // Empty lines turn out to be problematic and pretty common. So we skip 'em
                    if (empty(trim($line))) {
                        continue;
                    }

                    // CALL THE PARSER!
                    $data = $parser->$parse_function($line);
                    $model_name::updateData($data, $file_num);
                }
                fclose($file_handle);
                self::$quex_complete[$file_type] = $file_num;
            } else {
                // TODO do something here if the schema doesn't work.
            }

            if (self::$remove_txt_after_load) {
                // Remove text files after they have been parsed
                unlink($file);
            }
        }

        try {
            foreach (self::$latest_models as $type_arr) {
                foreach ($type_arr as $quex_item) {

                    if (is_subclass_of($quex_item, QuexData::class)) {
                        if ($quex_item->save()) {

                        } else {
                            // TODO DEBUG ERROR OR SOMETING
                        }
                    } else {
                        // TODO DEBUG ERROR OR SOMETING
                        echo get_class($quex_item)." is not a subclass of ".QuexData::class."<br>";
                    }
                }
            }
        } catch (\Exception $e) {
            // TODO Do something if a row failed to save?
            echo "Skipping line: {$e->getMessage()} <br>";
        }

        return count($quexfiles);
    }


    /**
     * Loads the most recent data from the quex tables into the Applicant table
     *
     * TODO I don't like that this file has to rely on the controller to load the child classes but I don't want this file to know they exist. Figure that out.
     *
     * @return bool
     */
    public static function loadApplicants() {

        $types = ["abs", "aca", "cor", "crs", "hom", "roo", "skp"];

        foreach ($types as $type) {
            $class = 'Entrada\Modules\Admissions\Models\Entrada\QUEX'.strtoupper($type).'Data';
            $key_model = new $class();
            $models = self::$latest_models[get_class($key_model)];
            if (empty($models)) {
                Log::debug("No class of type $class found");
                continue;
            }

            Applicant::loadAll($models, $type);
        }

        Applicant::saveRecent();

        return true;
    }

    /**
     * Scan PDFs in the -self::$unzipped folder and attempt to process them based on their filenames
     *
     * @param null $year
     */
    public static function loadSupportingDocuments($year = null) {

        // The applicant period happens in a window that "6 months from now" is safely the correct enrollment year
        $year = $year ?: Carbon::instance(new \DateTime("+6 months"))->format("Y");

        $applicants = Applicant::all()->keyBy("reference_number");

        $refs_changed = [];

        $files = [];

        $folder = self::getUnzipFolder();

        foreach (glob($folder . "/".$year."_*.pdf") as $file) {
            $file = str_replace($folder."/", "", $file);
            $files[$file] = $file;
        }

        // Checklists -     Cycle_DocumentType_ApplicationType_DocumentID
        // Datasheets -     Cycle_OUACRefno_DocumentType_ApplicationType_ProgramCode_DocumentID
        // Transcripts -    Cycle_OUACRefno_DocumentType_DocumentTitle_InstitutionCode_DocumentID

        // Check the filename against the possible regex patterns
        foreach ($files as $file) {
            $matches = [];

            // Clear this all to null. We only use some of them for each doc type but it makes passsing easier.
            $match = $cycle = $file_num = $doc_type = $doc_subtype = $doc_add = $doc_num = $doc_id = null;

            // Checklists -     Cycle_DocumentType_ApplicationType_DocumentID
            //                  2018_001_CHECKLISTS_NEW_000000008750378.pdf
            //                  0_0_A_A_0
            if (preg_match('/^([0-9]{4})_([0-9]{3})_([A-Za-z]+)_([A-Za-z]+)_([0-9]+)\.pdf/', $file, $matches)) {
                list($match, $cycle, $file_num, $doc_subtype, $doc_add, $doc_id) = $matches;

                // TODO Find out what we do with these checklists, for now skip
                continue;

            }
            // Datasheets -
            //                   2018_805086_005_DATASHEETS_000000008801490.pdf
            //                  0_0_0_A_0
            elseif (preg_match('/^([0-9]{4})_([0-9]+)_([0-9]+)_([A-Za-z]+)_([0-9]+)\.pdf/', $file, $matches)) {
               list($match, $cycle, $ref_num, $file_num, $doc_subtype, $doc_id) = $matches;
               $doc_type = "datasheet";
            }
            // Datasheets Amended -
            //                   2018_805086_005_DATASHEETS_AMENDED_000000008801490.pdf
            //                  0_0_0_A_A_0
            elseif (preg_match('/^([0-9]{4})_([0-9]+)_([0-9]+)_([A-Za-z]+)_([A-Za-z]+)_([0-9]+)\.pdf/', $file, $matches)) {
               list($match, $cycle, $ref_num, $file_num, $doc_subtype, $doc_add, $doc_id) = $matches;
               $doc_type = "datasheet";
            }
            // Sketches -
            //                   2018_800003_AutobiographicSketch_SUBMISSIONS_001_000000008654997
            //                   0_0_A_A_0_0
            elseif (preg_match('/^([0-9]{4})_([0-9]+)_([A-Za-z]+)_([A-Za-z]+)_([0-9]+)_([0-9]+)\.pdf/', $file, $matches)) {
               list($match, $cycle, $ref_num, $doc_subtype, $doc_add, $file_num, $doc_id) = $matches;
                $doc_type = "sketch";
            }
            // Academic     -
            //                  2018_800003_MCAT_14338306_000000008757160
            //                  0_0_A_0_0
            elseif (preg_match('/^([0-9]{4})_([0-9]+)_([A-Za-z]+)_([0-9]+)_([0-9]+)\.pdf/', $file, $matches)) {
               list($match, $cycle, $ref_num, $doc_subtype, $file_num, $doc_id) = $matches;
                $doc_type = "academic";
            }
            // Miscellaneous     -
            //                  2018_800003_MISCELLANEOUS_000000008652559
            //                  0_0_A_0
            elseif(preg_match('/^([0-9]{4})_([0-9]+)_([A-Za-z]+)_([0-9]+)\.pdf/', $file, $matches)) {
               list($match, $cycle, $ref_num, $doc_subtype, $doc_id) = $matches;
                $doc_type = "misc";
            }
            // I don't know what would end up here.
            else {
                echo $file . " does not match a defined pattern and has been skipped. \n";
                continue;
            }

            if (empty($ref_num) || empty($applicants[$ref_num])){
                // TODO We should probably create an applicant if we have a file for them, but this may only be an issue during testing
                echo "{$file} skipped because it does not match an existing applicant. \n";
                continue;
            }

            $applicants[$ref_num]->addFile($file, $doc_type, $doc_subtype, [
                "file_num" => $file_num,
                "additional" => $doc_add,
                "doc_id" => $doc_id,
                "cycle_id" => $cycle
            ]);

            $refs_changed[] = $ref_num;
        }

        // For performance, only update the Applicants that were touched by the process
        if (!empty($refs_changes)) {
            $dirty = Applicant::where([
                "reference_number" => $refs_changed
            ])->get();

            foreach($dirty as $applicant) {
                $applicant->processAdditional("file", true);
            }
        }
    }

    /**
     * Finds the QUEX Data (or child) class based only on its reference number key
     *
     * @param $data array array of data from the Parser
     * @return mixed
     */
    public static function updateData($data, $file_num = null) {
        // This is called from child class
        $class = get_called_class();
        if (!isset(self::$latest_models[$class])) {
            $class::loadModels();
        }

        $models = self::$latest_models;

        // Check if the new model data is already represented in the $models array
        $newModel = new $class($data);
        $modelKey = $newModel->arrayKey();
        if (empty($models[$class][$modelKey])) {
            $models[$class][$modelKey] = $newModel;
        }

        // If the new file_number is bigger than the existing models file number, overwrite stuff!
        if (!$file_num || $file_num > $models[$class][$modelKey]->file_num) {
            $models[$class][$modelKey]->fill($data);
            $models[$class][$modelKey]->file_number = $file_num;
        }

        self::$latest_models = $models;
        return $models[$class][$modelKey];
    }

    /**
     * Load all models of this type (called in subclasses) into the models array
     */
    public static function loadModels() {
        $class = get_called_class();

        $models = $class::orderBy("file_number", "DESC")->groupBy($class::$ref_num)->get();

        $arr = [];

        foreach ($models as $model) {
            $arr[$model->arrayKey()] = $model;
        }

        self::$latest_models[$class] = $arr;
    }

    /**
     * Fetch host data from env() or the Entrada config array
     *
     * @return array
     */
    public static function getHostData() {
        return [
            env("APPLICANT_HOST", config("entrada.admissions.med_host", "")),
            env("APPLICANT_USER", config("entrada.admissions.med_user", "")),
            env("APPLICANT_PASS", config("entrada.admissions.med_pass", "")),
            env("APPLICANT_PORT", config("entrada.admissions.med_port", 22)),
        ];
    }

    /**
     * Permanently erase all Applicants, ApplicantFiles and QUEX<type>Data models from storage
     */
    public static function permanentlyDeleteApplicants() {
        // Applicants
        Applicant::all()->each(function($app) {
            $app->forceDelete();
        });
        // Files
        ApplicantFile::all()->each(function($app) {
            $app->forceDelete();
        });
        // QUEX Data
        QUEXACAData::all()->each(function($app) {
            $app->forceDelete();
        });;
        QUEXABSData::all()->each(function($app) {
            $app->forceDelete();
        });;
        QUEXCORData::all()->each(function($app) {
            $app->forceDelete();
        });;
        QUEXCRSData::all()->each(function($app) {
            $app->forceDelete();
        });;
        QUEXHOMData::all()->each(function($app) {
            $app->forceDelete();
        });;
        QUEXROOData::all()->each(function($app) {
            $app->forceDelete();
        });;
        QUEXSKPData::all()->each(function($app) {
            $app->forceDelete();
        });;

        // Clear Folders
        // TODO empty folders if necessary
    }


    /**
     * A unique array key for this model (based on the compound reference number)
     * @return string the key
     */
    protected function arrayKey() {
        $class = get_called_class();

        return implode("_", array_intersect_key($this->attributes,
            array_flip($class::$ref_num)));
    }

    /**
     * A public getter for the fillable array
     * We do some checking against fillable outside the model
     *
     * TODO the above checking could technically be moved inside the model
     *
     * @return array the fillable array
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    // ====
    //  Folders
    // ====

    private static function getZipFolder() {
        return storage_path("admissions/".self::$decrypt_folder);
    }

    private static function getDownloadFolder() {

        $folder = storage_path("admissions/". self::$download_folder);

        if (!is_dir($folder)) {
            mkdir($folder);
        }

        return $folder;
    }

    private static function getUnzipFolder() {

        return storage_path("admissions/".self::$unzip_to);
    }

    /**
     * Some conversion is necessary before these items are saved to the database
     *
     * @inheritdoc
     * @return bool
     */
    public function save(array $options = []) {

        $dates = empty($this->date_fields) ? [] : $this->date_fields;

        foreach ($dates as $key => $val) {

            if (empty($this->$key)) {
                $this->$key = "";
                continue;
            }

            switch ($val) {
                case "yyyymm":
                    $this->$key = date("Y-m-d", strtotime("{$this->$key}01"));
                    break;
                default:
                    break;
            }
        }

        return parent::save($options);
    }



}
