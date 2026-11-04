<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class StepThreeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_account_can_login_and_logout(): void
    {
        $this->seed(RoleSeeder::class);
        $user = $this->createActiveUser();

        $response = $this->post('/connexion', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/tableau-de-bord');
        $this->assertAuthenticatedAs($user);

        $logout = $this->post('/deconnexion');

        $logout->assertRedirect('/connexion');
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login(): void
    {
        $this->seed(RoleSeeder::class);
        $user = $this->createActiveUser([
            'status' => User::STATUS_EN_ATTENTE_VALIDATION,
        ]);

        $response = $this->from('/connexion')->post('/connexion', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/connexion');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_account_is_rejected_on_next_request(): void
    {
        $this->seed(RoleSeeder::class);
        $user = $this->createActiveUser();

        $this->actingAs($user)->get('/tableau-de-bord')->assertOk();

        $user->forceFill([
            'status' => User::STATUS_DESACTIVE,
        ])->save();

        $this->actingAs($user)
            ->get('/tableau-de-bord')
            ->assertRedirect('/connexion');
        $this->assertGuest();
    }

    public function test_login_is_limited_after_five_attempts(): void
    {
        $key = 'unknown@example.com|127.0.0.1';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/connexion', [
                'email' => 'unknown@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->post('/connexion', [
            'email' => 'unknown@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5));
    }

    public function test_password_reset_response_is_not_enumerating(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);
        $known = $this->createActiveUser();

        $responseKnown = $this->post('/mot-de-passe/oublie', ['email' => $known->email]);
        $responseUnknown = $this->post('/mot-de-passe/oublie', [
            'email' => 'unknown@example.com',
        ]);

        $responseKnown->assertSessionHas('status');
        $responseUnknown->assertSessionHas('status');
        $this->assertSame(
            $responseKnown->getSession()->get('status'),
            $responseUnknown->getSession()->get('status'),
        );
        Notification::assertSentTo($known, ResetPassword::class);
    }

    public function test_password_reset_replaces_password_with_minimum_of_eight_characters(): void
    {
        $this->seed(RoleSeeder::class);
        $user = $this->createActiveUser();
        $token = Password::createToken($user);

        $response = $this->post('/mot-de-passe/reinitialiser', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nouveaumotdepasse',
            'password_confirmation' => 'nouveaumotdepasse',
        ]);

        $response->assertRedirect('/connexion');
        $user->refresh();
        $this->assertTrue(Hash::check('nouveaumotdepasse', $user->password));
    }

    public function test_administrator_can_send_password_reset_assistance(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $administrator = User::factory()->create([
            'status' => User::STATUS_ACTIF,
            'role_id' => Role::query()->where('name', Role::ADMINISTRATEUR)->value('id'),
        ]);
        $user = $this->createActiveUser();

        $response = $this->actingAs($administrator)->post(
            route('admin.users.reset-password', $user),
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    private function createActiveUser(array $attributes = []): User
    {
        $role = Role::query()->where('name', Role::VENDEUR)->firstOrFail();

        return User::factory()->create(array_merge([
            'status' => User::STATUS_ACTIF,
            'role_id' => $role->id,
            'password' => Hash::make('password'),
        ], $attributes));
    }
}
