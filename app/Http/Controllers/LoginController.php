<?php

namespace App\Http\Controllers;

use App\Events\UserLoggedIn;
use App\Events\UserLoginFailed;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.connexion');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $key = Str::lower($email).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $response = back()->withErrors([
                'email' => 'Trop de tentatives. Réessayez dans quelques instants.',
            ]);
            $response->setStatusCode(429);

            return $response;
        }

        if (! Auth::attempt([
            'email' => $email,
            'password' => $validated['password'],
        ])) {
            RateLimiter::hit($key, 60);
            UserLoginFailed::dispatch($email, $request->ip());

            throw ValidationException::withMessages([
                'email' => 'Ces identifiants sont incorrects.',
            ]);
        }

        $user = $request->user();
        if ($user->status !== User::STATUS_ACTIF) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Votre compte n’est pas encore actif.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('auth_role', $user->role?->name);
        UserLoggedIn::dispatch($user, $request->ip());

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget('auth_role');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
