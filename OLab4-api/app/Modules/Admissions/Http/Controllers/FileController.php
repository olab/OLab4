<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Modules\Admissions\Models\Entrada\ApplicantFile;
use Entrada\Modules\Admissions\Models\Entrada\CycleRole;
use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    /**
     * Display a listing of the Files visible to the logged in User.
     *
     * This function uses the CycleRole::admissionsUserFromUser to get an Admissions specific User object
     *      (or a child of it) based on the logged in user and returns a custom ApplicantFile array based on the
     *      Applicants this user can view, or all ApplicantFiles, if the user is an admin
     *
     * @return \Illuminate\Http\Response|array|mixed
     */
    public function index() {
        $this->authorize("view", new ApplicantFile());

        $user = CycleRole::admissionsUserFromUser(Auth::user());

        return $user->files();
    }

    /**
     * Display the specified ApplicantFile
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //        
        $file = ApplicantFile::findOrFail($id);

        // If the User can see this File's Applicant, they can surely see the File
        $this->authorize("view", $file->applicant);
        
        return $file;
    }

    /**
     * Returns the File specified by $id and returns it as a file binary for download
     *
     * @param $id int the ID of the File we are trying to download
     * @return mixed
     */
    public function download($id) {
        $file = ApplicantFile::findOrFail($id);

        // If the User can see this File's Applicant, they can surely see the File
        $this->authorize("view", $file->applicant);
        
        return response()->file($file->filePath());
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $file = ApplicantFile::findOrFail($id);

        // If the User can see this File's Applicant, they can surely see the File
        $this->authorize("delete", $file->applicant);

        if ($file->delete()) {
            return response([__("File :id deleted", ["id" => $id])], 200);
        } else {
            Log::debug("File {$id} failed to delete with error:" . $file->errors);
            return response([__("File :id failed to delete", ["id" => $id])], 500);
        }
    }
}
