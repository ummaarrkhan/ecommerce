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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 50)->default('cod')->after('payment_status');
            $table->string('payment_reference', 150)->nullable()->after('payment_method');
            $table->string('carrier', 100)->nullable()->after('notes');
            $table->string('tracking_number', 150)->nullable()->after('carrier');
            $table->string('tracking_url', 500)->nullable()->after('tracking_number');
            $table->timestamp('shipped_at')->nullable()->after('tracking_url');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->date('estimated_delivery')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_reference',
                'carrier',
                'tracking_number',
                'tracking_url',
                'shipped_at',
                'delivered_at',
                'estimated_delivery',
            ]);
        });
    }
};
