<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Modules\Admissions\Models\Entrada\ApplicantReaderScore;
use Entrada\Modules\Admissions\Models\Entrada\CycleRole;
use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ScoreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response|array
     */
    public function index() {
        //
        $user = CycleRole::admissionsUserFromUser(Auth::user());

        return $user->scores();
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
        $score = ApplicantReaderScore::findOrFail($id);

        return $score;
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
        // TODO return another endpoint for updating scores
    }

    public function setScore(Request $request, $id) {
        $score = ApplicantReaderScore::findOrFail($id);
        $user = CycleRole::admissionsUserFromUser(Auth::user());

        $this->validate($request, [
            "value" => "required|numeric"
        ], [
            "value.required" => __("A value is required to update a Score"),
            "value.numeric" => __("The value should be numeric")
        ]);

        foreach ($user->scores() as $userScore) {
            if ($score->score_id == $userScore) {
                $score->setScore($request->get("value"));
            }
        }

        return response([__("You are not authorized to do that")], 403);
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
