<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\MailCode;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = User::create($data);

        event(new Registered($user));
        Auth::login($user);
        

        return [
            'message' => 'Created user. The email confirmation link has been sent',
            'data' => $user
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        return $request->user();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update_name(UpdateUserRequest $request)
    {
        $data = $request->validate([
            'name' => ['required']
        ]);
        $user = $request->user();

        $user->update($data);

        return [
            'message' => 'Name updated'
        ];
    }

    function update_email(Request $request) {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $code = rand(100000, 999999);

        Mail::to($request->user())->queue(new MailCode($code));

        return [
            'message' => 'Enter the code'
        ];
    }
}
