<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        if ($user->hasVerifiedEmail()) {
            return response(['message' => 'The email has already been confirmed']);
        }
        
        $user->markEmailAsVerified();

        return response([
            'message' => 'Email has been successfully confirmed'
        ]);
    }
}
