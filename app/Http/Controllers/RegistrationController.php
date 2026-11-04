<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.inscription');
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
        ]);
        $user->forceFill([
            'status' => User::STATUS_EMAIL_NON_VERIFIE,
        ])->save();

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('verification.pending');
    }

    public function pending(): View
    {
        return view('auth.verification-pending');
    }
}
