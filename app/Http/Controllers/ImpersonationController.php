<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user
     */
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        // Only admin_spmi can impersonate
        if (! $request->user()->hasRole('admin_spmi')) {
            throw new AuthenticationException('Unauthorized to impersonate users.');
        }

        // Cannot impersonate yourself
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat menyamar sebagai diri sendiri.');
        }

        // Store original admin user ID in session
        session(['impersonated_by' => $request->user()->id, 'impersonated_user' => $user->id]);

        // Login as the target user
        auth()->login($user, false);

        return redirect()->route('dashboard')->with('success', "Anda sekarang login sebagai {$user->name}. Gunakan tombol di header untuk kembali.");
    }

    /**
     * Stop impersonating and return to admin account
     */
    public function stopImpersonating(Request $request): RedirectResponse
    {
        // Check if currently impersonating
        if (! session('impersonated_by')) {
            return back()->with('error', 'Anda tidak sedang menyamar sebagai pengguna lain.');
        }

        $adminId = session('impersonated_by');
        $admin = User::find($adminId);

        if (! $admin) {
            auth()->logout();
            session()->flush();

            return redirect()->route('login')->with('error', 'Admin user tidak ditemukan.');
        }

        // Clear impersonation session
        session()->forget(['impersonated_by', 'impersonated_user']);

        // Login back as admin
        auth()->login($admin, false);

        return redirect()->route('users.index')->with('success', 'Berhasil kembali ke akun admin.');
    }
}
