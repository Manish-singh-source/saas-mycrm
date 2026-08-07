<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait SeedsRecords
{
    /**
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $values
     */
    protected function seedRecord(string $table, array $identity, array $values, bool $withUuid = false): int
    {
        $existing = DB::table($table)->where($identity)->first();
        $now = now();

        $values = $this->withTimestamps($table, $values, $now);

        if ($existing !== null) {
            DB::table($table)->where('id', $existing->id)->update($values);

            return (int) $existing->id;
        }

        if ($withUuid) {
            $values['uuid'] = (string) Str::uuid();
        }

        $insert = array_merge($identity, $values);
        $insert = $this->withTimestamps($table, $insert, $now);

        return (int) DB::table($table)->insertGetId($insert);
    }

    /**
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $values
     */
    protected function seedPivot(string $table, array $identity, array $values = []): void
    {
        if (DB::table($table)->where($identity)->exists()) {
            if ($values !== []) {
                DB::table($table)->where($identity)->update($values);
            }

            return;
        }

        DB::table($table)->insert(array_merge($identity, $values));
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function withTimestamps(string $table, array $values, mixed $now): array
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($table);

        if (in_array('created_at', $columns, true) && ! array_key_exists('created_at', $values)) {
            $values['created_at'] = $now;
        }

        if (in_array('updated_at', $columns, true) && ! array_key_exists('updated_at', $values)) {
            $values['updated_at'] = $now;
        }

        return $values;
    }
}