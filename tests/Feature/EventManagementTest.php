<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);
    }

    public function test_admin_cannot_manage_another_company_event(): void
    {
        $companyA = Company::create([
            'name' => 'Studio A',
            'slug' => 'studio-a',
            'status' => 'approved',
        ]);
        $companyB = Company::create([
            'name' => 'Studio B',
            'slug' => 'studio-b',
            'status' => 'approved',
        ]);

        $eventB = Event::create([
            'company_id' => $companyB->id,
            'title' => 'Other Company Event',
            'description' => 'Protected event',
            'registration_open' => true,
        ]);

        $admin = User::factory()->create(['company_id' => $companyA->id]);
        $admin->assignRole('admin');

        $this->assertFalse($admin->can('view', $eventB));
        $this->assertFalse($admin->can('update', $eventB));
        $this->assertFalse($admin->can('delete', $eventB));
    }

    public function test_admin_from_company_a_receives_forbidden_for_company_b_event_actions(): void
    {
        $companyA = Company::create([
            'name' => 'Studio A',
            'slug' => 'studio-a',
            'status' => 'approved',
        ]);
        $companyB = Company::create([
            'name' => 'Studio B',
            'slug' => 'studio-b',
            'status' => 'approved',
        ]);

        $eventB = Event::create([
            'company_id' => $companyB->id,
            'title' => 'Other Company Event',
            'description' => 'Protected event',
            'registration_open' => true,
        ]);

        $admin = User::factory()->create(['company_id' => $companyA->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $this->get(route('events.show', $eventB))->assertForbidden();
        $this->get(route('events.edit', $eventB))->assertForbidden();
        $this->delete(route('events.destroy', $eventB))->assertForbidden();
    }

    public function test_admin_can_create_event_with_form_payload(): void
    {
        $company = Company::create([
            'name' => 'Studio One',
            'slug' => 'studio-one',
            'status' => 'approved',
        ]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $response = $this->post(route('events.store'), [
            'title' => 'Launch Event',
            'description' => 'A new event',
            'location' => 'Main Hall',
            'start_date' => '2026-07-10T10:00',
            'end_date' => '2026-07-10T18:00',
            'programme' => '[]',
            'registration_open' => '1',
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'company_id' => $company->id,
            'title' => 'Launch Event',
            'location' => 'Main Hall',
        ]);
    }

    public function test_public_event_page_is_accessible_without_authentication(): void
    {
        $company = Company::create([
            'name' => 'Studio One',
            'slug' => 'studio-one',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Open Event',
            'description' => 'Public event page',
            'registration_open' => true,
        ]);

        $response = $this->get(route('events.public.show', $event));

        $response->assertOk();
    }
}
