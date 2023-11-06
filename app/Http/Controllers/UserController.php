<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function show(Request $request)
    {
        // TODO: UserResource
        return response()->json($request->user());
    }

    public function update(Request $request)
    {
        // TODO: validate
        try {
            $user = $request->user();
            $user->update($request->all());

            return response()->json($user);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reserves()
    {
        $user = auth()->user();
        return $user->reserves();
    }
}
