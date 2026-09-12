<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformDummyDesignationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $designations = [["Chief Operating Officer", "PL-DES-COO", 1], ["Director of Customer Success", "PL-DES-DIR-CS", 2], ["Support Manager", "PL-DES-SUP-MGR", 3], ["Finance Manager", "PL-DES-FIN-MGR", 3], ["Engineering Manager", "PL-DES-ENG-MGR", 3], ["Sales Manager", "PL-DES-SALES-MGR", 3], ["Compliance Officer", "PL-DES-COMP-OFF", 4], ["Customer Success Specialist", "PL-DES-CS-SPEC", 5], ["Support Specialist", "PL-DES-SUP-SPEC", 5], ["Billing Specialist", "PL-DES-BILL-SPEC", 5], ["Operations Analyst", "PL-DES-OPS-ANL", 5], ["Content Strategist", "PL-DES-CONT-STR", 5]];
        DB::table("platform_designations")->upsert(array_map(fn ($d) => ["uuid" => (string) Str::uuid(), "name" => $d[0], "code" => $d[1], "description" => $d[0]." role for platform testing.", "level" => $d[2], "status" => "active", "created_at" => $now, "updated_at" => $now], $designations), ["code"], ["name", "description", "level", "status", "updated_at"]);
    }
}