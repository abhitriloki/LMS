<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function admin_can_view_users_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(5)->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
        $response->assertViewHas('users');
    }

    /** @test */
    public function non_admin_cannot_view_users_list()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_search_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'John']));

        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    /** @test */
    public function admin_can_filter_users_by_role()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $employee = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'instructor']));

        $response->assertStatus(200);
        $response->assertSee($instructor->name);
        $response->assertDontSee($employee->name);
    }

    /** @test */
    public function admin_can_filter_users_by_department()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $department1 = Department::factory()->create(['name' => 'IT']);
        $department2 = Department::factory()->create(['name' => 'HR']);
        
        $user1 = User::factory()->create(['department_id' => $department1->id]);
        $user2 = User::factory()->create(['department_id' => $department2->id]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['department_id' => $department1->id]));

        $response->assertStatus(200);
        $response->assertSee($user1->name);
        $response->assertDontSee($user2->name);
    }

    /** @test */
    public function admin_can_create_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $department = Department::factory()->create();

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'department_id' => $department->id,
            'position' => 'Developer',
            'phone' => '1234567890',
            'bio' => 'Test bio',
        ];

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'employee',
            'department_id' => $department->id,
        ]);
    }

    /** @test */
    public function admin_can_create_user_with_avatar()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $avatar = UploadedFile::fake()->image('avatar.jpg');

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'avatar' => $avatar,
        ];

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);

        $response->assertRedirect();
        
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    /** @test */
    public function non_admin_cannot_create_user()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
        ];

        $response = $this->actingAs($user)->post(route('admin.users.store'), $userData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_user_details()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.show');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    /** @test */
    public function user_can_view_own_details()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get(route('admin.users.show', $user));

        $response->assertStatus(200);
    }

    /** @test */
    public function user_cannot_view_other_users_details()
    {
        $user1 = User::factory()->create(['role' => 'employee']);
        $user2 = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user1)->get(route('admin.users.show', $user2));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => 'instructor',
            'department_id' => $department->id,
            'position' => 'Senior Developer',
        ];

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'role' => 'instructor',
            'department_id' => $department->id,
        ]);
    }

    /** @test */
    public function admin_can_update_user_avatar()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $newAvatar = UploadedFile::fake()->image('new-avatar.jpg');

        $updateData = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar' => $newAvatar,
        ];

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), $updateData);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    /** @test */
    public function user_can_update_own_profile()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $updateData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '9876543210',
            'bio' => 'Updated bio',
        ];

        $response = $this->actingAs($user)->patch(route('profile.update'), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '9876543210',
        ]);
    }

    /** @test */
    public function user_can_upload_avatar_in_profile()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $avatar = UploadedFile::fake()->image('avatar.jpg');

        $updateData = [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $avatar,
        ];

        $response = $this->actingAs($user)->patch(route('profile.update'), $updateData);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    /** @test */
    public function super_admin_can_delete_any_user()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function admin_can_delete_employee()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $employee));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
    }

    /** @test */
    public function admin_cannot_delete_super_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $superAdmin));

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    /** @test */
    public function user_cannot_delete_themselves()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->delete(route('admin.users.destroy', $user));

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function deleting_user_removes_avatar()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        
        // Upload avatar
        $avatar = UploadedFile::fake()->image('avatar.jpg');
        Storage::disk('public')->put('avatars/test-avatar.jpg', $avatar);
        $user->update(['avatar' => 'avatars/test-avatar.jpg']);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect();
        Storage::disk('public')->assertMissing('avatars/test-avatar.jpg');
    }

    /** @test */
    public function department_assignment_works_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $department = Department::factory()->create(['name' => 'Engineering']);

        $updateData = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'department_id' => $department->id,
        ];

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), $updateData);

        $response->assertRedirect();
        $user->refresh();
        $this->assertEquals($department->id, $user->department_id);
        $this->assertEquals('Engineering', $user->department->name);
    }
}
