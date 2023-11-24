<?php

namespace App\Http\Controllers;

use App\Exceptions\AlreadyRegisteredException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validation = Validator::make($request->all(), [
           'first_name' => 'required',
           'last_name' => 'required',
           'email' => 'required',
           'password' => 'required',
        ]);
        if ($validation->fails()){
            return response()->json(['errors' => $validation->errors()]);
        }

        $request->validate(['email' => 'required|email'], $request->only(['email', 'password']));

        if (User::query()->where('email', $request->email)->first()) {
            return new AlreadyRegisteredException('ایمیل وارد شده تکراری است', Response::HTTP_BAD_REQUEST);
        } else {
            $user = User::query()->create($request->all());
            return \response()->json(['user' => $user], 201);
        }
    }

    public function verify(Request $request)
    {
        $request->validate(['email' => 'required|email'], $request->only(['email', 'password']));

        if ($user = User::query()->where('email', $request->email)->first()) {
            $user->email_verified_at = now();
            $user->save();
            return $user->createToken('Abbra Access Token');
        } else {
            return \response()->json(['message' => 'کاربری با این ایمیل یافت نشد'], Response::HTTP_NOT_FOUND);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], $request->all());

        if ($user = User::query()->where('email', $request->email)->first()) {
            if (Hash::check($request->password, $user->password)) {
                return $user->createToken('Abbra Access Token');
            } else {
                return \response()->json(['message' => 'کاربر یافت نشد'], Response::HTTP_NOT_FOUND);
            }
        } else {
            return \response()->json(['message' => 'کاربر یافت نشد'], Response::HTTP_NOT_FOUND);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            return \response(['message' => 'توکن کاربر با موفقیت حذف شد']);
        } catch (\Exception $exception) {
            Log::error($exception);
            return \response()->json(['message' => $exception->getMessage()]);
        }
    }
}
