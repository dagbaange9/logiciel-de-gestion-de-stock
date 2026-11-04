<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StepOneDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeder_creates_the_three_roles_without_duplicates(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertSame(
            3,
            Role::query()->whereIn('name', [
                Role::ADMINISTRATEUR,
                Role::MAGASINIER,
                Role::VENDEUR,
            ])->count(),
        );
    }

    public function test_initial_administrator_is_active_and_password_is_hashed(): void
    {
        $this->seed(RoleSeeder::class);
        putenv('INITIAL_ADMIN_PASSWORD=MotDePasseTest');

        $this->artisan('app:create-initial-administrator', [
            '--name' => 'Administrateur initial',
            '--email' => 'Admin@Example.com',
            '--phone' => '770000000',
        ])->assertSuccessful();

        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame(User::STATUS_ACTIF, $administrator->status);
        $this->assertSame(Role::ADMINISTRATEUR, $administrator->role->name);
        $this->assertNotSame('MotDePasseTest', $administrator->password);
        $this->assertTrue(password_verify('MotDePasseTest', $administrator->password));
    }

    public function test_email_is_unique_without_case_difference(): void
    {
        $this->seed(RoleSeeder::class);
        $role = Role::query()->where('name', Role::VENDEUR)->firstOrFail();

        User::factory()->create([
            'email' => 'User@Example.com',
            'phone' => '770000001',
            'role_id' => $role->id,
        ]);

        $this->expectException(QueryException::class);

        User::factory()->create([
            'email' => 'user@example.COM',
            'phone' => '770000002',
            'role_id' => $role->id,
        ]);
    }

    public function test_user_status_is_limited_to_the_documented_values(): void
    {
        $this->seed(RoleSeeder::class);
        $role = Role::query()->where('name', Role::VENDEUR)->firstOrFail();

        $this->expectException(ValidationException::class);

        User::factory()->create([
            'status' => 'statut_inconnu',
            'role_id' => $role->id,
        ]);
    }

    public function test_role_used_by_a_user_cannot_be_deleted(): void
    {
        $this->seed(RoleSeeder::class);
        $role = Role::query()->where('name', Role::VENDEUR)->firstOrFail();

        User::factory()->create([
            'role_id' => $role->id,
        ]);

        $this->expectException(QueryException::class);

        $role->delete();
    }

    public function test_seeder_removes_no_sample_administrator_and_is_reusable(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(3, Role::query()->count());
        $this->assertSame(0, User::query()->count());
    }
}
