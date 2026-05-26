<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number', 20)->nullable()->unique()->after('id');
            $table->unsignedTinyInteger('device_limit')->default(1)->after('password');
            $table->boolean('is_admin')->default(false)->after('device_limit');
            $table->decimal('wallet_balance', 10, 2)->default(0)->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'device_limit', 'is_admin', 'wallet_balance']);
        });
    }
};
