<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_packages', function (Blueprint $table) {
            $table->string('validity_type', 20)->default('days')->after('validity_days');
        });

        Schema::table('package_purchases', function (Blueprint $table) {
            $table->string('validity_type', 20)->default('days')->after('speed_mbps');
        });

        DB::table('data_packages')->update(['validity_type' => 'days']);

        DB::table('package_purchases')
            ->whereNull('expires_at')
            ->update(['validity_type' => 'until_finished']);
    }

    public function down(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            $table->dropColumn('validity_type');
        });

        Schema::table('data_packages', function (Blueprint $table) {
            $table->dropColumn('validity_type');
        });
    }
};
