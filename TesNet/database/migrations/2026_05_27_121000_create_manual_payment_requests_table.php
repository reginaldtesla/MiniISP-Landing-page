<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            $table->string('type', 32); // package|custom_data
            $table->string('status', 32)->default('pending'); // pending|approved|rejected

            $table->string('package_slug')->nullable();
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('amount_pesewas');

            $table->string('payment_method', 32)->default('momo'); // momo|airtime|cash|other
            $table->string('provider', 64)->nullable(); // MTN|Vodafone|AirtelTigo|etc
            $table->string('payer_phone', 32)->nullable();
            $table->string('reference', 128)->nullable();

            $table->text('note')->nullable(); // student note
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->json('metadata')->nullable(); // for custom_data quote + any extra

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['type', 'package_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_payment_requests');
    }
};

