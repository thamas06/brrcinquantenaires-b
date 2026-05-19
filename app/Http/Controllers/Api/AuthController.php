<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $data['email'])->first();
        if(!$user || !Hash::check($data['password'], $user->password)){
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => 'employee'
        ]);

        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    public function assignRole(Request $request, $id)
    {
        $actor = $request->user();
        if(!$actor) return response()->json(['message'=>'Unauthorized'],401);

        $data = $request->validate([
            'role' => 'required|string'
        ]);
        $role = $data['role'];

        // allowed roles set
        $allowedRoles = ['admin','manager','caissier','employee'];
        if(!in_array($role, $allowedRoles)){
            return response()->json(['message'=>'Invalid role'], 422);
        }

        // Permission rules
        // admin can assign any of manager, caissier, employee
        // manager can assign caissier and employee
        if($actor->role === 'admin'){
            // ok
        }elseif($actor->role === 'manager'){
            if(!in_array($role, ['caissier','employee'])){
                return response()->json(['message'=>'Forbidden'],403);
            }
        }else{
            return response()->json(['message'=>'Forbidden'],403);
        }

        $user = User::find($id);
        if(!$user) return response()->json(['message'=>'User not found'],404);

        $user->role = $role;
        $user->save();

        return response()->json(['message'=>'Role updated','user'=>$user]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if($user && $user->currentAccessToken()){
            $user->currentAccessToken()->delete();
        }
        return response()->json(['message'=>'Logged out']);
    }

    public function users(Request $request)
    {
        return User::select('id','name','email','role')->get();
    }
}
