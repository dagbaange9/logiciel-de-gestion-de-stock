<?php

namespace Tests\Feature;

use App\Events\UserApproved;
use App\Events\UserLoggedIn;
use App\Events\UserLoginFailed;
use App\Events\UserRejected;
use App\Events\UserRoleChanged;
use App\Events\UserStatusChanged;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class StepFiveAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_account_cannot_access_dashboard(): void
    {
        $this->seed(RoleSeeder::class);
        $pending = $this->createUser(User::STATUS_EN_ATTENTE_VALIDATION, Role::VENDEUR);

        $this->from('/connexion')->post('/connexion', [
            'email' => $pending->email,
            'password' => 'password',
        ])->assertRedirect('/connexion');

        $this->assertGuest();
    }

    public function test_non_administrator_roles_receive_forbidden_on_administration_routes(): void
    {
        $this->seed(RoleSeeder::class);

        foreach ([Role::VENDEUR, Role::MAGASINIER] as $roleName) {
            $user = $this->createUser(User::STATUS_ACTIF, $roleName);

            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.users.show', $user))->assertForbidden();
        }
    }

    public function test_sixth_login_attempt_is_rate_limited(): void
    {
        $email = 'unknown@example.com';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/connexion', [
                'email' => $email,
                'password' => 'wrongpassword',
            ]);
        }

        $this->post('/connexion', [
            'email' => $email,
            'password' => 'wrongpassword',
        ])->assertStatus(429);

        $this->assertTrue(RateLimiter::tooManyAttempts($email.'|127.0.0.1', 5));
    }

    public function test_sensitive_events_are_dispatched(): void
    {
        Event::fake([
            UserApproved::class,
            UserRejected::class,
            UserLoggedIn::class,
            UserLoginFailed::class,
            UserRoleChanged::class,
            UserStatusChanged::class,
        ]);
        $this->seed(RoleSeeder::class);
        $user = $this->createUser(User::STATUS_ACTIF, Role::VENDEUR);

        UserApproved::dispatch($user);
        UserRejected::dispatch($user);
        UserLoggedIn::dispatch($user, '127.0.0.1');
        UserLoginFailed::dispatch('unknown@example.com', '127.0.0.1');
        UserRoleChanged::dispatch($user, $user->role_id);
        UserStatusChanged::dispatch($user, 'activation');

        Event::assertDispatched(UserApproved::class);
        Event::assertDispatched(UserRejected::class);
        Event::assertDispatched(UserLoggedIn::class);
        Event::assertDispatched(UserLoginFailed::class);
        Event::assertDispatched(UserRoleChanged::class);
        Event::assertDispatched(UserStatusChanged::class);
    }

    private function createUser(string $status, string $roleName): User
    {
        return User::factory()->create([
            'status' => $status,
            'role_id' => Role::query()->where('name', $roleName)->value('id'),
            'password' => Hash::make('password'),
        ]);
    }
}
