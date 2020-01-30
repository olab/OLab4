<?php

namespace Entrada\Modules\Admissions\Http\Controllers;
use Entrada\Modules\Admissions\Models\Entrada;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Admissions\Models\Entrada\QUEXData;
use Entrada\Modules\Admissions\Models\Entrada\QUEXABSData;
use Entrada\Modules\Admissions\Models\Entrada\QUEXACAData;
use Entrada\Modules\Admissions\Models\Entrada\QUEXCRSData;
use Entrada\Modules\Admissions\Models\Entrada\QUEXCORData;
use Entrada\Modules\Admissions\Models\Entrada\QUEXHOMData;
use Entrada\Modules\Admissions\Models\Entrada\QUEXROOData;
use Entrada\Modules\Admissions\Models\Entrada\QUEXSKPData;
use Illuminate\Support\Facades\Log;


class DataLoaderController extends Controller
{

    private static $resource_dir = __DIR__ . "/../../Resources/";
    private static $library_dir = __DIR__ . "/../../Libraries/";


    /**
     * Fetches files from the OMSAS server based on credentials in the Entrada config array "admissions"
     * array(
        "med_host" => "",   // the SFTP host
        "med_path" => "",   // the path of the main OMSAS files (QUEX)
        "med_supp" => "",   // the path of any supporting documents
        "med_user" => "",   // the SFTP username
        "med_pass" => "",   // the SFTP password
        "med_port" => ""    // the SFTP port
        ),
     *
     * fetches the files (zipped and encrypted) and puts them in the folder at -QUEXData::$encrypted
     *
     * @return bool|\Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function fetchFilesFromServer() {

        $this->authorize("update", new QUEXData());

        try {
            $host_data = QUEXData::getHostData();

            if (empty($host_data[0])) {
                return response([__("No host data specified")], 500);
            }

            // Load main files!
            $file_path = env("APPLICANT_HOST", config("entrada.admissions.med_host", ""));
            QUEXData::fetchFilesFromServer($host_data, $file_path);

            // Load Supplemental files!
            $supplemental_file_path = env("APPLICANT_SUPP", config("entrada.admissions.med_supp", ""));
            if (!empty($supplemental_file_path)) {
                QUEXData::fetchFilesFromServer($host_data, $supplemental_file_path);
            }
        } catch (\Exception $e) {
            Log::debug("Failed to fetch files from server with error: ".$e->getMessage());
            echo $e->getMessage();
            return false;
        }
    }


    /**
     * Triggers the QUEXData::decryptFiles method
     *
     * Attempts to decrypt files in -QUEXData::$decrypted using the keys and passphrase loaded into gpg[2]
     *
     */
    public function decrypt() {

        $this->authorize("update", new QUEXData());

        QUEXData::decryptFiles();
    }



    /**
     * Triggers the QUEXData::unzipFiles and QUEXData::scanFiles methods
     *
     * Attempts to unzip the files and load them into the database
     */
    public function load() {

        $this->authorize("update", new QUEXData());

        // First we unzip the files from the storage folder!
        QUEXData::unzipFiles();

        // Then we scan the text files!
        QUEXData::scanFiles();
    }

    /**
     * Triggers the QUEXData::unzipFiles method
     *
     * Attempts to unzip the files stored in -QUEXData::$decrypted
     *
     */
    public function unzip() {

        $this->authorize("update", new QUEXData());

        // First we unzip the files from the storage folder!
        QUEXData::unzipFiles();

    }

    /**
     * Triggers the QUEXData::scanFiles function
     *
     * Attempts to load data from files in -QUEXData::unzipped into database
     */
    public function parse() {

        $this->authorize("update", new QUEXData());

        // First we unzip the files from the storage folder!
        QUEXData::scanFiles();
    }

    /**
     * Triggers QUEXData::loadModels method
     *
     * Attempts to generate models using the newest QUEX data available in the database
     */
    public function loadApplicants() {

        $this->authorize("update", new QUEXData());

        self::loadModels();

        QUEXData::loadApplicants();
    }

    /**
     * Triggers the QUEXData::loadSupportingDocuments method
     *
     * Attempts to loading supporting documents (letters, transcripts, etc) from the -QUEXData::unzipped folder
     *  and assign to Applicants based on the reference_number
     */
    public function loadAdditionalFiles() {

        $this->authorize("update", new QUEXData());

        QUEXData::loadSupportingDocuments();
    }

    /**
     * Loads all QUEX data into local memory.
     *
     * @TODO this method isn't great, but it keeps the parent QUEXData model from being dependent on it's children
     */
    public static function loadModels() {
        QUEXABSData::loadModels();
        QUEXACAData::loadModels();
        QUEXCORData::loadModels();
        QUEXCRSData::loadModels();
        QUEXHOMData::loadModels();
        QUEXROOData::loadModels();
        QUEXSKPData::loadModels();
    }


    /**
     * Load gpg keys from the modules/Admissions/Resources/Keys folder into the gpg keyring
     *
     */
    public function loadKeys() {

        $this->authorize("update", new QUEXData());

        $keydir = self::$resource_dir . "Keys";
        $passphrase = ENV("GPG_PASSPHRASE", "My secret pass phrase.");

        $gpg = ENV("GPG_LOCATION", config("entrada.admissions.gpg_location","/usr/bin/gpg"));
        foreach (glob($keydir . "/*.{asc,gpg,pgp}", GLOB_BRACE) as $file) {
            $key_added = shell_exec("$gpg --import $file");
            var_dump($key_added);
        }
    }
}
