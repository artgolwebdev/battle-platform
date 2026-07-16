<?php

namespace Database\Seeders;

use App\Models\Battle;
use App\Models\BattleMatch;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create multiple companies with different statuses
        $approvedCompany1 = Company::create([
            'name' => 'Urban Dance Studio',
            'slug' => 'urban-dance-studio',
            'status' => 'approved',
        ]);

        $approvedCompany2 = Company::create([
            'name' => 'Breakers Academy',
            'slug' => 'breakers-academy',
            'status' => 'approved',
        ]);

        $pendingCompany = Company::create([
            'name' => 'Pending Dance Crew',
            'slug' => 'pending-dance-crew',
            'status' => 'pending',
        ]);

        // Create admins for each company
        $admin1 = User::factory()->create([
            'name' => 'Urban Admin',
            'email' => 'urban@example.com',
            'company_id' => $approvedCompany1->id,
        ]);
        $admin1->assignRole('admin');
        $approvedCompany1->update(['owner_admin_id' => $admin1->id]);

        $admin2 = User::factory()->create([
            'name' => 'Breakers Admin',
            'email' => 'breakers@example.com',
            'company_id' => $approvedCompany2->id,
        ]);
        $admin2->assignRole('admin');
        $approvedCompany2->update(['owner_admin_id' => $admin2->id]);

        $pendingAdmin = User::factory()->create([
            'name' => 'Pending Admin',
            'email' => 'pending@example.com',
            'company_id' => $pendingCompany->id,
        ]);
        $pendingAdmin->assignRole('admin');
        $pendingCompany->update(['owner_admin_id' => $pendingAdmin->id]);

        // Event 1: Urban Dance Studio - Championship Event
        $event1 = Event::create([
            'company_id' => $approvedCompany1->id,
            'title' => 'Urban Dance Championship 2024',
            'description' => 'Annual street dance competition featuring the best talent in the city.',
            'location' => 'Downtown Arena',
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(8),
            'registration_open' => true,
        ]);

        // Category 1: 1v1 Battles with prelims (8 registrations for clean bracket)
        $category1v1 = EventCategory::create([
            'event_id' => $event1->id,
            'name' => '1v1 Battles',
            'description' => 'Head-to-head solo battles',
            'has_prelims' => true,
            'current_phase' => 'prelims',
        ]);

        // Add registration fields for 1v1
        $category1v1->registrationFields()->createMany([
            [
                'field_name' => 'nickname',
                'field_type' => 'text',
                'required' => true,
            ],
            [
                'field_name' => 'style',
                'field_type' => 'select',
                'required' => true,
                'options' => ['breakdance', 'popping', 'locking', 'hip-hop', 'other'],
            ],
            [
                'field_name' => 'years_experience',
                'field_type' => 'number',
                'required' => false,
            ],
        ]);

        // Create 8 registrations for 1v1 category
        $dancerNames = ['Alex "Glitch"', 'Marcus "Flow"', 'Jordan "Snap"', 'Taylor "Wave"', 'Casey "Spin"', 'Morgan "Freeze"', 'Riley "Rock"', 'Sam "Slide"'];
        $registrations1v1 = [];
        foreach ($dancerNames as $index => $name) {
            $registration = Registration::create([
                'event_id' => $event1->id,
                'category_id' => $category1v1->id,
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                'responses' => [
                    'nickname' => explode('"', $name)[1],
                    'style' => ['breakdance', 'popping', 'locking', 'hip-hop', 'other'][$index % 5],
                    'years_experience' => rand(2, 10),
                ],
                'status' => 'approved',
                'order_column' => $index + 1,
            ]);
            $registrations1v1[] = $registration;
        }

        // Set current prelims dancer to the 3rd one (partway through prelims)
        $category1v1->update([
            'current_prelims_registration_id' => $registrations1v1[2]->id,
        ]);

        // Category 2: Crew Battles without prelims (16 registrations for clean bracket)
        $categoryCrew = EventCategory::create([
            'event_id' => $event1->id,
            'name' => 'Crew Battles',
            'description' => 'Team dance battles',
            'has_prelims' => false,
            'current_phase' => 'bracket',
        ]);

        // Add registration fields for Crew
        $categoryCrew->registrationFields()->createMany([
            [
                'field_name' => 'crew_name',
                'field_type' => 'text',
                'required' => true,
            ],
            [
                'field_name' => 'member_count',
                'field_type' => 'number',
                'required' => true,
            ],
            [
                'field_name' => 'crew_representing',
                'field_type' => 'text',
                'required' => false,
            ],
        ]);

        // Create 16 crew registrations
        $crewNames = ['The Firebirds', 'Ice Cold Crew', 'Thunder Squad', 'Lightning Strikes', 'Shadow Dancers', 'Solar Flares', 'Wind Riders', 'Earth Shakers', 'Storm Chasers', 'Blaze Masters', 'Frost Giants', 'Tidal Wave', 'Phoenix Rising', 'Dragon Force', 'Titan Crew', 'Velocity'];
        $registrationsCrew = [];
        foreach ($crewNames as $index => $name) {
            $registration = Registration::create([
                'event_id' => $event1->id,
                'category_id' => $categoryCrew->id,
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                'responses' => [
                    'crew_name' => $name,
                    'member_count' => rand(3, 7),
                    'crew_representing' => 'Urban Dance Studio',
                ],
                'status' => 'approved',
                'seed' => $index + 1,
            ]);
            $registrationsCrew[] = $registration;
        }

        // Generate bracket for crew category
        $battle = $this->generateBracket($event1, $categoryCrew->id, $registrationsCrew, 'random');

        // Score some matches to show in-progress bracket
        $this->scoreSomeMatches($battle, $registrationsCrew);

        // Category 3: All Styles with prelims (not started yet)
        $categoryAllStyles = EventCategory::create([
            'event_id' => $event1->id,
            'name' => 'All Styles',
            'description' => 'Open category for all dance styles',
            'has_prelims' => true,
            'current_phase' => 'registration',
        ]);

        $categoryAllStyles->registrationFields()->createMany([
            [
                'field_name' => 'primary_style',
                'field_type' => 'text',
                'required' => true,
            ],
            [
                'field_name' => 'music_preference',
                'field_type' => 'select',
                'required' => false,
                'options' => ['hip-hop', 'r&b', 'electronic', 'rock', 'any'],
            ],
        ]);

        // Create 6 registrations for All Styles (not enough for bracket yet)
        for ($i = 0; $i < 6; $i++) {
            Registration::create([
                'event_id' => $event1->id,
                'category_id' => $categoryAllStyles->id,
                'name' => "Dancer {$i}",
                'email' => "dancer{$i}@example.com",
                'responses' => [
                    'primary_style' => ['contemporary', 'jazz', 'modern', 'ballet', 'fusion', 'experimental'][$i],
                    'music_preference' => ['hip-hop', 'r&b', 'electronic', 'rock', 'any'][$i % 5],
                ],
                'status' => 'approved',
            ]);
        }

        // Event 2: Breakers Academy - Local Battle
        $event2 = Event::create([
            'company_id' => $approvedCompany2->id,
            'title' => 'Local Breakers Battle',
            'description' => 'Community breakdance competition for local talent.',
            'location' => 'Community Center',
            'start_date' => now()->addDays(14),
            'end_date' => now()->addDays(14),
            'registration_open' => true,
        ]);

        // Category: Breakdown (no prelims, just bracket)
        $categoryBreakdown = EventCategory::create([
            'event_id' => $event2->id,
            'name' => 'Breakdown',
            'description' => 'Pure breakdance battles',
            'has_prelims' => false,
            'current_phase' => 'registration',
        ]);

        $categoryBreakdown->registrationFields()->create([
            'field_name' => 'bboy_name',
            'field_type' => 'text',
            'required' => true,
        ]);

        // Create 4 registrations (minimum for bracket)
        for ($i = 0; $i < 4; $i++) {
            Registration::create([
                'event_id' => $event2->id,
                'category_id' => $categoryBreakdown->id,
                'name' => "B-Boy {$i}",
                'email' => "bboy{$i}@example.com",
                'responses' => [
                    'bboy_name' => "B-Boy {$i}",
                ],
                'status' => 'approved',
            ]);
        }

        // Event 3: Pending Company (should not be able to create events)
        // This company has no events to demonstrate the approval gate
    }

    private function generateBracket(Event $event, int $categoryId, array $registrations, string $seedType): Battle
    {
        return DB::transaction(function () use ($event, $categoryId, $registrations, $seedType) {
            $bracketSize = count($registrations) >= 2 ? (int) pow(2, ceil(log(count($registrations), 2))) : 2;

            $battle = $event->battles()->create([
                'category_id' => $categoryId,
                'name' => 'Main Bracket',
                'status' => 'active',
                'seed_type' => $seedType,
            ]);

            $seedingOrder = $this->getSeedingOrder($bracketSize);
            $round1MatchesCount = $bracketSize / 2;

            for ($position = 0; $position < $round1MatchesCount; $position++) {
                $player1Seed = $seedingOrder[$position * 2];
                $player2Seed = $seedingOrder[$position * 2 + 1];

                $reg1 = collect($registrations)->firstWhere('seed', $player1Seed);
                $reg2 = collect($registrations)->firstWhere('seed', $player2Seed);

                $battle->matches()->create([
                    'round' => 1,
                    'position' => $position,
                    'registration1_id' => $reg1?->id,
                    'registration2_id' => $reg2?->id,
                    'status' => 'pending',
                ]);
            }

            $numRounds = (int) log($bracketSize, 2);
            for ($round = 2; $round <= $numRounds; $round++) {
                $roundMatchesCount = $bracketSize / pow(2, $round);
                for ($position = 0; $position < $roundMatchesCount; $position++) {
                    $battle->matches()->create([
                        'round' => $round,
                        'position' => $position,
                        'registration1_id' => null,
                        'registration2_id' => null,
                        'status' => 'pending',
                    ]);
                }
            }

            return $battle;
        });
    }

    private function getSeedingOrder(int $n): array
    {
        $order = [1];
        while (count($order) < $n) {
            $next = [];
            $target = count($order) * 2 + 1;
            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $target - $seed;
            }
            $order = $next;
        }

        return $order;
    }

    private function scoreSomeMatches(Battle $battle, array $registrations): void
    {
        // Score first round matches (first 4 matches)
        $round1Matches = $battle->matches()->where('round', 1)->limit(4)->get();

        foreach ($round1Matches as $index => $match) {
            if ($match->registration1_id && $match->registration2_id) {
                $winnerId = $index % 2 === 0 ? $match->registration1_id : $match->registration2_id;
                $match->update([
                    'score1' => rand(3, 5),
                    'score2' => rand(1, 3),
                    'winner_id' => $winnerId,
                    'status' => 'completed',
                ]);

                // Propagate winner to next round
                $this->propagateWinner($battle, $match, $winnerId);
            }
        }

        // Score one second round match
        $round2Match = $battle->matches()->where('round', 2)->first();
        if ($round2Match && $round2Match->registration1_id && $round2Match->registration2_id) {
            $round2Match->update([
                'score1' => rand(3, 5),
                'score2' => rand(1, 3),
                'winner_id' => $round2Match->registration1_id,
                'status' => 'completed',
            ]);
        }
    }

    private function propagateWinner(Battle $battle, BattleMatch $match, int $winnerId): void
    {
        $nextRound = $match->round + 1;
        $nextPosition = floor($match->position / 2);
        $slot = $match->position % 2 === 0 ? 'registration1_id' : 'registration2_id';

        $nextMatch = $battle->matches()
            ->where('round', $nextRound)
            ->where('position', $nextPosition)
            ->first();

        if ($nextMatch) {
            $nextMatch->update([
                $slot => $winnerId,
            ]);
        }
    }
}
