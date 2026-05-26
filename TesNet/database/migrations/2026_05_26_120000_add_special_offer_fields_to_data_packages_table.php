<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('data_packages', 'is_special_offer')) {
                $table->boolean('is_special_offer')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('data_packages', 'special_starts_at')) {
                $table->timestamp('special_starts_at')->nullable()->after('is_special_offer');
            }
            if (! Schema::hasColumn('data_packages', 'special_ends_at')) {
                $table->timestamp('special_ends_at')->nullable()->after('special_starts_at');
            }
            if (! Schema::hasColumn('data_packages', 'promo_label')) {
                $table->string('promo_label', 64)->nullable()->after('special_ends_at');
            }
        });

        Schema::table('data_packages', function (Blueprint $table) {
            if (! $this->indexExists('data_packages', 'data_packages_special_offer_idx')) {
                $table->index(['is_special_offer', 'special_starts_at', 'special_ends_at'], 'data_packages_special_offer_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_packages', function (Blueprint $table) {
            if ($this->indexExists('data_packages', 'data_packages_special_offer_idx')) {
                $table->dropIndex('data_packages_special_offer_idx');
            }

            $columns = ['promo_label', 'special_ends_at', 'special_starts_at', 'is_special_offer'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('data_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $definition) {
            if (($definition['name'] ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};
