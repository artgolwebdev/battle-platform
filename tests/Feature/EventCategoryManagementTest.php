<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);
    }

    public function test_approved_admin_can_create_update_and_delete_category(): void
    {
        $company = Company::create([
            'name' => 'Studio One',
            'slug' => 'studio-one',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Open Event',
            'description' => 'Category admin',
            'registration_open' => true,
        ]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $this->post(route('events.categories.store', $event), [
            'name' => '1v1',
            'description' => 'Solo battles',
        ])->assertRedirect();

        $category = EventCategory::where('event_id', $event->id)->where('name', '1v1')->firstOrFail();

        $this->put(route('events.categories.update', [$event, $category]), [
            'name' => '1v1 Open',
            'description' => 'Updated category',
        ])->assertRedirect();

        $this->assertDatabaseHas('event_categories', [
            'id' => $category->id,
            'name' => '1v1 Open',
        ]);

        $this->delete(route('events.categories.destroy', [$event, $category]))
            ->assertRedirect();

        $this->assertDatabaseMissing('event_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_pending_company_cannot_manage_categories(): void
    {
        $company = Company::create([
            'name' => 'Pending Studio',
            'slug' => 'pending-studio',
            'status' => 'pending',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Pending Event',
            'registration_open' => true,
        ]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $this->post(route('events.categories.store', $event), [
            'name' => 'Crew',
        ])->assertForbidden();

        $this->assertDatabaseMissing('event_categories', [
            'event_id' => $event->id,
            'name' => 'Crew',
        ]);
    }

    public function test_company_admin_cannot_manage_other_company_categories_but_superadmin_can(): void
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

        $eventA = Event::create([
            'company_id' => $companyA->id,
            'title' => 'Event A',
            'registration_open' => true,
        ]);

        $eventB = Event::create([
            'company_id' => $companyB->id,
            'title' => 'Event B',
            'registration_open' => true,
        ]);

        $categoryB = EventCategory::create([
            'event_id' => $eventB->id,
            'name' => 'Crew',
            'description' => null,
        ]);

        $adminA = User::factory()->create(['company_id' => $companyA->id]);
        $adminA->assignRole('admin');

        $this->actingAs($adminA);

        $this->get(route('events.show', $eventB))->assertForbidden();
        $this->put(route('events.categories.update', [$eventB, $categoryB]), [
            'name' => 'Crew Updated',
            'description' => null,
        ])->assertForbidden();
        $this->delete(route('events.categories.destroy', [$eventB, $categoryB]))->assertForbidden();

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $this->actingAs($superadmin);

        $this->put(route('events.categories.update', [$eventB, $categoryB]), [
            'name' => 'Crew Updated',
            'description' => 'Updated by superadmin',
        ])->assertRedirect();

        $this->assertDatabaseHas('event_categories', [
            'id' => $categoryB->id,
            'name' => 'Crew Updated',
        ]);
    }

    public function test_delete_is_blocked_when_category_has_dependents(): void
    {
        $company = Company::create([
            'name' => 'Studio One',
            'slug' => 'studio-one',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Open Event',
            'registration_open' => true,
        ]);

        $category = EventCategory::create([
            'event_id' => $event->id,
            'name' => '1v1',
        ]);

        Registration::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => 'Rider One',
            'email' => 'rider@example.com',
        ]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $response = $this->delete(route('events.categories.destroy', [$event, $category]));

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('event_categories', [
            'id' => $category->id,
        ]);
    }
}