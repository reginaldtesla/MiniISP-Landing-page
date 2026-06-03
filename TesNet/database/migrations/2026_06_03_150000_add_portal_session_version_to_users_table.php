<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'portal_session_version')) {
                $table->unsignedInteger('portal_session_version')->default(0)->after('device_limit');
            }
        });

        if (Schema::hasColumn('users', 'device_limit')) {
            DB::table('users')->where('is_admin', false)->update(['device_limit' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'portal_session_version')) {
                $table->dropColumn('portal_session_version');
            }
        });
    }
};
