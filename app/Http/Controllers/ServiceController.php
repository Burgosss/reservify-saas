<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return response()->json(
            Service::where('is_active', true)->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'description'  => 'nullable|string',
            'duration_min' => 'required|integer|min:15|max:480',
            'price'        => 'required|numeric|min:0',
            'currency'     => 'nullable|string|size:3',
        ]);

        $service = Service::create($data);

        return response()->json($service, 201);
    }

    public function show(Service $service)
    {
        return response()->json($service);
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:150',
            'description'  => 'nullable|string',
            'duration_min' => 'sometimes|integer|min:15|max:480',
            'price'        => 'sometimes|numeric|min:0',
            'is_active'    => 'sometimes|boolean',
        ]);

        $service->update($data);

        return response()->json($service);
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json(null, 204);
    }
}
