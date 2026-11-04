<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals((string) sha1($user->getEmailForVerification()), $hash)) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('verification.pending');
        }

        $user->markEmailAsVerified();
        $user->forceFill([
            'status' => User::STATUS_EN_ATTENTE_VALIDATION,
        ])->save();

        return redirect()->route('verification.pending');
    }
}
