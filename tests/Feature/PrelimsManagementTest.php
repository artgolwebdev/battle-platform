<?php

namespace Tests\Feature;

use App\Events\PrelimsDancerChanged;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventBus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrelimsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);
    }

    private function createCategoryWithRegistrations(bool $hasPrelims = true): array
    {
        $company = Company::create([
            'name' => 'Studio One',
            'slug' => 'studio-one',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Battle Night',
            'registration_open' => true,
        ]);

        $category = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Crew',
            'description' => null,
            'has_prelims' => $hasPrelims,
        ]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');

        $registrations = collect([
            'Alpha',
            'Bravo',
            'Charlie',
        ])->map(function ($name) use ($event, $category) {
            return Registration::create([
                'event_id' => $event->id,
                'category_id' => $category->id,
                'name' => $name,
                'email' => strtolower($name) . '@example.com',
                'status' => 'approved',
            ]);
        });

        return compact('company', 'event', 'category', 'admin', 'registrations');
    }

    public function test_reordering_persists_and_survives_reload(): void
    {
        extract($this->createCategoryWithRegistrations());

        $this->actingAs($admin);

        $response = $this->patchJson(route('events.categories.prelims.reorder', [$event, $category]), [
            'registrations' => [$registrations[2]->id, $registrations[1]->id, $registrations[0]->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('registrations', ['id' => $registrations[2]->id, 'order_column' => 1]);
        $this->assertDatabaseHas('registrations', ['id' => $registrations[1]->id, 'order_column' => 2]);
        $this->assertDatabaseHas('registrations', ['id' => $registrations[0]->id, 'order_column' => 3]);

        $this->get(route('events.categories.prelims.show', [$event, $category]))
            ->assertOk()
            ->assertSeeInOrder(['Charlie', 'Bravo', 'Alpha']);
    }

    public function test_next_advances_by_manual_order_not_registration_id(): void
    {
        extract($this->createCategoryWithRegistrations());

        $this->actingAs($admin);

        $this->patchJson(route('events.categories.prelims.reorder', [$event, $category]), [
            'registrations' => [$registrations[2]->id, $registrations[0]->id, $registrations[1]->id],
        ])->assertOk();

        $this->post(route('events.categories.prelims.start', [$event, $category]))->assertRedirect();

        $this->post(route('events.categories.prelims.next', [$event, $category]))->assertRedirect();
        $this->assertEquals($registrations[2]->id, $category->fresh()->current_prelims_registration_id);

        $this->post(route('events.categories.prelims.next', [$event, $category]))->assertRedirect();
        $this->assertEquals($registrations[0]->id, $category->fresh()->current_prelims_registration_id);
    }

    public function test_prelims_broadcasts_on_next_and_jump(): void
    {
        extract($this->createCategoryWithRegistrations());

        $this->actingAs($admin);
        EventBus::fake();

        $this->post(route('events.categories.prelims.start', [$event, $category]))->assertRedirect();

        $this->post(route('events.categories.prelims.next', [$event, $category]))->assertRedirect();
        EventBus::assertDispatched(PrelimsDancerChanged::class, function (PrelimsDancerChanged $broadcast) use ($event, $category, $registrations) {
            return $broadcast->eventId === $event->id
                && $broadcast->categoryId === $category->id
                && $broadcast->currentPhase === 'prelims'
                && $broadcast->currentPrelimsRegistrationId === $registrations[0]->id
                && $broadcast->registrationName === $registrations[0]->name;
        });

        $this->post(route('events.categories.prelims.jump', [$event, $category]), [
            'registration_id' => $registrations[1]->id,
        ])->assertRedirect();

        EventBus::assertDispatchedTimes(PrelimsDancerChanged::class, 2);
        EventBus::assertDispatched(PrelimsDancerChanged::class, function (PrelimsDancerChanged $broadcast) use ($event, $category, $registrations) {
            return $broadcast->eventId === $event->id
                && $broadcast->categoryId === $category->id
                && $broadcast->currentPhase === 'prelims'
                && $broadcast->currentPrelimsRegistrationId === $registrations[1]->id
                && $broadcast->registrationName === $registrations[1]->name;
        });
    }

    public function test_jump_sets_the_pointer_directly(): void
    {
        extract($this->createCategoryWithRegistrations());

        $this->actingAs($admin);
        $this->post(route('events.categories.prelims.start', [$event, $category]))->assertRedirect();

        $this->post(route('events.categories.prelims.jump', [$event, $category]), [
            'registration_id' => $registrations[1]->id,
        ])->assertRedirect();

        $this->assertEquals($registrations[1]->id, $category->fresh()->current_prelims_registration_id);

        $this->post(route('events.categories.prelims.jump', [$event, $category]), [
            'registration_id' => $registrations[0]->id,
        ])->assertRedirect();

        $this->assertEquals($registrations[0]->id, $category->fresh()->current_prelims_registration_id);
    }

    public function test_complete_prelims_transitions_to_bracket_and_generates_battle(): void
    {
        extract($this->createCategoryWithRegistrations());

        $this->actingAs($admin);
        EventBus::fake();

        $this->post(route('events.categories.prelims.start', [$event, $category]))->assertRedirect();

        $this->post(route('events.categories.prelims.complete', [$event, $category]))->assertRedirect(route('events.bracket.show', ['event' => $event, 'category_id' => $category->id]));

        $this->assertDatabaseHas('event_categories', [
            'id' => $category->id,
            'current_phase' => 'bracket',
        ]);

        $this->assertDatabaseHas('battles', [
            'event_id' => $event->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        EventBus::assertDispatched(PrelimsDancerChanged::class, function (PrelimsDancerChanged $broadcast) use ($event, $category) {
            return $broadcast->eventId === $event->id
                && $broadcast->categoryId === $category->id
                && $broadcast->currentPhase === 'bracket'
                && $broadcast->currentPrelimsRegistrationId === null
                && $broadcast->registrationName === null;
        });
    }

    public function test_category_without_prelims_skips_straight_to_bracket(): void
    {
        extract($this->createCategoryWithRegistrations(false));

        $this->actingAs($admin);

        $this->post(route('events.categories.prelims.complete', [$event, $category]))->assertRedirect(route('events.bracket.show', ['event' => $event, 'category_id' => $category->id]));

        $this->assertDatabaseHas('event_categories', [
            'id' => $category->id,
            'current_phase' => 'bracket',
        ]);
    }

    public function test_admin_from_other_company_cannot_view_or_act_on_prelims_queue_but_superadmin_can(): void
    {
        extract($this->createCategoryWithRegistrations());

        $otherCompany = Company::create([
            'name' => 'Other Studio',
            'slug' => 'other-studio',
            'status' => 'approved',
        ]);

        $otherAdmin = User::factory()->create(['company_id' => $otherCompany->id]);
        $otherAdmin->assignRole('admin');

        $this->actingAs($otherAdmin);
        $this->get(route('events.categories.prelims.show', [$event, $category]))->assertForbidden();
        $this->patchJson(route('events.categories.prelims.reorder', [$event, $category]), [
            'registrations' => [$registrations[0]->id, $registrations[1]->id, $registrations[2]->id],
        ])->assertForbidden();

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $this->actingAs($superadmin);
        $this->get(route('events.categories.prelims.show', [$event, $category]))->assertOk();
        $this->patchJson(route('events.categories.prelims.reorder', [$event, $category]), [
            'registrations' => [$registrations[1]->id, $registrations[2]->id, $registrations[0]->id],
        ])->assertOk();
    }

    public function test_public_page_shows_now_performing_only_in_prelims(): void
    {
        extract($this->createCategoryWithRegistrations());

        $this->actingAs($admin);
        $this->post(route('events.categories.prelims.start', [$event, $category]))->assertRedirect();
        $this->post(route('events.categories.prelims.jump', [$event, $category]), [
            'registration_id' => $registrations[1]->id,
        ])->assertRedirect();

        auth()->logout();

        $this->get(route('events.public.show', $event))
            ->assertOk()
            ->assertSee('now performing')
            ->assertSee($registrations[1]->name);
    }
}
