<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformLegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $creatorId = DB::table('platform_users')->orderBy('id')->value('id');
        $documents = [
            [
                'document_type' => 'terms_and_conditions',
                'title' => 'Terms and Conditions',
                'version' => '1.0',
                'content' => "# Terms and Conditions\n\nThese Terms and Conditions govern access to and use of the platform. By using the platform, an organization and its authorized users agree to use the service lawfully, protect account credentials, and comply with applicable regulations.\n\nThe platform is provided according to the subscribed plan and applicable service limits. Accounts must not be used to abuse, disrupt, reverse engineer, or gain unauthorized access to the service. Organizations remain responsible for their users, submitted data, and maintaining appropriate security controls.\n\nWe may update these terms when necessary. Material changes will be communicated through the platform. Continued use after the effective date constitutes acceptance of the updated terms. Contact support for questions about these terms.",
            ],
            [
                'document_type' => 'dpa',
                'title' => 'Data Processing Agreement',
                'version' => '1.0',
                'content' => "# Data Processing Agreement\n\nThis Data Processing Agreement describes how the platform processes personal data on behalf of an organization using the service. The organization remains responsible for lawful collection and instructions, while the platform processes data only to provide, secure, support, and improve the contracted service.\n\nThe platform will maintain reasonable technical and organizational safeguards, restrict personnel access, assist with applicable data requests, and notify the organization of qualifying incidents as required by applicable law. Subprocessors will be subject to appropriate contractual protections.",
            ],
            [
                'document_type' => 'tenant_agreement',
                'title' => 'Tenant Agreement',
                'version' => '1.0',
                'content' => "# Tenant Agreement\n\nThis Tenant Agreement governs an organization’s subscription and use of its isolated workspace on the platform. The organization may provision authorized users, configure supported integrations, and use the features included in its selected plan.\n\nThe organization must keep credentials confidential, ensure users follow applicable policies, and pay fees according to the subscribed plan, subject to the applicable service terms.",
            ],            [
                'document_type' => 'privacy_policy',
                'title' => 'Privacy Policy',
                'version' => '1.0',
                'content' => "# Privacy Policy\n\nThis Privacy Policy explains how the platform collects, uses, stores, and protects information provided by organizations and users. Information may include account details, contact information, usage data, security events, and operational logs needed to provide and secure the service.\n\nWe use this information to operate the platform, authenticate users, provide support, process subscriptions, prevent abuse, maintain auditability, and improve reliability. We do not use organization data for unrelated purposes. Access is limited to authorized personnel and service providers who need it to perform platform operations.\n\nWe retain information for as long as needed for service delivery, security, legal, and accounting purposes. Organizations may contact support to request access, correction, or clarification regarding personal information, subject to applicable law and verification requirements.\n\nThis policy may be updated periodically. The current version and effective date are shown in the platform.",
            ],
        ];

        foreach ($documents as $document) {
            $existing = DB::table('legal_documents')->where('document_type', $document['document_type'])->where('version', $document['version'])->first();
            $values = $document + ['status' => 'published', 'published_at' => $now, 'created_by' => $creatorId, 'updated_at' => $now];
            if ($existing) {
                DB::table('legal_documents')->where('id', $existing->id)->update($values);
                continue;
            }
            DB::table('legal_documents')->insert($values + ['uuid' => (string) Str::uuid(), 'created_at' => $now]);
        }
    }
}