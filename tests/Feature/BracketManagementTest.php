<?php

namespace Tests\Feature;

use App\Events\BracketMatchUpdated;
use App\Models\Battle;
use App\Models\BattleMatch;
use App\Models\Company;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventBus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BracketManagementTest extends TestCase
{
    use RefreshDatabase;

    private $company;
    private $admin;
    private $event;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Company A',
            'slug' => 'company-a',
            'status' => 'approved',
        ]);

        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->admin->assignRole('admin');

        $this->event = Event::create([
            'company_id' => $this->company->id,
            'title' => 'Tournament Event',
            'description' => 'Event description',
            'registration_open' => true,
        ]);
    }

    public function test_admin_cannot_generate_bracket_with_less_than_two_registrations(): void
    {
        $this->actingAs($this->admin);

        // 1 registration (needs 2+)
        Registration::create([
            'event_id' => $this->event->id,
            'name' => 'Player 1',
            'email' => 'p1@example.com',
            'status' => 'approved',
        ]);

        $response = $this->post(route('events.bracket.store', $this->event), [
            'seed_type' => 'random',
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseMissing('battles', [
            'event_id' => $this->event->id,
        ]);
    }

    public function test_admin_can_generate_bracket_with_random_seeding(): void
    {
        $this->actingAs($this->admin);

        for ($i = 1; $i <= 4; $i++) {
            Registration::create([
                'event_id' => $this->event->id,
                'name' => "Player $i",
                'email' => "p$i@example.com",
                'status' => 'approved',
            ]);
        }

        $response = $this->post(route('events.bracket.store', $this->event), [
            'seed_type' => 'random',
        ]);

        $response->assertRedirect(route('events.bracket.show', $this->event));
        $this->assertDatabaseHas('battles', [
            'event_id' => $this->event->id,
            'seed_type' => 'random',
            'status' => 'active',
        ]);

        $battle = $this->event->battles()->first();
        // 4 players -> size 4 -> 3 total matches (2 in round 1, 1 in round 2)
        $this->assertCount(3, $battle->matches);
        $this->assertCount(2, $battle->matches()->where('round', 1)->get());
        $this->assertCount(1, $battle->matches()->where('round', 2)->get());

        // Assert all registrations got seeds assigned (1 to 4)
        $seeds = $this->event->registrations()->pluck('seed')->toArray();
        sort($seeds);
        $this->assertEquals([1, 2, 3, 4], $seeds);
    }

    public function test_admin_can_generate_bracket_with_manual_seeding(): void
    {
        $this->actingAs($this->admin);

        // Pre-seed manually
        $p1 = Registration::create([
            'event_id' => $this->event->id,
            'name' => 'Player 1',
            'email' => 'p1@example.com',
            'status' => 'approved',
            'seed' => 4,
        ]);
        $p2 = Registration::create([
            'event_id' => $this->event->id,
            'name' => 'Player 2',
            'email' => 'p2@example.com',
            'status' => 'approved',
            'seed' => 1,
        ]);
        $p3 = Registration::create([
            'event_id' => $this->event->id,
            'name' => 'Player 3',
            'email' => 'p3@example.com',
            'status' => 'approved',
            'seed' => 2,
        ]);
        $p4 = Registration::create([
            'event_id' => $this->event->id,
            'name' => 'Player 4',
            'email' => 'p4@example.com',
            'status' => 'approved',
            'seed' => 3,
        ]);

        $response = $this->post(route('events.bracket.store', $this->event), [
            'seed_type' => 'manual',
        ]);

        $response->assertRedirect(route('events.bracket.show', $this->event));

        // Check seeds remain or normalized properly to 1, 2, 3, 4 based on sorted order
        // Player 2 had seed 1 -> remains seed 1
        $this->assertEquals(1, $p2->fresh()->seed);
        // Player 3 had seed 2 -> remains seed 2
        $this->assertEquals(2, $p3->fresh()->seed);
        // Player 4 had seed 3 -> remains seed 3
        $this->assertEquals(3, $p4->fresh()->seed);
        // Player 1 had seed 4 -> remains seed 4
        $this->assertEquals(4, $p1->fresh()->seed);

        $battle = $this->event->battles()->first();

        // Round 1 pairings for N=4 seeding order [1, 4, 2, 3]
        // Match 0: Seed 1 (Player 2) vs Seed 4 (Player 1)
        // Match 1: Seed 2 (Player 3) vs Seed 3 (Player 4)
        $match0 = $battle->matches()->where('round', 1)->where('position', 0)->first();
        $match1 = $battle->matches()->where('round', 1)->where('position', 1)->first();

        $this->assertEquals($p2->id, $match0->registration1_id);
        $this->assertEquals($p1->id, $match0->registration2_id);
        $this->assertEquals($p3->id, $match1->registration1_id);
        $this->assertEquals($p4->id, $match1->registration2_id);
    }

    public function test_cannot_generate_duplicate_active_bracket(): void
    {
        $this->actingAs($this->admin);

        for ($i = 1; $i <= 2; $i++) {
            Registration::create([
                'event_id' => $this->event->id,
                'name' => "Player $i",
                'email' => "p$i@example.com",
                'status' => 'approved',
            ]);
        }

        // First bracket
        $this->post(route('events.bracket.store', $this->event), ['seed_type' => 'random'])->assertRedirect();
        $this->assertEquals(1, $this->event->battles()->count());

        // Second bracket attempts - should fail
        $response = $this->post(route('events.bracket.store', $this->event), ['seed_type' => 'random']);
        $response->assertSessionHasErrors(['error']);
        $this->assertEquals(1, $this->event->battles()->count());
    }

    public function test_bracket_handles_byes_correctly(): void
    {
        $this->actingAs($this->admin);

        // 3 approved players (needs next power of 2: 4 slots. Seed 4 is a bye)
        $p1 = Registration::create([
            'event_id' => $this->event->id, 'name' => 'P1', 'email' => 'p1@example.com', 'status' => 'approved', 'seed' => 1
        ]);
        $p2 = Registration::create([
            'event_id' => $this->event->id, 'name' => 'P2', 'email' => 'p2@example.com', 'status' => 'approved', 'seed' => 2
        ]);
        $p3 = Registration::create([
            'event_id' => $this->event->id, 'name' => 'P3', 'email' => 'p3@example.com', 'status' => 'approved', 'seed' => 3
        ]);

        $this->post(route('events.bracket.store', $this->event), [
            'seed_type' => 'manual',
        ]);

        $battle = $this->event->battles()->first();

        // Pairings order: [1, 4, 2, 3]
        // Match 0: Seed 1 (P1) vs Seed 4 (NULL) -> BYE
        // Match 1: Seed 2 (P2) vs Seed 3 (P3)
        $match0 = $battle->matches()->where('round', 1)->where('position', 0)->first();
        $match1 = $battle->matches()->where('round', 1)->where('position', 1)->first();

        // Match 0 (bye) should be automatically completed
        $this->assertEquals('completed', $match0->status);
        $this->assertEquals($p1->id, $match0->winner_id);

        // Match 1 should be pending
        $this->assertEquals('pending', $match1->status);

        // Winner of Match 0 (P1) should be propagated to Round 2 Match 0 as player 1
        $round2Match = $battle->matches()->where('round', 2)->where('position', 0)->first();
        $this->assertEquals($p1->id, $round2Match->registration1_id);
        $this->assertNull($round2Match->registration2_id); // waits for Match 1 winner
    }

    public function test_admin_can_score_match_and_progression_occurs(): void
    {
        EventBus::fake();
        $this->actingAs($this->admin);

        for ($i = 1; $i <= 4; $i++) {
            Registration::create([
                'event_id' => $this->event->id,
                'name' => "Player $i",
                'email' => "p$i@example.com",
                'status' => 'approved',
                'seed' => $i
            ]);
        }

        $this->post(route('events.bracket.store', $this->event), ['seed_type' => 'manual']);

        $battle = $this->event->battles()->first();
        // Match 0 is Seed 1 vs 4. Match 1 is Seed 2 vs 3.
        $match0 = $battle->matches()->where('round', 1)->where('position', 0)->first();

        $response = $this->post(route('events.bracket.update-match', [$this->event, $match0]), [
            'score1' => 10,
            'score2' => 8,
            'winner_id' => $match0->registration1_id, // Seed 1
        ]);

        $response->assertRedirect(route('events.bracket.show', $this->event));
        $this->assertEquals('completed', $match0->fresh()->status);
        $this->assertEquals(10, $match0->fresh()->score1);
        $this->assertEquals($match0->registration1_id, $match0->fresh()->winner_id);

        EventBus::assertDispatched(BracketMatchUpdated::class, function (BracketMatchUpdated $broadcast) use ($battle, $match0) {
            return $broadcast->eventId === $this->event->id
                && $broadcast->categoryId === 0
                && $broadcast->battleId === $battle->id
                && $broadcast->matchId === $match0->id
                && $broadcast->round === 1
                && $broadcast->position === 0
                && $broadcast->winnerId === $match0->registration1_id
                && $broadcast->score1 === 10
                && $broadcast->score2 === 8
                && $broadcast->registration1Name === $match0->registration1->name
                && $broadcast->registration2Name === $match0->registration2->name;
        });

        // Round 2 match position 0 should receive the winner
        $round2Match = $battle->matches()->where('round', 2)->where('position', 0)->first();
        $this->assertEquals($match0->registration1_id, $round2Match->registration1_id);
    }

    public function test_match_updates_are_locked_if_subsequent_round_is_completed(): void
    {
        $this->actingAs($this->admin);

        for ($i = 1; $i <= 4; $i++) {
            Registration::create([
                'event_id' => $this->event->id,
                'name' => "Player $i",
                'email' => "p$i@example.com",
                'status' => 'approved',
                'seed' => $i
            ]);
        }

        $this->post(route('events.bracket.store', $this->event), ['seed_type' => 'manual']);

        $battle = $this->event->battles()->first();
        $match0 = $battle->matches()->where('round', 1)->where('position', 0)->first();
        $match1 = $battle->matches()->where('round', 1)->where('position', 1)->first();
        $round2Match = $battle->matches()->where('round', 2)->where('position', 0)->first();

        // 1. Complete both Round 1 matches
        $this->post(route('events.bracket.update-match', [$this->event, $match0]), [
            'score1' => 10, 'score2' => 8, 'winner_id' => $match0->registration1_id
        ]);
        $this->post(route('events.bracket.update-match', [$this->event, $match1]), [
            'score1' => 7, 'score2' => 9, 'winner_id' => $match1->registration2_id
        ]);

        // 2. Complete the Round 2 match
        $round2Match = $round2Match->fresh();
        $this->post(route('events.bracket.update-match', [$this->event, $round2Match]), [
            'score1' => 5, 'score2' => 3, 'winner_id' => $round2Match->registration1_id
        ]);

        $this->assertEquals('completed', $round2Match->fresh()->status);

        // 3. Attempt to update Round 1 match0 again — should fail
        $response = $this->post(route('events.bracket.update-match', [$this->event, $match0]), [
            'score1' => 12, 'score2' => 14, 'winner_id' => $match0->registration2_id
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertEquals($match0->registration1_id, $match0->fresh()->winner_id); // Winner did not change
    }

    public function test_admin_can_reset_bracket(): void
    {
        $this->actingAs($this->admin);

        for ($i = 1; $i <= 2; $i++) {
            Registration::create([
                'event_id' => $this->event->id,
                'name' => "Player $i",
                'email' => "p$i@example.com",
                'status' => 'approved',
            ]);
        }

        $this->post(route('events.bracket.store', $this->event), ['seed_type' => 'random']);

        $battle = $this->event->battles()->first();
        $this->assertNotNull($battle);

        $response = $this->delete(route('events.bracket.destroy', [$this->event, $battle]));
        $response->assertRedirect(route('events.bracket.show', $this->event));

        $this->assertEquals(0, $this->event->battles()->count());
        $this->assertEquals(0, BattleMatch::count());
    }

    public function test_company_scoping_rules_enforced(): void
    {
        $companyB = Company::create(['name' => 'Company B', 'slug' => 'company-b', 'status' => 'approved']);
        $adminB = User::factory()->create(['company_id' => $companyB->id]);
        $adminB->assignRole('admin');

        $this->actingAs($adminB);

        // Admin B attempts to access Company A's event bracket show page
        $this->get(route('events.bracket.show', $this->event))->assertForbidden();

        // Admin B attempts to generate a bracket for Company A's event
        $this->post(route('events.bracket.store', $this->event), ['seed_type' => 'random'])->assertForbidden();
    }

    public function test_public_view_is_read_only_and_hides_emails(): void
    {
        for ($i = 1; $i <= 2; $i++) {
            Registration::create([
                'event_id' => $this->event->id,
                'name' => "SecretPlayer $i",
                'email' => "secret$i@sensitive-data.com",
                'status' => 'approved',
            ]);
        }

        $this->actingAs($this->admin);
        $this->post(route('events.bracket.store', $this->event), ['seed_type' => 'random']);

        // Log out to test public view
        auth()->logout();

        $response = $this->get(route('events.public.show', $this->event));

        $response->assertOk();
        $response->assertSee('SecretPlayer 1');
        $response->assertSee('SecretPlayer 2');
        $response->assertDontSee('sensitive-data.com');
        $response->assertDontSee('Score Match');
        $response->assertDontSee('Choose Winner...');
    }
}

