<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FreeRADIUS 3 default MySQL queries expect IPv6 + class columns on radacct.
 * Without them, accounting INSERT/UPDATE fails and portal usage stays at zero.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('radacct')) {
            return;
        }

        Schema::table('radacct', function (Blueprint $table) {
            if (! Schema::hasColumn('radacct', 'framedipv6address')) {
                $table->string('framedipv6address', 45)->default('')->after('framedipaddress');
            }

            if (! Schema::hasColumn('radacct', 'framedipv6prefix')) {
                $table->string('framedipv6prefix', 45)->default('')->after('framedipv6address');
            }

            if (! Schema::hasColumn('radacct', 'framedinterfaceid')) {
                $table->string('framedinterfaceid', 44)->default('')->after('framedipv6prefix');
            }

            if (! Schema::hasColumn('radacct', 'delegatedipv6prefix')) {
                $table->string('delegatedipv6prefix', 45)->default('')->after('framedinterfaceid');
            }

            if (! Schema::hasColumn('radacct', 'class')) {
                $table->string('class', 64)->nullable()->after('delegatedipv6prefix');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('radacct')) {
            return;
        }

        Schema::table('radacct', function (Blueprint $table) {
            $columns = [
                'class',
                'delegatedipv6prefix',
                'framedinterfaceid',
                'framedipv6prefix',
                'framedipv6address',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('radacct', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
