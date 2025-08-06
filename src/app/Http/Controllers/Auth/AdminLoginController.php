<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AdminLoginController extends Controller
{
    public function showLogin () {
        return view('auth.admin_login');
    }

    public function login (LoginRequest $request) {
        $credentials = $request->only('email', 'password');
        //$credentials['authority'] = 1;

        if (Auth::attempt($credentials)) {
            if (Auth::user()->authority == 1) {
                return redirect()->route('admin.attendance.list');
            } else {
                Auth::logout();
                return redirect()->route('admin.login')->withErrors(['email' => '管理者権限がありません']);
            }
        }
        return back()->withErrors(['email' => 'ログイン情報が登録されていません']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
