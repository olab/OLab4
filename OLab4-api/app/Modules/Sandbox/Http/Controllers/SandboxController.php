<?php

namespace Entrada\Modules\Sandbox\Http\Controllers;

use Auth;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Sandbox\Models\Entrada\Sandbox;
use Illuminate\Http\Request;

class SandboxController extends Controller
{

    public function __construct()
    {
        $this->input_fields = [
            'title' => 'required|string',
            'description' => 'required|string',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Entrada\Modules\Sandbox\Models\Entrada\Sandbox $sandbox
     * @return \Illuminate\Http\Response
     */
    public function index(Sandbox $sandbox)
    {
        $this->authorize('view', $sandbox);

        return [
            'sandboxes' => $sandbox->with('created_by', 'updated_by')->orderBy('created_date', 'desc')->paginate(),
            'current_user_can' => [
                'read' => Auth::user()->can('view', $sandbox),
                'create' => Auth::user()->can('create', $sandbox),
                'update' => Auth::user()->can('update', $sandbox),
                'delete' => Auth::user()->can('delete', $sandbox),
            ]
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Entrada\Modules\Sandbox\Models\Entrada\Sandbox $sandbox
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Sandbox $sandbox)
    {
        // Authorizes the creation of sandbox
        $this->authorize('create', $sandbox);

        $this->validate($request, $this->input_fields);

        $new = $sandbox->create($request->only('title', 'description'));

        return response($new, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Entrada\Modules\Sandbox\Models\Entrada\Sandbox $sandbox
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Sandbox $sandbox, $id)
    {
        $this->authorize('view', $sandbox);

        return $sandbox->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * Note: PUT and PATCH methods in Laravel require
     * an extra header: "Content-Type: application/x-www-form-urlencoded"
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $sandbox = Sandbox::findOrFail($id);

        // Authorizes the update of sandbox
        $this->authorize('update', $sandbox);

        // Validate request
        $this->validate($request, $this->input_fields);

        // Save new data to sandbox model
        $update = $sandbox->update($request->only('title', 'description'));

        return response($sandbox->findOrFail($id), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $sandbox = Sandbox::findOrFail($id);

        $this->authorize('delete', $sandbox);

        $delete = $sandbox->delete();

        if ($delete) {
            // Successful delete returns a 204
            return response('', 204);
        }

        return response('', 404);
    }

    /**
     * Seed the database with fake data for testing
     *
     * @param  \Faker\Generator $faker
     * @param  \Entrada\Modules\Sandbox\Models\Entrada\Sandbox $sandbox
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function seeder(\Faker\Generator $faker, Sandbox $sandbox)
    {
        $number = 10;

        for ($i = 0; $i <= $number; $i++) {
            $sandbox->create([
                'title' => $faker->sentence(3),
                'description' => $faker->paragraph(),
            ]);
        }
    }
}
