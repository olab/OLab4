<?php

namespace Entrada\Modules\User\Http\Controllers;

use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\User\Http\Middleware\AddModulesToUserSummary;
use Entrada_Auth;
use Auth;
use Models_User_Photo;
use PhotoResource;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware(AddModulesToUserSummary::class, [
            'only' => [
                'summary'
            ]
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Entrada_Auth::getUserProfile();
    }

    /**
     * Displays the user summary (counters, notifications, etc)
     * from other modules
     *
     * @return \Illuminate\Http\Response
     */
    public function summary()
    {
        return [ 'profile' => $this->index() ];
    }

    /**
     * Displays the user photo
     *
     * @return \Illuminate\Http\Response
     */
    public function photo()
    {
        global $ENTRADA_ACL;

        // Get user photo record
        $photo_object = Models_User_Photo::get(Auth::user()->id, Models_User_Photo::UPLOADED);

        $uploaded_photo = $photo_object ? $photo_object->toArray() : null;

        // if present, set to upload, otherwise use official
        $photo_type = $uploaded_photo ? "upload" : "official";

        // default display file
        $display_file = ENTRADA_ABSOLUTE."/images/headshot-male.gif";

        // default image type
        $mime_type = 'image/jpeg';

        if ($uploaded_photo && ($ENTRADA_ACL->amIAllowed(new PhotoResource(Auth::user()->id, Auth::user()->privacy_level, $photo_type), "read"))) {

            $photo_suffix = '-'.$photo_type.'-thumbnail';
    
            if ((@file_exists(STORAGE_USER_PHOTOS."/".Auth::user()->id.$photo_suffix)) && (@is_readable(STORAGE_USER_PHOTOS."/".Auth::user()->id.$photo_suffix))) {

                // set display file
                $display_file = STORAGE_USER_PHOTOS."/".Auth::user()->id.$photo_suffix;
                $mime_type = $uploaded_photo["photo_mimetype"];
            }
        }

        $image = @file_get_contents($display_file);

        return response([
            'photo' => 'data:'.$mime_type.';base64,'.base64_encode($image)
        ]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
