<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);
    }

    public function test_admin_can_view_and_review_registrations_for_their_company_event(): void
    {
        $company = Company::create([
            'name' => 'Studio One',
            'slug' => 'studio-one',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Open Event',
            'description' => 'Reviewable registrations',
            'registration_open' => true,
        ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'responses' => ['nickname' => 'Speed'],
        ]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $this->get(route('events.registrations.index', $event))
            ->assertOk()
            ->assertSee('Jane Doe');

        $this->patch(route('events.registrations.update', ['event' => $event, 'registration' => $registration]), [
            'status' => 'approved',
        ])->assertRedirect(route('events.registrations.index', $event));

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => 'approved',
        ]);
    }
}
