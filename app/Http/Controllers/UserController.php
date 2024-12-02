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
            'email' => ['required', 'email', Rule::unique('users')],
            'code' => ['required', 'digits:6', Rule::exists('users')]
        ]);
        $user = $request->user();

        $user->update([
            'email' => $data['email'],
            'code' => null,
            'email_verified_at' => null
        ]);
        $user->sendEmailVerificationNotification();

        return [
            'message' => 'Confirm the new email'
        ];
    }


    function get_code(Request $request) {
        $user = $request->user();
        
        $code = rand(100000, 999999);
        $user->update([
            'code' => $code
        ]);

        Mail::to($request->user())->queue(new MailCode($code));

        return [
            'message' => 'The confirmation code was sent to the old email'
        ];
    }
}
