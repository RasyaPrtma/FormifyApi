<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthenticationController extends Controller
{
    //Login Function
    public function login(Request $request){
        // Make Validation
        $validate = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required|min:5'
        ],[
            'email' => 'The :attribute must be a valid email address.',
            'required' => 'The :attribute field is required',
            'min' => 'The :attribute Character Minimum Length Is 5'
        ]);

        // Check Validation
        if($validate->fails()){
            return response($validate->errors(),422);
        }

        // Check If Email/Password Correct
        $user = User::where('email',$request->email)->first();
        if($user !== null && Hash::check($request->password,$user->password)){
            // Create Token
            $token = $user->createToken($user->id)->plainTextToken;
            // return message success
            return response()->json([
                'message' => 'Login Success',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'accessToken' => $token
                ]
            ],200);
        }
        // return fails login
        return response()->json(['message' => 'Email or Password incorrect'],401);
    }
    // Logout Function
    public function logout(Request $request){
        // Delete Token
        $request->user()->currentAccessToken()->delete();
        // Return response
        return response()->json(['message' => 'Logout success'],200);
    }
}
