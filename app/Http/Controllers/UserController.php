<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function show(Request $request)
    {
        return response()->json(new UserResource($request->user()));
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
            return \response()->json(['message' => 'شما مجاز به انجام این کار نیستید'], Response::HTTP_FORBIDDEN);
        }
    }

    public function avatar(Request $request)
    {
        $validation = Validator::make($request->all(), ['photo' => 'required|image']);
        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $request->user();
            if (Storage::disk('avatars')->exists($user->avatar)) {
                Storage::disk('avatars')->delete($user->avatar);
            }

            $file = $request->file('photo');
            $fileName = md5($file->hashName() . time());
            $photo = $file->storeAs('', $fileName, 'avatars');
            $user->avatar = $fileName;
            $user->save();

            $avatar = asset('storage/avatars/' . $photo);

            return \response()->json(compact('avatar'), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return \response()->json(['message' => 'خطا در ثبت عکس پروفایل'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reserves()
    {
        $user = auth()->user();
        return $user->reserves();
    }
}
