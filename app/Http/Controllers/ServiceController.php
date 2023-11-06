<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Service::all());
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'name' => 'required',
                'money_cost' => 'required',
                'time_cost' => 'required'
            ]);

            if ($validation->fails()){
                return \response()->json(['errors' => $validation->errors()]);
            }

            $service = Service::query()->create($request->all());
            return response()->json(compact('service'), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $service = Service::query()->findOrFail($id);
        return response()->json($service);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        try {
            $service = Service::query()->findOrFail($id);
            $service->update($request->all());
            return \response()->json(compact('service'));
        } catch (\Exception $e) {
            Log::error($e);
            return \response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        try {
            $service = Service::query()->findOrFail($id);
            $service->delete();

            return \response()->json(['message' => 'سرویس با موفقیت حذف شد']);
        } catch (\Exception $e) {
            Log::error($e);
            return \response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
