<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $columns = DB::select('SHOW COLUMNS FROM transactions');
        $columnNames = array_column($columns, 'Field');

        if (!in_array('from_village_id', $columnNames)) {
            DB::statement('ALTER TABLE transactions ADD COLUMN from_village_id BIGINT UNSIGNED NULL AFTER courier_id');
        }

        if (!in_array('to_village_id', $columnNames)) {
            DB::statement('ALTER TABLE transactions ADD COLUMN to_village_id BIGINT UNSIGNED NULL AFTER from_village_id');
        }

        try {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_from_village_id_foreign FOREIGN KEY (from_village_id) REFERENCES villages(id) ON DELETE SET NULL');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_to_village_id_foreign FOREIGN KEY (to_village_id) REFERENCES villages(id) ON DELETE SET NULL');
        } catch (\Exception $e) {}

        if (in_array('from_district_id', $columnNames)) {
            try { DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_from_district_id_foreign'); } catch (\Exception $e) {}
            DB::statement('ALTER TABLE transactions DROP COLUMN from_district_id');
        }
        if (in_array('to_district_id', $columnNames)) {
            try { DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_to_district_id_foreign'); } catch (\Exception $e) {}
            DB::statement('ALTER TABLE transactions DROP COLUMN to_district_id');
        }
    }

    public function down(): void
    {
        try { DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_from_village_id_foreign'); } catch (\Exception $e) {}
        try { DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_to_village_id_foreign'); } catch (\Exception $e) {}

        $columns = DB::select('SHOW COLUMNS FROM transactions');
        $columnNames = array_column($columns, 'Field');

        if (in_array('from_village_id', $columnNames)) {
            DB::statement('ALTER TABLE transactions DROP COLUMN from_village_id');
        }
        if (in_array('to_village_id', $columnNames)) {
            DB::statement('ALTER TABLE transactions DROP COLUMN to_village_id');
        }

        if (in_array('from_district_id', $columnNames)) {
            try { DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_from_district_id_foreign'); } catch (\Exception $e) {}
            DB::statement('ALTER TABLE transactions DROP COLUMN from_district_id');
        }
        if (in_array('to_district_id', $columnNames)) {
            try { DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_to_district_id_foreign'); } catch (\Exception $e) {}
            DB::statement('ALTER TABLE transactions DROP COLUMN to_district_id');
        }
    }
};