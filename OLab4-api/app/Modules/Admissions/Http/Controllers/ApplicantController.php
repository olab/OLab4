<?php

namespace Entrada\Modules\Admissions\Http\Controllers;
use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\CycleRole;
use Entrada\Modules\Admissions\Models\Entrada\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Entrada\Http\Controllers\Controller;


class ApplicantController extends Controller
{

    /**
     * Returns all Applicants currently visible to the logged in user
     *      /admissions/applicants
     *      This function uses the CycleRole::admissionsUserFromUser to get an Admissions specific User object
     *      (or a child of it) based on the logged in user and returns a custom relation
     *
     * There are a few Applicant endpoints (for File Review, etc) so this one is filtered through responseFormat() to
     * remove any unnecessary attributes
     *
     * @param Request $request
     * @return array the array of Applicants
     */
    public function index(Request $request) {

        $this->authorize("view_list", new Applicant());

        if ($request->get("file-review")) {
            return $this->review($request);
        }

        $user = CycleRole::admissionsUserFromUser(Auth::user());

        // Get the most recent applicants for each reference number
        $format = Applicant::responseFormat($request);

        $applicants = $user->applicants();

        if (empty($applicants)) {
            return [];
        } else {
            return $applicants
                ->makeVisible($format)
                ->toArray();
        }
    }

    /**
     * Returns a specific applicant
     *
     * @param Request $request
     * @param $id integer the applicant_id of the Applicant (passed by the router)
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function show(Request $request, $id) {
        $applicant = Applicant::findOrFail($id);
        $this->authorize("view", $applicant);

        return $applicant;
    }

    /**
     * Returns all Applicants currently visible to the logged in user at endpoint:
     *      /admissions/file-review/applicants
     *
     * Only users that meet the requirements of File Review (applicant_staus = (A)dvancing) are returned
     * The response is filtered so that only data related to the File Review process is returned
     *
     * @param Request $request
     * @return mixed
     */
    public function review(Request $request) {
        $this->authorize("view_list", new Applicant());
        $user = CycleRole::admissionsUserFromUser(Auth::user());

        return $applicants = $user->fileReviewApplicants();
    }

