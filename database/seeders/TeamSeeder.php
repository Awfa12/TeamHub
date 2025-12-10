<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================
        // USERS - Create various test users
        // ============================================
        
        // Primary test users (easy to remember credentials)
        $alice = User::factory()->create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
        ]);

        $bob = User::factory()->create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
        ]);

        $charlie = User::factory()->create([
            'name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
        ]);

        $diana = User::factory()->create([
            'name' => 'Diana Prince',
            'email' => 'diana@example.com',
        ]);

        $eve = User::factory()->create([
            'name' => 'Eve Wilson',
            'email' => 'eve@example.com',
        ]);

        $frank = User::factory()->create([
            'name' => 'Frank Castle',
            'email' => 'frank@example.com',
        ]);

        // Legacy test users (for backwards compatibility)
        $owner = User::factory()->create([
            'name' => 'Demo Owner',
            'email' => 'owner@example.com',
        ]);

        $admin = User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
        ]);

        $member = User::factory()->create([
            'name' => 'Demo Member',
            'email' => 'member@example.com',
        ]);

        // ============================================
        // TEAM 1: Acme Corporation (Large team)
        // ============================================
        
        $acme = Team::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'owner_id' => $alice->id,
            'settings' => ['theme' => 'blue'],
            'active' => true,
        ]);

        // Team memberships
        $alice->teams()->attach($acme->id, ['role' => 'owner', 'joined_at' => now()->subDays(30)]);
        $bob->teams()->attach($acme->id, ['role' => 'admin', 'joined_at' => now()->subDays(25)]);
        $charlie->teams()->attach($acme->id, ['role' => 'admin', 'joined_at' => now()->subDays(20)]);
        $diana->teams()->attach($acme->id, ['role' => 'member', 'joined_at' => now()->subDays(15)]);
        $eve->teams()->attach($acme->id, ['role' => 'member', 'joined_at' => now()->subDays(10)]);
        $frank->teams()->attach($acme->id, ['role' => 'member', 'joined_at' => now()->subDays(5)]);

        // Acme Channels
        $acmeGeneral = Channel::create([
            'team_id' => $acme->id,
            'name' => 'general',
            'slug' => 'general',
            'description' => 'Company-wide announcements and discussion',
            'is_private' => false,
            'creator_id' => $alice->id,
        ]);

        $acmeEngineering = Channel::create([
            'team_id' => $acme->id,
            'name' => 'engineering',
            'slug' => 'engineering',
            'description' => 'Engineering team discussions',
            'is_private' => false,
            'creator_id' => $bob->id,
        ]);

        $acmeMarketing = Channel::create([
            'team_id' => $acme->id,
            'name' => 'marketing',
            'slug' => 'marketing',
            'description' => 'Marketing campaigns and strategy',
            'is_private' => false,
            'creator_id' => $charlie->id,
        ]);

        $acmeRandom = Channel::create([
            'team_id' => $acme->id,
            'name' => 'random',
            'slug' => 'random',
            'description' => 'Non-work banter and fun stuff',
            'is_private' => false,
            'creator_id' => $alice->id,
        ]);

        $acmeLeadership = Channel::create([
            'team_id' => $acme->id,
            'name' => 'leadership',
            'slug' => 'leadership',
            'description' => 'Private channel for leadership team',
            'is_private' => true,
            'creator_id' => $alice->id,
        ]);

        // Private channel memberships
        $acmeLeadership->users()->attach($alice->id, ['role' => 'owner', 'joined_at' => now()]);
        $acmeLeadership->users()->attach($bob->id, ['role' => 'participant', 'joined_at' => now()]);
        $acmeLeadership->users()->attach($charlie->id, ['role' => 'participant', 'joined_at' => now()]);

        $acmeHrConfidential = Channel::create([
            'team_id' => $acme->id,
            'name' => 'hr-confidential',
            'slug' => 'hr-confidential',
            'description' => 'HR sensitive discussions',
            'is_private' => true,
            'creator_id' => $alice->id,
        ]);

        $acmeHrConfidential->users()->attach($alice->id, ['role' => 'owner', 'joined_at' => now()]);
        $acmeHrConfidential->users()->attach($diana->id, ['role' => 'participant', 'joined_at' => now()]);

        // Sample messages in Acme general
        $this->createSampleMessages($acmeGeneral, [$alice, $bob, $charlie, $diana, $eve]);

        // ============================================
        // TEAM 2: Startup Squad (Small team)
        // ============================================
        
        $startup = Team::create([
            'name' => 'Startup Squad',
            'slug' => 'startup-squad',
            'owner_id' => $bob->id,
            'settings' => ['theme' => 'green'],
            'active' => true,
        ]);

        $bob->teams()->attach($startup->id, ['role' => 'owner', 'joined_at' => now()->subDays(60)]);
        $diana->teams()->attach($startup->id, ['role' => 'admin', 'joined_at' => now()->subDays(55)]);
        $eve->teams()->attach($startup->id, ['role' => 'member', 'joined_at' => now()->subDays(30)]);

        $startupGeneral = Channel::create([
            'team_id' => $startup->id,
            'name' => 'general',
            'slug' => 'general',
            'description' => 'Main discussion channel',
            'is_private' => false,
            'creator_id' => $bob->id,
        ]);

        $startupProduct = Channel::create([
            'team_id' => $startup->id,
            'name' => 'product',
            'slug' => 'product',
            'description' => 'Product development and roadmap',
            'is_private' => false,
            'creator_id' => $bob->id,
        ]);

        $startupFounders = Channel::create([
            'team_id' => $startup->id,
            'name' => 'founders-only',
            'slug' => 'founders-only',
            'description' => 'Private founder discussions',
            'is_private' => true,
            'creator_id' => $bob->id,
        ]);

        $startupFounders->users()->attach($bob->id, ['role' => 'owner', 'joined_at' => now()]);
        $startupFounders->users()->attach($diana->id, ['role' => 'participant', 'joined_at' => now()]);

        $this->createSampleMessages($startupGeneral, [$bob, $diana, $eve]);

        // ============================================
        // TEAM 3: Demo Team (Legacy - for documentation)
        // ============================================
        
        $demoTeam = Team::create([
            'name' => 'Demo Team',
            'slug' => 'demo-team',
            'owner_id' => $owner->id,
            'settings' => [],
            'active' => true,
        ]);

        $owner->teams()->attach($demoTeam->id, ['role' => 'owner', 'joined_at' => now()]);
        $admin->teams()->attach($demoTeam->id, ['role' => 'admin', 'joined_at' => now()]);
        $member->teams()->attach($demoTeam->id, ['role' => 'member', 'joined_at' => now()]);

        $demoGeneral = Channel::create([
            'team_id' => $demoTeam->id,
            'name' => 'general',
            'slug' => 'general',
            'description' => 'General discussion',
            'is_private' => false,
            'creator_id' => $owner->id,
        ]);

        $demoLeadership = Channel::create([
            'team_id' => $demoTeam->id,
            'name' => 'leadership',
            'slug' => 'leadership',
            'description' => 'Private leadership channel',
            'is_private' => true,
            'creator_id' => $owner->id,
        ]);

        $demoLeadership->users()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);
        $demoLeadership->users()->attach($admin->id, ['role' => 'participant', 'joined_at' => now()]);

        $this->createSampleMessages($demoGeneral, [$owner, $admin, $member]);

        // ============================================
        // TEAM 4: Design Collective (Creative team)
        // ============================================
        
        $design = Team::create([
            'name' => 'Design Collective',
            'slug' => 'design-collective',
            'owner_id' => $charlie->id,
            'settings' => ['theme' => 'purple'],
            'active' => true,
        ]);

        $charlie->teams()->attach($design->id, ['role' => 'owner', 'joined_at' => now()->subDays(45)]);
        $alice->teams()->attach($design->id, ['role' => 'member', 'joined_at' => now()->subDays(40)]);
        $frank->teams()->attach($design->id, ['role' => 'member', 'joined_at' => now()->subDays(20)]);

        Channel::create([
            'team_id' => $design->id,
            'name' => 'general',
            'slug' => 'general',
            'description' => 'Design team chat',
            'is_private' => false,
            'creator_id' => $charlie->id,
        ]);

        Channel::create([
            'team_id' => $design->id,
            'name' => 'inspiration',
            'slug' => 'inspiration',
            'description' => 'Share design inspiration',
            'is_private' => false,
            'creator_id' => $charlie->id,
        ]);

        Channel::create([
            'team_id' => $design->id,
            'name' => 'critiques',
            'slug' => 'critiques',
            'description' => 'Design review and feedback',
            'is_private' => false,
            'creator_id' => $charlie->id,
        ]);

        // ============================================
        // TEAM 5: Archived/Inactive Team
        // ============================================
        
        $archived = Team::create([
            'name' => 'Old Project',
            'slug' => 'old-project',
            'owner_id' => $frank->id,
            'settings' => [],
            'active' => false, // Inactive team
        ]);

        $frank->teams()->attach($archived->id, ['role' => 'owner', 'joined_at' => now()->subDays(100)]);
        $eve->teams()->attach($archived->id, ['role' => 'member', 'joined_at' => now()->subDays(90)]);

        $archivedGeneral = Channel::create([
            'team_id' => $archived->id,
            'name' => 'general',
            'slug' => 'general',
            'description' => 'General discussion',
            'is_private' => false,
            'creator_id' => $frank->id,
            'archived' => true, // Archived channel
        ]);

        // ============================================
        // OUTPUT SUMMARY
        // ============================================
        
        $this->command->info('');
        $this->command->info('🎉 Seed data created successfully!');
        $this->command->info('');
        $this->command->table(
            ['Email', 'Password', 'Teams'],
            [
                ['alice@example.com', 'password', 'Acme Corp (owner), Design Collective (member)'],
                ['bob@example.com', 'password', 'Acme Corp (admin), Startup Squad (owner)'],
                ['charlie@example.com', 'password', 'Acme Corp (admin), Design Collective (owner)'],
                ['diana@example.com', 'password', 'Acme Corp (member), Startup Squad (admin)'],
                ['eve@example.com', 'password', 'Acme Corp (member), Startup Squad (member), Old Project (member)'],
                ['frank@example.com', 'password', 'Acme Corp (member), Design Collective (member), Old Project (owner)'],
                ['owner@example.com', 'password', 'Demo Team (owner)'],
                ['admin@example.com', 'password', 'Demo Team (admin)'],
                ['member@example.com', 'password', 'Demo Team (member)'],
            ]
        );
        $this->command->info('');
    }

    /**
     * Create sample messages for a channel
     */
    private function createSampleMessages(Channel $channel, array $users): void
    {
        $sampleMessages = [
            'Hey everyone! 👋',
            'Good morning team!',
            'Has anyone seen the latest updates?',
            'I\'ll take a look at that today.',
            'Great work on the project!',
            'Let me know if you need any help.',
            'Thanks for the update!',
            'I agree with that approach.',
            'Can we schedule a quick sync?',
            'Just pushed the changes.',
            'Looking good! 🎉',
            'I\'ll review this afternoon.',
        ];

        $messageCount = rand(5, 10);
        
        for ($i = 0; $i < $messageCount; $i++) {
            $user = $users[array_rand($users)];
            $body = $sampleMessages[array_rand($sampleMessages)];
            
            Message::create([
                'uuid' => (string) Str::uuid(),
                'channel_id' => $channel->id,
                'user_id' => $user->id,
                'body' => $body,
                'created_at' => now()->subMinutes(rand(1, 1440)), // Random time in last 24 hours
                'updated_at' => now()->subMinutes(rand(1, 1440)),
            ]);
        }
    }
}
