<?php

namespace App\Http\Controllers;

use App\User;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();
        return response()->json($users);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function store()
    {
        $this->validate(request(), [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = User::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => request('password')
        ]);

        return response()->json($user);
    }

    public function update(User $user)
    {
        $user->name = 'New Name';
        $user->save();

        return response()->json($user);
    }

    public function delete(User $user)
    {
        $user->delete();

        return response()->json(null);
    }
}
