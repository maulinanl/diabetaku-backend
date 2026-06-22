<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function loginPage()
    {
        if (session()->has('admin_id')) {
            return redirect()->route('admin.web.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = DB::table('users')
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return back()
                ->withInput()
                ->with('error', 'Email atau password salah.');
        }

        if ((int) $user->role_id !== 1) {
            return back()
                ->withInput()
                ->with('error', 'Akun ini bukan akun admin.');
        }

        if ($user->account_status !== 'Aktif') {
            return back()
                ->withInput()
                ->with('error', 'Akun admin tidak aktif.');
        }

        session([
            'admin_id' => $user->user_id,
            'admin_name' => $user->full_name,
            'admin_email' => $user->email,
        ]);

        return redirect()->route('admin.web.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
