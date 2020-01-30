<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Admissions\Models\Entrada\Cycle;
use Entrada\Modules\Admissions\Models\Entrada\Pool;
use Entrada\Modules\Admissions\Models\Entrada\PoolFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class FilterController extends Controller
{
    /**
     * Returns all Pool Filters for the current Cycle
     *
     * @param Request $request
     * @return array the array of Filters
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request) {
        $this->authorize("view", new PoolFilter());

        $filters = Cycle::currentCycle()->poolFilters;

        return $filters;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request) {
        $data = $request->all();
        $this->validate($request, [
            "pool_id" => "required|int",
            "subpool" => "required|string",
            "gpa_total" => "numeric",
            "gpa_last_2_years" => "numeric",
            "mcat_total" => "integer|min:472|max:528",
            "bbfl" => "integer|min:118|max:132",
            "psbb" => "integer|min:118|max:132",
            "cpbs" => "integer|min:118|max:132",
            "cars" => "integer|min:118|max:132",
            "has_reference_letters" => "boolean",
            "has_sketch_review" => "boolean"
        ], [
            "pool_id.int" => __("Pool ID should be an integer"),
            "pool_id.required" => __("A Pool ID is required to create a new Filter"),
            "subpool.string" => __("The Subpool identifier should be a string"),
            "subpool.required" => __("A Subpool identifier is required to create a new Filter"),
            "gpa_total.numeric" => __("GPA Total should be numeric"),
            "gpa_last_2_years.numeric" => __("GPA (last two years) should be numeric"),
            "mcat_total.integer" => __("MCAT Total should be an integer"),
            "mcat_total.min" => __("MCAT Total should be at least 472"),
            "mcat_total.max" => __("MCAT Total should be at most 528"),
            "bbfl.integer" => __("BBFL Score should be an integer"),
            "bbfl.min" => __("BBFL Score should be at least 118"),
            "bbfl.max" => __("BBFL Score should be at most 132"),
            "psbb.integer" => __("PSBB Score should be an integer"),
            "psbb.min" => __("PSBB Score should be at least 118"),
            "psbb.max" => __("PSBB Score should be at most 132"),
            "cpbs.integer" => __("CPBS Score should be an integer"),
            "cpbs.min" => __("CPBS Score should be at least 118"),
            "cpbs.max" => __("CPBS Score should be at most 132"),
            "cars.integer" => __("CARS Score should be an integer"),
            "cars.min" => __("CARS Score should be at least 118"),
            "cars.max" => __("CARS Score should be at most 132"),
            "has_reference_letters.boolean" => __("HasReferenceLetters should be true or false"),
            "has_sketch_review" => __("HasSketchReview should be true or false")
        ]);

        // This method can technically be use as an UPDATE as well;
        // TODO Consider refactoring if the front-end isn't dependent on this method
        $filter = PoolFilter::firstOrNew([
            "pool_id" => $data["pool_id"],
            "subpool" => $data["subpool"],
        ]);

        //$this->authorize("update", $filter);

        // Set all fillable fields
        $filter->fill($data);
        if ($filter->save()) {
            return response(["message" => __("Filter Created Successfully"), "filter" => $filter->refresh()], 200);
        } else {
            return response([__("Filter failed to save")], 500);
        }
    }


    /**
     * Create a Filter for the Pool specified in the binding
     *
     * This function responds to both PUT and POST
     * @TODO consider refactoring if the frontend is not dependent
     *
     * @param Request $request
     * @param Pool $pool
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeWithPool(Request $request, Pool $pool) {
        $data = $request->all();

        $this->validate($request, [
            "subpool" => "string|required",
            "gpa_total" => "numeric",
            "gpa_last_2_years" => "numeric",
            "mcat_total" => "integer|min:472|max:528",
            "bbfl" => "integer|min:472|max:528",
            "psbb" => "integer|min:118|max:132",
            "cpbs" => "integer|min:118|max:132",
            "cars" => "integer|min:118|max:132",
            "has_reference_letters" => "boolean",
            "has_sketch_review" => "boolean"
        ], [
            "subpool.string" => __("The Subpool identifier should be a string"),
            "subpool.required" => __("A Subpool identifier is required to create a new Filter"),
            "gpa_total.numeric" => __("GPA Total should be numeric"),
            "gpa_last_2_years.numeric" => __("GPA (last two years) should be numeric"),
            "mcat_total.integer" => __("MCAT Total should be an integer"),
            "mcat_total.min" => __("MCAT Total should be at least 472"),
            "mcat_total.max" => __("MCAT Total should be at most 528"),
            "bbfl.integer" => __("BBFL Score should be an integer"),
            "bbfl.min" => __("BBFL Score should be at least 118"),
            "bbfl.max" => __("BBFL Score should be at most 132"),
            "psbb.integer" => __("PSBB Score should be an integer"),
            "psbb.min" => __("PSBB Score should be at least 118"),
            "psbb.max" => __("PSBB Score should be at most 132"),
            "cpbs.integer" => __("CPBS Score should be an integer"),
            "cpbs.min" => __("CPBS Score should be at least 118"),
            "cpbs.max" => __("CPBS Score should be at most 132"),
            "cars.integer" => __("CARS Score should be an integer"),
            "cars.min" => __("CARS Score should be at least 118"),
            "cars.max" => __("CARS Score should be at most 132"),
            "has_reference_letters.boolean" => __("HasReferenceLetters should be true or false"),
            "has_sketch_review" => __("HasSketchReview should be true or false")
        ]);

        if (empty($data["subpool"])) {
            return response([__("No 'subpool' specified for Filter")]);
        }

        $filter = PoolFilter::firstOrNew([
            "pool_id" => $pool->pool_id,
            "subpool" => $data["subpool"],
        ]);

        $this->authorize("create", $filter);

        if (!empty($filter->filter_id)) {
            return response([__("Filter with subpool ':subpool' already exists for pool ':pool'", [
                "subpool" => $data["subpool"],
                "pool" => $pool->pool_id
            ])], 403);
        }

        // Set all fillable fields
        $filter->fill($data);
        if (!$filter->save()) {
            Log::debug("Save Filter failed with message: ".$filter->error);
            // Without refresh(), only fields set implicitly during creation are returned. Let's grab em all!
            return response([__("Filter failed to save")], 500);
        }

        return response([
            "message" => __("Filter Created Successfully"),
            "filter" => $filter->refresh()
        ], 200);
    }

    /**
     * Returns a specific Filter
     *
     * @param Request $request
     * @param $id integer the filter_id of the Filter (passed by the router)
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, $id) {
        $filter = PoolFilter::findOrFail($id);
        $this->authorize("view", $filter);

        return $filter;
    }

    /**
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, int $id) {

        $filter = PoolFilter::findOrFail($id);
        $this->authorize("update", $filter);

        $this->validate($request, [
            "pool_id" => "int",
            "subpool" => "string",
            "cumulative_avg" => "numeric",
            "average_last_2_years" => "numeric",
            "mcat_total" => "integer|min:472|max:528",
            "bbfl" => "integer|min:472|max:528",
            "psbb" => "integer|min:118|max:132",
            "cpbs" => "integer|min:118|max:132",
            "cars" => "integer|min:118|max:132",
            "has_reference_letters" => "boolean",
            "has_sketch_review" => "boolean"
        ], [
            "pool_id.int" => __("Pool ID should be an integer"),
            "subpool.string" => __("The Subpool identifier should be a string"),
            "cumulative_avg.numeric" => __("GPA Total should be numeric"),
            "average_last_2_years.numeric" => __("GPA (last two years) should be numeric"),
            "mcat_total.integer" => __("MCAT Total should be an integer"),
            "mcat_total.min" => __("MCAT Total should be at least 472"),
            "mcat_total.max" => __("MCAT Total should be at most 528"),
            "bbfl.integer" => __("BBFL Score should be an integer"),
            "bbfl.min" => __("BBFL Score should be at least 118"),
            "bbfl.max" => __("BBFL Score should be at most 132"),
            "psbb.integer" => __("PSBB Score should be an integer"),
            "psbb.min" => __("PSBB Score should be at least 118"),
            "psbb.max" => __("PSBB Score should be at most 132"),
            "cpbs.integer" => __("CPBS Score should be an integer"),
            "cpbs.min" => __("CPBS Score should be at least 118"),
            "cpbs.max" => __("CPBS Score should be at most 132"),
            "cars.integer" => __("CARS Score should be an integer"),
            "cars.min" => __("CARS Score should be at least 118"),
            "cars.max" => __("CARS Score should be at most 132"),
            "has_reference_letters.boolean" => __("HasReferenceLetters should be true or false"),
            "has_sketch_review" => __("HasSketchReview should be true or false")
        ]);

        $data = $request->all();
        // We're using fill later, so we should ensure
        //  that the pool_id of the request matches the pool_id in the body (if one is set)
        if (!empty($data["pool_id"]) && $data["pool_id"] !== $filter->pool_id) {
            return response([__("You cannot reassign filters!")], 403);
        }

        $filter->fill($data);
        if ($filter->save()) {
            return response([
                "message" => __("Filter Updated Successfully"),
                "filter" => $filter->refresh()
            ], 200);
        } else {
            Log::debug("Filter {$id} failed to save with message: ".$filter->error);
            return response([__("Filter :id failed to save", ["id" => $id])], 500);
        }
    }

    /**
     * Update a Filter for a Pool, both specified in the binding
     *
     * This function responds to both PUT and POST
     * @TODO consider refactoring if the frontend is not dependent
     *
     * @param Request $request
     * @param Pool $pool
     * @param PoolFilter $filter
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateWithPool(Request $request, Pool $pool, PoolFilter $filter) {

        if (empty($pool)) {
            return response([__("No Pool ID Provided")], 400);
        }
        $this->authorize("update", $pool);

        if (empty($filter)) {
            return response([__("No Filter ID Provided")], 400);
        }
        $this->authorize("update", $filter);

        $data = $request->all();

        // We're using fill later, so we should ensure
        //  that the pool_id of the request matches the pool_id in the body (if one is set)
        if (!empty($data["pool_id"]) && $data["pool_id"] !== $pool->pool_id) {
            return response([__("Pool ID in request endpoint does not match Pool ID in request body!")], 400);
        }

        $filter->fill($data);
        if (!$filter->save()) {
            Log::debug("Save Filter failed with message: " . $filter->errors);
            return response([__("Filter failed to save")], 500);
        }

        return response([
            "message" => __("Filter Updated Successfully"),
            "filter" => $filter->refresh()
        ], 200);
    }
}
