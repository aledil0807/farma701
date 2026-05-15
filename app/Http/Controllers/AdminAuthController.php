<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $adminEmail = config('services.admin_auth.email');
        $adminPassword = config('services.admin_auth.password');

        if (
            $request->email === $adminEmail &&
            $request->password === $adminPassword
        ) {
            $request->session()->put('admin_authenticated', true);
            $request->session()->put('admin_email', $request->email);

            return redirect()->route('admin.dashboard');
        }

        return back()
            ->withErrors(['login' => 'Credenciales inválidas.'])
            ->withInput();
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_authenticated');
        $request->session()->forget('admin_email');

        return redirect()->route('admin.login');
    }
}