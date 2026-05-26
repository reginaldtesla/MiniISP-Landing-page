<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('data_limit_bytes')->nullable()->after('data_limit_mb');
        });
    }

    public function down(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            $table->dropColumn('data_limit_bytes');
        });
    }
};
