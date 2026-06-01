<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_payment_requests', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('manual_payment_requests', function (Blueprint $table) {
            $table->dropColumn('proof_path');
        });
    }
};