    /**
     * Update an Applicant, specified by applicant_id
     *      /admissions/applicants/{applicant_id}
     *
     * @param Request $request
     * @param int $id The ID applicant we're updating
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    public function update(Request $request, $id) {
        $applicant = Applicant::findOrFail($id);
        $this->authorize("update", $applicant);

        $this->validate($request, [
            "year" => "int",
            "pool_id" => "int",
            "cycle_id" => "int",
            "reference_number" => "int",
            "given_name" => "string",
            "surname" => "string", 
            "sex" => "string|max:1",
            "birthdate" => "date",
            "age" => "int",
            "total_credits" => "numeric",
            "cumulative_avg" => "numeric",
            "average_last_2_years" => "numeric",
            "grad_indicator" => "string:max:1",
            "aboriginal_status" => "string:max:1",
            "apply_to_mdphd" => "string:max:1",
            "citizenship" => "string",
            "mcat_total" => "integer|min:472|max:528",
            "bbfl" => "integer|min:472|max:528",
            "psbb" => "integer|min:118|max:132",
            "cpbs" => "integer|min:118|max:132",
            "cars" => "integer|min:118|max:132",
            "has_reference_letters" => "boolean",
            "has_sketch_review" => "boolean",

            'local_address1',
            'local_address2',
            'local_address3',
            'local_address4',
            'local_telephone',
            'email_address',
            'last_university_name', 
        ], [
            "year.int" => __("Year should be an integer"),
            "pool_id.int" => __("Pool ID should be an integer"),
            "cycle_id.int" => __("Cycle ID should be an integer"),
            "reference_number.int" => __("Reference Number should be an integer"),
            "given_name.string" => __("Given name should be a string"),
            "surname.string" => __("Surname should be a string"),
            "sex.string|max:1" => __("Sex should be a single character (M|F|X| )"),
            "birthdate.date" => __("Birthdate should be a date"),
            "age.int" => __("Age should be an integer"),
            "total_credits.numeric" => __("Total Credits should be a number"),
            "cumulative_avg.numeric" => __("Cumulative Average should be a number"),
            "average_last_2_years.numeric" => __("Average (Last 2 Years) should be a number"),
            "grad_indicator.string:max:1" => __("Grad Indicator should be Y if true and empty otherwise"),
            "aboriginal_status.string:max:1" => __("Aboriginal Status should be Y if true and empty otherwise"),
            "apply_to_mdphd.string:max:1" => __("MDPhD status should be Y if true and empty otherwise"),
            "citizenship.string" => __("Citizenship country should be a string"),
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
            "has_reference_letters.boolean" => __("Has Reference Letters should be true or false"),
            "has_sketch_review.boolean" => __("Has Sketch Review should be true or false")
        ]);
        

        // For now, we will accept all fillable data. Someday, we may want to restrict updates.
        $data = $request->all();

        if (empty($data)) {
            return response([__("No Fields Supplied")], 400);
        }

        // Prevent updating if the sort page is locked!
        if (Setting::fetch("sort_page_locked")) {
            return response([__("Sort Page is Locked")], 423);
        }

        // I'd rather not have erroneous fields in the edited_fields database,
        //      so we'll clean this here even if fill() does it automatically
        // This section gets the values from $data, keyed by the fillable() array
        $data =  array_intersect_key($data, array_flip($applicant->getFillable()));

        // We use updateData instead of Laravel's built in save, because we want to mark the fields for the audit trail
        if ($applicant->updateData($data)) {
            return response([
                "message" => __("Applicant Updated Successfully"),
                "applicant" => $applicant->refresh()
            ], 200);
        } else {
            Log::debug("Applicant {$id} failed to update with message: " . $applicant->errors);
            return response([__("Applicant :id failed to save", ["id" => $id])], 500);
        }
    }

    /**
     * Performs a bulk update of the Applicants's applicant_status
     *      PUT /admissions/applicant
     *
     * the format for the response payload is:
     * [
     *      "advancing" => [ <applicant_ids...>],
     *      "pending" => [ <applicant_ids...>],
     *      "rejected" => [ <applicant_ids...>
     * ]
     *
     * All of the statuses are optional, if you only want to move some Applicants to advancing, you can omit the other
     *  keys or leave them empty.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function massUpdate(Request $request) {
        $this->authorize("mass_update", new Applicant());

        if (Setting::fetch("sort_page_locked")) {
            return response([__("Sort Page is Locked")], 423);
        }

        /*
         * check that advancing, pending and rejected are arrays, only if they are included
         */
        $this->validate($request, [
            "advancing" => "array|sometimes|required",
            "pending" => "array|sometimes|required",
            "rejected" => "array|sometimes|required"
        ], [
            "advancing.array" => __("The advancing attribute should be an array of applicant_ids"),
            "pending.array" => __("The pending attribute should be an array of applicant_ids"),
            "rejected.array" => __("The rejected attribute should be an array of applicant_ids")
        ]);


        // We're looking for specific array keys in the request payload
        foreach (["advancing", "pending", "rejected"] as $status) {
            $applicant_ids = $request->get($status);

            // Empty arrays are valid input, we can skip the processing
            if (empty($applicant_ids)){
                continue;
            }

            switch ($status) {
                case "advancing":
                    $setApp = Applicant::ADVANCING;
                    break;
                case "pending":
                    $setApp = Applicant::PENDING;
                    break;
                case "rejected":
                    $setApp = Applicant::REJECTED;
                    break;
                default:
                    return response([__("Invalid Application Status ':status'" , [
                        "status" => $request->get("application_status")
                    ])], 400);
                    break;
            }

            // Update the specified array
            Applicant::whereIn("applicant_id", $applicant_ids)->update(["application_status" => $setApp]);
        }

        // If we made it here, we're good to return our success message.
        return response([__("Applicants Updated")], 200);
    }

    /**
     * Return Files for an Applicant, specified by applicant_id
     *
     * @param $applicant_id
     * @return mixed
     */
    public function files($applicant_id) {
        $applicant = Applicant::findOrFail($applicant_id);

        $this->authorize("view", $applicant);

        return $applicant->files;
    }

    /**
     * Return Groups for an Applicant, specified by applicant_id
     *
     * @param $applicant_id
     * @return array|mixed
     */
    public function groups($applicant_id) {
        $applicant = Applicant::findOrFail($applicant_id);

        // If the user can view this Applicant, they should be able to view their Groups
        $this->authorize("view", $applicant);

        return $applicant->groups;
    }

    /**
     * Return Readers assigned to an Applicant, specified by applicant_id
     *
     * @param $applicant_id
     * @return array|mixed
     */
    public function readers($applicant_id) {
        $applicant = Applicant::findOrFail($applicant_id);

        // If the user can view this Applicant, they should be able to view their Groups
        $this->authorize("view", $applicant);

        return $applicant->readers();
    }
}
