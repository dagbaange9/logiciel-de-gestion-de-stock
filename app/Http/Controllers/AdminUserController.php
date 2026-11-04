<?php

namespace App\Http\Controllers;

use App\Events\UserRoleChanged;
use App\Events\UserStatusChanged;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('role')
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->when(
                $request->filled('role'),
                fn ($query) => $query->whereHas(
                    'role',
                    fn ($roleQuery) => $roleQuery->where('name', $request->string('role')->toString()),
                ),
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'statuses' => User::STATUSES,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('admin.users.show', [
            'user' => $user->load('role'),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = Role::query()
            ->where('name', $request->validated('role'))
            ->firstOrFail();
        $previousRoleId = $user->role_id;

        $user->forceFill(['role_id' => $role->id])->save();

        if ($previousRoleId !== $role->id) {
            UserRoleChanged::dispatch($user, $previousRoleId);
        }

        return back()->with('status', 'Le rôle a été modifié. Il prendra effet lors de la prochaine connexion.');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->forceFill([
            'status' => User::STATUS_ACTIF,
            'deactivated_at' => null,
        ])->save();
        UserStatusChanged::dispatch($user, 'activation');

        return back()->with('status', 'Le compte a été activé.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->forceFill([
            'status' => User::STATUS_DESACTIVE,
            'deactivated_at' => now(),
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        UserStatusChanged::dispatch($user, 'desactivation');

        return back()->with('status', 'Le compte a été désactivé.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'Le compte a été supprimé logiquement.');
    }
}
