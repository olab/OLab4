<?php

namespace Entrada\Modules\Events\Http\Controllers;

use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Models_Event;

class EventsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        global $ENTRADA_USER;

        $this->validate($request, [
            'from' => 'timestamp',
            'to' => 'timestamp'
        ]);

        $event_start = $request->from ? $request->from : strtotime("-12 months 00:00:00");
        $event_finish = $request->to ? $request->to : strtotime("+12 months 23:59:59");

        return events_fetch_filtered_events(
            $ENTRADA_USER->getActiveId(),
            $ENTRADA_USER->getActiveGroup(),
            $ENTRADA_USER->getActiveRole(),
            $ENTRADA_USER->getActiveOrganisation(),
            "date",
            "asc",
            "custom",
            $event_start,
            $event_finish,
            events_filters_defaults($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveGroup(), $ENTRADA_USER->getActiveRole(), $ENTRADA_USER->getActiveOrganisation()),
            false,
            0,
            0,
            0,
            false
        );
    }

    /**
     * Append to user summary response
     *
     * @return \Illuminate\Http\Response
     */
    public static function appendToUserSummary()
    {
        $controller = new static();
        $request = new Request;

        return [
            'total' => count($controller->index($request)),
            'unread_count' => 0
        ];
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
        return Models_Event::fetchEventById($id);
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
