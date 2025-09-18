<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->registerUser($request->validated());

        event(new Registered($user));
        Auth::login($user);
        session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function login(LoginRequest $request)
    {
        if ($this->authService->login($request->only('email', 'password'), 'user')) {
            $request->session()->regenerate();
            return redirect()->route('attendance.index');
        }
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    public function admin_login()
    {
        return view('admin.login');
    }

    public function admin_login_form(LoginRequest $request)
    {
        if ($this->authService->login($request->only('email', 'password'), 'admin')) {
            $request->session()->regenerate();
            return redirect()->route('admin.attendance_list');
        }
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
