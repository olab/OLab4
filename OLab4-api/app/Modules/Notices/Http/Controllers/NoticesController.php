<?php

namespace Entrada\Modules\Notices\Http\Controllers;

use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Models_Notice;

class NoticesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->validate($request, [
            'filter' => 'nullable|string'
        ]);

        $include_read_notices = in_array($request->filter, ['read', 'all', null]) ? true : false;

        $only_read_notices = $request->filter == 'read' ? true : false;

        return [
            'notices' => Models_Notice::fetchUserNotices($include_read_notices, $only_read_notices)
        ];
    }

    /**
     * Display notices summary
     *
     * @return \Illuminate\Http\Response
     */
    public static function appendToUserSummary() {
        return [
            'total' => count(Models_Notice::fetchUserNotices($include_read_notices = true, $only_read_notices = false)),
            'unread_count' => count(Models_Notice::fetchUserNotices($include_read_notices = false, $only_read_notices = false)),
        ];
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
        return Models_Notice::fetchNotice($id);
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
        $notice = Models_Notice::fetchNotice($id);

        if ($notice) {
            if ($request->action == 'read') {
                $read = Models_Notice::markNoticeAsRead($id);

                return $read ? response($notice, 200) : response('Could not mark notice as read.', 400);
            }
        }

        return response('', 404);
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
