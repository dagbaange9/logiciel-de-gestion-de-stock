<?php

namespace App\Http\Controllers;

use App\Events\UserEmailChanged;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $newEmail = strtolower(trim((string) $validated['email']));

        $user->forceFill([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ])->save();

        if (! empty($validated['password'])) {
            $user->forceFill(['password' => $validated['password']])->save();
        }

        if ($newEmail !== $user->email) {
            $user->forceFill(['pending_email' => $newEmail])->save();
            $verificationUrl = URL::temporarySignedRoute(
                'profile.email.verify',
                now()->addMinutes(60),
                ['user' => $user->id, 'hash' => sha1($newEmail)],
            );

            try {
                Mail::raw(
                    'Confirmez votre nouvelle adresse e-mail en ouvrant ce lien valide 60 minutes : '.$verificationUrl,
                    fn ($message) => $message->to($newEmail)->subject('Vérification de votre adresse e-mail'),
                );
            } catch (Throwable $exception) {
                report($exception);
            }

            $status = 'Le profil a été enregistré. Une nouvelle adresse e-mail est en attente de vérification.';
        } else {
            $status = 'Le profil a été enregistré.';
        }

        return back()->with('status', $status);
    }

    public function verifyEmail(Request $request, User $user, string $hash): RedirectResponse
    {
        if (! $request->user()->is($user) || $user->pending_email === null) {
            abort(403);
        }

        if (! hash_equals(sha1($user->pending_email), $hash)) {
            abort(403);
        }

        if (User::query()
            ->withTrashed()
            ->where('email', strtolower($user->pending_email))
            ->whereKeyNot($user->id)
            ->exists()) {
            return redirect()->route('profile.edit')->withErrors([
                'email' => 'Cette adresse e-mail est déjà utilisée.',
            ]);
        }

        $user->forceFill([
            'email' => $user->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
        ])->save();
        UserEmailChanged::dispatch($user);

        return redirect()->route('profile.edit')->with('status', 'La nouvelle adresse e-mail a été vérifiée.');
    }
}
