<?php

namespace Tests\Feature;

use App\Models\Battle;
use App\Models\BattleMatch;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmokePassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);
    }

    public function test_superadmin_can_view_pending_companies_and_approve_reject(): void
    {
        $superadmin = User::factory()->create(['email' => 'superadmin@example.com']);
        $superadmin->assignRole('superadmin');

        $pendingCompany = Company::create([
            'name' => 'Pending Studio',
            'slug' => 'pending-studio',
            'status' => 'pending',
        ]);

        $approvedCompany = Company::create([
            'name' => 'Approved Studio',
            'slug' => 'approved-studio',
            'status' => 'approved',
        ]);

        $this->actingAs($superadmin)
            ->get(route('companies.index'))
            ->assertOk()
            ->assertSee('Pending Studio')
            ->assertSee('Approved Studio');

        // Approve pending company
        $this->actingAs($superadmin)
            ->patch(route('companies.approve', $pendingCompany))
            ->assertRedirect();

        $pendingCompany->refresh();
        $this->assertEquals('approved', $pendingCompany->status);

        // Reject approved company
        $this->actingAs($superadmin)
            ->patch(route('companies.reject', $approvedCompany))
            ->assertRedirect();

        $approvedCompany->refresh();
        $this->assertEquals('rejected', $approvedCompany->status);
    }

    public function test_pending_company_admin_cannot_create_events_or_categories(): void
    {
        $pendingCompany = Company::create([
            'name' => 'Pending Studio',
            'slug' => 'pending-studio',
            'status' => 'pending',
        ]);

        $admin = User::factory()->create([
            'company_id' => $pendingCompany->id,
        ]);
        $admin->assignRole('admin');

        // Try to create event
        $this->actingAs($admin)
            ->post(route('events.store'), [
                'company_id' => $pendingCompany->id,
                'title' => 'Test Event',
                'description' => 'Test',
                'registration_open' => true,
            ])
            ->assertForbidden();

        // Try to create category (would need an event first, but let's test the policy)
        $event = Event::create([
            'company_id' => $pendingCompany->id,
            'title' => 'Test Event',
            'registration_open' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('events.categories.store', $event), [
                'name' => 'Test Category',
            ])
            ->assertForbidden();
    }

    public function test_approved_company_admin_can_create_event_and_categories_with_prelims(): void
    {
        $company = Company::create([
            'name' => 'Approved Studio',
            'slug' => 'approved-studio',
            'status' => 'approved',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $admin->assignRole('admin');

        // Create event
        $this->actingAs($admin)
            ->post(route('events.store'), [
                'title' => 'Championship Event',
                'description' => 'Annual competition',
                'location' => 'Arena',
                'start_date' => now()->addWeek(),
                'end_date' => now()->addWeek()->addDay(),
                'registration_open' => true,
            ])
            ->assertRedirect();

        $event = Event::where('title', 'Championship Event')->first();

        // Create category with prelims
        $this->actingAs($admin)
            ->post(route('events.categories.store', $event), [
                'name' => '1v1 Battles',
                'description' => 'Solo battles',
                'has_prelims' => true,
            ])
            ->assertRedirect();

        $categoryWithPrelims = EventCategory::where('name', '1v1 Battles')->first();
        $this->assertTrue($categoryWithPrelims->has_prelims);

        // Create category without prelims
        $this->actingAs($admin)
            ->post(route('events.categories.store', $event), [
                'name' => 'Crew Battles',
                'description' => 'Team battles',
                'has_prelims' => false,
            ])
            ->assertRedirect();

        $categoryWithoutPrelims = EventCategory::where('name', 'Crew Battles')->first();
        $this->assertFalse($categoryWithoutPrelims->has_prelims);
    }

    public function test_categories_have_independent_registration_fields(): void
    {
        $company = Company::create([
            'name' => 'Approved Studio',
            'slug' => 'approved-studio',
            'status' => 'approved',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $admin->assignRole('admin');

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Test Event',
            'registration_open' => true,
        ]);

        $category1 = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Category 1',
        ]);

        $category2 = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Category 2',
        ]);

        // Add different fields to each category using the event-level route with category_id
        $this->actingAs($admin)
            ->post(route('events.fields.store', $event), [
                'category_id' => $category1->id,
                'field_name' => 'nickname',
                'field_type' => 'text',
                'required' => true,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('events.fields.store', $event), [
                'category_id' => $category2->id,
                'field_name' => 'crew_name',
                'field_type' => 'text',
                'required' => true,
            ])
            ->assertRedirect();

        // Verify fields are independent
        $category1->refresh();
        $category2->refresh();

        $this->assertCount(1, $category1->registrationFields);
        $this->assertCount(1, $category2->registrationFields);
        $this->assertEquals('nickname', $category1->registrationFields->first()->field_name);
        $this->assertEquals('crew_name', $category2->registrationFields->first()->field_name);
    }

    public function test_prelims_queue_next_jump_and_complete(): void
    {
        $company = Company::create([
            'name' => 'Approved Studio',
            'slug' => 'approved-studio',
            'status' => 'approved',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $admin->assignRole('admin');

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Test Event',
            'registration_open' => true,
        ]);

        $category = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Test Category',
            'has_prelims' => true,
            'current_phase' => 'registration',
        ]);

        // Create registrations
        $reg1 = Registration::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => 'Dancer 1',
            'email' => 'dancer1@example.com',
            'status' => 'approved',
            'order_column' => 1,
        ]);

        $reg2 = Registration::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => 'Dancer 2',
            'email' => 'dancer2@example.com',
            'status' => 'approved',
            'order_column' => 2,
        ]);

        $reg3 = Registration::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => 'Dancer 3',
            'email' => 'dancer3@example.com',
            'status' => 'approved',
            'order_column' => 3,
        ]);

        // Start prelims
        $this->actingAs($admin)
            ->post(route('events.categories.prelims.start', [$event, $category]))
            ->assertRedirect();

        $category->refresh();
        $this->assertEquals('prelims', $category->current_phase);

        // Use next to advance
        $this->actingAs($admin)
            ->post(route('events.categories.prelims.next', [$event, $category]))
            ->assertRedirect();

        $category->refresh();
        $this->assertEquals($reg1->id, $category->current_prelims_registration_id);

        // Use jump to skip to specific registration
        $this->actingAs($admin)
            ->post(route('events.categories.prelims.jump', [$event, $category]), [
                'registration_id' => $reg3->id,
            ])
            ->assertRedirect();

        $category->refresh();
        $this->assertEquals($reg3->id, $category->current_prelims_registration_id);

        // Complete prelims and generate bracket
        $this->actingAs($admin)
            ->post(route('events.categories.prelims.complete', [$event, $category]))
            ->assertRedirect();

        $category->refresh();
        $this->assertEquals('bracket', $category->current_phase);
        $this->assertNull($category->current_prelims_registration_id);

        // Verify bracket was generated
        $battle = Battle::where('event_id', $event->id)
            ->where('category_id', $category->id)
            ->first();
        $this->assertNotNull($battle);
    }

    public function test_can_score_match_in_bracket(): void
    {
        $company = Company::create([
            'name' => 'Approved Studio',
            'slug' => 'approved-studio',
            'status' => 'approved',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $admin->assignRole('admin');

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Test Event',
            'registration_open' => true,
        ]);

        $category = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Test Category',
            'has_prelims' => false,
        ]);

        // Create registrations
        $reg1 = Registration::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => 'Dancer 1',
            'email' => 'dancer1@example.com',
            'status' => 'approved',
            'seed' => 1,
        ]);

        $reg2 = Registration::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => 'Dancer 2',
            'email' => 'dancer2@example.com',
            'status' => 'approved',
            'seed' => 2,
        ]);

        // Generate bracket
        $this->actingAs($admin)
            ->post(route('events.bracket.store', $event), [
                'seed_type' => 'random',
                'category_id' => $category->id,
            ])
            ->assertRedirect();

        $battle = Battle::where('event_id', $event->id)
            ->where('category_id', $category->id)
            ->first();
        $match = $battle->matches()->where('round', 1)->first();

        // Score match
        $this->actingAs($admin)
            ->post(route('events.bracket.update-match', [$event, $match]), [
                'score1' => 5,
                'score2' => 3,
                'winner_id' => $reg1->id,
            ])
            ->assertRedirect();

        $match->refresh();
        $this->assertEquals(5, $match->score1);
        $this->assertEquals(3, $match->score2);
        $this->assertEquals($reg1->id, $match->winner_id);
        $this->assertEquals('completed', $match->status);
    }

    public function test_public_visitor_can_view_event_and_see_now_performing(): void
    {
        $company = Company::create([
            'name' => 'Approved Studio',
            'slug' => 'approved-studio',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Test Event',
            'description' => 'Public event',
            'location' => 'Arena',
            'registration_open' => true,
        ]);

        $category = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Test Category',
            'has_prelims' => true,
            'current_phase' => 'prelims',
        ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => 'Current Dancer',
            'email' => 'dancer@example.com',
            'status' => 'approved',
        ]);

        $category->update([
            'current_prelims_registration_id' => $registration->id,
        ]);

        // Public visitor can view event
        $this->get(route('events.public.show', $event))
            ->assertOk()
            ->assertSee('Test Event')
            ->assertSee('Public event')
            ->assertSee('Arena')
            ->assertSee('now performing')
            ->assertSee('Current Dancer');
    }

    public function test_public_visitor_can_view_live_bracket(): void
    {
        $company = Company::create([
            'name' => 'Approved Studio',
            'slug' => 'approved-studio',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Test Event',
            'registration_open' => true,
        ]);

        $category = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Test Category',
            'has_prelims' => false,
        ]);

        $reg1 = Registration::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => 'Dancer 1',
            'email' => 'dancer1@example.com',
            'status' => 'approved',
            'seed' => 1,
        ]);

        $reg2 = Registration::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => 'Dancer 2',
            'email' => 'dancer2@example.com',
            'status' => 'approved',
            'seed' => 2,
        ]);

        // Generate bracket
        $battle = Battle::create([
            'event_id' => $event->id,
            'category_id' => $category->id,
            'name' => 'Main Bracket',
            'status' => 'active',
            'seed_type' => 'random',
        ]);

        $match = BattleMatch::create([
            'battle_id' => $battle->id,
            'round' => 1,
            'position' => 0,
            'registration1_id' => $reg1->id,
            'registration2_id' => $reg2->id,
            'score1' => 5,
            'score2' => 3,
            'winner_id' => $reg1->id,
            'status' => 'completed',
        ]);

        // Public visitor can view bracket
        $this->get(route('events.public.show', $event))
            ->assertOk()
            ->assertSee('Tournament Bracket')
            ->assertSee('Dancer 1')
            ->assertSee('Dancer 2');
    }

    public function test_public_registration_shows_only_category_fields(): void
    {
        $company = Company::create([
            'name' => 'Approved Studio',
            'slug' => 'approved-studio',
            'status' => 'approved',
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Test Event',
            'registration_open' => true,
        ]);

        $category1 = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Category 1',
        ]);

        $category2 = EventCategory::create([
            'event_id' => $event->id,
            'name' => 'Category 2',
        ]);

        $category1->registrationFields()->create([
            'field_name' => 'nickname',
            'field_type' => 'text',
            'required' => true,
        ]);

        $category2->registrationFields()->create([
            'field_name' => 'crew_name',
            'field_type' => 'text',
            'required' => true,
        ]);

        // Public registration form shows category selector
        $this->get(route('events.public.register', $event))
            ->assertOk()
            ->assertSee('Category')
            ->assertSee('Category 1')
            ->assertSee('Category 2');

        // Submit registration for category 1
        $this->post(route('events.public.register', $event), [
            'name' => 'Test Dancer',
            'email' => 'test@example.com',
            'category_id' => $category1->id,
            'fields' => [
                'nickname' => 'Test Nickname',
            ],
        ])->assertRedirect();

        $registration = Registration::where('email', 'test@example.com')->first();
        $this->assertEquals($category1->id, $registration->category_id);
        $this->assertEquals(['nickname' => 'Test Nickname'], $registration->responses);
    }
}
