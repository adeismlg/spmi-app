<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    private const GOOGLE_HOSTED_DOMAIN = 'polinema.ac.id';

    public function show(): View
    {
        return view('auth.login');
    }

    public function redirectToGoogle(): RedirectResponse
    {
        abort_unless($this->googleSsoIsConfigured(), 503, 'Login Google belum dikonfigurasi.');

        return Socialite::driver('google')
            ->with(['hd' => self::GOOGLE_HOSTED_DOMAIN, 'prompt' => 'select_account'])
            ->redirect();
    }

    // public function handleGoogleCallback(Request $request): RedirectResponse
    // {
    //     if ($request->query('error')) {
    //         return redirect()->route('login')->withErrors([
    //             'google' => 'Login Google dibatalkan atau tidak dapat diselesaikan.',
    //         ]);
    //     }

    //     $googleUser = Socialite::driver('google')->user();
    //     $email = strtolower(trim((string) $googleUser->getEmail()));
    //     $googleId = (string) $googleUser->getId();
    //     $emailIsVerified = ($googleUser->user['email_verified'] ?? false) === true;

    //     if (
    //         $googleId === ''
    //         || ! $emailIsVerified
    //         || ! str_ends_with($email, '@'.self::GOOGLE_HOSTED_DOMAIN)
    //     ) {
    //         return redirect()->route('login')->withErrors([
    //             'google' => 'Gunakan akun Google Polinema terverifikasi dengan alamat @polinema.ac.id.',
    //         ]);
    //     }

    //     $user = User::query()->where('google_id', $googleId)->first()
    //         ?? User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

    //     if (! $user) {
    //         return redirect()->route('login')->withErrors([
    //             'google' => 'Akun SPMI belum terdaftar. Hubungi administrator untuk mendapatkan akses.',
    //         ]);
    //     }

    //     if (
    //         ($user->google_id !== null && $user->google_id !== $googleId)
    //         || strtolower($user->email) !== $email
    //     ) {
    //         return redirect()->route('login')->withErrors([
    //             'google' => 'Akun Google tidak cocok dengan akun SPMI. Hubungi administrator.',
    //         ]);
    //     }

    //     $user->forceFill([
    //         'google_id' => $googleId,
    //         'email_verified_at' => $user->email_verified_at ?? now(),
    //     ])->save();

    //     Auth::login($user);
    //     $request->session()->regenerate();

    //     return redirect()->intended(route('dashboard'));
    // }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        if ($request->query('error')) {
            return redirect()->route('login')->withErrors([
                'google' => 'Login Google dibatalkan atau tidak dapat diselesaikan.',
            ]);
        }

        try {
            // Wajib menggunakan stateless() untuk mengatasi masalah sesi di balik Cloudflare Tunnel
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'google' => 'Gagal terhubung dengan Google OAuth: ' . $e->getMessage(),
            ]);
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        $googleId = (string) $googleUser->getId();
        
        // Perbaikan: Google mengembalikan 'verified_email' (bukan email_verified)
        $emailIsVerified = ($googleUser->user['verified_email'] ?? false) === true;

        if (
            $googleId === ''
            || ! $emailIsVerified
            || ! str_ends_with($email, '@'.self::GOOGLE_HOSTED_DOMAIN)
        ) {
            return redirect()->route('login')->withErrors([
                'google' => 'Gunakan akun Google Polinema terverifikasi dengan alamat @polinema.ac.id.',
            ]);
        }

        $user = User::query()->where('google_id', $googleId)->first()
            ?? User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'google' => 'Akun SPMI belum terdaftar. Hubungi administrator untuk mendapatkan akses.',
            ]);
        }

        if (
            ($user->google_id !== null && $user->google_id !== $googleId)
            || strtolower($user->email) !== $email
        ) {
            return redirect()->route('login')->withErrors([
                'google' => 'Akun Google tidak cocok dengan akun SPMI. Hubungi administrator.',
            ]);
        }

        $user->forceFill([
            'google_id' => $googleId,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function googleSsoIsConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
