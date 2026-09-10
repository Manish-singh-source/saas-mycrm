<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformKnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $categories = [
            ['name' => 'Getting Started', 'slug' => 'getting-started', 'audience' => 'all', 'status' => 'active'],
            ['name' => 'Account & Security', 'slug' => 'account-security', 'audience' => 'all', 'status' => 'active'],
            ['name' => 'Billing & Subscriptions', 'slug' => 'billing-subscriptions', 'audience' => 'all', 'status' => 'active'],
        ];

        foreach ($categories as $category) {
            DB::table('knowledge_base_categories')->upsert([
                $category + ['uuid' => (string) Str::uuid(), 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ], ['slug'], ['name', 'audience', 'status', 'updated_at']);
        }

        $securityId = DB::table('knowledge_base_categories')->where('slug', 'account-security')->value('id');
        $articles = [
            ['title' => 'How to invite your team members', 'slug' => 'how-to-invite-your-team-members', 'category_id' => DB::table('knowledge_base_categories')->where('slug', 'getting-started')->value('id'), 'body' => 'Invite colleagues from Platform Users, assign their department and role, and send the invitation. The invited user can finish setup from the secure email link.', 'audience' => 'all', 'status' => 'published', 'published_at' => $now],
            ['title' => 'Protecting your platform account with two-factor authentication', 'slug' => 'protecting-your-platform-account-with-two-factor-authentication', 'category_id' => $securityId, 'body' => 'Enable two-factor authentication from your security settings, scan the provisioning QR code with an authenticator app, and save the recovery codes in a secure location.', 'audience' => 'all', 'status' => 'published', 'published_at' => $now],
            ['title' => 'Understanding plans and add-ons', 'slug' => 'understanding-plans-and-add-ons', 'category_id' => DB::table('knowledge_base_categories')->where('slug', 'billing-subscriptions')->value('id'), 'body' => 'Plans define the core catalogue features available to a tenant. Add-ons extend a plan with optional capabilities and are billed according to their configured pricing model.', 'audience' => 'all', 'status' => 'draft', 'published_at' => null],
        ];
        $creatorId = DB::table('platform_users')->orderBy('id')->value('id');
        foreach ($articles as $article) {
            DB::table('knowledge_base_articles')->upsert([
                $article + ['uuid' => (string) Str::uuid(), 'created_by' => $creatorId, 'created_at' => $now, 'updated_at' => $now],
            ], ['slug'], ['title', 'category_id', 'body', 'audience', 'status', 'created_by', 'published_at', 'updated_at']);
        }
    }
}
