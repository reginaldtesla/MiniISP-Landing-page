<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('package_purchases', 'bytes_consumed')) {
                $table->unsignedBigInteger('bytes_consumed')->default(0)->after('data_limit_bytes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('package_purchases', 'bytes_consumed')) {
                $table->dropColumn('bytes_consumed');
            }
        });
    }
};
