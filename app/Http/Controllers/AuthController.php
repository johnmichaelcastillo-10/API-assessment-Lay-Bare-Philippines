<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username',
            'firstName' => 'required|string',
            'middleName' => 'required|string',
            'lastName' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Validation error occurred',
                'errors' => $validator->errors()
            ]);
        }

        $register = new User();
        $register->username = $request->input('username');
        $register->firstName = $request->input('firstName');
        $register->middleName = $request->input('middleName');
        $register->lastName = $request->input('lastName');
        $register->email = $request->input('email');
        $register->password = Hash::make($request->input('password'));

        $register->save();

        $token = $register->createToken('registerToken')->plainTextToken;



        return response()->json([
            'status_code' => 201,
            'message' => 'Registered successfully',
            'data' => $register,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);


        $user = User::where('username', $fields['username'])->first();


        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json([
                'status_code' => 401,
                'message' => 'Bad Credentials'
            ], 401);
        }


        $token = $user->createToken('loginToken')->plainTextToken;

        return response()->json([
            'status_code' => 201,
            'message' => 'Login successfully',
            'data' => $user,
            'token' => $token
        ], 201);
    }

    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'status_code' => 200,
            'message' => 'Logged out'
        ], 200);
    }
}
