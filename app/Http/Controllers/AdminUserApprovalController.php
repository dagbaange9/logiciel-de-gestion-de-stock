<?php

namespace App\Http\Controllers;

use App\Events\UserApproved;
use App\Events\UserRejected;
use App\Http\Requests\DecideUserRequest;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class AdminUserApprovalController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('role')
            ->where('status', User::STATUS_EN_ATTENTE_VALIDATION)
            ->latest()
            ->get();

        return view('admin.users.pending', ['users' => $users]);
    }

    public function approve(DecideUserRequest $request, User $user): RedirectResponse
    {
        $role = Role::query()
            ->where('name', $request->validated('role'))
            ->firstOrFail();

        if ($user->status !== User::STATUS_EN_ATTENTE_VALIDATION) {
            return back()->withErrors(['user' => 'Ce compte n’est pas en attente de validation.']);
        }

        $user->forceFill([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIF,
        ])->save();

        UserApproved::dispatch($user);

        try {
            Mail::to($user->email)->send(new AccountApprovedMail($user));
        } catch (Throwable $exception) {
            report($exception);
        }

        return back()->with('status', 'Le compte a été approuvé.');
    }

    public function refuse(DecideUserRequest $request, User $user): RedirectResponse
    {
        if ($user->status !== User::STATUS_EN_ATTENTE_VALIDATION) {
            return back()->withErrors(['user' => 'Ce compte n’est pas en attente de validation.']);
        }

        $user->forceFill([
            'status' => User::STATUS_REFUSE,
        ])->save();

        UserRejected::dispatch($user);

        try {
            Mail::to($user->email)->send(new AccountRejectedMail($user));
        } catch (Throwable $exception) {
            report($exception);
        }

        return back()->with('status', 'Le compte a été refusé.');
    }

    public function resetPassword(DecideUserRequest $request, User $user): RedirectResponse
    {
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', 'Le lien de réinitialisation a été envoyé.');
    }
}
