<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformDummyDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $departments = [["Executive Office", "PL-DEPT-EXEC", null], ["Operations", "PL-DEPT-OPS", "PL-DEPT-EXEC"], ["Customer Success", "PL-DEPT-CS", "PL-DEPT-OPS"], ["Support", "PL-DEPT-SUPPORT", "PL-DEPT-OPS"], ["Finance", "PL-DEPT-FIN", "PL-DEPT-EXEC"], ["Engineering", "PL-DEPT-ENG", "PL-DEPT-EXEC"], ["Sales", "PL-DEPT-SALES", "PL-DEPT-OPS"], ["Compliance", "PL-DEPT-COMP", "PL-DEPT-EXEC"], ["Content and Enablement", "PL-DEPT-CONTENT", "PL-DEPT-OPS"]];
        foreach ($departments as $d) {
            DB::table("platform_departments")->upsert([["uuid" => (string) Str::uuid(), "name" => $d[0], "code" => $d[1], "parent_id" => $d[2] ? DB::table("platform_departments")->where("code", $d[2])->value("id") : null, "status" => "active", "created_at" => $now, "updated_at" => $now]], ["code"], ["name", "parent_id", "status", "updated_at"]);
        }
    }
}