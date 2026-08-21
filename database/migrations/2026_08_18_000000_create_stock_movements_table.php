<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32);
            $table->integer('quantity_change');
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->string('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['warehouse_id', 'product_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        DB::table('warehouse_product')
            ->where('stock_quantity', '>', 0)
            ->orderBy('id')
            ->chunkById(500, function ($positions): void {
                $now = now();

                DB::table('stock_movements')->insert(
                    $positions->map(fn ($position): array => [
                        'warehouse_id' => $position->warehouse_id,
                        'product_id' => $position->product_id,
                        'type' => 'initial_balance',
                        'quantity_change' => $position->stock_quantity,
                        'quantity_before' => 0,
                        'quantity_after' => $position->stock_quantity,
                        'comment' => 'Остаток на момент включения журнала',
                        'created_at' => $now,
                    ])->all(),
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
