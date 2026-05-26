<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->default('package');
            $table->string('package_slug')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GHS');
            $table->unsignedInteger('amount_pesewas');
            $table->string('paystack_reference')->unique();
            $table->string('paystack_access_code')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('channel')->nullable();
            $table->json('metadata')->nullable();
            $table->json('paystack_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('package_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('package_slug');
            $table->string('package_name');
            $table->unsignedInteger('data_limit_mb');
            $table->unsignedSmallInteger('speed_mbps')->nullable();
            $table->timestamp('activated_at');
            $table->timestamp('expires_at');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_purchases');
        Schema::dropIfExists('transactions');
    }
};
