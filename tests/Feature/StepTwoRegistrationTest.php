<?php

namespace Tests\Feature;

use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StepTwoRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_unverified_account_without_role(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $this->post(route('register.store'), [
            'name' => 'Vendeur Test',
            'email' => 'vendeur@example.com',
            'phone' => '770000001',
            'password' => 'motdepasse',
            'password_confirmation' => 'motdepasse',
        ])->assertRedirect(route('verification.pending'));

        $user = User::query()->where('email', 'vendeur@example.com')->firstOrFail();
        $this->assertSame(User::STATUS_EMAIL_NON_VERIFIE, $user->status);
        $this->assertNull($user->role_id);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_signed_email_verification_moves_account_to_pending_validation(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->unverified()->create([
            'status' => User::STATUS_EMAIL_NON_VERIFIE,
        ]);
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($url)->assertRedirect(route('verification.pending'));

        $user->refresh();
        $this->assertSame(User::STATUS_EN_ATTENTE_VALIDATION, $user->status);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_non_administrator_cannot_approve_pending_account(): void
    {
        $this->seed(RoleSeeder::class);
        $seller = User::factory()->create([
            'status' => User::STATUS_ACTIF,
            'role_id' => Role::query()->where('name', Role::VENDEUR)->value('id'),
        ]);
        $pending = User::factory()->create([
            'status' => User::STATUS_EN_ATTENTE_VALIDATION,
        ]);

        $this->actingAs($seller)->post(
            route('admin.users.approve', $pending),
            ['role' => Role::VENDEUR],
        )->assertForbidden();

        $pending->refresh();
        $this->assertSame(User::STATUS_EN_ATTENTE_VALIDATION, $pending->status);
    }

    public function test_administrator_can_approve_pending_account(): void
    {
        Mail::fake();
        $this->seed(RoleSeeder::class);
        $administrator = $this->createUser(Role::ADMINISTRATEUR);
        $pending = User::factory()->create([
            'status' => User::STATUS_EN_ATTENTE_VALIDATION,
        ]);

        $this->actingAs($administrator)->post(
            route('admin.users.approve', $pending),
            ['role' => Role::MAGASINIER],
        )->assertRedirect();

        $pending->refresh();
        $this->assertSame(User::STATUS_ACTIF, $pending->status);
        $this->assertSame(Role::MAGASINIER, $pending->role->name);
        Mail::assertSent(AccountApprovedMail::class);
    }

    public function test_administrator_can_refuse_pending_account(): void
    {
        Mail::fake();
        $this->seed(RoleSeeder::class);
        $administrator = $this->createUser(Role::ADMINISTRATEUR);
        $pending = User::factory()->create([
            'status' => User::STATUS_EN_ATTENTE_VALIDATION,
        ]);

        $this->actingAs($administrator)->post(
            route('admin.users.refuse', $pending),
        )->assertRedirect();

        $pending->refresh();
        $this->assertSame(User::STATUS_REFUSE, $pending->status);
        Mail::assertSent(AccountRejectedMail::class);
    }

    private function createUser(string $roleName): User
    {
        return User::factory()->create([
            'status' => User::STATUS_ACTIF,
            'role_id' => Role::query()->where('name', $roleName)->value('id'),
        ]);
    }
}
