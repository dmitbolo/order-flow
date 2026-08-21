<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE warehouse_product
            ADD CONSTRAINT warehouse_product_stock_quantity_non_negative
            CHECK (stock_quantity >= 0)
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE stock_movements
            ADD CONSTRAINT stock_movements_quantity_change_non_zero
            CHECK (quantity_change <> 0)
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE stock_movements
            ADD CONSTRAINT stock_movements_quantity_consistent
            CHECK (
                quantity_before >= 0
                AND quantity_after >= 0
                AND quantity_after = quantity_before + quantity_change
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT stock_movements_quantity_consistent');
        DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT stock_movements_quantity_change_non_zero');
        DB::statement('ALTER TABLE warehouse_product DROP CONSTRAINT warehouse_product_stock_quantity_non_negative');
    }
};
