<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthAndRbacTest extends TestCase
{
    use DatabaseTransactions;

    public function test_superadmin_can_login_successfully(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'superadmin',
            'password' => 'superadmin123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'username',
                        'email',
                        'roles',
                        'permissions',
                    ],
                ],
            ]);

        $this->assertContains('superadmin', $response->json('data.user.roles'));
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'superadmin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_authenticated_user_can_get_me_profile(): void
    {
        $user = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.username', 'superadmin');
    }

    public function test_superadmin_can_access_admin_users(): void
    {
        $user = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    public function test_operator_is_forbidden_from_admin_users(): void
    {
        $operator = User::where('username', 'operator1')->first();

        $response = $this->actingAs($operator, 'sanctum')
            ->getJson('/api/admin/users');

        $response->assertStatus(403)
            ->assertJsonPath('status', 'error');
    }

    public function test_superadmin_can_create_new_user(): void
    {
        $admin = User::where('username', 'superadmin')->first();
        $operatorRole = Role::where('name', 'operator')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users', [
                'name'      => 'Operator Baru',
                'username'  => 'operator_baru',
                'email'     => 'baru@proper.id',
                'password'  => 'rahasia123',
                'role_ids'  => [$operatorRole->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.username', 'operator_baru');

        $this->assertDatabaseHas('users', ['username' => 'operator_baru']);
    }

    public function test_superadmin_cannot_deactivate_themselves(): void
    {
        $admin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/users/{$admin->id}/toggle-active");

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_superadmin_can_view_permissions_list(): void
    {
        $admin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => [
                    'list',
                    'grouped',
                ],
            ]);
    }
}
