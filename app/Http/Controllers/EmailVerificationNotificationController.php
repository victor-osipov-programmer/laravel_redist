<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response(['message' => 'The email has already been confirmed']);
        }
        
        $request->user()->sendEmailVerificationNotification();

        return response([
            'message' => 'The confirmation link has been sent to the mail'
        ]);
    }
}
