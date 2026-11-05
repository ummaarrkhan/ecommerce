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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Customer who placed the order; guests are allowed.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Unique order reference shown to the customer.
            $table->string('order_number')->unique();

            // Customer contact and shipping information.
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->string('shipping_address');
            $table->string('shipping_city', 100);
            $table->string('shipping_postcode', 30);
            $table->string('shipping_country', 100);

            // Financial totals.
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            // Order and payment statuses.
            $table->string('status', 30)->default('pending');
            $table->string('payment_status', 30)->default('unpaid');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
