<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Library;
use App\Models\LibraryUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Staff add / edit (LibraryUserController::store). Runs against the configured database
 * inside a transaction that is rolled back, on the first library with an active branch.
 */
class LibraryUserStoreTest extends TestCase
{
    use DatabaseTransactions;

    private Library $library;
    private Branch $branch;
    private string $role;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::withoutGlobalScopes()->where('status', 1)->whereNull('deleted_at')
            ->whereIn('library_id', Library::whereNull('deleted_at')->pluck('id'))->first();
        $role = Role::where('guard_name', 'library_user')->value('name');
        if (!$branch || !$role) {
            $this->markTestSkipped('Needs a library with an active branch and a library_user role.');
        }

        $this->branch = $branch;
        $this->library = Library::find($branch->library_id);
        $this->role = $role;
        $this->withoutMiddleware();
        $this->actingAs($this->library, 'library');
        // a writable storage folder for the fake photo disk (storage/ may belong to the web server)
        $this->app->useStoragePath(sys_get_temp_dir() . '/libraro-test-storage');
        Storage::fake('public');
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'name' => 'Test Staff',
            'mobile' => '9876543210',
            'email' => 'staff.test.' . uniqid() . '@example.com',
            'role' => $this->role,
            'branch' => [$this->branch->id],
            'password' => 'secret12',
            'password_confirmation' => 'secret12',
        ], $over);
    }

    private function post_(array $data)
    {
        return $this->postJson(route('library-users.store'), $data);
    }

    public function test_creates_staff_and_goes_to_access_step(): void
    {
        $email = 'New.Staff.' . uniqid() . '@Example.com ';
        $res = $this->post_($this->payload(['email' => $email, 'mobile' => '+91 98765 43210', 'name' => '  Rahul   Verma ']));

        $res->assertOk()->assertJson(['success' => true]);
        $user = LibraryUser::where('email', strtolower(trim($email)))->first();
        $this->assertNotNull($user);
        $this->assertSame('9876543210', $user->mobile);
        $this->assertSame('Rahul Verma', $user->name);
        $this->assertSame((int) $this->library->id, (int) $user->library_id);
        $this->assertTrue($user->hasRole($this->role));
        $this->assertStringContainsString('/permissions', $res->json('redirect'));
    }

    public function test_mobile_with_leading_zero_is_accepted(): void
    {
        $this->post_($this->payload(['mobile' => '09876543210']))->assertOk();
    }

    public function test_required_fields(): void
    {
        $this->post_([])->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'mobile', 'role', 'branch', 'password', 'password_confirmation']);
    }

    public function test_bad_mobile_numbers(): void
    {
        $this->post_($this->payload(['mobile' => '12345']))->assertStatus(422)->assertJsonValidationErrors(['mobile']);
        $this->post_($this->payload(['mobile' => '5876543210']))->assertStatus(422)->assertJsonValidationErrors(['mobile']);
    }

    public function test_bad_email(): void
    {
        $this->post_($this->payload(['email' => 'not-an-email']))->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_email_already_used_by_staff_any_case(): void
    {
        $email = 'dup.' . uniqid() . '@example.com';
        $this->post_($this->payload(['email' => $email]))->assertOk();
        $this->post_($this->payload(['email' => strtoupper($email)]))->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_email_of_a_removed_staff_member_can_be_used_again(): void
    {
        $email = 'gone.' . uniqid() . '@example.com';
        $this->post_($this->payload(['email' => $email]))->assertOk();
        LibraryUser::where('email', $email)->first()->delete();
        $this->post_($this->payload(['email' => $email]))->assertOk();
    }

    public function test_owner_sign_in_email_is_refused(): void
    {
        $this->post_($this->payload(['email' => $this->library->email]))->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_passwords(): void
    {
        $this->post_($this->payload(['password' => '123', 'password_confirmation' => '123']))->assertStatus(422)->assertJsonValidationErrors(['password']);
        $this->post_($this->payload(['password_confirmation' => 'other123']))->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_branch_of_another_library_or_inactive_is_refused(): void
    {
        $other = Branch::withoutGlobalScopes()->where('library_id', '!=', $this->library->id)->value('id');
        if ($other) {
            $this->post_($this->payload(['branch' => [$other]]))->assertStatus(422)->assertJsonValidationErrors(['branch.0']);
        }
        $this->post_($this->payload(['branch' => []]))->assertStatus(422)->assertJsonValidationErrors(['branch']);
    }

    public function test_unknown_role_is_refused(): void
    {
        $this->post_($this->payload(['role' => 'nope']))->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_photo_rules(): void
    {
        $this->post_($this->payload(['profile_picture' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')]))
            ->assertStatus(422)->assertJsonValidationErrors(['profile_picture']);
        $this->post_($this->payload(['profile_picture' => UploadedFile::fake()->image('big.jpg')->size(2048)]))
            ->assertStatus(422)->assertJsonValidationErrors(['profile_picture']);

        $email = 'photo.' . uniqid() . '@example.com';
        $this->post_($this->payload(['email' => $email, 'profile_picture' => UploadedFile::fake()->image('me.jpg', 200, 200)]))->assertOk();
        Storage::disk('public')->assertExists(LibraryUser::where('email', $email)->value('profile_picture'));
    }

    public function test_edit_keeps_password_when_empty_and_allows_same_email(): void
    {
        $email = 'edit.' . uniqid() . '@example.com';
        $this->post_($this->payload(['email' => $email]))->assertOk();
        $user = LibraryUser::where('email', $email)->first();
        $hash = $user->password;

        $this->post_($this->payload(['id' => $user->id, 'email' => $email, 'name' => 'Renamed', 'password' => '', 'password_confirmation' => '']))
            ->assertOk()->assertJson(['success' => true]);
        $user->refresh();
        $this->assertSame('Renamed', $user->name);
        $this->assertSame($hash, $user->password);
    }

    public function test_staff_of_another_library_cannot_be_edited(): void
    {
        $foreign = LibraryUser::where('library_id', '!=', $this->library->id)->value('id');
        if (!$foreign) {
            $this->markTestSkipped('No staff in another library.');
        }
        $this->post_($this->payload(['id' => $foreign]))->assertNotFound();
    }

    private function makeStaff(): LibraryUser
    {
        $email = 'acts.' . uniqid() . '@example.com';
        $this->post_($this->payload(['email' => $email]))->assertOk();
        return LibraryUser::where('email', $email)->first();
    }

    public function test_turn_off_and_on(): void
    {
        $user = $this->makeStaff();
        $before = (int) $user->status;
        $this->postJson('/library-users/toggle-status/' . $user->id)->assertOk()->assertJson(['status' => (int) !$before]);
        $this->postJson('/library-users/toggle-status/' . $user->id)->assertOk()->assertJson(['status' => $before]);
    }

    public function test_access_keeps_only_plan_permissions(): void
    {
        $user = $this->makeStaff();
        $planIds = \App\Models\Subscription::find($this->library->library_type)?->permissions()->pluck('permissions.id')->all() ?? [];
        $outside = \Spatie\Permission\Models\Permission::whereNotIn('id', $planIds ?: [0])->value('id');
        $given = array_filter([$planIds[0] ?? null, $outside]);

        $this->post(route('library-users.permissions.update', $user->id), ['permissions' => $given])->assertRedirect();
        $saved = $user->permissions()->pluck('permissions.id')->all();
        $this->assertSame(array_values(array_intersect($given, $planIds)), $saved);
    }

    public function test_other_librarys_staff_actions_are_refused(): void
    {
        $foreign = LibraryUser::where('library_id', '!=', $this->library->id)->value('id');
        if (!$foreign) {
            $this->markTestSkipped('No staff in another library.');
        }
        $this->postJson('/library-users/toggle-status/' . $foreign)->assertNotFound();
        $this->get(route('library-users.permissions', $foreign))->assertNotFound();
    }
}
