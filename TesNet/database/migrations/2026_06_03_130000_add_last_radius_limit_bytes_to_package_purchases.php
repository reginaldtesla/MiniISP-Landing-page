<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('package_purchases', 'last_radius_limit_bytes')) {
                $table->unsignedBigInteger('last_radius_limit_bytes')->default(0)->after('bytes_consumed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('package_purchases', 'last_radius_limit_bytes')) {
                $table->dropColumn('last_radius_limit_bytes');
            }
        });
    }
};
