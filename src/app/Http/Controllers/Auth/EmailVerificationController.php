<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function show() {
        return view('auth.verify');
    }

    public function verify(EmailVerificationRequest $request) {
        $request->fulfill();
        Auth::login($request->user());
        return redirect()->route('attendance');
    }

    public function resend(Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back();
    }
}
