<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    public function update(UpdateUserRequest $request)
    {
        try {
            $user = $request->user();
            $user->update($request->all());

            return response()->json($user);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function promote()
    {
        if (auth()->user()->isAdmin() || User::query()->count() === 1) {
            $user = auth()->user();
            $user->role = User::ROLE_ADMIN;
            $user->save();
            return \response()->json(['message' => 'کاربر با موفقیت ارتقا یافت']);
        } else {
            return \response()->json(['message' => 'شما مجاز به انجام این کار نیستید'],  Response::HTTP_FORBIDDEN);
        }
    }

    public function reserves()
    {
        $user = auth()->user();
        return $user->reserves();
    }
}
