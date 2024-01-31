<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer',
            'size' => 'integer',
            'sort_by' => 'in:firstName, lastName',
            'sort_dir' => 'in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Invalid input',
                'errors' => $validator->errors(),
            ], 400);
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('size', 5);
        $sortBy = $request->input('sort_by', 'firstName');
        $sortDir = $request->input('sort_dir', 'asc');

        $usersQuery = User::query();

        $usersQuery->orderBy($sortBy, $sortDir);

        $users = $usersQuery->paginate($pageSize, ['*'], 'page', $page);

        $result = [
            'status_code' => 200,
            'message' => 'OK',
            'data' => $users,
        ];

        return response()->json($result, 200);
    }




    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username',
            'firstName' => 'required|string',
            'middleName' => 'string|nullable',
            'lastName' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400);
        }

        $user = new User();
        $user->username = $request->input('username');
        $user->firstName = $request->input('firstName');
        $user->middleName = $request->input('middleName');
        $user->lastName = $request->input('lastName');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));

        $user->save();

        return response()->json([
            'status_code' => 201,
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'string|unique:users,username,' . $user->id,
            'firstName' => 'string',
            'middleName' => 'string|nullable',
            'lastName' => 'string',
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => 'string|min:8|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400);
        }

        $user->update([
            'username' => $request->input('username', $user->username),
            'firstName' => $request->input('firstName', $user->firstName),
            'middleName' => $request->input('middleName', $user->middleName),
            'lastName' => $request->input('lastName', $user->lastName),
            'email' => $request->input('email', $user->email),
            'password' => $request->filled('password') ? Hash::make($request->input('password')) : $user->password,
        ]);

        return response()->json([
            'status_code' => 200,
            'message' => 'User updated successfully',
            'data' => $user,
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'status_code' => 200,
            'message' => 'User soft-deleted successfully',
        ], 200);
    }
}
