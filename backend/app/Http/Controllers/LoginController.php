<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function submit(Request $request)
    {
        # validate the phone number
        $request->validate([
            // 'attribute' => 'rules',
            'phone' => 'required|numeric|min-digits:10',
        ]);
        # find or create a user model
        // return response()->json([
        //   'phone' => $request->phone,
        // ]);
        $user = User::firstOrCreate([
            'phone' => $request->phone,
        ]);

        if (!$user) {
            return response()->json([
                'message' => 'Could not process user with that phone number.',
            ], 401);
        }

        # send the user a one-time use code
        // $user->notify(new LoginNeedsVerification());
        $loginCode = rand(111111, 999999);
        $user->update([
            'login_code' => $loginCode,
        ]);


        # return back a response
        return response()->json([
            'message' => "verification text {$loginCode}",
        ]);
    }

    public function verify(Request $request)
    {
        # validate incoming request
        $request->validate([
            'phone' => 'required|numeric|min:10',
            'login_code' => 'required|numeric|between:111111,999999',
        ]);

        # find the user
        $user = User::where('phone', $request->phone)
            ->where('login_code', $request->login_code)
            ->first();

        # is the code provided the same as saved?
        # if not return back message
        if (!$user) {
            return response()->json([
                'message' => 'Invalid verification code',
            ], 401);
        }

        # if so, return back an auth token
        $user->update([
            'login_code' => null,
        ]);
        return $user->createToken($request->login_code)->plainTextToken;
    }
}
