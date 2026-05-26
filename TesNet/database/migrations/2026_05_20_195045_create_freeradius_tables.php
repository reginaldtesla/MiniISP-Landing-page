<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('radcheck')) {
            Schema::create('radcheck', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username', 64)->default('')->index();
                $table->string('attribute', 64)->default('');
                $table->char('op', 2)->default('==');
                $table->string('value', 253)->default('');
            });
        }

        if (! Schema::hasTable('radreply')) {
            Schema::create('radreply', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username', 64)->default('')->index();
                $table->string('attribute', 64)->default('');
                $table->char('op', 2)->default('=');
                $table->string('value', 253)->default('');
            });
        }

        if (! Schema::hasTable('radacct')) {
            Schema::create('radacct', function (Blueprint $table) {
                $table->bigIncrements('radacctid');
                $table->string('acctsessionid', 64)->default('');
                $table->string('acctuniqueid', 32)->unique();
                $table->string('username', 64)->default('')->index();
                $table->string('realm', 64)->default('');
                $table->string('nasipaddress', 15)->default('');
                $table->string('nasportid', 32)->nullable();
                $table->string('nasporttype', 32)->nullable();
                $table->dateTime('acctstarttime')->nullable()->index();
                $table->dateTime('acctupdatetime')->nullable();
                $table->dateTime('acctstoptime')->nullable()->index();
                $table->integer('acctinterval')->nullable();
                $table->integer('acctsessiontime')->unsigned()->nullable();
                $table->string('acctauthentic', 32)->nullable();
                $table->string('connectinfo_start', 128)->nullable();
                $table->string('connectinfo_stop', 128)->nullable();
                $table->bigInteger('acctinputoctets')->nullable();
                $table->bigInteger('acctoutputoctets')->nullable();
                $table->string('calledstationid', 50)->default('');
                $table->string('callingstationid', 50)->default('');
                $table->string('acctterminatecause', 32)->default('');
                $table->string('servicetype', 32)->nullable();
                $table->string('framedprotocol', 32)->nullable();
                $table->string('framedipaddress', 15)->default('');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('radacct');
        Schema::dropIfExists('radreply');
        Schema::dropIfExists('radcheck');
    }
};
