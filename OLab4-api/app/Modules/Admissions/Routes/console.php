<?php

use Entrada\Modules\Admissions\Models\Entrada\QUEXData;
use Illuminate\Support\Facades\Log;
/**
 * Add any artisan commands within a console.php file. 
 * All module-specific commands should have the <module name>:<command>
 * format to enable namespacing within the `php artisan` master list of 
 * commands.
 */

$no_host_specified_error = "No host specified for data retrieval. Please specify one using the config.inc.php setting 'admissions' => 'med_host'."."\n";
$no_passphrase_specified_error = "Decryption failed. Ensure you have set your decryption passphrase in config.inc.php setting 'admissions' => 'med_host'. Even if it is blank."."\n";

Artisan::command('admissions:omsas', function () use ($no_host_specified_error, $no_passphrase_specified_error) {

    $host_data = QUEXData::getHostData();

    if (empty($host_data[0])) {
        return response(["No host data specified"], 500);
    }

    // Load main files!
    $file_path = env("APPLICANT_PATH", config("entrada.admissions.med_path", ""));
    $recd = QUEXData::fetchFilesFromServer($host_data, $file_path);
    echo "{$recd} files loaded from {$file_path} \n";

    // Load Supplemental files!
    $supplemental_file_path = env("APPLICANT_SUPP", config("entrada.admissions.med_supp", ""));
    if (!empty($supplemental_file_path)) {
        $recd = QUEXData::fetchFilesFromServer($host_data, $supplemental_file_path);
        echo "{$recd} files loaded from {$supplemental_file_path} \n";
    } else {
        echo "med_supp folder not set in config. Supplemental file load skipped. You can add the folder and run admissions:fetch-omsas again.\n";
        Log::info("med_supp folder not set in config. Supplemental file load skipped. You can add the folder and run admissions:fetch-omsas again.");
    }
    echo "OMSAS files fetched from server successfully.\n";
    Log::info("OMSAS files fetched from server successfully");

    // Decrypt
    if (QUEXData::decryptFiles()) {
        echo "OMSAS file decryption complete.\n";
        Log::info("OMSAS file decryption complete");
    } else {
        echo $no_passphrase_specified_error;
        Log::info($no_passphrase_specified_error);
    }

    $numberUnzipped = QUEXData::unzipFiles();
    if ($numberUnzipped) {
        echo "OMSAS files unzipped successfully.\n";
        Log::info("OMSAS files unzipped successfully");
    } else {
        echo "No files to unzip. Halting.\n";
        Log::info("No files to unzip.");
        return true;
    }

    $numberScanned = QUEXData::scanFiles();
    if ($numberScanned) {
        echo "OMSAS QUEX files scanned and loaded.\n";
        Log::info("OMSAS QUEX files scanned and loaded");
    } else {
        echo "No QUEX Files scanned. Halting.\n";
        Log::info("No QUEX Files scanned.");
        return true;
    }
    echo "OMSAS QUEX files scanned and loaded.\n";
    Log::info("OMSAS QUEX files scanned and loaded");

    // TODO Find a more abstract solution for this.
    \Entrada\Modules\Admissions\Http\Controllers\DataLoaderController::loadModels();
    Log::info("OMSAS QUEX models loaded into memory");

    QUEXData::loadApplicants();
    echo "OMSAS QUEX models applied to Applicants.\n";
    Log::info("OMSAS QUEX models applied to Applicants");

    QUEXData::loadSupportingDocuments();
    echo "Supporting Documents successfully loaded. \n";
    Log::info("Supporting Documents successfully loaded.");

})->describe('Unzip OMSAS files and process them.');

// FETCH OMSAS FILES FROM SERVER
Artisan::command('admissions:fetch-omsas', function () use ($no_host_specified_error) {

    try {
        $host_data = QUEXData::getHostData();

        if (empty($host_data[0])) {
            return response(["No host data specified"], 500);
        }

        // Load main files!
        $file_path = env("APPLICANT_PATH", config("entrada.admissions.med_path", ""));
        $recd = QUEXData::fetchFilesFromServer($host_data, $file_path);
        echo "{$recd} files loaded from {$file_path} \n";

        // Load Supplemental files!
        $supplemental_file_path = env("APPLICANT_SUPP", config("entrada.admissions.med_supp", ""));
        if (!empty($supplemental_file_path)) {
            $recd = QUEXData::fetchFilesFromServer($host_data, $supplemental_file_path);
            echo "{$recd} files loaded from {$supplemental_file_path} \n";
        } else {
            Log::info("med_supp folder not set in config. Supplemental file load skipped. You can add the folder and run admissions:fetch-omsas again.");
        }
        Log::info("OMSAS files fetched from server successfully");
    } catch (\Exception $e) {
        echo $e->getMessage();
    }

})->describe('Fetch OMSAS files from server.');

// DECRYPT FILES
Artisan::command('admissions:decrypt-omsas', function () use ($no_passphrase_specified_error) {

    if (QUEXData::decryptFiles()) {
        Log::info("OMSAS file decryption complete");
    } else {
        echo $no_passphrase_specified_error;
        Log::info($no_passphrase_specified_error);
    }
})->describe('Decrypt OMSAS files and process them.');

// UNZIP DECRYPTED FILES
Artisan::command('admissions:unzip-omsas', function () {

    $numberUnzipped = QUEXData::unzipFiles();
    if ($numberUnzipped) {
        echo "OMSAS files unzipped successfully.\n";
        Log::info("OMSAS files unzipped successfully");
    } else {
        echo "No files to unzip.\n";
        Log::info("No files to unzip.");
    }
})->describe('Unzip Decrypted OMSAS QUEX text files.');

Artisan::command('admissions:load-omsas', function () {

    $numberScanned = QUEXData::scanFiles();
    if ($numberScanned) {
        echo "OMSAS QUEX files scanned and loaded.\n";
        Log::info("OMSAS QUEX files scanned and loaded");
    } else {
        echo "No QUEX Files scanned.\n";
        Log::info("No QUEX Files scanned.");
        return true;
    }
})->describe('Load data from QUEX text files into database.');

Artisan::command('admissions:process-omsas', function () {

    // TODO Find a more abstract solution for this.
    \Entrada\Modules\Admissions\Http\Controllers\DataLoaderController::loadModels();
    Log::info("OMSAS QUEX models loaded into memory");
    QUEXData::loadApplicants();
    Log::info("OMSAS QUEX models applied to Applicants");

})->describe('Update Applicants with QUEX data from database.');


Artisan::command('admissions:load-omsas-additional', function () {

    QUEXData::loadSupportingDocuments();
    Log::info("Supporting Documents successfully loaded.");

})->describe('Update Applicants with QUEX data from database.');

Artisan::command('admissions:load-keys', function($test = null) {
    echo $test;
})->describe('Load GPG keys from the keys folder.');

Artisan::command('admissions:delete-all', function() {
   QUEXData::permanentlyDeleteApplicants();
});