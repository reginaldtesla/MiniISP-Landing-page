<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('package_slug');
            $table->string('package_name');
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('amount_pesewas');
            $table->string('status', 20)->default('available');
            $table->text('admin_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('package_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_vouchers');
    }
};
