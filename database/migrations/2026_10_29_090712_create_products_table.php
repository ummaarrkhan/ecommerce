<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('sku', 100)->unique();

            $table->text('description')->nullable();

            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();

            $table->unsignedInteger('stock_quantity')->default(0);

            $table->string('image')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
