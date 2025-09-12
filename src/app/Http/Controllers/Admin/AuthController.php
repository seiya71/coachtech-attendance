<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function admin_login()
    {
        return view('admin.login');
    }

    public function admin_login_form(LoginRequest $request)
    {
        if (
            Auth::attempt([
                'email' => $request->email,
                'password' => $request->password,
                'role' => 'admin',
            ])
        ) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.attendance_list'));
        }

        return back();
    }
}
