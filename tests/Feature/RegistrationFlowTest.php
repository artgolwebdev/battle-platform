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

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);
    }

    public function test_admin_cannot_view_registration_from_other_company(): void
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

        $event = Event::create([
            'company_id' => $companyB->id,
            'title' => 'Other Company Event',
            'description' => 'Protected registration',
            'registration_open' => true,
        ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'name' => 'Sample',
            'email' => 'sample@example.com',
            'responses' => [],
        ]);

        $admin = User::factory()->create(['company_id' => $companyA->id]);
        $admin->assignRole('admin');

        $this->assertFalse($admin->can('view', $registration));
    }

    public function test_public_registration_form_renders_category_specific_fields_and_submits(): void
    {
        $company = Company::create([
            'name' => 'Studio One',
            'slug' => 'studio-one',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Open Event',
            'description' => 'Public registration page',
            'registration_open' => true,
        ]);

        $oneVOne = EventCategory::create([
            'event_id' => $event->id,
            'name' => '1v1',
            'description' => null,
        ]);

        $crew = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Crew',
            'description' => null,
        ]);

        $oneVOne->registrationFields()->create([
            'field_name' => 'nickname',
            'field_type' => 'text',
            'required' => true,
        ]);

        $crew->registrationFields()->create([
            'field_name' => 'crew_name',
            'field_type' => 'text',
            'required' => true,
        ]);

        $response = $this->get(route('events.public.register', $event));

        $response->assertOk()
            ->assertSee('Category')
            ->assertSee('data-category="' . $oneVOne->id . '"', false)
            ->assertSee('data-category="' . $crew->id . '"', false);

        $this->post(route('events.public.register', $event), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'category_id' => $oneVOne->id,
            'fields' => [
                'nickname' => 'Speed',
            ],
        ])->assertRedirect(route('events.public.show', $event));

        $registration = $event->registrations()->latest()->first();
        $this->assertSame($oneVOne->id, $registration->category_id);
        $this->assertSame(['nickname' => 'Speed'], $registration->responses);
    }

    public function test_each_category_validates_its_own_required_fields(): void
    {
        $company = Company::create([
            'name' => 'Studio One',
            'slug' => 'studio-one',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Open Event',
            'description' => 'Public registration page',
            'registration_open' => true,
        ]);

        $oneVOne = EventCategory::create([
            'event_id' => $event->id,
            'name' => '1v1',
            'description' => null,
        ]);

        $crew = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Crew',
            'description' => null,
        ]);

        $oneVOne->registrationFields()->create([
            'field_name' => 'nickname',
            'field_type' => 'text',
            'required' => true,
        ]);

        $crew->registrationFields()->create([
            'field_name' => 'crew_name',
            'field_type' => 'text',
            'required' => true,
        ]);

        $this->post(route('events.public.register', $event), [
            'name' => 'Crew Lead',
            'email' => 'crew@example.com',
            'category_id' => $crew->id,
            'fields' => [
                'crew_name' => 'The Flyers',
            ],
        ])->assertRedirect(route('events.public.show', $event));

        $this->post(route('events.public.register', $event), [
            'name' => 'Crew Lead 2',
            'email' => 'crew2@example.com',
            'category_id' => $crew->id,
            'fields' => [],
        ])->assertSessionHasErrors(['fields.crew_name']);
    }
}
