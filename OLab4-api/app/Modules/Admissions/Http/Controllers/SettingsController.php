<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Modules\Admissions\Models\Entrada\Setting;
use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * Returns all settings or, if "setting" is sent, returns the Setting with shortcode "setting" if it exists
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request) {

        $data = $request->all();
        $setting_name = empty($data["setting"]) ? null : $data["setting"];

        if ($setting_name) {
            $setting = $setting_name ? Setting::where(["shortname" => $setting_name])->first(["shortname", "value"]) : null;

            // Only return a setting if it exists
            return empty($setting) ?
                response([__("Setting: $setting_name not found")], 404) :
                [$setting];
        } else {
            // If we didn"t declare a setting, return all of them
            return [
                Setting::all(["shortname", "value"])
            ];
        }
    }

    public function update(Request $request, $id) {

        $setting = Setting::findOrFail($id);

        $this->validate($request, [
            "value" => "required"
        ], [
            "value.required" => __("A value is required to update a Setting")
        ]);

        $setting->value = $request->get("value");

        if ($setting->save()) {
            return response([__( "Setting ':shortname' updated", [
                "shortname" => $setting->shortname
            ])], 200);
        } else {
            Log::debug(["Setting '" . $setting->shortname . "' failed to save with error ".json_encode($setting->errors)], 500);
            return response([__("Setting ':setting' failed to save", [
                "setting" => $setting->shortname
            ])], 500);
        }
    }

    /**
     * Creates or Updates a single setting based on form data "<setting>" => "<value>"
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateByShortname(Request $request) {

        $this->validate($request, [
           "setting" => "required",
            "value" => "required"
        ], [
            "string.required" => __("A setting name is required to update a Setting"),
            "value.required" => __("A setting value is required to update a Setting")
        ]);

        // Get the data from the request
        $data = $request->all();
        $shortname = $data["setting"];
        $new_value = $data["value"];

        // Get or create the setting
        $setting = Setting::firstOrCreate(["shortname" => $shortname]);
        $setting->value = $new_value;
        return $setting->save() ?
            response([$shortname . " updated"], 200) :
            response([$shortname . " failed with error ".json_encode($setting->errors)], 500);

    }
}
