<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\Cycle;
use Entrada\Modules\Admissions\Models\Entrada\CycleRole;
use Entrada\Modules\Admissions\Models\Entrada\Pool;
use Entrada\Modules\Admissions\Models\Entrada\PoolFilter;
use Entrada\Modules\Admissions\Models\Entrada\QUEXROOData;
use Entrada\Modules\Admissions\Models\Entrada\Setting;
use Entrada\Modules\Admissions\Scopes\CycleScope;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Entrada\Http\Controllers\Controller;

class PoolController extends Controller
{
    /**
     * Returns the list of Applicant Pools
     *
     * @return array the list of Pools
     */
    public function index() {
        $this->authorize("view", new Pool());

        $user = CycleRole::admissionsUserFromUser(Auth::user());

        // returns the pools based ont eh
        return $user->pools();
    }

    /**
     * Store a newly created Pool in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize("create", new Pool());

        $this->validate($request, [
            "cycle_id" => "integer",
            "name" => "string|required|max:32"
        ], [
            "cycle_id.integer" => __("Cycle ID should be an integer"),
            "name.string" => __("Pool name should be a string"),
            "name.required" => __("A Pool name is required to create a new Pool"),
            "name.max" => __("Pool name should be at most 32 characters")
        ]);

        $data = $request->all();
        $pool = new Pool();
        $pool->fill($data);

        if (empty($pool->cycle_id)) {
            $pool->cycle_id = Cycle::cycleFromRequest();
        }

        if (empty($pool->className)) {
            $pool->classname = Pool::classNamify($pool->name);
        }

        if ($pool->save()) {
            return response([
                "message" => __("Pool create successfully"),
                "pool" => $pool->refresh()
            ], 200);
        } else {
            Log::debug("Pool failed to save with error: ".json_encode($pool->errors));
            return response([
                __("Pool failed to save")
            ], 500);
        }
    }

    /**
     * Display the specified Pool.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pool = Pool::findOrFail($id);
        $this->authorize("view", $pool);
        
        return $pool;        
    }

    /**
     * Update the specified Pool in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $pool = Pool::findOrFail($id);
        $this->authorize("update", $pool);

        $data = $request->data();

        $pool->fill($data);

        if ($pool->save()) {
            return response([
                "message" => __("Pool updated successfully"),
                "pool" => $pool
            ], 200);
        } else {
            Log::debug("Pool {$id} failed to save with error: ".$pool->errors);
            return response([__("Pool :id failed to save", ["id" => $id])], 500);
        }
        
    }

    /**
     * Remove the specified Pool from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pool = Pool::findOrFail($id);
        $this->authorize("delete", $pool);

        if ($pool->delete()) {
            return response([__("Pool :id deleted successfully", ["id" => $id])], 200);
        } else {
            Log::debug("Pool {$id} failed to delete with error: ".$pool->errors);
            return response([__("Pool :id failed to delete", ["id" => $id])], 500);
        }
    }
}
