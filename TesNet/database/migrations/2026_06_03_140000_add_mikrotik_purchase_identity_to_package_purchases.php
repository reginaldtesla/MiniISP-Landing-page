<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('package_purchases', 'mikrotik_username')) {
                $table->string('mikrotik_username', 64)->nullable()->after('status');
            }

            if (! Schema::hasColumn('package_purchases', 'mikrotik_password')) {
                $table->text('mikrotik_password')->nullable()->after('mikrotik_username');
            }

            if (! Schema::hasColumn('package_purchases', 'mikrotik_profile')) {
                $table->string('mikrotik_profile', 64)->nullable()->after('mikrotik_password');
            }

            if (! Schema::hasColumn('package_purchases', 'mikrotik_synced_at')) {
                $table->timestamp('mikrotik_synced_at')->nullable()->after('mikrotik_profile');
            }
        });
    }

    public function down(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            $columns = ['mikrotik_username', 'mikrotik_password', 'mikrotik_profile', 'mikrotik_synced_at'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('package_purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
