<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StepFourUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_filter_user_list(): void
    {
        $this->seed(RoleSeeder::class);
        $administrator = $this->createUser(Role::ADMINISTRATEUR);
        $seller = $this->createUser(Role::VENDEUR);

        $this->actingAs($administrator)
            ->get('/admin/utilisateurs?role=vendeur&status=actif')
            ->assertOk()
            ->assertSee($seller->name)
            ->assertDontSee($administrator->name);
    }

    public function test_seller_cannot_access_admin_user_routes(): void
    {
        $this->seed(RoleSeeder::class);
        $seller = $this->createUser(Role::VENDEUR);

        $this->actingAs($seller)
            ->get('/admin/utilisateurs')
            ->assertForbidden();
    }

    public function test_administrator_can_change_role(): void
    {
        $this->seed(RoleSeeder::class);
        $administrator = $this->createUser(Role::ADMINISTRATEUR);
        $seller = $this->createUser(Role::VENDEUR);

        $this->actingAs($administrator)
            ->put(route('admin.users.role.update', $seller), ['role' => Role::MAGASINIER])
            ->assertRedirect();

        $seller->refresh();
        $this->assertSame(
            Role::query()->where('name', Role::MAGASINIER)->value('id'),
            $seller->role_id,
        );
    }

    public function test_deactivation_removes_database_sessions(): void
    {
        $this->seed(RoleSeeder::class);
        $administrator = $this->createUser(Role::ADMINISTRATEUR);
        $seller = $this->createUser(Role::VENDEUR);
        DB::table('sessions')->insert([
            'id' => 'session-test',
            'user_id' => $seller->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($administrator)
            ->post(route('admin.users.deactivate', $seller))
            ->assertRedirect();

        $seller->refresh();
        $this->assertSame(User::STATUS_DESACTIVE, $seller->status);
        $this->assertNotNull($seller->deactivated_at);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $seller->id)->count());
    }

    public function test_profile_update_keeps_old_email_until_verified(): void
    {
        Mail::fake();
        $this->seed(RoleSeeder::class);
        $user = $this->createUser(Role::VENDEUR);
        $oldEmail = $user->email;

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Nom modifié',
            'email' => 'nouvelle@example.com',
            'phone' => '770000999',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame($oldEmail, $user->email);
        $this->assertSame('nouvelle@example.com', $user->pending_email);
    }

    public function test_signed_profile_email_link_changes_email(): void
    {
        $this->seed(RoleSeeder::class);
        $user = $this->createUser(Role::VENDEUR);
        $user->forceFill(['pending_email' => 'nouvelle@example.com'])->save();
        $url = URL::temporarySignedRoute(
            'profile.email.verify',
            now()->addMinutes(60),
            ['user' => $user->id, 'hash' => sha1('nouvelle@example.com')],
        );

        $this->actingAs($user)->get($url)->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('nouvelle@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_administrator_can_delete_account_logically(): void
    {
        $this->seed(RoleSeeder::class);
        $administrator = $this->createUser(Role::ADMINISTRATEUR);
        $seller = $this->createUser(Role::VENDEUR);

        $this->actingAs($administrator)
            ->delete(route('admin.users.destroy', $seller))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $seller->id]);
    }

    private function createUser(string $roleName): User
    {
        return User::factory()->create([
            'status' => User::STATUS_ACTIF,
            'role_id' => Role::query()->where('name', $roleName)->value('id'),
        ]);
    }
}
