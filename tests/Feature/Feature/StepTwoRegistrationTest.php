<?php

namespace Tests\Feature\Feature;

use App\Events\UserApproved;
use App\Events\UserRejected;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StepTwoRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_an_unverified_account_and_sends_verification_email(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $response = $this->post('/inscription', [
            'name' => 'Candidate Test',
            'email' => 'Candidate@Example.com',
            'phone' => '770000123',
            'password' => 'motdepasse',
            'password_confirmation' => 'motdepasse',
            'role' => Role::ADMINISTRATEUR,
        ]);

        $response->assertRedirect(route('verification.pending'));

        $user = User::query()->where('email', 'candidate@example.com')->firstOrFail();
        $this->assertSame(User::STATUS_EMAIL_NON_VERIFIE, $user->status);
        $this->assertNull($user->role_id);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_signed_verification_link_moves_account_to_pending_validation(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->unverified()->create([
            'status' => User::STATUS_EMAIL_NON_VERIFIE,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
        );

        $response = $this->get($url);

        $response->assertRedirect(route('verification.pending'));
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(User::STATUS_EN_ATTENTE_VALIDATION, $user->status);
    }

    public function test_administrator_approves_pending_account_and_assigns_selected_role(): void
    {
        Event::fake([UserApproved::class]);
        Mail::fake();
        $this->seed(RoleSeeder::class);

        $administrator = $this->createAdministrator();
        $pending = User::factory()->create([
            'status' => User::STATUS_EN_ATTENTE_VALIDATION,
            'role_id' => null,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($administrator)->post(
            route('admin.users.approve', $pending),
            ['role' => Role::MAGASINIER],
        );

        $response->assertRedirect();
        $pending->refresh();
        $this->assertSame(User::STATUS_ACTIF, $pending->status);
        $this->assertSame(Role::MAGASINIER, $pending->role->name);
        Event::assertDispatched(UserApproved::class);
        Mail::assertSent(AccountApprovedMail::class, fn (AccountApprovedMail $mail): bool => $mail->user->is($pending));
    }

    public function test_seller_cannot_approve_or_refuse_an_account(): void
    {
        $this->seed(RoleSeeder::class);
        $seller = User::factory()->create();
        $pending = User::factory()->create([
            'status' => User::STATUS_EN_ATTENTE_VALIDATION,
            'role_id' => null,
        ]);

        $response = $this->actingAs($seller)->post(
            route('admin.users.approve', $pending),
            ['role' => Role::VENDEUR],
        );

        $response->assertForbidden();
    }

    public function test_administrator_refuses_pending_account(): void
    {
        Event::fake([UserRejected::class]);
        Mail::fake();
        $this->seed(RoleSeeder::class);

        $administrator = $this->createAdministrator();
        $pending = User::factory()->create([
            'status' => User::STATUS_EN_ATTENTE_VALIDATION,
            'role_id' => null,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($administrator)->post(route('admin.users.refuse', $pending));

        $response->assertRedirect();
        $pending->refresh();
        $this->assertSame(User::STATUS_REFUSE, $pending->status);
        Event::assertDispatched(UserRejected::class);
        Mail::assertSent(AccountRejectedMail::class, fn (AccountRejectedMail $mail): bool => $mail->user->is($pending));
    }

    private function createAdministrator(): User
    {
        $role = Role::query()->where('name', Role::ADMINISTRATEUR)->firstOrFail();

        return User::factory()->create([
            'status' => User::STATUS_ACTIF,
            'role_id' => $role->id,
        ]);
    }
}
