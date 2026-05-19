<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = Schema::getColumnListing('transactions');

        try {
            DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_from_village_id_foreign');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_to_village_id_foreign');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE transactions DROP FOREIGN KEY transactions_courier_id_foreign');
        } catch (\Throwable $e) {
        }

        Schema::table('transactions', function (Blueprint $table) use ($columns) {
            if (in_array('from_village_id', $columns, true)) {
                $table->dropColumn('from_village_id');
            }

            if (in_array('to_village_id', $columns, true)) {
                $table->dropColumn('to_village_id');
            }

            if (in_array('courier_id', $columns, true)) {
                $table->dropColumn('courier_id');
            }
        });
    }

    public function down(): void
    {
        $columns = Schema::getColumnListing('transactions');

        Schema::table('transactions', function (Blueprint $table) use ($columns) {
            if (! in_array('courier_id', $columns, true)) {
                $table->unsignedBigInteger('courier_id')->nullable()->after('shipping_cost');
            }

            if (! in_array('from_village_id', $columns, true)) {
                $table->unsignedBigInteger('from_village_id')->nullable()->after('courier_id');
            }

            if (! in_array('to_village_id', $columns, true)) {
                $table->unsignedBigInteger('to_village_id')->nullable()->after('from_village_id');
            }
        });

        try {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_courier_id_foreign FOREIGN KEY (courier_id) REFERENCES couriers(id) ON DELETE SET NULL');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_from_village_id_foreign FOREIGN KEY (from_village_id) REFERENCES villages(id) ON DELETE SET NULL');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_to_village_id_foreign FOREIGN KEY (to_village_id) REFERENCES villages(id) ON DELETE SET NULL');
        } catch (\Throwable $e) {
        }
    }
};
