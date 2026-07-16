<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NavigationDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);
    }

    public function test_guest_sees_simple_browse_events_link_not_dropdown(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Browse Events')
            ->assertDontSee('My Events')
            ->assertDontSee('My Registrations')
            ->assertDontSee('Create Event')
            ->assertDontSee('dropdown-toggle');
    }

    public function test_logged_in_participant_sees_browse_events_and_my_registrations(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->get('/');

        $response->assertOk()
            ->assertSee('Events')
            ->assertSee('Browse Events')
            ->assertSee('My Registrations')
            ->assertDontSee('My Events')
            ->assertDontSee('Create Event');
    }

    public function test_company_admin_with_pending_company_sees_disabled_create_event(): void
    {
        $company = Company::create([
            'name' => 'Pending Company',
            'slug' => 'pending-company',
            'status' => 'pending',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/');

        $response->assertOk()
            ->assertSee('Events')
            ->assertSee('Browse Events')
            ->assertSee('My Events')
            ->assertSee('Create Event')
            ->assertSee('Awaiting company approval');
    }

    public function test_company_admin_with_approved_company_sees_enabled_create_event(): void
    {
        $company = Company::create([
            'name' => 'Approved Company',
            'slug' => 'approved-company',
            'status' => 'approved',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/');

        $response->assertOk()
            ->assertSee('Events')
            ->assertSee('Browse Events')
            ->assertSee('My Events')
            ->assertSee('Create Event')
            ->assertDontSee('Awaiting company approval');
    }

    public function test_superadmin_sees_full_dropdown_menu(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->get('/');

        $response->assertOk()
            ->assertSee('Events')
            ->assertSee('Browse Events')
            ->assertSee('My Events')
            ->assertSee('Create Event')
            ->assertDontSee('My Registrations');
    }

    public function test_company_admin_without_company_sees_my_events_but_no_create_event(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/');

        $response->assertOk()
            ->assertSee('Events')
            ->assertSee('Browse Events')
            ->assertSee('My Events')
            ->assertDontSee('Create Event')
            ->assertDontSee('Awaiting company approval');
    }
}
